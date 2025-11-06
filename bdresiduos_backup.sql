-- MySQL dump 10.13  Distrib 9.3.0, for Win64 (x86_64)
--
-- Host: localhost    Database: bdresiduos
-- ------------------------------------------------------
-- Server version	9.3.0

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
-- Table structure for table `barrio`
--

DROP TABLE IF EXISTS `barrio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barrio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_barrio` varchar(100) DEFAULT NULL,
  `id_comuna` int DEFAULT NULL,
  `zona` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_comuna` (`id_comuna`),
  CONSTRAINT `barrio_ibfk_1` FOREIGN KEY (`id_comuna`) REFERENCES `comuna` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1386 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barrio`
--

LOCK TABLES `barrio` WRITE;
/*!40000 ALTER TABLE `barrio` DISABLE KEYS */;
INSERT INTO `barrio` VALUES (100,'LAS AMERICAS',1,1),(101,'SAN JOSE',1,1),(102,'AVDA SANTANDER',1,1),(103,'CENTRO',1,1),(104,'SAN ANDRES',1,1),(105,'LOS DOS PUENTES',1,1),(106,'CARACHA',1,1),(107,'SAN AGUSTIN CENTRO',1,1),(108,'LOS ALAMOS',2,1),(109,'MARCOS DE LA ROSA',1,1),(110,'EL OBRERO',1,1),(111,'SAN ANDRESITO',1,1),(112,'EL PORTALITO',1,1),(113,'AVDA ECUADOR',1,1),(114,'CRISTO REY',1,1),(115,'LA PANADERIA',1,1),(116,'SANTIAGO',1,1),(117,'BOMBONA',1,1),(118,'LA NORMAL',1,1),(119,'EL CHURO',1,1),(120,'EL CILINDRO',1,1),(121,'HULLAGUANGA',1,1),(130,'JULIAN BUCHELI',2,1),(131,'ATAHUALPA',2,1),(132,'AVDA COLOMBIA',2,1),(133,'LOS OLIVOS',2,1),(134,'EL PRADO',2,1),(135,'EL RECUERDO',2,1),(136,'FATIMA',2,1),(137,'JAVERIANO',2,1),(138,'LAS LUNAS',2,1),(139,'LAS VIOLETAS',2,1),(140,'LOS BALCONES',2,1),(141,'MEDARDO BUCHELI',2,1),(142,'NAVARRETE',2,1),(143,'PARQUE BOLIVAR',2,1),(144,'SALOMON',2,1),(145,'SAN MIGUEL',2,1),(146,'VILLA LUCIA',2,1),(147,'NORMANDIA',2,1),(148,'AVDA BOYACA',2,1),(149,'AIRE LIBRE',2,1),(150,'CHAMPAGNAT',2,1),(160,'VILLA ALEJANDRIA',3,1),(161,'ARNULFO GUERRERO',3,1),(162,'BELISARIO BETANCOURTH',3,1),(163,'CAICEDONIA',3,1),(164,'CASALOMA',3,1),(165,'EL EJIDO',3,1),(166,'GUAMUEZ',3,1),(167,'JOSE ANTONIO GALAN',3,1),(168,'LA ESMERALDA',3,1),(170,'LA ESTRELLA',3,1),(171,'LAS BRISAS',3,1),(172,'LAS LAJAS',3,1),(173,'LAS MERCEDES',3,1),(174,'LOS PINOS',3,1),(175,'PIE DE CUESTA',3,1),(176,'POPULAR',3,1),(177,'ROSAL DE ORIENTE',3,1),(178,'SANTA BARBARA',3,1),(179,'SANTA CATALINA',3,1),(180,'SANTA MONICA',3,1),(181,'VILLAFLOR I',3,1),(182,'VILLAFLOR II',3,1),(183,'BAVARIA',3,1),(184,'SIETE DE AGOSTO',3,1),(185,'LOS ARRAYANES CANCHALA',3,1),(186,'VILLA ORIENTE',3,1),(187,'MERCEDARIO',3,1),(200,'CAMILO TORRES',3,1),(201,'VILLAS DEL SOL',4,1),(202,'ALTOS DEL CAMPO',4,1),(203,'AVDA IDEMA',4,1),(204,'BELEN',4,1),(205,'EL BERNAL',4,1),(206,'BETANIA',4,1),(207,'CHILE',4,1),(208,'DOCE DE OCTUBRE 1 Y 2',4,1),(209,'EL PORVENIR',4,1),(210,'EL TEJAR',4,1),(211,'EL TRIUNFO',4,1),(212,'LA HABANA',4,1),(213,'LA VICTORIA',9,1),(214,'LAUREANO GOMES',4,1),(215,'LORENZO',4,1),(216,'LOS ELICEOS',4,1),(217,'MADRIGAL',5,1),(218,'MIRAFLORES 1 Y 2',4,1),(219,'PRAGA',4,1),(220,'PUERTAS DEL SOL',4,1),(221,'RINCON COLONIAL',4,1),(222,'SAN JUAN DE LOS PASTOS',4,1),(223,'SANTA FE',4,1),(224,'SENDOYA',4,1),(225,'VILLA DOCENTE',4,1),(226,'VILLA OLIMPICA',4,1),(227,'LAS FERIAS',5,1),(229,'SAN GERMAN',4,1),(240,'VIVIENDA CRISTIANA',5,1),(241,'ALTOS DE CHAPALITO',5,1),(242,'ANTONIO NARIÑO',5,1),(243,'CANTARANA',5,1),(244,'CHAMBU 1',5,1),(245,'CHAPAL',5,1),(246,'CIUDAD JARDIN',6,1),(247,'EL PILAR',5,1),(248,'EL PROGRESO',5,1),(249,'EL REMANSO',5,1),(250,'EMILIO BOTERO',5,1),(251,'LA MINGA',5,1),(252,'LA ROSA',5,1),(253,'LA VEGA',5,1),(254,'MARIA ISABEL 1 Y 2',5,1),(255,'POTRERILLO',5,1),(256,'PRADOS DEL SUR',5,1),(257,'SAN MARTIN',5,1),(258,'SANTA CLARA',5,1),(259,'VENECIA',5,1),(260,'VILLA DEL RIO',5,1),(261,'LOS ROBLES',5,1),(280,'AGUALONGO',6,1),(281,'ALTAMIRA',6,1),(282,'BACHUE',6,1),(283,'CAICEDO',6,1),(284,'COOPERATIVA POPULAR NARIÑENSE',6,1),(285,'EL ESTADIO',6,1),(286,'GRANADA',6,1),(287,'INEM',6,1),(288,'LA CRUZ',6,1),(289,'LA PALMA',6,1),(290,'FUNDADORES',6,1),(291,'MIJITAYO 1',6,1),(292,'NIZA 1',6,1),(293,'NIZA 2',6,1),(294,'NUEVA COLOMBIA',6,1),(295,'QUITO LOPEZ',6,1),(296,'SAN CARLOS',6,1),(297,'SAN SEBASTIAN',6,1),(298,'SANTA ISABEL',6,1),(299,'LA PAZ',4,1),(300,'SUMATAMBO',6,1),(301,'TAMASAGRA 1',6,1),(302,'LUIS CARLOS GALAN',6,1),(303,'VILLA DE LOS RIOS',6,1),(310,'VILLA VERGEL',7,1),(311,'ACHALAY',7,1),(312,'CAPUSIGRA',7,1),(313,'CASTILLOS DEL NORTE',7,1),(314,'BOSQUE',7,1),(315,'EL EDEN',7,1),(316,'EL RINCON DE LA AURORA',7,1),(317,'FRANCISCO DE LA VILLOTA',7,1),(318,'LA PRIMAVERA',7,1),(319,'LAS ACACIAS',7,1),(320,'LOS ANDES',7,1),(321,'LOS EXAGONOS',7,1),(322,'LOS ROSALES 1',7,1),(323,'LOS ROSALES 2',7,1),(324,'SAN FELIPE ',7,1),(325,'SAN IGNACIO',7,1),(326,'SANTA MARIA',7,1),(327,'VILLA AURORA',7,1),(328,'VILLA CAMPANELA',7,1),(329,'VILLA SOFIA',7,1),(340,'GUALCALOMA',8,1),(341,'ALTOS DE LA COLINA',8,1),(342,'ARCO IRIS',8,1),(343,'BELLO HORIZONTE',8,1),(344,'COLON',8,1),(345,'COLPATRIA',8,1),(346,'JORGE GIRALDO RESTREPO',8,1),(347,'LA CASTELLANA',8,1),(348,'LA CUESTA',8,1),(349,'LAS MARGARITAS',8,1),(350,'LOS FRAILEJONES',8,1),(351,'LOS LAURELES',8,1),(352,'MARILUZ 1 Y 2',8,1),(353,'MIRA VALLE',8,1),(354,'PANAMERICANO',8,1),(355,'PANORAMICO I',8,1),(356,'PRADOS DEL OESTE',8,1),(357,'QUINTAS DE SAN PEDRO',8,1),(358,'SAN DIEGO',8,1),(359,'SAN JUAN DE DIOS',8,1),(360,'SAN VICENTE',8,1),(361,'SINDAMANOY',8,1),(362,'TORRES DE PUBENZA',8,1),(363,'VERACRUZ',8,1),(364,'VILLAS DE SAN RAFAEL',8,1),(365,'SAN PEDRO',8,1),(366,'VILLA VICTORIA',4,1),(367,'ANGANOY',8,1),(368,'SALAZAR MEJIA',8,1),(369,'EL ARROYO',8,1),(380,'CAMINO REAL',9,1),(381,'CASTILLA',9,1),(382,'TOROBAJO',9,1),(383,'TOROBAJO LA VICTORIA',9,1),(384,'EL CERAMICO',9,1),(385,'EL DORADO',9,1),(386,'EL MIRADOR',9,1),(388,'SAN ANTONIO DE PADUA',9,1),(391,'EL REFUGIO',9,1),(392,'FIGUEROA',9,1),(393,'JOSE IGNACIO ZARAMA',9,1),(394,'JUAN 23',9,1),(395,'LA COLINA',9,1),(396,'LAS CUADRAS',9,1),(397,'LOS NOGALES',9,1),(400,'LUIS BRAND',9,1),(401,'MANACA',9,1),(402,'MARIDIAZ',9,1),(403,'MARCELLA',9,1),(404,'MORASURCO',9,1),(405,'PALERMO',9,1),(406,'PANDIACO',9,1),(407,'PINOS DEL NORTE',9,1),(408,'RIVIERA',9,1),(409,'SANTA ANA',9,1),(410,'SAÑUDO',9,1),(411,'TEQUENDAMA',9,1),(412,'TERRANOVA',9,1),(413,'TERRAZAS DE BRICEÑO',9,1),(414,'TITAN',9,1),(415,'UNIVERSITARIO',9,1),(416,'VILLA CAMPESTRE',9,1),(417,'VILLA DEL PARQUE',9,1),(418,'VILLA MARIA',9,1),(419,'PARQUE INFANTIL',9,1),(420,'POLVORIN',9,1),(421,'AVDA ESTUDIANTES',9,1),(422,'NUEVO AMANECER',9,1),(440,'ARANDA',10,1),(441,'AVDA ORIENTAL',10,1),(442,'BELLA VISTA',10,1),(443,'BUENOS AIRES',10,1),(444,'EL CEMENTERIO',10,1),(445,'DESTECHADOS',10,1),(446,'EL FUTURO',10,1),(447,'LA ESPERANZA',10,1),(448,'LIBERTAD',10,1),(449,'LA LOMA',10,1),(450,'NIÑO JESUS DE PRAGA',10,1),(451,'NUEVA ARANDA',10,1),(452,'NUEVO HORIZONTE',10,1),(453,'NUEVO SOL',10,1),(454,'OCHO DE MARZO',10,1),(455,'PEDAGOGICO',10,1),(456,'PRADOS DEL NORTE',10,1),(457,'QUEBRADA GALLINACERA',10,1),(458,'QUILLOCTOCTO',10,1),(459,'RIO BLANCO',10,1),(460,'SAN ALBANO',10,1),(461,'SOL DE ORIENTE',10,1),(462,'VILLA GUERRERO',10,1),(463,'VILLA NUEVA',10,1),(464,'VILLAS DEL NORTE',10,1),(465,'MARQUETALIA',10,1),(466,'BARRIO CHINO',10,1),(467,'LA COMPUERTA',10,1),(468,'EL RINCON DEL ROSARIO',10,1),(469,'LOMA DEL CARMEN',10,1),(470,'EL DIVINO NIÑO',10,1),(480,'EL COMUN',11,1),(481,'ALAMEDA I Y II',11,1),(482,'AQUINE 1',11,1),(483,'BELALCAZAR',11,1),(484,'CENTENARIO',11,1),(485,'CIUDAD REAL',11,1),(486,'CORAZON DE JESUS',11,1),(487,'EL CALVARIO',11,1),(488,'EL CORRALITO',11,1),(490,'EL CIVIL',11,1),(491,'LA LOMITA',11,1),(492,'LOS ALCAZARES',11,1),(493,'RINCON DEL PARAISO',11,1),(494,'SANTA MATILDE',10,1),(495,'VILLA ELENA',11,1),(496,'LA FLORESTA',11,1),(497,'OJO DE AGUA',11,1),(498,'VILLA JAZMIN',11,1),(509,'VILLA ANGELA',12,1),(510,'VILLA RECREO',12,1),(511,'BALCONES DEL OESTE',12,1),(512,'CARLOS PIZARRO',12,1),(513,'EL MANANTIAL',12,1),(514,'EL PARAISO',12,1),(515,'FRAY EZEQUIEL MORENO',12,1),(516,'GUALCALA',12,1),(517,'CAROLINA',12,1),(518,'LA FLORIDA',12,1),(519,'JOSEFINA',12,1),(520,'MARIA PAZ',12,1),(521,'MONSERRATE',12,1),(522,'PARQUE DE BAVIERA',12,1),(523,'PUCALPA 1, 2 Y 3',12,1),(524,'SAN DIEGO NORTE',12,1),(525,'EL SENA',12,1),(526,'SIMON BOLIVAR',12,1),(527,'SINDAGUA',12,1),(528,'VILLA ADRIANA MARIA',12,1),(529,'SALIDA AL NORTE',12,1),(600,'SAN JOSE',13,2),(601,'BUESAQUILLO ALTO',13,2),(602,'VILLA JULIA BUESAQUILLO',13,2),(603,'VDA TAMBOLOMA BUESAQUILLO',13,2),(604,'SAN FRANCISCO BUESAQUILLO',13,2),(605,'LA HUECADA BUESAQUILLO',13,2),(606,'LA ALIANZA BUESAQUILLO',13,2),(607,'EL CARMELO BUESAQUILLO',13,2),(608,'BUESAQUILLO CENTRO',13,2),(609,'PEJENDINO REYES BUESAQUILLO',13,2),(610,'SAN FELIPE OBONUCO',22,2),(611,'CUJACAL CENTRO BUESAQUILLO',13,2),(612,'VDA CUJACAL ALTO BUESAQUILLO',13,2),(613,'CUJACAL BAJO BUESAQUILLO',13,2),(614,'VDA PUENTE TABLA BUESAQUILLO',13,2),(615,'VILLA DEL ROSARIO',10,2),(616,'VDA CUBIJAN BAJO CATAMBUCO',14,2),(617,'VDA EL CAMPANERO CATAMBUCO',14,2),(618,'PUENTE RIO BOBO CATAMBUCO',14,2),(619,'VDA SAN ANTONIO DE CASANARE ',14,2),(620,'VDA SAN ANTONIO DE ACUYUYO',14,2),(621,'VDA SAN ISIDRO CATAMBUCO',14,2),(622,'VDA LA MERCED CATAMBUCO',14,2),(623,'VDA SAN JOSE DE CASANARE CATAMBUCO',14,2),(624,'VDA LA VICTORIA CATAMBUCO',14,2),(625,'VDA CRUZ DE AMARILLO CATAMBUCO',14,2),(626,'VDA CHAVEZ CATAMBUCO',14,2),(627,'VDA BELLAVISTA CATAMBUCO',14,2),(628,'VDA SAN JOSE DE CATAMBUCO',14,2),(629,'VDA BOTANA CATAMBUCO',14,2),(630,'ALTO SANTA MARIA CATAMBUCO',14,2),(631,'VDA ALTO CASANARE CATAMBUCO',14,2),(632,'VDA CUBIJAN ALTO CATAMBUCO',14,2),(633,'VDA GUADALUPE CATAMBUCO',14,2),(634,'VDA BOTANILLA CATAMBUCO',14,2),(635,'CATAMBUCO CENTRO',14,2),(636,'VDA GUALMATAN ALTO CATAMBUCO',24,2),(637,'VDA FRAY EZEQUIEL CATAMBUCO',14,2),(638,'JONGOVITO',29,2),(639,'SAN MIGUEL DE JONGOVITO',29,2),(640,'VDA EL ESTERO EL ENCANO',15,2),(641,'VDA CAMPO ALEGRE EL ENCANO',15,2),(642,'VDA CASAPAMBA EL ENCANO',15,2),(643,'VDA EL CARRIZO EL ENCANO',15,2),(644,'EL ENCANO CENTRO',15,2),(645,'VDA EL MOTILON EL ENCANO',15,2),(646,'VDA EL PUERTO EL ENCANO',15,2),(647,'VDA EL SOCORRO EL ENCANO',15,2),(648,'VDA BELLAVISTA EL ENCANO',15,2),(649,'VDA ROMERILLO EL ENCANO',15,2),(650,'VDA SANTA CLARA EL ENCANO',15,2),(651,'VDA SANTA ROSA EL ENCANO',15,2),(652,'VDA SANTA ISABEL EL ENCANO',15,2),(653,'VDA SAN JOSE EL ENCANO',15,2),(654,'VDA EL NARANJAL EL ENCANO',15,2),(655,'VDA SANTA LUCIA EL ENCANO',15,2),(656,'VDA LOS AFILADORES EL ENCANO',15,2),(657,'VDA MOJODINOY EL ENCANO',15,2),(658,'VDA SANTA TERESITA EL ENCANO',15,2),(659,'VDA RAMOS EL ENCANO',15,2),(660,'VDA EL EDEN GENOY',16,2),(661,'VDA PULLITO PAMBA  GENOY',16,2),(662,'VDA NUEVA CAMPIÑA GENOY',16,2),(663,'VDA LA COCHA GENOY',16,2),(664,'GENOY CENTRO',16,2),(665,'VDA CHARGUAYACO GENOY',16,2),(666,'VDA CASTILLO LOMA GENOY',16,2),(667,'VDA AGUAPAMBA GENOY',16,2),(668,'VDA BELLAVISTA GENOY',16,2),(669,'VDA LOS ARRAYANES CALDERA',17,2),(670,'ALTO CALDERA',17,2),(671,'VDA ALTO ARRAYANES',17,2),(672,'LA CALDERA CENTRO',17,2),(673,'VDA LA PRADERA LA CALDERA',17,2),(674,'VDA SAN ANTONIO LA CALDERA',17,2),(676,'VDA EL PURGATORIO CABRERA',25,2),(677,'VDA LA PLAYA LA LAGUNA',26,2),(678,'VDA SAN LUIS LA LAGUNA',26,2),(679,'CORREGIMIENTO SAN FERNANDO',18,2),(680,'VDA LA PAZ CABRERA',25,2),(681,'LA LAGUNA CENTRO',26,2),(682,'VDA EL BARBERO LA LAGUNA',26,2),(683,'VDA DUARTE CABRERA',25,2),(685,'VDA CABRERA CENTRO',25,2),(686,'VDA BUENA VISTA CABRERA',25,2),(687,'VDA ALTO SAN PEDRO LA LAGUNA',26,2),(688,'VDA AGUAPAMBA LA LAGUNA',26,2),(689,'EL ROSARIO',4,1),(693,'VDA JAMONDINO',27,2),(694,'VDA VILLA MARIA MAPACHICO',19,2),(695,'VDA SAN CAYETANO MAPACHICO',19,2),(696,'MAPACHICO CENTRO',19,2),(697,'VDA EL ROSAL MAPACHICO',19,2),(698,'VDA BRICEÑO MAPACHICO',19,2),(699,'VDA SAN JUAN DE ANGANOY',19,2),(700,'VDA SAN JUAN BAJO MORASURCO',20,2),(701,'VDA TOSOABY MORASURCO',20,2),(702,'VDA SAN JUAN ALTO MORASURCO',20,2),(703,'VDA PINASACO MORASURCO',20,2),(704,'VDA CHACHATOY MORASURCO',20,2),(705,'VDA CHACHATOY BAJO MORASURCO',20,2),(706,'VDA LA JOSEFINA MORASURCO',20,2),(707,'VDA DAZA MORASURCO',20,2),(708,'JUANOY ALTO MORASURCO',20,2),(709,'JUANOY BAJO MORASURCO',20,2),(710,'EL ALGIBE MORASURCO',20,2),(711,'VDA SANTA RITA MORASURCO',20,2),(712,'LOS SAUCES MORASURCO',20,2),(713,'TESCUAL MORASURCO',20,2),(714,'SAN ANTONIO DE ARANDA',10,1),(721,'VDA LAS MALBAS OBOCUNO',22,2),(722,'MOSQUERA OBONUCO',22,2),(723,'VDA RECUERDO OBONUCO',22,2),(724,'VDA SANTANDER OBONUCO',22,2),(725,'VDA SAN ANTONIO OBONUCO',22,2),(726,'OBONUCO CENTRO',22,2),(727,'VDA BELLAVISTA OBONUCO',22,2),(728,'VDA LA PLAYA OBONUCO',22,2),(729,'VDA JURADO SANTA BARBARA',23,2),(730,'VDA CONCEPCION ALTO SANTA BARBARA',23,2),(731,'VDA CONCEPCION BAJO SANTA BARBARA',23,2),(732,'VDA BAJO CASANARE EL SOCORRO',30,2),(733,'VDA EL CARMEN EL SOCORRO',30,2),(734,'VDA CEROTAL SANTA BARBARA',23,2),(735,'ALTO SANTA BARBARA',23,2),(736,'CORREGIMIENTO EL SOCORRO',30,2),(737,'VDA LOS ALIZALES SANTA BARBARA',23,2),(738,'VDA LA ESPERANZA SANTA BARBARA',23,2),(739,'VDA LAS ENCINAS SANTA BARBARA',23,2),(740,'VDA LAS IGLESIAS SANTA BARBARA',23,2),(741,'VDA LOS ANGELES SANTA BARBARA',23,2),(742,'VDA SAN GABRIEL CORREGIMIENTO EL SOCORRO',30,2),(743,'SANTA BARBARA CENTRO',23,2),(744,'VDA EL DIVINO NIÑO SANTA BARBARA',23,2),(745,'LA MERCED RELLENO SANITARIO',11,1),(749,'LOS LIRIOS',8,1),(750,'GUALMATAN CENTRO',24,2),(751,'VDA LA VOCACIONAL GUALMATAN',24,2),(752,'VDA HUERTECILLA GUALMATAN',24,2),(753,'VDA SECTOR FATIMA GUALMATAN',24,2),(754,'VDA SECTOR NUEVA BETANIA GUALMATAN',24,2),(755,'VERSALLES',9,1),(756,'AVDA PANAMERICANA',1,1),(757,'RINCON DE PASTO',10,1),(758,'CUJACAL',13,2),(759,'PORTAL DE ARANDA',10,1),(760,'LA AURORA',7,1),(761,'MOCONDINO',28,2),(762,'CANCHALA',3,1),(763,'PRADOS DEL NIZA',6,1),(764,'PUERRES',3,2),(765,'JUAN PABLO II',10,1),(766,'SAN JUAN BOSCO',2,1),(767,'LOS CRISTALES',5,1),(768,'LAS ORQUIDEAS',12,1),(769,'AQUINE 2',11,1),(770,'AQUINE 3',11,1),(771,'MARILUZ 3',8,1),(772,'AQUINE 4',11,1),(773,'PARANA',9,1),(774,'TAMASAGRA 2',6,1),(775,'TAMASAGRA 3',6,1),(776,'PANORAMICO II',8,1),(777,'CHAMBU 2',5,1),(778,'MIJITAYO 2',6,1),(779,'CAMPIÑA DE ORIENTE',12,1),(780,'PORTAL DEL NORTE',10,1),(781,'LOS SAUCES',9,1),(782,'SANTA ANITA',6,1),(783,'GILBERTO PABON',6,1),(784,'LAS LUNAS 2',5,1),(785,'DOLORES LA LAGUNA',26,2),(786,'LA INDEPENDENCIA',10,1),(787,'REMANSOS DEL NORTE',8,1),(788,'FRAY EZEQUIEL',5,1),(900,'JERUSALEN',6,1),(901,'Ninguno',0,1),(903,'CORREGIMIENTO DE CABRERA',25,2),(904,'CIUDADELA INVIPAS',14,1),(905,'PUENTE PUEYO',2,1),(906,'SAN LUS',12,1),(907,'PINASACO',9,1);
/*!40000 ALTER TABLE `barrio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cantidad_x_mes`
--

DROP TABLE IF EXISTS `cantidad_x_mes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cantidad_x_mes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_generador` int NOT NULL,
  `id_mes` int NOT NULL,
  `anio` int NOT NULL,
  `total_kg` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `unique_generador_mes_anio` (`id_generador`,`id_mes`,`anio`),
  KEY `id_mes` (`id_mes`),
  KEY `idx_cantidad_x_mes_generador` (`id_generador`),
  KEY `idx_cantidad_x_mes_anio` (`anio`),
  CONSTRAINT `cantidad_x_mes_ibfk_1` FOREIGN KEY (`id_generador`) REFERENCES `generador` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cantidad_x_mes_ibfk_2` FOREIGN KEY (`id_mes`) REFERENCES `mes` (`id`),
  CONSTRAINT `cantidad_x_mes_chk_1` CHECK ((`total_kg` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=977 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cantidad_x_mes`
--

LOCK TABLES `cantidad_x_mes` WRITE;
/*!40000 ALTER TABLE `cantidad_x_mes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cantidad_x_mes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_sujeto` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=503001002 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1030040,'Coreográficos'),(1060050,'Deposito, Taller, Estación Servicio, Lavandería'),(1060060,'Hotel, Hospedaje, Motel'),(1060080,'Consultorio Médico y Odontológico, Laboratorio Den'),(1060110,'Consultorio, clínica Veterinaria y Otros'),(1080015,'Hogares Comunitarios'),(1080020,'Hospitales, Clínicas, Centros de Salud'),(1080030,'Laboratorio Clínico y Radiológico'),(1080040,'Cuartel, Cementerio, Salas de Velación, Cárcel'),(1090020,'Salones de Belleza, Peluquería, Barbería'),(2010020,'Farmacias, Droguerias y Depositos de Medicamentos'),(3010010,'Escuelas, Colegios, Universidades Otros');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificado`
--

DROP TABLE IF EXISTS `certificado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificado` (
  `id` int NOT NULL,
  `id_reporte` int NOT NULL,
  `codigo_certificado` varchar(50) NOT NULL,
  `fecha_aprobacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `firma_revisor` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_certificado` (`codigo_certificado`),
  KEY `id_reporte` (`id_reporte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificado`
--

LOCK TABLES `certificado` WRITE;
/*!40000 ALTER TABLE `certificado` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comuna`
--

DROP TABLE IF EXISTS `comuna`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comuna` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_comuna` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comuna`
--

LOCK TABLES `comuna` WRITE;
/*!40000 ALTER TABLE `comuna` DISABLE KEYS */;
INSERT INTO `comuna` VALUES (0,'Ninguno'),(1,'Comuna 1'),(2,'Comuna 2'),(3,'Comuna 3'),(4,'Comuna 4'),(5,'Comuna 5'),(6,'Comuna 6'),(7,'Comuna 7'),(8,'Comuna 8'),(9,'Comuna 9'),(10,'Comuna 10'),(11,'Comuna 11'),(12,'Comuna 12'),(13,'Corregimiento Buesaquillo'),(14,'Corregimiento Catambuco'),(15,'Corregimiento El Encano'),(16,'Corregimiento de Genoy'),(17,'Corregimiento La Caldera'),(18,'Corregimiento San Fernando'),(19,'Corregimiento Mapachico'),(20,'Corregimiento Morasurco'),(22,'Corregimiento Obonuco'),(23,'Corregimiento Santa Barbara'),(24,'Corregimiento de Gualmatan'),(25,'Corregimiento  Cabrera'),(26,'Corregimiento La Laguna'),(27,'Corregimiento Jamondino'),(28,'Corregimiento de Mocondino'),(29,'Corregimiento Jongovito'),(30,'Corregimiento El Socorro'),(31,'Municipio de Pasto'),(32,'Sector Rural');
/*!40000 ALTER TABLE `comuna` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contingencias`
--

DROP TABLE IF EXISTS `contingencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contingencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `generador_id` int NOT NULL,
  `anio` int NOT NULL,
  `fecha_reporte` date NOT NULL,
  `persona_reporta` int DEFAULT NULL,
  `incendios_numero` int DEFAULT '0',
  `incendios_acciones` json DEFAULT NULL,
  `incendios_otra_accion` text,
  `inundaciones_numero` int DEFAULT '0',
  `inundaciones_acciones` text,
  `agua_numero` int DEFAULT '0',
  `agua_acciones` json DEFAULT NULL,
  `agua_otra_accion` text,
  `energia_numero` int DEFAULT '0',
  `energia_acciones` json DEFAULT NULL,
  `energia_otra_accion` text,
  `derrames_numero` int DEFAULT '0',
  `derrames_tipo` varchar(50) DEFAULT NULL,
  `derrames_acciones` json DEFAULT NULL,
  `derrames_otra_accion` text,
  `recoleccion_numero` int DEFAULT '0',
  `recoleccion_acciones` json DEFAULT NULL,
  `recoleccion_otra_accion` text,
  `operativas_numero` int DEFAULT '0',
  `operativas_acciones` json DEFAULT NULL,
  `operativas_otra_accion` text,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` enum('borrador','confirmado') DEFAULT 'borrador',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_contingencia` (`generador_id`,`anio`),
  KEY `fk_persona_reporta_usuario` (`persona_reporta`),
  CONSTRAINT `contingencias_ibkf_1` FOREIGN KEY (`generador_id`) REFERENCES `generador` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_persona_reporta_usuario` FOREIGN KEY (`persona_reporta`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contingencias`
--

LOCK TABLES `contingencias` WRITE;
/*!40000 ALTER TABLE `contingencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `contingencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generador`
--

DROP TABLE IF EXISTS `generador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `generador` (
  `id` int NOT NULL AUTO_INCREMENT,
  `periodo_reporte` date NOT NULL,
  `nom_generador` varchar(100) NOT NULL,
  `razon_social` varchar(100) DEFAULT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `id_sujeto` int DEFAULT NULL,
  `tipo_sujeto` varchar(50) NOT NULL,
  `dir_establecimiento` text NOT NULL,
  `tel_establecimiento` varchar(20) DEFAULT NULL,
  `nom_responsable` varchar(100) NOT NULL,
  `cargo_responsable` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `categoria` varchar(20) DEFAULT NULL,
  `media_total` decimal(10,2) DEFAULT '0.00',
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_comuna` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generador`
--

LOCK TABLES `generador` WRITE;
/*!40000 ALTER TABLE `generador` DISABLE KEYS */;
/*!40000 ALTER TABLE `generador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_emails`
--

DROP TABLE IF EXISTS `logs_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `generador_id` int NOT NULL,
  `anio` int NOT NULL,
  `tipo_email` varchar(20) NOT NULL,
  `destinatario` varchar(255) NOT NULL,
  `fecha_envio` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `logs_emails_ibfk_1` (`generador_id`),
  CONSTRAINT `logs_emails_ibfk_1` FOREIGN KEY (`generador_id`) REFERENCES `generador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_emails`
--

LOCK TABLES `logs_emails` WRITE;
/*!40000 ALTER TABLE `logs_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mes`
--

DROP TABLE IF EXISTS `mes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) NOT NULL,
  `numero` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `unique_numero_mes` (`numero`),
  CONSTRAINT `mes_chk_1` CHECK ((`numero` between 1 and 12))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mes`
--

LOCK TABLES `mes` WRITE;
/*!40000 ALTER TABLE `mes` DISABLE KEYS */;
INSERT INTO `mes` VALUES (1,'Enero',1),(2,'Febrero',2),(3,'Marzo',3),(4,'Abril',4),(5,'Mayo',5),(6,'Junio',6),(7,'Julio',7),(8,'Agosto',8),(9,'Septiembre',9),(10,'Octubre',10),(11,'Noviembre',11),(12,'Diciembre',12);
/*!40000 ALTER TABLE `mes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reporte_anual_adicional`
--

DROP TABLE IF EXISTS `reporte_anual_adicional`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reporte_anual_adicional` (
  `id` int NOT NULL AUTO_INCREMENT,
  `generador_id` int NOT NULL,
  `anio` int NOT NULL,
  `num_capacitaciones_programadas` int DEFAULT '0',
  `archivo_cronograma` varchar(255) DEFAULT NULL,
  `num_capacitaciones_ejecutadas` int DEFAULT '0',
  `num_empleados_capacitados` int DEFAULT NULL,
  `archivo_soportes_capacitaciones` varchar(255) DEFAULT NULL,
  `tiene_accidentes` enum('si','no') DEFAULT 'no',
  `num_accidentes` int DEFAULT '0',
  `acciones_preventivas` json DEFAULT NULL,
  `otra_accion_preventiva` text,
  `num_auditorias` int DEFAULT '0',
  `archivo_resultados_auditorias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `archivo_plan_mejoramiento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_generador_anio` (`generador_id`,`anio`),
  CONSTRAINT `reporte_anual_adicional_ibfk_1` FOREIGN KEY (`generador_id`) REFERENCES `generador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reporte_anual_adicional`
--

LOCK TABLES `reporte_anual_adicional` WRITE;
/*!40000 ALTER TABLE `reporte_anual_adicional` DISABLE KEYS */;
/*!40000 ALTER TABLE `reporte_anual_adicional` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `resumen_anual`
--

DROP TABLE IF EXISTS `resumen_anual`;
/*!50001 DROP VIEW IF EXISTS `resumen_anual`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `resumen_anual` AS SELECT 
 1 AS `id`,
 1 AS `nom_generador`,
 1 AS `anio`,
 1 AS `total_residuos_kg`,
 1 AS `categoria`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `revisiones_anuales`
--

DROP TABLE IF EXISTS `revisiones_anuales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revisiones_anuales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `generador_id` int NOT NULL,
  `anio` int NOT NULL,
  `formulario_mensual` enum('pendiente','aprobado','rechazado','sin_datos') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'sin_datos',
  `formulario_contingencias` enum('pendiente','aprobado','rechazado','sin_datos') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'sin_datos',
  `formulario_accidentes` enum('pendiente','aprobado','rechazado','sin_datos') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'sin_datos',
  `observaciones_mensual` text,
  `observaciones_contingencias` text,
  `observaciones_accidentes` text,
  `soporte_pdf` varchar(255) DEFAULT NULL,
  `certificado_pdf` varchar(255) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `revisado_por` int DEFAULT NULL,
  `estado_general` enum('pendiente','aprobado','rechazado','incompleto','sin_datos') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `estado_finalizado` tinyint(1) DEFAULT '0',
  `fecha_finalizacion` datetime DEFAULT NULL,
  `certificado_generado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `generador_id` (`generador_id`,`anio`),
  KEY `revisado_por` (`revisado_por`),
  CONSTRAINT `revisiones_anuales_ibfk_1` FOREIGN KEY (`generador_id`) REFERENCES `generador` (`id`),
  CONSTRAINT `revisiones_anuales_ibfk_2` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revisiones_anuales`
--

LOCK TABLES `revisiones_anuales` WRITE;
/*!40000 ALTER TABLE `revisiones_anuales` DISABLE KEYS */;
/*!40000 ALTER TABLE `revisiones_anuales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcategoria`
--

DROP TABLE IF EXISTS `subcategoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcategoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_clase` varchar(100) DEFAULT NULL,
  `id_sujeto` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_sujeto` (`id_sujeto`),
  CONSTRAINT `subcategoria_ibfk_1` FOREIGN KEY (`id_sujeto`) REFERENCES `categoria` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcategoria`
--

LOCK TABLES `subcategoria` WRITE;
/*!40000 ALTER TABLE `subcategoria` DISABLE KEYS */;
INSERT INTO `subcategoria` VALUES (1,'Coreograficos',1030040),(2,'Lavanderia',1060050),(3,'Lavanderia Hospitalaria',1060050),(4,'Motel',1060060),(5,'Residencia',1060060),(6,'Casa de paso',1060060),(7,'Consultorio Medico',1060080),(8,'Consultorio Odontologico',1060080),(9,'Laboratorio Dental',1060080),(10,'Consultorio Medico Instituciones Educativas',1060080),(11,'Consultorio Medico Terminal de Transporte',1060080),(12,'Consultorio Odontologico Terminal de Transporte',1060080),(13,'Consultorio Psicologico',1060080),(14,'Opticas CON Consultorio',1060080),(15,'Opticas SIN Consultorio',1060080),(16,'Talleres Opticos',1060080),(17,'Centros de Ortopedia',1060080),(18,'Transporte Asistencial Basico  - Medicalizado',1060080),(19,'Consultorio Veterinario',1060110),(20,'Clinica Veterinaria',1060110),(21,'Otros',1060110),(22,'Estética Veterinaria',1060110),(23,'Fundaciones',1080015),(24,'Hogares Adulto Mayor',1080015),(25,'Centros Día',1080015),(26,'Hospitales',1080020),(27,'Clinicas',1080020),(28,'Centros de Salud',1080020),(29,'IPS',1080020),(30,'Laboratorio Clinico',1080030),(31,'Laboratorio Radiologico',1080030),(32,'Recepción de Muestras',1080030),(33,'Batallon',1080040),(34,'Cementerio',1080040),(35,'Salas de Velacion',1080040),(36,'Carcel',1080040),(37,'Funeraria',1080040),(38,'Horno Crematorio',1080040),(39,'Laboratorio de Tanatopraxia',1080040),(40,'Salones de Belleza',1090020),(41,'Peluqueria',1090020),(42,'Barberia',1090020),(43,'Centros de Estetica',1090020),(44,'Centro de Tatuaje',1090020),(45,'Farmacias',2010020),(46,'Droguerias',2010020),(47,'Depositos de Medicamentos',2010020),(48,'Colegios Privado',3010010),(49,'Universidades',3010010),(50,'Instituto Técnico para el Trabajo y Desarrollo Humano',3010010),(51,'Colegio Público',3010010);
/*!40000 ALTER TABLE `subcategoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_generador`
--

DROP TABLE IF EXISTS `tipo_generador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_generador` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_tipo` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom_tipo` (`nom_tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_generador`
--

LOCK TABLES `tipo_generador` WRITE;
/*!40000 ALTER TABLE `tipo_generador` DISABLE KEYS */;
INSERT INTO `tipo_generador` VALUES (4,'Barbería'),(5,'Instituto de formación'),(1,'IPS'),(3,'Salón de belleza');
/*!40000 ALTER TABLE `tipo_generador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_generador`
--

DROP TABLE IF EXISTS `usuario_generador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_generador` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `generador_id` int NOT NULL,
  `fecha_asociacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usuario_generador` (`usuario_id`,`generador_id`),
  KEY `generador_id` (`generador_id`),
  CONSTRAINT `usuario_generador_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_generador_ibfk_2` FOREIGN KEY (`generador_id`) REFERENCES `generador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_generador`
--

LOCK TABLES `usuario_generador` WRITE;
/*!40000 ALTER TABLE `usuario_generador` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_generador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(100) DEFAULT NULL,
  `token_recuperacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `expiracion_token` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'andrescuastumal@gmail.com','$2y$10$8u/0ODeMU17iiwe6BYakJ.xrEnjIVfgJ5Jh1Obg7TcDaRaRRc.fpG','generador',NULL,NULL),(2,'sms@saludpasto.gov.co','$2y$10$oCL1aRYeLsRxb7x0LV3myem1zh/XutI/Tt7TCGyqzleSfmm/oU8iy','admin',NULL,NULL),(3,'sistemas@saludpasto.gov.co','$2y$10$HiG/0IN1ABOrY3by7J4HMuNdXAo85FrRVSFJWLYnwcDAoEPyHLRr2','generador',NULL,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `resumen_anual`
--

/*!50001 DROP VIEW IF EXISTS `resumen_anual`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `resumen_anual` AS select `g`.`id` AS `id`,`g`.`nom_generador` AS `nom_generador`,extract(year from curdate()) AS `anio`,sum(`c`.`total_kg`) AS `total_residuos_kg`,`g`.`categoria` AS `categoria` from (`generador` `g` left join `cantidad_x_mes` `c` on(((`g`.`id` = `c`.`id_generador`) and (`c`.`anio` = extract(year from curdate()))))) group by `g`.`id` */;
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

-- Dump completed on 2025-10-17 14:34:55
