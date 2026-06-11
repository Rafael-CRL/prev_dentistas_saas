-- =============================================================================
-- AUDITORIA TÉCNICA FINAL - FASE 3 (SaaS Multi-tenant)
-- Migração consolidada para Prev-Dentistas
-- Alinhado com: DB.md e Planejamento.md (Ajustado para Schema Remoto)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. AJUSTE DA TABELA MESTRA DE CLÍNICAS (Sincronização com remoto)
-- A tabela já existe remotamente com 'nome_fantasia' e 'razao_social'
CREATE TABLE IF NOT EXISTS `clinicas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome_fantasia` VARCHAR(100) NOT NULL,
    `razao_social` VARCHAR(100) DEFAULT NULL,
    `cnpj` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX `uk_cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CADASTRO DA CLÍNICA PADRÃO (Se não existir)
INSERT IGNORE INTO `clinicas` (`id`, `nome_fantasia`, `status`) VALUES (1, 'Clínica Principal Prev-Dentistas', 'ativo');
SET @default_clinica_id = 1;

-- 3. NOVAS TABELAS ADMINISTRATIVAS (Zero Hardcode)

-- Personalização flexível (Chave-Valor)
CREATE TABLE IF NOT EXISTS `clinica_configuracoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clinica_id` INT NOT NULL,
    `chave` VARCHAR(50) NOT NULL,
    `valor` TEXT,
    CONSTRAINT `fk_config_clinica` FOREIGN KEY (`clinica_id`) REFERENCES `clinicas` (`id`) ON DELETE CASCADE,
    UNIQUE INDEX `uk_clinica_chave` (`clinica_id`, `chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parâmetros de taxas de maquininha
CREATE TABLE IF NOT EXISTS `clinica_taxas_cartao` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clinica_id` INT NOT NULL,
    `bandeira` VARCHAR(50) NOT NULL,
    `modalidade` ENUM('debito', 'credito') NOT NULL,
    `parcelas` INT DEFAULT 1,
    `taxa_percentual` DECIMAL(5,2) NOT NULL,
    CONSTRAINT `fk_taxas_clinica` FOREIGN KEY (`clinica_id`) REFERENCES `clinicas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regras de repasse para dentistas
CREATE TABLE IF NOT EXISTS `clinica_regras_comissao` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clinica_id` INT NOT NULL,
    `tipo` ENUM('fixo', 'percentual') NOT NULL,
    `valor_regra` DECIMAL(10,2) NOT NULL,
    `valor_meta` DECIMAL(10,2) DEFAULT NULL,
    `percentual_bonus` DECIMAL(5,2) DEFAULT NULL,
    CONSTRAINT `fk_comissao_clinica` FOREIGN KEY (`clinica_id`) REFERENCES `clinicas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ATUALIZAÇÃO DAS TABELAS EXISTENTES (Isolamento de Dados)

-- Funções auxiliares para adicionar coluna clinica_id se não existir
-- Nota: Como o script será rodado via PDO exec ou ferramenta similar, usamos IF NOT EXISTS para colunas via verificação manual em scripts ou tentando ALTER e ignorando erro,
-- mas para migration.sql pura, assumimos que as tabelas existem e não têm a coluna ainda.

