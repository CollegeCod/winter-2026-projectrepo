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
-- Dumping data for table `invoice`
--

LOCK TABLES `invoice` WRITE;
/*!40000 ALTER TABLE `invoice` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-25 14:18:07
