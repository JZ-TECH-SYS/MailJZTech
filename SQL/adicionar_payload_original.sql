-- =============================================
-- Migração: Adicionar coluna payload_original
-- Data: 2025-12-14
-- Descrição: Salva os metadados do payload recebido na API
--            para facilitar debug de e-mails que não chegam
-- =============================================

-- Adicionar coluna payload_original na tabela emails_enviados
ALTER TABLE emails_enviados 
ADD COLUMN payload_original JSON NULL COMMENT 'Metadados do payload original recebido na API (para debug)' 
AFTER mensagem_erro;

-- Verificar se foi criada
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'emails_enviados' 
AND COLUMN_NAME = 'payload_original';
