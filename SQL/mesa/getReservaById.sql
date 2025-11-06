-- Buscar reserva por ID com dados de mesa e cliente
SELECT
    mr.idreserva,
    mr.idempresa,
    mr.idmesa,
    mr.idcliente,
    mr.identificador,
    mr.status,
    mr.expira_em,
    mr.criado_em,
    mr.atualizado_em,
    mr.criado_por,
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
WHERE mr.idreserva = :idreserva
    AND mr.idempresa = :idempresa;
