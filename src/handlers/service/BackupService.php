<?php

namespace src\handlers\service;

use src\Config;
use src\handlers\service\GoogleCloud;
use Exception;

/**
 * Service para realizar backup de bancos MySQL e envio para Google Cloud Storage.
 * 
 * Fluxo completo:
 * 1. Gerar dump MySQL via mysqldump
 * 2. Comprimir arquivo (.sql.gz)
 * 3. Calcular checksum SHA256
 * 4. Upload para Google Cloud Storage
 * 5. Limpeza de arquivos temporários
 * 6. Limpeza de backups antigos (retenção)
 */
class BackupService
{
    /**
     * Gera dump de um banco MySQL e retorna o caminho do arquivo temporário.
     *
     * @param string $nomeBanco
     * @return string Caminho do arquivo .sql gerado
     * @throws Exception
     */
    public static function gerarDumpMySQL(string $nomeBanco): string
    {
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
        $timestamp = date('Ymd_His');
        $arquivoSql = "{$dirTemp}/backup_{$nomeBanco}_{$timestamp}.sql";

        // Localizar binário e montar comando com --result-file (captura erros em $output)
        $mysqldump = self::encontrarMysqldump();

        // Preferir passar senha via variável de ambiente para evitar problemas com caracteres especiais
        // Flags escolhidas:
        // - single-transaction: consistência sem bloquear
        // - routines, triggers, events: inclui SPs, funções, triggers e eventos
        // - default-character-set: força utf8mb4
        // - add-drop-database/add-drop-table: facilita restore limpo
        // - set-gtid-purged=OFF: compatível com MariaDB/MySQL sem erro
        $baseArgs = sprintf(
            '%s --user=%s --host=%s --port=%s --single-transaction --routines --triggers --events --default-character-set=utf8mb4 --add-drop-database --add-drop-table --set-gtid-purged=OFF %s --result-file=%s 2>&1',
            $mysqldump,
            escapeshellarg($usuario),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($nomeBanco),
            escapeshellarg($arquivoSql)
        );
        $comando = sprintf('MYSQL_PWD=%s %s', escapeshellarg($senha), $baseArgs);

        // Executar comando
        // Log (senha mascarada)
        \core\Controller::log('[backup] executando: ' . str_replace($senha, '***', $comando));

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            $erro = trim(implode("\n", $output));

            // Fallback: tenta com parâmetro --password=
            $comandoFallback = sprintf(
                '%s --user=%s --password=%s --host=%s --port=%s --single-transaction --skip-routines --skip-triggers --events --default-character-set=utf8mb4 --add-drop-database --add-drop-table --set-gtid-purged=OFF %s --result-file=%s 2>&1',
                $mysqldump,
                escapeshellarg($usuario),
                escapeshellarg($senha),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($nomeBanco),
                escapeshellarg($arquivoSql)
            );
            \core\Controller::log('[backup] fallback: ' . str_replace($senha, '***', $comandoFallback));
            $output = [];
            exec($comandoFallback, $output, $returnCode);
            $erro2 = trim(implode("\n", $output));

            if ($returnCode !== 0) {
                throw new Exception("Erro ao executar mysqldump: " . ($erro2 ?: $erro ?: 'retcode ' . $returnCode));
            }
        }

        if (!file_exists($arquivoSql) || filesize($arquivoSql) === 0) {
            throw new Exception("Arquivo de backup não foi gerado ou está vazio");
        }

        return $arquivoSql;
    }

    /**
     * Comprime um arquivo usando gzip.
     *
     * @param string $arquivoOrigem
     * @return string Caminho do arquivo .gz gerado
     * @throws Exception
     */
    public static function comprimirArquivo(string $arquivoOrigem): string
    {
        if (!file_exists($arquivoOrigem)) {
            throw new Exception("Arquivo para compressão não encontrado: {$arquivoOrigem}");
        }

        $arquivoGz = $arquivoOrigem . '.gz';

        // Abrir arquivo original para leitura
        $fpOrigem = fopen($arquivoOrigem, 'rb');
        if (!$fpOrigem) {
            throw new Exception("Não foi possível abrir arquivo para leitura: {$arquivoOrigem}");
        }

        // Abrir arquivo .gz para escrita
        $fpGz = gzopen($arquivoGz, 'wb9'); // 9 = máxima compressão
        if (!$fpGz) {
            fclose($fpOrigem);
            throw new Exception("Não foi possível criar arquivo comprimido: {$arquivoGz}");
        }

        // Copiar dados comprimindo
        while (!feof($fpOrigem)) {
            $buffer = fread($fpOrigem, 8192);
            gzwrite($fpGz, $buffer);
        }

        fclose($fpOrigem);
        gzclose($fpGz);

        // Remover arquivo original (manter apenas .gz)
        unlink($arquivoOrigem);

        if (!file_exists($arquivoGz)) {
            throw new Exception("Arquivo comprimido não foi gerado");
        }

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

        return $hash;
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
        if (!file_exists($arquivoLocal)) {
            throw new Exception("Arquivo local não encontrado: {$arquivoLocal}");
        }

        // Simular estrutura de $_FILES para o método uploadFile
        $fileData = [
            'name' => basename($arquivoLocal),
            'tmp_name' => $arquivoLocal,
            'size' => filesize($arquivoLocal)
        ];

        $resultado = GoogleCloud::uploadFile($fileData, $bucketNome, $objetoNome);

        return $resultado;
    }

    /**
     * Remove backups antigos do GCS baseado na retenção configurada.
     *
     * @param string $bucketNome
     * @param string $pastaBase
     * @param int $retencaoDias
     * @param array $logsExistentes Lista de objetos GCS dos logs recentes
     * @return int Quantidade de arquivos removidos
     */
    public static function limparBackupsAntigos(string $bucketNome, string $pastaBase, int $retencaoDias, array $logsExistentes): int
    {
        // Calcular data limite
        $dataLimite = strtotime("-{$retencaoDias} days");
        $removidos = 0;

        // Para cada log, verificar se está fora da retenção
        foreach ($logsExistentes as $log) {
            if (empty($log['gcs_objeto'])) {
                continue;
            }

            // Extrair timestamp do nome do arquivo (formato: backup-YYYYMMDD-HHMMSS.sql.gz)
            if (preg_match('/backup-(\d{8})-(\d{6})\.sql\.gz$/', basename($log['gcs_objeto']), $matches)) {
                $dataBackup = \DateTime::createFromFormat('Ymd His', $matches[1] . ' ' . $matches[2]);

                if ($dataBackup && $dataBackup->getTimestamp() < $dataLimite) {
                    try {
                        GoogleCloud::deleteFile($bucketNome, $log['gcs_objeto']);
                        $removidos++;
                    } catch (Exception $e) {
                        // Log erro mas continua processamento
                        error_log("Erro ao remover backup antigo {$log['gcs_objeto']}: " . $e->getMessage());
                    }
                }
            }
        }

        return $removidos;
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
