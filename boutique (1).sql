-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3307
-- Généré le : jeu. 26 juin 2025 à 22:39
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `boutique`
--

-- --------------------------------------------------------

--
-- Structure de la table `boutiques`
--

CREATE TABLE `boutiques` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `boutique_image` varchar(255) DEFAULT NULL,
  `admin_email` varchar(100) DEFAULT NULL,
  `admin_password` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `pinterest` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `boutiques`
--

INSERT INTO `boutiques` (`id`, `nom`, `description`, `logo`, `adresse`, `telephone`, `email`, `created_at`, `boutique_image`, `admin_email`, `admin_password`, `facebook`, `instagram`, `twitter`, `pinterest`) VALUES
(7, 'DEFACTO', 'Defacto est une marque de mode offrant des vêtements modernes et abordables pour hommes, femmes et enfants. Alliant style et confort, elle est présente dans plusieurs pays.', 'uploads/684c79366ecfd.png', 'Rue de Rabat, Centre Commercial Mega Mall, Kénitra, Maroc', '+212 537 12 34 56', 'contact@defacto.ma', '2025-06-13 19:17:10', 'uploads/684c79366fe38.jpg', 'contact@defacto.ma', '$2y$10$cYsUY2Qt.tgCswplQ9NgOuMWMWkwUOlzwgshSuJJa1PXdJLjGtFt.', 'https://www.facebook.com/profile.php?id=61563345237096', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_inscription` datetime DEFAULT current_timestamp(),
  `code_client` varchar(120) DEFAULT NULL,
  `boutique_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `email`, `telephone`, `date_inscription`, `code_client`, `boutique_id`) VALUES
(16, 'Soukaina mouna', 'mouna.soukaina05@gmail.com', '0633158241', '2025-06-14 00:26:19', 'O8LVGW', 7),
(17, 'soso', 'soso@gmail.com', '09777865356', '2025-06-15 12:18:42', 'FCAHTS', 7),
(18, 'Soukaina mouna', 'Soukaina.mouna@uit.ac.ma', '0633158241', '2025-06-15 15:47:43', '8HLMU3', 7),
(19, 'NADIA', '', '0537123456', '2025-06-15 19:18:16', 'IQRWGZ', 7),
(20, 'fatima mouna', 'fatima.mouna@uit.ac.ma', '0633158241', '2025-06-15 19:20:59', 'HS4X3K', 7),
(21, 'marso', 'm.h@uit.ac.ma', '067785323', '2025-06-15 19:21:27', 'WY2RVN', 7),
(22, 'NADIA', 'Nadia.amrani@uit.ac.ma', '0537123456', '2025-06-15 19:52:01', 'OD8G47', 7),
(23, 'hamza bouhou', 'hamza.bouhou@uit.ac.ma', '0633158248', '2025-06-15 20:00:31', 'T6DFVQ', 7),
(24, 'hamid', 'hamid@gmail.com', '0633158242', '2025-06-15 20:03:15', '02QTOI', 7);

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'En attente',
  `date_commande` datetime DEFAULT current_timestamp(),
  `quantity` int(11) DEFAULT NULL,
  `boutique_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `produit_id`, `client_id`, `statut`, `date_commande`, `quantity`, `boutique_id`) VALUES
(70, 12, 16, 'Prête à retirer', '2025-06-14 00:26:19', 1, 7),
(71, 17, 16, 'Prête à retirer', '2025-06-14 00:26:19', 1, 7),
(72, 13, 16, 'En attente', '2025-06-14 15:42:22', 1, 7),
(73, 11, 17, 'En attente', '2025-06-15 12:18:42', 1, 7),
(74, 12, 17, 'En attente', '2025-06-15 12:19:42', 1, 7),
(75, 11, 18, 'En attente', '2025-06-15 15:47:43', 1, 7),
(76, 12, 18, 'En attente', '2025-06-15 15:48:23', 1, 7),
(77, 13, 18, 'En attente', '2025-06-15 15:50:02', 1, 7),
(78, 11, 16, 'En attente', '2025-06-15 16:35:07', 2, 7),
(79, 12, 18, 'En attente', '2025-06-15 16:36:14', 1, 7),
(80, 13, 19, 'En attente', '2025-06-15 19:18:16', 1, 7),
(81, 15, 19, 'En attente', '2025-06-15 19:18:16', 1, 7),
(82, 17, 19, 'En attente', '2025-06-15 19:18:16', 1, 7),
(83, 13, 19, 'En attente', '2025-06-15 19:19:05', 1, 7),
(84, 12, 20, 'En attente', '2025-06-15 19:20:59', 1, 7),
(85, 18, 21, 'En attente', '2025-06-15 19:21:27', 1, 7),
(86, 11, 16, 'En attente', '2025-06-15 19:22:44', 1, 7),
(87, 13, 16, 'En attente', '2025-06-15 19:51:02', 1, 7),
(88, 12, 22, 'En attente', '2025-06-15 19:52:01', 1, 7),
(89, 18, 19, 'En attente', '2025-06-15 19:59:44', 1, 7),
(90, 12, 23, 'En attente', '2025-06-15 20:00:31', 1, 7),
(91, 16, 23, 'En attente', '2025-06-15 20:01:36', 1, 7),
(92, 17, 24, 'En attente', '2025-06-15 20:03:15', 1, 7);

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `boutique_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `prix`, `image`, `description`, `stock`, `boutique_id`) VALUES
(11, 'T-shirt Homme Basique', 89.99, './uploads/7fdbde88bc208b7352e1025804f98d52.jpg', 'T-shirt en coton doux, coupe classique, idéal pour un look décontracté.', 95, 7),
(12, 'Jean Slim Femme', 249.00, './uploads/9458c93a823933bf182565709f174c91.jpg', 'Jean slim stretch pour femme, coupe moderne et confortable.', 93, 7),
(13, 'Veste Enfant Bleu', 199.00, './uploads/0bc3311ad9b7e3f5e8293156dba3eeb4.jpg', ' Veste légère pour enfants, idéale pour le printemps.', 95, 7),
(14, 'Sweat à Capuche Homme', 179.99, './uploads/2ab58f818711c8b81a74d7dd304809de.jpg', ' Sweat avec capuche, intérieur molletonné, parfait pour l\'hiver.', 100, 7),
(15, 'Robe d’Été Femme', 179.00, './uploads/43645bbfed6ceaa4231df20a20bf4238.jpg', 'Robe légère et fluide avec motifs floraux, parfaite pour les journées chaudes.', 99, 7),
(16, 'Polo Homme Classique', 129.00, './uploads/0b65eda035ec58fa792ef0ec64ff4a57.jpg', 'Polo à manches courtes, col boutonné, disponible en plusieurs couleurs.', 99, 7),
(17, 'Chemise Femme Blanche', 159.00, './uploads/8fbf14aee30ed2d9e96ae254caf9e692.jpg', 'Chemise élégante en coton, idéale pour le bureau ou les sorties formelles.', 97, 7),
(18, 'Jogging Enfant Gris', 119.00, './uploads/c8cf1d4a5ede90a197fb81732ed9774e.jpg', 'Ensemble confortable pour enfants, parfait pour l’école ou les loisirs.', 98, 7);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `boutiques`
--
ALTER TABLE `boutiques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`),
  ADD UNIQUE KEY `admin_email` (`admin_email`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boutique_id` (`boutique_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `boutiques`
--
ALTER TABLE `boutiques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`),
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`boutique_id`) REFERENCES `boutiques` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
