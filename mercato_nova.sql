-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 29 mai 2026 à 16:55
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mercato_nova`
--

-- --------------------------------------------------------

--
-- Structure de la table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE IF NOT EXISTS `favorites` (
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `favorites`
--

INSERT INTO `favorites` (`user_id`, `product_id`, `created_at`) VALUES
(2, 1, '2026-05-28 12:40:06'),
(2, 5, '2026-05-28 13:31:11');

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('panier','validee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'panier',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_mode` enum('achat','enchere','negociation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'achat',
  `seller_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `icon`, `description`, `price`, `sale_mode`, `seller_id`, `created_at`, `start_date`, `end_date`) VALUES
(1, 'Smartphone Nova X', 'tech', '📱', 'Téléphone performant avec grand écran OLED.', 399.00, 'enchere', NULL, '2026-05-26 17:44:31', '2026-05-20 18:00:00', '2026-05-21 18:00:00'),
(2, 'Casque Bluetooth Pro', 'tech', '🎧', 'Réduction de bruit et autonomie longue durée.', 79.99, 'negociation', NULL, '2026-05-26 17:44:31', NULL, NULL),
(3, 'Veste urbaine', 'mode', '🧥', 'Veste confortable pour toutes les saisons.', 59.00, 'achat', NULL, '2026-05-26 17:44:31', NULL, NULL),
(4, 'Lampe design', 'maison', '💡', 'Lampe LED moderne pour bureau ou salon.', 34.00, 'achat', NULL, '2026-05-26 17:44:31', NULL, NULL),
(5, 'Roman collector', 'livres', '📚', 'Édition spéciale en excellent état.', 18.00, 'enchere', NULL, '2026-05-26 17:44:31', '2026-05-28 15:01:57', '2026-05-28 17:01:57'),
(6, 'Sac de voyage', 'mode', '🎒', 'Solide, pratique et spacieux.', 45.00, 'negociation', NULL, '2026-05-26 17:44:31', NULL, NULL),
(7, 'Clavier mécanique', 'tech', '⌨️', 'Switchs rapides et rétroéclairage RGB.', 89.00, 'achat', NULL, '2026-05-26 17:44:31', NULL, NULL),
(8, 'Set cuisine', 'maison', '🍳', 'Kit complet pour cuisiner facilement.', 49.00, 'achat', NULL, '2026-05-26 17:44:31', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verification_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `role` enum('acheteur','vendeur','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'acheteur',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `verification_token`, `is_verified`, `role`, `created_at`) VALUES
(1, 'prout', 'camil.duvracdjoubri@edu.ece.fr', '$2y$10$Ir9HZT3a5Dc7JkikFXpt2.BOovRvbTVvAyoDWwz304UXrVP3hM5jq', '3ded392248fb9354a5c268c1ff824be0d91903ecbb8ceea733be537c48fb4344', 0, 'acheteur', '2026-05-27 16:49:38'),
(2, 'prouttt', 'lolmancool0@gmail.com', '$2y$10$R98HUhSYYbODzK4dEi8SIerHAbRnVZm8.Xkoh1SVwZpmz18oAKhSS', NULL, 1, 'acheteur', '2026-05-28 12:39:39');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
