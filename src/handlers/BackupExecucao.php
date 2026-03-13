<?php

namespace src\handlers;

use src\handlers\BackupConfig;
use src\handlers\service\BackupService;
use src\models\Backup_banco_config;
use src\models\Backup_execucao_log;
use core\Database;
use Exception;

/**
 * Handler para orquestrar a execução de backups COMPLETOS (schema + dados).
 * Gerencia o fluxo: dump, validação, compressão, upload, logs e limpeza.
 * 
 * Retenção padrão: 7 dias (configurável por banco)
 */
class BackupExecucao
{
    /** @var int Retenção padrão em dias */
    private const RETENCAO_PADRAO = 7;

    /**
     * Executa backup de todos os bancos ativos.
     *
     * @return array Resultado da execução para cada banco
     */
    public static function executarTodos(): array
    {
        $configuracoes = BackupConfig::listarTodos(true); // Apenas ativos
        $resultados = [];

        \core\Controller::log("=== INÍCIO BACKUP AUTOMÁTICO ===");
        \core\Controller::log("Total de bancos ativos: " . count($configuracoes));

        foreach ($configuracoes as $config) {
            try {
                $resultado = self::executarPorId($config['idbackup_banco_config']);
                $resultados[] = [
                    'banco' => $config['nome_banco'],
                    'sucesso' => true,
                    'detalhes' => $resultado
                ];
                \core\Controller::log("✓ Backup OK: {$config['nome_banco']}");
            } catch (Exception $e) {
                $resultados[] = [
                    'banco' => $config['nome_banco'],
                    'sucesso' => false,
                    'erro' => $e->getMessage()
                ];
                \core\Controller::log("✗ Backup ERRO: {$config['nome_banco']} - " . $e->getMessage());
            }
        }

        \core\Controller::log("=== FIM BACKUP AUTOMÁTICO ===");
        return $resultados;
    }

    /**
     * Executa backup COMPLETO de um banco específico.
     * Fluxo:
     * 1. Criar log inicial (status: running)
     * 2. Gerar dump MySQL (schema + dados)
     * 3. Validar conteúdo do backup
     * 4. Comprimir arquivo (.sql.gz)
     * 5. Calcular checksum SHA256
     * 6. Upload para GCS com naming padrão
     * 7. Atualizar log (status: success)
     * 8. Limpar arquivos temporários
     * 9. Executar limpeza de backups antigos (retenção)
     *
     * @param int $idConfig
     * @return array Informações do backup realizado
     * @throws Exception
     */
    public static function executarPorId(int $idConfig): array
    {
        $inicio = microtime(true);
        
        // Buscar configuração
        $config = BackupConfig::obterPorId($idConfig);
        $nomeBanco = $config['nome_banco'];
        $retencaoDias = $config['retencao_dias'] ?? self::RETENCAO_PADRAO;

        \core\Controller::log("========================================");
        \core\Controller::log("[{$nomeBanco}] Iniciando backup completo...");
        \core\Controller::log("[{$nomeBanco}] Bucket: {$config['bucket_nome']}");
        \core\Controller::log("[{$nomeBanco}] Retenção: {$retencaoDias} dias");
        \core\Controller::log("========================================");

        // Criar log inicial
        $idLog = Backup_execucao_log::insert([
            'idbackup_banco_config' => $idConfig,
            'status' => 'running',
            'iniciado_em' => date('Y-m-d H:i:s')
        ])->execute();

        $arquivoLocal = null;

        try {
            // 1. Gerar dump MySQL COMPLETO (schema + dados)
            $arquivoSql = BackupService::gerarDumpMySQL($nomeBanco);
            
            // 2. Comprimir arquivo
            $arquivoGz = BackupService::comprimirArquivo($arquivoSql);
            $arquivoLocal = $arquivoGz;

            // 3. Calcular checksum
            $checksum = BackupService::calcularChecksum($arquivoGz);

            // 4. Obter tamanho do arquivo
            $tamanhoBytes = filesize($arquivoGz);

            // 5. Preparar caminho no GCS com naming padrão hierárquico
            // Formato: pasta_base/ambiente/YYYY/MM/DD/dbname-ambiente-YYYYMMDD_HHMMSS.sql.gz
            $nomeArquivo = basename($arquivoGz);
            $objetoGCS = BackupService::gerarCaminhoGCS(
                $config['pasta_base'],
                $nomeBanco,
                $nomeArquivo
            );

            // 6. Upload para GCS
            $uploadInfo = BackupService::uploadParaGCS(
                $arquivoGz,
                $config['bucket_nome'],
                $objetoGCS
            );

            // 7. Finalizar log com sucesso
            $duracao = round(microtime(true) - $inicio, 2);
            
            Backup_execucao_log::finalizarLog($idLog, 'success', [
                'finalizado_em' => date('Y-m-d H:i:s'),
                'gcs_objeto' => $objetoGCS,
                'tamanho_bytes' => $tamanhoBytes,
                'checksum_sha256' => $checksum
            ]);

            // 8. Atualizar estatísticas da configuração
            BackupConfig::atualizarEstatisticas($idConfig);

            // 9. Limpar arquivos temporários
            BackupService::limparArquivosTemp($arquivoLocal);

            // 10. Executar limpeza de backups antigos (somente se upload OK)
            $limpeza = self::limparBackupsAntigos($idConfig);

            $tamanhoMB = round($tamanhoBytes / 1024 / 1024, 2);
            \core\Controller::log("[{$nomeBanco}] ✓ Backup concluído com sucesso!");
            \core\Controller::log("[{$nomeBanco}] Duração total: {$duracao}s");
            \core\Controller::log("[{$nomeBanco}] Tamanho: {$tamanhoMB} MB");
            \core\Controller::log("[{$nomeBanco}] Objeto GCS: {$objetoGCS}");
            \core\Controller::log("========================================");

            return [
                'idlog' => $idLog,
                'gcs_objeto' => $objetoGCS,
                'tamanho_bytes' => $tamanhoBytes,
                'tamanho_mb' => $tamanhoMB,
                'checksum' => $checksum,
                'bucket' => $config['bucket_nome'],
                'duracao_segundos' => $duracao,
                'limpeza' => $limpeza
            ];

        } catch (Exception $e) {
            // Registrar erro no log
            Backup_execucao_log::finalizarLog($idLog, 'error', [
                'finalizado_em' => date('Y-m-d H:i:s'),
                'mensagem_erro' => $e->getMessage()
            ]);

            // Limpar arquivos temporários em caso de erro
            if ($arquivoLocal && file_exists($arquivoLocal)) {
                BackupService::limparArquivosTemp($arquivoLocal);
            }

            \core\Controller::log("[{$nomeBanco}] ✗ ERRO: " . $e->getMessage());
            \core\Controller::log("========================================");

            // NÃO executar limpeza se o backup falhou
            throw $e;
        }
    }

