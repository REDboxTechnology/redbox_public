-- MySQL dump 10.13  Distrib 8.0.18, for Win64 (x86_64)
--
-- Host: db-mysql-02.mysql.database.azure.com    Database: projetos_tuberculose
-- ------------------------------------------------------
-- Server version	5.6.47.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `configuracao` varchar(255) NOT NULL DEFAULT '',
  `valor` varchar(255) NOT NULL DEFAULT '',
  `descricao` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `form_data`
--

DROP TABLE IF EXISTS `form_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT '0',
  `redcap_record_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `unique_id` varchar(50) NOT NULL DEFAULT '0',
  `first_form` bit(1) NOT NULL DEFAULT b'0',
  `form_id` varchar(5) NOT NULL DEFAULT '',
  `json_data` text NOT NULL,
  `parsed_json_data` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` varchar(150) NOT NULL,
  `operation` varchar(6) NOT NULL,
  `semantics` text,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `form_data_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=138670 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `form_metadata`
--

DROP TABLE IF EXISTS `form_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `redcap_form_id` int(11) NOT NULL,
  `field_name` varchar(30) NOT NULL,
  `identifier` tinyint(1) NOT NULL,
  `semantic_annotation` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `data_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `redcap_form_id_fk_idx` (`redcap_form_id`),
  CONSTRAINT `redcap_form_id_fk` FOREIGN KEY (`redcap_form_id`) REFERENCES `redcap_forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `form_uploads`
--

DROP TABLE IF EXISTS `form_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT '0',
  `token` varchar(32) NOT NULL DEFAULT '',
  `file_name` varchar(150) NOT NULL DEFAULT '',
  `file_type` varchar(50) NOT NULL DEFAULT '',
  `file_size` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `form_uploads_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_alerts`
--

DROP TABLE IF EXISTS `redcap_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `event_name` varchar(45) NOT NULL,
  `days` int(11) NOT NULL,
  `days_offset` int(11) NOT NULL DEFAULT '0',
  `days_frequency` int(11) NOT NULL,
  `ref_field` varchar(45) NOT NULL,
  `short_description` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `filter_logic` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_form_idx` (`form_id`),
  KEY `fk_category_idx` (`category_id`),
  CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `redcap_alerts_category` (`id`),
  CONSTRAINT `fk_form` FOREIGN KEY (`form_id`) REFERENCES `redcap_forms` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_alerts_category`
--

DROP TABLE IF EXISTS `redcap_alerts_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_alerts_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_alerts_dag`
--

DROP TABLE IF EXISTS `redcap_alerts_dag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_alerts_dag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dag_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `redcap_name` varchar(45) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `recipients` varchar(1000) NOT NULL DEFAULT 'gd@redbox.technology',
  PRIMARY KEY (`id`),
  KEY `project_id_idx` (`project_id`),
  KEY `category_id_idx` (`category_id`),
  CONSTRAINT `category_id` FOREIGN KEY (`category_id`) REFERENCES `redcap_alerts_category` (`id`),
  CONSTRAINT `project_id` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_alerts_log`
--

DROP TABLE IF EXISTS `redcap_alerts_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_alerts_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_dag_id` int(11) NOT NULL,
  `alert_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `record` varchar(10) NOT NULL,
  `deadline` date NOT NULL,
  `deadline_expired` tinyint(1) NOT NULL,
  `alert_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent` tinyint(1) NOT NULL DEFAULT '0',
  `sent_timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_fk_idx` (`alert_id`),
  KEY `alert_dag_fk_idx` (`alert_dag_id`),
  KEY `alertdag_fk_idx` (`alert_dag_id`),
  KEY `category_fk_idx` (`category_id`),
  CONSTRAINT `alert_fk` FOREIGN KEY (`alert_id`) REFERENCES `redcap_alerts` (`id`),
  CONSTRAINT `alertdag_fk` FOREIGN KEY (`alert_dag_id`) REFERENCES `redcap_alerts_dag` (`id`),
  CONSTRAINT `category_fk` FOREIGN KEY (`category_id`) REFERENCES `redcap_alerts_category` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21428 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_alerts_queries_log`
--

DROP TABLE IF EXISTS `redcap_alerts_queries_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_alerts_queries_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `dag_id` int(11) NOT NULL,
  `queries_count` int(11) NOT NULL,
  `alert_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id_fk_idx` (`project_id`),
  CONSTRAINT `project_id_fk` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_autovalidation_log`
--

DROP TABLE IF EXISTS `redcap_autovalidation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_autovalidation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` varchar(45) NOT NULL,
  `pid` int(11) NOT NULL,
  `form_name` varchar(150) NOT NULL,
  `event` varchar(150) NOT NULL,
  `instance` int(11) NOT NULL DEFAULT '1',
  `redcap_instance` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `checked` tinyint(1) NOT NULL,
  `fail_reason` varchar(150) DEFAULT NULL,
  `fail_field` varchar(45) DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `source` varchar(45) NOT NULL DEFAULT 'det',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=206979 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_crf_validation_comments`
