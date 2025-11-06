-- ============================================
-- Inserir novo tipo de despesa com ID sequencial por empresa
-- ============================================
-- 🔹 Gera automaticamente o próximo ID sequencial por empresa
-- 🔹 IDs começam em 100 (1-99 reservados para tipos padrão)
-- 🔹 Cada empresa tem sua própria sequência de IDs
--
-- Parâmetros:
--   :idempresa - ID da empresa
--   :nome      - Nome do tipo de despesa
-- ============================================

INSERT INTO tipo_despesa (idtipo_despesa, idempresa, nome, ativo)
SELECT 
    COALESCE(MAX(td.idtipo_despesa), 99) + 1 as prox_id,
    :idempresa,
    ':nome',
    1
FROM tipo_despesa td
WHERE td.idempresa = :idempresa;
