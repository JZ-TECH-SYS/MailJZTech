-- ============================================
-- SQL: Obter logs detalhados de execução por configuração
-- Uso: BackupExecucao::obterLogsDetalhados()
-- ============================================

SELECT 
    l.idbackup_execucao_log,
    l.idbackup_banco_config,
    c.nome_banco,
    l.iniciado_em,
    l.finalizado_em,
    TIMESTAMPDIFF(SECOND, l.iniciado_em, l.finalizado_em) AS duracao_segundos,
    l.status,
    l.gcs_objeto,
    l.tamanho_bytes,
    ROUND(l.tamanho_bytes / 1024 / 1024, 2) AS tamanho_mb,
    l.checksum_sha256,
    l.mensagem_erro
FROM backup_execucao_log l
INNER JOIN backup_banco_config c ON c.idbackup_banco_config = l.idbackup_banco_config
WHERE l.idbackup_banco_config = :idconfig
ORDER BY l.iniciado_em DESC
LIMIT :limite