--

DROP TABLE IF EXISTS `redcap_crf_validation_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_crf_validation_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `field_name` varchar(45) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` varchar(45) NOT NULL,
  `form_name` varchar(100) NOT NULL,
  `comment_text` text NOT NULL,
  `username` varchar(45) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_project_comments_idx` (`project_id`),
  CONSTRAINT `fk_project_comments` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_crf_validation_replies`
--

DROP TABLE IF EXISTS `redcap_crf_validation_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_crf_validation_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comment_idx` (`comment_id`),
  CONSTRAINT `fk_comment` FOREIGN KEY (`comment_id`) REFERENCES `redcap_crf_validation_comments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_duplicates`
--

DROP TABLE IF EXISTS `redcap_duplicates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_duplicates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `record_id` varchar(10) NOT NULL,
  `record_id_duplicate` varchar(10) NOT NULL,
  `date_duplicate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `username` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_project_id_idx` (`project_id`),
  CONSTRAINT `fk_project_id_duplicate` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=613 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_external_databases`
--

DROP TABLE IF EXISTS `redcap_external_databases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_external_databases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `db_name` varchar(45) NOT NULL,
  `host` varchar(45) NOT NULL,
  `user` varchar(45) NOT NULL,
  `password` varchar(45) NOT NULL,
  `port` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_forms`
--

DROP TABLE IF EXISTS `redcap_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT '0',
  `form_id` varchar(5) DEFAULT '0',
  `form_name` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(100) NOT NULL DEFAULT '',
  `first_form` tinyint(1) NOT NULL DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '0',
  `kobo_url` varchar(255) DEFAULT NULL,
  `enable_notification` tinyint(1) NOT NULL DEFAULT '0',
  `email_notification` varchar(150) DEFAULT NULL,
  `is_repeatable` tinyint(1) NOT NULL DEFAULT '0',
  `is_in_event` tinyint(1) NOT NULL DEFAULT '0',
  `unique_id_field` varchar(45) DEFAULT NULL,
  `kaleido_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `respondent_confirmation` tinyint(1) NOT NULL DEFAULT '0',
  `respondent_email_field` varchar(100) DEFAULT NULL,
  `author_field` varchar(45) DEFAULT NULL,
  `autolock` tinyint(1) NOT NULL DEFAULT '0',
  `autolock_autovalidate` tinyint(1) NOT NULL DEFAULT '0',
  `autolock_admin_only` tinyint(1) NOT NULL DEFAULT '0',
  `autolock_unlock_duplicate` tinyint(1) NOT NULL DEFAULT '0',
  `autolock_unlock_non_complete` tinyint(1) NOT NULL DEFAULT '0',
  `check_duplicity` tinyint(1) NOT NULL DEFAULT '0',
  `check_duplicity_fields` varchar(255) DEFAULT NULL,
  `check_data_quality` tinyint(1) NOT NULL DEFAULT '0',
  `force_unverified` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `redcap_forms_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_projects`
--

DROP TABLE IF EXISTS `redcap_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project` varchar(255) NOT NULL DEFAULT '',
  `redcap_pid` int(11) DEFAULT NULL,
  `api_token` varchar(50) DEFAULT '',
  `api_url` varchar(255) DEFAULT NULL,
  `api_token_deidentified` varchar(50) DEFAULT NULL,
  `database_name` varchar(45) DEFAULT NULL,
  `alert_queries` tinyint(1) NOT NULL DEFAULT '0',
  `export_csv_data` tinyint(1) NOT NULL DEFAULT '0',
  `kld_user` varchar(45) DEFAULT NULL,
  `kld_passwd` varchar(255) DEFAULT NULL,
  `kld_from` varchar(150) DEFAULT NULL,
  `kld_api_url` varchar(255) DEFAULT NULL,
  `kld_consortia_id` varchar(255) DEFAULT NULL,
  `kld_environment_id` varchar(255) DEFAULT NULL,
  `kld_api_key` varchar(255) DEFAULT NULL,
  `kld_ipfs_api_endpoint` varchar(255) DEFAULT NULL,
  `kld_ipfs_ui_endpoint` varchar(255) DEFAULT NULL,
  `kld_smart_contract_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_user_support`
