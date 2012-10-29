-- MySQL dump 10.13  Distrib 5.5.28, for Linux (x86_64)
--
-- Host: localhost    Database: sandbox
-- ------------------------------------------------------
-- Server version	5.5.28-29.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` mediumint(8) unsigned NOT NULL,
  `imageId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `imageId` (`imageId`),
  CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`imageId`) REFERENCES `image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment`
--

LOCK TABLES `comment` WRITE;
/*!40000 ALTER TABLE `comment` DISABLE KEYS */;
INSERT INTO `comment` VALUES (1,2,7),(2,2,28),(3,3,24),(4,1,27),(5,1,2),(6,1,28),(7,1,17),(8,2,10),(9,1,4),(10,3,5),(11,1,15),(12,3,12),(13,2,20),(14,1,3),(15,2,8),(16,1,6),(17,3,5),(18,2,20),(19,2,10),(20,2,17),(21,1,21),(22,1,6),(23,3,29),(24,1,23),(25,2,9),(26,2,13),(27,3,6),(28,1,24),(29,2,26),(30,3,12),(31,2,16),(32,3,10),(33,2,29),(34,3,9),(35,3,1),(36,3,19),(37,3,23),(38,2,26),(39,2,12),(40,3,10),(41,2,8),(42,2,17),(43,1,27),(44,2,19),(45,1,28),(46,3,12),(47,3,8),(48,2,4),(49,1,25),(50,2,8),(51,2,7),(52,2,11),(53,2,8),(54,1,25),(55,3,26),(56,2,16),(57,1,12),(58,3,22),(59,1,18),(60,3,6),(61,1,2),(62,1,9),(63,2,10),(64,2,25),(65,3,20),(66,3,30),(67,3,26),(68,2,23),(69,2,18),(70,2,21),(71,2,4),(72,3,23),(73,2,10),(74,3,8),(75,3,15),(76,1,13),(77,3,12),(78,3,12),(79,3,12),(80,3,7),(81,2,10),(82,2,24),(83,3,30),(84,3,14),(85,3,23),(86,3,24),(87,1,4),(88,2,20),(89,3,7),(90,2,28),(91,3,5),(92,1,17),(93,3,23),(94,2,7),(95,3,24),(96,3,28),(97,1,7),(98,3,29),(99,2,6),(100,3,27);
/*!40000 ALTER TABLE `comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment_data`
--

DROP TABLE IF EXISTS `comment_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `comment_data_ibfk_1` FOREIGN KEY (`id`) REFERENCES `comment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_data`
--

LOCK TABLES `comment_data` WRITE;
/*!40000 ALTER TABLE `comment_data` DISABLE KEYS */;
INSERT INTO `comment_data` VALUES (1,'Comment # 1 userId: 2, imageId: 7'),(2,'Comment # 2 userId: 2, imageId: 28'),(3,'Comment # 3 userId: 3, imageId: 24'),(4,'Comment # 4 userId: 1, imageId: 27'),(5,'Comment # 5 userId: 1, imageId: 2'),(6,'Comment # 6 userId: 1, imageId: 28'),(7,'Comment # 7 userId: 1, imageId: 17'),(8,'Comment # 8 userId: 2, imageId: 10'),(9,'Comment # 9 userId: 1, imageId: 4'),(10,'Comment # 10 userId: 3, imageId: 5'),(11,'Comment # 11 userId: 1, imageId: 15'),(12,'Comment # 12 userId: 3, imageId: 12'),(13,'Comment # 13 userId: 2, imageId: 20'),(14,'Comment # 14 userId: 1, imageId: 3'),(15,'Comment # 15 userId: 2, imageId: 8'),(16,'Comment # 16 userId: 1, imageId: 6'),(17,'Comment # 17 userId: 3, imageId: 5'),(18,'Comment # 18 userId: 2, imageId: 20'),(19,'Comment # 19 userId: 2, imageId: 10'),(20,'Comment # 20 userId: 2, imageId: 17'),(21,'Comment # 21 userId: 1, imageId: 21'),(22,'Comment # 22 userId: 1, imageId: 6'),(23,'Comment # 23 userId: 3, imageId: 29'),(24,'Comment # 24 userId: 1, imageId: 23'),(25,'Comment # 25 userId: 2, imageId: 9'),(26,'Comment # 26 userId: 2, imageId: 13'),(27,'Comment # 27 userId: 3, imageId: 6'),(28,'Comment # 28 userId: 1, imageId: 24'),(29,'Comment # 29 userId: 2, imageId: 26'),(30,'Comment # 30 userId: 3, imageId: 12'),(31,'Comment # 31 userId: 2, imageId: 16'),(32,'Comment # 32 userId: 3, imageId: 10'),(33,'Comment # 33 userId: 2, imageId: 29'),(34,'Comment # 34 userId: 3, imageId: 9'),(35,'Comment # 35 userId: 3, imageId: 1'),(36,'Comment # 36 userId: 3, imageId: 19'),(37,'Comment # 37 userId: 3, imageId: 23'),(38,'Comment # 38 userId: 2, imageId: 26'),(39,'Comment # 39 userId: 2, imageId: 12'),(40,'Comment # 40 userId: 3, imageId: 10'),(41,'Comment # 41 userId: 2, imageId: 8'),(42,'Comment # 42 userId: 2, imageId: 17'),(43,'Comment # 43 userId: 1, imageId: 27'),(44,'Comment # 44 userId: 2, imageId: 19'),(45,'Comment # 45 userId: 1, imageId: 28'),(46,'Comment # 46 userId: 3, imageId: 12'),(47,'Comment # 47 userId: 3, imageId: 8'),(48,'Comment # 48 userId: 2, imageId: 4'),(49,'Comment # 49 userId: 1, imageId: 25'),(50,'Comment # 50 userId: 2, imageId: 8'),(51,'Comment # 51 userId: 2, imageId: 7'),(52,'Comment # 52 userId: 2, imageId: 11'),(53,'Comment # 53 userId: 2, imageId: 8'),(54,'Comment # 54 userId: 1, imageId: 25'),(55,'Comment # 55 userId: 3, imageId: 26'),(56,'Comment # 56 userId: 2, imageId: 16'),(57,'Comment # 57 userId: 1, imageId: 12'),(58,'Comment # 58 userId: 3, imageId: 22'),(59,'Comment # 59 userId: 1, imageId: 18'),(60,'Comment # 60 userId: 3, imageId: 6'),(61,'Comment # 61 userId: 1, imageId: 2'),(62,'Comment # 62 userId: 1, imageId: 9'),(63,'Comment # 63 userId: 2, imageId: 10'),(64,'Comment # 64 userId: 2, imageId: 25'),(65,'Comment # 65 userId: 3, imageId: 20'),(66,'Comment # 66 userId: 3, imageId: 30'),(67,'Comment # 67 userId: 3, imageId: 26'),(68,'Comment # 68 userId: 2, imageId: 23'),(69,'Comment # 69 userId: 2, imageId: 18'),(70,'Comment # 70 userId: 2, imageId: 21'),(71,'Comment # 71 userId: 2, imageId: 4'),(72,'Comment # 72 userId: 3, imageId: 23'),(73,'Comment # 73 userId: 2, imageId: 10'),(74,'Comment # 74 userId: 3, imageId: 8'),(75,'Comment # 75 userId: 3, imageId: 15'),(76,'Comment # 76 userId: 1, imageId: 13'),(77,'Comment # 77 userId: 3, imageId: 12'),(78,'Comment # 78 userId: 3, imageId: 12'),(79,'Comment # 79 userId: 3, imageId: 12'),(80,'Comment # 80 userId: 3, imageId: 7'),(81,'Comment # 81 userId: 2, imageId: 10'),(82,'Comment # 82 userId: 2, imageId: 24'),(83,'Comment # 83 userId: 3, imageId: 30'),(84,'Comment # 84 userId: 3, imageId: 14'),(85,'Comment # 85 userId: 3, imageId: 23'),(86,'Comment # 86 userId: 3, imageId: 24'),(87,'Comment # 87 userId: 1, imageId: 4'),(88,'Comment # 88 userId: 2, imageId: 20'),(89,'Comment # 89 userId: 3, imageId: 7'),(90,'Comment # 90 userId: 2, imageId: 28'),(91,'Comment # 91 userId: 3, imageId: 5'),(92,'Comment # 92 userId: 1, imageId: 17'),(93,'Comment # 93 userId: 3, imageId: 23'),(94,'Comment # 94 userId: 2, imageId: 7'),(95,'Comment # 95 userId: 3, imageId: 24'),(96,'Comment # 96 userId: 3, imageId: 28'),(97,'Comment # 97 userId: 1, imageId: 7'),(98,'Comment # 98 userId: 3, imageId: 29'),(99,'Comment # 99 userId: 2, imageId: 6'),(100,'Comment # 100 userId: 3, imageId: 27');
/*!40000 ALTER TABLE `comment_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  CONSTRAINT `image_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image`
--

LOCK TABLES `image` WRITE;
/*!40000 ALTER TABLE `image` DISABLE KEYS */;
INSERT INTO `image` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,3),(22,3),(23,3),(24,3),(25,3),(26,3),(27,3),(28,3),(29,3),(30,3);
/*!40000 ALTER TABLE `image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image_data`
--

DROP TABLE IF EXISTS `image_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `desc` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `image_data_ibfk_1` FOREIGN KEY (`id`) REFERENCES `image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image_data`
--

LOCK TABLES `image_data` WRITE;
/*!40000 ALTER TABLE `image_data` DISABLE KEYS */;
INSERT INTO `image_data` VALUES (1,'file_1_1.jpg','User 1, file 01'),(2,'file_1_2.jpg','User 1, file 02'),(3,'file_1_3.jpg','User 1, file 03'),(4,'file_1_4.jpg','User 1, file 04'),(5,'file_1_5.jpg','User 1, file 05'),(6,'file_1_6.jpg','User 1, file 06'),(7,'file_1_7.jpg','User 1, file 07'),(8,'file_1_8.jpg','User 1, file 08'),(9,'file_1_9.jpg','User 1, file 09'),(10,'file_1_10.jpg','User 1, file 10'),(11,'file_2_1.jpg','User 2, file 01'),(12,'file_2_2.jpg','User 2, file 02'),(13,'file_2_3.jpg','User 2, file 03'),(14,'file_2_4.jpg','User 2, file 04'),(15,'file_2_5.jpg','User 2, file 05'),(16,'file_2_6.jpg','User 2, file 06'),(17,'file_2_7.jpg','User 2, file 07'),(18,'file_2_8.jpg','User 2, file 08'),(19,'file_2_9.jpg','User 2, file 09'),(20,'file_2_10.jpg','User 2, file 10'),(21,'file_3_1.jpg','User 3, file 01'),(22,'file_3_2.jpg','User 3, file 02'),(23,'file_3_3.jpg','User 3, file 03'),(24,'file_3_4.jpg','User 3, file 04'),(25,'file_3_5.jpg','User 3, file 05'),(26,'file_3_6.jpg','User 3, file 06'),(27,'file_3_7.jpg','User 3, file 07'),(28,'file_3_8.jpg','User 3, file 08'),(29,'file_3_9.jpg','User 3, file 09'),(30,'file_3_10.jpg','User 3, file 10');
/*!40000 ALTER TABLE `image_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1),(2),(3);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_data`
--

DROP TABLE IF EXISTS `user_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_data` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_data`
--

LOCK TABLES `user_data` WRITE;
/*!40000 ALTER TABLE `user_data` DISABLE KEYS */;
INSERT INTO `user_data` VALUES (1,'User #1'),(2,'User #2'),(3,'User #3');
/*!40000 ALTER TABLE `user_data` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-10-29 20:02:37
