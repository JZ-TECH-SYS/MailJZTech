-- =====================================================
-- MailJZTech - Serviço de Envio de E-mail
-- DDL - Data Definition Language
-- =====================================================

-- Tabela de Sistemas (Clientes que usam a API)
CREATE TABLE IF NOT EXISTS `sistemas` (
  `idsistema` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL COMMENT 'Nome do sistema/cliente',
  `descricao` TEXT COMMENT 'Descrição do sistema',
  `nome_remetente` VARCHAR(255) NOT NULL COMMENT 'Nome que aparecerá como remetente',
  `email_remetente` VARCHAR(255) NOT NULL DEFAULT 'contato@gztech.com.br' COMMENT 'E-mail padrão de envio',
  `chave_api` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Chave de API para autenticação',
  `ativo` BOOLEAN DEFAULT TRUE COMMENT 'Se o sistema está ativo',
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_chave_api (chave_api),
  INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cadastro de sistemas que utilizam a API de e-mail';

-- Tabela de E-mails Enviados
CREATE TABLE IF NOT EXISTS `emails_enviados` (
  `idemail` INT AUTO_INCREMENT PRIMARY KEY,
  `idsistema` INT NOT NULL COMMENT 'ID do sistema que enviou',
  `destinatario` VARCHAR(320) NOT NULL COMMENT 'E-mail de destino',
  `cc` TEXT COMMENT 'E-mails em cópia (JSON)',
  `bcc` TEXT COMMENT 'E-mails em cópia oculta (JSON)',
  `assunto` VARCHAR(255) NOT NULL,
  `corpo_html` LONGTEXT NOT NULL COMMENT 'Corpo em HTML',
  `corpo_texto` LONGTEXT COMMENT 'Corpo em texto puro',
  `anexos` JSON COMMENT 'Informações dos anexos (JSON)',
  `status` ENUM('pendente', 'enviado', 'erro', 'devolvido') DEFAULT 'pendente',
  `mensagem_erro` TEXT COMMENT 'Mensagem de erro se houver',
  `data_envio` TIMESTAMP NULL COMMENT 'Data/hora do envio',
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (idsistema) REFERENCES sistemas(idsistema) ON DELETE CASCADE,
  INDEX idx_idsistema (idsistema),
  INDEX idx_status (status),
  INDEX idx_data_criacao (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de e-mails enviados';

-- Tabela de Logs de E-mails
CREATE TABLE IF NOT EXISTS `emails_logs` (
  `idlog` INT AUTO_INCREMENT PRIMARY KEY,
  `idemail` INT NOT NULL COMMENT 'ID do e-mail',
  `acao` VARCHAR(50) NOT NULL COMMENT 'Ação realizada (enviado, erro, retry, etc)',
  `detalhes` TEXT COMMENT 'Detalhes da ação',
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idemail) REFERENCES emails_enviados(idemail) ON DELETE CASCADE,
  INDEX idx_idemail (idemail),
  INDEX idx_data_criacao (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs detalhados de envios de e-mail';

-- Tabela de Configurações da API (opcional)
CREATE TABLE IF NOT EXISTS `configuracoes_api` (
  `idconfig` INT AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(255) NOT NULL UNIQUE,
  `valor` TEXT,
  `descricao` TEXT,
  `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações gerais da API';
