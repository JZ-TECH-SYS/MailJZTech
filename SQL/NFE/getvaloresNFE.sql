select
     pa.`idempresa`
    ,pa.`idtipopagamento`
    ,tp.`descricao`
    ,sum(pa.`valor`) as valor_pagamento
from pedido_venda p
left join nota_fiscal     n on  n.idempresa = p.idempresa  and n.idpedidovenda    = p.idpedidovenda
left join pagamentos     pa on pa.idempresa = p.idempresa  and pa.idpedidovenda   = p.idpedidovenda
left join tipo_pagamento tp on tp.idempresa = pa.idempresa and tp.idtipopagamento = pa.idtipopagamento
where p.idempresa = :idempresa
  and date(p.data_baixa) BETWEEN ':datainicio' and ':datafim'
  and pa.valor > 0
group by 1,2,3