<?php

namespace src\handlers;

use src\handlers\BackupConfig;
use src\handlers\service\BackupService;
use src\models\Backup_banco_config;
use src\models\Backup_execucao_log;
use core\Database;
use Exception;

/**
 * Handler para orquestrar a execução de backups de bancos de dados.
 * Gerencia o fluxo completo: dump, upload, logs e limpeza.
 */
class BackupExecucao
{
    /**
     * Executa backup de todos os bancos ativos.
     *
     * @return array Resultado da execução para cada banco
     */
    public static function executarTodos(): array
    {
        $configuracoes = BackupConfig::listarTodos(true); // Apenas ativos
        $resultados = [];

        foreach ($configuracoes as $config) {
            try {
                $resultado = self::executarPorId($config['idbackup_banco_config']);
                $resultados[] = [
                    'banco' => $config['nome_banco'],
                    'sucesso' => true,
                    'detalhes' => $resultado
                ];
            } catch (Exception $e) {
                $resultados[] = [
                    'banco' => $config['nome_banco'],
                    'sucesso' => false,
                    'erro' => $e->getMessage()
                ];
            }
        }

        return $resultados;
    }

    /**
     * Executa backup de um banco específico.
     *
     * @param int $idConfig
     * @return array Informações do backup realizado
     * @throws Exception
     */
    public static function executarPorId(int $idConfig): array
    {
        // Buscar configuração
        $config = BackupConfig::obterPorId($idConfig);

        // Criar log inicial
        $idLog = Backup_execucao_log::insert([
            'idbackup_banco_config' => $idConfig,
            'status' => 'running',
            'iniciado_em' => date('Y-m-d H:i:s')
        ])->execute();

        $arquivoLocal = null;

        try {
            // 1. Gerar dump MySQL
            $arquivoSql = BackupService::gerarDumpMySQL($config['nome_banco']);
            
            // 2. Comprimir arquivo
            $arquivoGz = BackupService::comprimirArquivo($arquivoSql);
            $arquivoLocal = $arquivoGz;

            // 3. Calcular checksum
            $checksum = BackupService::calcularChecksum($arquivoGz);

            // 4. Preparar caminho no GCS simplificado (formato: pasta_base/backup-YYYYMMDD-HHMMSS.sql.gz)
            $timestamp = date('Ymd_His');
            $nomeArquivo = "backup-{$timestamp}.sql.gz";
            $objetoGCS = "{$config['pasta_base']}/{$nomeArquivo}";

            // 5. Upload para GCS
            $uploadInfo = BackupService::uploadParaGCS(
                $arquivoGz,
                $config['bucket_nome'],
                $objetoGCS
            );

            // 6. Obter tamanho do arquivo
            $tamanhoBytes = filesize($arquivoGz);

            // 7. Finalizar log com sucesso
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

            // 10. Executar limpeza de backups antigos
            self::limparBackupsAntigos($idConfig);

            return [
                'idlog' => $idLog,
                'gcs_objeto' => $objetoGCS,
                'tamanho_bytes' => $tamanhoBytes,
                'checksum' => $checksum,
                'bucket' => $config['bucket_nome']
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
     *
     * @param int $idConfig
     * @return array Informações da limpeza
     * @throws Exception
     */
    public static function limparBackupsAntigos(int $idConfig): array
    {
        $config = BackupConfig::obterPorId($idConfig);

        // Buscar logs existentes para verificar quais arquivos remover do GCS
        $logsAntigos = Backup_execucao_log::select()
            ->where('idbackup_banco_config', $idConfig)
            ->where('status', 'success')
            ->orderBy('iniciado_em', 'DESC')
            ->get();

        // Remover arquivos antigos do GCS
        $arquivosRemovidos = BackupService::limparBackupsAntigos(
            $config['bucket_nome'],
            $config['pasta_base'],
            $config['retencao_dias'],
            $logsAntigos
        );

        // Remover logs antigos do banco de dados
        $logsRemovidos = Backup_execucao_log::limparAntigos(
            $idConfig,
            $config['retencao_dias']
        );

        return [
            'arquivos_gcs_removidos' => $arquivosRemovidos,
            'logs_db_removidos' => $logsRemovidos
        ];
    }
}
