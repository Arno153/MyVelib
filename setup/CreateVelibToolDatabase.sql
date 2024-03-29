-- phpMyAdmin SQL Dump
-- version 4.9.9
-- https://www.phpmyadmin.net/
--

--
-- Base de données :  `velib`
--
CREATE DATABASE IF NOT EXISTS `velib` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `velib`;

-- --------------------------------------------------------

--
-- Structure de la table `velib_activ_station_stat`
--

DROP TABLE IF EXISTS `velib_activ_station_stat`;
CREATE TABLE `velib_activ_station_stat` (
  `date` date NOT NULL,
  `heure` int(11) NOT NULL,
  `nbStationUpdatedInThisHour` int(11) NOT NULL,
  `nbStationUpdatedLAst3Hour` int(11) DEFAULT NULL,
  `nbStationUpdatedLAst6Hour` int(11) DEFAULT NULL,
  `nbStationAtThisDate` int(11) DEFAULT NULL,
  `nbrVelibExit` int(11) DEFAULT NULL,
  `nbrEvelibExit` int(11) DEFAULT NULL,
  `networkNbBike` int(11) DEFAULT NULL,
  `networkMinNbBike` int(11) DEFAULT NULL,
  `networkMaxNbBike` int(11) DEFAULT NULL,
  `networkNbBikeOverflow` int(11) DEFAULT NULL,
  `networkEstimatedNbBike` int(11) DEFAULT NULL,
  `networkEstimatedNbBikeOverflow` int(11) DEFAULT NULL,
  `networkNbEBike` int(11) DEFAULT NULL,
  `networkNbEBikeOverflow` int(11) DEFAULT NULL,
  `networkEstimatedNbEBike` int(11) DEFAULT NULL,
  `networkEstimatedNbEBikeOverflow` int(11) DEFAULT NULL,
  `networkNbDock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `velib_api_sanitize`
--

DROP TABLE IF EXISTS `velib_api_sanitize`;
CREATE TABLE `velib_api_sanitize` (
  `JsonDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `JsonMD5` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `velib_network`
--

DROP TABLE IF EXISTS `velib_network`;
CREATE TABLE `velib_network` (
  `id` int(11) NOT NULL,
  `network_key` varchar(50) NOT NULL,
  `Current_Value` varchar(50)  NOT NULL,
  `Min_Value` varchar(50) NOT NULL,
  `Max_Value` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(1, 'LastUpdate', '2010-01-01 00:00:00', '2010-01-01 00:00:00', '2010-01-01 00:00:00');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(2, 'operative_station_nbr', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(3, 'velib_nbr', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(4, 'evelib_nbr', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(5, 'inactive_station_nbr', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(6, 'velib_nbr_overflow', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(7, 'evelib_nbr_overflow', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(8, 'evelib_nbr2', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(9, 'velib_nbr2', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(10, 'nbrVelibUtilises', '0', '0', '0');
INSERT INTO velib_network (id, network_key, Current_Value, Min_Value, Max_Value) VALUES(11, 'nbrEVelibUtilises', '0', '0', '0');


-- --------------------------------------------------------

--
-- Structure de la table `velib_station`
--

DROP TABLE IF EXISTS `velib_station`;
CREATE TABLE `velib_station` (
  `id` int(11) NOT NULL,
  `stationName` varchar(255) NOT NULL,
  `stationCode` varchar(10)  NOT NULL COMMENT 'code station api veli sans les 0 devant',
  `stationState` varchar(50) NOT NULL,
  `stationLat` double(24,15) NOT NULL,
  `stationLon` double(24,15) NOT NULL,
  `stationAdress` varchar(300) DEFAULT NULL COMMENT 'depuis api google à partir de lat/lon',
  `stationKioskState` varchar(3)  DEFAULT NULL,
  `stationNbEDock` int(11) NOT NULL COMMENT 'nombre de diapason (E ou pas)',
  `stationNbBike` int(11) NOT NULL,
  `stationNbEBike` int(11) NOT NULL,
  `nbFreeDock` int(11) NOT NULL,
  `nbFreeEDock` int(11) NOT NULL,
  `stationNbBikeOverflow` int(11) NOT NULL,
  `stationNbEBikeOverflow` int(11) NOT NULL,
  `stationLastChange` timestamp NOT NULL COMMENT 'date du dernier changement de la station',
  `stationLastExit` datetime DEFAULT NULL COMMENT 'date du dernier retrait',
  `stationInsertedInDb` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stationOperativeDate` datetime DEFAULT NULL,
  `stationLastView` datetime DEFAULT NULL COMMENT 'date de dernière collecte des infos de la station',
  `stationLastComeBack` datetime DEFAULT NULL,
  `stationLastChangeAtComeBack` datetime DEFAULT NULL,
  `stationAvgHourBetweenExit` float(5,1) DEFAULT NULL,
  `stationAvgHourBetweenComeBack` float(5,1) DEFAULT NULL,
  `stationSignaleHS` tinyint(1) NOT NULL DEFAULT '0',
  `stationSignaleHSDate` datetime DEFAULT NULL,
  `stationSignaleHSCount` int(11) NOT NULL DEFAULT '0',
  `stationSignaledElectrified` int(1) NOT NULL DEFAULT '2' COMMENT '0:non - 1-oui - 2:unknown',
  `stationSignaledElectrifiedDate` datetime DEFAULT NULL,
  `stationHidden` tinyint(1) NOT NULL DEFAULT '0',
  `stationLocationHasChanged` tinyint(1) NOT NULL DEFAULT '0',
  `stationCP` int(11) DEFAULT NULL,
  `stationCommune` varchar(255) COLLATE utf8_bin DEFAULT NULL															 
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `velib_station_min_velib`
--

DROP TABLE IF EXISTS `velib_station_min_velib`;
CREATE TABLE `velib_station_min_velib` (
  `stationCode` varchar(10) COLLATE latin1_general_ci NOT NULL,
  `stationStatDate` date NOT NULL,
  `stationVelibMinVelib` int(11) NOT NULL COMMENT 'velib + vae',
  `stationVelibMinEVelib` int(11) DEFAULT NULL COMMENT 'vae',
  `stationVelibMaxVelib` int(11) NOT NULL DEFAULT '0' COMMENT 'velib + vae',
  `stationVelibMinVelibOverflow` int(11) DEFAULT NULL COMMENT 'velib + vae',
  `stationVelibMinEVelibOverflow` int(11) DEFAULT NULL COMMENT 'vae',
  `stationVelibMaxVelibOverflow` int(11) DEFAULT NULL COMMENT 'velib + vae',
  `stationVelibExit` int(11) DEFAULT '0',
  `stationEVelibExit` int(11) DEFAULT NULL,
  `updateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
PARTITION BY RANGE COLUMNS(stationStatDate)
(
PARTITION p0 VALUES LESS THAN ('2019-06-30') ENGINE=InnoDB,
PARTITION p1 VALUES LESS THAN ('2019-11-30') ENGINE=InnoDB,
PARTITION p2 VALUES LESS THAN ('2021-01-01') ENGINE=InnoDB,
PARTITION p3 VALUES LESS THAN ('2022-01-01') ENGINE=InnoDB,
PARTITION p4 VALUES LESS THAN (MAXVALUE) ENGINE=InnoDB
);

-- --------------------------------------------------------

--
-- Structure de la table `velib_station_status`
--

DROP TABLE IF EXISTS `velib_station_status`;
CREATE TABLE IF NOT EXISTS `velib_station_status` (
  `id` int(11) NOT NULL,
  `stationCode` varchar(10) COLLATE utf8_bin NOT NULL COMMENT 'code station api veli sans les 0 devant',
  `stationState` varchar(50) COLLATE utf8_bin NOT NULL,
  `stationStatusDate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `velib_activ_station_stat`
--
ALTER TABLE `velib_activ_station_stat`
  ADD PRIMARY KEY (`date`,`heure`);

--
-- Index pour la table `velib_api_sanitize`
--
ALTER TABLE `velib_api_sanitize`
  ADD PRIMARY KEY (`JsonDate`),
  ADD KEY `JsonMD5` (`JsonMD5`);

--
-- Index pour la table `velib_network`
--
ALTER TABLE `velib_network`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_index` (`network_key`);

--
-- Index pour la table `velib_station`
--
ALTER TABLE `velib_station`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stationCode` (`stationCode`),
  ADD KEY `stationState` (`stationState`) USING BTREE;
  
--
-- Index pour la table `velib_station_min_velib`
--
ALTER TABLE `velib_station_min_velib`
  ADD PRIMARY KEY (`stationCode`,`stationStatDate`),
  ADD KEY `stationstatdate_idx` (`stationStatDate`,`stationCode`) USING BTREE;
--
-- Index pour la table `velib_station_status`
--
ALTER TABLE `velib_station_status`
  ADD PRIMARY KEY (`id`,`stationStatusDate`),
  ADD KEY `id` (`id`,`stationState`);
  
--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `velib_network`
--
ALTER TABLE `velib_network`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `velib_station`
--
ALTER TABLE `velib_station`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;