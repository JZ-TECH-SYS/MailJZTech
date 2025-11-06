-- ============================================
-- Buscar o último tipo de despesa criado para uma empresa
-- ============================================
-- 🔹 Usado após inserção para retornar o ID gerado
--
-- Parâmetros:
--   :idempresa - ID da empresa
--   :nome      - Nome do tipo de despesa
-- ============================================

SELECT idtipo_despesa 
FROM tipo_despesa 
WHERE idempresa = :idempresa 
  AND nome = ':nome'
ORDER BY data_cadastro DESC
LIMIT 1;
