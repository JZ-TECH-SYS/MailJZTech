-- Buscar o próximo número de mesa disponível
-- MAX(idmesa) dos pedidos abertos + 1
SELECT COALESCE(MAX(pv.idmesa), 0) + 1 AS proxima_mesa
FROM pedido_venda pv
WHERE pv.idempresa = :idempresa
  AND pv.idsituacao_pedido_venda = 1
