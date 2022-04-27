-- MySQL dump 10.13  Distrib 8.0.28, for Linux (x86_64)
--
-- Host: localhost    Database: johncms
-- ------------------------------------------------------
-- Server version	8.0.28-0ubuntu0.20.04.3

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
-- Table structure for table `cms_ads`
--

DROP TABLE IF EXISTS `cms_ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_ads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint unsigned DEFAULT '0',
  `view` tinyint unsigned DEFAULT '0',
  `layout` tinyint unsigned DEFAULT '0',
  `count` int unsigned DEFAULT '0',
  `count_link` int unsigned DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` int unsigned DEFAULT '0',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `time` int unsigned DEFAULT '0',
  `day` int unsigned DEFAULT '0',
  `mesto` tinyint unsigned DEFAULT '0',
  `bold` tinyint unsigned DEFAULT '0',
  `italic` tinyint unsigned DEFAULT '0',
  `underline` tinyint unsigned DEFAULT '0',
  `show` tinyint unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_ads`
--

LOCK TABLES `cms_ads` WRITE;
/*!40000 ALTER TABLE `cms_ads` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_ads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_album_cat`
--

DROP TABLE IF EXISTS `cms_album_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_album_cat` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `sort` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `access` (`access`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_album_cat`
--

LOCK TABLES `cms_album_cat` WRITE;
/*!40000 ALTER TABLE `cms_album_cat` DISABLE KEYS */;
INSERT INTO `cms_album_cat` VALUES (2,5,1,'Альбом','','',4),(3,6,1,'Меми з Кобзарем','Прикольні зображення з Кобзарем','',4),(4,6,2,'Меми з котиками українською','','',4),(5,6,3,'Мемы на русском','','',4);
/*!40000 ALTER TABLE `cms_album_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_album_comments`
--

DROP TABLE IF EXISTS `cms_album_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_album_comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_album_comments`
--

LOCK TABLES `cms_album_comments` WRITE;
/*!40000 ALTER TABLE `cms_album_comments` DISABLE KEYS */;
INSERT INTO `cms_album_comments` VALUES (1,1,1651068254,6,'Чудова річ, а що це таке?','','a:4:{s:11:\"author_name\";s:10:\"Randomizer\";s:9:\"author_ip\";i:2533363984;s:19:\"author_ip_via_proxy\";i:0;s:14:\"author_browser\";s:115:\"Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36\";}');
/*!40000 ALTER TABLE `cms_album_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_album_downloads`
--

DROP TABLE IF EXISTS `cms_album_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_album_downloads` (
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `file_id` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_album_downloads`
--

LOCK TABLES `cms_album_downloads` WRITE;
/*!40000 ALTER TABLE `cms_album_downloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_album_downloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_album_files`
--

DROP TABLE IF EXISTS `cms_album_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_album_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `album_id` int unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `img_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tmb_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `time` int unsigned NOT NULL DEFAULT '0',
  `comments` tinyint(1) NOT NULL DEFAULT '1',
  `comm_count` int unsigned NOT NULL DEFAULT '0',
  `access` tinyint unsigned NOT NULL DEFAULT '0',
  `vote_plus` int NOT NULL DEFAULT '0',
  `vote_minus` int NOT NULL DEFAULT '0',
  `views` int unsigned NOT NULL DEFAULT '0',
  `downloads` int unsigned NOT NULL DEFAULT '0',
  `unread_comments` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `album_id` (`album_id`),
  KEY `access` (`access`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_album_files`
--

LOCK TABLES `cms_album_files` WRITE;
/*!40000 ALTER TABLE `cms_album_files` DISABLE KEYS */;
INSERT INTO `cms_album_files` VALUES (1,5,2,'','img_1651063305.jpg','tmb_1651063305.jpg',1651063306,1,1,4,0,0,3,0,1),(2,5,2,'','img_1651063400.jpg','tmb_1651063400.jpg',1651063401,1,0,4,0,0,2,0,0),(3,6,3,'Це не мем, це правда!','img_1651066008.jpg','tmb_1651066008.jpg',1651066009,1,0,4,0,0,0,0,0),(4,6,3,'Вуса','img_1651066054.jpg','tmb_1651066054.jpg',1651066055,1,0,4,0,0,0,0,0),(5,6,3,'Вишневий садочок','img_1651066092.jpg','tmb_1651066092.jpg',1651066092,1,0,4,0,0,0,0,0),(6,6,3,'','img_1651066141.jpg','tmb_1651066141.jpg',1651066142,1,0,4,0,0,0,0,0),(7,6,3,'Світлини та життя','img_1651066210.jpg','tmb_1651066210.jpg',1651066211,1,0,4,0,0,0,0,0),(8,6,3,'Найкраща компанія у світі','img_1651066260.jpg','tmb_1651066260.jpg',1651066261,1,0,4,0,0,0,0,0),(9,6,4,'','img_1651066679.jpg','tmb_1651066679.jpg',1651066680,1,0,4,0,0,0,0,0),(10,6,4,'','img_1651066765.jpg','tmb_1651066765.jpg',1651066765,1,0,4,0,0,0,0,0),(11,6,4,'','img_1651066785.jpg','tmb_1651066785.jpg',1651066786,1,0,4,0,0,0,0,0),(12,6,4,'','img_1651066803.jpg','tmb_1651066803.jpg',1651066804,1,0,4,0,0,0,0,0),(13,6,4,'','img_1651066873.jpg','tmb_1651066873.jpg',1651066874,1,0,4,0,0,0,0,0),(14,6,5,'- Сколько кошек у нормального человека? - У нормального человека 1-2 кошки. - Но это же кошатник! - Нет, кошатник, имеет 3-4 кошки. - Но это же маньяк! - Нет, у маньяка 5-6 кошек. - Но это же странный человек! - Нет, у странного человека нет кошки. - Но это же нормальный человек! - Нет, у нормального человека 1-2 кошки.\r\n\r\nhttps://vse-shutochki.ru/kartinka/572788','img_1651067085.jpg','tmb_1651067085.jpg',1651067086,1,0,4,0,0,2,0,0);
/*!40000 ALTER TABLE `cms_album_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_album_views`
--

DROP TABLE IF EXISTS `cms_album_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_album_views` (
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `file_id` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_album_views`
--

LOCK TABLES `cms_album_views` WRITE;
/*!40000 ALTER TABLE `cms_album_views` DISABLE KEYS */;
INSERT INTO `cms_album_views` VALUES (1,14,1651071101),(4,1,1651063573),(4,2,1651063567),(5,1,1651065464),(5,2,1651063729),(6,1,1651068191),(6,14,1651067089);
/*!40000 ALTER TABLE `cms_album_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_album_votes`
--

DROP TABLE IF EXISTS `cms_album_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_album_votes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `file_id` int unsigned NOT NULL DEFAULT '0',
  `vote` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_album_votes`
--

LOCK TABLES `cms_album_votes` WRITE;
/*!40000 ALTER TABLE `cms_album_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_album_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_ban_ip`
--

DROP TABLE IF EXISTS `cms_ban_ip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_ban_ip` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip1` bigint NOT NULL DEFAULT '0',
  `ip2` bigint NOT NULL DEFAULT '0',
  `ban_type` tinyint NOT NULL DEFAULT '0',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `who` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip1` (`ip1`),
  UNIQUE KEY `ip2` (`ip2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_ban_ip`
--

LOCK TABLES `cms_ban_ip` WRITE;
/*!40000 ALTER TABLE `cms_ban_ip` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_ban_ip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_ban_users`
--

DROP TABLE IF EXISTS `cms_ban_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_ban_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `ban_time` int NOT NULL DEFAULT '0',
  `ban_while` int NOT NULL DEFAULT '0',
  `ban_type` tinyint NOT NULL DEFAULT '1',
  `ban_who` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ban_ref` int NOT NULL DEFAULT '0',
  `ban_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ban_raz` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ban_time` (`ban_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_ban_users`
--

LOCK TABLES `cms_ban_users` WRITE;
/*!40000 ALTER TABLE `cms_ban_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_ban_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_contact`
--

DROP TABLE IF EXISTS `cms_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_contact` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `from_id` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  `type` tinyint unsigned NOT NULL DEFAULT '1',
  `friends` tinyint unsigned NOT NULL DEFAULT '0',
  `ban` tinyint unsigned NOT NULL DEFAULT '0',
  `man` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_user` (`user_id`,`from_id`),
  KEY `time` (`time`),
  KEY `ban` (`ban`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_contact`
--

LOCK TABLES `cms_contact` WRITE;
/*!40000 ALTER TABLE `cms_contact` DISABLE KEYS */;
INSERT INTO `cms_contact` VALUES (1,4,1,1651067336,1,0,0,0),(2,1,4,1651067346,1,0,0,0);
/*!40000 ALTER TABLE `cms_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_counters`
--

DROP TABLE IF EXISTS `cms_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_counters` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '1',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `link1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` tinyint NOT NULL DEFAULT '1',
  `switch` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_counters`
--

LOCK TABLES `cms_counters` WRITE;
/*!40000 ALTER TABLE `cms_counters` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_counters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_forum_files`
--

DROP TABLE IF EXISTS `cms_forum_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_forum_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cat` int unsigned NOT NULL DEFAULT '0',
  `subcat` int unsigned NOT NULL DEFAULT '0',
  `topic` int unsigned NOT NULL DEFAULT '0',
  `post` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  `filename` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `filetype` tinyint unsigned NOT NULL DEFAULT '0',
  `dlcount` int unsigned NOT NULL DEFAULT '0',
  `del` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cat` (`cat`),
  KEY `subcat` (`subcat`),
  KEY `topic` (`topic`),
  KEY `post` (`post`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_forum_files`
--

LOCK TABLES `cms_forum_files` WRITE;
/*!40000 ALTER TABLE `cms_forum_files` DISABLE KEYS */;
INSERT INTO `cms_forum_files` VALUES (1,1,3,1,3,1651063137,'DSC0851_2.jpg',5,4,0);
/*!40000 ALTER TABLE `cms_forum_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_forum_rdm`
--

DROP TABLE IF EXISTS `cms_forum_rdm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_forum_rdm` (
  `topic_id` int unsigned NOT NULL DEFAULT '0',
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`,`user_id`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_forum_rdm`
--

LOCK TABLES `cms_forum_rdm` WRITE;
/*!40000 ALTER TABLE `cms_forum_rdm` DISABLE KEYS */;
INSERT INTO `cms_forum_rdm` VALUES (2,6,1651064384),(2,5,1651064708),(1,6,1651065397),(1,1,1651065491),(1,5,1651066973),(3,5,1651067117),(3,6,1651067231),(3,4,1651067577),(2,4,1651067705),(1,4,1651067707),(3,1,1651073800),(2,1,1651073808);
/*!40000 ALTER TABLE `cms_forum_rdm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_forum_vote`
--

DROP TABLE IF EXISTS `cms_forum_vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_forum_vote` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` int NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  `topic` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type_topic` (`type`,`topic`),
  KEY `type` (`type`),
  KEY `topic` (`topic`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_forum_vote`
--

LOCK TABLES `cms_forum_vote` WRITE;
/*!40000 ALTER TABLE `cms_forum_vote` DISABLE KEYS */;
INSERT INTO `cms_forum_vote` VALUES (1,1,1651064604,2,'Ваша любимая игра',2),(2,2,0,2,'Жизнь',2),(3,2,0,2,'Дота',0);
/*!40000 ALTER TABLE `cms_forum_vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_forum_vote_users`
--

DROP TABLE IF EXISTS `cms_forum_vote_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_forum_vote_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user` int NOT NULL DEFAULT '0',
  `topic` int NOT NULL,
  `vote` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `topic_user` (`topic`,`user`),
  KEY `topic` (`topic`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_forum_vote_users`
--

LOCK TABLES `cms_forum_vote_users` WRITE;
/*!40000 ALTER TABLE `cms_forum_vote_users` DISABLE KEYS */;
INSERT INTO `cms_forum_vote_users` VALUES (1,1,2,2),(2,4,2,2);
/*!40000 ALTER TABLE `cms_forum_vote_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_library_comments`
--

DROP TABLE IF EXISTS `cms_library_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_library_comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int unsigned NOT NULL DEFAULT '0',
  `time` int NOT NULL DEFAULT '0',
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_library_comments`
--

LOCK TABLES `cms_library_comments` WRITE;
/*!40000 ALTER TABLE `cms_library_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_library_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_library_rating`
--

DROP TABLE IF EXISTS `cms_library_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_library_rating` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `st_id` int unsigned NOT NULL,
  `point` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_article` (`user_id`,`st_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_library_rating`
--

LOCK TABLES `cms_library_rating` WRITE;
/*!40000 ALTER TABLE `cms_library_rating` DISABLE KEYS */;
INSERT INTO `cms_library_rating` VALUES (1,1,1,4),(2,6,1,5),(3,6,2,5),(4,1,2,5),(5,6,3,5),(6,5,1,5),(7,4,2,5),(8,4,3,5);
/*!40000 ALTER TABLE `cms_library_rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_mail`
--

DROP TABLE IF EXISTS `cms_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_mail` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `from_id` int unsigned NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int unsigned NOT NULL DEFAULT '0',
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `sys` tinyint(1) NOT NULL DEFAULT '0',
  `delete` int unsigned NOT NULL DEFAULT '0',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `count` int NOT NULL DEFAULT '0',
  `size` int NOT NULL DEFAULT '0',
  `them` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `spam` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `from_id` (`from_id`),
  KEY `time` (`time`),
  KEY `read` (`read`),
  KEY `sys` (`sys`),
  KEY `delete` (`delete`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_mail`
--

LOCK TABLES `cms_mail` WRITE;
/*!40000 ALTER TABLE `cms_mail` DISABLE KEYS */;
INSERT INTO `cms_mail` VALUES (1,4,1,'Спасибо!',1651067346,0,0,0,'',0,0,'',0);
/*!40000 ALTER TABLE `cms_mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_sessions`
--

DROP TABLE IF EXISTS `cms_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_sessions` (
  `session_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` bigint NOT NULL DEFAULT '0',
  `ip_via_proxy` bigint NOT NULL DEFAULT '0',
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastdate` int unsigned NOT NULL DEFAULT '0',
  `sestime` int unsigned NOT NULL DEFAULT '0',
  `views` int unsigned NOT NULL DEFAULT '0',
  `movings` smallint unsigned NOT NULL DEFAULT '0',
  `place` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `lastdate` (`lastdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_sessions`
--

LOCK TABLES `cms_sessions` WRITE;
/*!40000 ALTER TABLE `cms_sessions` DISABLE KEYS */;
INSERT INTO `cms_sessions` VALUES ('0716551fd2cde73f9a0783a7a80fc9bc',2509938959,0,'TelegramBot (like TwitterBot)',1651070695,1651070695,1,1,'/downloads?act=view&id=4'),('0b0ba4bd7f37202bd20d36858bc57962',39616436,0,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.4 Safari/605.1.15',1651068041,1651068029,4,4,'/guestbook'),('16cd2fc16b5f8b6f23b85a6553f47170',2509938957,0,'TelegramBot (like TwitterBot)',1651064151,1651064151,1,1,'/profile'),('2392624b1a25770e484bd13734a748b5',1401661213,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0',1651061548,1651061548,1,1,'/'),('45ab7e93898613d662fa9be7f522743f',1437434924,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0',1651062935,1651062715,6,3,'/'),('7b493279e3a24068c5f7392a52ae0a1d',2509938945,0,'TelegramBot (like TwitterBot)',1651064233,1651064233,1,1,'/downloads'),('836d6fad9f0a52a214bd6c36deb16396',1560139576,0,'Mozilla/5.0 (Linux; Android 12; SM-G970U1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.41 Mobile Safari/537.36',1651075331,1651075331,1,1,'/'),('8b6a1f546ed804644cd1d07e946e054f',2533363984,0,'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',1651064106,1651064018,4,2,'/registration'),('9e158baeca7c6714e562804e4c9944c5',86813288,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36 Edg/100.0.1185.50',1651063153,1651063033,2,1,'/registration'),('9e57ee42048806cb0989e47320b857d6',1539630214,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)',1651057825,1651057672,9,4,'/login'),('b437902245c67de84543b21dca513730',1539630214,0,'Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.41 Mobile Safari/537.36',1651059359,1651059151,5,2,'/login'),('fed38cc1eb59629785ed91e4d2e7ebbc',1437434924,0,'Mozilla/5.0 (Linux; U; Android 7.0; ru-ru; Redmi Note 4 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/89.0.4389.116 Mobile S',1651062125,1651061938,3,3,'/community/users');
/*!40000 ALTER TABLE `cms_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_users_data`
--

DROP TABLE IF EXISTS `cms_users_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_users_data` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `val` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_users_data`
--

LOCK TABLES `cms_users_data` WRITE;
/*!40000 ALTER TABLE `cms_users_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_users_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_users_guestbook`
--

DROP TABLE IF EXISTS `cms_users_guestbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_users_guestbook` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int unsigned NOT NULL,
  `time` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_users_guestbook`
--

LOCK TABLES `cms_users_guestbook` WRITE;
/*!40000 ALTER TABLE `cms_users_guestbook` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_users_guestbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_users_iphistory`
--

DROP TABLE IF EXISTS `cms_users_iphistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_users_iphistory` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `ip` bigint NOT NULL DEFAULT '0',
  `ip_via_proxy` bigint NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_users_iphistory`
--

LOCK TABLES `cms_users_iphistory` WRITE;
/*!40000 ALTER TABLE `cms_users_iphistory` DISABLE KEYS */;
INSERT INTO `cms_users_iphistory` VALUES (1,1,0,0,1651057611),(5,1,1539630968,0,1651061619),(6,1,785600015,0,1651065694);
/*!40000 ALTER TABLE `cms_users_iphistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `download__bookmark`
--

DROP TABLE IF EXISTS `download__bookmark`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `download__bookmark` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `file_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `download__bookmark`
--

LOCK TABLES `download__bookmark` WRITE;
/*!40000 ALTER TABLE `download__bookmark` DISABLE KEYS */;
/*!40000 ALTER TABLE `download__bookmark` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `download__category`
--

DROP TABLE IF EXISTS `download__category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `download__category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `refid` int unsigned NOT NULL DEFAULT '0',
  `dir` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int NOT NULL DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` int unsigned NOT NULL DEFAULT '0',
  `rus_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` int unsigned NOT NULL DEFAULT '0',
  `desc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `total` (`total`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `download__category`
--

LOCK TABLES `download__category` WRITE;
/*!40000 ALTER TABLE `download__category` DISABLE KEYS */;
INSERT INTO `download__category` VALUES (1,0,'upload/downloads/files/Games',1651064423,'Games',2,'Игры','jar, sis, sisx, jad, mp3, avi, zip',1,'Сюда заливаем Ява игры. Да и всё остальное'),(2,0,'upload/downloads/files/NotGame',1651064736,'NotGame',2,'Не Игры','',0,'И сюда закиньте что-то'),(3,2,'upload/downloads/files/NotGame/Podpapka',1651064770,'Podpapka',1,'Подпапка','jar, sis, sisx, jad, mp3, avi, zip',1,'И в подпапка тоже что-то надо');
/*!40000 ALTER TABLE `download__category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `download__comments`
--

DROP TABLE IF EXISTS `download__comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `download__comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int unsigned NOT NULL,
  `time` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `download__comments`
--

LOCK TABLES `download__comments` WRITE;
/*!40000 ALTER TABLE `download__comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `download__comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `download__files`
--

DROP TABLE IF EXISTS `download__files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `download__files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `refid` int unsigned NOT NULL DEFAULT '0',
  `dir` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int unsigned NOT NULL DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int unsigned NOT NULL DEFAULT '0',
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `rus_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` int unsigned NOT NULL DEFAULT '0',
  `rate` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0|0',
  `about` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `desc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `comm_count` int unsigned NOT NULL DEFAULT '0',
  `updated` int unsigned NOT NULL DEFAULT '0',
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jadkey` text COLLATE utf8mb4_unicode_ci,
  `online` int unsigned NOT NULL DEFAULT '0',
  `3d` int unsigned NOT NULL DEFAULT '0',
  `bluetooth` int unsigned NOT NULL DEFAULT '0',
  `vendor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `mirrors` text COLLATE utf8mb4_unicode_ci,
  `md5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sha1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `time` (`time`),
  KEY `type` (`type`),
  KEY `user_id` (`user_id`),
  KEY `comm_count` (`comm_count`),
  KEY `updated` (`updated`),
  KEY `md5` (`md5`),
  KEY `sha1` (`sha1`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `download__files`
--

LOCK TABLES `download__files` WRITE;
/*!40000 ALTER TABLE `download__files` DISABLE KEYS */;
INSERT INTO `download__files` VALUES (1,1,'upload/downloads/files/Games',1651064758,'Playman_Extreme_Running_Nokia_3220.jar',2,6,'Playman Extreme Running','128x128: S40 2ed (русская версия)',0,'1|0','Спортивный человечек Playman вернулся! Теперь ему со своей сестрой предстоит участвовать в соревнованиях по паркуру. Бегаем-прыгаем по городу, по крышам, по заборам. Соревнуемся кто круче! Вас ждут 12 зон, и 45 уровней в этой отличной игрушке. В игре доступны режимы карьеры и мультиплеер до 7 человек.','',0,1651065601,'Sport, Extreme, Running, Playman, Mr. Godliving, 2007',NULL,0,0,0,'Mr. Goodliving','https://oldfag.top/downloads/?act=view&id=7627','e449217db88a4487f31f9bac84ccb0e5','c25e46273dfccd48e63dde7addea1abe82d9cde2',0),(2,2,'upload/downloads/files/NotGame',1651065385,'Lugans_ka_ODTRK_pozivn_mp3.mp3',2,6,'Луганська ОДТРК (позивні)','Завантажити',0,'0|0','Позивні (jingles) з ефіру Луганської областної державної телерадіокомпанії','',0,1651065385,'',NULL,0,0,0,'','','b948c434596e6925be9193a24de3a62a','dceb451c7dc2df40464d5ceddf4fed48052d7427',0),(3,3,'upload/downloads/files/NotGame/Podpapka',1651065540,'bluejabb_130792.jar',2,6,'BlueJabb','Завантажити',1,'1|0','Jabber client with Bluetooth messaging support','',0,1651065540,'',NULL,0,0,0,'','','73b6d01391381818d77daf8aa03cf137','8c5831a94df950f1ccdfeb431f11b0df22cdfc12',0),(4,1,'upload/downloads/files/Games',1651070494,'1651070494240x320_S40_3ed_Rus.jar',2,4,'Age of Heroes 2: Ужас из подземелья','Скачать',1,'0|0','Долгожданное продолжение великолепной стратеги \"Age of Heroes\" от \"Qplaze-RME\", вновь приглашает перенестись в мир магии и сражений. Вас \r\nждут невероятные приключения, ведь поверженное зло снова показало свой \r\nмертвый оскал. В руках Героя оказались судьбы других. Мёртвые проснулись\r\n не только в людских поселениях, но и во владениях подземных жителей. \r\nГномы – храбрые воины, но их оружие слабо против магии смерти, и один из\r\n немногих, кто может им помочь, некромант по имени Ортега, бывший \r\nпридворный маг, теперь коротающий свой век в маленькой хижине на склоне \r\nгоры. Такова судьба некроманта - сражаться с неживыми порождениями злых \r\nсил. Для него снова пришло время взяться за боевой посох и использовать \r\nсамые сильные заклинания - мир не должен погрузиться во тьму!\r\nВо второй части появилось значительное количество улучшений и новых \r\nвозможностей, которые сделали прохождение игры ещё более захватывающим и\r\n интересным. \r\nСреди них и обновлённая графика, и два разных мира, новые противники и \r\nновые союзники, герою теперь доступно для улучшения более 20 параметров.\r\n Новые артефакты, новая магия, сложные и интересные задания, противники,\r\n которые могут преследовать героя по всей карте, улучшенная схема \r\nсражений - всего не перечислить.\r\n\r\nХарактеристики игры:\r\n- Великолепная графика.\r\n- Возможность сохранения.\r\n- 10 боевых существ.\r\n- 20 уровней улучшения персонажа.\r\n- 3 уровня магии.\r\n- Совершенно новые квесты.\r\n- Два мира:\r\n    Горная долина и Подземелья гномов.\r\n\r\nУлучшения по сравнению с первой частью:\r\n- Увеличено количество уровней улучшения\r\n- Изменено боевое поле\r\n- Нет необходимости подтверждать конец хода\r\n- Противник может окружать войска героя\r\n- Разветвлённые диалоги с персонажами\r\n- Набор войск прямо на карте\r\n- Противники могут преследовать героя','',0,1651070494,'Rpg Qplaze',NULL,0,0,0,'Qplaze','','05e169926bc1348570115471d51f9f09','8c043168bc297562b508c54cd0653088a4efa86b',0);
/*!40000 ALTER TABLE `download__files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `download__more`
--

DROP TABLE IF EXISTS `download__more`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `download__more` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `refid` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rus_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int unsigned NOT NULL DEFAULT '0',
  `updated` int unsigned NOT NULL DEFAULT '0',
  `jadkey` text COLLATE utf8mb4_unicode_ci,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `md5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sha1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `time` (`time`),
  KEY `updated` (`updated`),
  KEY `user_id` (`user_id`),
  KEY `md5` (`md5`),
  KEY `sha1` (`sha1`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `download__more`
--

LOCK TABLES `download__more` WRITE;
/*!40000 ALTER TABLE `download__more` DISABLE KEYS */;
INSERT INTO `download__more` VALUES (1,1,1651065601,'file1_PER_K300.jar','128x128 : SE K,J,Z300 (English version)',144899,1651065601,NULL,6,'Randomizer','6fd9fa3c4a52d74e8371725a63ad257a','dcc20d36346bede7b273fd7b446c32cda3e157e2',0);
/*!40000 ALTER TABLE `download__more` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_messages`
--

DROP TABLE IF EXISTS `email_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `priority` int DEFAULT NULL COMMENT 'Priority of sending the message',
  `locale` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The language used for displaying the message',
  `template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template name',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT 'Event fields',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'The time when the message was sent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_messages`
--

LOCK TABLES `email_messages` WRITE;
/*!40000 ALTER TABLE `email_messages` DISABLE KEYS */;
INSERT INTO `email_messages` VALUES (1,1,'ru','system::mail/templates/registration','{\"email_to\":\"downloads@mail.re\",\"name_to\":\"Jhonny Depp\",\"subject\":\"\\u0420\\u0435\\u0433\\u0438\\u0441\\u0442\\u0440\\u0430\\u0446\\u0438\\u044f \\u043d\\u0430 \\u0441\\u0430\\u0439\\u0442\\u0435\",\"user_name\":\"Jhonny Depp\",\"user_login\":\"downloads\",\"link_to_confirm\":\"http:\\/\\/stage.oldfag.top\\/registration\\/?act=confirm_email&id=2&code=email_62692445131e26.15541189\"}','2022-04-27 15:31:11','2022-04-27 11:08:53','2022-04-27 15:31:11'),(2,1,'ru','system::mail/templates/registration','{\"email_to\":\"user@mails.ru\",\"name_to\":\"Just User\",\"subject\":\"\\u0420\\u0435\\u0433\\u0438\\u0441\\u0442\\u0440\\u0430\\u0446\\u0438\\u044f \\u043d\\u0430 \\u0441\\u0430\\u0439\\u0442\\u0435\",\"user_name\":\"Just User\",\"user_login\":\"simpleuser\",\"link_to_confirm\":\"http:\\/\\/stage.oldfag.top\\/registration\\/?act=confirm_email&id=3&code=email_6269249741ebe7.30280994\"}','2022-04-27 15:33:11','2022-04-27 11:10:15','2022-04-27 15:33:11');
/*!40000 ALTER TABLE `email_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `storage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int unsigned DEFAULT NULL,
  `md5` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sha1` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `files_storage_index` (`storage`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES (1,'local','image.png','guestbook/39/7d/51/397d51e0004c8c0b98d96c0bab5b236e.png',93537,'397d51e0004c8c0b98d96c0bab5b236e','1511db6b5270b80ad867451aef7264ab421d881e','2022-04-27 15:57:42','2022-04-27 15:57:42',NULL);
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_messages`
--

DROP TABLE IF EXISTS `forum_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int NOT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` int DEFAULT NULL,
  `user_id` int unsigned NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` bigint DEFAULT NULL,
  `ip_via_proxy` bigint DEFAULT NULL,
  `pinned` tinyint(1) DEFAULT NULL,
  `editor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edit_time` int DEFAULT NULL,
  `edit_count` int DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `deleted_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `deleted` (`deleted`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_messages`
--

LOCK TABLES `forum_messages` WRITE;
/*!40000 ALTER TABLE `forum_messages` DISABLE KEYS */;
INSERT INTO `forum_messages` VALUES (1,1,'Мы рады приветствовать Вас на нашем сайте :)\r\nДавайте знакомиться!',1571257080,1,'admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.121 Safari/537.36 Vivaldi/2.8.1664.44',0,0,NULL,NULL,NULL,NULL,NULL,NULL),(2,1,'Давайте! Моё имя вы уже знаете) а ваше? ))',1651062918,4,'Сергей','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0',1437434924,0,NULL,NULL,NULL,NULL,NULL,NULL),(3,1,'Вадик я ',1651063137,1,'admin','Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.41 Mobile Safari/537.36',785600015,0,NULL,NULL,NULL,NULL,NULL,NULL),(4,2,'Можно перечислить сюда любимые игры. Максимум написать название одной и на каком устройстве она проходилась)',1651063547,4,'Сергей','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0',1437434924,0,NULL,NULL,NULL,NULL,NULL,NULL),(5,2,'Double Dragon: Family Computer (NES clone)\r\nPlayman Extreme Running: Nokia 3720 classic\r\nHill Climb Racing: Enot E102\r\nTrackmania Nations ESWC/Forever: Windows XP desktop / Dell Inspiron 3531',1651064367,6,'Randomizer','Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',2533363984,0,NULL,'Randomizer',1651064384,1,NULL,NULL),(6,1,'[timestamp]27.04.2022 13:08[/timestamp]\nadmin,  Це дійсно Ви? Цікаво, я по иншому уявляв)\n\n[timestamp]27.04.2022 13:08[/timestamp]\nЯ Randomizer, і хочу сказати, що Oldfag.top!',1651064934,6,'Randomizer','Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',2533363984,0,NULL,NULL,NULL,NULL,NULL,NULL),(7,1,'[c][url=http://stage.oldfag.top/forum/?act=show_post&id=6]#[/url] [url=http://stage.oldfag.top/profile/?user=6]Randomizer[/url] ([time]27.04.2022 13:08[/time])\nЦе дійсно Ви? Цікаво, я по иншому уявляв)[/c]Не, то просто рендомна фотка',1651065360,1,'admin','Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.41 Mobile Safari/537.36',785600015,0,NULL,NULL,NULL,NULL,NULL,NULL),(8,3,'Желаю всево харошева, плахова не желаю',1651067070,5,'Gachimuchi','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36 Edg/100.0.1185.50',86813288,0,NULL,NULL,NULL,NULL,NULL,NULL),(9,3,'Взаємно, погане люди обирають самі, якщо вони його не обирають, то мають обрати хороше',1651067230,6,'Randomizer','Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',2533363984,0,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `forum_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_sections`
--

DROP TABLE IF EXISTS `forum_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_sections` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort` int NOT NULL DEFAULT '100',
  `access` int DEFAULT NULL,
  `section_type` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_sections`
--

LOCK TABLES `forum_sections` WRITE;
/*!40000 ALTER TABLE `forum_sections` DISABLE KEYS */;
INSERT INTO `forum_sections` VALUES (1,0,'Общение','Свободное общение на любую тему','',NULL,1,0,0),(2,1,'О разном','','',NULL,1,0,1),(3,1,'Знакомства','','',NULL,2,0,1),(4,1,'Жизнь ресурса','','',NULL,3,0,1),(5,1,'Новости','','',NULL,4,0,1),(6,1,'Предложения и пожелания','','',NULL,5,0,1),(7,1,'Разное','','',NULL,6,0,1);
/*!40000 ALTER TABLE `forum_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_topic`
--

DROP TABLE IF EXISTS `forum_topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_topic` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int DEFAULT NULL,
  `user_id` int unsigned NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `post_count` int DEFAULT NULL,
  `mod_post_count` int DEFAULT NULL,
  `last_post_date` int DEFAULT NULL,
  `last_post_author` int unsigned DEFAULT NULL,
  `last_post_author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_message_id` bigint DEFAULT NULL,
  `mod_last_post_date` int DEFAULT NULL,
  `mod_last_post_author` int unsigned DEFAULT NULL,
  `mod_last_post_author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mod_last_message_id` bigint DEFAULT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  `closed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `deleted_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curators` mediumtext COLLATE utf8mb4_unicode_ci,
  `pinned` tinyint(1) DEFAULT NULL,
  `has_poll` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_topic`
--

LOCK TABLES `forum_topic` WRITE;
/*!40000 ALTER TABLE `forum_topic` DISABLE KEYS */;
INSERT INTO `forum_topic` VALUES (1,3,'Привет всем!','','',NULL,6,1,'admin','2019-10-16 20:18:00',5,5,1651065360,1,'admin',7,1651065360,1,'admin',7,NULL,NULL,NULL,NULL,'',NULL,NULL),(2,3,'Есть ли любимые игры?',NULL,NULL,NULL,6,4,'Сергей','2022-04-27 12:45:47',2,2,1651064367,6,'Randomizer',5,1651064367,6,'Randomizer',5,NULL,NULL,NULL,NULL,'a:0:{}',NULL,1),(3,6,'Пожелание',NULL,'мета','мета',4,5,'Gachimuchi','2022-04-27 13:44:30',2,2,1651067230,6,'Randomizer',9,1651067230,6,'Randomizer',9,NULL,NULL,NULL,NULL,'a:0:{}',NULL,NULL);
/*!40000 ALTER TABLE `forum_topic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guest`
--

DROP TABLE IF EXISTS `guest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guest` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `adm` tinyint(1) NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` bigint NOT NULL DEFAULT '0',
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `admin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `otvet` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `otime` int unsigned NOT NULL DEFAULT '0',
  `edit_who` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `edit_time` int unsigned NOT NULL DEFAULT '0',
  `edit_count` tinyint unsigned NOT NULL DEFAULT '0',
  `attached_files` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `adm` (`adm`),
  KEY `time` (`time`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guest`
--

LOCK TABLES `guest` WRITE;
/*!40000 ALTER TABLE `guest` DISABLE KEYS */;
INSERT INTO `guest` VALUES (1,1,1217060516,1,'admin','Добро пожаловать в Админ Клуб!\r\nСюда имеют доступ ТОЛЬКО Модераторы и Администраторы.\r\nПростым пользователям доступ сюда закрыт.',2130706433,'Opera/9.51','','',0,'',0,0,NULL),(2,0,1217060536,1,'admin','Добро пожаловать в Гостевую!',2130706433,'Opera/9.51','admin','Проверка ответа Администратора',1217064021,'',0,0,NULL),(3,0,1217061125,1,'admin','Гостевая поддерживает полноценное форматирование текста в визуальном редакторе:<br>\n<span style=\"font-weight: bold\">жирный</span><br>\n<span style=\"font-style:italic\">курсив</span><br>\n<span style=\"text-decoration:underline\">подчеркнутый</span><br>\n<span style=\"color:red\">красный</span><br>\n<span style=\"color:green\">зеленый</span><br>\n<span style=\"color:blue\">синий</span><br>\nВставку ссылок: <a href=\"https://johncms.com\">https://johncms.com</a>, картинок, таблиц, видео и многого другого',2130706433,'Opera/9.51','','',0,'',0,0,NULL),(4,0,1651063224,1,'admin','<p>Блин. Гостевая требует права на папку . Которых в инструкции нет</p>',785600015,'Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.41 Mobile Safari/537.36','','',0,'',0,0,'[]'),(5,0,1651063372,4,'Сергей','<p>тест поста</p>',1437434924,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0','','',0,'',0,0,'[]'),(6,0,1651063386,4,'Сергей','<p>но сообщения отправляются...</p>',1437434924,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0','','',0,'',0,0,'[]'),(7,0,1651064214,6,'Randomizer','<p>admin, test</p>',2533363984,'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36','','',0,'',0,0,'[]'),(8,1,1651065731,6,'Randomizer','<p>А тут треба щось писати?</p>',2533363984,'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36','','',0,'',0,0,'[]'),(9,1,1651067367,4,'Сергей','<p>Разок сюда написать можно для БД)</p>',1437434924,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0','','',0,'',0,0,'[]'),(10,0,1651068229,6,'Randomizer','<p>Оцінювання світлин не працює чомусь :(</p>',2533363984,'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36','','',0,'',0,0,'[]'),(11,0,1651074105,1,'admin','<p>Просто скрин из буфера обмена&nbsp;<br>&nbsp;</p><figure class=\"image\"><img><figcaption>podpis</figcaption></figure><p>asdasdas&nbsp;</p>',1539630214,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)','','',0,'',0,0,'[null]'),(12,0,1651074201,1,'admin','<figure class=\"image\"><img></figure>',1539630214,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)','','',0,'',0,0,'[null]'),(13,0,1651074708,1,'admin','<p>&nbsp;</p><p>sad</p><figure class=\"image\"><img></figure>',1539630214,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)','','',0,'',0,0,'[null]'),(14,0,1651075067,1,'admin','<figure class=\"image\"><img src=\"/upload/guestbook/39/7d/51/397d51e0004c8c0b98d96c0bab5b236e.png\"></figure><p>ddsad</p>',1539630214,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)','','',0,'',0,0,'[1]');
/*!40000 ALTER TABLE `guest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `karma_users`
--

DROP TABLE IF EXISTS `karma_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `karma_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `karma_user` int unsigned NOT NULL DEFAULT '0',
  `points` tinyint unsigned NOT NULL DEFAULT '0',
  `type` tinyint unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `karma_user` (`karma_user`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `karma_users`
--

LOCK TABLES `karma_users` WRITE;
/*!40000 ALTER TABLE `karma_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `karma_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_cats`
--

DROP TABLE IF EXISTS `library_cats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_cats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dir` tinyint(1) NOT NULL DEFAULT '0',
  `pos` int unsigned NOT NULL DEFAULT '0',
  `user_add` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_cats`
--

LOCK TABLES `library_cats` WRITE;
/*!40000 ALTER TABLE `library_cats` DISABLE KEYS */;
INSERT INTO `library_cats` VALUES (1,0,'Обзоры','Тут публикуем обзоры игр',0,1,0),(2,0,'Другое','Всякое разное',0,2,0),(3,0,'Вірші','Вірші різних поетів письменників',0,3,0);
/*!40000 ALTER TABLE `library_cats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_tags`
--

DROP TABLE IF EXISTS `library_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `lib_text_id` int unsigned NOT NULL DEFAULT '0',
  `tag_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `lib_text_id` (`lib_text_id`),
  KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_tags`
--

LOCK TABLES `library_tags` WRITE;
/*!40000 ALTER TABLE `library_tags` DISABLE KEYS */;
INSERT INTO `library_tags` VALUES (1,1,'library tags not working'),(2,2,'football'),(3,2,'liverpool'),(4,3,'');
/*!40000 ALTER TABLE `library_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_texts`
--

DROP TABLE IF EXISTS `library_texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_texts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int unsigned NOT NULL DEFAULT '0',
  `text` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `announce` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploader` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uploader_id` int unsigned NOT NULL DEFAULT '0',
  `count_views` int unsigned NOT NULL DEFAULT '0',
  `premod` tinyint(1) NOT NULL DEFAULT '0',
  `comments` tinyint(1) NOT NULL DEFAULT '0',
  `comm_count` int unsigned NOT NULL DEFAULT '0',
  `time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_texts`
--

LOCK TABLES `library_texts` WRITE;
/*!40000 ALTER TABLE `library_texts` DISABLE KEYS */;
INSERT INTO `library_texts` VALUES (1,2,'Двум пользователям выданы права администратора. Я вас поздравляю \r\nЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайта','Статья о том, что теперь все админы','Представляете, теперь каждый может получить админку на сайте :)','admin',1,6,1,1,0,1651063840),(2,1,'Cool game!)\r\nLiverpool the best!','Football','aka Soccer','Randomizer',6,6,1,1,0,1651065034),(3,3,'Мені однаково, чи буду\r\nЯ жить в Україні, чи ні.\r\nЧи хто згадає, чи забуде\r\nМене в снігу на чужині —\r\nОднаковісінько мені.\r\nВ неволі виріс між чужими,\r\nІ, неоплаканий своїми,\r\nВ неволі, плачучи, умру,\r\nІ все з собою заберу —\r\nМалого сліду не покину\r\nНа нашій славній Україні,\r\nНа нашій — не своїй землі.\r\nI не пом\'яне батько з сином,\r\nНе скаже синові: — Молись.\r\nМолися, сину: за Вкраїну\r\nЙого замучили колись. —\r\nМені однаково, чи буде\r\nТой син молитися, чи ні...\r\nТа не однаково мені,\r\nЯк Україну злії люди\r\nПрисплять, лукаві, і в огні\r\nЇї, окраденую, збудять...\r\nОх, не однаково мені.','Мені однаково, чи буду... (з циклу \"В казематі\" 1847-го року)','Мені однаково, чи буду\r\nЯ жить в Україні, чи ні.\r\nЧи хто згадає, чи забуде\r\nМене в снігу на чужині —\r\nОднаковісінько мені...','Randomizer',6,4,1,1,0,1651066483);
/*!40000 ALTER TABLE `library_texts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_articles`
--

DROP TABLE IF EXISTS `news_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int unsigned DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `active_from` datetime DEFAULT NULL,
  `active_to` datetime DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `preview_text` text COLLATE utf8mb4_unicode_ci,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_count` int DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `attached_files` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_code` (`section_id`,`code`),
  KEY `news_articles_section_id_index` (`section_id`),
  KEY `news_articles_code_index` (`code`),
  KEY `news_articles_tags_index` (`tags`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_articles`
--

LOCK TABLES `news_articles` WRITE;
/*!40000 ALTER TABLE `news_articles` DISABLE KEYS */;
INSERT INTO `news_articles` VALUES (1,0,1,'2021-04-27 15:41:00','2023-04-27 15:41:00','Май нейм из Статья','Май нейм из Статья','mai-neim-iz-statya','My name is statya','Просто описание статьи. Интересно что же тут написать ? :)','<p>Краткое описание того что потом будет длинным :)</p>','<p>Это очень длинная история о том как я решил упростить разработку сайта.&nbsp;</p><p>Это очень длинная история о том как я решил упростить разработку сайта</p><p>Это очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайта</p>',3,'My tag is statya',1,NULL,'[]','2022-04-27 12:46:29','2022-04-27 13:58:17',NULL);
/*!40000 ALTER TABLE `news_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_comments`
--

DROP TABLE IF EXISTS `news_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `attached_files` longtext COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `news_comments_article_id_index` (`article_id`),
  CONSTRAINT `news_comments_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `news_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_comments`
--

LOCK TABLES `news_comments` WRITE;
/*!40000 ALTER TABLE `news_comments` DISABLE KEYS */;
INSERT INTO `news_comments` VALUES (1,1,1,'<p>Комментариий :)</p>','{\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 12) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/101.0.4951.41 Mobile Safari\\/537.36\",\"ip\":\"46.211.78.15\",\"ip_via_proxy\":0}','2022-04-27 13:02:55','[]',NULL),(2,1,6,'<p>It need to be simpler</p>','{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/100.0.4896.127 Safari\\/537.36\",\"ip\":\"151.0.17.16\",\"ip_via_proxy\":0}','2022-04-27 13:06:58','[]',NULL),(3,1,1,'<p>Randomizer, в смысле сами новости или как?</p>','{\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 12) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/101.0.4951.41 Mobile Safari\\/537.36\",\"ip\":\"46.211.78.15\",\"ip_via_proxy\":0}','2022-04-27 13:20:51','[]',NULL),(4,1,6,'<p>Занадто багато повторень</p>','{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/100.0.4896.127 Safari\\/537.36\",\"ip\":\"151.0.17.16\",\"ip_via_proxy\":0}','2022-04-27 13:21:43','[]',NULL);
/*!40000 ALTER TABLE `news_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_search_index`
--

DROP TABLE IF EXISTS `news_search_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_search_index` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `news_search_index_article_id_index` (`article_id`),
  CONSTRAINT `news_search_index_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `news_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_search_index`
--

LOCK TABLES `news_search_index` WRITE;
/*!40000 ALTER TABLE `news_search_index` DISABLE KEYS */;
INSERT INTO `news_search_index` VALUES (1,1,'Май нейм из Статья Краткое описание того что потом будет длинным :) Это очень длинная история о том как я решил упростить разработку сайта.&nbsp;Это очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайтаЭто очень длинная история о том как я решил упростить разработку сайта');
/*!40000 ALTER TABLE `news_search_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_sections`
--

DROP TABLE IF EXISTS `news_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_sections` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `news_sections_parent_index` (`parent`),
  KEY `news_sections_code_index` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_sections`
--

LOCK TABLES `news_sections` WRITE;
/*!40000 ALTER TABLE `news_sections` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_votes`
--

DROP TABLE IF EXISTS `news_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_votes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `vote` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_user` (`article_id`,`user_id`),
  CONSTRAINT `news_votes_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `news_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_votes`
--

LOCK TABLES `news_votes` WRITE;
/*!40000 ALTER TABLE `news_votes` DISABLE KEYS */;
INSERT INTO `news_votes` VALUES (1,1,1,1),(2,1,6,1),(3,1,4,1);
/*!40000 ALTER TABLE `news_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Module name',
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Event type',
  `user_id` int unsigned NOT NULL COMMENT 'User identifier',
  `sender_id` int unsigned DEFAULT NULL COMMENT 'Sender identifier',
  `entity_id` int unsigned DEFAULT NULL COMMENT 'Entity identifier',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT 'Event fields',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'Read date',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_module_type_entity` (`user_id`,`module`,`event_type`,`entity_id`),
  KEY `notifications_user_id_index` (`user_id`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'forum','new_message',1,6,6,'{\"topic_name\":\"\\u041f\\u0440\\u0438\\u0432\\u0435\\u0442 \\u0432\\u0441\\u0435\\u043c!\",\"user_name\":\"Randomizer\",\"topic_url\":\"\\/forum\\/?type=topic&amp;id=1\",\"reply_to_message\":\"\\/forum\\/?act=show_post&id=3\",\"message\":\"\\u0426\\u0435 \\u0434\\u0456\\u0439\\u0441\\u043d\\u043e \\u0412\\u0438? \\u0426\\u0456\\u043a\\u0430\\u0432\\u043e, \\u044f \\u043f\\u043e \\u0438\\u043d\\u0448\\u043e\\u043c\\u0443 \\u0443\\u044f\\u0432\\u043b\\u044f\\u0432)\",\"post_id\":\"6\",\"topic_id\":1}','2022-04-27 13:08:24','2022-04-27 13:08:15','2022-04-27 13:08:24'),(2,'forum','new_message',6,1,7,'{\"topic_name\":\"\\u041f\\u0440\\u0438\\u0432\\u0435\\u0442 \\u0432\\u0441\\u0435\\u043c!\",\"user_name\":\"admin\",\"topic_url\":\"\\/forum\\/?type=topic&amp;id=1\",\"reply_to_message\":\"\\/forum\\/?act=show_post&id=6\",\"message\":\"\\u041d\\u0435, \\u0442\\u043e \\u043f\\u0440\\u043e\\u0441\\u0442\\u043e \\u0440\\u0435\\u043d\\u0434\\u043e\\u043c\\u043d\\u0430 \\u0444\\u043e\\u0442\\u043a\\u0430\",\"post_id\":\"7\",\"topic_id\":1}','2022-04-27 13:16:35','2022-04-27 13:16:00','2022-04-27 13:16:35');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name_lat` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rights` tinyint unsigned NOT NULL DEFAULT '0',
  `failed_login` tinyint unsigned NOT NULL DEFAULT '0',
  `imname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sex` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `komm` int unsigned NOT NULL DEFAULT '0',
  `postforum` int unsigned NOT NULL DEFAULT '0',
  `postguest` int unsigned NOT NULL DEFAULT '0',
  `yearofbirth` int unsigned NOT NULL DEFAULT '0',
  `datereg` int unsigned NOT NULL DEFAULT '0',
  `lastdate` int unsigned NOT NULL DEFAULT '0',
  `mail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icq` int unsigned NOT NULL DEFAULT '0',
  `skype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jabber` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `www` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `about` text COLLATE utf8mb4_unicode_ci,
  `live` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mibile` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` bigint NOT NULL DEFAULT '0',
  `ip_via_proxy` bigint NOT NULL DEFAULT '0',
  `browser` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `preg` tinyint(1) NOT NULL DEFAULT '0',
  `regadm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mailvis` tinyint(1) NOT NULL DEFAULT '0',
  `dayb` int NOT NULL DEFAULT '0',
  `monthb` int NOT NULL DEFAULT '0',
  `sestime` int unsigned NOT NULL DEFAULT '0',
  `total_on_site` int unsigned NOT NULL DEFAULT '0',
  `lastpost` int unsigned NOT NULL DEFAULT '0',
  `rest_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rest_time` int unsigned NOT NULL DEFAULT '0',
  `movings` int unsigned NOT NULL DEFAULT '0',
  `place` text COLLATE utf8mb4_unicode_ci,
  `set_user` text COLLATE utf8mb4_unicode_ci,
  `set_forum` text COLLATE utf8mb4_unicode_ci,
  `set_mail` text COLLATE utf8mb4_unicode_ci,
  `karma_plus` int NOT NULL DEFAULT '0',
  `karma_minus` int NOT NULL DEFAULT '0',
  `karma_time` int unsigned NOT NULL DEFAULT '0',
  `karma_off` tinyint(1) NOT NULL DEFAULT '0',
  `comm_count` int unsigned NOT NULL DEFAULT '0',
  `comm_old` int unsigned NOT NULL DEFAULT '0',
  `smileys` text COLLATE utf8mb4_unicode_ci,
  `notification_settings` text COLLATE utf8mb4_unicode_ci,
  `email_confirmed` tinyint(1) DEFAULT NULL,
  `confirmation_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `coins` int NOT NULL DEFAULT '0',
  `exp` int NOT NULL DEFAULT '0',
  `lvl` int NOT NULL DEFAULT '0',
  `hp` int NOT NULL DEFAULT '0',
  `mp` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name_lat` (`name_lat`),
  KEY `lastdate` (`lastdate`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin','4543316a5cd11c3587e2909ff85162d4',9,0,'Vadik','m',0,2,5,1992,1651057611,1651075067,'admin@mailsd.ru',0,'','','http://stage.oldfag.top','','','','',1539630214,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)',1,'',1,10,10,1651075048,0,1651075067,'',0,5,'/guestbook','a:0:{}','a:0:{}','a:0:{}',0,0,1651057629,0,0,0,'a:0:{}',NULL,1,NULL,NULL,'',0,0,0,0,0),(2,'downloads','downloads','14e1b600b1fd579f47433b88e8d85291',4,0,'Jhonny Depp','m',0,0,0,2012,1651057733,1651057733,'downloads@mail.re',0,'','','','Whi am I','','','',1539630214,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)',1,'',1,12,12,1651057733,0,0,'',0,0,NULL,'a:0:{}','a:0:{}','a:0:{}',0,0,0,0,0,0,'a:0:{}',NULL,1,'email_62692445131e26.15541189',NULL,'',0,0,0,0,0),(3,'simpleuser','simpleuser','14e1b600b1fd579f47433b88e8d85291',0,0,'Just User','m',0,0,0,2013,1651057815,1651057815,'user@mails.ru',0,'','','','Nothing Here','','','',1539630214,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.4972.0 Safari/537.36 OPR/88.0.4401.0 (Edition developer)',1,'',1,10,12,1651057815,0,0,'',0,0,NULL,'a:0:{}','a:0:{}','a:0:{}',0,0,0,0,0,0,'a:0:{}',NULL,1,'email_6269249741ebe7.30280994',NULL,'',0,0,0,0,0),(4,'Сергей','sergei','fcb565153cc483fb4b83aec5073d48b1',7,0,'Сергей','m',0,2,3,2020,1651062875,1651075194,'sergei.ru.net@mail.ru',0,'','','','Олдфаг)','','','',1437434924,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0',1,'',0,10,10,1651075194,0,1651067367,'',0,1,'/downloads?act=view&id=4','a:0:{}','a:0:{}','a:0:{}',0,0,1651062877,0,0,0,'a:0:{}',NULL,1,NULL,NULL,'',0,0,0,0,0),(5,'Gachimuchi','gachimuchi','b382e36ae12be72b30acc192580f3098',7,0,'Billy','m',0,1,0,2002,1651063153,1651067121,'kylie@spaces.ru',0,'','','','','','','',86813288,0,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36 Edg/100.0.1185.50',1,'',0,2,2,1651066889,0,1651067070,'',0,55,'/','a:0:{}','a:0:{}','a:0:{}',0,0,1651063162,0,0,0,'a:0:{}',NULL,1,NULL,NULL,'',0,0,0,0,0),(6,'Randomizer','randomizer','627b63e6f83bf06e4c43e0bfeb808825',7,0,'NameASys','m',1,4,3,2004,1651064106,1651068254,'randomizer@pvltst.tk',0,'','','','','','','',2533363984,0,'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',1,'',0,4,4,1651067998,0,1651068254,'',0,10,'/album/comments','a:0:{}','a:0:{}','a:0:{}',0,0,1651064110,0,0,0,'a:0:{}',NULL,1,NULL,NULL,'',0,0,0,0,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-27 16:42:14
