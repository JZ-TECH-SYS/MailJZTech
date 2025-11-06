SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'enviado' THEN 1 ELSE 0 END) as enviados,
    SUM(CASE WHEN status = 'erro' THEN 1 ELSE 0 END) as erros,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
FROM emails_enviados 
WHERE idsistema = :idsistema
