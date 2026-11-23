-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: flowershop
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `event` varchar(50) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(1024) DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_event` (`event`),
  KEY `idx_activity_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"Can I customize?\"}','2026-10-22 00:07:21'),(2,NULL,'login_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','{\"reason\": \"not_found\", \"username\": \"admin@bloomify.com\"}','2026-10-25 16:49:21'),(3,1,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','{\"source\": \"web\"}','2026-10-25 16:49:38'),(4,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"hi\"}','2026-11-09 14:43:48'),(5,1,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"web\"}','2026-11-11 00:56:10'),(6,1,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"web\"}','2026-11-11 01:08:36'),(7,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"magandang hapon\"}','2026-11-16 07:52:57'),(8,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"Same-day delivery?\"}','2026-11-16 07:54:25'),(9,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"Same-day delivery?\"}','2026-11-16 08:25:03'),(10,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"Same-day delivery?\"}','2026-11-16 08:27:34'),(11,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"Any bestseller bouquets?\"}','2026-11-16 08:35:00'),(12,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"Any bestseller bouquets?\"}','2026-11-16 08:35:54'),(13,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"magandang hapon\"}','2026-11-16 08:38:19'),(14,NULL,'chatbot_message','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"chatbot\", \"flagged\": 0, \"message_preview\": \"san ang lokasyon ng shop na ito?\"}','2026-11-16 08:38:36'),(15,1,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"web\"}','2026-11-17 01:57:38'),(16,3,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"web\"}','2026-11-17 02:01:57'),(17,1,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"web\"}','2026-11-17 02:19:31'),(18,3,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','{\"source\": \"web\"}','2026-11-17 02:21:47'),(19,3,'login_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"reason\": \"invalid_password\", \"username\": \"aizer@gmail.com\"}','2026-11-23 12:04:00'),(20,3,'login_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"reason\": \"invalid_password\", \"username\": \"aizer@gmail.com\"}','2026-11-23 12:04:08'),(21,3,'login_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"reason\": \"invalid_password\", \"username\": \"aizer@gmail.com\"}','2026-11-23 12:04:26'),(22,3,'login_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"reason\": \"invalid_password\", \"username\": \"aizer@gmail.com\"}','2026-11-23 12:04:33'),(23,1,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"source\": \"web\"}','2026-11-23 12:54:26'),(24,3,'login_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"reason\": \"invalid_password\", \"username\": \"aizer@gmail.com\"}','2026-11-23 13:15:36'),(25,6,'login_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"reason\": \"invalid_password\", \"username\": \"rhamzel@gmail.com\"}','2026-11-23 13:16:08'),(26,8,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"source\": \"web\"}','2026-11-23 13:18:13'),(27,1,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36','{\"source\": \"web\"}','2026-11-23 13:22:23');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banned_words`
--

DROP TABLE IF EXISTS `banned_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banned_words` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`),
  KEY `idx_banned_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banned_words`
--

LOCK TABLES `banned_words` WRITE;
/*!40000 ALTER TABLE `banned_words` DISABLE KEYS */;
/*!40000 ALTER TABLE `banned_words` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banned_words_audit`
--

DROP TABLE IF EXISTS `banned_words_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banned_words_audit` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word_id` int unsigned DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `admin_id` int unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bwa_word` (`word_id`),
  KEY `idx_bwa_admin` (`admin_id`),
  KEY `idx_bwa_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banned_words_audit`
--