--

DROP TABLE IF EXISTS `redcap_user_support`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_user_support` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `record_id` varchar(15) NOT NULL,
  `event_unique_name` varchar(45) NOT NULL,
  `form_name` varchar(45) NOT NULL,
  `instance` int(11) NOT NULL DEFAULT '1',
  `action` varchar(45) NOT NULL,
  `reason` text NOT NULL,
  `complete` tinyint(1) NOT NULL DEFAULT '0',
  `support_team_alert_sent` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_project_id_idx` (`project_id`),
  CONSTRAINT `fk_project_user_support` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_user_support_config`
--

DROP TABLE IF EXISTS `redcap_user_support_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_user_support_config` (
  `project_id` int(11) NOT NULL,
  `not_allowed_forms` varchar(255) DEFAULT NULL,
  `event_unique_name_records_filter` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`project_id`),
  KEY `fk_user_support_config_project_idx` (`project_id`),
  CONSTRAINT `fk_user_support_config_project` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_validation_issues`
--

DROP TABLE IF EXISTS `redcap_validation_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_validation_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `validation_rule_id` int(11) NOT NULL,
  `redcap_record_id` varchar(45) NOT NULL,
  `event_id` int(11) NOT NULL,
  `unique_event_name` varchar(255) NOT NULL,
  `form_instance_number` int(11) NOT NULL DEFAULT '1',
  `issue_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_solved` tinyint(1) NOT NULL DEFAULT '0',
  `solved_by` varchar(45) DEFAULT NULL,
  `issue_date_solved` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_validation_rule_fk_idx` (`validation_rule_id`),
  CONSTRAINT `fk_validation_rule_fk` FOREIGN KEY (`validation_rule_id`) REFERENCES `redcap_validation_rules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3262 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_validation_rules`
--

DROP TABLE IF EXISTS `redcap_validation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_validation_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `validation_type_id` int(11) NOT NULL,
  `field_name` varchar(45) NOT NULL,
  `value_to_compare` varchar(45) DEFAULT NULL,
  `compare_other_field` tinyint(1) DEFAULT '0',
  `compare_function` tinyint(1) DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `get_value_from_function` varchar(45) DEFAULT NULL,
  `query_text` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_validation_type_id_idx` (`validation_type_id`),
  KEY `fk_form_id_idx` (`form_id`),
  CONSTRAINT `fk_form_id` FOREIGN KEY (`form_id`) REFERENCES `redcap_forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_validation_type_id` FOREIGN KEY (`validation_type_id`) REFERENCES `redcap_validation_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_validation_types`
--

DROP TABLE IF EXISTS `redcap_validation_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_validation_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(150) NOT NULL,
  `comparator` varchar(255) NOT NULL,
  `is_regex` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_visits`
--

