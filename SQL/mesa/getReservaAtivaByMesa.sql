-- Buscar reserva ativa por número da mesa
SELECT
    mr.idreserva,
    mr.idempresa,
    mr.idmesa,
    mr.idcliente,
    mr.identificador,
    mr.status,
    mr.expira_em,
    mr.criado_em,
    ms.numero_mesa,
    ms.apelido AS mesa_apelido,
    p.nome AS cliente_nome,
    p.celular AS cliente_celular
FROM mesa_reserva mr
INNER JOIN mesa_salao ms 
    ON ms.idmesa = mr.idmesa 
    AND ms.idempresa = mr.idempresa
LEFT JOIN pessoa p 
    ON p.idcliente = mr.idcliente 
    AND p.idempresa = mr.idempresa
WHERE mr.idempresa = :idempresa
    AND ms.numero_mesa = :numero_mesa
    AND mr.status = 1
    AND mr.expira_em > NOW()
ORDER BY mr.expira_em DESC
LIMIT 1;
