-- Adicionar suporte a 2FA (TOTP) na tabela de usuários

-- Adicionar coluna para armazenar o secret do TOTP
ALTER TABLE usuarios ADD COLUMN totp_secret VARCHAR(255) NULL COMMENT 'Secret para TOTP (Google Authenticator, Microsoft Authenticator)';

-- Adicionar coluna para indicar se 2FA está habilitado
ALTER TABLE usuarios ADD COLUMN totp_habilitado BOOLEAN DEFAULT FALSE COMMENT 'Indica se 2FA está habilitado';

-- Adicionar coluna para armazenar backup codes
ALTER TABLE usuarios ADD COLUMN backup_codes JSON NULL COMMENT 'Códigos de backup para recuperação (JSON array)';

-- Adicionar coluna para data da última alteração de 2FA
ALTER TABLE usuarios ADD COLUMN data_2fa_alteracao TIMESTAMP NULL COMMENT 'Data da última alteração de 2FA';

-- Criar índice para melhorar performance
CREATE INDEX idx_usuarios_totp_habilitado ON usuarios(totp_habilitado);

-- Adicionar coluna para rastrear tentativas de login falhadas (segurança)
ALTER TABLE usuarios ADD COLUMN tentativas_login_falhas INT DEFAULT 0 COMMENT 'Número de tentativas de login falhadas';

-- Adicionar coluna para rastrear último login bem-sucedido
ALTER TABLE usuarios ADD COLUMN ultimo_login_sucesso TIMESTAMP NULL COMMENT 'Data do último login bem-sucedido';

-- Adicionar coluna para rastrear IP do último login
ALTER TABLE usuarios ADD COLUMN ultimo_ip_login VARCHAR(45) NULL COMMENT 'IP do último login bem-sucedido';