-- Usuarios
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `clinica_id` INT NOT NULL AFTER `id`;
UPDATE `usuarios` SET `clinica_id` = @default_clinica_id WHERE `clinica_id` = 0;
-- Tenta dropar o índice antigo (se existir) e cria o novo
ALTER TABLE `usuarios` DROP INDEX IF EXISTS `login`;
ALTER TABLE `usuarios` ADD UNIQUE INDEX IF NOT EXISTS `uk_usuario_clinica_login` (`clinica_id`, `login`);
-- FK (Se não existir)
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'usuarios' AND CONSTRAINT_NAME = 'fk_usuarios_clinica' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@exist = 0, 'ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Pacientes
ALTER TABLE `pacientes` ADD COLUMN IF NOT EXISTS `clinica_id` INT NOT NULL AFTER `id`;
UPDATE `pacientes` SET `clinica_id` = @default_clinica_id WHERE `clinica_id` = 0;
ALTER TABLE `pacientes` DROP INDEX IF EXISTS `cpf`;
ALTER TABLE `pacientes` ADD UNIQUE INDEX IF NOT EXISTS `uk_paciente_clinica_cpf` (`clinica_id`, `cpf`);
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'pacientes' AND CONSTRAINT_NAME = 'fk_pacientes_clinica' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@exist = 0, 'ALTER TABLE pacientes ADD CONSTRAINT fk_pacientes_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Procedimentos
ALTER TABLE `procedimentos` ADD COLUMN IF NOT EXISTS `clinica_id` INT NOT NULL AFTER `id`;
UPDATE `procedimentos` SET `clinica_id` = @default_clinica_id WHERE `clinica_id` = 0;
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'procedimentos' AND CONSTRAINT_NAME = 'fk_procedimentos_clinica' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@exist = 0, 'ALTER TABLE procedimentos ADD CONSTRAINT fk_procedimentos_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Atendimentos
ALTER TABLE `atendimentos` ADD COLUMN IF NOT EXISTS `clinica_id` INT NOT NULL AFTER `id`;
UPDATE `atendimentos` SET `clinica_id` = @default_clinica_id WHERE `clinica_id` = 0;
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'atendimentos' AND CONSTRAINT_NAME = 'fk_atendimentos_clinica' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@exist = 0, 'ALTER TABLE atendimentos ADD CONSTRAINT fk_atendimentos_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Despesas
ALTER TABLE `despesas` ADD COLUMN IF NOT EXISTS `clinica_id` INT NOT NULL AFTER `id`;
UPDATE `despesas` SET `clinica_id` = @default_clinica_id WHERE `clinica_id` = 0;
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'despesas' AND CONSTRAINT_NAME = 'fk_despesas_clinica' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@exist = 0, 'ALTER TABLE despesas ADD CONSTRAINT fk_despesas_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Atendimento Procedimentos
ALTER TABLE `atendimento_procedimentos` ADD COLUMN IF NOT EXISTS `clinica_id` INT NOT NULL AFTER `id`;
UPDATE `atendimento_procedimentos` SET `clinica_id` = @default_clinica_id WHERE `clinica_id` = 0;
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'atendimento_procedimentos' AND CONSTRAINT_NAME = 'fk_atendimento_procedimentos_clinica' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@exist = 0, 'ALTER TABLE atendimento_procedimentos ADD CONSTRAINT fk_atendimento_procedimentos_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Atendimento Pagamentos
ALTER TABLE `atendimento_pagamentos` ADD COLUMN IF NOT EXISTS `clinica_id` INT NOT NULL AFTER `id`;
UPDATE `atendimento_pagamentos` SET `clinica_id` = @default_clinica_id WHERE `clinica_id` = 0;
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'atendimento_pagamentos' AND CONSTRAINT_NAME = 'fk_atendimento_pagamentos_clinica' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@exist = 0, 'ALTER TABLE atendimento_pagamentos ADD CONSTRAINT fk_atendimento_pagamentos_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. CARGA INICIAL DE PARÂMETROS (Necessário para Fase 4 e Zero Hardcode)
INSERT IGNORE INTO `clinica_taxas_cartao` (`clinica_id`, `bandeira`, `modalidade`, `parcelas`, `taxa_percentual`) VALUES
(@default_clinica_id, 'Visa', 'debito', 1, 1.50),
(@default_clinica_id, 'Master', 'debito', 1, 1.50),
(@default_clinica_id, 'Visa', 'credito', 1, 2.99),
(@default_clinica_id, 'Master', 'credito', 1, 2.99),
(@default_clinica_id, 'default', 'debito', 1, 0.99),
(@default_clinica_id, 'default', 'credito', 1, 2.99),
(@default_clinica_id, 'default', 'credito', 2, 5.00),
(@default_clinica_id, 'default', 'credito', 3, 5.00),
(@default_clinica_id, 'default', 'credito', 4, 5.00),
(@default_clinica_id, 'default', 'credito', 5, 5.00),
(@default_clinica_id, 'default', 'credito', 6, 5.00),
(@default_clinica_id, 'default', 'credito', 7, 10.76),
(@default_clinica_id, 'default', 'credito', 8, 10.76),
(@default_clinica_id, 'default', 'credito', 9, 10.76),
(@default_clinica_id, 'default', 'credito', 10, 10.76),
(@default_clinica_id, 'default', 'credito', 11, 10.76),
(@default_clinica_id, 'default', 'credito', 12, 10.76);

INSERT IGNORE INTO `clinica_regras_comissao` (`clinica_id`, `tipo`, `valor_regra`, `valor_meta`, `percentual_bonus`) VALUES
(@default_clinica_id, 'percentual', 20.00, 10000.00, 5.00);

INSERT IGNORE INTO `clinica_configuracoes` (`clinica_id`, `chave`, `valor`) VALUES
(@default_clinica_id, 'comissao_especializado', '50.00'),
(@default_clinica_id, 'comissao_canal', '10.00'),
(@default_clinica_id, 'comissao_protese', '10.00'),
(@default_clinica_id, 'clinica_endereco', 'Rua União 1, Esquina com a Rua D - Atalaia, Ananindeua - PA, 67013-350'),
(@default_clinica_id, 'clinica_telefone', '(91) 98306-7459');

SET FOREIGN_KEY_CHECKS = 1;
