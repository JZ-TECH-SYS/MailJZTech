SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('enviado', 'aceito') THEN 1 ELSE 0 END) as enviados,
    SUM(CASE WHEN status IN ('erro', 'falha', 'rejeitado', 'bounce') THEN 1 ELSE 0 END) as erros,
    SUM(CASE WHEN status IN ('pendente', 'processando') THEN 1 ELSE 0 END) as pendentes,
    SUM(CASE WHEN status = 'aceito' THEN 1 ELSE 0 END) as aceitos,
    SUM(CASE WHEN status = 'rejeitado' THEN 1 ELSE 0 END) as rejeitados,
    SUM(CASE WHEN status = 'bounce' THEN 1 ELSE 0 END) as bounces,
    SUM(CASE WHEN status = 'falha' THEN 1 ELSE 0 END) as falhas
FROM emails_enviados 
-- Filtro opcional por sistema: quando :idsistema = 0, considera TODOS os sistemas
WHERE (:idsistema = 0 OR idsistema = :idsistema)
