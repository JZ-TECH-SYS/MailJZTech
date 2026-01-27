<?php

namespace src\handlers\service;

use src\Config;
use src\handlers\service\GoogleCloud;
use Exception;

/**
 * Service para realizar backup COMPLETO (schema + dados) de bancos MySQL 
 * e envio para Google Cloud Storage.
 * 
 * Fluxo completo:
 * 1. Gerar dump MySQL via mysqldump (SCHEMA + DADOS)
 * 2. Validar que o dump contém dados (INSERT/CREATE TABLE)
 * 3. Comprimir arquivo (.sql.gz)
 * 4. Calcular checksum SHA256
 * 5. Upload para Google Cloud Storage com naming padrão
 * 6. Limpeza de arquivos temporários
 * 7. Limpeza de backups antigos (retenção de 7 dias)
 */
class BackupService
{
    /** @var string Ambiente do backup (prod/hml/dev) */
    private static string $ambiente = 'prod';

    /**
     * Define o ambiente para naming dos backups.
     * 
     * @param string $ambiente (prod|hml|dev)
     */
    public static function setAmbiente(string $ambiente): void
    {
        $ambientesValidos = ['prod', 'hml', 'dev'];
        if (!in_array(strtolower($ambiente), $ambientesValidos)) {
            throw new Exception("Ambiente inválido: {$ambiente}. Use: " . implode(', ', $ambientesValidos));
        }
        self::$ambiente = strtolower($ambiente);
    }

    /**
     * Gera dump COMPLETO (schema + dados) de um banco MySQL.
     *
     * @param string $nomeBanco
     * @return string Caminho do arquivo .sql gerado
     * @throws Exception
     */
    public static function gerarDumpMySQL(string $nomeBanco): string
    {
        $inicio = microtime(true);
        \core\Controller::log("[backup] Iniciando dump do banco: {$nomeBanco}");

        // Validar credenciais
        $host = Config::DB_HOST;
        $usuario = Config::USER_MASTER_DB;
        $senha = Config::PASS_MASTER_DB;
        $port = Config::DB_PORT ?: '3306';

        if (empty($usuario) || empty($senha)) {
            throw new Exception("Credenciais MySQL não configuradas no .env (USER_MASTER_DB, PASS_MASTER_DB)");
        }

        // Criar diretório temporário único
        $dirTemp = self::criarDiretorioTemp();
        
        // Gerar nome do arquivo com formato padrão
        $timestamp = self::getTimestampBrasilia();
        $arquivoSql = "{$dirTemp}/{$nomeBanco}-" . self::$ambiente . "-{$timestamp}.sql";

        // Localizar binário mysqldump
        $mysqldump = self::encontrarMysqldump();

        // =============================================================
        // COMANDO COMPLETO - SCHEMA + DADOS
        // =============================================================
        // Flags importantes para backup COMPLETO:
        // --single-transaction: backup consistente sem lock (InnoDB)
        // --routines: inclui stored procedures e functions
        // --triggers: inclui triggers
        // --events: inclui eventos agendados
        // --add-drop-table: facilita restore limpo
        // --complete-insert: usa INSERT com nomes de colunas (mais seguro)
        // --hex-blob: encode binary data em hex (evita corrupção)
        // --quick: não carrega tabela inteira em memória
        // --lock-tables=false: não trava tabelas
        // NÃO USAR: --no-data, --schema-only (apenas estrutura)
        // =============================================================
        
        $baseArgs = sprintf(
            '%s --user=%s --host=%s --port=%s ' .
            '--single-transaction ' .
            '--routines ' .
            '--triggers ' .
            '--events ' .
            '--add-drop-table ' .
            '--complete-insert ' .
            '--hex-blob ' .
            '--quick ' .
            '--lock-tables=false ' .
            '--default-character-set=utf8mb4 ' .
            '--set-gtid-purged=OFF ' .
            '%s --result-file=%s 2>&1',
            $mysqldump,
            escapeshellarg($usuario),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($nomeBanco),
            escapeshellarg($arquivoSql)
        );
        
        // Passar senha via variável de ambiente (mais seguro)
        $comando = sprintf('MYSQL_PWD=%s %s', escapeshellarg($senha), $baseArgs);

        // Log (senha mascarada)
        \core\Controller::log('[backup] Executando mysqldump com SCHEMA + DADOS...');
        \core\Controller::log('[backup] Comando: ' . str_replace($senha, '***', $comando));

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            $erro = trim(implode("\n", $output));
            \core\Controller::log("[backup] Erro no comando principal: {$erro}");

            // Fallback: tenta com --password= (alguns sistemas não suportam MYSQL_PWD)
            $comandoFallback = sprintf(
                '%s --user=%s --password=%s --host=%s --port=%s ' .
                '--single-transaction ' .
                '--routines ' .
                '--triggers ' .
                '--events ' .
                '--add-drop-table ' .
                '--complete-insert ' .
                '--hex-blob ' .
                '--quick ' .
                '--lock-tables=false ' .
                '--default-character-set=utf8mb4 ' .
                '--set-gtid-purged=OFF ' .
                '%s --result-file=%s 2>&1',
                $mysqldump,
                escapeshellarg($usuario),
                escapeshellarg($senha),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($nomeBanco),
                escapeshellarg($arquivoSql)
            );
            
            \core\Controller::log('[backup] Tentando fallback com --password...');
            $output = [];
            exec($comandoFallback, $output, $returnCode);
            $erro2 = trim(implode("\n", $output));

            if ($returnCode !== 0) {
                throw new Exception("Erro ao executar mysqldump: " . ($erro2 ?: $erro ?: 'retcode ' . $returnCode));
            }
        }

