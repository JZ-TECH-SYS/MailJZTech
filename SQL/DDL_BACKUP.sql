-- ============================================
-- DDL: Sistema de Backup Automatizado
-- Projeto: MailJZTech
-- Data: 09/11/2025
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
--
-- Estrutura para tabela `backup_banco_config`
--

CREATE TABLE IF NOT EXISTS `backup_banco_config` (
  `idbackup_banco_config` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_banco` VARCHAR(120) NOT NULL COMMENT 'Nome do banco de dados a ser backupeado',
  `bucket_nome` VARCHAR(120) NOT NULL DEFAULT 'dbjztech' COMMENT 'Nome do bucket no Google Cloud Storage',
  `pasta_base` VARCHAR(200) NOT NULL COMMENT 'Pasta raiz no bucket para organizar backups',
  `retencao_dias` INT NOT NULL DEFAULT 5 COMMENT 'Quantidade de dias para manter backups',
  `ultimo_backup_em` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data/hora do último backup realizado',
  `total_backups` INT NOT NULL DEFAULT 0 COMMENT 'Contador total de backups realizados',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Status da configuração (1=ativo, 0=inativo)',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `ux_nome_banco` (`nome_banco`),
  INDEX `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estrutura para tabela `backup_execucao_log`
--

CREATE TABLE IF NOT EXISTS `backup_execucao_log` (
  `idbackup_execucao_log` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `idbackup_banco_config` INT NOT NULL COMMENT 'Referência à configuração do banco',
  `iniciado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Início da execução do backup',
  `finalizado_em` TIMESTAMP NULL DEFAULT NULL COMMENT 'Término da execução do backup',
  `status` VARCHAR(20) NOT NULL DEFAULT 'running' COMMENT 'Status: running, success, error, pruned',
  `mensagem_erro` TEXT NULL COMMENT 'Detalhes do erro caso status=error',
  `arquivo_local` VARCHAR(500) NULL COMMENT 'Caminho temporário do arquivo gerado',
  `gcs_objeto` VARCHAR(500) NULL COMMENT 'Caminho do objeto no Google Cloud Storage',
  `tamanho_bytes` BIGINT NULL COMMENT 'Tamanho do arquivo em bytes',
  `checksum_sha256` VARCHAR(64) NULL COMMENT 'Hash SHA256 para verificação de integridade',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`idbackup_banco_config`) REFERENCES `backup_banco_config`(`idbackup_banco_config`) ON DELETE CASCADE,
  INDEX `idx_config` (`idbackup_banco_config`),
  INDEX `idx_status` (`status`),
  INDEX `idx_iniciado_em` (`iniciado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Inserir configuração de exemplo (opcional)
--

-- INSERT INTO `backup_banco_config` 
--   (`nome_banco`, `bucket_nome`, `pasta_base`, `retencao_dias`, `ativo`) 
-- VALUES 
--   ('mailjztech', 'dbjztech', 'mailjztech_prod', 7, 1);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
