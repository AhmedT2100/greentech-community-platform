-- SQL script to add innovation module tables to greentech database
-- Execute this in phpMyAdmin for the greentech database

USE greentech;

-- Create categorie table
CREATE TABLE IF NOT EXISTS `categorie` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create innovation table
CREATE TABLE IF NOT EXISTS `innovation` (
  `id_innovation` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `statut` enum('en attente','validé','rejetée') DEFAULT 'en attente',
  `rating_total` decimal(10,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id_innovation`),
  KEY `categorie_id` (`id_categorie`),
  CONSTRAINT `innovation_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
