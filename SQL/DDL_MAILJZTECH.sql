-- ========================================
-- MailJZTech - DDL (Data Definition Language)
-- Sistema de Envio de E-mails
-- ========================================

-- Tabela de Usuários (Login)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `idusuario` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(150) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NULL,
  `totp_secret` VARCHAR(255) NULL COMMENT 'Secret para autenticação 2FA (TOTP)',
  `totp_habilitado` BOOLEAN DEFAULT FALSE,
  `backup_codes` JSON NULL COMMENT 'Códigos de backup para 2FA em JSON',
  `status` ENUM('ativo', 'inativo') DEFAULT 'ativo',
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ultimo_acesso` TIMESTAMP NULL,
  INDEX idx_nome (nome),
  INDEX idx_email (email),
  INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Sistemas (Clientes que usam a API)
CREATE TABLE IF NOT EXISTS `sistemas` (
  `idsistema` INT AUTO_INCREMENT PRIMARY KEY,
  `idusuario` INT NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `descricao` TEXT NULL,
  `chave_api` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Chave de API para autenticação',
  `nome_remetente` VARCHAR(150) NOT NULL COMMENT 'Nome que aparecerá no campo "De" do e-mail',
  `email_remetente` VARCHAR(255) DEFAULT 'contato@jztech.com.br' COMMENT 'E-mail padrão do remetente',
  `status` ENUM('ativo', 'inativo') DEFAULT 'ativo',
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ultimo_uso` TIMESTAMP NULL,
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
  INDEX idx_idusuario (idusuario),
  INDEX idx_chave_api (chave_api),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de E-mails Enviados
CREATE TABLE IF NOT EXISTS `emails_enviados` (
  `idemail` INT AUTO_INCREMENT PRIMARY KEY,
  `idsistema` INT NOT NULL,
  `idusuario` INT NOT NULL,
  `destinatario` VARCHAR(255) NOT NULL,
  `cc` TEXT NULL COMMENT 'E-mails em CC (separados por vírgula)',
  `bcc` TEXT NULL COMMENT 'E-mails em BCC (separados por vírgula)',
  `assunto` VARCHAR(255) NOT NULL,
  `corpo_html` LONGTEXT NOT NULL,
  `corpo_texto` LONGTEXT NULL,
  `anexos` JSON NULL COMMENT 'Lista de anexos em JSON',
  `status` ENUM('enviado', 'erro', 'pendente') DEFAULT 'pendente',
  `mensagem_erro` TEXT NULL,
  `data_envio` TIMESTAMP NULL,
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (idsistema) REFERENCES sistemas(idsistema) ON DELETE CASCADE,
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
  INDEX idx_idsistema (idsistema),
  INDEX idx_idusuario (idusuario),
  INDEX idx_status (status),
  INDEX idx_data_envio (data_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Logs de E-mails
CREATE TABLE IF NOT EXISTS `emails_logs` (
  `idlog` INT AUTO_INCREMENT PRIMARY KEY,
  `idemail` INT NULL,
  `idsistema` INT NOT NULL,
  `idusuario` INT NOT NULL,
  `tipo_log` ENUM('envio', 'criacao', 'atualizacao', 'erro', 'autenticacao', 'validacao') DEFAULT 'envio',
  `mensagem` TEXT NOT NULL,
  `dados_adicionais` JSON NULL COMMENT 'Dados adicionais em JSON',
  `ip_origem` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `data_log` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idemail) REFERENCES emails_enviados(idemail) ON DELETE SET NULL,
  FOREIGN KEY (idsistema) REFERENCES sistemas(idsistema) ON DELETE CASCADE,
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
  INDEX idx_idsistema (idsistema),
  INDEX idx_idusuario (idusuario),
  INDEX idx_tipo_log (tipo_log),
  INDEX idx_data_log (data_log)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configurações da API
CREATE TABLE IF NOT EXISTS `configuracoes_api` (
  `idconfig` INT AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT NOT NULL,
  `descricao` TEXT NULL,
  `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Inserir configurações padrão
-- ========================================
INSERT IGNORE INTO `configuracoes_api` (`chave`, `valor`, `descricao`) VALUES
('email_padrao', 'contato@jztech.com.br', 'E-mail padrão para envio de e-mails'),
('smtp_host', 'smtp.gmail.com', 'Host do servidor SMTP'),
('smtp_port', '587', 'Porta do servidor SMTP'),
('smtp_usuario', 'seu_email@gmail.com', 'Usuário do SMTP'),
('smtp_senha', 'sua_senha_app', 'Senha do SMTP (senha de app do Gmail)'),
('smtp_encriptacao', 'tls', 'Tipo de encriptação (tls ou ssl)'),
('max_tentativas_login', '5', 'Máximo de tentativas de login'),
('tempo_bloqueio_login', '900', 'Tempo de bloqueio após exceder tentativas (em segundos)');
