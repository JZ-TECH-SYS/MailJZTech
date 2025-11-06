CREATE FUNCTION `proxima_nota_empresa`(
        `p_idempresa` INTEGER,
        `p_modelo` INTEGER
    )
    RETURNS INTEGER
    DETERMINISTIC
    CONTAINS SQL
    SQL SECURITY DEFINER
    COMMENT ''
BEGIN
    DECLARE v_proximo_numero INT;

    SELECT 
        IFNULL(MAX(CAST(numeronota AS UNSIGNED)), 0) + 1 
    INTO v_proximo_numero
    FROM nota_fiscal
    WHERE idempresa = p_idempresa
      AND modelo = p_modelo;

    RETURN v_proximo_numero;
END$$



CREATE FUNCTION calcular_impostosv2(
    p_idproduto      INTEGER,
    p_idpedidovenda  INTEGER,
    p_idempresa      INTEGER,
    p_data_emissao   DATE
)
RETURNS JSON
DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT 'v2 – casa por NCM; fallback NCM NULL; sem defaults; sem subqueries reabrindo temp table'
BEGIN
    /* principais */
    DECLARE v_idcontrole_fiscal INT;
    DECLARE v_result            JSON DEFAULT JSON_ARRAY();
    DECLARE v_preco_unitario    DECIMAL(15,2) DEFAULT 0;
    DECLARE v_quantidade        DECIMAL(15,4) DEFAULT 0;
    DECLARE v_valor_total       DECIMAL(15,2) DEFAULT 0;
    DECLARE v_ncm_prod          VARCHAR(10);
    DECLARE v_fallback_ncm      VARCHAR(10) DEFAULT '21069090';
    DECLARE v_rows              INT DEFAULT 0;
    DECLARE v_msg               TEXT;

    /* linha-a-linha */
    DECLARE v_tipo_imposto      VARCHAR(50);
    DECLARE v_CST               VARCHAR(10);
    DECLARE v_CSOSN             VARCHAR(20);
    DECLARE v_aliquota          DECIMAL(7,4);
    DECLARE v_base              DECIMAL(15,2);
    DECLARE v_UF                CHAR(2);
    DECLARE v_uf_origem         CHAR(2);
    DECLARE v_CFOP              VARCHAR(10);
    DECLARE v_NCM               VARCHAR(10);
    DECLARE v_CEST              VARCHAR(20);
    DECLARE v_CNPJ              VARCHAR(14);
    DECLARE v_tipo_iva          ENUM('CBS','IBS','IS');
    DECLARE v_aliquota_cbs      DECIMAL(7,4);
    DECLARE v_aliquota_ibs      DECIMAL(7,4);
    DECLARE v_aliquota_is       DECIMAL(7,4);
    DECLARE v_seletivo_cod_prod VARCHAR(4);
    DECLARE v_aliquota_fcp      DECIMAL(7,4);
    DECLARE v_v_fcp             DECIMAL(15,2);
    DECLARE v_mod_bc_st         TINYINT;
    DECLARE v_mva_st            DECIMAL(7,4);
    DECLARE v_cbenef            VARCHAR(10);
    DECLARE v_valor_imposto     DECIMAL(15,2);

    /* 0) NCM e preços do item */
    SELECT p.ncm,
           COALESCE(p.preco, 0),
           COALESCE(pvi.quantidade, 0)
      INTO v_ncm_prod, v_preco_unitario, v_quantidade
      FROM produtos p
      LEFT JOIN pedido_venda_item pvi
             ON pvi.idempresa     = p.idempresa
            AND pvi.idproduto     = p.idproduto
            AND pvi.idpedidovenda = p_idpedidovenda
     WHERE p.idempresa = p_idempresa
       AND p.idproduto = p_idproduto
     LIMIT 1;

    IF v_ncm_prod IS NULL OR v_ncm_prod = '' THEN
        SET v_ncm_prod = v_fallback_ncm;
    END IF;

    SET v_valor_total = ROUND(v_preco_unitario * v_quantidade, 2);

    /* 1) regra fiscal padrão (com vigência) */
    SELECT cf.idcontrole_fiscal
      INTO v_idcontrole_fiscal
      FROM controle_fiscal cf
     WHERE cf.idempresa = p_idempresa
       AND cf.padrao = 1
       AND (cf.apartirde IS NULL
            OR (YEAR(p_data_emissao)*100 + MONTH(p_data_emissao)) >= cf.apartirde)
     ORDER BY cf.apartirde DESC
     LIMIT 1;

    IF v_idcontrole_fiscal IS NULL THEN
        SET v_msg = 'SEM_REGRA_FISCAL_PADRAO: cadastre controle_fiscal padrao para a empresa';
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = v_msg, MYSQL_ERRNO = 1644;
    END IF;

    /* 2) temps com collation padronizada */
    DROP TEMPORARY TABLE IF EXISTS tmp_det_fiscal;
    CREATE TEMPORARY TABLE tmp_det_fiscal
    (
        iddetalhes_tributacao INT,
        tipo_imposto          VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        CST                   VARCHAR(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        CSOSN                 VARCHAR(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        aliquota              DECIMAL(7,4),
        base                  DECIMAL(15,2),
        UF                    CHAR(2)     CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        uf_origem             CHAR(2)     CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        CFOP                  VARCHAR(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        NCM                   VARCHAR(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        CEST                  VARCHAR(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        CNPJ                  VARCHAR(14) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        tipo_iva              ENUM('CBS','IBS','IS'),
        aliquota_cbs          DECIMAL(7,4),
        aliquota_ibs          DECIMAL(7,4),
        aliquota_is           DECIMAL(7,4),
        seletivo_cod_prod     VARCHAR(4)  CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        aliquota_fcp          DECIMAL(7,4),
        v_fcp                 DECIMAL(15,2),
        mod_bc_st             TINYINT,
        mva_st                DECIMAL(7,4),
        cbenef                VARCHAR(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        prio                  TINYINT
    ) ENGINE=Memory;

    INSERT INTO tmp_det_fiscal
    SELECT
        dt.iddetalhes_tributacao, dt.tipo_imposto, dt.CST, dt.CSOSN, dt.aliquota, dt.base,
        dt.UF, dt.uf_origem, dt.CFOP, dt.NCM, dt.CEST, dt.CNPJ,
        dt.tipo_iva, dt.aliquota_cbs, dt.aliquota_ibs, dt.aliquota_is,
        dt.seletivo_cod_prod, dt.aliquota_fcp, dt.v_fcp,
        dt.mod_bc_st, dt.mva_st, dt.cbenef,
        CASE
          WHEN dt.NCM COLLATE utf8mb3_general_ci = v_ncm_prod COLLATE utf8mb3_general_ci THEN 2
          WHEN dt.NCM IS NULL THEN 1
          ELSE 0
        END AS prio
    FROM controle_fiscal_detalhes_tributacao dt
    WHERE dt.idcontrole_fiscal = v_idcontrole_fiscal
      AND dt.idempresa = p_idempresa
      AND (dt.NCM IS NULL
           OR dt.NCM COLLATE utf8mb3_general_ci = v_ncm_prod COLLATE utf8mb3_general_ci);

    /* Tabela com max(prio) por tipo_imposto */
    DROP TEMPORARY TABLE IF EXISTS tmp_best_prio;
    CREATE TEMPORARY TABLE tmp_best_prio
    (
        tipo_imposto VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        prio TINYINT,
        PRIMARY KEY (tipo_imposto)
    ) ENGINE=Memory;

    INSERT INTO tmp_best_prio (tipo_imposto, prio)
    SELECT tipo_imposto, MAX(prio)
      FROM tmp_det_fiscal
     GROUP BY tipo_imposto;

    /* Tabela com max(iddetalhes_tributacao) para o prio escolhido */
    DROP TEMPORARY TABLE IF EXISTS tmp_best_row;
    CREATE TEMPORARY TABLE tmp_best_row
    (
        tipo_imposto VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
        prio TINYINT,
        max_id INT,
        PRIMARY KEY (tipo_imposto)
    ) ENGINE=Memory;

    INSERT INTO tmp_best_row (tipo_imposto, prio, max_id)
    SELECT d.tipo_imposto, b.prio, MAX(d.iddetalhes_tributacao)
      FROM tmp_det_fiscal d
      JOIN tmp_best_prio b
        ON b.tipo_imposto COLLATE utf8mb3_general_ci = d.tipo_imposto COLLATE utf8mb3_general_ci
       AND b.prio = d.prio
     GROUP BY d.tipo_imposto, b.prio;

    /* 3) cursor – JOIN sem subquery na mesma temp table */
    BEGIN
        DECLARE v_done BOOL DEFAULT FALSE;

        DECLARE c_impostos CURSOR FOR
            SELECT d.tipo_imposto, d.CST, d.CSOSN, d.aliquota, d.base,
                   d.UF, d.uf_origem, d.CFOP, d.NCM, d.CEST, d.CNPJ,
                   d.tipo_iva, d.aliquota_cbs, d.aliquota_ibs, d.aliquota_is,
                   d.seletivo_cod_prod, d.aliquota_fcp, d.v_fcp,
                   d.mod_bc_st, d.mva_st, d.cbenef
              FROM tmp_det_fiscal d
              JOIN tmp_best_row br
                ON br.tipo_imposto COLLATE utf8mb3_general_ci = d.tipo_imposto COLLATE utf8mb3_general_ci
               AND br.prio = d.prio
               AND br.max_id = d.iddetalhes_tributacao
             ORDER BY d.tipo_imposto;

        DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;

        OPEN c_impostos;
        imp_loop: LOOP
            FETCH c_impostos
             INTO v_tipo_imposto, v_CST, v_CSOSN, v_aliquota, v_base,
                  v_UF, v_uf_origem, v_CFOP, v_NCM, v_CEST, v_CNPJ,
                  v_tipo_iva, v_aliquota_cbs, v_aliquota_ibs, v_aliquota_is,
                  v_seletivo_cod_prod, v_aliquota_fcp, v_v_fcp,
                  v_mod_bc_st, v_mva_st, v_cbenef;

            IF v_done THEN
                LEAVE imp_loop;
            END IF;

            SET v_rows = v_rows + 1;

            IF v_base IS NULL THEN
               SET v_base = v_valor_total;
            END IF;

            SET v_valor_imposto = 0;
            CASE v_tipo_iva
                WHEN 'CBS' THEN SET v_valor_imposto = ROUND(v_base * (IFNULL(v_aliquota_cbs,0)/100),2);
                WHEN 'IBS' THEN SET v_valor_imposto = ROUND(v_base * (IFNULL(v_aliquota_ibs,0)/100),2);
                WHEN 'IS'  THEN SET v_valor_imposto = ROUND(v_base * (IFNULL(v_aliquota_is ,0)/100),2);
                ELSE
                    IF v_aliquota IS NOT NULL THEN
                       SET v_valor_imposto = ROUND(v_base * (v_aliquota/100),2);
                    END IF;
            END CASE;

            SET v_result = JSON_ARRAY_APPEND(v_result,'$',
              JSON_OBJECT(
                'UF',                 v_UF,
                'CST',                v_CST,
                'NCM',                COALESCE(v_NCM, v_ncm_prod),
                'CEST',               v_CEST,
                'CFOP',               v_CFOP,
                'CNPJ',               v_CNPJ,
                'base',               IFNULL(ROUND(v_base,2),NULL),
                'orig',               v_uf_origem,   -- string UF (compatibilidade)
                'CSOSN',              v_CSOSN,
                'v_fcp',              v_v_fcp,
                'cbenef',             v_cbenef,
                'mva_st',             v_mva_st,
                'origem',             v_uf_origem,
                'aliquota',           IFNULL(ROUND(v_aliquota,4),NULL),
                'tipo_iva',           v_tipo_iva,
                'mod_bc_st',          v_mod_bc_st,
                'aliquota_is',        v_aliquota_is,
                'aliquota_cbs',       v_aliquota_cbs,
                'aliquota_fcp',       v_aliquota_fcp,
                'aliquota_ibs',       v_aliquota_ibs,
                'tipo_imposto',       v_tipo_imposto,
                'valor_imposto',      v_valor_imposto,
                'seletivo_cod_prod',  v_seletivo_cod_prod
              )
            );
        END LOOP;
        CLOSE c_impostos;
    END;

    /* limpar temps */
    DROP TEMPORARY TABLE IF EXISTS tmp_best_row;
    DROP TEMPORARY TABLE IF EXISTS tmp_best_prio;
    DROP TEMPORARY TABLE IF EXISTS tmp_det_fiscal;

    /* 4) sem linhas -> erro */
    IF v_rows = 0 THEN
        SET v_msg = CONCAT(
            'SEM_DETALHE_FISCAL empresa=', CAST(p_idempresa AS CHAR),
            ' produto=', CAST(p_idproduto AS CHAR),
            ' ncm=', IFNULL(v_ncm_prod,'NULL'),
            ' - cadastre linhas em controle_fiscal_detalhes_tributacao (NCM exato ou NCM NULL)'
        );
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = v_msg, MYSQL_ERRNO = 1644;
    END IF;

    RETURN v_result;
END$$
