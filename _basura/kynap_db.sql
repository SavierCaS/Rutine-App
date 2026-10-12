-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: kynap_db
-- ------------------------------------------------------
-- Server version	8.0.17

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
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `imagen_user` varchar(255) DEFAULT NULL,
  `chat_activado` tinyint(1) DEFAULT '0',
  `mensajes_enviados` int(11) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'Diud Ospino','diud@gmail.com','$2y$10$.ILHJCzD75LAI4V.I1Dcdu9BlaAtGg24J1E.kT2/WVx50YT0yGrke','icon_users/Boy.jpg',1,5,'2025-05-30 18:03:02','2025-06-06 11:06:34'),(2,'Savier','savier@gmail.com','$2y$10$wbHsPArz/A54z8ICzzAkk.oJC9ECtW.w71YYSnZ7zi5rDo8xdAKlW',NULL,0,2,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(3,'daniel','daniel@gmail.com','$2y$10$Seg9QC.Lqv/JBpJSxBSuiOPRWGJtrVuqhvGx.Iod4F7p2QRfxfm4a',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(4,'raul','raul@gmail.com','$2y$10$AEqokFusz6wT256WOJJNB.lliyylo7l18O2auNj7mtqBzkzveUDru',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(5,'casiani','casiani@gmail.com','$2y$10$7NQNo6M2MWA0gmFhHjoA8OaKilOM/bpdC1rFPpIet8kYXNXq9ZK1S',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(6,'Hernan Moreno','hernan@gmail.com','$2y$10$yUTCVz1MejlhToYXtyXEGeiI/OAaEK2w4DplQZdFhd3Jewd7dzzY.',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(7,'Jorge Raul','jorgeraul@gmail.com','$2y$10$XxtaLyhhGHCFZWU1m09/oeY3AxUnNV33w3qY6WQsdWFS5F0xaHLoO',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(8,'Maria Lopez','marialopez@gmail.com','$2y$10$fVKaJ3OtJ/OaWlDaQlcv7eyO9DT5oh0317HeOoG70iyHWcYm6pIj.',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(9,'Davi','david@gmail.com','$2y$10$/KyxAVvUggBhkUqqmc8oV.wFIVG01gAR6klx3c3RSZN/aOLH3J4Su',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(10,'jaime','jaime@gmail.com','$2y$10$XjWu4l/3mpUZDO4lIKR5ouaKkeb9JRFvebYbrlCpCHba/fn6NpdFW',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(11,'lionela mesa','lionela@gmail.com','$2y$10$TIljVunlwVUYaWGeGM6BYuF8QszOxX1TdyelLRcknZqOBmJa2OrR6',NULL,0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(12,'son goku','goku@gmail.com','$2y$10$gYfLkEH0l9Zm21Nm25vcY.o2UPsf8jycvEXK1c7ciVMnQnSetGwK6','icon_users/icon10.jpg',0,0,'2025-05-30 18:03:02','2025-05-30 18:03:02'),(13,'kaoru','kaoru@gmail.com','$2y$10$4Fhnyjv6qmkEwmfaixRBfuTO0UbTnlIm.GesQH3mTYbKMb5yRZj0K','icon_users/Boy.jpg',0,0,'2025-06-06 10:41:14','2025-06-06 10:49:07'),(14,'hana','hana@gmail.com','$2y$10$AtiiIzLlS1SvUQqm6aPYMuy5gsZrY6bTveW2zldV2Y8AY42A1N00.','icon_users/WomenWorked.jpg',0,0,'2025-06-06 10:50:00','2025-06-06 10:50:55'),(15,'Katerine Nuñez','katerine@gmail.com','$2y$10$a7pGMWWdQlKtvd4owNiMLO3JpdJa14v0tkH8BwdBE0WmhFBA4Osqm','icon_users/WomenWorked.jpg',1,1,'2025-06-06 11:39:27','2025-06-06 11:41:54'),(16,'Katerine Mileth','mileht@gmail.com','$2y$10$oDZ27lVlzssV6Wm6Rs4Q9elQHqVdL5oAyEM0G2/ofQVUClp4pJS2q','icon_users/WomenWorked.jpg',1,1,'2025-06-06 11:53:34','2025-06-06 11:56:02'),(17,'smith nuñez','smith@gmail.com','$2y$10$BIiy3rd3Ng/rayGyTuFIZeXnPg68BrLPKhuWCZpFycMlOY07UqUE2','icon_users/Boy.jpg',1,5,'2025-06-06 11:59:46','2025-06-06 12:05:29'),(18,'mbappe','mbappe@gmail.com','$2y$10$2xhndYW8wUqjzlVwRjUGa.HOwAwAOrOtIajzRU1b1abr.2gzUjabu',NULL,0,0,'2025-06-11 16:17:24','2025-06-11 16:17:24'),(19,'lionelamesatwo','lionelamesatwo@gmail.com','$2y$10$ETISYraGSM5w/BRf1HxTDexKd4bD0Ehe7cTNX1gdRhRUzNpz.0ZgK','icon_users/Skull_Boy.jpg',1,5,'2025-06-11 17:13:11','2025-06-11 18:29:56'),(20,'mane','mane@gmail.com','$2y$10$jxkwmn0Csu4GQtL0WZLzxeqZGsNCXdkIZOs4Z5c1.x2OjRFTHP4RW','icon_users/Gygamen_Stone.jpg',1,5,'2025-06-11 21:53:04','2025-06-11 22:03:35'),(21,'mbapeeex','mbapeeex@gmail.com','$2y$10$oGzQPEoCJPA5G0i1SqNoV.jLs7UbSY5mVYkT4.FZ.YiutHgVxinim','icon_users/BlueMenAdmin.jpg',1,5,'2025-06-11 23:08:35','2025-06-11 23:15:18'),(22,'mbape23','mbape123@gmail.com','$2y$10$twCqw8nl0aZnPlcsIeTWiu4Ay58Ch.QuFnBcfkmvxqY5qYdM5GNA6','icon_users/Queen.jpg',1,5,'2025-06-11 23:16:34','2025-06-11 23:21:08');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recomendaciones`
--

DROP TABLE IF EXISTS `recomendaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recomendaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recomendacion` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_usuario_recomendacion` (`user_id`),
  CONSTRAINT `fk_usuario_recomendacion` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recomendaciones`
--

LOCK TABLES `recomendaciones` WRITE;
/*!40000 ALTER TABLE `recomendaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `recomendaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `peso` int(11) NOT NULL,
  `altura` int(11) NOT NULL,
  `objetivo` varchar(255) DEFAULT NULL,
  `nivel_actividad` varchar(255) DEFAULT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `condiciones` text,
  `alergias` text,
  `preferencia` varchar(255) DEFAULT NULL,
  `agua` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `edad` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,78,187,'ganar_musculo','ligero','masculino','','','cualquierCosa',4,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(2,2,87,190,'ganar_musculo','moderado','masculino','','','cualquierCosa',5,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(3,3,54,32,'mantener_peso','ligero','masculino','','','cualquierCosa',3,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(4,4,5,45,'mantener_peso','moderado','masculino','','','cualquierCosa',44,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(5,5,54,454,'perder_peso','activo','masculino','','','cualquierCosa',45,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(6,6,78,198,'ganar_musculo','moderado','masculino','','','cualquierCosa',4,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(7,7,79,187,'ganar_musculo','moderado','masculino','','','cualquierCosa',7,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(8,8,68,160,'perder_peso','sedentario','femenino','','','cualquierCosa',8,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(9,10,70,180,'ganar_musculo','ligero','masculino','','','cualquierCosa',6,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(10,11,80,145,'perder_peso','sedentario','masculino','diabetes, hipertension, colesterol','Pendejadas','vegetariano',100,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(11,12,100,195,'ganar_musculo','activo','masculino','','','cualquierCosa',5,'2025-05-30 18:03:37','2025-05-30 18:03:37',0),(12,13,65,178,'mantener_peso','moderado','masculino','','','cualquierCosa',7,'2025-06-06 10:48:05','2025-06-06 10:48:05',0),(13,14,60,173,'ganar_musculo','moderado','femenino','','','cualquierCosa',5,'2025-06-06 10:50:35','2025-06-06 10:50:35',0),(14,15,65,170,'mantener_peso','moderado','femenino','','','cualquierCosa',5,'2025-06-06 11:39:52','2025-06-06 11:39:52',0),(15,16,65,167,'ganar_musculo','ligero','femenino','','','cualquierCosa',5,'2025-06-06 11:54:03','2025-06-06 11:54:03',0),(16,17,63,172,'ganar_musculo','moderado','masculino','','','cualquierCosa',5,'2025-06-06 12:00:07','2025-06-06 12:00:07',0),(17,19,400,120,'perder_peso','sedentario','masculino','','','cualquierCosa',500,'2025-06-11 17:13:54','2025-06-11 17:13:54',0),(18,20,69,179,'ganar_musculo','moderado','masculino','','','cualquierCosa',5,'2025-06-11 21:53:32','2025-06-11 21:53:32',23),(19,21,70,175,'ganar_musculo','ligero','masculino','','','cualquierCosa',5,'2025-06-11 23:09:12','2025-06-11 23:09:12',20),(20,22,70000,1500,'ganar_musculo','activo','femenino','diabetes, hipertension, colesterol','mani','vegetariano',20000,'2025-06-11 23:17:48','2025-06-11 23:17:48',1500);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-11 23:28:04
