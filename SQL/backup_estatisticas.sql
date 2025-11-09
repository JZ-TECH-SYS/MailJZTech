-- ============================================
-- SQL: Estatísticas gerais dos backups (Dashboard)
-- Uso: BackupExecucao::obterEstatisticas()
-- ============================================

SELECT 
    COUNT(DISTINCT c.idbackup_banco_config) AS total_bancos,
    COUNT(l.idbackup_execucao_log) AS total_backups,
    SUM(CASE WHEN l.status = 'success' THEN 1 ELSE 0 END) AS backups_sucesso,
    SUM(CASE WHEN l.status = 'error' THEN 1 ELSE 0 END) AS backups_erro,
    SUM(CASE WHEN l.status = 'running' THEN 1 ELSE 0 END) AS backups_running,
    COALESCE(SUM(l.tamanho_bytes), 0) AS espaco_total_bytes,
    ROUND(COALESCE(SUM(l.tamanho_bytes), 0) / 1024 / 1024, 2) AS espaco_total_mb,
    MAX(l.iniciado_em) AS ultimo_backup,
    AVG(TIMESTAMPDIFF(SECOND, l.iniciado_em, l.finalizado_em)) AS duracao_media_segundos
FROM backup_banco_config c
LEFT JOIN backup_execucao_log l ON c.idbackup_banco_config = l.idbackup_banco_config
WHERE c.ativo = 1;
