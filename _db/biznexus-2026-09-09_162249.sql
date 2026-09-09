/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for osx10.19 (x86_64)
--
-- Host: localhost    Database: biznexus
-- ------------------------------------------------------
-- Server version	12.1.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `account_balances`
--

DROP TABLE IF EXISTS `account_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `fiscal_period_id` bigint(20) unsigned DEFAULT NULL,
  `balance_date` date NOT NULL,
  `opening_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `period_debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `period_credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `closing_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `balance_type` enum('DEBIT','CREDIT') NOT NULL DEFAULT 'DEBIT',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_balances_account_id_foreign` (`account_id`),
  KEY `account_balances_fiscal_period_id_foreign` (`fiscal_period_id`),
  KEY `account_balances_created_by_foreign` (`created_by`),
  KEY `account_balances_updated_by_foreign` (`updated_by`),
  KEY `account_balances_company_id_account_id_balance_date_index` (`company_id`,`account_id`,`balance_date`),
  CONSTRAINT `account_balances_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `account_balances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `account_balances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `account_balances_fiscal_period_id_foreign` FOREIGN KEY (`fiscal_period_id`) REFERENCES `fiscal_periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `account_balances_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_balances`
--

LOCK TABLES `account_balances` WRITE;
/*!40000 ALTER TABLE `account_balances` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `account_balances` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `account_code` varchar(50) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') NOT NULL,
  `account_category` varchar(100) DEFAULT NULL,
  `normal_balance` enum('DEBIT','CREDIT') NOT NULL DEFAULT 'DEBIT',
  `level` int(11) NOT NULL DEFAULT 1,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `is_postable` tinyint(1) NOT NULL DEFAULT 1,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_company_id_account_code_unique` (`company_id`,`account_code`),
  KEY `accounts_parent_id_foreign` (`parent_id`),
  KEY `accounts_currency_id_foreign` (`currency_id`),
  KEY `accounts_created_by_foreign` (`created_by`),
  KEY `accounts_updated_by_foreign` (`updated_by`),
  KEY `accounts_account_type_index` (`account_type`),
  KEY `accounts_status_index` (`status`),
  KEY `accounts_company_id_parent_id_index` (`company_id`,`parent_id`),
  CONSTRAINT `accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounts_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `accounts` VALUES
(1,1,NULL,'1000','Assets','ASSET',NULL,'DEBIT',1,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(2,1,1,'1100','Current Assets','ASSET',NULL,'DEBIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(3,1,2,'1110','Cash','ASSET',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(4,1,2,'1120','Bank','ASSET',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(5,1,2,'1130','Accounts Receivable','ASSET',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(6,1,2,'1140','Inventory','ASSET',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(7,1,1,'1200','Fixed Assets','ASSET',NULL,'DEBIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(8,1,7,'1210','Property & Equipment','ASSET',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(9,1,7,'1220','Accumulated Depreciation','ASSET',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(10,1,NULL,'2000','Liabilities','LIABILITY',NULL,'CREDIT',1,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(11,1,10,'2100','Current Liabilities','LIABILITY',NULL,'CREDIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(12,1,11,'2110','Accounts Payable','LIABILITY',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(13,1,11,'2120','Tax Payable','LIABILITY',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(14,1,11,'2130','Salary Payable','LIABILITY',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(15,1,10,'2200','Long-term Liabilities','LIABILITY',NULL,'CREDIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(16,1,15,'2210','Loans Payable','LIABILITY',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(17,1,NULL,'3000','Equity','EQUITY',NULL,'CREDIT',1,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(18,1,17,'3100','Owners Equity','EQUITY',NULL,'CREDIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(19,1,18,'3110','Share Capital','EQUITY',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(20,1,18,'3120','Retained Earnings','EQUITY',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(21,1,NULL,'4000','Revenue','REVENUE',NULL,'CREDIT',1,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(22,1,21,'4100','Sales Revenue','REVENUE',NULL,'CREDIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(23,1,22,'4110','Sales','REVENUE',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(24,1,21,'4200','Other Income','REVENUE',NULL,'CREDIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(25,1,24,'4210','Interest Income','REVENUE',NULL,'CREDIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(26,1,NULL,'5000','Expenses','EXPENSE',NULL,'DEBIT',1,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(27,1,26,'5100','Operating Expenses','EXPENSE',NULL,'DEBIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(28,1,27,'5110','Salary Expense','EXPENSE',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(29,1,27,'5120','Rent Expense','EXPENSE',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(30,1,27,'5130','Utilities Expense','EXPENSE',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(31,1,27,'5140','Office Supplies','EXPENSE',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(32,1,27,'5150','Travel Expense','EXPENSE',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(33,1,26,'5200','Cost of Sales','EXPENSE',NULL,'DEBIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(34,1,33,'5210','Cost of Goods Sold','EXPENSE',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(35,1,26,'5300','Financial Expenses','EXPENSE',NULL,'DEBIT',2,1,0,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(36,1,35,'5310','Interest Expense','EXPENSE',NULL,'DEBIT',3,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(37,1,NULL,'9999','Test Account Company 1','ASSET',NULL,'DEBIT',1,0,1,NULL,'active',NULL,NULL,NULL,'2026-09-08 01:01:06','2026-09-08 01:01:06'),
(38,2,NULL,'100001','Test Account','ASSET',NULL,'DEBIT',1,0,1,NULL,'active',NULL,NULL,1,'2026-09-08 01:01:06','2026-09-08 06:13:42');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `attachable_type` varchar(100) NOT NULL,
  `attachable_id` bigint(20) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attachments_company_id_foreign` (`company_id`),
  KEY `attachments_attachable_type_attachable_id_index` (`attachable_type`,`attachable_id`),
  KEY `attachments_uploaded_by_index` (`uploaded_by`),
  CONSTRAINT `attachments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_company_id_module_index` (`company_id`,`module`),
  KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `audit_logs` VALUES
(1,NULL,1,'Finance','Account',1,'UPDATE','{\"id\":1,\"company_id\":1,\"parent_id\":null,\"account_code\":\"1000\",\"account_name\":\"Assets\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":1,\"is_group\":true,\"is_postable\":false,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}','{\"id\":1,\"company_id\":1,\"parent_id\":null,\"account_code\":\"1000\",\"account_name\":\"Assets\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":1,\"is_group\":true,\"is_postable\":false,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-07 10:35:56'),
(2,1,1,'Finance','Journal',1,'CREATE',NULL,'{\"company_id\":\"1\",\"journal_number\":\"JV-2026-000001\",\"journal_date\":\"2026-09-07T00:00:00.000000Z\",\"fiscal_period_id\":null,\"reference_type\":null,\"reference_id\":null,\"description\":null,\"currency_id\":null,\"exchange_rate\":\"1.00000000\",\"status\":\"DRAFT\",\"created_by\":1,\"updated_at\":\"2026-09-07T18:54:19.000000Z\",\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"id\":1,\"total_debit\":\"700.0000\",\"total_credit\":\"700.0000\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-07 12:54:19'),
(3,1,1,'Finance','Journal',2,'CREATE',NULL,'{\"company_id\":\"1\",\"journal_number\":\"JV-2026-000002\",\"journal_date\":\"2026-09-07T00:00:00.000000Z\",\"fiscal_period_id\":null,\"reference_type\":null,\"reference_id\":null,\"description\":\"Another test\",\"currency_id\":null,\"exchange_rate\":\"1.00000000\",\"status\":\"DRAFT\",\"created_by\":1,\"updated_at\":\"2026-09-07T19:01:05.000000Z\",\"created_at\":\"2026-09-07T19:01:05.000000Z\",\"id\":2,\"total_debit\":\"9000.0000\",\"total_credit\":\"9000.0000\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-07 13:01:05'),
(4,1,1,'Finance','Journal',2,'APPROVE',NULL,'{\"id\":2,\"company_id\":1,\"branch_id\":null,\"department_id\":null,\"journal_number\":\"JV-2026-000002\",\"journal_date\":\"2026-09-07T00:00:00.000000Z\",\"posting_date\":null,\"fiscal_period_id\":null,\"reference_type\":null,\"reference_id\":null,\"description\":\"Another test\",\"status\":\"APPROVED\",\"currency_id\":null,\"exchange_rate\":\"1.00000000\",\"total_debit\":\"9000.0000\",\"total_credit\":\"9000.0000\",\"posted_at\":null,\"posted_by\":null,\"reversal_of_journal_id\":null,\"reversal_reason\":null,\"reversed_at\":null,\"reversed_by\":null,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-09-07T19:01:05.000000Z\",\"updated_at\":\"2026-09-08T12:00:16.000000Z\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-08 06:00:16'),
(5,1,1,'Finance','Journal',2,'POST',NULL,'{\"id\":2,\"company_id\":1,\"branch_id\":null,\"department_id\":null,\"journal_number\":\"JV-2026-000002\",\"journal_date\":\"2026-09-07T00:00:00.000000Z\",\"posting_date\":\"2026-09-08T00:00:00.000000Z\",\"fiscal_period_id\":9,\"reference_type\":null,\"reference_id\":null,\"description\":\"Another test\",\"status\":\"POSTED\",\"currency_id\":null,\"exchange_rate\":\"1.00000000\",\"total_debit\":\"9000.0000\",\"total_credit\":\"9000.0000\",\"posted_at\":\"2026-09-08T12:00:26.000000Z\",\"posted_by\":1,\"reversal_of_journal_id\":null,\"reversal_reason\":null,\"reversed_at\":null,\"reversed_by\":null,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-09-07T19:01:05.000000Z\",\"updated_at\":\"2026-09-08T12:00:26.000000Z\",\"lines\":[{\"id\":3,\"journal_id\":2,\"account_id\":5,\"description\":\"Hello1\",\"debit\":\"9000.0000\",\"credit\":\"0.0000\",\"currency_debit\":\"9000.0000\",\"currency_credit\":\"0.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-07T19:01:05.000000Z\",\"updated_at\":\"2026-09-07T19:01:05.000000Z\",\"account\":{\"id\":5,\"company_id\":1,\"parent_id\":2,\"account_code\":\"1130\",\"account_name\":\"Accounts Receivable\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}},{\"id\":4,\"journal_id\":2,\"account_id\":13,\"description\":\"Hello2\",\"debit\":\"0.0000\",\"credit\":\"9000.0000\",\"currency_debit\":\"0.0000\",\"currency_credit\":\"9000.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-07T19:01:05.000000Z\",\"updated_at\":\"2026-09-07T19:01:05.000000Z\",\"account\":{\"id\":13,\"company_id\":1,\"parent_id\":11,\"account_code\":\"2120\",\"account_name\":\"Tax Payable\",\"account_type\":\"LIABILITY\",\"account_category\":null,\"normal_balance\":\"CREDIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}}]}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-08 06:00:26'),
(6,1,1,'Finance','Journal',1,'SUBMIT',NULL,'{\"id\":1,\"company_id\":1,\"branch_id\":null,\"department_id\":null,\"journal_number\":\"JV-2026-000001\",\"journal_date\":\"2026-09-07T00:00:00.000000Z\",\"posting_date\":null,\"fiscal_period_id\":null,\"reference_type\":null,\"reference_id\":null,\"description\":null,\"status\":\"SUBMITTED\",\"currency_id\":null,\"exchange_rate\":\"1.00000000\",\"total_debit\":\"700.0000\",\"total_credit\":\"700.0000\",\"posted_at\":null,\"posted_by\":null,\"reversal_of_journal_id\":null,\"reversal_reason\":null,\"reversed_at\":null,\"reversed_by\":null,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"updated_at\":\"2026-09-08T12:01:17.000000Z\",\"lines\":[{\"id\":1,\"journal_id\":1,\"account_id\":3,\"description\":\"test1\",\"debit\":\"700.0000\",\"credit\":\"0.0000\",\"currency_debit\":\"700.0000\",\"currency_credit\":\"0.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"updated_at\":\"2026-09-07T18:54:19.000000Z\",\"account\":{\"id\":3,\"company_id\":1,\"parent_id\":2,\"account_code\":\"1110\",\"account_name\":\"Cash\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}},{\"id\":2,\"journal_id\":1,\"account_id\":4,\"description\":\"test2\",\"debit\":\"0.0000\",\"credit\":\"700.0000\",\"currency_debit\":\"0.0000\",\"currency_credit\":\"700.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"updated_at\":\"2026-09-07T18:54:19.000000Z\",\"account\":{\"id\":4,\"company_id\":1,\"parent_id\":2,\"account_code\":\"1120\",\"account_name\":\"Bank\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}}]}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-08 06:01:17'),
(7,1,1,'Finance','Journal',1,'APPROVE',NULL,'{\"id\":1,\"company_id\":1,\"branch_id\":null,\"department_id\":null,\"journal_number\":\"JV-2026-000001\",\"journal_date\":\"2026-09-07T00:00:00.000000Z\",\"posting_date\":null,\"fiscal_period_id\":null,\"reference_type\":null,\"reference_id\":null,\"description\":null,\"status\":\"APPROVED\",\"currency_id\":null,\"exchange_rate\":\"1.00000000\",\"total_debit\":\"700.0000\",\"total_credit\":\"700.0000\",\"posted_at\":null,\"posted_by\":null,\"reversal_of_journal_id\":null,\"reversal_reason\":null,\"reversed_at\":null,\"reversed_by\":null,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"updated_at\":\"2026-09-08T12:01:20.000000Z\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-08 06:01:20'),
(8,1,1,'Finance','Journal',1,'POST',NULL,'{\"id\":1,\"company_id\":1,\"branch_id\":null,\"department_id\":null,\"journal_number\":\"JV-2026-000001\",\"journal_date\":\"2026-09-07T00:00:00.000000Z\",\"posting_date\":\"2026-09-08T00:00:00.000000Z\",\"fiscal_period_id\":9,\"reference_type\":null,\"reference_id\":null,\"description\":null,\"status\":\"POSTED\",\"currency_id\":null,\"exchange_rate\":\"1.00000000\",\"total_debit\":\"700.0000\",\"total_credit\":\"700.0000\",\"posted_at\":\"2026-09-08T12:01:22.000000Z\",\"posted_by\":1,\"reversal_of_journal_id\":null,\"reversal_reason\":null,\"reversed_at\":null,\"reversed_by\":null,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"updated_at\":\"2026-09-08T12:01:22.000000Z\",\"lines\":[{\"id\":1,\"journal_id\":1,\"account_id\":3,\"description\":\"test1\",\"debit\":\"700.0000\",\"credit\":\"0.0000\",\"currency_debit\":\"700.0000\",\"currency_credit\":\"0.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"updated_at\":\"2026-09-07T18:54:19.000000Z\",\"account\":{\"id\":3,\"company_id\":1,\"parent_id\":2,\"account_code\":\"1110\",\"account_name\":\"Cash\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}},{\"id\":2,\"journal_id\":1,\"account_id\":4,\"description\":\"test2\",\"debit\":\"0.0000\",\"credit\":\"700.0000\",\"currency_debit\":\"0.0000\",\"currency_credit\":\"700.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-07T18:54:19.000000Z\",\"updated_at\":\"2026-09-07T18:54:19.000000Z\",\"account\":{\"id\":4,\"company_id\":1,\"parent_id\":2,\"account_code\":\"1120\",\"account_name\":\"Bank\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}}]}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-08 06:01:22'),
(9,1,1,'Finance','Journal',3,'CREATE',NULL,'{\"company_id\":1,\"journal_number\":\"JV-2026-000003\",\"journal_date\":\"2026-09-09T00:00:00.000000Z\",\"fiscal_period_id\":null,\"reference_type\":null,\"reference_id\":null,\"description\":\"Daily Expances\",\"currency_id\":1,\"exchange_rate\":\"1.00000000\",\"status\":\"DRAFT\",\"created_by\":1,\"updated_at\":\"2026-09-09T03:03:22.000000Z\",\"created_at\":\"2026-09-09T03:03:21.000000Z\",\"id\":3,\"total_debit\":\"390.0000\",\"total_credit\":\"390.0000\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-08 21:03:22'),
(10,1,1,'Finance','Journal',3,'SUBMIT',NULL,'{\"id\":3,\"company_id\":1,\"branch_id\":null,\"department_id\":null,\"journal_number\":\"JV-2026-000003\",\"journal_date\":\"2026-09-09T00:00:00.000000Z\",\"posting_date\":null,\"fiscal_period_id\":null,\"reference_type\":null,\"reference_id\":null,\"description\":\"Daily Expances\",\"status\":\"SUBMITTED\",\"currency_id\":1,\"exchange_rate\":\"1.00000000\",\"total_debit\":\"390.0000\",\"total_credit\":\"390.0000\",\"posted_at\":null,\"posted_by\":null,\"reversal_of_journal_id\":null,\"reversal_reason\":null,\"reversed_at\":null,\"reversed_by\":null,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-09-09T03:03:21.000000Z\",\"updated_at\":\"2026-09-09T03:03:31.000000Z\",\"lines\":[{\"id\":5,\"journal_id\":3,\"account_id\":3,\"description\":\"Groceries\",\"debit\":\"390.0000\",\"credit\":\"0.0000\",\"currency_debit\":\"390.0000\",\"currency_credit\":\"0.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-09T03:03:21.000000Z\",\"updated_at\":\"2026-09-09T03:03:21.000000Z\",\"account\":{\"id\":3,\"company_id\":1,\"parent_id\":2,\"account_code\":\"1110\",\"account_name\":\"Cash\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}},{\"id\":6,\"journal_id\":3,\"account_id\":4,\"description\":\"Transfer\",\"debit\":\"0.0000\",\"credit\":\"390.0000\",\"currency_debit\":\"0.0000\",\"currency_credit\":\"390.0000\",\"cost_center_id\":null,\"department_id\":null,\"branch_id\":null,\"business_unit_id\":null,\"project_id\":null,\"tax_id\":null,\"reference\":null,\"created_at\":\"2026-09-09T03:03:21.000000Z\",\"updated_at\":\"2026-09-09T03:03:21.000000Z\",\"account\":{\"id\":4,\"company_id\":1,\"parent_id\":2,\"account_code\":\"1120\",\"account_name\":\"Bank\",\"account_type\":\"ASSET\",\"account_category\":null,\"normal_balance\":\"DEBIT\",\"level\":3,\"is_group\":false,\"is_postable\":true,\"currency_id\":null,\"status\":\"active\",\"description\":null,\"created_by\":null,\"updated_by\":null,\"created_at\":\"2026-09-07T15:44:23.000000Z\",\"updated_at\":\"2026-09-07T15:44:23.000000Z\"}}]}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','2026-09-08 21:03:31');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `account_type` enum('CASH','PETTY_CASH','BANK') NOT NULL DEFAULT 'BANK',
  `currency_id` bigint(20) unsigned NOT NULL,
  `gl_account_id` bigint(20) unsigned DEFAULT NULL,
  `opening_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `current_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_accounts_company_id_account_number_unique` (`company_id`,`account_number`),
  KEY `bank_accounts_currency_id_foreign` (`currency_id`),
  KEY `bank_accounts_gl_account_id_foreign` (`gl_account_id`),
  KEY `bank_accounts_created_by_foreign` (`created_by`),
  KEY `bank_accounts_updated_by_foreign` (`updated_by`),
  KEY `bank_accounts_status_index` (`status`),
  CONSTRAINT `bank_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_accounts_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_accounts_gl_account_id_foreign` FOREIGN KEY (`gl_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `bank_accounts` VALUES
(1,1,'Dutch-Bangla Bank','Motijheel','BizNexus Main','001122334455','BANK',1,1,500000.0000,500000.0000,'active',NULL,NULL,'2026-09-07 11:50:14','2026-09-07 11:50:14'),
(2,1,'bKash','Mobile','BizNexus bKash','01711223344','BANK',1,2,50000.0000,50000.0000,'active',NULL,NULL,'2026-09-07 11:50:14','2026-09-07 11:50:14'),
(3,1,'City Bank','Gulshan','BizNexus Operations','998877665544','BANK',1,3,250000.0000,250000.0000,'active',NULL,NULL,'2026-09-07 11:50:14','2026-09-07 11:50:14');
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `bank_reconciliations`
--

DROP TABLE IF EXISTS `bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bank_account_id` bigint(20) unsigned NOT NULL,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `book_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `difference` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `reconciled_by` bigint(20) unsigned DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_reconciliations_company_id_foreign` (`company_id`),
  KEY `bank_reconciliations_reconciled_by_foreign` (`reconciled_by`),
  KEY `bank_reconciliations_bank_account_id_statement_date_index` (`bank_account_id`,`statement_date`),
  KEY `bank_reconciliations_status_index` (`status`),
  CONSTRAINT `bank_reconciliations_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_reconciliations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_reconciliations_reconciled_by_foreign` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliations`
--

LOCK TABLES `bank_reconciliations` WRITE;
/*!40000 ALTER TABLE `bank_reconciliations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `bank_reconciliations` VALUES
(1,1,1,'2026-08-31',500000.0000,500000.0000,0.0000,'RECONCILED',1,'2026-09-01 04:00:00','2026-09-07 12:04:04','2026-09-07 12:04:04'),
(2,1,1,'2026-09-30',620000.0000,618500.0000,1500.0000,'PENDING',NULL,NULL,'2026-09-07 12:04:04','2026-09-07 12:04:04'),
(3,1,2,'2026-08-31',120000.0000,120000.0000,0.0000,'RECONCILED',1,'2026-09-01 05:00:00','2026-09-07 12:04:04','2026-09-07 12:04:04'),
(4,1,3,'2026-09-30',850000.0000,847200.0000,2800.0000,'PENDING',NULL,NULL,'2026-09-07 12:04:04','2026-09-07 12:04:04');
/*!40000 ALTER TABLE `bank_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `bank_transactions`
--

DROP TABLE IF EXISTS `bank_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bank_account_id` bigint(20) unsigned NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('DEPOSIT','WITHDRAWAL','TRANSFER','CHARGE','INTEREST') NOT NULL,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `reference` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'COMPLETED',
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_transactions_bank_account_id_transaction_number_unique` (`bank_account_id`,`transaction_number`),
  KEY `bank_transactions_created_by_foreign` (`created_by`),
  KEY `bank_transactions_status_index` (`status`),
  KEY `bank_transactions_transaction_date_index` (`transaction_date`),
  CONSTRAINT `bank_transactions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_transactions`
--

LOCK TABLES `bank_transactions` WRITE;
/*!40000 ALTER TABLE `bank_transactions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `bank_transactions` VALUES
(1,1,'BP-20260907-0001','2026-09-01','WITHDRAWAL',50000.0000,'BP-001','Office rent payment','COMPLETED',NULL,1,'2026-09-07 11:54:29','2026-09-07 11:54:29'),
(2,1,'BP-20260907-0002','2026-09-03','TRANSFER',120000.0000,'BP-002','Transfer to savings','COMPLETED',NULL,1,'2026-09-07 11:54:29','2026-09-07 11:54:29'),
(3,2,'BP-20260907-0003','2026-09-05','WITHDRAWAL',25000.0000,'BP-003','Utility bills','PENDING',NULL,1,'2026-09-07 11:54:29','2026-09-07 11:54:29'),
(4,3,'BP-20260907-0004','2026-09-06','CHARGE',1500.0000,'BP-004','Monthly maintenance fee','COMPLETED',NULL,1,'2026-09-07 11:54:29','2026-09-07 11:54:29'),
(5,1,'BR-20260907-0005','2026-09-02','DEPOSIT',250000.0000,'BR-001','Customer payment received','COMPLETED',NULL,1,'2026-09-07 11:57:41','2026-09-07 11:57:41'),
(6,1,'BR-20260907-0006','2026-09-04','DEPOSIT',180000.0000,'BR-002','Invoice payment from ABC Corp','COMPLETED',NULL,1,'2026-09-07 11:57:41','2026-09-07 11:57:41'),
(7,2,'BR-20260907-0007','2026-09-06','DEPOSIT',75000.0000,'BR-003','Mobile banking receipt','PENDING',NULL,1,'2026-09-07 11:57:41','2026-09-07 11:57:41'),
(8,3,'BR-20260907-0008','2026-09-07','DEPOSIT',320000.0000,'BR-004','Bulk deposit from sales','COMPLETED',NULL,1,'2026-09-07 11:57:41','2026-09-07 11:57:41');
/*!40000 ALTER TABLE `bank_transactions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_company_id_code_unique` (`company_id`,`code`),
  KEY `branches_status_index` (`status`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `branches` VALUES
(1,1,'AMTE','Amo Tea Estate',NULL,NULL,'amo@duncanbd.com','active','2026-09-07 23:50:44','2026-09-09 01:24:02'),
(2,1,'NLTE','Nalua Tea Estate',NULL,'+88-09666774422','nal@duncanbd.com','active','2026-09-09 01:24:21','2026-09-09 01:24:21');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `budget_lines`
--

DROP TABLE IF EXISTS `budget_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `cost_center_id` bigint(20) unsigned DEFAULT NULL,
  `period` int(11) NOT NULL,
  `budget_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budget_lines_account_id_foreign` (`account_id`),
  KEY `budget_lines_cost_center_id_foreign` (`cost_center_id`),
  KEY `budget_lines_budget_id_account_id_period_index` (`budget_id`,`account_id`,`period`),
  KEY `budget_lines_company_id_index` (`company_id`),
  CONSTRAINT `budget_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budget_lines_budget_id_foreign` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budget_lines_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budget_lines_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_lines`
--

LOCK TABLES `budget_lines` WRITE;
/*!40000 ALTER TABLE `budget_lines` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `budget_lines` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budgets_fiscal_year_id_foreign` (`fiscal_year_id`),
  KEY `budgets_created_by_foreign` (`created_by`),
  KEY `budgets_updated_by_foreign` (`updated_by`),
  KEY `budgets_status_index` (`status`),
  KEY `budgets_company_id_fiscal_year_id_index` (`company_id`,`fiscal_year_id`),
  CONSTRAINT `budgets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `budgets_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `budgets` VALUES
(2,1,1,'BizNexus Limited','This is a test budget!','DRAFT',1,1,'2026-09-08 11:49:21','2026-09-08 11:49:21');
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `business_units`
--

DROP TABLE IF EXISTS `business_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `division_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_units_company_id_code_unique` (`company_id`,`code`),
  KEY `business_units_division_id_foreign` (`division_id`),
  KEY `business_units_status_index` (`status`),
  CONSTRAINT `business_units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_units_division_id_foreign` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_units`
--

LOCK TABLES `business_units` WRITE;
/*!40000 ALTER TABLE `business_units` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `business_units` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cash_accounts`
--

DROP TABLE IF EXISTS `cash_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `gl_account_id` bigint(20) unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_type` enum('CASH','PETTY_CASH','BANK') NOT NULL DEFAULT 'CASH',
  `currency_code` varchar(3) NOT NULL DEFAULT 'BDT',
  `opening_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `current_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_accounts_code_unique` (`code`),
  KEY `cash_accounts_company_id_foreign` (`company_id`),
  KEY `cash_accounts_gl_account_id_foreign` (`gl_account_id`),
  KEY `cash_accounts_created_by_foreign` (`created_by`),
  KEY `cash_accounts_updated_by_foreign` (`updated_by`),
  CONSTRAINT `cash_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_accounts_gl_account_id_foreign` FOREIGN KEY (`gl_account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_accounts`
--

LOCK TABLES `cash_accounts` WRITE;
/*!40000 ALTER TABLE `cash_accounts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cash_accounts` VALUES
(1,1,1,'CAS-001','Main Cash Drawer','CASH','BDT',50000.0000,50000.0000,'active',NULL,1,1,'2026-09-07 11:59:39','2026-09-07 11:59:39'),
(2,1,1,'CAS-002','Petty Cash - Office','PETTY_CASH','BDT',10000.0000,10000.0000,'active',NULL,1,1,'2026-09-07 11:59:39','2026-09-07 11:59:39'),
(3,1,1,'CAS-003','Petty Cash - Warehouse','PETTY_CASH','BDT',5000.0000,5000.0000,'active',NULL,1,1,'2026-09-07 11:59:39','2026-09-07 11:59:39');
/*!40000 ALTER TABLE `cash_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `base_currency_id` bigint(20) unsigned DEFAULT NULL,
  `timezone` varchar(100) NOT NULL DEFAULT 'UTC',
  `fiscal_year_start` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_code_unique` (`code`),
  KEY `companies_created_by_foreign` (`created_by`),
  KEY `companies_updated_by_foreign` (`updated_by`),
  KEY `companies_status_index` (`status`),
  KEY `companies_code_index` (`code`),
  KEY `companies_base_currency_id_foreign` (`base_currency_id`),
  CONSTRAINT `companies_base_currency_id_foreign` FOREIGN KEY (`base_currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `companies` VALUES
(1,'AMTC','Amo Tea Company Limited','Amo Tea Company Limited','123 Business Street, Dhaka, Bangladesh','+880 1234-567890','amo@duncanbd.com','TAX-123456789','REG-123456',1,'Asia/Dhaka','2026-01-01','active',NULL,NULL,'2026-09-07 09:44:23','2026-09-09 01:23:39'),
(2,'BIZNEXUS','BizNexus Limited','BizNexus Limited','456 Test Street','+880 9999-999999','test2@example.com','TAX-999999999','REG-999999',1,'Asia/Dhaka','2026-01-01','active',NULL,NULL,'2026-09-08 01:01:06','2026-09-08 05:11:29');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `company_user_roles`
--

DROP TABLE IF EXISTS `company_user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_user_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_user_roles_user_id_company_id_role_id_unique` (`user_id`,`company_id`,`role_id`),
  KEY `company_user_roles_company_id_foreign` (`company_id`),
  KEY `company_user_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `company_user_roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_user_roles`
--

LOCK TABLES `company_user_roles` WRITE;
/*!40000 ALTER TABLE `company_user_roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `company_user_roles` VALUES
(8,2,1,2,'active','2026-09-08 02:57:57','2026-09-08 02:57:57'),
(9,2,2,2,'active','2026-09-08 02:57:57','2026-09-08 02:57:57'),
(12,1,1,1,'active','2026-09-09 03:04:08','2026-09-09 03:04:08'),
(13,1,2,1,'active','2026-09-09 03:04:08','2026-09-09 03:04:08');
/*!40000 ALTER TABLE `company_user_roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_centers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cost_centers_company_id_code_unique` (`company_id`,`code`),
  KEY `cost_centers_parent_id_foreign` (`parent_id`),
  KEY `cost_centers_manager_id_foreign` (`manager_id`),
  KEY `cost_centers_status_index` (`status`),
  CONSTRAINT `cost_centers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cost_centers_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cost_centers_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_centers`
--

LOCK TABLES `cost_centers` WRITE;
/*!40000 ALTER TABLE `cost_centers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cost_centers` VALUES
(1,1,NULL,'CC-ADMIN','Administration',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32'),
(2,1,NULL,'CC-FIN','Finance',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32'),
(3,1,NULL,'CC-HR','Human Resources',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32'),
(4,1,NULL,'CC-IT','Information Technology',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32'),
(5,1,NULL,'CC-MKT','Marketing',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32'),
(6,1,NULL,'CC-OPS','Operations',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32'),
(7,1,NULL,'CC-SALES','Sales',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32'),
(8,1,NULL,'CC-RD','Research & Development',NULL,'active','2026-09-07 11:00:32','2026-09-07 11:00:32');
/*!40000 ALTER TABLE `cost_centers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `decimal_places` int(11) NOT NULL DEFAULT 2,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_code_unique` (`code`),
  KEY `currencies_status_index` (`status`),
  KEY `currencies_code_index` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `currencies` VALUES
(1,'BDT','Bangladeshi Taka','৳',2,'active','2026-09-07 09:44:23','2026-09-07 09:44:23'),
(2,'USD','US Dollar','$',2,'active','2026-09-07 09:44:23','2026-09-07 09:44:23'),
(3,'EUR','Euro','€',2,'active','2026-09-07 09:44:23','2026-09-07 09:44:23'),
(4,'GBP','British Pound','£',2,'active','2026-09-07 09:44:23','2026-09-07 09:44:23'),
(5,'INR','Indian Rupee','₹',2,'active','2026-09-07 09:44:23','2026-09-07 09:44:23');
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customer_credit_notes`
--

DROP TABLE IF EXISTS `customer_credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `note_number` varchar(50) NOT NULL,
  `note_date` date NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_credit_notes_company_id_note_number_unique` (`company_id`,`note_number`),
  KEY `customer_credit_notes_customer_id_foreign` (`customer_id`),
  KEY `customer_credit_notes_created_by_foreign` (`created_by`),
  KEY `customer_credit_notes_updated_by_foreign` (`updated_by`),
  KEY `customer_credit_notes_status_index` (`status`),
  CONSTRAINT `customer_credit_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_credit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_credit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_credit_notes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_credit_notes`
--

LOCK TABLES `customer_credit_notes` WRITE;
/*!40000 ALTER TABLE `customer_credit_notes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `customer_credit_notes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customer_debit_notes`
--

DROP TABLE IF EXISTS `customer_debit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_debit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `customer_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `debit_note_number` varchar(255) NOT NULL,
  `debit_note_date` date NOT NULL,
  `subtotal` decimal(20,4) NOT NULL,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(20,4) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','posted','cancelled') NOT NULL DEFAULT 'draft',
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_debit_notes_debit_note_number_unique` (`debit_note_number`),
  KEY `customer_debit_notes_customer_id_foreign` (`customer_id`),
  KEY `customer_debit_notes_customer_invoice_id_foreign` (`customer_invoice_id`),
  KEY `customer_debit_notes_posted_by_foreign` (`posted_by`),
  KEY `customer_debit_notes_created_by_foreign` (`created_by`),
  KEY `customer_debit_notes_updated_by_foreign` (`updated_by`),
  KEY `customer_debit_notes_company_id_customer_id_index` (`company_id`,`customer_id`),
  KEY `customer_debit_notes_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `customer_debit_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_debit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_debit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_debit_notes_customer_invoice_id_foreign` FOREIGN KEY (`customer_invoice_id`) REFERENCES `customer_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_debit_notes_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_debit_notes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_debit_notes`
--

LOCK TABLES `customer_debit_notes` WRITE;
/*!40000 ALTER TABLE `customer_debit_notes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `customer_debit_notes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customer_invoice_lines`
--

DROP TABLE IF EXISTS `customer_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_invoice_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(20,4) NOT NULL DEFAULT 1.0000,
  `unit_price` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subtotal` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_invoice_lines_customer_invoice_id_foreign` (`customer_invoice_id`),
  KEY `customer_invoice_lines_account_id_foreign` (`account_id`),
  CONSTRAINT `customer_invoice_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_invoice_lines_customer_invoice_id_foreign` FOREIGN KEY (`customer_invoice_id`) REFERENCES `customer_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_invoice_lines`
--

LOCK TABLES `customer_invoice_lines` WRITE;
/*!40000 ALTER TABLE `customer_invoice_lines` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `customer_invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customer_invoices`
--

DROP TABLE IF EXISTS `customer_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `exchange_rate` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `subtotal` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `outstanding_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_invoices_company_id_invoice_number_unique` (`company_id`,`invoice_number`),
  KEY `customer_invoices_customer_id_foreign` (`customer_id`),
  KEY `customer_invoices_currency_id_foreign` (`currency_id`),
  KEY `customer_invoices_created_by_foreign` (`created_by`),
  KEY `customer_invoices_updated_by_foreign` (`updated_by`),
  KEY `customer_invoices_status_index` (`status`),
  KEY `customer_invoices_company_id_customer_id_index` (`company_id`,`customer_id`),
  KEY `customer_invoices_branch_id_foreign` (`branch_id`),
  CONSTRAINT `customer_invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_invoices_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_invoices_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_invoices`
--

LOCK TABLES `customer_invoices` WRITE;
/*!40000 ALTER TABLE `customer_invoices` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `customer_invoices` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customer_receipts`
--

DROP TABLE IF EXISTS `customer_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `receipt_date` date NOT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `exchange_rate` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `receipt_method` varchar(50) NOT NULL DEFAULT 'BANK_TRANSFER',
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_receipts_company_id_receipt_number_unique` (`company_id`,`receipt_number`),
  KEY `customer_receipts_customer_id_foreign` (`customer_id`),
  KEY `customer_receipts_currency_id_foreign` (`currency_id`),
  KEY `customer_receipts_bank_account_id_foreign` (`bank_account_id`),
  KEY `customer_receipts_created_by_foreign` (`created_by`),
  KEY `customer_receipts_updated_by_foreign` (`updated_by`),
  KEY `customer_receipts_status_index` (`status`),
  KEY `customer_receipts_branch_id_foreign` (`branch_id`),
  CONSTRAINT `customer_receipts_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_receipts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_receipts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_receipts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_receipts_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_receipts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_receipts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_receipts`
--

LOCK TABLES `customer_receipts` WRITE;
/*!40000 ALTER TABLE `customer_receipts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `customer_receipts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `receivable_account_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_company_id_customer_code_unique` (`company_id`,`customer_code`),
  KEY `customers_currency_id_foreign` (`currency_id`),
  KEY `customers_receivable_account_id_foreign` (`receivable_account_id`),
  KEY `customers_created_by_foreign` (`created_by`),
  KEY `customers_updated_by_foreign` (`updated_by`),
  KEY `customers_status_index` (`status`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_receivable_account_id_foreign` FOREIGN KEY (`receivable_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_company_id_code_unique` (`company_id`,`code`),
  KEY `departments_branch_id_foreign` (`branch_id`),
  KEY `departments_parent_id_foreign` (`parent_id`),
  KEY `departments_manager_id_foreign` (`manager_id`),
  KEY `departments_status_index` (`status`),
  CONSTRAINT `departments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `departments_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `departments` VALUES
(1,2,1,NULL,'IT','Information Technology',NULL,'active','2026-09-08 09:50:54','2026-09-08 09:50:54');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `divisions`
--

DROP TABLE IF EXISTS `divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `divisions_company_id_code_unique` (`company_id`,`code`),
  KEY `divisions_parent_id_foreign` (`parent_id`),
  KEY `divisions_status_index` (`status`),
  CONSTRAINT `divisions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `divisions_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `divisions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `divisions`
--

LOCK TABLES `divisions` WRITE;
/*!40000 ALTER TABLE `divisions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `divisions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `currency_id` bigint(20) unsigned NOT NULL,
  `rate_date` date NOT NULL,
  `exchange_rate` decimal(20,8) NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exchange_rates_currency_id_foreign` (`currency_id`),
  KEY `exchange_rates_created_by_foreign` (`created_by`),
  KEY `exchange_rates_company_id_currency_id_rate_date_index` (`company_id`,`currency_id`,`rate_date`),
  KEY `exchange_rates_rate_date_index` (`rate_date`),
  CONSTRAINT `exchange_rates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_rates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exchange_rates_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rates`
--

LOCK TABLES `exchange_rates` WRITE;
/*!40000 ALTER TABLE `exchange_rates` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exchange_rates` VALUES
(1,1,2,'2026-09-08',122.00000000,'Online','active',1,'2026-09-08 10:51:29','2026-09-08 10:51:29');
/*!40000 ALTER TABLE `exchange_rates` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `fiscal_periods`
--

DROP TABLE IF EXISTS `fiscal_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiscal_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `period_number` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fiscal_periods_closed_by_foreign` (`closed_by`),
  KEY `fiscal_periods_fiscal_year_id_status_index` (`fiscal_year_id`,`status`),
  KEY `fiscal_periods_start_date_end_date_index` (`start_date`,`end_date`),
  CONSTRAINT `fiscal_periods_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiscal_periods_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_periods`
--

LOCK TABLES `fiscal_periods` WRITE;
/*!40000 ALTER TABLE `fiscal_periods` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `fiscal_periods` VALUES
(1,1,'January',1,'2026-01-01','2026-01-31','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(2,1,'February',2,'2026-02-01','2026-02-28','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(3,1,'March',3,'2026-03-01','2026-03-31','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(4,1,'April',4,'2026-04-01','2026-04-30','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(5,1,'May',5,'2026-05-01','2026-05-31','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(6,1,'June',6,'2026-06-01','2026-06-30','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(7,1,'July',7,'2026-07-01','2026-07-31','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(8,1,'August',8,'2026-08-01','2026-08-31','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(9,1,'September',9,'2026-09-01','2026-09-30','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(10,1,'October',10,'2026-10-01','2026-10-31','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(11,1,'November',11,'2026-11-01','2026-11-30','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23'),
(12,1,'December',12,'2026-12-01','2026-12-31','OPEN',NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23');
/*!40000 ALTER TABLE `fiscal_periods` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `fiscal_years`
--

DROP TABLE IF EXISTS `fiscal_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiscal_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fiscal_years_created_by_foreign` (`created_by`),
  KEY `fiscal_years_updated_by_foreign` (`updated_by`),
  KEY `fiscal_years_company_id_status_index` (`company_id`,`status`),
  KEY `fiscal_years_is_current_index` (`is_current`),
  CONSTRAINT `fiscal_years_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiscal_years_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiscal_years_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_years`
--

LOCK TABLES `fiscal_years` WRITE;
/*!40000 ALTER TABLE `fiscal_years` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `fiscal_years` VALUES
(1,1,'2026','2026-01-01','2026-12-31','OPEN',1,NULL,NULL,'2026-09-07 09:44:23','2026-09-07 09:44:23');
/*!40000 ALTER TABLE `fiscal_years` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `journal_lines`
--

DROP TABLE IF EXISTS `journal_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `currency_debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `currency_credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cost_center_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `business_unit_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_lines_department_id_foreign` (`department_id`),
  KEY `journal_lines_branch_id_foreign` (`branch_id`),
  KEY `journal_lines_account_id_index` (`account_id`),
  KEY `journal_lines_cost_center_id_index` (`cost_center_id`),
  KEY `journal_lines_journal_id_account_id_index` (`journal_id`,`account_id`),
  KEY `journal_lines_business_unit_id_foreign` (`business_unit_id`),
  CONSTRAINT `journal_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_lines_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_lines_business_unit_id_foreign` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_lines_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_lines_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_lines_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_lines`
--

LOCK TABLES `journal_lines` WRITE;
/*!40000 ALTER TABLE `journal_lines` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `journal_lines` VALUES
(1,1,3,'test1',700.0000,0.0000,700.0000,0.0000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-07 12:54:19','2026-09-07 12:54:19'),
(2,1,4,'test2',0.0000,700.0000,0.0000,700.0000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-07 12:54:19','2026-09-07 12:54:19'),
(3,2,5,'Hello1',9000.0000,0.0000,9000.0000,0.0000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-07 13:01:05','2026-09-07 13:01:05'),
(4,2,13,'Hello2',0.0000,9000.0000,0.0000,9000.0000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-07 13:01:05','2026-09-07 13:01:05'),
(5,3,3,'Groceries',390.0000,0.0000,390.0000,0.0000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-08 21:03:21','2026-09-08 21:03:21'),
(6,3,4,'Transfer',0.0000,390.0000,0.0000,390.0000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-08 21:03:21','2026-09-08 21:03:21');
/*!40000 ALTER TABLE `journal_lines` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `journals`
--

DROP TABLE IF EXISTS `journals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `journal_number` varchar(50) NOT NULL,
  `journal_date` date NOT NULL,
  `posting_date` date DEFAULT NULL,
  `fiscal_period_id` bigint(20) unsigned DEFAULT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','APPROVED','POSTED','REJECTED','CANCELLED','REVERSED') NOT NULL DEFAULT 'DRAFT',
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `exchange_rate` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `total_debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `posted_at` timestamp NULL DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `reversal_of_journal_id` bigint(20) unsigned DEFAULT NULL,
  `reversal_reason` varchar(255) DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversed_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journals_company_id_journal_number_unique` (`company_id`,`journal_number`),
  KEY `journals_fiscal_period_id_foreign` (`fiscal_period_id`),
  KEY `journals_currency_id_foreign` (`currency_id`),
  KEY `journals_posted_by_foreign` (`posted_by`),
  KEY `journals_reversed_by_foreign` (`reversed_by`),
  KEY `journals_created_by_foreign` (`created_by`),
  KEY `journals_updated_by_foreign` (`updated_by`),
  KEY `journals_status_index` (`status`),
  KEY `journals_company_id_journal_date_index` (`company_id`,`journal_date`),
  KEY `journals_company_id_posting_date_index` (`company_id`,`posting_date`),
  KEY `journals_company_id_status_index` (`company_id`,`status`),
  KEY `journals_branch_id_foreign` (`branch_id`),
  KEY `journals_department_id_foreign` (`department_id`),
  CONSTRAINT `journals_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journals_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journals_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journals_fiscal_period_id_foreign` FOREIGN KEY (`fiscal_period_id`) REFERENCES `fiscal_periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journals_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journals_reversed_by_foreign` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journals_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journals`
--

LOCK TABLES `journals` WRITE;
/*!40000 ALTER TABLE `journals` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `journals` VALUES
(1,1,NULL,NULL,'JV-2026-000001','2026-09-07','2026-09-08',9,NULL,NULL,NULL,'POSTED',NULL,1.00000000,700.0000,700.0000,'2026-09-08 06:01:22',1,NULL,NULL,NULL,NULL,1,1,'2026-09-07 12:54:19','2026-09-08 06:01:22'),
(2,1,NULL,NULL,'JV-2026-000002','2026-09-07','2026-09-08',9,NULL,NULL,'Another test','POSTED',NULL,1.00000000,9000.0000,9000.0000,'2026-09-08 06:00:26',1,NULL,NULL,NULL,NULL,1,1,'2026-09-07 13:01:05','2026-09-08 06:00:26'),
(3,1,NULL,NULL,'JV-2026-000003','2026-09-09',NULL,NULL,NULL,NULL,'Daily Expances','SUBMITTED',1,1.00000000,390.0000,390.0000,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-09-08 21:03:21','2026-09-08 21:03:31');
/*!40000 ALTER TABLE `journals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_company_id_code_unique` (`company_id`,`code`),
  KEY `locations_branch_id_foreign` (`branch_id`),
  KEY `locations_status_index` (`status`),
  CONSTRAINT `locations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `locations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2024_01_01_000001_create_companies_table',1),
(5,'2024_01_01_000002_create_currencies_table',1),
(6,'2024_01_01_000003_create_exchange_rates_table',1),
(7,'2024_01_01_000004_create_fiscal_years_table',1),
(8,'2024_01_01_000005_create_fiscal_periods_table',1),
(9,'2024_01_01_000006_create_branches_table',1),
(10,'2024_01_01_000007_create_departments_table',1),
(11,'2024_01_01_000008_create_cost_centers_table',1),
(12,'2024_01_01_000009_create_audit_logs_table',1),
(13,'2024_01_01_000010_create_number_sequences_table',1),
(14,'2024_01_01_000011_add_currency_foreign_to_companies',1),
(15,'2024_01_01_000012_create_divisions_table',1),
(16,'2024_01_01_000013_create_business_units_table',1),
(17,'2024_01_01_000014_create_locations_table',1),
(18,'2024_01_01_000015_create_profit_centers_table',1),
(19,'2024_01_01_000016_create_attachments_table',1),
(20,'2024_01_01_100001_create_accounts_table',1),
(21,'2024_01_01_100002_create_journals_table',1),
(22,'2024_01_01_100003_create_taxes_table',1),
(23,'2024_01_01_100004_create_journal_lines_table',1),
(24,'2024_01_01_100004_create_suppliers_table',1),
(25,'2024_01_01_100005_create_supplier_invoices_table',1),
(26,'2024_01_01_100006_create_supplier_invoice_lines_table',1),
(27,'2024_01_01_100007_create_bank_accounts_table',1),
(28,'2024_01_01_100008_create_bank_transactions_table',1),
(29,'2024_01_01_100009_create_supplier_payments_table',1),
(30,'2024_01_01_100010_create_payment_allocations_table',1),
(31,'2024_01_01_100011_create_customers_table',1),
(32,'2024_01_01_100013_create_customer_invoices_table',1),
(33,'2024_01_01_100014_create_customer_invoice_lines_table',1),
(34,'2024_01_01_100015_create_customer_receipts_table',1),
(35,'2024_01_01_100016_create_receipt_allocations_table',1),
(36,'2024_01_01_100019_create_bank_reconciliations_table',1),
(37,'2024_01_01_100019_create_budgets_table',1),
(38,'2024_01_01_100020_create_budget_lines_table',1),
(39,'2024_01_01_100021_create_supplier_debit_notes_table',1),
(40,'2024_01_01_100022_create_customer_credit_notes_table',1),
(41,'2024_01_01_100015_create_cash_accounts_table',2),
(42,'2024_01_01_100016_create_recurring_journals_table',3),
(43,'2024_01_01_100023_create_tax_transactions_table',4),
(44,'2024_01_01_100024_create_supplier_credit_notes_table',5),
(45,'2024_01_01_100025_create_customer_debit_notes_table',6),
(46,'2024_01_01_100026_create_account_balances_table',7),
(47,'2024_01_01_100027_create_payment_methods_table',8),
(48,'2024_01_01_100028_add_business_unit_id_to_journal_lines_table',9),
(49,'2024_01_01_100034_add_account_type_to_bank_accounts_table',10),
(50,'2024_01_01_100029_add_payable_account_id_to_suppliers_table',11),
(51,'2024_01_01_100030_add_receivable_account_id_to_customers_table',11),
(52,'2024_01_01_100031_add_missing_invoice_fields_to_supplier_invoices_table',11),
(53,'2024_01_01_100032_add_missing_invoice_fields_to_customer_invoices_table',11),
(54,'2024_01_01_100033_add_current_balance_to_bank_accounts_table',11),
(55,'2026_09_08_000001_create_branches_table',11),
(56,'2026_09_08_000002_create_roles_table',12),
(57,'2026_09_08_000003_create_permissions_table',12),
(58,'2026_09_08_000004_create_permission_role_table',12),
(59,'2026_09_08_000005_create_role_user_table',12),
(60,'2026_09_08_000006_create_user_companies_table',12),
(61,'2026_09_08_000007_create_user_branches_table',12),
(62,'2026_09_08_000008_create_user_departments_table',11),
(63,'2026_09_08_000009_create_company_user_roles_table',13),
(64,'2026_09_08_000010_add_branch_and_department_to_finance_tables',14),
(65,'2026_09_08_000011_add_profile_picture_to_users_table',15),
(66,'2026_09_09_083112_create_notifications_table',16),
(67,'2026_09_09_090000_create_workflow_definitions_table',17),
(68,'2026_09_09_090001_create_workflow_instances_table',17),
(69,'2026_09_09_090002_create_workflow_approvals_table',17),
(70,'2026_09_09_090003_create_workflow_actions_table',17),
(71,'2026_09_08_233831_add_company_id_to_budget_lines_table',18),
(72,'2026_09_09_112949_add_branch_id_to_invoices_table',18);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_notifiable_type_notifiable_id_read_at_index` (`notifiable_type`,`notifiable_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `number_sequences`
--

DROP TABLE IF EXISTS `number_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `number_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `prefix` varchar(50) NOT NULL,
  `format` varchar(100) NOT NULL DEFAULT '{PREFIX}-{YEAR}-{SEQUENCE:6}',
  `last_number` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_sequences_company_id_document_type_unique` (`company_id`,`document_type`),
  CONSTRAINT `number_sequences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `number_sequences`
--

LOCK TABLES `number_sequences` WRITE;
/*!40000 ALTER TABLE `number_sequences` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `number_sequences` VALUES
(1,1,'JV','JV','{PREFIX}-{YEAR}-{SEQUENCE:6}',3,1,'2026-09-07 09:44:24','2026-09-08 21:03:21'),
(2,1,'PV','PV','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(3,1,'RV','RV','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(4,1,'BRV','BRV','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(5,1,'BPV','BPV','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(6,1,'SI','SI','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(7,1,'CI','CI','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(8,1,'SP','SP','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24'),
(9,1,'CR','CR','{PREFIX}-{YEAR}-{SEQUENCE:6}',0,1,'2026-09-07 09:44:24','2026-09-07 09:44:24');
/*!40000 ALTER TABLE `number_sequences` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_payment_id` bigint(20) unsigned NOT NULL,
  `supplier_invoice_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_allocations_supplier_invoice_id_foreign` (`supplier_invoice_id`),
  KEY `payment_alloc_idx` (`supplier_payment_id`,`supplier_invoice_id`),
  CONSTRAINT `payment_allocations_supplier_invoice_id_foreign` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_allocations_supplier_payment_id_foreign` FOREIGN KEY (`supplier_payment_id`) REFERENCES `supplier_payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` enum('CASH','BANK_TRANSFER','CHECK','CARD','MOBILE_BANKING','OTHER') NOT NULL DEFAULT 'OTHER',
  `description` text DEFAULT NULL,
  `requires_reference` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_code_unique` (`code`),
  KEY `payment_methods_created_by_foreign` (`created_by`),
  KEY `payment_methods_updated_by_foreign` (`updated_by`),
  KEY `payment_methods_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `payment_methods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_methods_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_methods_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `payment_methods` VALUES
(1,1,'Cash','CASH','CASH','Cash payment',0,1,NULL,NULL,'2026-09-07 13:49:45','2026-09-07 13:49:45'),
(2,1,'Bank Transfer','BANK_TRANSFER','BANK_TRANSFER','Bank transfer payment',1,1,NULL,NULL,'2026-09-07 13:49:45','2026-09-07 13:49:45'),
(3,1,'Check','CHECK','CHECK','Check payment',1,1,NULL,NULL,'2026-09-07 13:49:45','2026-09-07 13:49:45'),
(4,1,'bKash','BKASH','MOBILE_BANKING','bKash mobile banking',1,1,NULL,NULL,'2026-09-07 13:49:45','2026-09-07 13:49:45'),
(5,1,'Nagad','NAGAD','MOBILE_BANKING','Nagad mobile banking',1,1,NULL,NULL,'2026-09-07 13:49:45','2026-09-07 13:49:45');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `permission_role`
--

DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_role_permission_id_role_id_unique` (`permission_id`,`role_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_role`
--

LOCK TABLES `permission_role` WRITE;
/*!40000 ALTER TABLE `permission_role` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `permission_role` VALUES
(1,1,1,NULL,NULL),
(2,2,1,NULL,NULL),
(3,3,1,NULL,NULL),
(4,4,1,NULL,NULL),
(5,5,1,NULL,NULL),
(6,6,1,NULL,NULL),
(7,7,1,NULL,NULL),
(8,8,1,NULL,NULL),
(9,9,1,NULL,NULL),
(10,10,1,NULL,NULL),
(11,11,1,NULL,NULL),
(12,12,1,NULL,NULL),
(13,13,1,NULL,NULL),
(14,14,1,NULL,NULL),
(15,15,1,NULL,NULL),
(16,16,1,NULL,NULL),
(17,17,1,NULL,NULL),
(18,18,1,NULL,NULL),
(19,19,1,NULL,NULL),
(20,20,1,NULL,NULL),
(21,21,1,NULL,NULL),
(22,22,1,NULL,NULL),
(23,23,1,NULL,NULL),
(24,24,1,NULL,NULL),
(25,25,1,NULL,NULL),
(26,26,1,NULL,NULL),
(27,27,1,NULL,NULL),
(28,28,1,NULL,NULL),
(29,29,1,NULL,NULL),
(30,30,1,NULL,NULL),
(31,31,1,NULL,NULL),
(32,32,1,NULL,NULL),
(33,33,1,NULL,NULL),
(34,34,1,NULL,NULL),
(35,35,1,NULL,NULL),
(36,36,1,NULL,NULL),
(37,37,1,NULL,NULL),
(38,38,1,NULL,NULL),
(39,39,1,NULL,NULL),
(40,40,1,NULL,NULL),
(41,41,1,NULL,NULL),
(42,42,1,NULL,NULL),
(43,43,1,NULL,NULL),
(44,44,1,NULL,NULL),
(45,6,2,NULL,NULL),
(46,2,2,NULL,NULL),
(47,1,2,NULL,NULL),
(48,11,2,NULL,NULL),
(49,12,2,NULL,NULL),
(50,10,2,NULL,NULL),
(51,38,2,NULL,NULL),
(52,39,2,NULL,NULL),
(53,37,2,NULL,NULL),
(54,34,2,NULL,NULL),
(55,35,2,NULL,NULL),
(56,33,2,NULL,NULL),
(57,30,2,NULL,NULL),
(58,31,2,NULL,NULL),
(59,29,2,NULL,NULL),
(60,19,2,NULL,NULL),
(61,15,2,NULL,NULL),
(62,20,2,NULL,NULL),
(63,18,2,NULL,NULL),
(64,16,2,NULL,NULL),
(65,14,2,NULL,NULL),
(66,22,2,NULL,NULL),
(67,21,2,NULL,NULL),
(68,24,2,NULL,NULL),
(69,23,2,NULL,NULL),
(70,26,2,NULL,NULL),
(71,27,2,NULL,NULL),
(72,25,2,NULL,NULL),
(73,42,2,NULL,NULL),
(74,43,2,NULL,NULL),
(75,41,2,NULL,NULL),
(76,1,3,NULL,NULL),
(77,10,3,NULL,NULL),
(78,37,3,NULL,NULL),
(79,33,3,NULL,NULL),
(80,29,3,NULL,NULL),
(81,15,3,NULL,NULL),
(82,18,3,NULL,NULL),
(83,16,3,NULL,NULL),
(84,14,3,NULL,NULL),
(85,22,3,NULL,NULL),
(86,21,3,NULL,NULL),
(87,24,3,NULL,NULL),
(88,23,3,NULL,NULL),
(89,25,3,NULL,NULL),
(90,41,3,NULL,NULL),
(91,1,4,NULL,NULL),
(92,10,4,NULL,NULL),
(93,37,4,NULL,NULL),
(94,33,4,NULL,NULL),
(95,29,4,NULL,NULL),
(96,14,4,NULL,NULL),
(97,21,4,NULL,NULL),
(98,23,4,NULL,NULL),
(99,25,4,NULL,NULL),
(100,41,4,NULL,NULL),
(101,1,5,NULL,NULL),
(102,15,5,NULL,NULL),
(103,14,5,NULL,NULL),
(104,23,5,NULL,NULL),
(105,26,5,NULL,NULL),
(106,27,5,NULL,NULL),
(107,25,5,NULL,NULL),
(108,1,6,NULL,NULL),
(109,30,6,NULL,NULL),
(110,31,6,NULL,NULL),
(111,29,6,NULL,NULL),
(112,15,6,NULL,NULL),
(113,14,6,NULL,NULL),
(114,23,6,NULL,NULL);
/*!40000 ALTER TABLE `permission_role` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `group` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `permissions` VALUES
(1,'View Dashboard','dashboard.view','Dashboard',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(2,'View Companies','core.companies.view','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(3,'Create Company','core.companies.create','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(4,'Edit Company','core.companies.update','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(5,'Delete Company','core.companies.delete','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(6,'View Branches','core.branches.view','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(7,'Create Branch','core.branches.create','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(8,'Edit Branch','core.branches.update','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(9,'Delete Branch','core.branches.delete','Company Management',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(10,'View Chart of Accounts','finance.accounts.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(11,'Create Account','finance.accounts.create','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(12,'Edit Account','finance.accounts.update','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(13,'Delete Account','finance.accounts.delete','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(14,'View Journal Entry','finance.journals.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(15,'Create Journal','finance.journals.create','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(16,'Edit Journal','finance.journals.update','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(17,'Delete Journal','finance.journals.delete','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(18,'Submit Journal','finance.journals.submit','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(19,'Approve Journal','finance.journals.approve','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(20,'Post Journal','finance.journals.post','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(21,'View Ledger','finance.ledger.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(22,'Export Ledger','finance.ledger.export','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(23,'View Reports','finance.reports.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(24,'Export Reports','finance.reports.export','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(25,'View Suppliers','finance.suppliers.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(26,'Create Supplier','finance.suppliers.create','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(27,'Edit Supplier','finance.suppliers.update','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(28,'Delete Supplier','finance.suppliers.delete','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(29,'View Customers','finance.customers.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(30,'Create Customer','finance.customers.create','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(31,'Edit Customer','finance.customers.update','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(32,'Delete Customer','finance.customers.delete','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(33,'View Cost Centers','finance.costcenters.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(34,'Create Cost Center','finance.costcenters.create','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(35,'Edit Cost Center','finance.costcenters.update','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(36,'Delete Cost Center','finance.costcenters.delete','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(37,'View Budgets','finance.budgets.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(38,'Create Budget','finance.budgets.create','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(39,'Edit Budget','finance.budgets.update','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(40,'Delete Budget','finance.budgets.delete','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(41,'View Taxes','finance.taxes.view','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(42,'Create Tax','finance.taxes.create','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(43,'Edit Tax','finance.taxes.update','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40'),
(44,'Delete Tax','finance.taxes.delete','Finance',NULL,'2026-09-08 00:20:40','2026-09-08 00:20:40');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `profit_centers`
--

DROP TABLE IF EXISTS `profit_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `profit_centers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profit_centers_company_id_code_unique` (`company_id`,`code`),
  KEY `profit_centers_parent_id_foreign` (`parent_id`),
  KEY `profit_centers_manager_id_foreign` (`manager_id`),
  KEY `profit_centers_status_index` (`status`),
  CONSTRAINT `profit_centers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `profit_centers_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `profit_centers_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `profit_centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profit_centers`
--

LOCK TABLES `profit_centers` WRITE;
/*!40000 ALTER TABLE `profit_centers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `profit_centers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `receipt_allocations`
--

DROP TABLE IF EXISTS `receipt_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipt_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_receipt_id` bigint(20) unsigned NOT NULL,
  `customer_invoice_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipt_allocations_customer_invoice_id_foreign` (`customer_invoice_id`),
  KEY `receipt_alloc_idx` (`customer_receipt_id`,`customer_invoice_id`),
  CONSTRAINT `receipt_allocations_customer_invoice_id_foreign` FOREIGN KEY (`customer_invoice_id`) REFERENCES `customer_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_allocations_customer_receipt_id_foreign` FOREIGN KEY (`customer_receipt_id`) REFERENCES `customer_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipt_allocations`
--

LOCK TABLES `receipt_allocations` WRITE;
/*!40000 ALTER TABLE `receipt_allocations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `receipt_allocations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `recurring_journals`
--

DROP TABLE IF EXISTS `recurring_journals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_journals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL DEFAULT 'MONTHLY',
  `next_run_date` date NOT NULL,
  `lines` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`lines`)),
  `description` text DEFAULT NULL,
  `status` enum('active','paused','completed') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_journals_company_id_foreign` (`company_id`),
  KEY `recurring_journals_created_by_foreign` (`created_by`),
  KEY `recurring_journals_updated_by_foreign` (`updated_by`),
  CONSTRAINT `recurring_journals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_journals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recurring_journals_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_journals`
--

LOCK TABLES `recurring_journals` WRITE;
/*!40000 ALTER TABLE `recurring_journals` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `recurring_journals` VALUES
(1,1,'Monthly Rent','MONTHLY','2026-10-01','[]','Office rent payment','active',1,1,'2026-09-07 12:45:51','2026-09-07 12:45:51'),
(2,1,'Weekly Payroll','WEEKLY','2026-09-13','[]','Employee salary','active',1,1,'2026-09-07 12:45:51','2026-09-07 12:45:51'),
(3,1,'Annual Insurance','YEARLY','2027-01-01','[]','Insurance premium','paused',1,1,'2026-09-07 12:45:51','2026-09-07 12:45:51');
/*!40000 ALTER TABLE `recurring_journals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_user_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `roles` VALUES
(1,'Super Admin','super-admin','Full system access','active','2026-09-08 00:20:40','2026-09-08 00:20:40'),
(2,'Finance Manager','finance-manager','Manage all finance operations','active','2026-09-08 00:20:40','2026-09-08 00:20:40'),
(3,'Accountant','accountant','Create and manage journals and reports','active','2026-09-08 00:20:40','2026-09-08 00:20:40'),
(4,'Finance User','finance-user','View-only access to finance data','active','2026-09-08 00:20:40','2026-09-08 00:20:40'),
(5,'AP Clerk','ap-clerk','Manage accounts payable','active','2026-09-08 00:20:40','2026-09-08 00:20:40'),
(6,'AR Clerk','ar-clerk','Manage accounts receivable','active','2026-09-08 00:20:40','2026-09-08 00:20:40');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `sessions` VALUES
('hrnLdxlVGNHujIFcmYpcGP5AAuYWmD04ElTQ5ARO',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:154.0) Gecko/20100101 Firefox/154.0','eyJfdG9rZW4iOiI1WER1a3VEN3FNSm9YQmFYMWhkNllxQ2JrTEJ6Q21tVUY1ckdXZzZ6IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvYml6bmV4dXMudGVzdFwvZGFzaGJvYXJkIiwicm91dGUiOiJkYXNoYm9hcmQifSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsImFjdGl2ZV9jb21wYW55X2lkIjoxLCJhY3RpdmVfYnJhbmNoX2lkIjoyfQ==',1788949136);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `supplier_credit_notes`
--

DROP TABLE IF EXISTS `supplier_credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `supplier_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `credit_note_number` varchar(255) NOT NULL,
  `credit_note_date` date NOT NULL,
  `subtotal` decimal(20,4) NOT NULL,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(20,4) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','posted','cancelled') NOT NULL DEFAULT 'draft',
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_credit_notes_credit_note_number_unique` (`credit_note_number`),
  KEY `supplier_credit_notes_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_credit_notes_supplier_invoice_id_foreign` (`supplier_invoice_id`),
  KEY `supplier_credit_notes_posted_by_foreign` (`posted_by`),
  KEY `supplier_credit_notes_created_by_foreign` (`created_by`),
  KEY `supplier_credit_notes_updated_by_foreign` (`updated_by`),
  KEY `supplier_credit_notes_company_id_supplier_id_index` (`company_id`,`supplier_id`),
  KEY `supplier_credit_notes_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `supplier_credit_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_credit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_credit_notes_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_credit_notes_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_credit_notes_supplier_invoice_id_foreign` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_credit_notes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_credit_notes`
--

LOCK TABLES `supplier_credit_notes` WRITE;
/*!40000 ALTER TABLE `supplier_credit_notes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `supplier_credit_notes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `supplier_debit_notes`
--

DROP TABLE IF EXISTS `supplier_debit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_debit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `note_number` varchar(50) NOT NULL,
  `note_date` date NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_debit_notes_company_id_note_number_unique` (`company_id`,`note_number`),
  KEY `supplier_debit_notes_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_debit_notes_created_by_foreign` (`created_by`),
  KEY `supplier_debit_notes_updated_by_foreign` (`updated_by`),
  KEY `supplier_debit_notes_status_index` (`status`),
  CONSTRAINT `supplier_debit_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_debit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_debit_notes_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_debit_notes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_debit_notes`
--

LOCK TABLES `supplier_debit_notes` WRITE;
/*!40000 ALTER TABLE `supplier_debit_notes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `supplier_debit_notes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `supplier_invoice_approvals`
--

DROP TABLE IF EXISTS `supplier_invoice_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_invoice_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `approver_role` varchar(50) DEFAULT NULL,
  `approval_level` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `comments` text DEFAULT NULL,
  `acted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `si_approvals_si_level_idx` (`supplier_invoice_id`,`approval_level`),
  KEY `supplier_invoice_approvals_approver_id_index` (`approver_id`),
  CONSTRAINT `supplier_invoice_approvals_supplier_invoice_id_foreign` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_invoice_approvals`
--

LOCK TABLES `supplier_invoice_approvals` WRITE;
/*!40000 ALTER TABLE `supplier_invoice_approvals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `supplier_invoice_approvals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `supplier_invoice_lines`
--

DROP TABLE IF EXISTS `supplier_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(20,4) NOT NULL DEFAULT 1.0000,
  `unit_price` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subtotal` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_invoice_lines_supplier_invoice_id_foreign` (`supplier_invoice_id`),
  KEY `supplier_invoice_lines_account_id_foreign` (`account_id`),
  CONSTRAINT `supplier_invoice_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_invoice_lines_supplier_invoice_id_foreign` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_invoice_lines`
--

LOCK TABLES `supplier_invoice_lines` WRITE;
/*!40000 ALTER TABLE `supplier_invoice_lines` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `supplier_invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `supplier_invoices`
--

DROP TABLE IF EXISTS `supplier_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `exchange_rate` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `subtotal` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `outstanding_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_invoices_company_id_invoice_number_unique` (`company_id`,`invoice_number`),
  KEY `supplier_invoices_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_invoices_currency_id_foreign` (`currency_id`),
  KEY `supplier_invoices_created_by_foreign` (`created_by`),
  KEY `supplier_invoices_updated_by_foreign` (`updated_by`),
  KEY `supplier_invoices_status_index` (`status`),
  KEY `supplier_invoices_company_id_supplier_id_index` (`company_id`,`supplier_id`),
  KEY `supplier_invoices_branch_id_foreign` (`branch_id`),
  CONSTRAINT `supplier_invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_invoices_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_invoices_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_invoices_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_invoices`
--

LOCK TABLES `supplier_invoices` WRITE;
/*!40000 ALTER TABLE `supplier_invoices` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `supplier_invoices` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `supplier_payments`
--

DROP TABLE IF EXISTS `supplier_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `exchange_rate` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `payment_method` varchar(50) NOT NULL DEFAULT 'BANK_TRANSFER',
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_payments_company_id_payment_number_unique` (`company_id`,`payment_number`),
  KEY `supplier_payments_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_payments_currency_id_foreign` (`currency_id`),
  KEY `supplier_payments_bank_account_id_foreign` (`bank_account_id`),
  KEY `supplier_payments_created_by_foreign` (`created_by`),
  KEY `supplier_payments_updated_by_foreign` (`updated_by`),
  KEY `supplier_payments_status_index` (`status`),
  KEY `supplier_payments_branch_id_foreign` (`branch_id`),
  CONSTRAINT `supplier_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_payments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_payments_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_payments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_payments`
--

LOCK TABLES `supplier_payments` WRITE;
/*!40000 ALTER TABLE `supplier_payments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `supplier_payments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `supplier_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `payable_account_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_company_id_supplier_code_unique` (`company_id`,`supplier_code`),
  KEY `suppliers_currency_id_foreign` (`currency_id`),
  KEY `suppliers_payable_account_id_foreign` (`payable_account_id`),
  KEY `suppliers_created_by_foreign` (`created_by`),
  KEY `suppliers_updated_by_foreign` (`updated_by`),
  KEY `suppliers_status_index` (`status`),
  CONSTRAINT `suppliers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suppliers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `suppliers_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `suppliers_payable_account_id_foreign` FOREIGN KEY (`payable_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `suppliers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `suppliers` VALUES
(1,1,'SUP-001','Tech Solutions Ltd','John Smith','123 Tech Street, Dhaka','+8801711223344','john@techsolutions.com','TAX-100001',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(2,1,'SUP-002','Global Office Supplies','Maria Garcia','456 Commerce Ave, Dhaka','+8801711223355','maria@globaloffice.com','TAX-100002',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(3,1,'SUP-003','Prime Logistics Co','Ahmed Hassan','789 Industrial Zone, Dhaka','+8801711223366','ahmed@primelogistics.com','TAX-100003',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(4,1,'SUP-004','BuildRight Construction','Sarah Johnson','321 Builder Lane, Dhaka','+8801711223377','sarah@buildright.com','TAX-100004',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(5,1,'SUP-005','EcoGreen Products','Tanvir Rahman','654 Green Road, Dhaka','+8801711223388','tanvir@ecogreen.com','TAX-100005',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(6,1,'SUP-006','Digital Services Inc','Lisa Chen','987 Silicon Valley, Dhaka','+8801711223399','lisa@digitalservices.com','TAX-100006',1,NULL,'inactive',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(7,1,'SUP-007','Fresh Food Distributors','Karim Uddin','147 Market Street, Dhaka','+8801711223400','karim@freshfood.com','TAX-100007',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(8,1,'SUP-008','Steel Works International','Peter Brown','258 Steel Town, Dhaka','+8801711223411','peter@steelworks.com','TAX-100008',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(9,1,'SUP-009','IT Hardware Hub','Nadia Islam','369 Computer Plaza, Dhaka','+8801711223422','nadia@ithardware.com','TAX-100009',1,NULL,'active',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48'),
(10,1,'SUP-010','CleanPro Janitorial','Rashid Khan','741 Clean Avenue, Dhaka','+8801711223433','rashid@cleanpro.com','TAX-100010',1,NULL,'inactive',NULL,NULL,'2026-09-07 11:13:48','2026-09-07 11:13:48');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tax_transactions`
--

DROP TABLE IF EXISTS `tax_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tax_id` bigint(20) unsigned NOT NULL,
  `transaction_type` enum('INPUT','OUTPUT','WITHHOLDING','ADJUSTMENT') NOT NULL DEFAULT 'INPUT',
  `journal_line_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `taxable_amount` decimal(20,4) NOT NULL,
  `tax_amount` decimal(20,4) NOT NULL,
  `exchange_rate` decimal(20,6) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT NULL,
  `tax_date` date NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_transactions_tax_id_foreign` (`tax_id`),
  KEY `tax_transactions_journal_line_id_foreign` (`journal_line_id`),
  KEY `tax_transactions_invoice_id_foreign` (`invoice_id`),
  KEY `tax_transactions_payment_id_foreign` (`payment_id`),
  KEY `tax_transactions_created_by_foreign` (`created_by`),
  KEY `tax_transactions_updated_by_foreign` (`updated_by`),
  KEY `tax_transactions_company_id_tax_id_tax_date_index` (`company_id`,`tax_id`,`tax_date`),
  KEY `tax_transactions_company_id_transaction_type_index` (`company_id`,`transaction_type`),
  CONSTRAINT `tax_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_transactions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_transactions_journal_line_id_foreign` FOREIGN KEY (`journal_line_id`) REFERENCES `journal_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `supplier_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_transactions_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_transactions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_transactions`
--

LOCK TABLES `tax_transactions` WRITE;
/*!40000 ALTER TABLE `tax_transactions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `tax_transactions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `taxes`
--

DROP TABLE IF EXISTS `taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tax_code` varchar(50) NOT NULL,
  `tax_name` varchar(255) NOT NULL,
  `tax_type` enum('VAT','WITHHOLDING_TAX','INCOME_TAX','OTHER') NOT NULL,
  `rate` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `is_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `input_account_id` bigint(20) unsigned DEFAULT NULL,
  `output_account_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taxes_company_id_tax_code_unique` (`company_id`,`tax_code`),
  KEY `taxes_input_account_id_foreign` (`input_account_id`),
  KEY `taxes_output_account_id_foreign` (`output_account_id`),
  KEY `taxes_status_index` (`status`),
  CONSTRAINT `taxes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `taxes_input_account_id_foreign` FOREIGN KEY (`input_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `taxes_output_account_id_foreign` FOREIGN KEY (`output_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taxes`
--

LOCK TABLES `taxes` WRITE;
/*!40000 ALTER TABLE `taxes` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `taxes` VALUES
(1,1,'VAT-15','Value Added Tax','VAT',15.0000,0,1,2,'active','2026-09-07 12:06:10','2026-09-07 12:06:10'),
(2,1,'WHT-10','Withholding Tax','WITHHOLDING_TAX',10.0000,0,3,4,'active','2026-09-07 12:06:10','2026-09-07 12:06:10'),
(3,1,'CST-5','Corporate Surtax','INCOME_TAX',5.0000,0,5,6,'active','2026-09-07 12:06:10','2026-09-07 12:06:10');
/*!40000 ALTER TABLE `taxes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_branches`
--

DROP TABLE IF EXISTS `user_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_branches_user_id_company_id_branch_id_unique` (`user_id`,`company_id`,`branch_id`),
  KEY `user_branches_company_id_foreign` (`company_id`),
  KEY `user_branches_branch_id_foreign` (`branch_id`),
  CONSTRAINT `user_branches_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_branches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_branches`
--

LOCK TABLES `user_branches` WRITE;
/*!40000 ALTER TABLE `user_branches` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_branches` VALUES
(1,1,1,1,'active','2026-09-09 03:04:08','2026-09-09 03:04:08'),
(2,1,1,2,'active','2026-09-09 03:04:08','2026-09-09 03:04:08');
/*!40000 ALTER TABLE `user_branches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_companies`
--

DROP TABLE IF EXISTS `user_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_companies_user_id_company_id_unique` (`user_id`,`company_id`),
  KEY `user_companies_company_id_foreign` (`company_id`),
  CONSTRAINT `user_companies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_companies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_companies`
--

LOCK TABLES `user_companies` WRITE;
/*!40000 ALTER TABLE `user_companies` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_companies` VALUES
(9,2,1,0,'active','2026-09-08 02:57:57','2026-09-08 02:57:57'),
(10,2,2,0,'active','2026-09-08 02:57:57','2026-09-08 02:57:57'),
(13,1,1,0,'active','2026-09-09 03:04:08','2026-09-09 03:04:08'),
(14,1,2,0,'active','2026-09-09 03:04:08','2026-09-09 03:04:08');
/*!40000 ALTER TABLE `user_companies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_departments`
--

DROP TABLE IF EXISTS `user_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_departments_user_id_foreign` (`user_id`),
  KEY `user_departments_company_id_foreign` (`company_id`),
  KEY `user_departments_branch_id_foreign` (`branch_id`),
  KEY `user_departments_department_id_foreign` (`department_id`),
  CONSTRAINT `user_departments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_departments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_departments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_departments`
--

LOCK TABLES `user_departments` WRITE;
/*!40000 ALTER TABLE `user_departments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `user_departments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'Administrator','admin@biznexus.com',NULL,'$2y$12$Wx/tzmoCZ2FCJcXR3OhomOodUtKLDQE9hfRE9.F3AEaFMqOK0U9XS','profile-pictures/f6EPlilQOWDwiDduhdE7mL9SmBJpMNdrS9T26Wwl.jpg','3I6A4MxspBZOULGdppXCxoSNEXAvgCo1SB2sZIECr8xusLiEiZbUOPBHEnEb','2026-09-07 10:04:20','2026-09-08 02:58:45'),
(2,'Saiidur Rahman','engsaidur@gmail.com','2026-09-08 00:51:01','$2y$12$KFoebI6ZLFLB5ThkrQSLD.Xj16bvLxh5C5VtktwbEn3inSMbMrM22',NULL,'BBXLVVE4MJ','2026-09-08 00:51:02','2026-09-08 02:57:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_actions`
--

DROP TABLE IF EXISTS `workflow_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_instance_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `from_state` varchar(255) DEFAULT NULL,
  `to_state` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `acted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `workflow_actions_workflow_instance_id_acted_at_index` (`workflow_instance_id`,`acted_at`),
  CONSTRAINT `workflow_actions_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_actions`
--

LOCK TABLES `workflow_actions` WRITE;
/*!40000 ALTER TABLE `workflow_actions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `workflow_actions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_approvals`
--

DROP TABLE IF EXISTS `workflow_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_instance_id` bigint(20) unsigned NOT NULL,
  `approver_type` varchar(255) NOT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `acted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_approvals_workflow_instance_id_status_index` (`workflow_instance_id`,`status`),
  CONSTRAINT `workflow_approvals_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_approvals`
--

LOCK TABLES `workflow_approvals` WRITE;
/*!40000 ALTER TABLE `workflow_approvals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `workflow_approvals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_definitions`
--

DROP TABLE IF EXISTS `workflow_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_definitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `states` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`states`)),
  `transitions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`transitions`)),
  `approval_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approval_roles`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_definitions_entity_type_is_active_index` (`entity_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_definitions`
--

LOCK TABLES `workflow_definitions` WRITE;
/*!40000 ALTER TABLE `workflow_definitions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `workflow_definitions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_instances`
--

DROP TABLE IF EXISTS `workflow_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_definition_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `current_state` varchar(255) NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_instances_workflow_definition_id_foreign` (`workflow_definition_id`),
  KEY `workflow_instances_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `workflow_instances_current_state_index` (`current_state`),
  CONSTRAINT `workflow_instances_workflow_definition_id_foreign` FOREIGN KEY (`workflow_definition_id`) REFERENCES `workflow_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_instances`
--

LOCK TABLES `workflow_instances` WRITE;
/*!40000 ALTER TABLE `workflow_instances` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `workflow_instances` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-09 16:22:49