DROP TABLE IF EXISTS `redcap_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_config_id` int(11) NOT NULL,
  `window_name` varchar(100) NOT NULL,
  `days` int(11) NOT NULL,
  `days_before` int(11) NOT NULL DEFAULT '0',
  `days_after` int(11) NOT NULL DEFAULT '0',
  `reference_done_field` varchar(45) NOT NULL,
  `reference_done_field_event` varchar(45) NOT NULL,
  `reference_done_field_value` varchar(45) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `visit_window_fk1_idx` (`visit_config_id`),
  CONSTRAINT `visit_window_fk1` FOREIGN KEY (`visit_config_id`) REFERENCES `redcap_visits_config` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redcap_visits_config`
--

DROP TABLE IF EXISTS `redcap_visits_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redcap_visits_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `reference_date_field` varchar(45) NOT NULL,
  `reference_date_field_event` varchar(45) NOT NULL,
  `custom_record_id_field` varchar(45) DEFAULT NULL,
  `custom_record_id_field_event` varchar(45) DEFAULT NULL,
  `custom_center_field` varchar(45) DEFAULT NULL,
  `custom_center_field_event` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visit_window_fk1_idx` (`project_id`),
  CONSTRAINT `visit_window_config_fk1` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `vw_redcap_alerts`
--

DROP TABLE IF EXISTS `vw_redcap_alerts`;
/*!50001 DROP VIEW IF EXISTS `vw_redcap_alerts`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_redcap_alerts` AS SELECT 
 1 AS `alert_id`,
 1 AS `form_id`,
 1 AS `category_id`,
 1 AS `event_id`,
 1 AS `event_name`,
 1 AS `days`,
 1 AS `days_offset`,
 1 AS `days_frequency`,
 1 AS `ref_field`,
 1 AS `enabled`,
 1 AS `short_description`,
 1 AS `filter_logic`,
 1 AS `form_name`,
 1 AS `project_id`,
 1 AS `project_name`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_redcap_alerts_log`
--

DROP TABLE IF EXISTS `vw_redcap_alerts_log`;
/*!50001 DROP VIEW IF EXISTS `vw_redcap_alerts_log`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_redcap_alerts_log` AS SELECT 
 1 AS `alert_id`,
 1 AS `category_id`,
 1 AS `form_id`,
 1 AS `form_name`,
 1 AS `project_name`,
 1 AS `short_description`,
 1 AS `record`,
 1 AS `deadline`,
 1 AS `deadline_expired`,
 1 AS `alert_timestamp`,
 1 AS `dag_id`,
 1 AS `center_name`,
 1 AS `recipients`,
 1 AS `sent`,
 1 AS `sent_timestamp`,
 1 AS `log_id`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_redcap_user_support_stats`
--

DROP TABLE IF EXISTS `vw_redcap_user_support_stats`;
/*!50001 DROP VIEW IF EXISTS `vw_redcap_user_support_stats`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_redcap_user_support_stats` AS SELECT 
 1 AS `project_id`,
 1 AS `total`,
 1 AS `total_unlock`,
 1 AS `total_delete`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_redcap_validation_issues`
--

DROP TABLE IF EXISTS `vw_redcap_validation_issues`;
/*!50001 DROP VIEW IF EXISTS `vw_redcap_validation_issues`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_redcap_validation_issues` AS SELECT 
 1 AS `rule_id`,
 1 AS `project_id`,
 1 AS `redcap_instance`,
 1 AS `redcap_url`,
 1 AS `redcap_pid`,
 1 AS `project`,
 1 AS `form_id`,
 1 AS `form_name`,
 1 AS `form_name_desc`,
 1 AS `form_instance_number`,
 1 AS `event_id`,
 1 AS `unique_event_name`,
 1 AS `field_name`,
 1 AS `comparator`,
 1 AS `comparator_type`,
 1 AS `is_regex`,
 1 AS `value_to_compare`,
 1 AS `validation_type_id`,
 1 AS `compare_other_field`,
 1 AS `compare_function`,
 1 AS `get_value_from_function`,
 1 AS `issue_id`,
 1 AS `redcap_record_id`,
 1 AS `issue_date`,
 1 AS `is_solved`,
 1 AS `solved_by`,
 1 AS `issue_date_solved`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_redcap_validation_rules`
--

DROP TABLE IF EXISTS `vw_redcap_validation_rules`;
/*!50001 DROP VIEW IF EXISTS `vw_redcap_validation_rules`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_redcap_validation_rules` AS SELECT 
 1 AS `rule_id`,
 1 AS `project_id`,
 1 AS `redcap_pid`,
 1 AS `project_name`,
 1 AS `redcap_instance`,
 1 AS `form_id`,
 1 AS `form_name`,
 1 AS `field_name`,
 1 AS `validation_type_id`,
 1 AS `validation_type_desc`,
 1 AS `comparator`,
 1 AS `is_regex`,
 1 AS `value_to_compare`,
 1 AS `compare_other_field`,
 1 AS `compare_function`,
 1 AS `get_value_from_function`,
 1 AS `enabled`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_redcap_visits`
--

DROP TABLE IF EXISTS `vw_redcap_visits`;
/*!50001 DROP VIEW IF EXISTS `vw_redcap_visits`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_redcap_visits` AS SELECT 
 1 AS `id`,
 1 AS `visit_config_id`,
 1 AS `window_name`,
 1 AS `days`,
 1 AS `days_before`,
 1 AS `days_after`,
 1 AS `reference_done_field`,
 1 AS `reference_done_field_event`,
 1 AS `reference_done_field_value`,
 1 AS `order`,
 1 AS `reference_date_field`,
 1 AS `reference_date_field_event`,
 1 AS `custom_record_id_field`,
 1 AS `custom_record_id_field_event`,
 1 AS `custom_center_field`,
 1 AS `custom_center_field_event`,
 1 AS `project_id`,
 1 AS `redcap_pid`,
 1 AS `project`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'projetos_tuberculose'
