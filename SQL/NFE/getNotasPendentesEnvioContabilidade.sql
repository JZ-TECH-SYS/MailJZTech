SELECT
    nf.idempresa,
    nf.idregistronota,
    nf.xml,
    nf.chavesefaz
FROM nota_fiscal nf
WHERE nf.idempresa = 3
  AND nf.idsituacaonotasefaz in (2,5)
  AND DATE(nf.dataemissao) BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY) AND CURRENT_DATE
  AND NOT EXISTS (
        SELECT 1
        FROM nota_fiscal_envio nfc
        WHERE nfc.idnotafiscal = nf.idregistronota
          AND nfc.idempresa    = nf.idempresa
  )