LOCK TABLES `banned_words_audit` WRITE;
/*!40000 ALTER TABLE `banned_words_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `banned_words_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (85,6,23,1,'2026-03-29 17:53:26');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (12,'Best Sellers','Most popular flower arrangements and products',NULL,'2025-11-12 13:58:10'),(13,'Color Themes','Flowers arranged by color schemes and themes',NULL,'2025-11-12 13:58:10'),(14,'Arrangements','Various flower arrangement styles and types',NULL,'2025-11-12 13:58:10'),(15,'Flower Types','Different varieties and species of flowers',NULL,'2025-11-12 13:58:10'),(16,'Occasions','Flowers for specific events and celebrations',NULL,'2025-11-12 13:58:10'),(17,'Customize','Custom flower arrangements and personalization options',NULL,'2025-11-12 13:58:10');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_fallbacks`
--

DROP TABLE IF EXISTS `chatbot_fallbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_fallbacks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `keyword` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `match_type` enum('contains','exact') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contains',
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` int NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_fallbacks`
--

LOCK TABLES `chatbot_fallbacks` WRITE;
/*!40000 ALTER TABLE `chatbot_fallbacks` DISABLE KEYS */;
INSERT INTO `chatbot_fallbacks` VALUES (1,'blank','contains','Sorry, I didn’t quite understand that.\r\nHere are some things you can try:\r\n• View Services\r\n• Pricing\r\n• Contact Support\r\nHow can I help you today?',1,1,'2025-12-06 08:14:38','2025-12-06 08:14:38'),(2,'complaint, bad, angry, refund, issue, problem','contains','I’m really sorry you\'re experiencing this.\r\nLet me help you right away.',2,1,'2025-12-06 08:16:34','2025-12-06 08:16:34'),(3,'help, assist, support','contains','I\'m here to help!\r\nTell me what you need, or choose one of the quick options:\r\n• Contact Support\r\n• FAQs\r\n• View Services',3,1,'2025-12-06 08:18:35','2025-12-06 08:18:35');
/*!40000 ALTER TABLE `chatbot_fallbacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_quick_replies`
--

DROP TABLE IF EXISTS `chatbot_quick_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_quick_replies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_quick_replies`
--

LOCK TABLES `chatbot_quick_replies` WRITE;
/*!40000 ALTER TABLE `chatbot_quick_replies` DISABLE KEYS */;
INSERT INTO `chatbot_quick_replies` VALUES (1,'Get Started','Hi! How can I help you today? 😊',1,1,'2025-12-06 08:13:04','2025-12-06 08:13:04'),(2,'View Services','Please show me the list of services.',2,1,'2025-12-06 08:15:40','2025-12-06 08:15:40'),(3,'Contact Support','I need to talk to support.',3,1,'2025-12-06 08:17:53','2025-12-06 08:17:53');
/*!40000 ALTER TABLE `chatbot_quick_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_settings`
--

DROP TABLE IF EXISTS `chatbot_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `provider` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Custom',
  `model_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'grok-4-latest',
  `base_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'https://api.x.ai/v1',
  `api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `primary_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#23a86b',
  `accent_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#14b8a6',
  `header_bg_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0f8d56',
  `header_text_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#ffffff',
  `welcome_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hi! I''m RaymundoBot.',
  `welcome_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status_badge` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Online',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_settings`
--

LOCK TABLES `chatbot_settings` WRITE;
/*!40000 ALTER TABLE `chatbot_settings` DISABLE KEYS */;
INSERT INTO `chatbot_settings` VALUES (1,1,'Custom / Other','openai/gpt-oss-120b','https://api.groq.com/openai/v1','','#23A86B','#14B8A6','#0F8D56','#FFFFFF','','','','2025-12-06 07:25:37','2025-12-06 08:20:00');
/*!40000 ALTER TABLE `chatbot_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_thread_messages`
--

DROP TABLE IF EXISTS `contact_thread_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_thread_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `thread_id` int NOT NULL,
  `sender_type` enum('customer','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` int DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_mime` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` int DEFAULT NULL,
  `is_read_by_customer` tinyint(1) NOT NULL DEFAULT '0',
  `is_read_by_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_thread` (`thread_id`),
  KEY `idx_sender` (`sender_type`),
  CONSTRAINT `fk_contact_thread_messages_thread` FOREIGN KEY (`thread_id`) REFERENCES `contact_threads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_thread_messages`
--

LOCK TABLES `contact_thread_messages` WRITE;
/*!40000 ALTER TABLE `contact_thread_messages` DISABLE KEYS */;
INSERT INTO `contact_thread_messages` VALUES (1,1,'customer',5,'holla admin',NULL,NULL,NULL,NULL,1,1,'2025-11-30 14:48:11'),(2,1,'admin',1,'hello kamusta',NULL,NULL,NULL,NULL,1,1,'2025-11-30 14:56:10'),(3,1,'customer',5,'oorder sana ako, pero hulaan mo',NULL,NULL,NULL,NULL,1,1,'2025-11-30 14:57:41'),(4,2,'customer',6,'magandang gabi admin',NULL,NULL,NULL,NULL,1,1,'2025-11-30 15:00:53'),(5,2,'customer',6,'magandang gabi admin',NULL,NULL,NULL,NULL,1,1,'2025-11-30 15:29:24'),(6,2,'customer',6,'oii',NULL,NULL,NULL,NULL,1,1,'2025-11-30 15:29:43'),(7,2,'admin',1,'bakit',NULL,NULL,NULL,NULL,1,1,'2025-11-30 15:30:53'),(8,3,'customer',2,'zup admin',NULL,NULL,NULL,NULL,1,1,'2025-11-30 15:59:42'),(9,3,'admin',1,'yow',NULL,NULL,NULL,NULL,1,1,'2025-11-30 16:13:48'),(10,4,'customer',4,'goodmornight admin',NULL,NULL,NULL,NULL,1,1,'2025-11-30 16:24:00'),(11,4,'customer',4,'hello admin',NULL,NULL,NULL,NULL,1,1,'2025-12-06 16:23:13'),(12,4,'admin',1,'hii',NULL,NULL,NULL,NULL,1,1,'2025-12-06 16:24:03'),(13,5,'admin',1,'heyy',NULL,NULL,NULL,NULL,1,1,'2025-12-07 12:14:00'),(14,5,'customer',7,'hello admin',NULL,NULL,NULL,NULL,1,0,'2025-12-07 12:14:45'),(15,4,'customer',4,'yow',NULL,NULL,NULL,NULL,1,1,'2026-03-22 15:24:03'),(16,4,'customer',4,'hey',NULL,NULL,NULL,NULL,1,1,'2026-03-26 11:54:58');
/*!40000 ALTER TABLE `contact_thread_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_threads`
--

DROP TABLE IF EXISTS `contact_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_threads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','pending','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `last_message_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_contact_thread_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_threads`
--

LOCK TABLES `contact_threads` WRITE;
/*!40000 ALTER TABLE `contact_threads` DISABLE KEYS */;
INSERT INTO `contact_threads` VALUES (1,5,'Nathaniel Layron','nathaniel@gmail.com','to inform','open','2025-11-30 14:57:41','2025-11-30 14:48:11','2025-11-30 14:57:41'),(2,6,'Rhamzel Mogool','rhamzel@gmail.com','to update','pending','2025-11-30 15:30:53','2025-11-30 15:00:53','2025-11-30 15:30:53'),(3,2,'Glenford Macasa','glenfordmacasa@gmail.com','to inform','pending','2025-11-30 16:13:48','2025-11-30 15:59:42','2025-11-30 16:13:48'),(4,4,'Van Luci','van@gmail.com','to inform','open','2026-03-26 11:54:58','2025-11-30 16:24:00','2026-03-26 11:54:58'),(5,7,'Gerald Anderson','gerald@gmail.com','Support Assistance','open','2025-12-07 12:14:45','2025-12-07 12:13:55','2025-12-07 12:14:45');
/*!40000 ALTER TABLE `contact_threads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usage`
--

DROP TABLE IF EXISTS `coupon_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coupon_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usage`
--

LOCK TABLES `coupon_usage` WRITE;
/*!40000 ALTER TABLE `coupon_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT '0.00',
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_bouquet_designs`
--

DROP TABLE IF EXISTS `custom_bouquet_designs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_bouquet_designs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `status` enum('in_cart','ordered','discarded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_cart',
  `preview_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `canvas_json` longtext COLLATE utf8mb4_unicode_ci,
  `items_json` longtext COLLATE utf8mb4_unicode_ci,
  `design_notes` text COLLATE utf8mb4_unicode_ci,
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_custom_design_user_status` (`user_id`,`status`),
  KEY `idx_custom_design_order` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_bouquet_designs`
--

LOCK TABLES `custom_bouquet_designs` WRITE;
/*!40000 ALTER TABLE `custom_bouquet_designs` DISABLE KEYS */;
INSERT INTO `custom_bouquet_designs` VALUES (1,8,28,'ordered','storage/custom_designs/design_8_1795440030_ef5fb15e.png','{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":156.37,\"top\":241.09,\"width\":517,\"height\":900,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.1,\"scaleY\":0.1,\"angle\":-26,\"flipX\":true,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"63\",\"step\":\"fillers\",\"name\":\"Eucalyptus Branch\",\"price\":10},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795439454_6b043b5e6842f_Realistic_Eucalyptus_Branch_PNG___Greenery_Overlay_cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]},{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":300.19,\"top\":174.24,\"width\":517,\"height\":900,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.15,\"scaleY\":0.15,\"angle\":26,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"63\",\"step\":\"fillers\",\"name\":\"Eucalyptus Branch\",\"price\":10},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795439454_6b043b5e6842f_Realistic_Eucalyptus_Branch_PNG___Greenery_Overlay_cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]},{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":240.7,\"top\":264.97,\"width\":507,\"height\":900,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.23,\"scaleY\":0.23,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"61\",\"step\":\"flowers\",\"name\":\"Yellow Flower\",\"price\":100},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795438990_6b04398ecbc5e_Cremon_Disbud_Yellow_-_Bulk_and_Wholesale_-_10_stems_cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]},{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":189.61,\"top\":335.03,\"width\":507,\"height\":900,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.23,\"scaleY\":0.23,\"angle\":-14,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"61\",\"step\":\"flowers\",\"name\":\"Yellow Flower\",\"price\":100},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795438990_6b04398ecbc5e_Cremon_Disbud_Yellow_-_Bulk_and_Wholesale_-_10_stems_cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]},{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":210,\"top\":206,\"width\":445,\"height\":605,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.35,\"scaleY\":0.35,\"angle\":-8,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"62\",\"step\":\"flowers\",\"name\":\"White Flower\",\"price\":150},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795439077_6b0439e5d0cf4________________________________PNG__________________________________PNG_______________________cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]},{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":297,\"top\":271,\"width\":445,\"height\":605,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.35,\"scaleY\":0.35,\"angle\":14,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"62\",\"step\":\"flowers\",\"name\":\"White Flower\",\"price\":150},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795439077_6b0439e5d0cf4________________________________PNG__________________________________PNG_______________________cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]},{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":229.86,\"top\":359.53,\"width\":514,\"height\":531,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.39,\"scaleY\":0.39,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"52\",\"step\":\"container\",\"name\":\"Vintage Floral Ceramic Planter Pot\",\"price\":250},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795438647_6b04383728967_Vintage_Floral_Ceramic_Planter_Pot_cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]},{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":240,\"top\":361,\"width\":514,\"height\":900,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.28,\"scaleY\":0.28,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"bouquetMeta\":{\"id\":\"65\",\"step\":\"ribbon\",\"name\":\"White Ribbon\",\"price\":25},\"src\":\"http:\\/\\/localhost\\/flowershop-main\\/assets\\/images\\/products\\/cutout_1795439607_6b043bf7b4792_download_1_cutout.png\",\"crossOrigin\":\"anonymous\",\"filters\":[]}],\"background\":\"#ffffff\"}','[{\"product_id\":52,\"name\":\"Vintage Floral Ceramic Planter Pot\",\"step\":\"container\",\"quantity\":1,\"price\":250,\"image\":\"1772380598_Vintage_Floral_Ceramic_Planter_Pot.webp\"},{\"product_id\":61,\"name\":\"Yellow Flower\",\"step\":\"flowers\",\"quantity\":2,\"price\":100,\"image\":\"1795438990_Cremon_Disbud_Yellow_-_Bulk_and_Wholesale_-_10_stems.jpg\"},{\"product_id\":62,\"name\":\"White Flower\",\"step\":\"flowers\",\"quantity\":2,\"price\":150,\"image\":\"cutout_1795439077_6b0439e5d0cf4________________________________PNG__________________________________PNG_______________________cutout.png\"},{\"product_id\":63,\"name\":\"Eucalyptus Branch\",\"step\":\"fillers\",\"quantity\":2,\"price\":10,\"image\":\"1795439454_Realistic_Eucalyptus_Branch_PNG___Greenery_Overlay.jpg\"},{\"product_id\":65,\"name\":\"White Ribbon\",\"step\":\"ribbon\",\"quantity\":1,\"price\":25,\"image\":\"1795439607_download_1.jpg\"}]',NULL,795.00,'2026-11-23 13:20:30','2026-11-23 13:21:40');
/*!40000 ALTER TABLE `custom_bouquet_designs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_feedback`
--

DROP TABLE IF EXISTS `customer_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `order_item_id` int NOT NULL,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text NOT NULL,
  `status` enum('pending','published','hidden') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT '0',
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_feedback_order_item_user` (`order_item_id`,`user_id`),
  KEY `fk_feedback_order` (`order_id`),
  KEY `fk_feedback_user` (`user_id`),
  KEY `idx_feedback_status` (`status`),
  KEY `idx_feedback_featured` (`is_featured`),
  KEY `idx_feedback_product` (`product_id`),
  CONSTRAINT `fk_feedback_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_feedback`
--

LOCK TABLES `customer_feedback` WRITE;
/*!40000 ALTER TABLE `customer_feedback` DISABLE KEYS */;
INSERT INTO `customer_feedback` VALUES (1,3,3,1,5,4,'ayos maganda ang serbisyo','published',1,'k','2025-11-28 05:02:03','2025-12-07 12:47:36'),(2,2,2,1,4,2,'goods naman','published',0,'','2025-11-28 05:43:06','2025-12-07 12:47:19'),(3,4,4,2,3,3,'quality service','published',1,'a','2025-11-28 06:04:31','2025-11-28 06:08:02'),(4,5,5,2,6,5,'goods na goods ang service','published',1,'adf','2025-11-28 11:35:02','2025-11-29 15:20:22'),(5,1,1,1,4,3,'asdfsz','pending',0,'s','2025-11-29 15:24:02','2025-12-07 12:46:38');
/*!40000 ALTER TABLE `customer_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customize_step_filters`
--

DROP TABLE IF EXISTS `customize_step_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customize_step_filters` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `step_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `step_slug` (`step_key`,`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customize_step_filters`
--

LOCK TABLES `customize_step_filters` WRITE;
/*!40000 ALTER TABLE `customize_step_filters` DISABLE KEYS */;
INSERT INTO `customize_step_filters` VALUES (1,'container','Pot','pot',1,'2026-02-17 03:02:48','2026-02-17 03:02:48'),(3,'container','Basket / Box','basket-box',3,'2026-02-17 03:02:48','2026-02-17 03:02:48'),(4,'flowers','Pink Flowers','pink-flowers',1,'2026-02-17 03:02:48','2026-03-01 16:06:48'),(8,'container','Bouquet Wrapper','bouquet-wrapper',4,'2026-03-01 14:41:30','2026-03-01 14:41:30'),(9,'flowers','Yellow Flowers','yellow-flowers',4,'2026-03-01 14:42:04','2026-03-01 14:42:04'),(10,'flowers','White Flowers','white-flowers',5,'2026-03-01 14:43:16','2026-03-01 16:11:24'),(11,'fillers','Greeny','greeny',1,'2026-03-01 16:18:28','2026-03-01 16:18:28'),(12,'fillers','Softy','softy',2,'2026-03-01 16:18:48','2026-03-01 16:18:48'),(13,'ribbon','White Ribbon Decors','white-ribbon-decors',1,'2026-03-01 16:29:13','2026-03-01 16:29:13'),(14,'ribbon','Red Ribbon Decors','red-ribbon-decors',2,'2026-03-01 16:29:30','2026-03-01 16:29:30');
/*!40000 ALTER TABLE `customize_step_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customize_steps`
--

DROP TABLE IF EXISTS `customize_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customize_steps` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `step_order` int unsigned NOT NULL DEFAULT '1',
  `is_optional` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customize_steps`
--

LOCK TABLES `customize_steps` WRITE;
/*!40000 ALTER TABLE `customize_steps` DISABLE KEYS */;
INSERT INTO `customize_steps` VALUES (1,'Container Type','container','Container','Choose the base that fits your look','basket,pot,box,planter,handle,ceramic,gift',1,0,'2026-02-17 02:40:21','2026-02-17 02:40:21'),(2,'Flower Type + Color','flowers','Flowers','Pick the hero blooms','chrysanthemum,tulip,ranunculus,pom,daisy,rose,bloom',2,0,'2026-02-17 02:40:21','2026-02-17 02:40:21'),(3,'Leaves / Fillers','fillers','Fillers','Layer in eucalyptus, ruscus, baby\'s breath','eucalyptus,ruscus,filler,foliage,green,baby',3,0,'2026-02-17 02:40:21','2026-02-17 02:40:21'),(4,'Ribbon (Optional)','ribbon','Ribbon','Wraps, ribbons, finishing touches','ribbon,wrapper,wrap,sheet,bow',4,1,'2026-02-17 02:40:21','2026-02-17 02:40:21');
/*!40000 ALTER TABLE `customize_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_campaign_logs`
--

DROP TABLE IF EXISTS `email_campaign_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_campaign_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int unsigned DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `segments` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_count` int unsigned NOT NULL DEFAULT '0',
  `fail_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_campaign_admin` (`admin_id`),
  KEY `idx_email_campaign_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_campaign_logs`
--

LOCK TABLES `email_campaign_logs` WRITE;
/*!40000 ALTER TABLE `email_campaign_logs` DISABLE KEYS */;
INSERT INTO `email_campaign_logs` VALUES (1,1,'Fresh blooms and new deals','customers,newsletter',8,0,'2026-03-19 04:13:50','2026-03-19 04:13:50');
/*!40000 ALTER TABLE `email_campaign_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flagged_messages`
--

DROP TABLE IF EXISTS `flagged_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flagged_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `source` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `detected_words` varchar(512) DEFAULT NULL,
  `review_state` varchar(32) NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_flagged_user` (`user_id`),
  KEY `idx_flagged_source` (`source`),
  KEY `idx_flagged_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flagged_messages`
--

LOCK TABLES `flagged_messages` WRITE;
/*!40000 ALTER TABLE `flagged_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `flagged_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_item_logs`
--

DROP TABLE IF EXISTS `inventory_item_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_item_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int unsigned NOT NULL,
  `change_quantity` decimal(12,2) NOT NULL,
  `change_type` enum('restock','usage','adjustment','waste','transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usage',
  `source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `performed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_item_logs_item` (`item_id`),
  KEY `idx_inventory_item_logs_created` (`created_at`),
  CONSTRAINT `fk_inventory_logs_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item_logs`
--

LOCK TABLES `inventory_item_logs` WRITE;
/*!40000 ALTER TABLE `inventory_item_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_item_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pc',
  `current_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reorder_point` decimal(12,2) NOT NULL DEFAULT '0.00',
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_inventory_item_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
INSERT INTO `inventory_items` VALUES (1,'Red Rose','f1','inventory/1774503087_Premium_Stems_of_Red_Roses.webp','Flowers','pc',100.00,5.00,50.00,'bulaklak',1,'2026-03-20 15:39:53','2026-03-26 05:31:27'),(4,'Sunflower','f1','inventory/1774503100_Bouquet_of_Fresh_Sunflowers.jpg','Flowers','pc',60.00,5.00,70.00,'sun',1,'2026-03-20 15:43:54','2026-03-26 05:31:40');
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_logs`
--

DROP TABLE IF EXISTS `inventory_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `change_quantity` int NOT NULL,
  `new_quantity` int NOT NULL,
  `reason` enum('purchase','restock','adjustment','return') NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_logs`
--

LOCK TABLES `inventory_logs` WRITE;
/*!40000 ALTER TABLE `inventory_logs` DISABLE KEYS */;
INSERT INTO `inventory_logs` VALUES (1,1,50,100,'restock','Initial stock','2025-11-10 15:16:45'),(2,1,-1,99,'purchase','Order #1','2025-11-28 04:25:21'),(3,1,-1,98,'purchase','Order #2','2025-11-28 04:38:12'),(4,1,-1,97,'purchase','Order #3','2025-11-28 04:47:09'),(5,2,50,100,'restock','Initial stock','2025-11-28 04:51:34'),(6,2,-1,99,'purchase','Order #4','2025-11-28 05:57:11'),(7,2,-1,98,'purchase','Order #5','2025-11-28 11:34:00'),(8,1,-1,96,'purchase','Order #6','2025-11-28 13:08:45'),(9,1,1,97,'return','Customer cancelled order #6','2025-11-28 13:32:50'),(10,2,-93,5,'adjustment','Stock edited via product details','2025-11-28 13:40:57'),(11,2,-1,4,'purchase','Order #7','2025-11-30 09:07:10'),(12,1,-1,96,'purchase','Order #8','2025-11-30 15:34:57'),(13,1,-1,95,'purchase','Order #9','2025-11-30 16:56:07'),(14,2,-1,3,'purchase','Order #9','2025-11-30 16:56:07'),(15,2,-1,2,'purchase','Order #10','2025-11-30 17:36:20'),(18,2,1,3,'return','Customer cancelled order #10','2025-11-30 17:44:21'),(19,2,-1,2,'purchase','Order #11','2025-11-30 17:46:44'),(20,2,1,3,'return','Customer cancelled order #11','2025-11-30 17:47:23'),(21,3,20,40,'restock','Initial stock','2025-12-04 00:31:25'),(24,3,-20,20,'adjustment','Stock edited via product details','2025-12-04 01:16:57'),(25,1,-75,20,'adjustment','Stock edited via product details','2025-12-04 01:17:05'),(27,6,10,20,'restock','Initial stock','2025-12-04 01:32:41'),(29,8,10,20,'restock','Initial stock','2025-12-04 02:15:44'),(30,9,5,10,'restock','Initial stock','2025-12-04 02:23:32'),(39,18,10,20,'restock','Initial stock','2025-12-04 12:01:57'),(40,19,15,30,'restock','Initial stock','2025-12-04 12:13:07'),(41,20,5,10,'restock','Initial stock','2025-12-04 12:22:09'),(42,21,2,4,'restock','Initial stock','2025-12-04 12:32:19'),(43,22,5,10,'restock','Initial stock','2025-12-05 00:18:40'),(44,23,5,10,'restock','Initial stock','2025-12-05 00:23:00'),(45,24,7,14,'restock','Initial stock','2025-12-05 00:27:36'),(46,25,5,10,'restock','Initial stock','2025-12-05 00:33:20'),(47,26,5,10,'restock','Initial stock','2025-12-05 00:41:19'),(48,27,80,160,'restock','Initial stock','2025-12-05 00:58:48'),(49,28,50,100,'restock','Initial stock','2025-12-05 01:03:31'),(50,29,50,100,'restock','Initial stock','2025-12-05 01:18:05'),(51,30,40,80,'restock','Initial stock','2025-12-05 01:24:31'),(52,31,10,20,'restock','Initial stock','2025-12-05 01:30:58'),(53,32,10,20,'restock','Initial stock','2025-12-05 01:38:44'),(54,33,50,100,'restock','Initial stock','2025-12-06 00:50:12'),(55,34,50,100,'restock','Initial stock','2025-12-06 00:53:56'),(62,41,10,20,'restock','Initial stock','2025-12-06 03:51:03'),(63,42,12,24,'restock','Initial stock','2025-12-06 03:54:13'),(64,43,12,24,'restock','Initial stock','2025-12-06 04:01:00'),(65,44,10,20,'restock','Initial stock','2025-12-06 04:05:47'),(66,45,10,20,'restock','Initial stock','2025-12-06 04:08:47'),(67,46,10,20,'restock','Initial stock','2025-12-06 04:16:30'),(68,47,10,20,'restock','Initial stock','2025-12-06 04:21:18'),(69,48,30,60,'restock','Initial stock','2025-12-06 04:27:27'),(70,2,-3,0,'adjustment','Stock edited via product details','2025-12-06 04:45:29'),(71,21,-1,3,'purchase','Order #12','2025-12-06 16:40:11'),(72,34,-1,99,'purchase','Order #13','2026-01-25 13:35:39'),(74,48,-2,58,'purchase','Order #14','2026-02-17 06:11:54'),(75,50,2,4,'restock','Initial stock','2026-02-17 06:14:28'),(76,22,-1,9,'purchase','Order #15','2026-03-01 12:49:16'),(77,51,5,10,'restock','Initial stock','2026-03-01 15:53:37'),(78,52,3,6,'restock','Initial stock','2026-03-01 15:56:38'),(79,53,10,20,'restock','Initial stock','2026-03-01 15:58:42'),(80,54,50,100,'restock','Initial stock','2026-03-01 16:05:45'),(81,55,100,200,'restock','Initial stock','2026-03-01 16:10:00'),(82,56,100,200,'restock','Initial stock','2026-03-01 16:13:33'),(83,57,300,600,'restock','Initial stock','2026-03-01 16:23:16'),(84,58,250,500,'restock','Initial stock','2026-03-01 16:26:06'),(85,59,50,100,'restock','Initial stock','2026-03-01 16:32:29'),(86,60,50,100,'restock','Initial stock','2026-03-01 16:34:37'),(87,53,-1,19,'purchase','Order #16','2026-03-18 14:40:36'),(88,54,-1,99,'purchase','Order #16','2026-03-18 14:40:36'),(89,55,-1,199,'purchase','Order #16','2026-03-18 14:40:36'),(90,57,-1,599,'purchase','Order #16','2026-03-18 14:40:36'),(91,58,-1,499,'purchase','Order #16','2026-03-18 14:40:36'),(92,59,-1,99,'purchase','Order #16','2026-03-18 14:40:36'),(93,52,-1,5,'purchase','Order #17','2026-03-18 15:26:56'),(94,54,-1,98,'purchase','Order #17','2026-03-18 15:26:56'),(95,57,-1,598,'purchase','Order #17','2026-03-18 15:26:56'),(96,59,-1,98,'purchase','Order #17','2026-03-18 15:26:56'),(97,52,-1,4,'purchase','Order #18','2026-03-18 16:09:46'),(98,55,-1,198,'purchase','Order #18','2026-03-18 16:09:46'),(99,57,-1,597,'purchase','Order #18','2026-03-18 16:09:46'),(100,59,-1,97,'purchase','Order #18','2026-03-18 16:09:46'),(101,59,-3,94,'adjustment','','2026-03-20 13:23:16');
/*!40000 ALTER TABLE `inventory_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscriptions`
--

DROP TABLE IF EXISTS `newsletter_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `subscribed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscriptions`
--

LOCK TABLES `newsletter_subscriptions` WRITE;
/*!40000 ALTER TABLE `newsletter_subscriptions` DISABLE KEYS */;
INSERT INTO `newsletter_subscriptions` VALUES (1,'glenfordmacasa@gmail.com',1,'2025-11-10 13:21:59'),(3,'vanvan.luci@gmail.com',1,'2025-11-26 14:16:25'),(4,'shem.cuevillas@gmail.com',1,'2025-11-26 16:42:08'),(13,'nathaniel@gmail.com',1,'2025-11-30 14:11:10');
/*!40000 ALTER TABLE `newsletter_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','promotion','system','support') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT '0',
  `related_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (7,2,'New message from support','Our admin replied to your inquiry: \"yow\"','support',1,3,'2025-11-30 16:13:49'),(23,7,'New message from support','Our admin replied to your inquiry: \"heyy\"','support',1,5,'2025-12-07 12:14:00'),(46,1,'New message from Van Luci','hey','support',1,4,'2026-03-26 11:54:58'),(47,4,'Order #19 Pending','Order placed successfully','order',1,19,'2026-03-26 11:55:26'),(48,1,'New order placed','Order #19 needs review.','order',1,19,'2026-03-26 11:55:26'),(49,4,'Order #19 Cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','order',1,19,'2026-03-27 08:00:14'),(50,4,'Order #20 Pending','Order placed successfully','order',1,20,'2026-03-27 08:08:59'),(51,1,'New order placed','Order #20 needs review.','order',1,20,'2026-03-27 08:08:59'),(52,4,'Order #20 Confirmed','Order confirmed and payment verified','order',1,20,'2026-03-27 08:13:36'),(53,4,'Order #20 Delivered','Order has been delivered successfully','order',1,20,'2026-03-27 08:13:55'),(54,6,'Order #21 Pending','Order placed successfully','order',1,21,'2026-03-29 16:55:33'),(55,1,'New order placed','Order #21 needs review.','order',0,21,'2026-03-29 16:55:33'),(56,6,'Order #21 Cancelled','Order cancelled by customer','order',1,21,'2026-03-29 17:08:51'),(57,6,'Order #26 Pending','Order placed successfully','order',1,26,'2026-03-29 17:52:24'),(58,1,'New order placed','Order #26 needs review.','order',0,26,'2026-03-29 17:52:24'),(59,6,'Order #26 Confirmed','Order confirmed and payment verified','order',1,26,'2026-03-29 17:52:44'),(60,6,'Order #26 Delivered','Order has been delivered successfully','order',1,26,'2026-03-29 17:52:59'),(61,3,'Order #27 Pending','Order placed successfully','order',0,27,'2026-11-17 02:22:18'),(62,1,'New order placed','Order #27 needs review.','order',0,27,'2026-11-17 02:22:18'),(63,3,'Order #27 Cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','order',0,27,'2026-11-23 12:03:16'),(64,8,'Order #28 Pending','Order placed successfully','order',0,28,'2026-11-23 13:21:40'),(65,1,'New order placed','Order #28 needs review.','order',0,28,'2026-11-23 13:21:40'),(66,8,'Order #28 Processing','Order is being processed and prepared for shipment','order',0,28,'2026-11-23 13:23:53');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_inventory_usage`
--

DROP TABLE IF EXISTS `order_inventory_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_inventory_usage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `item_id` int unsigned NOT NULL,
  `quantity_used` decimal(12,4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_order_item` (`order_id`,`item_id`),
  KEY `idx_usage_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_inventory_usage`
--

LOCK TABLES `order_inventory_usage` WRITE;
/*!40000 ALTER TABLE `order_inventory_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_inventory_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,1,1,0.12),(2,2,1,1,0.12),(3,3,1,1,0.12),(4,4,2,1,100.00),(5,5,2,1,100.00),(6,6,1,1,0.12),(7,7,2,1,100.00),(8,8,1,1,0.12),(9,9,1,1,0.12),(10,9,2,1,100.00),(11,10,2,1,100.00),(12,11,2,1,100.00),(13,12,21,1,3000.00),(14,13,34,1,105.00),(15,14,48,2,1560.00),(16,15,22,1,4000.00),(17,16,53,1,25.00),(18,16,54,1,75.00),(19,16,55,1,50.00),(20,16,57,1,15.00),(21,16,58,1,80.00),(22,16,59,1,25.00),(23,17,52,1,250.00),(24,17,54,1,75.00),(25,17,57,1,15.00),(26,17,59,1,30.00),(27,18,52,1,250.00),(28,18,55,1,50.00),(29,18,57,1,15.00),(30,18,59,1,25.00),(31,19,22,1,4000.00),(32,20,52,1,250.00),(33,20,54,1,75.00),(34,20,55,4,50.00),(35,20,57,1,15.00),(36,20,58,1,80.00),(37,20,60,1,25.00),(38,21,48,1,1560.00),(43,26,23,1,2000.00),(44,27,22,1,4000.00),(45,28,52,1,250.00),(46,28,61,2,110.00),(47,28,62,2,150.00),(48,28,63,2,10.00),(49,28,65,1,25.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_tracking`
--

DROP TABLE IF EXISTS `order_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_tracking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','completed') NOT NULL DEFAULT 'pending',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_tracking`
--

LOCK TABLES `order_tracking` WRITE;
/*!40000 ALTER TABLE `order_tracking` DISABLE KEYS */;
INSERT INTO `order_tracking` VALUES (1,1,'pending','Order placed successfully','2025-11-28 04:25:21'),(2,1,'shipped','Order has been shipped with tracking: 333','2025-11-28 04:30:26'),(3,2,'pending','Order placed successfully','2025-11-28 04:38:12'),(4,3,'pending','Order placed successfully','2025-11-28 04:47:09'),(5,3,'delivered','Order has been delivered successfully','2025-11-28 05:01:13'),(6,2,'delivered','Order has been delivered successfully','2025-11-28 05:42:29'),(7,4,'pending','Order placed successfully','2025-11-28 05:57:11'),(8,4,'shipped','Order has been shipped with tracking: 777','2025-11-28 05:57:49'),(9,4,'delivered','Order has been delivered successfully','2025-11-28 05:58:43'),(10,5,'pending','Order placed successfully','2025-11-28 11:34:00'),(11,5,'delivered','Order has been delivered successfully','2025-11-28 11:34:29'),(12,6,'pending','Order placed successfully','2025-11-28 13:08:45'),(13,6,'cancelled','Order cancelled by customer','2025-11-28 13:32:50'),(14,6,'cancelled','Order has been cancelled','2025-11-28 13:35:14'),(15,1,'delivered','Order has been delivered successfully','2025-11-29 15:23:11'),(16,7,'pending','Order placed successfully','2025-11-30 09:07:10'),(17,7,'processing','Order is being processed and prepared for shipment','2025-11-30 09:08:17'),(18,8,'pending','Order placed successfully','2025-11-30 15:34:57'),(19,8,'processing','Order is being processed and prepared for shipment','2025-11-30 16:02:13'),(20,9,'pending','Order placed successfully','2025-11-30 16:56:07'),(21,10,'pending','Order placed successfully','2025-11-30 17:36:20'),(22,10,'cancelled','Order cancelled by customer','2025-11-30 17:44:21'),(23,11,'pending','Order placed successfully','2025-11-30 17:46:44'),(24,11,'cancelled','Order cancelled by customer','2025-11-30 17:47:23'),(25,9,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2025-12-03 23:01:54'),(26,12,'pending','Order placed successfully','2025-12-06 16:40:11'),(27,12,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2025-12-07 07:55:35'),(28,13,'pending','Order placed successfully','2026-01-25 13:35:39'),(29,13,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2026-01-26 16:57:00'),(30,14,'pending','Order placed successfully','2026-02-17 06:11:54'),(31,14,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2026-02-18 12:40:03'),(32,15,'pending','Order placed successfully','2026-03-01 12:49:16'),(33,15,'confirmed','Order confirmed and payment verified','2026-03-01 13:10:08'),(34,15,'processing','Order is being processed and prepared for shipment','2026-03-17 06:42:45'),(35,15,'shipped','Order has been shipped','2026-03-17 06:42:55'),(36,16,'pending','Order placed successfully','2026-03-18 14:40:36'),(37,17,'pending','Order placed successfully','2026-03-18 15:26:56'),(38,17,'confirmed','Order confirmed and payment verified','2026-03-18 16:02:36'),(39,18,'pending','Order placed successfully','2026-03-18 16:09:46'),(40,16,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2026-03-19 02:49:31'),(41,18,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2026-03-19 04:13:02'),(42,19,'pending','Order placed successfully','2026-03-26 11:55:26'),(43,19,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2026-03-27 08:00:14'),(44,20,'pending','Order placed successfully','2026-03-27 08:08:59'),(45,20,'confirmed','Order confirmed and payment verified','2026-03-27 08:13:36'),(46,20,'delivered','Order has been delivered successfully','2026-03-27 08:13:55'),(47,21,'pending','Order placed successfully','2026-03-29 16:55:33'),(48,21,'cancelled','Order cancelled by customer','2026-03-29 17:08:51'),(49,26,'pending','Order placed successfully','2026-03-29 17:52:24'),(50,26,'confirmed','Order confirmed and payment verified','2026-03-29 17:52:44'),(51,26,'delivered','Order has been delivered successfully','2026-03-29 17:52:59'),(52,27,'pending','Order placed successfully','2026-11-17 02:22:18'),(53,27,'cancelled','Order automatically cancelled after pending for more than 12 hours without admin confirmation.','2026-11-23 12:03:16'),(54,28,'pending','Order placed successfully','2026-11-23 13:21:40'),(55,28,'processing','Order is being processed and prepared for shipment','2026-11-23 13:23:53');
/*!40000 ALTER TABLE `order_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_distance_km` decimal(10,2) DEFAULT NULL,
  `shipping_distance_miles` decimal(10,2) DEFAULT NULL,
  `shipping_details` text,
  `recipient_is_alternate` tinyint(1) NOT NULL DEFAULT '0',
  `recipient_name` varchar(255) DEFAULT NULL,
  `recipient_contact` varchar(100) DEFAULT NULL,
  `recipient_address` text,
  `recipient_notes` text,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT NULL,
  `shipping_amount` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `coupon_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reward_discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `reward_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reward_points_earned` int unsigned NOT NULL DEFAULT '0',
  `reward_points_spent` int unsigned NOT NULL DEFAULT '0',
  `status` enum('pending','processing','shipped','delivered','cancelled','confirmed','completed') NOT NULL DEFAULT 'pending',
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `shipping_address` text,
  `customer_notes` text,
  `payment_method` varchar(50) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,4,10.13,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'delivered',0,'Brgy.Labasan, Bongabong, Oriental Mindoro, Oriental Mindoro 5211, Philippines',NULL,'credit_card','333',NULL,'2025-11-28 04:25:21'),(2,4,10.13,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'delivered',0,'Brgy.Labasan, Bongabong, Oriental Mindoro, Oriental Mindoro 5211, Philippines',NULL,'credit_card','666',NULL,'2025-11-28 04:38:12'),(3,5,10.13,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'delivered',0,'Brgy.Tambong, Gloria, Oriental Mindoro, Oriental Mindoro 5209, Philippines',NULL,'paypal','545',NULL,'2025-11-28 04:47:09'),(4,3,120.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'delivered',0,'Zone 1, Bulalacao, Oriental Mindoro 5214, Philippines',NULL,'cash_on_delivery','777',NULL,'2025-11-28 05:57:11'),(5,6,120.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'delivered',0,'Guimbonan, Gloria, Oriental Mindoro 5209, Philippines',NULL,'cash_on_delivery','232',NULL,'2025-11-28 11:34:00'),(6,4,10.13,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Brgy. Zone 4, pinamalayan, Oriental Mindoro 5208, Philippines',NULL,'credit_card','',NULL,'2025-11-28 13:08:45'),(7,4,120.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'processing',0,'Brgy. Zone 4, pinamalayan, Oriental Mindoro 5208, Philippines',NULL,'credit_card','',NULL,'2025-11-30 09:07:10'),(8,6,10.13,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'processing',0,'Guimbonan, Gloria, Oriental Mindoro 5209, Philippines',NULL,'cash_on_delivery','',NULL,'2025-11-30 15:34:57'),(9,4,109.12,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'WELCOME10',10.01,0.00,0.00,0,0,'cancelled',0,'Brgy.Labasan, Bongabong, Oriental Mindoro, Oriental Mindoro 5211, Philippines',NULL,'credit_card',NULL,NULL,'2025-11-30 16:56:07'),(10,6,120.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Guimbonan, Gloria, Oriental Mindoro 5209, Philippines',NULL,'credit_card',NULL,NULL,'2025-11-30 17:36:20'),(11,6,109.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'WELCOME1',10.00,0.00,0.00,0,0,'cancelled',0,'Guimbonan, Gloria, Oriental Mindoro 5209, Philippines',NULL,'credit_card',NULL,NULL,'2025-11-30 17:46:44'),(12,6,3310.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Guimbonan, Gloria, Oriental Mindoro 5209, Philippines',NULL,'paypal',NULL,NULL,'2025-12-06 16:40:11'),(13,6,115.50,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Store Pickup - Bloomify Flower Studio, Raymundo Avenue, Gloria, Oriental Mindoro (No delivery required)',NULL,'credit_card',NULL,NULL,'2026-01-25 13:35:39'),(14,6,3442.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Guimbonan, Gloria, Oriental Mindoro 5209, Philippines',NULL,'credit_card',NULL,NULL,'2026-02-17 06:11:54'),(15,4,4410.00,0.00,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'shipped',0,'Brgy.Labasan, Bongabong, Oriental Mindoro, Oriental Mindoro 5211, Philippines',NULL,'cash_on_delivery','',NULL,'2026-03-01 12:49:16'),(16,6,347.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"recipient\":{\"mode\":\"alternate\",\"name\":\"Benedict Lacay\",\"contact\":\"09123456789\",\"address\":\"Tambong, Gloria, Oriental Mindoro, 5209 Philippines\",\"notes\":\"Heller\"}}',1,'Benedict Lacay','09123456789','Tambong, Gloria, Oriental Mindoro, 5209 Philippines','Heller',NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines',NULL,'credit_card',NULL,NULL,'2026-03-18 14:40:35'),(17,6,457.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"recipient\":{\"mode\":\"self\"},\"bundle_status\":{\"has_custom_bundle\":true,\"finalized\":true,\"finalized_at\":\"2026-03-18T16:02:30+00:00\",\"final_total\":370}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'confirmed',0,'Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines',NULL,'credit_card','',NULL,'2026-03-18 15:26:56'),(18,6,424.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"bundle_status\":{\"has_custom_bundle\":true,\"finalized\":false,\"finalized_at\":null,\"final_total\":null},\"recipient\":{\"mode\":\"self\"}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',1,'Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines',NULL,'credit_card',NULL,NULL,'2026-03-18 16:09:46'),(19,4,4050.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Brgy.Labasan, Bongabong, Oriental Mindoro, Oriental Mindoro, 5211 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"bundle_status\":{\"has_custom_bundle\":false,\"finalized\":false,\"finalized_at\":null,\"final_total\":null},\"recipient\":{\"mode\":\"self\"}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Brgy.Labasan, Bongabong, Oriental Mindoro, Oriental Mindoro, 5211 Philippines',NULL,'credit_card',NULL,NULL,'2026-03-26 11:55:26'),(20,4,695.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Brgy. Zone 4, pinamalayan, Oriental Mindoro, 5208 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"bundle_status\":{\"has_custom_bundle\":true,\"finalized\":false,\"finalized_at\":null,\"final_total\":null},\"recipient\":{\"mode\":\"self\"}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'delivered',0,'Brgy. Zone 4, pinamalayan, Oriental Mindoro, 5208 Philippines',NULL,'credit_card','',NULL,'2026-03-27 08:08:59'),(21,6,1610.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"bundle_status\":{\"has_custom_bundle\":false,\"finalized\":false,\"finalized_at\":null,\"final_total\":null},\"recipient\":{\"mode\":\"self\"}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'cancelled',0,'Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines',NULL,'credit_card',NULL,NULL,'2026-03-29 16:55:33'),(26,6,2050.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"bundle_status\":{\"has_custom_bundle\":false,\"finalized\":false,\"finalized_at\":null,\"final_total\":null},\"recipient\":{\"mode\":\"self\"}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,0,'delivered',0,'Guimbonan, Gloria, Oriental Mindoro, 5209 Philippines',NULL,'credit_card','678',NULL,'2026-03-29 17:52:24'),(27,3,4050.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Zone 1, Bulalacao, Oriental Mindoro, 5214 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"bundle_status\":{\"has_custom_bundle\":false,\"finalized\":false,\"finalized_at\":null,\"final_total\":null},\"recipient\":{\"mode\":\"self\"}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,10,0,'cancelled',0,'Zone 1, Bulalacao, Oriental Mindoro, 5214 Philippines',NULL,'credit_card',NULL,NULL,'2026-11-17 02:22:17'),(28,8,865.00,50.00,NULL,NULL,'{\"method\":\"delivery\",\"address\":\"Barangay Zone IV, Pinamalyan, Oriental Mindoro, Pinamalayan, Mindoro Oriental, 5208 Philippines\",\"distance_text\":\"Outside Roxas (Oriental Mindoro)\",\"duration_text\":\"Zone-based rate\",\"messages\":[\"Applied Outside Roxas (Oriental Mindoro) shipping fee.\"],\"unit\":\"zone\",\"rate_used\":\"Outside Roxas (Oriental Mindoro)\",\"base_fee\":null,\"minimum_fee\":null,\"bundle_status\":{\"has_custom_bundle\":true,\"finalized\":true,\"finalized_at\":\"2026-11-23T21:23:43+08:00\",\"final_total\":815},\"recipient\":{\"mode\":\"self\"}}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,10,0,'processing',0,'Barangay Zone IV, Pinamalyan, Oriental Mindoro, Pinamalayan, Mindoro Oriental, 5208 Philippines',NULL,'credit_card','657869',NULL,'2026-11-23 13:21:40');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_inventory_components`
--

DROP TABLE IF EXISTS `product_inventory_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_inventory_components` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `item_id` int unsigned NOT NULL,
  `quantity_per_unit` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_product_component` (`product_id`,`item_id`),
  KEY `idx_component_item` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_inventory_components`
--

LOCK TABLES `product_inventory_components` WRITE;
/*!40000 ALTER TABLE `product_inventory_components` DISABLE KEYS */;
INSERT INTO `product_inventory_components` VALUES (2,23,1,1.0000,'2026-03-26 11:53:49','2026-03-26 11:53:49');
/*!40000 ALTER TABLE `product_inventory_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `rating` int NOT NULL,
  `review_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
INSERT INTO `product_reviews` VALUES (1,1,4,5,'okay nmn maganda','2025-11-28 04:29:13');
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `tags` varchar(255) DEFAULT NULL,
  `customize_step` tinyint unsigned DEFAULT NULL,
  `customize_filter_id` int unsigned DEFAULT NULL,
  `customize_archived` tinyint(1) NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `canvas_image` varchar(255) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  `availability_status` varchar(20) NOT NULL DEFAULT 'available',
  `customization_stage` varchar(50) DEFAULT NULL,
  `customization_tags` varchar(255) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `low_stock_threshold` int NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Red Bouquet of Rose','A rose is a beautiful, fragrant flower symbolizing love, passion, and elegance.','bouquet, rose, anniversary',NULL,NULL,0,900.00,'1764894918_red_Bouquet_of_Rose.jpg',NULL,13,50,'available',NULL,NULL,0.08,'50×30×20',0,'2025-11-10 15:16:45',5),(2,' Yellow Bouquet of Gerbera Daisies','A bright and cheerful bouquet featuring vibrant yellow gerbera daisies with white fillers, designed to bring warmth and positivity to any occasion.','yellow, bouquet, gerbera daisies,  bright theme,  cheerful flowers  warm tones',NULL,NULL,0,100.00,'1764849333_Yellow_Bouquet_of_Gerbera_Daisies.jpg',NULL,13,0,'available',NULL,NULL,0.65,'35x25x45 cm',0,'2025-11-28 04:51:34',5),(3,'Sympathy Serenity Bouquet','A dignified and serene all-white floral arrangement, designed to offer comfort and express heartfelt condolences.',' sympathy, funeral, condolence, white flowers, bouquet, elegant, serene, remembrance',NULL,NULL,0,1500.00,'1764808285_Sympathy_Serenity_Bouquet.jpg',NULL,16,20,'available',NULL,NULL,2.50,'40x25x30 cm',0,'2025-12-04 00:31:25',5),(6,'Sympathy Rectangular Memorial Spray','A formal, rectangular-shaped floral arrangement on a foam base, featuring a prominent central area for a personalized dedication card, commonly used as a dignified funeral tribute.','sympathy, funeral, floral spray, rectangular, memorial, condolence, funeral tribute',NULL,NULL,0,2500.00,'1764811961_Sympathy_Rectangular_Memorial_Spray.jpg',NULL,16,20,'available',NULL,NULL,3.80,'80x40x20 cm (Approx.)',1,'2025-12-04 01:32:41',5),(8,'Colorburst Premium Arrangement','A vibrant and luxurious mixed floral arrangement featuring a curated selection of seasonal blooms in bold colors, designed as a standout gift for celebratory occasions.','premium, mixed bouquet, colorburst, seasonal flowers, luxury, gift, celebration',NULL,NULL,0,1500.00,'1764814544_Colorburst_Premium_Arrangement.jpg',NULL,14,20,'available',NULL,NULL,1.00,'45x45x35 cm (Approx.)',1,'2025-12-04 02:15:44',5),(9,'Blossom Symphony Arrangement','A harmonious and artistic mixed floral arrangement that combines seasonal blooms in complementary colors and textures, creating a symphony of beauty perfect for home decor or as a heartfelt gift.','mixed bouquet, seasonal flowers, centerpiece, home decor, gift, blossom symphony, joyful',NULL,NULL,0,1700.00,'1764815012_Blossom_Symphony_Arrangement.jpg',NULL,14,10,'available',NULL,NULL,2.50,'	50x50x40 cm (Approx.)',0,'2025-12-04 02:23:32',5),(18,'Purple Bouquet of  Ranunculus','A striking and elegant bouquet featuring deep purple ranunculus blooms with soft green buds, designed to create a bold and luxurious floral presentation.','luxury florals, elegant theme',NULL,NULL,0,1500.00,'1764849717_Purple__Bouquet_of_Ranunculus.jpg',NULL,13,20,'available',NULL,NULL,0.70,'35x25x45 cm',1,'2025-12-04 12:01:57',5),(19,'Fresh Sunflowers','A bright and uplifting bouquet featuring vibrant yellow sunflowers with rich dark centers, designed to bring warmth and cheer to any occasion.','warm tones, bright flowers',NULL,NULL,0,800.00,'1764850387_Bouquet_of_Fresh_Sunflowers.jpg',NULL,15,30,'available',NULL,NULL,0.80,'35x25x45 cm',0,'2025-12-04 12:13:07',5),(20,'Bouquet of Mixed Premium Flowers','A vibrant and elegant bouquet featuring lilies, roses, chrysanthemums, carnations, and fillers, designed to create a colorful and expressive floral arrangement for any occasion.','colorful arrangement, premium flowers',NULL,NULL,0,1000.00,'1764850929_Bouquet_of_Mixed_Premium_Flowers.jpg',NULL,14,10,'available',NULL,NULL,0.90,'40x30x55 cm',0,'2025-12-04 12:22:09',5),(21,'Basket of Mixed Premium Flowers','A vibrant and lush basket arrangement featuring gerberas, lilies, chrysanthemums, carnations, and assorted foliage, designed to create a striking and joyful centerpiece for any celebration.','celebration blooms, colorful arrangement,mixed basket',NULL,NULL,0,3000.00,'1764851539_Basket_of_Mixed_Premium_Flowers.jpg',NULL,12,3,'available',NULL,NULL,2.50,'50x40x45 cm',1,'2025-12-04 12:32:19',5),(22,'Grand Arrangement of Mixed Premium Flowers','A luxurious and artistic arrangement featuring lilies, orchids, chrysanthemums, carnations, and lush foliage, designed to create an elegant and impressive display for special occasions.','luxury centerpiece, elegant display, grand arrangement',NULL,NULL,0,4000.00,'1764893920_Grand_Arrangement_of_Mixed_Premium_Flowers.jpg',NULL,14,9,'available',NULL,NULL,4.00,'70x50x80 cm',0,'2025-12-05 00:18:40',5),(23,'Bouquet of Premium Red Roses for Anniversary','A classic and elegant bouquet featuring lush premium red roses surrounded by delicate baby\'s breath, designed to express deep love and timeless romance.','love flowers, romantic bouquet, classic arrangement',NULL,NULL,0,2000.00,'1764894180_Bouquet_of_Premium_Red_Roses.jpg',NULL,12,100,'available',NULL,NULL,1.20,'40x30x50 cm',0,'2025-12-05 00:23:00',5),(24,'Bouquet of Mixed Pastel Flowers','A colorful and charming bouquet featuring roses, carnations, chrysanthemums, and fillers, designed to create a soft and joyful floral arrangement for any occasion.','mixed flower, cheerful arrangement, soft colors',NULL,NULL,0,1650.00,'1764894456_Bouquet_of_Mixed_Pastel_Flowers.jpg',NULL,12,14,'available',NULL,NULL,0.95,'40x30x50 cm',0,'2025-12-05 00:27:36',5),(25,'Bouquet of Pink Premium Flowers','A luxurious and romantic bouquet featuring pink roses, lilies, and chrysanthemums with elegant fillers, designed to create a soft and sophisticated floral presentation.','premium flowers, romantic arrangement, soft tones',NULL,NULL,0,1500.00,'1764894800_Bouquet_of_Pink_Premium_Flowers.jpg',NULL,12,10,'available',NULL,NULL,1.10,'45x35x55 cm',0,'2025-12-05 00:33:20',5),(26,'Pink Bouquet of Lisianthus and Roses','A lush and romantic bouquet featuring pink lisianthus, pink roses, and elegant fillers, designed to create a soft and graceful floral presentation.','romantic flowers, elegant arrangement, soft tones',NULL,NULL,0,1800.00,'1764895279_Pink_Bouquet_of_Lisianthus_and_Roses.jpg',NULL,13,10,'available',NULL,NULL,1.00,'45x35x55 ',0,'2025-12-05 00:41:19',5),(27,'Mixed Tulips','A cheerful and elegant featuring assorted colorful tulips, designed to bring freshness and vibrant beauty to any occasion.','spring bloom, fresh arrangement',NULL,NULL,0,110.00,'1764896328_Mixed_Tulips.jpg',NULL,15,160,'available',NULL,NULL,0.50,'30x20x40 cm',0,'2025-12-05 00:58:48',5),(28,' Premium Stems of Red Roses','A classic bundle featuring long-stemmed premium red roses with lush green foliage, designed for elegant arrangements and timeless romantic gestures.','red rose stems, long-stem roses',NULL,NULL,0,100.00,'1764896611_Premium_Stems_of_Red_Roses.webp',NULL,15,100,'available',NULL,NULL,0.80,'70x25x15 cm',0,'2025-12-05 01:03:31',5),(29,'Orange Stems of Ranunculus','A vibrant bundle featuring lush orange ranunculus blooms with delicate layered petals, designed to add warmth and elegance to any floral arrangement.','orange ranunculus, bright stems, elegant blooms',NULL,NULL,0,99.00,'1764897485_Orange_Stemss_of_Ranunculus.webp',NULL,15,100,'available',NULL,NULL,0.45,'40x20x35 cm  Weight',0,'2025-12-05 01:18:05',5),(30,'Bridal Bouquet of White Roses','A classic and elegant bridal bouquet featuring premium white roses surrounded by delicate baby\'s breath, designed to create a timeless and graceful wedding look.','wedding flowers, elegant arrangement, classic bridal style',NULL,NULL,0,3000.00,'1764897871_Bridal_Bouquet_of_White_Roses.jpg',NULL,16,80,'available',NULL,NULL,0.60,'25x25x30 cm',0,'2025-12-05 01:24:31',5),(31,'Luxury purple–pink Birthday Bouquet','A luxurious and vibrant bouquet featuring pink lilies, roses, carnations, and purple fillers, designed to create an elegant and eye-catching birthday floral presentation.','birthday bouquet, pink-purple flowers, premium arrangement',NULL,NULL,0,1900.00,'1764898258_Luxury_purple___pink_Birthday_Bouquet.jpg',NULL,16,20,'available',NULL,NULL,1.30,'45x35x55 cm',0,'2025-12-05 01:30:58',5),(32,'Graduation Bouquet of Mixed Flowers with Money Fan','A festive and meaningful graduation bouquet featuring vibrant mixed roses, fillers, and a grand money fan, designed to celebrate achievement with beauty and reward.','',NULL,NULL,0,3000.00,'1764898752_Graduation_Bouquet_of_Mixed_Flowers_with_Money_Fan.jpg',NULL,16,20,'available',NULL,NULL,1.40,'45x35x60 ',0,'2025-12-05 01:38:44',5),(33,'Golden Premium Yellow Roses','A luxurious box of vibrant yellow roses symbolizing joy, appreciation, and warm heartfelt wishes.','thank you flowers, luxury roses, yellow bouquet',NULL,NULL,0,110.00,'1764982211_Golden_Premium_Yellow_Roses.jpg',NULL,15,100,'available',NULL,NULL,0.45,'25x20x10',0,'2025-12-06 00:50:11',5),(34,'Elegant Premium Peach Rose','A refined box of soft peach roses symbolizing gratitude, elegance, and gentle affection—perfect for any heartfelt occasion.','elegant roses, fresh flowers, romantic gift, thank you flowers',NULL,NULL,0,105.00,'1764982436_Elegant_Premium_Peach_Rose.jpg',NULL,15,99,'available',NULL,NULL,0.45,'25x20x10',0,'2025-12-06 00:53:56',5),(41,'Rustic Elegance Dried Wedding Bouquet ','A timeless rustic wedding bouquet blending preserved white roses, dried wheat, sola wood blooms, and soft neutral foliage for a natural, earthy, and romantic bridal look.','boho wedding, sola wood flowers, dried wheat bouquet, neutral wedding palette, rustic chic flowers, bridal bouquet dried',NULL,NULL,0,1600.00,'1764993062_Rustic_Elegance_Dried.png',NULL,16,20,'available',NULL,NULL,0.40,'30 cm x 25 cm x 45 cm',0,'2025-12-06 03:51:02',5),(42,'Blush & White Birthday Rose Bouquet','A charming pastel bouquet featuring pink and white roses, baby’s breath, and elegant white anthuriums—perfect for making any birthday extra special.','flowers, birthday gift flowers, elegant bouquet, celebration flowers, rose bouquet',NULL,NULL,0,1850.00,'1764993253_Blush___White_Birthday_Rose_Bouquet.jpg',NULL,16,24,'available',NULL,NULL,0.55,'40 cm x 35 cm x 55 cm',0,'2025-12-06 03:54:13',5),(43,'Golden Gift Bloom Money Bouquet','money bouquet, gift bouquet, yellow roses, yellow carnations, chrysanthemum bouquet, cash bouquet, celebration gift,','money bouquet, gift bouquet, yellow roses, yellow carnations, chrysanthemum bouquet, cash bouquet, celebration gift, premium bouquet, surprise bouquet, birthday gift flowers',NULL,NULL,0,2500.00,'1764993660_Golden_Gift_Bloom_Money_Bouquet.jpg',NULL,16,24,'available',NULL,NULL,0.75,'45 cm x 40 cm x 60 cm',0,'2025-12-06 04:01:00',5),(44,'Eternal Love Premium Anniversary  Red Rose Bouquet','A luxurious anniversary bouquet showcasing rich red roses, elegant foliage, and gold-accented black wrapping—crafted to express deep love and timeless devotion.','anniversary bouquet, red roses, premium roses, romantic bouquet,',NULL,NULL,0,2000.00,'1764993947_Eternal_Love_Premium_Red_Rose_Bouquet.jpg',NULL,16,20,'available',NULL,NULL,0.85,'50 cm x 45 cm x 60 cm',0,'2025-12-06 04:05:47',5),(45,'Sweet Romance Pink & Red Rose Bouquet','A romantic blend of pink and deep red roses paired with baby’s breath and lush greens—perfect for expressing love this Valentine’s Day.','valentines bouquet, pink roses, red roses, romantic flowers',NULL,NULL,0,1350.00,'1764994127_Sweet_Romance_Pink___Red_Rose_Bouquet.jpg',NULL,16,20,'available',NULL,NULL,0.70,'45 cm x 40 cm x 55 cm',0,'2025-12-06 04:08:47',5),(46,'Blushing Harmony Bridal Bouquet','A romantic bridal bouquet featuring soft pink and white blooms accented with eucalyptus and baby’s breath—perfect for weddings with a sweet, elegant, and timeless theme.','wedding bouquet, bridal bouquet, pink roses, white roses, pink carnations',NULL,NULL,0,2800.00,'1764994590_Blushing_Harmony_Bridal_Bouquet.jpg',NULL,16,20,'available',NULL,NULL,0.60,'40 cm x 35 cm x 50 cm',0,'2025-12-06 04:16:30',5),(47,'Royal Blue & White Elegance Bouquet','A striking bouquet of vivid blue roses and delicate white baby’s breath, designed for those who love bold, modern, and unforgettable color themes.','blue roses, royal blue bouquet, blue and white flowers, color theme bouquet, modern bouquet, elegant flowers',NULL,NULL,0,1950.00,'1764994878_Royal_Blue___White_Elegance_Bouquet.jpg',NULL,13,20,'available',NULL,NULL,0.60,'40 cm x 35 cm x 55 cm',0,'2025-12-06 04:21:18',5),(48,'Pink Dream  Rose Bouquet','A crowd-favorite arrangement of assorted pink and white roses surrounded by lush pink baby’s breath, offering a soft, romantic look that customers love year-round.','best seller, pink roses, white roses, baby’s breath bouquet',NULL,NULL,0,1560.00,'1764995247_Pink_Dream_Signature_Rose_Bouquet.jpg',NULL,12,58,'available',NULL,NULL,0.65,'40 cm x 35 cm x 55 cm',0,'2025-12-06 04:27:27',5),(50,'pampatay','patay','jgsaugw',NULL,NULL,0,1200.00,'1771308868_Sympathy_Heart-Shaped_Tribute.jpg',NULL,16,4,'available',NULL,NULL,12.00,'18x15',0,'2026-02-17 06:14:28',5),(51,'Handwoven Rustic Gift Basket with Handle','A handwoven rustic gift basket made from natural rattan/wicker material, featuring a sturdy braided handle and tight weave pattern. Ideal for gift packaging, fruit arrangements, hampers, and home décor.','basket, rattan, handwoven, gift basket, rustic, wicker, hamper basket, storage basket',1,3,0,150.00,'1772380417_Handwoven_Rustic_Gift_Basket_with_Handle.jpg',NULL,17,10,'available',NULL,NULL,0.60,'30 x 22 x 12 cm',0,'2026-03-01 15:53:37',5),(52,'Vintage Floral Ceramic Planter Pot','A vintage-style ceramic planter pot featuring a glossy dark green finish with colorful hand-painted floral patterns. Designed with a wide mouth and thick rim, perfect for small plants, succulents, or decorative indoor use.','ceramic pot, planter, floral, vintage, plant pot, succulent pot, indoor decor, glazed pot',1,1,0,250.00,'1772380598_Vintage_Floral_Ceramic_Planter_Pot.webp','cutout_1795438647_6b04383728967_Vintage_Floral_Ceramic_Planter_Pot_cutout.png',17,4,'available',NULL,NULL,0.50,'12 x 12 x 10 cm',0,'2026-03-01 15:56:38',5),(53,'Customize Sheet of Premium Purple Floral Wrapper','A premium-quality floral wrapping sheet in vibrant purple color with an elegant gold border. Made from durable and smooth waterproof material, ideal for flower bouquets, gift wrapping, and special occasion arrangements.','floral wrapper, bouquet wrapper, gift wrap, purple wrapper, gold border, flower packaging, waterproof wrapper',1,8,0,25.00,'1772380722_Customize_Sheet_of_Premium_Purple_Floral_Wrapper.png',NULL,17,19,'available',NULL,NULL,0.02,'58 x 58 cm',0,'2026-03-01 15:58:42',5),(54,'Yellow Pom Pom Chrysanthemums','Fresh yellow pom-pom chrysanthemums featuring dense round blooms with a warm golden center. Commonly used as fillers for bouquets, floral arrangements, and event decorations.','chrysanthemum, button mums, yellow flowers, pompom flowers, bouquet filler, fresh flowers, floral supply',2,9,0,75.00,'1772381145_Pom_Pom_Yellow_Chrysanthemums.jpg',NULL,17,98,'available',NULL,NULL,0.30,'40 – 50 cm',0,'2026-03-01 16:05:45',5),(55,'Pink-Violet Fresh Roses','Fresh pink-violet roses with soft lavender tones and tightly layered petals. Perfect for romantic bouquets, special occasions, and elegant floral arrangements. Long stems with healthy green leaves, ideal for hand bouquets and event styling.','roses, pink roses, violet roses, fresh flowers, bouquet flower, romantic flowers, floral supply',2,4,0,50.00,'1772381400_Customize_pink_violet_rose.jpg',NULL,17,100,'available',NULL,NULL,0.02,'45 – 60 cm',0,'2026-03-01 16:10:00',5),(56,'White Pom-Pom Chrysanthemums','Fresh white pom-pom chrysanthemums featuring full, round blooms with soft ivory centers. Commonly used as bouquet fillers, sympathy arrangements, and elegant event décor.','white chrysanthemum, button mums, white flowers, bouquet filler, sympathy flowers, fresh flowers, floral supply',2,10,0,25.00,'1772381613_Customize_White_Ivory_Harmony.jpg',NULL,17,200,'available',NULL,NULL,0.30,'40 – 50 cm',0,'2026-03-01 16:13:33',5),(57,'Customizee Bundle of Fresh Eucalyptus Stems','Fresh eucalyptus stems with round, silvery-green leaves and a natural aromatic scent. Commonly used as greenery fillers for bouquets, wedding arrangements, and floral styling.','eucalyptus, greenery, foliage, bouquet filler, floral greens, fresh leaves, wedding greenery',3,11,0,15.00,'1772382196_Customizee_Bundle_of_Fresh_Eucalyptus_Stems.png',NULL,17,597,'available',NULL,NULL,0.25,'40 – 60 cm',0,'2026-03-01 16:23:16',5),(58,'Fresh White Baby’s Breath','Fresh white baby’s breath featuring delicate clusters of tiny blooms on slender green stems. Perfect as a bouquet filler, wedding décor accent, and floral arrangements.','baby’s breath, gypsophila, white flowers, bouquet filler, wedding flowers, floral supply, fresh flowers',3,12,0,80.00,'1772382366_Fresh_White_Baby___s_Breath.jpg',NULL,17,499,'available',NULL,NULL,0.15,'40 – 60 cm',0,'2026-03-01 16:26:06',5),(59,'White Silk Satin Ribbon','Premium white silk satin ribbon with a smooth, glossy finish on both sides. Soft, flexible, and easy to shape for bouquet wrapping, gift packaging, and decorative crafts.','satin ribbon, silk ribbon, white ribbon, bouquet ribbon, gift ribbon, floral supplies, craft ribbon',4,13,0,25.00,'1772382749_Customize_White_Silk_Ribbon.webp',NULL,17,94,'available',NULL,NULL,0.05,'2.5 cm',0,'2026-03-01 16:32:29',5),(60,'Customize Red Folded Silk Ribbon','remium white silk satin ribbon with a smooth, glossy finish on both sides. Soft, flexible, and easy to shape for bouquet wrapping, gift packaging, and decorative crafts.','satin ribbon, silk ribbon, white ribbon, bouquet ribbon, gift ribbon, floral supplies, craft ribbon',4,14,0,25.00,'1772382877_Customize_Red_Folded_Silk_Ribbon.jpg',NULL,17,100,'available',NULL,NULL,0.05,'2.5 cm',0,'2026-03-01 16:34:37',5),(61,'Yellow Flower','fdfs',NULL,2,NULL,0,100.00,'1795438990_Cremon_Disbud_Yellow_-_Bulk_and_Wholesale_-_10_stems.jpg','cutout_1795438990_6b04398ecbc5e_Cremon_Disbud_Yellow_-_Bulk_and_Wholesale_-_10_stems_cutout.png',17,0,'available',NULL,NULL,0.98,'12 x 12 x 10 cm',0,'2026-11-23 13:03:10',0),(62,'White Flower','asdhgf',NULL,2,NULL,0,150.00,'cutout_1795439077_6b0439e5d0cf4________________________________PNG__________________________________PNG_______________________cutout.png','cutout_1795439077_6b0439e5d0cf4________________________________PNG__________________________________PNG_______________________cutout.png',17,0,'available',NULL,NULL,1.00,'12 x 12 x 10 cm',0,'2026-11-23 13:04:37',0),(63,'Eucalyptus Branch','sadfsfd',NULL,3,NULL,0,10.00,'1795439454_Realistic_Eucalyptus_Branch_PNG___Greenery_Overlay.jpg','cutout_1795439454_6b043b5e6842f_Realistic_Eucalyptus_Branch_PNG___Greenery_Overlay_cutout.png',17,0,'available',NULL,NULL,1.00,'12 x 12 x 10 cm',0,'2026-11-23 13:10:54',0),(64,'Eucalyptus Branch','sadfsfd',NULL,3,NULL,0,10.00,'1795439467_Realistic_Eucalyptus_Branch_PNG___Greenery_Overlay.jpg','cutout_1795439467_6b043b6b283cf_Realistic_Eucalyptus_Branch_PNG___Greenery_Overlay_cutout.png',17,0,'available',NULL,NULL,1.00,'12 x 12 x 10 cm',0,'2026-11-23 13:11:07',0),(65,'White Ribbon','asdfs',NULL,4,13,0,25.00,'1795439607_download_1.jpg','cutout_1795439607_6b043bf7b4792_download_1_cutout.png',17,0,'available',NULL,NULL,1.00,'12 x 12 x 10 cm',0,'2026-11-23 13:13:27',0);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_point_logs`
--

DROP TABLE IF EXISTS `reward_point_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_point_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `order_id` int unsigned DEFAULT NULL,
  `points_change` int NOT NULL,
  `balance_after` int unsigned NOT NULL,
  `reason` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_order_reason` (`user_id`,`order_id`,`reason`),
  KEY `idx_reward_logs_user` (`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_point_logs`
--

LOCK TABLES `reward_point_logs` WRITE;
/*!40000 ALTER TABLE `reward_point_logs` DISABLE KEYS */;
INSERT INTO `reward_point_logs` VALUES (1,3,27,10,10,'order_earned','{\"points_per_order\":10}','2026-11-17 02:22:18'),(2,8,28,10,10,'order_earned','{\"points_per_order\":10}','2026-11-23 13:21:40');
/*!40000 ALTER TABLE `reward_point_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_tiers`
--

DROP TABLE IF EXISTS `reward_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_tiers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `required_orders` int unsigned NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL,
  `points_cost` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_required_orders` (`required_orders`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_tiers`
--

LOCK TABLES `reward_tiers` WRITE;
/*!40000 ALTER TABLE `reward_tiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `reward_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_settings`
--

DROP TABLE IF EXISTS `shipping_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_settings` (
  `id` tinyint unsigned NOT NULL,
  `per_km_rate` decimal(10,2) NOT NULL DEFAULT '10.00',
  `per_mile_rate` decimal(10,2) NOT NULL DEFAULT '16.09',
  `base_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `minimum_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `zone_roxas_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `zone_oriental_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `zone_out_province_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `calculation_unit` enum('km','mile') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'km',
  `google_maps_api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allow_out_of_stock_orders` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` int unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_settings`
--

LOCK TABLES `shipping_settings` WRITE;
/*!40000 ALTER TABLE `shipping_settings` DISABLE KEYS */;
INSERT INTO `shipping_settings` VALUES (1,50.00,100.00,10.00,20.00,25.00,50.00,100.00,'km','',1,1,'2026-03-29 17:51:29');
/*!40000 ALTER TABLE `shipping_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `address_type` enum('home','work','other') DEFAULT 'home',
  `street_address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `geocoded_at` datetime DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
INSERT INTO `user_addresses` VALUES (1,4,'home','Brgy.Labasan','Bongabong, Oriental Mindoro','Oriental Mindoro','5211','Philippines',NULL,NULL,NULL,1,'2025-11-28 04:24:00'),(2,5,'home','Brgy.Tambong','Gloria, Oriental Mindoro','Oriental Mindoro','5209','Philippines',NULL,NULL,NULL,0,'2025-11-28 04:46:15'),(3,3,'home','Zone 1','Bulalacao','Oriental Mindoro','5214','Philippines',NULL,NULL,NULL,1,'2025-11-28 05:56:46'),(4,6,'home','Guimbonan','Gloria','Oriental Mindoro','5209','Philippines',NULL,NULL,NULL,0,'2025-11-28 11:33:42'),(5,4,'work','Brgy. Zone 4','pinamalayan','Oriental Mindoro','5208','Philippines',NULL,NULL,NULL,0,'2025-11-28 13:08:34'),(6,1,'home','adf','asf','2we2','242','Philippines',NULL,NULL,NULL,0,'2025-11-30 16:12:05'),(7,8,'work','Barangay Zone IV, Pinamalyan, Oriental Mindoro','Pinamalayan','Mindoro Oriental','5208','Philippines',NULL,NULL,NULL,0,'2026-11-23 13:21:12');
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `google_id` varchar(255) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `email_opt_in` tinyint(1) NOT NULL DEFAULT '1',
  `avatar_url` varchar(512) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `reward_points` int unsigned NOT NULL DEFAULT '0',
  `is_disabled` tinyint(1) NOT NULL DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_password_reset_token_index` (`password_reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@gmail.com','$2y$10$aErxf/IZEw9.erwqjWRnu.QRGT0.58S.CephsJPrmqf1UiI/VCZtS','Administrator',NULL,'09099479197','admin',NULL,NULL,NULL,NULL,1,NULL,NULL,0,0,NULL,0,NULL,'2025-11-10 13:16:49'),(2,'Glenford','glenfordmacasa@gmail.com','$2y$10$Ab5vmSOCmIW5t3OEtSBhxOFTfmg.OZKhfeBfFZolYvE/wnaQ/waZa','Glenford Macasa',NULL,NULL,'customer',NULL,NULL,NULL,NULL,1,NULL,NULL,0,0,NULL,0,NULL,'2025-11-10 13:20:41'),(3,'aizer','aizer@gmail.com','$2y$10$Nfg3msBSUw9DOFQ9W6azVuyJuhZYH4Hf3fjbAN9IleDCGXSRsonOK','Aizer Cuasay',NULL,NULL,'customer',NULL,NULL,NULL,NULL,1,NULL,NULL,10,0,NULL,0,NULL,'2025-11-26 16:39:14'),(4,'van','vanvan.luci@gmail.com','$2y$10$d1NWDdrgOcHpjguYYDZy8.g0fwuKC/hbCSwyqW4zHFN6CDaXkViq6','Van Luci',NULL,'','customer',NULL,NULL,'c2d5e3cd72348221bd796359fd002aae60b2f0b906331a59fc312fd30f1d3206','2026-03-22 23:14:01',1,NULL,NULL,0,0,NULL,0,NULL,'2025-11-28 04:20:16'),(5,'nathaniel','nathaniellayron862@gmail.com','$2y$10$mIAS6NOnuo4JrNuyQ2UaI.M6JROaJ3UeOa.29I3iAVjGashHhNheG','Nathaniel Layron',NULL,'09776990461','customer',NULL,NULL,'f30d323b2879c63ebb9919a5ec1fe09bd5d16abce1c7f935401898a416dcce6e','2026-03-19 03:57:59',1,NULL,NULL,0,0,NULL,0,NULL,'2025-11-28 04:39:52'),(6,'rhamzel','rhamzel@gmail.com','$2y$10$uO6rFU3Hrhs4ghx276HPxOJw7cIzOqHDKjX5AwQo5CKcApvCEk6Ca','Rhamzel Mogool',NULL,NULL,'customer',NULL,NULL,NULL,NULL,1,NULL,NULL,0,0,NULL,0,NULL,'2025-11-28 11:32:10'),(7,'gerald','gerald@gmail.com','$2y$10$fOdQOJAH0wbwCMWoOMqr1u.njwkF7m/nmH4VOyI.TCNA4nbyCt7MW','Gerald Anderson',NULL,NULL,'customer',NULL,NULL,NULL,NULL,1,NULL,NULL,0,0,NULL,0,NULL,'2025-12-07 11:59:59'),(8,'lvinarcelo','alvinmarcelo@gmail.com','$2y$10$l1gnTrSPaiDrMwSEG/Gy..iEkMROePnUcbSHStz8hU/bZUZcUWdKi','Alvin P. Marcelo',NULL,NULL,'customer',NULL,NULL,NULL,NULL,1,NULL,'2004-11-17',10,0,NULL,0,NULL,'2026-11-23 13:17:46');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'flowershop'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-23 21:33:11
