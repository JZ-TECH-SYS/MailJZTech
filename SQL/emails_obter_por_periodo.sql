SELECT 
    DATE(data_criacao) as data,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'enviado' THEN 1 ELSE 0 END) as enviados,
    SUM(CASE WHEN status = 'erro' THEN 1 ELSE 0 END) as erros
FROM emails_enviados 
WHERE idsistema = :idsistema 
AND data_criacao >= DATE_SUB(NOW(), INTERVAL :dias DAY)
GROUP BY DATE(data_criacao)
ORDER BY data DESC
