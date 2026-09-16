-- MedInsight AI — dump completo do banco (estrutura + dados)
-- Gerado a partir do banco tcc25 em uso local.
-- Login de teste: admin@teste.com / medico@teste.com / paciente@teste.com — senha "senha123" para os três.

CREATE DATABASE IF NOT EXISTS TCC25 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE TCC25;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `instituicao_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `admin_owner` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `instituicao_id` (`instituicao_id`),
  CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `admins_ibfk_2` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `auditoria_medica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_medica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `acao` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `data_acao` timestamp NOT NULL DEFAULT current_timestamp(),
  `instituicao_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `idx_auditoria_usuario` (`usuario_id`),
  KEY `idx_auditoria_paciente` (`paciente_id`),
  CONSTRAINT `auditoria_medica_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`),
  CONSTRAINT `auditoria_medica_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `auditoria_medica_ibfk_3` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `auditoria_medica` WRITE;
/*!40000 ALTER TABLE `auditoria_medica` DISABLE KEYS */;
INSERT INTO `auditoria_medica` VALUES (1,2,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 01:18:29',1),(2,1,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 01:19:25',1),(3,1,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 01:24:44',1),(4,1,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 01:26:01',1),(5,2,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 16:09:11',1),(6,2,NULL,'Enviou Mensagem','Enviou mensagem para usuário ID: 3','::1','2026-09-16 16:09:22',1),(7,1,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 16:09:45',1),(8,3,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 16:09:54',1),(9,3,NULL,'Enviou Mensagem','Enviou mensagem para usuário ID: 2','::1','2026-09-16 16:09:54',1),(10,1,NULL,'Login','Usuário efetuou login no sistema.','::1','2026-09-16 16:10:04',1);
/*!40000 ALTER TABLE `auditoria_medica` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `diagnosticos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diagnosticos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `data_diagnostico` date DEFAULT NULL,
  `tipo_em` varchar(5) DEFAULT NULL,
  `surtos` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `diagnosticos_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diagnosticos_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `diagnosticos` WRITE;
/*!40000 ALTER TABLE `diagnosticos` DISABLE KEYS */;
/*!40000 ALTER TABLE `diagnosticos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `exame_fisico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exame_fisico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `exame_neurologico` text DEFAULT NULL,
  `forca_muscular` text DEFAULT NULL,
  `reflexos` text DEFAULT NULL,
  `coordenacao` text DEFAULT NULL,
  `sensibilidade` text DEFAULT NULL,
  `equilibrio` text DEFAULT NULL,
  `funcao_visual` text DEFAULT NULL,
  `outros_exames_fisicos` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `exame_fisico_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exame_fisico_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `exame_fisico` WRITE;
/*!40000 ALTER TABLE `exame_fisico` DISABLE KEYS */;
/*!40000 ALTER TABLE `exame_fisico` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `exames_complementares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exames_complementares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `rm_cerebro_medula` text DEFAULT NULL,
  `potenciais_evocados_visuais` text DEFAULT NULL,
  `potenciais_evocados_somatossensoriais` text DEFAULT NULL,
  `potenciais_evocados_auditivos_de_tronco_encefalico` text DEFAULT NULL,
  `analise_do_liquido_cefalorraquidiano` text DEFAULT NULL,
  `outros_exames` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `exames_complementares_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exames_complementares_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `exames_complementares` WRITE;
/*!40000 ALTER TABLE `exames_complementares` DISABLE KEYS */;
/*!40000 ALTER TABLE `exames_complementares` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `historico_acessos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historico_acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data_acesso` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historico_acessos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `historico_acessos` WRITE;
/*!40000 ALTER TABLE `historico_acessos` DISABLE KEYS */;
/*!40000 ALTER TABLE `historico_acessos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `historico_medico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historico_medico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medicamento_em_uso` text DEFAULT NULL,
  `tratamentos_anteriores_em` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `historico_outras_doencas` text DEFAULT NULL,
  `historico_familiar` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `historico_medico_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_medico_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `historico_medico` WRITE;
/*!40000 ALTER TABLE `historico_medico` DISABLE KEYS */;
/*!40000 ALTER TABLE `historico_medico` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `historico_social`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historico_social` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `tabagismo` varchar(50) DEFAULT NULL,
  `alcool` varchar(100) DEFAULT NULL,
  `atividade_fisica` text DEFAULT NULL,
  `suporte_social` text DEFAULT NULL,
  `impacto_profissional_social` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `historico_social_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_social_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `historico_social` WRITE;
/*!40000 ALTER TABLE `historico_social` DISABLE KEYS */;
/*!40000 ALTER TABLE `historico_social` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ia_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ia_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `imagem` longtext DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `data_diagnostico` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `ia_results_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ia_results_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ia_results` WRITE;
/*!40000 ALTER TABLE `ia_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `ia_results` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `instituicao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instituicao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `cep` varchar(12) NOT NULL,
  `logradouro` text NOT NULL,
  `cidade` varchar(60) NOT NULL,
  `bairro` varchar(50) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `tipo` enum('publico','privado','filantropico') NOT NULL,
  `telefone` varchar(25) NOT NULL,
  `email` varchar(155) NOT NULL,
  `site` text DEFAULT NULL,
  `atividade` enum('ativo','inativo') DEFAULT 'ativo',
  `nome_responsavel` varchar(100) NOT NULL,
  `telefone_responsavel` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `instituicao` WRITE;
/*!40000 ALTER TABLE `instituicao` DISABLE KEYS */;
INSERT INTO `instituicao` VALUES (1,'Hospital Teste','12200-000','Rua A','São José','Centro','12345678000199','privado','(12)99999-9999','contato@hospital.com','https://hospital.com','ativo','Diretor Teste','(12)98888-8888');
/*!40000 ALTER TABLE `instituicao` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `licencas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licencas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(512) NOT NULL,
  `instituicao_id` int(11) DEFAULT NULL,
  `status` enum('ativa','inativa') DEFAULT 'ativa',
  `usado` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_em` timestamp NULL DEFAULT NULL,
  `plano_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `plano_id` (`plano_id`),
  KEY `instituicao_id` (`instituicao_id`),
  CONSTRAINT `licencas_ibfk_1` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`),
  CONSTRAINT `licencas_ibfk_2` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `licencas` WRITE;
/*!40000 ALTER TABLE `licencas` DISABLE KEYS */;
INSERT INTO `licencas` VALUES (1,'212fcd6fd4b54ccf8e5d346edee5298141b571754e164feb839f284ddd310bf4',1,'ativa',1,'2026-09-16 01:18:02','2027-09-16 01:18:02',3);
/*!40000 ALTER TABLE `licencas` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `medico_paciente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medico_paciente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medico_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medico_id` (`medico_id`,`paciente_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `medico_paciente_ibfk_1` FOREIGN KEY (`medico_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medico_paciente_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `medico_paciente` WRITE;
/*!40000 ALTER TABLE `medico_paciente` DISABLE KEYS */;
INSERT INTO `medico_paciente` VALUES (1,2,1,NULL);
/*!40000 ALTER TABLE `medico_paciente` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `mensagens_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mensagens_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `para_usuario_id` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `origem_papel_id` int(11) NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `origem_papel_id` (`origem_papel_id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `para_usuario_id` (`para_usuario_id`),
  CONSTRAINT `mensagens_chat_ibfk_1` FOREIGN KEY (`origem_papel_id`) REFERENCES `papeis` (`id`),
  CONSTRAINT `mensagens_chat_ibfk_2` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`),
  CONSTRAINT `mensagens_chat_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `mensagens_chat_ibfk_4` FOREIGN KEY (`para_usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mensagens_chat` WRITE;
/*!40000 ALTER TABLE `mensagens_chat` DISABLE KEYS */;
INSERT INTO `mensagens_chat` VALUES (1,1,2,3,'Olá, paciente! Como você está se sentindo hoje?',2,'2026-09-16 01:18:02',0,NULL),(2,1,2,3,'Agendamento confirmado para a próxima semana.',2,'2026-09-16 01:18:02',0,NULL);
/*!40000 ALTER TABLE `mensagens_chat` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pacientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `instituicao_id` int(11) NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sexo` char(1) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `profissao` varchar(255) DEFAULT NULL,
  `estado_civil` varchar(50) DEFAULT NULL,
  `nome_cuidador` varchar(100) DEFAULT NULL,
  `telefone_cuidador` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `instituicao_id` (`instituicao_id`),
  CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `pacientes_ibfk_2` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pacientes` WRITE;
/*!40000 ALTER TABLE `pacientes` DISABLE KEYS */;
INSERT INTO `pacientes` VALUES (1,3,1,'98765432100','Paciente Teste','M',NULL,'(12)98888-1111',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `pacientes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `papeis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `papeis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `instituicao_id` int(11) NOT NULL,
  `is_delete` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`,`instituicao_id`),
  KEY `instituicao_id` (`instituicao_id`),
  CONSTRAINT `papeis_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `papeis` WRITE;
/*!40000 ALTER TABLE `papeis` DISABLE KEYS */;
INSERT INTO `papeis` VALUES (1,'ADMIN','Administrador do sistema',1,0),(2,'MEDICO','Médico responsável',1,0),(3,'PACIENTE','Paciente cadastrado',1,0);
/*!40000 ALTER TABLE `papeis` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `papeis_permissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `papeis_permissoes` (
  `papel_id` int(11) NOT NULL,
  `permissao_id` int(11) NOT NULL,
  PRIMARY KEY (`papel_id`,`permissao_id`),
  KEY `permissao_id` (`permissao_id`),
  CONSTRAINT `papeis_permissoes_ibfk_1` FOREIGN KEY (`papel_id`) REFERENCES `papeis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `papeis_permissoes_ibfk_2` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `papeis_permissoes` WRITE;
/*!40000 ALTER TABLE `papeis_permissoes` DISABLE KEYS */;
INSERT INTO `papeis_permissoes` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30),(1,31),(1,32),(1,33),(1,34),(1,35),(1,36),(1,37),(1,38),(1,39),(1,40),(1,41),(1,42),(1,43),(1,44),(1,45),(1,46),(1,47),(1,48),(1,49),(1,50),(1,51),(1,52),(2,16),(2,17),(2,19),(2,21),(2,22),(2,23),(2,24),(2,25),(2,26),(2,27),(2,29),(2,30),(2,32),(2,33),(2,34),(2,35),(2,36),(2,39),(2,40),(2,41),(3,25),(3,30),(3,32),(3,33),(3,34),(3,36),(3,39);
/*!40000 ALTER TABLE `papeis_permissoes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `permissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `permissoes` WRITE;
/*!40000 ALTER TABLE `permissoes` DISABLE KEYS */;
INSERT INTO `permissoes` VALUES (1,'usuario.listar',NULL),(2,'usuario.visualizar',NULL),(3,'usuario.criar',NULL),(4,'usuario.editar',NULL),(5,'usuario.deletar',NULL),(6,'papel.listar',NULL),(7,'papel.criar',NULL),(8,'papel.deletar',NULL),(9,'papel.vincular_permissao',NULL),(10,'permissao.listar',NULL),(11,'admin.listar',NULL),(12,'admin.visualizar',NULL),(13,'papel.atribuir',NULL),(14,'admin.editar',NULL),(15,'admin.deletar',NULL),(16,'medico.listar',NULL),(17,'medico.visualizar',NULL),(18,'medico.criar',NULL),(19,'medico.editar',NULL),(20,'medico.deletar',NULL),(21,'medico.vincular_paciente',NULL),(22,'medico.listar_pacientes',NULL),(23,'medico.desvincular_paciente',NULL),(24,'paciente.listar',NULL),(25,'paciente.visualizar',NULL),(26,'paciente.criar',NULL),(27,'paciente.editar',NULL),(28,'paciente.deletar',NULL),(29,'ia.criar',NULL),(30,'ia.listar',NULL),(31,'ia.deletar',NULL),(32,'ia.visualizar_imagem',NULL),(33,'chat.enviar',NULL),(34,'chat.listar',NULL),(35,'chat.marcar_lida',NULL),(36,'chat.visualizar',NULL),(37,'auditoria.listar',NULL),(38,'auditoria.criar',NULL),(39,'anamnese.listar',NULL),(40,'anamnese.criar',NULL),(41,'anamnese.editar',NULL),(42,'anamnese.deletar',NULL),(43,'instituicao.visualizar',NULL),(44,'instituicao.criar',NULL),(45,'instituicao.editar',NULL),(46,'instituicao.deletar',NULL),(47,'licenca.visualizar',NULL),(48,'licenca.criar',NULL),(49,'licenca.ativar',NULL),(50,'licenca.renovar',NULL),(51,'plano.alterar',NULL),(52,'plano.visualizar',NULL);
/*!40000 ALTER TABLE `permissoes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `plano_tratamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plano_tratamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medicamentos_modificadores_doenca` text DEFAULT NULL,
  `tratamento_surtos` text DEFAULT NULL,
  `tratamento_sintomas` text DEFAULT NULL,
  `reabilitacao` text DEFAULT NULL,
  `acompanhamento_psicologico` text DEFAULT NULL,
  `outras_terapias` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `plano_tratamento_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plano_tratamento_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `plano_tratamento` WRITE;
/*!40000 ALTER TABLE `plano_tratamento` DISABLE KEYS */;
/*!40000 ALTER TABLE `plano_tratamento` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `planos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `limite_usuarios` int(11) DEFAULT NULL,
  `limite_papeis` int(11) DEFAULT NULL,
  `limite_ia` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `duracao_dias` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `planos` WRITE;
/*!40000 ALTER TABLE `planos` DISABLE KEYS */;
INSERT INTO `planos` VALUES (1,'Básico','Plano ideal para clínicas pequenas',5,1,50,199.90,30,1,'2026-09-16 01:18:01',NULL),(2,'Profissional','Plano intermediário',15,3,300,499.90,30,1,'2026-09-16 01:18:01',NULL),(3,'Enterprise','Ilimitado para grandes instituições',NULL,NULL,NULL,1299.90,30,1,'2026-09-16 01:18:01',NULL);
/*!40000 ALTER TABLE `planos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `qualidade_vida_em`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qualidade_vida_em` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `edss` float DEFAULT NULL,
  `questionario_msqol54` text DEFAULT NULL,
  `outras_avaliacoes` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `qualidade_vida_em_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qualidade_vida_em_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `qualidade_vida_em` WRITE;
/*!40000 ALTER TABLE `qualidade_vida_em` DISABLE KEYS */;
/*!40000 ALTER TABLE `qualidade_vida_em` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refresh_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(512) NOT NULL,
  `expira_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `revogado` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_refresh_usuario` (`usuario_id`),
  CONSTRAINT `refresh_tokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `refresh_tokens` WRITE;
/*!40000 ALTER TABLE `refresh_tokens` DISABLE KEYS */;
INSERT INTO `refresh_tokens` VALUES (2,1,'$2y$10$/3WQzlenpMMjFqOlTf3OW.A8srLwwPR4zql47QMVPMGBk87T8iNrC','2026-10-16 06:19:25',0,NULL),(3,1,'$2y$10$xcAzeNC0kMk5bmfcUbXBJeQZNjfhIyVUuuy9T6Hs0NaEngwqduVbS','2026-10-16 06:24:44',0,NULL),(4,1,'$2y$10$ZyhTSQBZ7o51eUqzsj2fIevVTi5tAEPJNqTvyispHaCCSn2tILzQO','2026-10-16 06:26:01',0,NULL),(7,1,'9a2e234feceba2ec301cd268fd899d49dc3b4b84788b8dc0b1d6c7cf89891b0a','2026-10-16 21:09:45',0,NULL),(8,3,'3c0600c0028c3b73e09754abfb9b99e8a249d1e539f08d985caf6d2f6080072b','2026-10-16 21:09:54',0,NULL),(9,1,'3f88c8f7a927db0949042469e3ca16eda5a7820aae12c36a6d1d46a9cee7ca49','2026-10-16 21:10:04',0,NULL);
/*!40000 ALTER TABLE `refresh_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sintomas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sintomas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `sintomas_iniciais` text DEFAULT NULL,
  `sintomas_atuais` text DEFAULT NULL,
  `fadiga` tinyint(1) DEFAULT NULL,
  `problema_visao` varchar(100) DEFAULT NULL,
  `problema_equilibrio` tinyint(1) DEFAULT NULL,
  `problema_coordenacao` tinyint(1) DEFAULT NULL,
  `espaticidade` tinyint(1) DEFAULT NULL,
  `fraqueza_muscular` tinyint(1) DEFAULT NULL,
  `problema_sensibilidade` varchar(100) DEFAULT NULL,
  `problema_bexiga` tinyint(1) DEFAULT NULL,
  `problema_intestino` tinyint(1) DEFAULT NULL,
  `problema_cognitivo` varchar(255) DEFAULT NULL,
  `problema_emocional` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `sintomas_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sintomas_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sintomas` WRITE;
/*!40000 ALTER TABLE `sintomas` DISABLE KEYS */;
/*!40000 ALTER TABLE `sintomas` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `admin_owner` tinyint(1) NOT NULL DEFAULT 0,
  `nome` varchar(150) DEFAULT NULL,
  `cpf` varchar(11) DEFAULT NULL,
  `crm` varchar(20) DEFAULT NULL,
  `especialidade` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `tentativas_login` int(11) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instituicao_id` (`instituicao_id`,`email`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `idx_usuarios_email` (`email`),
  KEY `idx_usuarios_deleted` (`deleted_at`),
  CONSTRAINT `fk_usuario_instituicao` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,'admin@teste.com','$2y$10$X/afuQ0lkshdUCMYu39EYuDaHOga5PpLBzPeG8oD./2Ziq9ybFfUu',1,'Admin Teste','11111111111',NULL,NULL,1,NULL,0,'2026-09-16 01:18:02',NULL),(2,1,'medico@teste.com','$2y$10$X/afuQ0lkshdUCMYu39EYuDaHOga5PpLBzPeG8oD./2Ziq9ybFfUu',0,'Dr. Médico Teste','22222222222','SP123456','Neurologia',1,NULL,0,'2026-09-16 01:18:02',NULL),(3,1,'paciente@teste.com','$2y$10$X/afuQ0lkshdUCMYu39EYuDaHOga5PpLBzPeG8oD./2Ziq9ybFfUu',0,'Paciente Teste','33333333333',NULL,NULL,1,NULL,0,'2026-09-16 01:18:02',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usuarios_papeis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios_papeis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituicao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `papel_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `instituicao_id` (`instituicao_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `papel_id` (`papel_id`),
  CONSTRAINT `usuarios_papeis_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuarios_papeis_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuarios_papeis_ibfk_3` FOREIGN KEY (`papel_id`) REFERENCES `papeis` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usuarios_papeis` WRITE;
/*!40000 ALTER TABLE `usuarios_papeis` DISABLE KEYS */;
INSERT INTO `usuarios_papeis` VALUES (1,1,1,1,'2026-09-16 01:18:02'),(2,1,2,2,'2026-09-16 01:18:02'),(3,1,3,3,'2026-09-16 01:18:02');
/*!40000 ALTER TABLE `usuarios_papeis` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

