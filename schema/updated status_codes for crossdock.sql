CREATE DATABASE  IF NOT EXISTS `test_exspeed` /*!40100 DEFAULT CHARACTER SET latin1 COLLATE latin1_general_ci */;
USE `test_exspeed`;
-- MySQL dump 10.13  Distrib 5.6.13, for osx10.6 (i386)
--
-- Host: strongtco.dyndns.org    Database: test_exspeed
-- ------------------------------------------------------
-- Server version	5.6.16

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
-- Table structure for table `exp_status_codes`
--

DROP TABLE IF EXISTS `exp_status_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exp_status_codes` (
  `STATUS_CODES_CODE` int(11) NOT NULL AUTO_INCREMENT,
  `SOURCE` enum('shipment','load','stop') COLLATE latin1_general_ci NOT NULL,
  `STATUS` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `DESCRIPTION` varchar(90) COLLATE latin1_general_ci DEFAULT NULL,
  `BEHAVIOR` enum('entry','assign','available','complete','manifest','dispatch','docked','terminal','late','arrive dock','depart dock','arrive stop','depart stop','arrive shipper','depart shipper','arrive cons','depart cons','arrshdock','depshdock','arrrecdock','deprecdock','picked','dropped','cancel','approved','billed','admin','other') COLLATE latin1_general_ci NOT NULL DEFAULT 'entry',
  `PREVIOUS` varchar(45) COLLATE latin1_general_ci DEFAULT NULL,
  `CREATED_DATE` timestamp NULL DEFAULT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CHANGED_DATE` timestamp NULL DEFAULT NULL,
  `CHANGED_BY` int(11) DEFAULT NULL,
  PRIMARY KEY (`STATUS_CODES_CODE`),
  UNIQUE KEY `STATUS_CODES_CODE_UNIQUE` (`STATUS_CODES_CODE`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exp_status_codes`
--

LOCK TABLES `exp_status_codes` WRITE;
/*!40000 ALTER TABLE `exp_status_codes` DISABLE KEYS */;
INSERT INTO `exp_status_codes` VALUES (1,'shipment','Entered','Freight bill entered','entry',NULL,'2014-02-28 22:47:58',5,'2014-03-01 06:00:13',5),(3,'shipment','Ready Dispatch','Freight Ready to Dispatch','assign','1,5,6','2014-02-28 22:48:55',5,'2014-10-01 22:08:11',5),(4,'shipment','Cancelled','Cancelled','cancel',NULL,'2014-03-01 00:03:08',5,'2014-03-01 05:59:48',5),(5,'shipment','Dispatched','Dispatched','dispatch','3,36','2014-03-01 02:07:56',6,'2014-12-03 18:27:36',6),(6,'shipment','Picked','Picked','picked','5','2014-03-24 21:58:34',6,'2014-05-06 04:06:30',5),(9,'load','Entered','Load Entered','entry','29','2014-04-18 19:25:34',5,'2014-09-17 19:24:11',5),(10,'load','Dispatched','Load Dispatched','dispatch','9,29','2014-04-18 19:27:33',5,'2014-05-11 20:55:28',5),(13,'load','Cancelled','Cancelled','cancel',NULL,'2014-04-18 19:49:47',5,'2014-04-18 19:49:47',5),(14,'stop','Entered','Stop Entered','entry',NULL,'2014-04-19 15:10:54',5,'2014-04-19 15:10:54',5),(15,'stop','Picked','Stop completed','complete','14','2014-04-19 15:13:37',5,'2014-04-24 02:13:16',5),(16,'stop','Dropped','Stop completed','complete','14','2014-04-19 15:14:25',5,'2014-04-29 04:18:46',5),(19,'load','Complete','Load Complete','complete','24,35,40','2014-04-29 03:59:04',6,'2014-12-15 08:08:58',6),(20,'shipment','Delivered','Shipment delivered, not approved','dropped','6,31','2014-05-03 20:07:59',5,'2014-08-22 03:16:36',5),(21,'load','Arrive Shipper','Arrives At The Shipper','arrive shipper','10,22,24,30,38,40','2014-05-03 21:29:16',5,'2014-12-03 21:48:57',6),(22,'load','Depart Shipper','Departed The Shipper','depart shipper','10,21,22,24,30,38,40','2014-05-03 21:29:57',5,'2014-12-03 21:59:44',6),(23,'load','Arrive Consignee','Arrived At The Consignee','arrive cons','22,24,38,40','2014-05-03 21:30:39',5,'2014-12-03 21:48:29',6),(24,'load','Depart Consignee','Departed The Consignee','depart cons','22,23,24,38,40','2014-05-03 21:31:13',5,'2014-12-03 21:49:54',6),(28,'stop','Complete','Stop completed','complete','14','2014-05-06 08:39:49',5,'2014-05-06 08:39:49',5),(29,'load','Send Freight Agreement','Send Carrier Freight Agreement','manifest','9','2014-05-11 20:48:52',5,'2014-09-23 20:07:56',5),(30,'load','Depart Stop','Departed Stop','depart stop','10,35','2014-05-24 04:47:45',5,'2014-10-04 23:50:22',5),(31,'shipment','Approved','Approved for billing, changes locked','approved','20','2014-08-22 03:00:33',5,'2014-08-22 03:05:42',5),(32,'shipment','Billed','Sent to Quickbooks','billed','31','2014-08-22 03:01:15',5,'2014-08-22 03:05:52',5),(33,'load','Approved','Approved for payment, changes locked','approved','19','2014-09-22 00:01:14',5,'2014-09-22 00:01:14',5),(34,'load','Paid','Sent to Quickbooks','billed','33','2014-09-22 00:01:57',5,'2014-09-22 00:01:57',5),(35,'load','Arrive Stop','Arrived At Stop','arrive stop','10,22,24,40','2014-10-04 23:49:27',5,'2014-12-11 20:24:27',6),(36,'shipment','Docked','Docked at crossdock','docked','6','2014-12-03 18:26:58',6,'2014-12-03 18:26:58',6),(37,'load','Arrive Shipping Dock','Arrive Shipping Dock','arrshdock','10,22,24,30,38,40','2014-12-03 21:24:05',6,'2014-12-03 21:38:55',6),(38,'load','Depart Shipping Dock','Depart Shipping Dock','depshdock','10,22,24,30,37,38,40','2014-12-03 21:26:27',6,'2014-12-03 22:20:15',6),(39,'load','Arrive Receiving Dock','Arrive Receiving Dock','arrrecdock','22,24,38,40','2014-12-03 21:28:31',6,'2014-12-03 22:23:27',6),(40,'load','Depart Receiving Dock','Depart Receiving Dock','deprecdock','22,24,38,39,40','2014-12-03 21:30:07',6,'2014-12-03 21:32:18',6);
/*!40000 ALTER TABLE `exp_status_codes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-12-16  9:18:19
