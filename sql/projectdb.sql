CREATE DATABASE  IF NOT EXISTS `projectdb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `projectdb`;
-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: projectdb
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `access_rules`
--

DROP TABLE IF EXISTS `access_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_rules` (
  `RULE_ID` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `MIN_AGE` tinyint unsigned DEFAULT NULL,
  `OPEN_HOUR` time DEFAULT NULL,
  `CLOSE_HOUR` time DEFAULT NULL,
  PRIMARY KEY (`RULE_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cred`
--

DROP TABLE IF EXISTS `cred`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cred` (
  `USER_ID` int unsigned NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  PRIMARY KEY (`USER_ID`),
  CONSTRAINT `fk_cred_user` FOREIGN KEY (`USER_ID`) REFERENCES `user` (`USER_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inv_status`
--

DROP TABLE IF EXISTS `inv_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_status` (
  `INV_STAT_ID` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `STAT_NAME` varchar(20) NOT NULL,
  PRIMARY KEY (`INV_STAT_ID`),
  UNIQUE KEY `STAT_NAME_UNIQUE` (`STAT_NAME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice`
--

DROP TABLE IF EXISTS `invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice` (
  `INV_ID` int unsigned NOT NULL,
  `MEMB_ID` int NOT NULL,
  `PACKAGE_ID` tinyint unsigned NOT NULL,
  `INV_STAT_ID` tinyint unsigned NOT NULL,
  `INV_PACKAGE_PRICE` decimal(6,2) NOT NULL,
  `INV_START_DATE` date NOT NULL,
  `INV_END_DATE` date NOT NULL,
  `INV_NUMB` varchar(9) NOT NULL,
  `INV_DATE` date NOT NULL,
  `PAID_DATE` date NOT NULL,
  PRIMARY KEY (`INV_ID`),
  UNIQUE KEY `INV_NUMB_UNIQUE` (`INV_NUMB`),
  KEY `MEMB_ID_idx` (`MEMB_ID`),
  KEY `fk_invoice_package_id_idx` (`PACKAGE_ID`),
  KEY `fk_invoice_inv_stat_id_idx` (`INV_STAT_ID`),
  CONSTRAINT `fk_invoice_inv_stat_id` FOREIGN KEY (`INV_STAT_ID`) REFERENCES `inv_status` (`INV_STAT_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_memb_id` FOREIGN KEY (`MEMB_ID`) REFERENCES `member` (`MEMB_ID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_package_id` FOREIGN KEY (`PACKAGE_ID`) REFERENCES `packages` (`PACKAGE_ID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `memb_status`
--

DROP TABLE IF EXISTS `memb_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `memb_status` (
  `MEMB_STATUS_ID` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `MEMB_STATUS_NAME` varchar(45) NOT NULL,
  PRIMARY KEY (`MEMB_STATUS_ID`),
  UNIQUE KEY `MEMB_STATUS_NAME_UNIQUE` (`MEMB_STATUS_NAME`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member` (
  `MEMB_ID` int NOT NULL AUTO_INCREMENT,
  `PACKAGE_ID` tinyint unsigned NOT NULL,
  `MEMB_STATUS_ID` tinyint unsigned NOT NULL,
  `MEMB_FNAME` varchar(50) NOT NULL,
  `MEMB_LNAME` varchar(50) NOT NULL,
  `MEMB_PHONE` varchar(20) DEFAULT NULL,
  `MEMB_EMAIL` varchar(70) NOT NULL,
  `MEMB_DOB` date NOT NULL,
  `MEMB_JOINDATE` date NOT NULL,
  `START_DATE` date DEFAULT NULL,
  `END_DATE` date DEFAULT NULL,
  `NOTES` text,
  PRIMARY KEY (`MEMB_ID`),
  UNIQUE KEY `MEMB_EMAIL_UNIQUE` (`MEMB_EMAIL`),
  KEY `fk_member_package_idx` (`PACKAGE_ID`),
  KEY `fk_member_memb_status_idx` (`MEMB_STATUS_ID`),
  CONSTRAINT `fk_member_memb_status` FOREIGN KEY (`MEMB_STATUS_ID`) REFERENCES `memb_status` (`MEMB_STATUS_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_member_package_id` FOREIGN KEY (`PACKAGE_ID`) REFERENCES `packages` (`PACKAGE_ID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packages` (
  `PACKAGE_ID` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `PACKAGE_NAME` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `PACKAGE_PRICE` decimal(6,2) NOT NULL,
  `PACKAGE_DAYS` smallint unsigned NOT NULL,
  `PACKAGE_DESC` varchar(2500) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`PACKAGE_ID`),
  UNIQUE KEY `PACKAGE_NAME_UNIQUE` (`PACKAGE_NAME`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_transaction`
--

DROP TABLE IF EXISTS `payment_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transaction` (
  `PMT_ID` int unsigned NOT NULL AUTO_INCREMENT,
  `INV_ID` int unsigned NOT NULL,
  `PMT_DATE_TIME` datetime NOT NULL,
  `PROC_CODE` varchar(40) DEFAULT NULL,
  `SUCCESS` tinyint(1) NOT NULL,
  `TOTAL` decimal(7,2) NOT NULL,
  `FEE` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`PMT_ID`),
  KEY `fk_payment_invoice_idx` (`INV_ID`),
  CONSTRAINT `fk_payment_invoice` FOREIGN KEY (`INV_ID`) REFERENCES `invoice` (`INV_ID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `PERM_ID` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `PERM_NAME` varchar(30) NOT NULL,
  PRIMARY KEY (`PERM_ID`),
  UNIQUE KEY `PERM_NAME_UNIQUE` (`PERM_NAME`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `qr_code`
--

DROP TABLE IF EXISTS `qr_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_code` (
  `QR_ID` int NOT NULL,
  `MEMB_ID` int unsigned NOT NULL,
  `RULE_ID` tinyint unsigned NOT NULL,
  PRIMARY KEY (`QR_ID`),
  KEY `fk_qr_member_idx` (`MEMB_ID`),
  KEY `fk_qr_rule_idx` (`RULE_ID`),
  CONSTRAINT `fk_qr_rule` FOREIGN KEY (`RULE_ID`) REFERENCES `access_rules` (`RULE_ID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `SETTING_ID` int NOT NULL AUTO_INCREMENT,
  `USER_ID` int unsigned NOT NULL,
  PRIMARY KEY (`SETTING_ID`),
  UNIQUE KEY `SETTING_ID_UNIQUE` (`SETTING_ID`),
  UNIQUE KEY `USER_ID_UNIQUE` (`USER_ID`),
  CONSTRAINT `fk_user_id` FOREIGN KEY (`USER_ID`) REFERENCES `user` (`USER_ID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `USER_ID` int unsigned NOT NULL AUTO_INCREMENT,
  `PERM_ID` tinyint unsigned NOT NULL,
  `USER_NAME` varchar(50) NOT NULL,
  `USER_FNAME` varchar(50) NOT NULL,
  `USER_LNAME` varchar(50) NOT NULL,
  `USER_EMAIL` varchar(50) NOT NULL,
  `RESET_CODE_HASH` varchar(255) DEFAULT NULL,
  `RESET_CODE_EXPIRES_AT` datetime DEFAULT NULL,
  `RESET_CODE_ATTEMPTS` int NOT NULL DEFAULT '0',
  `RESET_CODE_LAST_SENT_AT` datetime DEFAULT NULL,
  PRIMARY KEY (`USER_ID`),
  UNIQUE KEY `USER_NAME_UNIQUE` (`USER_NAME`),
  UNIQUE KEY `USER_EMAIL_UNIQUE` (`USER_EMAIL`),
  KEY `fk_user_permissions_idx` (`PERM_ID`),
  CONSTRAINT `fk_user_permissions` FOREIGN KEY (`PERM_ID`) REFERENCES `permissions` (`PERM_ID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-12 14:21:40