    /**
     * Obtém logs de execução de um banco específico.
     *
     * @param int $idConfig
     * @param int $limite
     * @return array
     * @throws Exception
     */
    public static function obterLogs(int $idConfig, int $limite = 50): array
    {
        if (!BackupConfig::existeId($idConfig)) {
            throw new Exception("Configuração não encontrada (ID: {$idConfig})");
        }

        return Backup_execucao_log::obterPorConfig($idConfig, $limite);
    }

    /**
     * Obtém logs detalhados usando SQL complexo.
     *
     * @param int $idConfig
     * @param int $limite
     * @return array
     * @throws Exception
     */
    public static function obterLogsDetalhados(int $idConfig, int $limite = 50): array
    {
        $params = [
            'idconfig' => $idConfig,  // SEM os dois pontos
            'limite' => $limite       // Número sem aspas
        ];

        $resultado = Database::switchParams($params, 'backup_logs_obter_por_config', true, true); // LOG ATIVADO

        if ($resultado['error']) {
            throw new Exception("Erro ao buscar logs detalhados: " . $resultado['error']);
        }

        return $resultado['retorno'];
    }

    /**
     * Obtém estatísticas gerais dos backups (dashboard).
     *
     * @return array
     * @throws Exception
     */
    public static function obterEstatisticas(): array
    {
        $resultado = Database::switchParams([], 'backup_estatisticas', true, false);

        if ($resultado['error']) {
            throw new Exception("Erro ao buscar estatísticas de backup");
        }

        $dados = $resultado['retorno'][0] ?? [];

        // Formatar dados
        return [
            'total_bancos' => (int)($dados['total_bancos'] ?? 0),
            'total_backups' => (int)($dados['total_backups'] ?? 0),
            'backups_sucesso' => (int)($dados['backups_sucesso'] ?? 0),
            'backups_erro' => (int)($dados['backups_erro'] ?? 0),
            'espaco_total_mb' => round(($dados['espaco_total_bytes'] ?? 0) / 1024 / 1024, 2),
            'ultimo_backup' => $dados['ultimo_backup'] ?? null
        ];
    }

    /**
     * Remove logs antigos e arquivos do GCS baseado na retenção configurada.
     * RETENÇÃO PADRÃO: 7 dias
     *
     * @param int $idConfig
     * @return array Informações da limpeza
     * @throws Exception
     */
    public static function limparBackupsAntigos(int $idConfig): array
    {
        $config = BackupConfig::obterPorId($idConfig);
        $retencaoDias = $config['retencao_dias'] ?? self::RETENCAO_PADRAO;

        \core\Controller::log("[{$config['nome_banco']}] Executando limpeza (retenção: {$retencaoDias} dias)...");

        // Buscar logs existentes para verificar quais arquivos remover do GCS
        $logsAntigos = Backup_execucao_log::select()
            ->where('idbackup_banco_config', $idConfig)
            ->where('status', 'success')
            ->orderBy('iniciado_em', 'DESC')
            ->get();

        // Remover arquivos antigos do GCS (retenção de 7 dias por padrão)
        $arquivosRemovidos = BackupService::limparBackupsAntigos(
            $config['bucket_nome'],
            $config['pasta_base'],
            $retencaoDias,
            $logsAntigos
        );

        // Remover logs antigos do banco de dados
        $logsRemovidos = Backup_execucao_log::limparAntigos(
            $idConfig,
            $retencaoDias
        );

        \core\Controller::log("[{$config['nome_banco']}] Limpeza concluída: {$arquivosRemovidos} arquivos GCS, {$logsRemovidos} logs DB");

        return [
            'arquivos_gcs_removidos' => $arquivosRemovidos,
            'logs_db_removidos' => $logsRemovidos,
            'retencao_dias' => $retencaoDias
        ];
    }
}
