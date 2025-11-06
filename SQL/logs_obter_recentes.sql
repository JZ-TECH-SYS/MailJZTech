SELECT 
    el.*,
    e.destinatario,
    e.assunto,
    s.nome as sistema
FROM emails_logs el
LEFT JOIN emails_enviados e ON el.idemail = e.idemail
LEFT JOIN sistemas s ON el.idsistema = s.idsistema
ORDER BY el.data_log DESC
LIMIT :limite
