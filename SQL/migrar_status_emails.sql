-- ================================================================
-- Migração: Expandir status de emails para melhor rastreabilidade
-- Data: 2025-12-14
-- Autor: MailJZTech
-- ================================================================

-- 1. Expandir o ENUM de status na tabela emails_enviados
-- Novos status:
--   - pendente: aguardando processamento
--   - processando: em processo de envio
--   - aceito: aceito pelo servidor SMTP (intermediário)
--   - enviado: confirmação de envio bem-sucedido
--   - rejeitado: rejeitado pelo servidor SMTP
--   - bounce: retornou (e-mail não entregue)
--   - falha: erro durante o processo de envio
--   - erro: erro genérico (legado, mantido para compatibilidade)

ALTER TABLE `emails_enviados` 
MODIFY COLUMN `status` ENUM(
    'pendente',
    'processando',
    'aceito',
    'enviado',
    'rejeitado',
    'bounce',
    'falha',
    'erro'
) COLLATE utf8mb4_unicode_ci DEFAULT 'pendente';

-- 2. Adicionar campo para código de resposta SMTP
ALTER TABLE `emails_enviados`
ADD COLUMN `smtp_code` VARCHAR(10) NULL AFTER `status`,
ADD COLUMN `smtp_response` TEXT NULL AFTER `smtp_code`,
ADD COLUMN `tentativas` INT(11) DEFAULT 0 AFTER `smtp_response`,
ADD COLUMN `tamanho_bytes` INT(11) NULL AFTER `tentativas`;

-- 3. Adicionar índice para busca por status
ALTER TABLE `emails_enviados`
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_data_status` (`data_criacao`, `status`);

-- 4. Criar tabela para tracking de eventos (bounces, callbacks, etc.)
CREATE TABLE IF NOT EXISTS `emails_eventos` (
    `idevento` INT(11) NOT NULL AUTO_INCREMENT,
    `idemail` INT(11) NOT NULL,
    `tipo_evento` ENUM('envio', 'aceito', 'rejeitado', 'bounce', 'entregue', 'aberto', 'clique', 'spam', 'erro') NOT NULL,
    `codigo_smtp` VARCHAR(10) NULL,
    `mensagem` TEXT NULL,
    `dados_extras` JSON NULL COMMENT 'Dados adicionais do evento',
    `origem` VARCHAR(50) NULL COMMENT 'Origem do evento: smtp, webhook, manual',
    `data_evento` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idevento`),
    INDEX `idx_idemail` (`idemail`),
    INDEX `idx_tipo_evento` (`tipo_evento`),
    INDEX `idx_data_evento` (`data_evento`),
    CONSTRAINT `fk_eventos_email` FOREIGN KEY (`idemail`) REFERENCES `emails_enviados` (`idemail`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Atualizar registros antigos com status 'erro' para 'falha' (opcional)
-- UPDATE `emails_enviados` SET `status` = 'falha' WHERE `status` = 'erro';

COMMIT;