--
/*!50003 DROP PROCEDURE IF EXISTS `create_dag_alerts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`viniciuscl`@`%` PROCEDURE `create_dag_alerts`(redcap_pid INT, redbox_project_id INT)
BEGIN
	DECLARE redcap_instance VARCHAR(45);
    SET redcap_instance = (SELECT database_name FROM projetos_tuberculose.redcap_projects WHERE id = redbox_project_id);
    
    IF redcap_instance = "redcap" THEN
		INSERT INTO projetos_tuberculose.redcap_alerts_dag (dag_id,project_id,name,recipients)
			SELECT group_id, redbox_project_id, group_name, 'gd@redbox.technology' FROM redcap.redcap_data_access_groups WHERE project_id = redcap_pid;
	ELSEIF redcap_instance = "redcap_sbgm" THEN
		INSERT INTO projetos_tuberculose.redcap_alerts_dag (dag_id,project_id,name,recipients)
			SELECT group_id, redbox_project_id, group_name, 'gd@redbox.technology' FROM redcap_sbgm.redcap_data_access_groups WHERE project_id = redcap_pid;
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `redcap_sbgm_update_dag_alerts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`viniciuscl`@`%` PROCEDURE `redcap_sbgm_update_dag_alerts`(v_project_id INT, v_redcap_pid INT)
BEGIN
	UPDATE projetos_tuberculose.redcap_alerts_dag D SET D.recipients =
		CASE
			WHEN (SELECT GROUP_CONCAT(I.user_email) AS users_emails
				FROM redcap_sbgm.redcap_user_rights U
				INNER JOIN redcap_sbgm.redcap_data_access_groups G ON G.group_id = U.group_id
				INNER JOIN redcap_sbgm.redcap_user_information I ON U.username = I.username
					WHERE U.project_id = v_redcap_pid AND U.group_id = D.dag_id
						AND I.user_suspended_time IS NULL
                    GROUP BY U.group_id) IS NULL
			THEN "gd@redbox.technology"
			WHEN (SELECT GROUP_CONCAT(I.user_email) AS users_emails
				FROM redcap_sbgm.redcap_user_rights U
				INNER JOIN redcap_sbgm.redcap_data_access_groups G ON G.group_id = U.group_id
				INNER JOIN redcap_sbgm.redcap_user_information I ON U.username = I.username
					WHERE U.project_id = v_redcap_pid AND U.group_id = D.dag_id
						AND I.user_suspended_time IS NULL
                    GROUP BY U.group_id) IS NOT NULL
			THEN (SELECT GROUP_CONCAT(I.user_email) AS users_emails
				FROM redcap_sbgm.redcap_user_rights U
				INNER JOIN redcap_sbgm.redcap_data_access_groups G ON G.group_id = U.group_id
				INNER JOIN redcap_sbgm.redcap_user_information I ON U.username = I.username
					WHERE U.project_id = v_redcap_pid AND U.group_id = D.dag_id
						AND I.user_suspended_time IS NULL
                    GROUP BY U.group_id)
		END
		WHERE D.project_id = v_project_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `redcap_update_dag_alerts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`viniciuscl`@`%` PROCEDURE `redcap_update_dag_alerts`(v_project_id INT, v_redcap_pid INT)
BEGIN
	UPDATE projetos_tuberculose.redcap_alerts_dag D SET D.recipients =
		CASE
			WHEN (SELECT GROUP_CONCAT(I.user_email) AS users_emails
				FROM redcap.redcap_user_rights U
				INNER JOIN redcap.redcap_data_access_groups G ON G.group_id = U.group_id
				INNER JOIN redcap.redcap_user_information I ON U.username = I.username
					WHERE U.project_id = v_redcap_pid AND U.group_id = D.dag_id
						AND I.user_suspended_time IS NULL
                    GROUP BY U.group_id) IS NULL
			THEN "gd@redbox.technology"
			WHEN (SELECT GROUP_CONCAT(I.user_email) AS users_emails
				FROM redcap.redcap_user_rights U
				INNER JOIN redcap.redcap_data_access_groups G ON G.group_id = U.group_id
				INNER JOIN redcap.redcap_user_information I ON U.username = I.username
					WHERE U.project_id = v_redcap_pid AND U.group_id = D.dag_id
						AND I.user_suspended_time IS NULL
                    GROUP BY U.group_id) IS NOT NULL
			THEN (SELECT GROUP_CONCAT(I.user_email) AS users_emails
				FROM redcap.redcap_user_rights U
				INNER JOIN redcap.redcap_data_access_groups G ON G.group_id = U.group_id
				INNER JOIN redcap.redcap_user_information I ON U.username = I.username
					WHERE U.project_id = v_redcap_pid AND U.group_id = D.dag_id
						AND I.user_suspended_time IS NULL
                    GROUP BY U.group_id)
		END
		WHERE D.project_id = v_project_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `vw_redcap_alerts`
--

/*!50001 DROP VIEW IF EXISTS `vw_redcap_alerts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`viniciuscl`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_redcap_alerts` AS select `a`.`id` AS `alert_id`,`a`.`form_id` AS `form_id`,`a`.`category_id` AS `category_id`,`a`.`event_id` AS `event_id`,`a`.`event_name` AS `event_name`,`a`.`days` AS `days`,`a`.`days_offset` AS `days_offset`,`a`.`days_frequency` AS `days_frequency`,`a`.`ref_field` AS `ref_field`,`a`.`enabled` AS `enabled`,`a`.`short_description` AS `short_description`,`a`.`filter_logic` AS `filter_logic`,`f`.`form_name` AS `form_name`,`p`.`id` AS `project_id`,`p`.`project` AS `project_name` from ((`redcap_alerts` `a` join `redcap_forms` `f` on((`a`.`form_id` = `f`.`id`))) join `redcap_projects` `p` on((`f`.`project_id` = `p`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_redcap_alerts_log`
--

/*!50001 DROP VIEW IF EXISTS `vw_redcap_alerts_log`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`viniciuscl`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_redcap_alerts_log` AS select `a`.`alert_id` AS `alert_id`,`a`.`category_id` AS `category_id`,`a`.`form_id` AS `form_id`,`a`.`form_name` AS `form_name`,`a`.`project_name` AS `project_name`,`a`.`short_description` AS `short_description`,`l`.`record` AS `record`,`l`.`deadline` AS `deadline`,`l`.`deadline_expired` AS `deadline_expired`,`l`.`alert_timestamp` AS `alert_timestamp`,`d`.`dag_id` AS `dag_id`,`d`.`name` AS `center_name`,`d`.`recipients` AS `recipients`,`l`.`sent` AS `sent`,`l`.`sent_timestamp` AS `sent_timestamp`,`l`.`id` AS `log_id` from ((`redcap_alerts_log` `l` join `vw_redcap_alerts` `a` on((`l`.`alert_id` = `a`.`alert_id`))) join `redcap_alerts_dag` `d` on((`d`.`id` = `l`.`alert_dag_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_redcap_user_support_stats`
--

/*!50001 DROP VIEW IF EXISTS `vw_redcap_user_support_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`viniciuscl`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_redcap_user_support_stats` AS select `redcap_user_support`.`project_id` AS `project_id`,count(0) AS `total`,(select count(0) from `redcap_user_support` where (`redcap_user_support`.`action` = 'unlock') group by `redcap_user_support`.`project_id`) AS `total_unlock`,(select count(0) from `redcap_user_support` where (`redcap_user_support`.`action` = 'delete') group by `redcap_user_support`.`project_id`) AS `total_delete` from `redcap_user_support` group by `redcap_user_support`.`project_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_redcap_validation_issues`
--

/*!50001 DROP VIEW IF EXISTS `vw_redcap_validation_issues`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`viniciuscl`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_redcap_validation_issues` AS select `r`.`id` AS `rule_id`,`p`.`id` AS `project_id`,`p`.`database_name` AS `redcap_instance`,trim(trailing 'api/' from `p`.`api_url`) AS `redcap_url`,`p`.`redcap_pid` AS `redcap_pid`,`p`.`project` AS `project`,`r`.`form_id` AS `form_id`,`f`.`form_name` AS `form_name`,`f`.`description` AS `form_name_desc`,`i`.`form_instance_number` AS `form_instance_number`,`i`.`event_id` AS `event_id`,`i`.`unique_event_name` AS `unique_event_name`,`r`.`field_name` AS `field_name`,`t`.`comparator` AS `comparator`,`t`.`description` AS `comparator_type`,`t`.`is_regex` AS `is_regex`,`r`.`value_to_compare` AS `value_to_compare`,`r`.`validation_type_id` AS `validation_type_id`,`r`.`compare_other_field` AS `compare_other_field`,`r`.`compare_function` AS `compare_function`,`r`.`get_value_from_function` AS `get_value_from_function`,`i`.`id` AS `issue_id`,`i`.`redcap_record_id` AS `redcap_record_id`,`i`.`issue_date` AS `issue_date`,`i`.`is_solved` AS `is_solved`,`i`.`solved_by` AS `solved_by`,`i`.`issue_date_solved` AS `issue_date_solved` from ((((`redcap_validation_rules` `r` join `redcap_validation_issues` `i` on((`r`.`id` = `i`.`validation_rule_id`))) join `redcap_validation_types` `t` on((`r`.`validation_type_id` = `t`.`id`))) join `redcap_forms` `f` on((`f`.`id` = `r`.`form_id`))) join `redcap_projects` `p` on((`p`.`id` = `f`.`project_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_redcap_validation_rules`
--

/*!50001 DROP VIEW IF EXISTS `vw_redcap_validation_rules`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`viniciuscl`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_redcap_validation_rules` AS select `r`.`id` AS `rule_id`,`p`.`id` AS `project_id`,`p`.`redcap_pid` AS `redcap_pid`,`p`.`project` AS `project_name`,`p`.`database_name` AS `redcap_instance`,`r`.`form_id` AS `form_id`,`f`.`form_name` AS `form_name`,`r`.`field_name` AS `field_name`,`r`.`validation_type_id` AS `validation_type_id`,`vt`.`description` AS `validation_type_desc`,`vt`.`comparator` AS `comparator`,`vt`.`is_regex` AS `is_regex`,`r`.`value_to_compare` AS `value_to_compare`,`r`.`compare_other_field` AS `compare_other_field`,`r`.`compare_function` AS `compare_function`,`r`.`get_value_from_function` AS `get_value_from_function`,`r`.`enabled` AS `enabled` from (((`redcap_validation_rules` `r` join `redcap_forms` `f` on((`r`.`form_id` = `f`.`id`))) join `redcap_validation_types` `vt` on((`r`.`validation_type_id` = `vt`.`id`))) join `redcap_projects` `p` on((`p`.`id` = `f`.`project_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_redcap_visits`
--

/*!50001 DROP VIEW IF EXISTS `vw_redcap_visits`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`viniciuscl`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_redcap_visits` AS select `w`.`id` AS `id`,`w`.`visit_config_id` AS `visit_config_id`,`w`.`window_name` AS `window_name`,`w`.`days` AS `days`,`w`.`days_before` AS `days_before`,`w`.`days_after` AS `days_after`,`w`.`reference_done_field` AS `reference_done_field`,`w`.`reference_done_field_event` AS `reference_done_field_event`,`w`.`reference_done_field_value` AS `reference_done_field_value`,`w`.`order` AS `order`,`c`.`reference_date_field` AS `reference_date_field`,`c`.`reference_date_field_event` AS `reference_date_field_event`,`c`.`custom_record_id_field` AS `custom_record_id_field`,`c`.`custom_record_id_field_event` AS `custom_record_id_field_event`,`c`.`custom_center_field` AS `custom_center_field`,`c`.`custom_center_field_event` AS `custom_center_field_event`,`c`.`project_id` AS `project_id`,`p`.`redcap_pid` AS `redcap_pid`,`p`.`project` AS `project` from ((`redcap_visits` `w` join `redcap_visits_config` `c` on((`c`.`id` = `w`.`visit_config_id`))) join `redcap_projects` `p` on((`c`.`project_id` = `p`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-06-29  8:06:12
