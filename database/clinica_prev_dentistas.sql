-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 14/03/2026 às 04:00
-- Versão do servidor: 8.4.7
-- Versão do PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `clinica_prev_dentistas`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimentos`
--

DROP TABLE IF EXISTS `atendimentos`;
CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `paciente_id` int NOT NULL,
  `id_dentista` int NOT NULL,
  `data_atendimento` datetime NOT NULL,
  `valor_total` decimal(10,2) DEFAULT '0.00',
  `taxa_cartao` decimal(10,2) DEFAULT '0.00',
  `valor_liquido_clinica` decimal(10,2) DEFAULT '0.00',
  `custo_auxiliar` decimal(10,2) NOT NULL DEFAULT '0.00',
  `comissao_dentista` decimal(10,2) DEFAULT '0.00',
  `status_pagamento` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_arquivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_dentista` (`id_dentista`),
  KEY `paciente_id` (`paciente_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atendimentos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimento_pagamentos`
--

DROP TABLE IF EXISTS `atendimento_pagamentos`;
CREATE TABLE IF NOT EXISTS `atendimento_pagamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `forma_pagamento` enum('dinheiro','pix','debito','credito') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `qtd_parcelas` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id_atendimento` (`id_atendimento`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atendimento_pagamentos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimento_procedimentos`
--

DROP TABLE IF EXISTS `atendimento_procedimentos`;
CREATE TABLE IF NOT EXISTS `atendimento_procedimentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `id_procedimento` int NOT NULL,
  `quantidade` int DEFAULT '1',
  `valor_procedimento` decimal(10,2) DEFAULT NULL,
  `custo_auxiliar` decimal(10,2) DEFAULT '0.00',
  `natureza` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_arquivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `local` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status_execucao` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_execucao` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atendimento_procedimentos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

DROP TABLE IF EXISTS `despesas`;
CREATE TABLE IF NOT EXISTS `despesas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descricao` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('fixa','variavel') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_despesa` date NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `despesas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE IF NOT EXISTS `pacientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_paciente` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pacientes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `procedimentos`
--

DROP TABLE IF EXISTS `procedimentos`;
CREATE TABLE IF NOT EXISTS `procedimentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('geral','especializado','protese') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valor_base` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `procedimentos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `perfil` enum('proprietario','recepcionista','dentista') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--


--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `atendimentos`
--
ALTER TABLE `atendimentos`
  ADD CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Restrições para tabelas `atendimento_pagamentos`
--
ALTER TABLE `atendimento_pagamentos`
  ADD CONSTRAINT `atendimento_pagamentos_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
