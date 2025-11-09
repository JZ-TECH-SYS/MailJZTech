-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 09/11/2025 às 11:32
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gadoma77_mailjztech`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `emails_enviados`
--

CREATE TABLE `emails_enviados` (
  `idemail` int(11) NOT NULL,
  `idsistema` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `destinatario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cc` text COLLATE utf8mb4_unicode_ci COMMENT 'E-mails em CC (separados por vírgula)',
  `bcc` text COLLATE utf8mb4_unicode_ci COMMENT 'E-mails em BCC (separados por vírgula)',
  `assunto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corpo_html` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `corpo_texto` longtext COLLATE utf8mb4_unicode_ci,
  `anexos` json DEFAULT NULL COMMENT 'Lista de anexos em JSON',
  `status` enum('enviado','erro','pendente') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `mensagem_erro` text COLLATE utf8mb4_unicode_ci,
  `data_envio` timestamp NULL DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `emails_enviados`
--

INSERT INTO `emails_enviados` (`idemail`, `idsistema`, `idusuario`, `destinatario`, `cc`, `bcc`, `assunto`, `corpo_html`, `corpo_texto`, `anexos`, `status`, `mensagem_erro`, `data_envio`, `data_criacao`, `data_atualizacao`) VALUES
(1, 1, 1, 'jv.zyzz.legado@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:21:12', '2025-11-09 14:21:12', '2025-11-09 14:21:12'),
(2, 1, 1, 'jv.zyzz.legado@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:25:36', '2025-11-09 14:25:36', '2025-11-09 14:25:36'),
(3, 1, 1, 'Zehenrique0822@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:30:04', '2025-11-09 14:30:04', '2025-11-09 14:30:04'),
(4, 1, 1, 'Zehenrique0822@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:30:05', '2025-11-09 14:30:05', '2025-11-09 14:30:05'),
(5, 1, 1, 'Zehenrique0822@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:30:06', '2025-11-09 14:30:06', '2025-11-09 14:30:06'),
(6, 1, 1, 'Zehenrique0822@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:30:07', '2025-11-09 14:30:07', '2025-11-09 14:30:07'),
(7, 1, 1, '23143231432423Zehenrique0822@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:30:13', '2025-11-09 14:30:13', '2025-11-09 14:30:13'),
(8, 1, 1, 'Zehenrique0822@gmail.com', NULL, NULL, 'Olá!', '<h1>Bem-vindo!</h1>', NULL, NULL, 'enviado', NULL, '2025-11-09 14:30:27', '2025-11-09 14:30:27', '2025-11-09 14:30:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `emails_logs`
--

CREATE TABLE `emails_logs` (
  `idlog` int(11) NOT NULL,
  `idemail` int(11) DEFAULT NULL,
  `idsistema` int(11) NOT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `tipo_log` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT 'envio',
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dados_adicionais` json DEFAULT NULL COMMENT 'Dados adicionais em JSON',
  `ip_origem` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `data_log` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `emails_logs`
--

