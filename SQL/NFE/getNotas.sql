select
     p.*,
     n.idregistronota,
     n.idsituacaonotasefaz,
     n.idusuario,
     n.msgsefaz,
     n.numeronota,
     n.xml,
     n.chavesefaz,
     n.dataemissao,
     n.data_cancelamento,
     n.motivo_cancelamento,
     n.valor_total,
     n.valor_impostos,
     n.protocolo_autorizacao,
     n.cpf_cnpj_destinatario,
     n.msg_erro,
     n.status_processamento,
     n.data_hora_processamento,
     n.data_hora_autorizacao,
     n.protocolo_cancelamento,
     n.xml_cancelamento,
     n.recibo_sefaz,
     n.cstat_sefaz,
     n.serie,
     n.modelo,
    (
      SELECT 
         pa.idtipopagamento
      FROM pagamentos pa
      WHERE pa.idempresa     = p.idempresa
        AND pa.idpedidovenda = p.idpedidovenda
      ORDER BY pa.valor DESC, pa.idtipopagamento ASC
      LIMIT 1
    ) AS idtipopagamento,
    (
      SELECT 
          tp.descricao
      FROM pagamentos pa
      JOIN tipo_pagamento tp ON tp.idempresa  = pa.idempresa AND tp.idtipopagamento = pa.idtipopagamento
      WHERE pa.idempresa     = p.idempresa 
        AND pa.idpedidovenda = p.idpedidovenda
      ORDER BY pa.valor DESC, pa.idtipopagamento ASC
      LIMIT 1
    ) AS descricao_pagamento,
    (
      SELECT 
         pa.idpagamento
      FROM pagamentos pa
      WHERE pa.idempresa     = p.idempresa
        AND pa.idpedidovenda = p.idpedidovenda
      ORDER BY pa.valor DESC, pa.idtipopagamento ASC
      LIMIT 1
    ) AS idpagamento,
    (
      SELECT 
         pa.cAut
      FROM pagamentos pa
      WHERE pa.idempresa     = p.idempresa
        AND pa.idpedidovenda = p.idpedidovenda
      ORDER BY pa.valor DESC, pa.idtipopagamento ASC
      LIMIT 1
    ) AS cAut
from pedido_venda p
left join nota_fiscal n     on n.idempresa = p.idempresa and n.idpedidovenda = p.idpedidovenda
where p.idempresa = :idempresa
  and date(p.data_baixa) BETWEEN ':datainicio' and ':datafim'
  AND (
        ':meios' = ''
        OR EXISTS (
            SELECT 1
            FROM pagamentos px
             WHERE px.idempresa     = p.idempresa
               AND px.idpedidovenda = p.idpedidovenda
               AND px.idtipopagamento in (:meios)
        )
      )
order by p.data_baixa