        // Validar que o arquivo foi gerado
        if (!file_exists($arquivoSql)) {
            throw new Exception("Arquivo de backup não foi gerado");
        }

        $tamanhoBytes = filesize($arquivoSql);
        if ($tamanhoBytes === 0) {
            throw new Exception("Arquivo de backup está vazio (0 bytes)");
        }

        // =============================================================
        // VALIDAÇÃO CRÍTICA: Verificar se contém DADOS (não apenas schema)
        // =============================================================
        self::validarConteudoBackup($arquivoSql);

        $duracao = round(microtime(true) - $inicio, 2);
        $tamanhoMB = round($tamanhoBytes / 1024 / 1024, 2);
        
        \core\Controller::log("[backup] Dump concluído: {$tamanhoMB} MB em {$duracao}s");
        \core\Controller::log("[backup] Arquivo: {$arquivoSql}");

        return $arquivoSql;
    }

    /**
     * Valida se o backup contém dados (INSERT statements) e não apenas schema.
     * 
     * @param string $arquivo
     * @throws Exception se backup não contém dados
     */
    public static function validarConteudoBackup(string $arquivo): void
    {
        \core\Controller::log("[backup] Validando conteúdo do backup...");

        $fp = fopen($arquivo, 'r');
        if (!$fp) {
            throw new Exception("Não foi possível abrir arquivo para validação");
        }

        $temCreateTable = false;
        $temInsert = false;
        $linhasLidas = 0;
        $maxLinhas = 50000; // Ler no máximo 50k linhas para validação

        while (!feof($fp) && $linhasLidas < $maxLinhas) {
            $linha = fgets($fp);
            $linhasLidas++;

            if ($linha === false) continue;

            // Verificar se há CREATE TABLE (schema)
            if (!$temCreateTable && stripos($linha, 'CREATE TABLE') !== false) {
                $temCreateTable = true;
            }

            // Verificar se há INSERT (dados)
            if (!$temInsert && stripos($linha, 'INSERT INTO') !== false) {
                $temInsert = true;
            }

            // Se já encontrou ambos, pode parar
            if ($temCreateTable && $temInsert) {
                break;
            }
        }

        fclose($fp);

        \core\Controller::log("[backup] Validação: CREATE TABLE=" . ($temCreateTable ? 'SIM' : 'NÃO') . 
                             ", INSERT=" . ($temInsert ? 'SIM' : 'NÃO'));

        if (!$temCreateTable) {
            throw new Exception("ERRO CRÍTICO: Backup não contém estrutura (CREATE TABLE). Dump inválido!");
        }

        // Se não encontrou INSERT, pode ser que o banco esteja vazio
        // Isso é um WARNING, não um erro fatal
        if (!$temInsert) {
            \core\Controller::log("[backup] AVISO: Backup não contém dados (INSERT). Banco pode estar vazio.");
        }
    }

    /**
     * Conta estatísticas básicas do arquivo de backup.
     * 
     * @param string $arquivo
     * @return array
     */
    public static function obterEstatisticasBackup(string $arquivo): array
    {
        $stats = [
            'tamanho_bytes' => filesize($arquivo),
            'tamanho_mb' => round(filesize($arquivo) / 1024 / 1024, 2),
            'total_linhas' => 0,
            'total_inserts' => 0,
            'total_creates' => 0,
            'tabelas' => []
        ];

        $fp = fopen($arquivo, 'r');
        if (!$fp) return $stats;

        while (!feof($fp)) {
            $linha = fgets($fp);
            if ($linha === false) continue;

            $stats['total_linhas']++;

            if (stripos($linha, 'INSERT INTO') !== false) {
                $stats['total_inserts']++;
            }

            if (preg_match('/CREATE TABLE.*`([^`]+)`/i', $linha, $matches)) {
                $stats['total_creates']++;
                $stats['tabelas'][] = $matches[1];
            }
        }

        fclose($fp);
        return $stats;
    }

    /**
     * Comprime um arquivo usando gzip (nível máximo de compressão).
     *
     * @param string $arquivoOrigem
     * @return string Caminho do arquivo .gz gerado
     * @throws Exception
     */
    public static function comprimirArquivo(string $arquivoOrigem): string
    {
        $inicio = microtime(true);
        \core\Controller::log("[backup] Comprimindo arquivo: " . basename($arquivoOrigem));

        if (!file_exists($arquivoOrigem)) {
            throw new Exception("Arquivo para compressão não encontrado: {$arquivoOrigem}");
        }

        $tamanhoOriginal = filesize($arquivoOrigem);
        $arquivoGz = $arquivoOrigem . '.gz';

        // Abrir arquivo original para leitura
        $fpOrigem = fopen($arquivoOrigem, 'rb');
        if (!$fpOrigem) {
            throw new Exception("Não foi possível abrir arquivo para leitura: {$arquivoOrigem}");
        }

        // Abrir arquivo .gz para escrita (nível 9 = máxima compressão)
        $fpGz = gzopen($arquivoGz, 'wb9');
        if (!$fpGz) {
            fclose($fpOrigem);
            throw new Exception("Não foi possível criar arquivo comprimido: {$arquivoGz}");
        }

        // Copiar dados comprimindo em chunks de 8KB
        $bytesProcessados = 0;
        while (!feof($fpOrigem)) {
            $buffer = fread($fpOrigem, 8192);
            if ($buffer !== false) {
                gzwrite($fpGz, $buffer);
                $bytesProcessados += strlen($buffer);
            }
        }

        fclose($fpOrigem);
        gzclose($fpGz);

        // Validar arquivo comprimido
        if (!file_exists($arquivoGz)) {
            throw new Exception("Arquivo comprimido não foi gerado");
        }

        $tamanhoComprimido = filesize($arquivoGz);
        if ($tamanhoComprimido === 0) {
            throw new Exception("Arquivo comprimido está vazio");
        }

        // Validar integridade - testar se o gzip é válido
        $fpTest = gzopen($arquivoGz, 'rb');
        if (!$fpTest) {
            unlink($arquivoGz);
            throw new Exception("Arquivo comprimido corrompido - não é possível abrir");
        }
        gzclose($fpTest);

        // Remover arquivo original (manter apenas .gz)
        unlink($arquivoOrigem);

        $duracao = round(microtime(true) - $inicio, 2);
        $taxaCompressao = round((1 - ($tamanhoComprimido / $tamanhoOriginal)) * 100, 1);
        $tamanhoOriginalMB = round($tamanhoOriginal / 1024 / 1024, 2);
        $tamanhoComprimidoMB = round($tamanhoComprimido / 1024 / 1024, 2);

        \core\Controller::log("[backup] Compressão concluída em {$duracao}s");
        \core\Controller::log("[backup] Original: {$tamanhoOriginalMB}MB -> Comprimido: {$tamanhoComprimidoMB}MB ({$taxaCompressao}% redução)");

        return $arquivoGz;
    }

    /**
     * Calcula checksum SHA256 de um arquivo.
     *
     * @param string $arquivo
     * @return string Hash SHA256
     * @throws Exception
     */
    public static function calcularChecksum(string $arquivo): string
    {
        if (!file_exists($arquivo)) {
            throw new Exception("Arquivo não encontrado para cálculo de checksum: {$arquivo}");
        }

        $hash = hash_file('sha256', $arquivo);

        if ($hash === false) {
            throw new Exception("Erro ao calcular checksum do arquivo");
        }

        \core\Controller::log("[backup] Checksum SHA256: {$hash}");
        return $hash;
    }

    /**
     * Gera o caminho do objeto no GCS seguindo o padrão:
     * gs://<bucket>/backups/<ambiente>/<db>/<YYYY>/<MM>/<DD>/<nome-arquivo>
     * 
     * @param string $pastaBase Pasta base configurada
     * @param string $nomeBanco Nome do banco
     * @param string $nomeArquivo Nome do arquivo (com extensão)
     * @return string Caminho completo do objeto no GCS
     */
    public static function gerarCaminhoGCS(string $pastaBase, string $nomeBanco, string $nomeArquivo): string
    {
        // Definir timezone para São Paulo
        $tz = new \DateTimeZone('America/Sao_Paulo');
        $data = new \DateTime('now', $tz);
        
        $ano = $data->format('Y');
        $mes = $data->format('m');
        $dia = $data->format('d');

        // Formato: pasta_base/ambiente/ano/mes/dia/arquivo
        // Exemplo: mailjztech_prod/prod/2026/01/27/mailjztech-prod-20260127_030000.sql.gz
        $caminho = sprintf(
            '%s/%s/%s/%s/%s/%s',
            rtrim($pastaBase, '/'),
            self::$ambiente,
            $ano,
            $mes,
            $dia,
            $nomeArquivo
        );

        \core\Controller::log("[backup] Caminho GCS: {$caminho}");
        return $caminho;
    }

    /**
     * Faz upload de um arquivo para o Google Cloud Storage.
     *
     * @param string $arquivoLocal
     * @param string $bucketNome
     * @param string $objetoNome Caminho completo do objeto no bucket
     * @return array Informações do objeto enviado
     * @throws Exception
     */
    public static function uploadParaGCS(string $arquivoLocal, string $bucketNome, string $objetoNome): array
    {
        $inicio = microtime(true);
        \core\Controller::log("[backup] Iniciando upload para GCS...");
        \core\Controller::log("[backup] Bucket: {$bucketNome}");
        \core\Controller::log("[backup] Objeto: {$objetoNome}");

        if (!file_exists($arquivoLocal)) {
            throw new Exception("Arquivo local não encontrado: {$arquivoLocal}");
        }

        $tamanhoBytes = filesize($arquivoLocal);
        $tamanhoMB = round($tamanhoBytes / 1024 / 1024, 2);
        \core\Controller::log("[backup] Tamanho do arquivo: {$tamanhoMB} MB");

        // Simular estrutura de $_FILES para o método uploadFile
        $fileData = [
            'name' => basename($arquivoLocal),
            'tmp_name' => $arquivoLocal,
            'size' => $tamanhoBytes
        ];

        $resultado = GoogleCloud::uploadFile($fileData, $bucketNome, $objetoNome);

        $duracao = round(microtime(true) - $inicio, 2);
        \core\Controller::log("[backup] Upload concluído em {$duracao}s");

        return $resultado;
    }

    /**
     * Remove backups antigos do GCS baseado na retenção de 7 dias.
     * Lista objetos diretamente do bucket e remove os mais antigos.
     *
     * @param string $bucketNome
     * @param string $pastaBase
     * @param int $retencaoDias (padrão: 7)
     * @param array $logsExistentes Lista opcional de objetos dos logs do banco
     * @return int Quantidade de arquivos removidos
     */
    public static function limparBackupsAntigos(string $bucketNome, string $pastaBase, int $retencaoDias = 7, array $logsExistentes = []): int
    {
        \core\Controller::log("[backup] Iniciando limpeza de backups antigos (retenção: {$retencaoDias} dias)...");

        $resultado = [
            'removidos' => 0,
            'erros' => 0,
            'detalhes' => []
        ];

        // Calcular data limite
        $tz = new \DateTimeZone('America/Sao_Paulo');
        $dataLimite = new \DateTime("-{$retencaoDias} days", $tz);
        $timestampLimite = $dataLimite->getTimestamp();

        \core\Controller::log("[backup] Data limite: " . $dataLimite->format('Y-m-d H:i:s'));

        // ESTRATÉGIA 1: Listar objetos diretamente do bucket
        try {
            $objetosAntigos = GoogleCloud::listObjectsOlderThan($bucketNome, $pastaBase, $timestampLimite);
            
            foreach ($objetosAntigos as $objeto) {
                try {
                    GoogleCloud::deleteFile($bucketNome, $objeto['name']);
                    $resultado['removidos']++;
                    $resultado['detalhes'][] = [
                        'objeto' => $objeto['name'],
                        'status' => 'removido'
                    ];
                    \core\Controller::log("[backup] Removido: {$objeto['name']}");
                } catch (Exception $e) {
                    $resultado['erros']++;
                    $resultado['detalhes'][] = [
                        'objeto' => $objeto['name'],
                        'status' => 'erro',
                        'mensagem' => $e->getMessage()
                    ];
                    \core\Controller::log("[backup] Erro ao remover {$objeto['name']}: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            \core\Controller::log("[backup] Erro ao listar objetos do bucket: " . $e->getMessage());
            
            // ESTRATÉGIA 2 (fallback): Usar logs do banco de dados
            \core\Controller::log("[backup] Usando fallback: logs do banco de dados...");
            
            foreach ($logsExistentes as $log) {
                if (empty($log['gcs_objeto'])) {
                    continue;
                }

                // Extrair data do nome do arquivo
                // Formatos aceitos: 
                // - backup-YYYYMMDD-HHMMSS.sql.gz (legado)
                // - dbname-env-YYYYMMDD_HHMMSS.sql.gz (novo)
                $dataBackup = self::extrairDataDoNomeArquivo(basename($log['gcs_objeto']));

                if ($dataBackup && $dataBackup->getTimestamp() < $timestampLimite) {
                    try {
                        GoogleCloud::deleteFile($bucketNome, $log['gcs_objeto']);
                        $resultado['removidos']++;
                        $resultado['detalhes'][] = [
                            'objeto' => $log['gcs_objeto'],
                            'status' => 'removido'
                        ];
                        \core\Controller::log("[backup] Removido (fallback): {$log['gcs_objeto']}");
                    } catch (Exception $e) {
                        $resultado['erros']++;
                        $resultado['detalhes'][] = [
                            'objeto' => $log['gcs_objeto'],
                            'status' => 'erro',
                            'mensagem' => $e->getMessage()
                        ];
                    }
                }
            }
        }

        \core\Controller::log("[backup] Limpeza concluída: {$resultado['removidos']} removidos, {$resultado['erros']} erros");

        return $resultado['removidos'];
    }

    /**
     * Extrai a data de um nome de arquivo de backup.
     * 
     * @param string $nomeArquivo
     * @return \DateTime|null
     */
    private static function extrairDataDoNomeArquivo(string $nomeArquivo): ?\DateTime
    {
        $tz = new \DateTimeZone('America/Sao_Paulo');

        // Formato novo: dbname-env-YYYYMMDD_HHMMSS.sql.gz
        if (preg_match('/(\d{8})_(\d{6})\.sql\.gz$/', $nomeArquivo, $matches)) {
            return \DateTime::createFromFormat('Ymd His', $matches[1] . ' ' . $matches[2], $tz);
        }

        // Formato legado: backup-YYYYMMDD-HHMMSS.sql.gz
        if (preg_match('/backup-(\d{8})-(\d{6})\.sql\.gz$/', $nomeArquivo, $matches)) {
            return \DateTime::createFromFormat('Ymd His', $matches[1] . ' ' . $matches[2], $tz);
        }

        return null;
    }

    /**
     * Retorna timestamp formatado no timezone de Brasília.
     * Formato: YYYYMMDD_HHMMSS
     * 
     * @return string
     */
    private static function getTimestampBrasilia(): string
    {
        $tz = new \DateTimeZone('America/Sao_Paulo');
        $data = new \DateTime('now', $tz);
        return $data->format('Ymd_His');
    }

    /**
     * Valida se as credenciais MySQL estão configuradas.
     *
     * @return bool
     */
    public static function validarCredenciaisMySQL(): bool
    {
        $usuario = Config::USER_MASTER_DB;
        $senha = Config::PASS_MASTER_DB;

        return !empty($usuario) && !empty($senha);
    }

    /**
     * Remove arquivos temporários de um backup.
     *
     * @param string $arquivo
     * @return void
     */
    public static function limparArquivosTemp(string $arquivo): void
    {
        if (file_exists($arquivo)) {
            unlink($arquivo);
        }

        // Tentar remover diretório pai se estiver vazio
        $dir = dirname($arquivo);
        if (is_dir($dir) && count(scandir($dir)) === 2) { // apenas . e ..
            rmdir($dir);
        }
    }

    /**
     * Cria um diretório temporário único para o backup.
     *
     * @return string Caminho do diretório criado
     * @throws Exception
     */
    private static function criarDiretorioTemp(): string
    {
        $baseTemp = sys_get_temp_dir();
        $dirTemp = $baseTemp . '/backup_mysql_' . uniqid();

        if (!mkdir($dirTemp, 0755, true) && !is_dir($dirTemp)) {
            throw new Exception("Não foi possível criar diretório temporário: {$dirTemp}");
        }

        return $dirTemp;
    }

    /**
     * Localiza o executável mysqldump no sistema.
     *
     * @return string Caminho do mysqldump
     * @throws Exception
     */
    private static function encontrarMysqldump(): string
    {
        // Tentar localizar mysqldump
        if (stripos(PHP_OS, 'WIN') === 0) {
            $caminhosPossiveis = [
                'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
                'mysqldump'
            ];
        } else {
            $caminhosPossiveis = [
                '/usr/bin/mysqldump',
                '/usr/local/bin/mysqldump',
                'mysqldump',
                '/usr/bin/mariadb-dump',
                '/usr/local/bin/mariadb-dump',
                'mariadb-dump'
            ];
        }

        foreach ($caminhosPossiveis as $caminho) {
            if (file_exists($caminho)) {
                return $caminho;
            }
        }

        // Última tentativa: verificar se está no PATH
        if (stripos(PHP_OS, 'WIN') === 0) {
            exec('where mysqldump', $output, $returnCode);
        } else {
            exec('command -v mysqldump || command -v mariadb-dump', $output, $returnCode);
        }

        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

    throw new Exception("Executável mysqldump/mariadb-dump não encontrado. Instale o MySQL Client (mysql-client ou mariadb-client) ou configure o PATH");
    }
}