INSERT INTO `emails_logs` (`idlog`, `idemail`, `idsistema`, `idusuario`, `tipo_log`, `mensagem`, `dados_adicionais`, `ip_origem`, `user_agent`, `data_log`) VALUES
(2, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"jv.zyzz.legado@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:21:12'),
(3, 1, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:21:12\"}', '45.169.127.135', NULL, '2025-11-09 14:21:12'),
(4, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"jv.zyzz.legado@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:25:35'),
(5, 2, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:25:36\"}', '45.169.127.135', NULL, '2025-11-09 14:25:36'),
(6, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"Zehenrique0822@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:30:03'),
(7, 3, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:30:04\"}', '45.169.127.135', NULL, '2025-11-09 14:30:04'),
(8, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"Zehenrique0822@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:30:05'),
(9, 4, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:30:05\"}', '45.169.127.135', NULL, '2025-11-09 14:30:05'),
(10, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"Zehenrique0822@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:30:06'),
(11, 5, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:30:06\"}', '45.169.127.135', NULL, '2025-11-09 14:30:06'),
(12, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"Zehenrique0822@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:30:07'),
(13, 6, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:30:07\"}', '45.169.127.135', NULL, '2025-11-09 14:30:07'),
(14, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"23143231432423Zehenrique0822@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:30:13'),
(15, 7, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:30:13\"}', '45.169.127.135', NULL, '2025-11-09 14:30:13'),
(16, NULL, 1, 1, 'envio', 'Tentando enviar e-mail', '{\"assunto\": \"Olá!\", \"destinatario\": \"Zehenrique0822@gmail.com\"}', '45.169.127.135', NULL, '2025-11-09 14:30:27'),
(17, 8, 1, 1, 'envio', 'E-mail enviado com sucesso', '{\"timestamp\": \"2025-11-09 11:30:27\"}', '45.169.127.135', NULL, '2025-11-09 14:30:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sistemas`
--

CREATE TABLE `sistemas` (
  `idsistema` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `chave_api` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chave de API para autenticação',
  `nome_remetente` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome que aparecerá no campo "De" do e-mail',
  `email_remetente` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'contato@jztech.com.br' COMMENT 'E-mail padrão do remetente',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ultimo_uso` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `sistemas`
--

INSERT INTO `sistemas` (`idsistema`, `idusuario`, `nome`, `descricao`, `chave_api`, `nome_remetente`, `email_remetente`, `status`, `data_criacao`, `data_atualizacao`, `ultimo_uso`) VALUES
(1, 1, 'Clickexpress', 'Clickexpress', '7757d2d4ab6003f5a700efcb15c6f5fcb1eb0e705110e4b63d0067a0c5199893', 'Clickexpress', 'contato@jztech.com.br', 'ativo', '2025-11-09 00:32:27', '2025-11-09 00:32:27', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `idusuario` int(11) NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `totp_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secret para autenticação 2FA (TOTP)',
  `totp_habilitado` tinyint(1) DEFAULT '0',
  `backup_codes` json DEFAULT NULL COMMENT 'Códigos de backup para 2FA em JSON',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ultimo_acesso` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`idusuario`, `nome`, `email`, `senha`, `token`, `totp_secret`, `totp_habilitado`, `backup_codes`, `status`, `data_criacao`, `data_atualizacao`, `ultimo_acesso`) VALUES
(1, 'joao', 'jv.zyzz.legado@gmail.com', '$2y$10$XkKWK7zNh6lsYXSANxOtBeoiBXDUEJykmyLB8Rf9EfJlXYicJ7Eq2', '17ec3d24bd7cc8030cb91bfa8d228b67', 'SCB7KA2BCZTW4UGSN6P47AAU5E6KQ6Z436PSBJSBRREEORQ32DYQ====', 1, '[\"43260151\", \"58885399\", \"57129491\", \"41901815\", \"01075882\", \"92853598\", \"59839087\", \"04768167\", \"72377738\", \"00909238\"]', 'ativo', '2025-11-06 21:33:15', '2025-11-09 14:26:18', '2025-11-09 14:26:18');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `emails_enviados`
--
ALTER TABLE `emails_enviados`
  ADD PRIMARY KEY (`idemail`),
  ADD KEY `idx_idsistema` (`idsistema`),
  ADD KEY `idx_idusuario` (`idusuario`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_data_envio` (`data_envio`);

--
-- Índices de tabela `emails_logs`
--
ALTER TABLE `emails_logs`
  ADD PRIMARY KEY (`idlog`),
  ADD KEY `idemail` (`idemail`),
  ADD KEY `idx_idsistema` (`idsistema`),
  ADD KEY `idx_idusuario` (`idusuario`),
  ADD KEY `idx_tipo_log` (`tipo_log`),
  ADD KEY `idx_data_log` (`data_log`);

--
-- Índices de tabela `sistemas`
--
ALTER TABLE `sistemas`
  ADD PRIMARY KEY (`idsistema`),
  ADD UNIQUE KEY `chave_api` (`chave_api`),
  ADD KEY `idx_idusuario` (`idusuario`),
  ADD KEY `idx_chave_api` (`chave_api`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idusuario`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_nome` (`nome`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `emails_enviados`
--
ALTER TABLE `emails_enviados`
  MODIFY `idemail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `emails_logs`
--
ALTER TABLE `emails_logs`
  MODIFY `idlog` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `sistemas`
--
ALTER TABLE `sistemas`
  MODIFY `idsistema` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `emails_enviados`
--
ALTER TABLE `emails_enviados`
  ADD CONSTRAINT `emails_enviados_ibfk_1` FOREIGN KEY (`idsistema`) REFERENCES `sistemas` (`idsistema`) ON DELETE CASCADE,
  ADD CONSTRAINT `emails_enviados_ibfk_2` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `emails_logs`
--
ALTER TABLE `emails_logs`
  ADD CONSTRAINT `emails_logs_ibfk_1` FOREIGN KEY (`idemail`) REFERENCES `emails_enviados` (`idemail`) ON DELETE SET NULL,
  ADD CONSTRAINT `emails_logs_ibfk_2` FOREIGN KEY (`idsistema`) REFERENCES `sistemas` (`idsistema`) ON DELETE CASCADE,
  ADD CONSTRAINT `emails_logs_ibfk_3` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `sistemas`
--
ALTER TABLE `sistemas`
  ADD CONSTRAINT `sistemas_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
