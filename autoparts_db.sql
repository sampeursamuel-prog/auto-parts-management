-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 24 avr. 2026 à 17:49
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `autoparts_db`
--

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `sp_entree_stock`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_entree_stock` (IN `p_numero_bon` VARCHAR(50), IN `p_id_fournisseur` INT, IN `p_id_user` INT, IN `p_code_barre` VARCHAR(50), IN `p_quantite` INT, IN `p_prix_achat` DECIMAL(10,2), IN `p_date_peremption` DATE, IN `p_emplacement` VARCHAR(50), OUT `p_id_lot` INT)   BEGIN
    DECLARE v_id_produit INT;
    DECLARE v_id_entree INT;
    DECLARE v_numero_lot VARCHAR(100);
    
    -- Récupérer le produit
    SELECT id_produit INTO v_id_produit 
    FROM produits 
    WHERE code_barre = p_code_barre AND est_actif = 1;
    
    IF v_id_produit IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produit non trouvé';
    END IF;
    
    -- Créer le bon d'entrée
    INSERT INTO entrees_stock (numero_bon_entree, id_fournisseur, id_user, statut)
    VALUES (p_numero_bon, p_id_fournisseur, p_id_user, 'validee');
    
    SET v_id_entree = LAST_INSERT_ID();
    
    -- Générer numéro de lot
    SET v_numero_lot = CONCAT('LOT-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', 
                              LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Créer le lot
    INSERT INTO lots_produits (id_produit, numero_lot, id_fournisseur, 
                               date_peremption, quantite_initiale, quantite_actuelle,
                               prix_achat_unitaire)
    VALUES (v_id_produit, v_numero_lot, p_id_fournisseur,
            p_date_peremption, p_quantite, p_quantite,
            p_prix_achat);
    
    SET p_id_lot = LAST_INSERT_ID();
    
    -- Ajouter le détail
    INSERT INTO details_entrees_stock (id_entree, id_produit, code_barre_scanne,
                                       quantite, prix_unitaire_ht, id_lot, emplacement_stockage)
    VALUES (v_id_entree, v_id_produit, p_code_barre,
            p_quantite, p_prix_achat, p_id_lot, p_emplacement);
    
    -- Mettre à jour le stock
    UPDATE produits 
    SET stock_actuel = stock_actuel + p_quantite
    WHERE id_produit = v_id_produit;
    
    -- Enregistrer le mouvement
    INSERT INTO mouvements_stock (id_produit, id_lot, id_user, type_mouvement,
                                  quantite, stock_avant, stock_apres,
                                  reference_type, reference_id)
    SELECT v_id_produit, p_id_lot, p_id_user, 'entree',
           p_quantite, (stock_actuel - p_quantite), stock_actuel,
           'entree', v_id_entree
    FROM produits WHERE id_produit = v_id_produit;
    
END$$

DROP PROCEDURE IF EXISTS `sp_fermer_caisse`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_fermer_caisse` (IN `p_id_session` INT, IN `p_id_superviseur` INT, IN `p_montant_especes_reel` DECIMAL(10,2), IN `p_notes` TEXT)   BEGIN
    DECLARE v_montant_attendu DECIMAL(10,2);
    DECLARE v_difference DECIMAL(10,2);
    
    -- Calculer le montant attendu
    SELECT montant_initial + montant_total_ventes INTO v_montant_attendu
    FROM sessions_caisse WHERE id_session = p_id_session;
    
    -- Calculer la différence
    SET v_difference = p_montant_especes_reel - v_montant_attendu;
    
    -- Fermer la session
    UPDATE sessions_caisse 
    SET date_fermeture = NOW(),
        montant_especes = p_montant_especes_reel,
        montant_attendu = v_montant_attendu,
        difference = v_difference,
        statut = 'fermee',
        notes_fermeture = p_notes
    WHERE id_session = p_id_session;
    
    -- Vérifier différence anormale
    IF ABS(v_difference) > 5000 THEN
        INSERT INTO alertes (type_alerte, niveau, id_session, message, details)
        VALUES ('caisse_difference', 'critical', p_id_session,
                CONCAT('Différence de caisse de ', v_difference, ' FCFA'),
                JSON_OBJECT('montant_attendu', v_montant_attendu, 
                           'montant_reel', p_montant_especes_reel));
    END IF;
    
END$$

DROP PROCEDURE IF EXISTS `sp_vente_scanner`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_vente_scanner` (IN `p_id_user` INT, IN `p_code_barre` VARCHAR(50), IN `p_quantite` INT, IN `p_mode_paiement` VARCHAR(20), IN `p_montant_recu` DECIMAL(10,2), OUT `p_numero_facture` VARCHAR(50))   BEGIN
    DECLARE v_id_produit INT;
    DECLARE v_id_session INT;
    DECLARE v_id_vente INT;
    DECLARE v_prix_vente DECIMAL(10,2);
    DECLARE v_tva DECIMAL(5,2);
    DECLARE v_stock_actuel INT;
    DECLARE v_id_lot INT;
    DECLARE v_total_ttc DECIMAL(10,2);
    
    -- Récupérer la session ouverte
    SELECT id_session INTO v_id_session 
    FROM sessions_caisse 
    WHERE id_user = p_id_user AND statut = 'ouverte'
    ORDER BY date_ouverture DESC LIMIT 1;
    
    IF v_id_session IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aucune session de caisse ouverte';
    END IF;
    
    -- Récupérer le produit
    SELECT id_produit, prix_vente_ht, tva, stock_actuel 
    INTO v_id_produit, v_prix_vente, v_tva, v_stock_actuel
    FROM produits 
    WHERE code_barre = p_code_barre AND est_actif = 1;
    
    IF v_id_produit IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produit non trouvé';
    END IF;
    
    -- Vérifier le stock
    IF v_stock_actuel < p_quantite THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuffisant';
    END IF;
    
    -- Récupérer un lot (FIFO)
    SELECT id_lot INTO v_id_lot
    FROM lots_produits 
    WHERE id_produit = v_id_produit AND quantite_actuelle > 0
    ORDER BY date_reception ASC LIMIT 1;
    
    -- Calculer le total
    SET v_total_ttc = p_quantite * v_prix_vente * (1 + v_tva/100);
    
    -- Générer numéro de facture
    SET p_numero_facture = CONCAT('FACT-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', 
                                  LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Créer la vente
    INSERT INTO ventes (numero_facture, id_session, id_user, 
                        montant_total_ht, montant_tva, montant_total_ttc, 
                        montant_final, montant_recu, mode_paiement, statut)
    VALUES (p_numero_facture, v_id_session, p_id_user, 
            p_quantite * v_prix_vente,
            p_quantite * v_prix_vente * (v_tva/100),
            v_total_ttc,
            v_total_ttc,
            p_montant_recu, p_mode_paiement, 'complete');
    
    SET v_id_vente = LAST_INSERT_ID();
    
    -- Ajouter le détail
    INSERT INTO details_vente (id_vente, id_produit, code_barre_scanne, id_lot,
                               quantite, prix_unitaire_ht, tva)
    VALUES (v_id_vente, v_id_produit, p_code_barre, v_id_lot,
            p_quantite, v_prix_vente, v_tva);
    
    -- Mettre à jour le stock
    UPDATE produits 
    SET stock_actuel = stock_actuel - p_quantite
    WHERE id_produit = v_id_produit;
    
    -- Mettre à jour le lot
    IF v_id_lot IS NOT NULL THEN
        UPDATE lots_produits 
        SET quantite_actuelle = quantite_actuelle - p_quantite
        WHERE id_lot = v_id_lot;
    END IF;
    
    -- Enregistrer le mouvement
    INSERT INTO mouvements_stock (id_produit, id_lot, id_user, type_mouvement,
                                  quantite, stock_avant, stock_apres,
                                  reference_type, reference_id)
    SELECT v_id_produit, v_id_lot, p_id_user, 'sortie_vente',
           p_quantite, (stock_actuel + p_quantite), stock_actuel,
           'vente', v_id_vente
    FROM produits WHERE id_produit = v_id_produit;
    
    -- Mettre à jour la session
    UPDATE sessions_caisse 
    SET montant_total_ventes = montant_total_ventes + v_total_ttc
    WHERE id_session = v_id_session;
    
END$$

DROP PROCEDURE IF EXISTS `sp_verifier_alertes_stock`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_verifier_alertes_stock` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id_produit INT;
    DECLARE v_nom_produit VARCHAR(200);
    DECLARE v_stock_actuel INT;
    DECLARE v_stock_minimum INT;
    
    DECLARE cur_products CURSOR FOR 
        SELECT id_produit, nom_produit, stock_actuel, stock_minimum
        FROM produits 
        WHERE est_actif = 1;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur_products;
    
    read_loop: LOOP
        FETCH cur_products INTO v_id_produit, v_nom_produit, v_stock_actuel, v_stock_minimum;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Alerte stock minimum
        IF v_stock_actuel <= v_stock_minimum AND v_stock_actuel > 0 THEN
            INSERT INTO alertes (type_alerte, niveau, id_produit, message)
            VALUES ('stock_minimum', 'warning', v_id_produit,
                    CONCAT('Stock bas pour ', v_nom_produit, ': ', v_stock_actuel, ' restants'));
        
        -- Alerte rupture
        ELSEIF v_stock_actuel = 0 THEN
            INSERT INTO alertes (type_alerte, niveau, id_produit, message)
            VALUES ('stock_rupture', 'critical', v_id_produit,
                    CONCAT('RUPTURE DE STOCK: ', v_nom_produit));
        END IF;
        
    END LOOP;
    
    CLOSE cur_products;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `achats`
--

DROP TABLE IF EXISTS `achats`;
CREATE TABLE IF NOT EXISTS `achats` (
  `id_achat` int NOT NULL AUTO_INCREMENT,
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_fournisseur` int NOT NULL,
  `id_magasin` int NOT NULL,
  `date_achat` datetime NOT NULL,
  `devise_achat` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `taux_change` decimal(10,4) NOT NULL,
  `montant_total_usd` decimal(10,2) NOT NULL,
  `montant_total_htg` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','recu','annule') COLLATE utf8mb4_unicode_ci DEFAULT 'recu',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_achat`),
  KEY `id_fournisseur` (`id_fournisseur`),
  KEY `id_magasin` (`id_magasin`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `achat_details`
--

DROP TABLE IF EXISTS `achat_details`;
CREATE TABLE IF NOT EXISTS `achat_details` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_achat` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire_usd` decimal(10,2) NOT NULL,
  `prix_unitaire_htg` decimal(10,2) NOT NULL,
  `sous_total_usd` decimal(10,2) NOT NULL,
  `sous_total_htg` decimal(10,2) NOT NULL,
  `tva` decimal(5,2) DEFAULT '0.00',
  PRIMARY KEY (`id_detail`),
  KEY `id_achat` (`id_achat`),
  KEY `id_produit` (`id_produit`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

DROP TABLE IF EXISTS `alertes`;
CREATE TABLE IF NOT EXISTS `alertes` (
  `id_alerte` int NOT NULL AUTO_INCREMENT,
  `type_alerte` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveau` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'warning',
  `id_produit` int DEFAULT NULL,
  `id_lot` int DEFAULT NULL,
  `id_session` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` json DEFAULT NULL,
  `est_lue` tinyint(1) DEFAULT '0',
  `est_ignoree` tinyint(1) DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_traitement` timestamp NULL DEFAULT NULL,
  `id_user_traitement` int DEFAULT NULL,
  PRIMARY KEY (`id_alerte`),
  KEY `id_produit` (`id_produit`),
  KEY `id_lot` (`id_lot`),
  KEY `id_session` (`id_session`),
  KEY `id_user_traitement` (`id_user_traitement`),
  KEY `idx_alertes_creation` (`date_creation`),
  KEY `idx_alertes_lue` (`est_lue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `alertes_config`
--

DROP TABLE IF EXISTS `alertes_config`;
CREATE TABLE IF NOT EXISTS `alertes_config` (
  `id_config` int NOT NULL AUTO_INCREMENT,
  `type_alerte` enum('stock_minimum','stock_rupture','peremption','caisse_difference','vente_anormale') COLLATE utf8mb4_unicode_ci NOT NULL,
  `seuil_valeur` decimal(10,2) DEFAULT NULL,
  `notification_email` tinyint(1) DEFAULT '1',
  `notification_sms` tinyint(1) DEFAULT '0',
  `notification_app` tinyint(1) DEFAULT '1',
  `destinataires_emails` text COLLATE utf8mb4_unicode_ci,
  `destinataires_sms` text COLLATE utf8mb4_unicode_ci,
  `est_actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `alertes_config`
--

INSERT INTO `alertes_config` (`id_config`, `type_alerte`, `seuil_valeur`, `notification_email`, `notification_sms`, `notification_app`, `destinataires_emails`, `destinataires_sms`, `est_actif`) VALUES
(1, 'stock_minimum', 5.00, 1, 1, 1, NULL, NULL, 1),
(2, 'stock_rupture', 0.00, 1, 1, 1, NULL, NULL, 1),
(3, 'peremption', 30.00, 1, 0, 1, NULL, NULL, 1),
(4, 'caisse_difference', 5000.00, 1, 1, 1, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_id` int DEFAULT NULL COMMENT 'Pour les sous-catégories',
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categorie`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom_categorie`, `description`, `parent_id`, `image_url`, `est_actif`, `date_creation`) VALUES
(1, 'Moteur', 'Pièces moteur, pistons, cylindres, etc.', NULL, NULL, 1, '2026-03-28 16:44:05'),
(2, 'Freinage', 'Plaquettes, disques, étriers, etc.', NULL, NULL, 1, '2026-03-28 16:44:05'),
(3, 'Suspension', 'Amortisseurs, ressorts, silentblocs, etc.', NULL, NULL, 1, '2026-03-28 16:44:05'),
(4, 'Électrique', 'Batteries, alternateur, démarreur, etc.', NULL, NULL, 1, '2026-03-28 16:44:05'),
(5, 'Transmission', 'Boîte de vitesses, embrayage, cardans, etc.', NULL, NULL, 1, '2026-03-28 16:44:05'),
(6, 'Carrosserie', 'Pare-chocs, rétroviseurs, vitres, etc.', NULL, NULL, 1, '2026-03-28 16:44:05'),
(7, 'Échappement', 'Lignes d\'échappement, catalyseurs, etc.', NULL, NULL, 1, '2026-03-28 16:44:05'),
(8, 'Climatisation', 'Compresseurs, condenseurs, etc.', NULL, NULL, 1, '2026-03-28 16:44:05');

-- --------------------------------------------------------

--
-- Structure de la table `categories_clients`
--

DROP TABLE IF EXISTS `categories_clients`;
CREATE TABLE IF NOT EXISTS `categories_clients` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remise_base` decimal(5,2) DEFAULT '0.00',
  `seuil_min_points` int DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `nom_categorie` (`nom_categorie`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id_client` int NOT NULL AUTO_INCREMENT,
  `code_client` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `plaque_immatriculation` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_modele_vehicule` int DEFAULT NULL,
  `points_fidelite` int DEFAULT '0',
  `date_inscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `est_actif` tinyint(1) DEFAULT '1',
  `type_client` enum('particulier','entreprise','grossiste') COLLATE utf8mb4_unicode_ci DEFAULT 'particulier',
  `categorie_client` enum('bronze','argent','or','platine') COLLATE utf8mb4_unicode_ci DEFAULT 'bronze',
  `remise_automatique` decimal(5,2) DEFAULT '0.00',
  `plafond_credit` decimal(12,2) DEFAULT '0.00',
  `solde_credit` decimal(12,2) DEFAULT '0.00',
  `date_dernier_achat` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_client`),
  UNIQUE KEY `code_client` (`code_client`),
  KEY `id_modele_vehicule` (`id_modele_vehicule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `compatibilite_produits`
--

DROP TABLE IF EXISTS `compatibilite_produits`;
CREATE TABLE IF NOT EXISTS `compatibilite_produits` (
  `id_compatibilite` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL,
  `id_modele` int NOT NULL,
  `moteur` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precision_compatibilite` text COLLATE utf8mb4_unicode_ci,
  `est_oem` tinyint(1) DEFAULT '0',
  `reference_constructeur` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_compatibilite`),
  UNIQUE KEY `unique_compat` (`id_produit`,`id_modele`,`moteur`),
  KEY `idx_compatibilite_produit` (`id_produit`),
  KEY `idx_compatibilite_modele` (`id_modele`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cotations`
--

DROP TABLE IF EXISTS `cotations`;
CREATE TABLE IF NOT EXISTS `cotations` (
  `id_cotation` int NOT NULL AUTO_INCREMENT,
  `numero_cotation` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_client` int DEFAULT NULL,
  `date_cotation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_validite` date DEFAULT NULL,
  `montant_total_ht` decimal(12,2) NOT NULL,
  `montant_tva` decimal(12,2) NOT NULL,
  `montant_total_ttc` decimal(12,2) NOT NULL,
  `remise_globale` decimal(5,2) DEFAULT '0.00',
  `montant_apres_remise` decimal(12,2) NOT NULL,
  `statut` enum('brouillon','envoye','accepte','refuse','transforme_vente') COLLATE utf8mb4_unicode_ci DEFAULT 'brouillon',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `id_user_creation` int DEFAULT NULL,
  `date_transformation` timestamp NULL DEFAULT NULL,
  `id_vente` int DEFAULT NULL,
  PRIMARY KEY (`id_cotation`),
  UNIQUE KEY `numero_cotation` (`numero_cotation`),
  KEY `id_client` (`id_client`),
  KEY `id_user_creation` (`id_user_creation`),
  KEY `id_vente` (`id_vente`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `details_cotation`
--

DROP TABLE IF EXISTS `details_cotation`;
CREATE TABLE IF NOT EXISTS `details_cotation` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_cotation` int NOT NULL,
  `id_produit` int NOT NULL,
  `code_barre` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire_ht` decimal(10,2) NOT NULL,
  `remise_ligne` decimal(5,2) DEFAULT '0.00',
  `sous_total_ht` decimal(10,2) GENERATED ALWAYS AS (((`quantite` * `prix_unitaire_ht`) * (1 - (`remise_ligne` / 100)))) STORED,
  `tva` decimal(5,2) DEFAULT '18.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_detail`),
  KEY `id_cotation` (`id_cotation`),
  KEY `id_produit` (`id_produit`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `details_entrees_stock`
--

DROP TABLE IF EXISTS `details_entrees_stock`;
CREATE TABLE IF NOT EXISTS `details_entrees_stock` (
  `id_detail_entree` int NOT NULL AUTO_INCREMENT,
  `id_entree` int NOT NULL,
  `id_produit` int NOT NULL,
  `code_barre_scanne` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire_ht` decimal(10,2) NOT NULL,
  `remise` decimal(5,2) DEFAULT '0.00',
  `sous_total_ht` decimal(10,2) GENERATED ALWAYS AS (((`quantite` * `prix_unitaire_ht`) * (1 - (`remise` / 100)))) STORED,
  `id_lot` int DEFAULT NULL,
  `emplacement_stockage` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_detail_entree`),
  KEY `id_entree` (`id_entree`),
  KEY `id_produit` (`id_produit`),
  KEY `id_lot` (`id_lot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `details_inventaire`
--

DROP TABLE IF EXISTS `details_inventaire`;
CREATE TABLE IF NOT EXISTS `details_inventaire` (
  `id_detail_inventaire` int NOT NULL AUTO_INCREMENT,
  `id_inventaire` int NOT NULL,
  `id_produit` int NOT NULL,
  `id_lot` int DEFAULT NULL,
  `emplacement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantite_theorique` int NOT NULL,
  `quantite_comptee` int NOT NULL,
  `ecart` int GENERATED ALWAYS AS ((`quantite_comptee` - `quantite_theorique`)) STORED,
  `id_user_comptage` int NOT NULL,
  `date_comptage` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `est_corrige` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_detail_inventaire`),
  KEY `id_inventaire` (`id_inventaire`),
  KEY `id_produit` (`id_produit`),
  KEY `id_lot` (`id_lot`),
  KEY `id_user_comptage` (`id_user_comptage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `details_vente`
--

DROP TABLE IF EXISTS `details_vente`;
CREATE TABLE IF NOT EXISTS `details_vente` (
  `id_detail_vente` int NOT NULL AUTO_INCREMENT,
  `id_vente` int NOT NULL,
  `id_produit` int NOT NULL,
  `code_barre_scanne` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_lot` int DEFAULT NULL COMMENT 'Lot spécifique vendu',
  `quantite` int NOT NULL,
  `prix_unitaire_ht` decimal(10,2) NOT NULL,
  `tva` decimal(5,2) NOT NULL,
  `prix_unitaire_ttc` decimal(10,2) GENERATED ALWAYS AS ((`prix_unitaire_ht` * (1 + (`tva` / 100)))) STORED,
  `remise_ligne` decimal(5,2) DEFAULT '0.00',
  `sous_total_ht` decimal(10,2) GENERATED ALWAYS AS (((`quantite` * `prix_unitaire_ht`) * (1 - (`remise_ligne` / 100)))) STORED,
  PRIMARY KEY (`id_detail_vente`),
  KEY `id_produit` (`id_produit`),
  KEY `id_lot` (`id_lot`),
  KEY `idx_details_vente_vente` (`id_vente`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `details_vente`
--

INSERT INTO `details_vente` (`id_detail_vente`, `id_vente`, `id_produit`, `code_barre_scanne`, `id_lot`, `quantite`, `prix_unitaire_ht`, `tva`, `remise_ligne`) VALUES
(1, 2, 2, '', NULL, 1, 147.29, 18.00, 0.00);

--
-- Déclencheurs `details_vente`
--
DROP TRIGGER IF EXISTS `trg_after_vente_detail`;
DELIMITER $$
CREATE TRIGGER `trg_after_vente_detail` AFTER INSERT ON `details_vente` FOR EACH ROW BEGIN
    -- Mettre à jour le stock du produit
    UPDATE produits 
    SET stock_actuel = stock_actuel - NEW.quantite
    WHERE id_produit = NEW.id_produit;
    
    -- Mettre à jour le lot si applicable
    IF NEW.id_lot IS NOT NULL THEN
        UPDATE lots_produits 
        SET quantite_actuelle = quantite_actuelle - NEW.quantite
        WHERE id_lot = NEW.id_lot;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `details_ventes`
--

DROP TABLE IF EXISTS `details_ventes`;
CREATE TABLE IF NOT EXISTS `details_ventes` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_vente` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  `prix_unitaire` decimal(15,2) NOT NULL,
  `montant_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detail`),
  KEY `id_vente` (`id_vente`),
  KEY `id_produit` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `detail_inventaire`
--

DROP TABLE IF EXISTS `detail_inventaire`;
CREATE TABLE IF NOT EXISTS `detail_inventaire` (
  `id_detail_inventaire` int NOT NULL AUTO_INCREMENT,
  `id_inventaire` int NOT NULL,
  `id_produit` int NOT NULL,
  `id_lot` int DEFAULT NULL,
  `emplacement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantite_theorique` int NOT NULL,
  `quantite_comptee` int NOT NULL,
  `ecart` int GENERATED ALWAYS AS ((`quantite_comptee` - `quantite_theorique`)) STORED,
  `id_user_comptage` int NOT NULL,
  `date_comptage` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `est_corrige` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_detail_inventaire`),
  KEY `idx_id_inventaire` (`id_inventaire`),
  KEY `idx_id_produit` (`id_produit`),
  KEY `idx_id_lot` (`id_lot`),
  KEY `idx_id_user_comptage` (`id_user_comptage`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `detail_inventaire`
--

INSERT INTO `detail_inventaire` (`id_detail_inventaire`, `id_inventaire`, `id_produit`, `id_lot`, `emplacement`, `quantite_theorique`, `quantite_comptee`, `id_user_comptage`, `date_comptage`, `notes`, `est_corrige`) VALUES
(1, 5, 2, NULL, NULL, 18, 0, 1, '2026-04-21 00:58:54', NULL, 0),
(2, 5, 3, NULL, NULL, 50, 0, 1, '2026-04-21 00:58:54', NULL, 0),
(4, 6, 2, NULL, NULL, 18, 0, 1, '2026-04-21 00:59:40', NULL, 0),
(5, 6, 3, NULL, NULL, 50, 0, 1, '2026-04-21 00:59:40', NULL, 0),
(7, 7, 2, NULL, NULL, 18, 0, 1, '2026-04-21 01:19:43', NULL, 0),
(8, 7, 3, NULL, NULL, 50, 0, 1, '2026-04-21 01:19:43', NULL, 0),
(10, 8, 2, NULL, NULL, 18, 0, 1, '2026-04-22 18:12:07', NULL, 0),
(11, 8, 3, NULL, NULL, 50, 0, 1, '2026-04-22 18:12:07', NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `devises`
--

DROP TABLE IF EXISTS `devises`;
CREATE TABLE IF NOT EXISTS `devises` (
  `id_devise` int NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbole` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taux_htg` decimal(10,4) NOT NULL COMMENT 'Taux par rapport au Gourde',
  `est_defaut` tinyint(1) DEFAULT '0',
  `est_actif` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_mise_a_jour` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_devise`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devises`
--

INSERT INTO `devises` (`id_devise`, `code`, `nom`, `symbole`, `taux_htg`, `est_defaut`, `est_actif`, `date_creation`, `date_mise_a_jour`) VALUES
(1, 'HTG', 'Gourde', 'G', 1.0000, 1, 1, '2026-03-30 18:50:50', NULL),
(2, 'USD', 'Dollar US', '$', 135.0000, 0, 1, '2026-03-30 18:50:50', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `entrees_stock`
--

DROP TABLE IF EXISTS `entrees_stock`;
CREATE TABLE IF NOT EXISTS `entrees_stock` (
  `id_entree` int NOT NULL AUTO_INCREMENT,
  `numero_bon_entree` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_fournisseur` int DEFAULT NULL,
  `id_user` int NOT NULL COMMENT 'Magasinier qui fait l''entrée',
  `id_magasin` int DEFAULT NULL,
  `date_entree` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_facture` date DEFAULT NULL,
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant_total_ht` decimal(12,2) DEFAULT NULL,
  `montant_tva` decimal(12,2) DEFAULT NULL,
  `montant_total_ttc` decimal(12,2) DEFAULT NULL,
  `statut` enum('brouillon','validee','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'brouillon',
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_entree`),
  UNIQUE KEY `numero_bon_entree` (`numero_bon_entree`),
  KEY `id_fournisseur` (`id_fournisseur`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
CREATE TABLE IF NOT EXISTS `fournisseurs` (
  `id_fournisseur` int NOT NULL AUTO_INCREMENT,
  `nom_fournisseur` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_fournisseur` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_principal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `site_web` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delai_livraison` int DEFAULT NULL COMMENT 'Délai en jours',
  `conditions_paiement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fournisseur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventaire`
--

DROP TABLE IF EXISTS `inventaire`;
CREATE TABLE IF NOT EXISTS `inventaire` (
  `id_inventaire` int NOT NULL AUTO_INCREMENT,
  `numero_inventaire` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_user` int NOT NULL COMMENT 'Responsable inventaire',
  `id_magasin` int DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `type_inventaire` enum('complet','cyclique','cible') COLLATE utf8mb4_unicode_ci DEFAULT 'complet',
  `statut` enum('planifie','en_cours','valide','annule') COLLATE utf8mb4_unicode_ci DEFAULT 'planifie',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `id_user_validation` int DEFAULT NULL,
  PRIMARY KEY (`id_inventaire`),
  KEY `idx_numero_inventaire` (`numero_inventaire`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_id_magasin` (`id_magasin`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `inventaire`
--

INSERT INTO `inventaire` (`id_inventaire`, `numero_inventaire`, `id_user`, `id_magasin`, `date_debut`, `date_fin`, `type_inventaire`, `statut`, `notes`, `id_user_validation`) VALUES
(5, 'INV-20260421-4715', 1, 1, '2026-04-20 20:58:54', '2026-04-20 20:59:40', 'complet', 'annule', '', NULL),
(6, 'INV-20260421-5989', 1, 1, '2026-04-20 20:59:40', '2026-04-20 21:19:43', 'complet', 'annule', 'test inventaire ', NULL),
(7, 'INV-20260421-8179', 1, 1, '2026-04-20 21:19:43', '2026-04-22 14:12:07', 'complet', 'annule', '', NULL),
(8, 'INV-20260422-0811', 1, 1, '2026-04-22 14:12:07', NULL, 'complet', 'en_cours', '', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `inventaires`
--

DROP TABLE IF EXISTS `inventaires`;
CREATE TABLE IF NOT EXISTS `inventaires` (
  `id_inventaire` int NOT NULL AUTO_INCREMENT,
  `numero_inventaire` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_user` int NOT NULL COMMENT 'Responsable inventaire',
  `id_magasin` int DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `type_inventaire` enum('complet','cyclique','cible') COLLATE utf8mb4_unicode_ci DEFAULT 'complet',
  `statut` enum('planifie','en_cours','valide','annule') COLLATE utf8mb4_unicode_ci DEFAULT 'planifie',
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_inventaire`),
  UNIQUE KEY `numero_inventaire` (`numero_inventaire`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventaire_sessions`
--

DROP TABLE IF EXISTS `inventaire_sessions`;
CREATE TABLE IF NOT EXISTS `inventaire_sessions` (
  `id_session` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_magasin` int NOT NULL,
  `id_user` int NOT NULL,
  `type` enum('complet','partiel') COLLATE utf8mb4_unicode_ci DEFAULT 'complet',
  `status` enum('en_cours','valide','annule') COLLATE utf8mb4_unicode_ci DEFAULT 'en_cours',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `id_user_validation` int DEFAULT NULL,
  PRIMARY KEY (`id_session`),
  UNIQUE KEY `reference` (`reference`),
  KEY `id_magasin` (`id_magasin`),
  KEY `id_user` (`id_user`),
  KEY `id_user_validation` (`id_user_validation`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `inventaire_sessions`
--

INSERT INTO `inventaire_sessions` (`id_session`, `reference`, `id_magasin`, `id_user`, `type`, `status`, `notes`, `date_debut`, `date_fin`, `id_user_validation`) VALUES
(1, 'INV-20260420-0616', 1, 1, 'complet', 'annule', '', '2026-04-20 15:49:49', NULL, NULL),
(2, 'INV-20260420-9484', 1, 1, 'complet', 'annule', '', '2026-04-20 15:49:59', NULL, NULL),
(3, 'INV-20260420-9910', 1, 1, 'complet', 'annule', '', '2026-04-20 15:56:36', NULL, NULL),
(4, 'INV-20260420-0866', 1, 1, 'complet', 'annule', '', '2026-04-20 16:07:15', NULL, NULL),
(5, 'INV-20260420-8193', 1, 1, 'partiel', 'en_cours', '', '2026-04-20 16:07:43', NULL, NULL),
(6, 'INV-20260421-4715', 1, 1, 'complet', 'en_cours', '', '2026-04-20 20:58:54', NULL, NULL),
(7, 'INV-20260421-5989', 1, 1, 'complet', 'en_cours', 'test inventaire ', '2026-04-20 20:59:40', NULL, NULL),
(8, 'INV-20260421-8179', 1, 1, 'complet', 'en_cours', '', '2026-04-20 21:19:43', NULL, NULL),
(9, 'INV-20260422-0811', 1, 1, 'complet', 'en_cours', '', '2026-04-22 14:12:07', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `logs_activite`
--

DROP TABLE IF EXISTS `logs_activite`;
CREATE TABLE IF NOT EXISTS `logs_activite` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `logs_activite`
--

INSERT INTO `logs_activite` (`id_log`, `id_user`, `action`, `module`, `details`, `ip_address`, `user_agent`, `date_action`) VALUES
(1, NULL, 'MODIFICATION_STOCK', 'produit', 'Produit 2: stock de 20 à 19', NULL, NULL, '2026-04-17 19:45:54'),
(2, NULL, 'MODIFICATION_STOCK', 'produit', 'Produit 2: stock de 19 à 18', NULL, NULL, '2026-04-17 19:45:54'),
(3, NULL, 'MODIFICATION_STOCK', 'produit', 'Produit 3: stock de 0 à 50', NULL, NULL, '2026-04-20 19:27:10');

-- --------------------------------------------------------

--
-- Structure de la table `lots_produits`
--

DROP TABLE IF EXISTS `lots_produits`;
CREATE TABLE IF NOT EXISTS `lots_produits` (
  `id_lot` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL,
  `numero_lot` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Scannable',
  `numero_serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pour pièces tracées',
  `id_fournisseur` int DEFAULT NULL,
  `date_fabrication` date DEFAULT NULL,
  `date_peremption` date DEFAULT NULL,
  `quantite_initiale` int NOT NULL,
  `quantite_actuelle` int NOT NULL,
  `prix_achat_unitaire` decimal(10,2) NOT NULL,
  `date_reception` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_lot`),
  UNIQUE KEY `numero_lot` (`numero_lot`),
  KEY `id_produit` (`id_produit`),
  KEY `id_fournisseur` (`id_fournisseur`),
  KEY `idx_lots_numero` (`numero_lot`),
  KEY `idx_lots_peremption` (`date_peremption`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `lots_produits`
--
DROP TRIGGER IF EXISTS `trg_check_lot_peremption`;
DELIMITER $$
CREATE TRIGGER `trg_check_lot_peremption` AFTER INSERT ON `lots_produits` FOR EACH ROW BEGIN
    IF NEW.date_peremption IS NOT NULL AND 
       NEW.date_peremption <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN
        INSERT INTO alertes (type_alerte, niveau, id_produit, id_lot, message)
        VALUES ('peremption', 'warning', NEW.id_produit, NEW.id_lot,
                CONCAT('Lot ', NEW.numero_lot, ' expire le ', NEW.date_peremption));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `magasins`
--

DROP TABLE IF EXISTS `magasins`;
CREATE TABLE IF NOT EXISTS `magasins` (
  `id_magasin` int NOT NULL AUTO_INCREMENT,
  `code_magasin` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_magasin` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gerant` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `est_actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_magasin`),
  UNIQUE KEY `code_magasin` (`code_magasin`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `magasins`
--

INSERT INTO `magasins` (`id_magasin`, `code_magasin`, `nom_magasin`, `adresse`, `ville`, `telephone`, `email`, `gerant`, `date_creation`, `est_actif`) VALUES
(2, 'TF002', 'Total Family Lison', 'Lison, Rue Principale', 'Lison', '32353247', 'lison@totalfamily.com', 'Administrateur', '2026-04-06 14:44:21', 1),
(1, 'TF001', 'Total Family Bon Repos', 'Bon Repos, Route de Frères', 'Port-au-Prince', '48623116 / 33331301', 'bonrepos@totalfamily.com', 'Administrateur', '2026-04-06 14:44:21', 1),
(3, 'TF003', 'Total Family Croix Des Missions', 'Croix Des Missions, Boulevard Central', 'Croix-des-Bouquets', '32404285', 'croixdesmissions@totalfamily.com', 'Administrateur', '2026-04-06 14:44:21', 1);

-- --------------------------------------------------------

--
-- Structure de la table `marques`
--

DROP TABLE IF EXISTS `marques`;
CREATE TABLE IF NOT EXISTS `marques` (
  `id_marque` int NOT NULL AUTO_INCREMENT,
  `nom_marque` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays_origine` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_marque`),
  UNIQUE KEY `nom_marque` (`nom_marque`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `marques`
--

INSERT INTO `marques` (`id_marque`, `nom_marque`, `logo_url`, `pays_origine`) VALUES
(1, 'Toyota', NULL, 'Japon'),
(2, 'Honda', NULL, 'Japon'),
(3, 'Nissan', NULL, 'Japon'),
(4, 'Renault', NULL, 'France'),
(5, 'Peugeot', NULL, 'France'),
(6, 'Citroën', NULL, 'France'),
(7, 'Volkswagen', NULL, 'Allemagne'),
(8, 'BMW', NULL, 'Allemagne'),
(9, 'Mercedes-Benz', NULL, 'Allemagne'),
(10, 'Ford', NULL, 'USA');

-- --------------------------------------------------------

--
-- Structure de la table `modeles`
--

DROP TABLE IF EXISTS `modeles`;
CREATE TABLE IF NOT EXISTS `modeles` (
  `id_modele` int NOT NULL AUTO_INCREMENT,
  `id_marque` int NOT NULL,
  `nom_modele` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `annee_debut` int DEFAULT NULL,
  `annee_fin` int DEFAULT NULL,
  `type_carburant` enum('Essence','Diesel','Electrique','Hybride','GPL') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `puissance_min` int DEFAULT NULL,
  `puissance_max` int DEFAULT NULL,
  PRIMARY KEY (`id_modele`),
  KEY `id_marque` (`id_marque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

DROP TABLE IF EXISTS `mouvements_stock`;
CREATE TABLE IF NOT EXISTS `mouvements_stock` (
  `id_mouvement` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL,
  `id_lot` int DEFAULT NULL,
  `id_user` int NOT NULL,
  `type_mouvement` enum('entree','sortie_vente','retour_client','retour_fournisseur','inventaire','ajustement','transfert','perte') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL,
  `stock_avant` int NOT NULL,
  `stock_apres` int NOT NULL,
  `reference_type` enum('vente','entree','inventaire','ajustement') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL,
  `date_mouvement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `raison` text COLLATE utf8mb4_unicode_ci,
  `justificatif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_mouvement`),
  KEY `id_produit` (`id_produit`),
  KEY `id_lot` (`id_lot`),
  KEY `id_user` (`id_user`),
  KEY `idx_mouvements_date` (`date_mouvement`),
  KEY `idx_mouvements_type` (`type_mouvement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications_queue`
--

DROP TABLE IF EXISTS `notifications_queue`;
CREATE TABLE IF NOT EXISTS `notifications_queue` (
  `id_notification` int NOT NULL AUTO_INCREMENT,
  `type_notification` enum('email','sms','push') COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinataire` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('pending','envoye','echec') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_envoi` timestamp NULL DEFAULT NULL,
  `erreur_message` text COLLATE utf8mb4_unicode_ci,
  `tentative` int DEFAULT '0',
  PRIMARY KEY (`id_notification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres_systeme`
--

DROP TABLE IF EXISTS `parametres_systeme`;
CREATE TABLE IF NOT EXISTS `parametres_systeme` (
  `id_param` int NOT NULL AUTO_INCREMENT,
  `nom_param` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valeur_param` text COLLATE utf8mb4_unicode_ci,
  `type_param` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_param`),
  UNIQUE KEY `nom_param` (`nom_param`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres_systeme`
--

INSERT INTO `parametres_systeme` (`id_param`, `nom_param`, `valeur_param`, `type_param`, `description`, `date_modification`) VALUES
(1, 'ouvrir_tiroir_caisse', 'true', 'boolean', 'Ouvrir automatiquement le tiroir-caisse après chaque vente', '2026-03-31 21:05:25'),
(2, 'impression_ticket', 'true', 'boolean', 'Imprimer automatiquement le ticket de caisse', '2026-03-31 21:05:25'),
(3, 'seuil_stock_minimum_global', '5', 'integer', 'Seuil d\'alerte global pour le stock minimum', '2026-03-31 21:05:25'),
(4, 'seuil_difference_caisse', '5000', 'decimal', 'Seuil de différence acceptable pour une session de caisse', '2026-03-31 21:05:25'),
(5, 'temps_session_max', '12', 'integer', 'Durée maximale d\'une session de caisse en heures', '2026-03-31 21:05:25'),
(6, 'nom_magasin', 'TOTAL FAMILY ', 'string', 'Nom du magasin', '2026-03-31 21:05:25'),
(7, 'devise', 'HTG', 'string', 'Devise utilisée', '2026-03-31 21:05:25'),
(8, 'tva_default', '18', 'decimal', 'TVA par défaut', '2026-03-31 21:05:25'),
(9, 'devise_secondaire', 'USD', 'string', 'Devise secondaire', '2026-03-31 21:05:25'),
(10, 'taux_usd_htg', '135.00', 'decimal', 'Taux de change USD/HTG', '2026-03-31 21:05:25'),
(11, 'items_per_page', '20', 'integer', 'Nombre d\'éléments par page', '2026-03-31 21:05:25'),
(12, 'ticket_footer', 'Merci de votre visite !', 'text', 'Message de bas de ticket', '2026-03-31 21:05:25'),
(13, 'alert_stock_email', 'true', 'boolean', 'Envoyer des alertes email pour stock bas', '2026-03-31 21:05:25'),
(14, 'printer_type', 'network', 'string', 'Type d\'imprimante (network, usb, bluetooth)', '2026-03-31 21:05:25'),
(15, 'printer_ip', '192.168.1.100', 'string', 'Adresse IP de l\'imprimante réseau', '2026-03-31 21:05:25'),
(16, 'printer_port', '9100', 'integer', 'Port de l\'imprimante', '2026-03-31 21:05:25'),
(17, 'scanner_type', 'usb', 'string', 'Type de scanner (usb, network, serial)', '2026-03-31 21:05:25'),
(18, 'scanner_port', '/dev/ttyUSB0', 'string', 'Port du scanner série', '2026-03-31 21:05:25'),
(19, 'backup_enabled', 'true', 'boolean', 'Activer les sauvegardes automatiques', '2026-03-31 21:05:25'),
(20, 'backup_keep_days', '30', 'integer', 'Nombre de jours de conservation des sauvegardes', '2026-03-31 21:05:25'),
(21, 'notification_email_enabled', 'true', 'boolean', 'Activer les notifications email', '2026-03-31 21:05:25'),
(22, 'notification_sms_enabled', 'false', 'boolean', 'Activer les notifications SMS', '2026-03-31 21:05:25'),
(23, 'notification_email_recipients', '', 'text', 'Destinataires des emails (séparés par des virgules)', '2026-03-31 21:05:25');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id_permission` int NOT NULL AUTO_INCREMENT,
  `nom_permission` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `nom_permission` (`nom_permission`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id_permission`, `nom_permission`, `description`, `module`) VALUES
(1, 'user_create', 'Créer des utilisateurs', 'user'),
(2, 'user_read', 'Lire les utilisateurs', 'user'),
(3, 'user_update', 'Modifier les utilisateurs', 'user'),
(4, 'user_delete', 'Supprimer des utilisateurs', 'user'),
(5, 'product_create', 'Créer des produits', 'product'),
(6, 'product_read', 'Lire les produits', 'product'),
(7, 'product_update', 'Modifier les produits', 'product'),
(8, 'product_delete', 'Supprimer des produits', 'product'),
(9, 'sale_create', 'Effectuer des ventes', 'sale'),
(10, 'sale_read', 'Consulter les ventes', 'sale'),
(11, 'sale_cancel', 'Annuler des ventes', 'sale'),
(12, 'stock_view', 'Voir les stocks', 'stock'),
(13, 'stock_adjust', 'Ajuster les stocks', 'stock'),
(14, 'stock_entry', 'Faire des entrées de stock', 'stock'),
(15, 'report_view', 'Consulter les rapports', 'report'),
(16, 'report_export', 'Exporter les rapports', 'report'),
(17, 'cash_open', 'Ouvrir une session de caisse', 'cash'),
(18, 'cash_close', 'Fermer une session de caisse', 'cash'),
(19, 'cash_view', 'Voir les sessions de caisse', 'cash'),
(20, 'magasin_read', 'Voir les magasins', 'magasin');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

DROP TABLE IF EXISTS `produits`;
CREATE TABLE IF NOT EXISTS `produits` (
  `id_produit` int NOT NULL AUTO_INCREMENT,
  `code_barre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Scannable',
  `code_interne` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_produit` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `id_categorie` int DEFAULT NULL,
  `id_magasin` int DEFAULT NULL,
  `prix_achat_ht` decimal(10,2) NOT NULL,
  `prix_achat_usd` decimal(10,2) DEFAULT '0.00',
  `taux_change_achat` decimal(10,4) DEFAULT '1.0000',
  `prix_vente_ht` decimal(10,2) NOT NULL,
  `tva` decimal(5,2) DEFAULT '18.00',
  `prix_vente_ttc` decimal(10,2) GENERATED ALWAYS AS ((`prix_vente_ht` * (1 + (`tva` / 100)))) STORED,
  `marge_estimee` decimal(10,2) DEFAULT '0.00',
  `marge_pourcentage` decimal(5,2) DEFAULT '0.00',
  `marge` decimal(10,2) GENERATED ALWAYS AS ((`prix_vente_ht` - `prix_achat_ht`)) STORED,
  `taux_marge` decimal(5,2) GENERATED ALWAYS AS ((((`prix_vente_ht` - `prix_achat_ht`) / nullif(`prix_achat_ht`,0)) * 100)) STORED,
  `stock_actuel` int NOT NULL DEFAULT '0',
  `stock_minimum` int DEFAULT '5',
  `stock_securite` int DEFAULT '10',
  `unite_mesure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pièce',
  `poids_unitaire` decimal(10,3) DEFAULT NULL,
  `emplacement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Localisation dans le magasin',
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT '1',
  `est_promotion` tinyint(1) DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produit`),
  UNIQUE KEY `code_barre` (`code_barre`),
  UNIQUE KEY `code_interne` (`code_interne`),
  KEY `idx_produits_code_barre` (`code_barre`),
  KEY `idx_produits_nom` (`nom_produit`),
  KEY `idx_produits_stock` (`stock_actuel`),
  KEY `idx_produits_categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_produit`, `code_barre`, `code_interne`, `nom_produit`, `description`, `id_categorie`, `id_magasin`, `prix_achat_ht`, `prix_achat_usd`, `taux_change_achat`, `prix_vente_ht`, `tva`, `marge_estimee`, `marge_pourcentage`, `stock_actuel`, `stock_minimum`, `stock_securite`, `unite_mesure`, `poids_unitaire`, `emplacement`, `image_url`, `est_actif`, `est_promotion`, `date_creation`, `date_modification`) VALUES
(1, 'PROD-1', NULL, 'Filter l\'huile', 'Filter l\'huile', 1, NULL, 125.00, 0.00, 1.0000, 145.00, 10.00, 0.00, 0.00, 25, 5, 10, 'pièce', NULL, 'RACK-HUILE-001', NULL, 1, 0, '2026-03-30 14:40:54', '2026-04-22 16:11:25'),
(2, 'PROD-2', NULL, 'Filter l\'huile', 'Filter l\'huile', 1, 1, 145.00, 0.00, 1.0000, 158.00, 10.00, 0.00, 0.00, 18, 5, 10, 'pièce', NULL, 'RACK-HUILE-002', NULL, 1, 0, '2026-03-30 17:40:02', '2026-04-22 16:11:25'),
(3, 'PROD-3', NULL, 'Bougies', 'bougies', 1, 1, 1000.00, 0.00, 1.0000, 1500.00, 18.00, 0.00, 0.00, 50, 5, 20, 'pièce', NULL, '', NULL, 1, 0, '2026-04-20 19:26:39', '2026-04-22 16:11:25');

--
-- Déclencheurs `produits`
--
DROP TRIGGER IF EXISTS `trg_log_modification_stock`;
DELIMITER $$
CREATE TRIGGER `trg_log_modification_stock` AFTER UPDATE ON `produits` FOR EACH ROW BEGIN
    IF OLD.stock_actuel != NEW.stock_actuel THEN
        INSERT INTO logs_activite (id_user, action, module, details)
        VALUES (NULL, 'MODIFICATION_STOCK', 'produit',
                CONCAT('Produit ', NEW.id_produit, ': stock de ', 
                       OLD.stock_actuel, ' à ', NEW.stock_actuel));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `remises_promotion`
--

DROP TABLE IF EXISTS `remises_promotion`;
CREATE TABLE IF NOT EXISTS `remises_promotion` (
  `id_remise` int NOT NULL AUTO_INCREMENT,
  `code_promo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_remise` enum('pourcentage','montant_fixe') COLLATE utf8mb4_unicode_ci DEFAULT 'pourcentage',
  `valeur_remise` decimal(10,2) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `montant_minimum` decimal(10,2) DEFAULT '0.00',
  `utilisations_max` int DEFAULT NULL,
  `utilisations_count` int DEFAULT '0',
  `est_actif` tinyint(1) DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_remise`),
  UNIQUE KEY `code_promo` (`code_promo`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `remises_promotion`
--

INSERT INTO `remises_promotion` (`id_remise`, `code_promo`, `type_remise`, `valeur_remise`, `date_debut`, `date_fin`, `montant_minimum`, `utilisations_max`, `utilisations_count`, `est_actif`, `description`) VALUES
(1, 'BIENVENUE10', 'pourcentage', 10.00, '2026-04-05', '2026-05-05', 0.00, NULL, 0, 1, '10% de réduction pour les nouveaux clients');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id_role` int NOT NULL AUTO_INCREMENT,
  `nom_role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `niveau` int NOT NULL COMMENT '1=super_admin, 2=admin, 3=superviseur, 4=caissier, 5=magasinier',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `nom_role` (`nom_role`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id_role`, `nom_role`, `description`, `niveau`, `date_creation`) VALUES
(1, 'super_admin', 'Accès complet à toutes les fonctionnalités', 1, '2026-03-28 16:44:05'),
(2, 'admin', 'Gestion des utilisateurs, produits, et rapports', 2, '2026-03-28 16:44:05'),
(3, 'superviseur', 'Supervision des ventes, gestion des stocks', 3, '2026-03-28 16:44:05'),
(4, 'caissier', 'Gestion des ventes et ouverture de caisse', 4, '2026-03-28 16:44:05'),
(5, 'magasinier', 'Gestion des entrées de stock et inventaire', 5, '2026-03-28 16:44:05');

-- --------------------------------------------------------

--
-- Structure de la table `roles_permissions`
--

DROP TABLE IF EXISTS `roles_permissions`;
CREATE TABLE IF NOT EXISTS `roles_permissions` (
  `id_role` int NOT NULL,
  `id_permission` int NOT NULL,
  `date_attribution` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_role`,`id_permission`),
  KEY `id_permission` (`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles_permissions`
--

INSERT INTO `roles_permissions` (`id_role`, `id_permission`, `date_attribution`) VALUES
(1, 1, '2026-03-28 16:44:05'),
(1, 2, '2026-03-28 16:44:05'),
(1, 3, '2026-03-28 16:44:05'),
(1, 4, '2026-03-28 16:44:05'),
(1, 5, '2026-03-28 16:44:05'),
(1, 6, '2026-03-28 16:44:05'),
(1, 7, '2026-03-28 16:44:05'),
(1, 8, '2026-03-28 16:44:05'),
(1, 9, '2026-03-28 16:44:05'),
(1, 10, '2026-03-28 16:44:05'),
(1, 11, '2026-03-28 16:44:05'),
(1, 12, '2026-03-28 16:44:05'),
(1, 13, '2026-03-28 16:44:05'),
(1, 14, '2026-03-28 16:44:05'),
(1, 15, '2026-03-28 16:44:05'),
(1, 16, '2026-03-28 16:44:05'),
(1, 17, '2026-03-28 16:44:05'),
(1, 18, '2026-03-28 16:44:05'),
(1, 19, '2026-03-28 16:44:05'),
(1, 20, '2026-04-06 01:46:35'),
(2, 1, '2026-03-28 16:44:05'),
(2, 2, '2026-03-28 16:44:05'),
(2, 3, '2026-03-28 16:44:05'),
(2, 5, '2026-03-28 16:44:05'),
(2, 6, '2026-03-28 16:44:05'),
(2, 7, '2026-03-28 16:44:05'),
(2, 8, '2026-03-28 16:44:05'),
(2, 9, '2026-03-28 16:44:05'),
(2, 10, '2026-03-28 16:44:05'),
(2, 11, '2026-03-28 16:44:05'),
(2, 12, '2026-03-28 16:44:05'),
(2, 13, '2026-03-28 16:44:05'),
(2, 14, '2026-03-28 16:44:05'),
(2, 15, '2026-03-28 16:44:05'),
(2, 16, '2026-03-28 16:44:05'),
(2, 17, '2026-03-28 16:44:05'),
(2, 18, '2026-03-28 16:44:05'),
(2, 19, '2026-03-28 16:44:05'),
(3, 6, '2026-03-28 16:44:05'),
(3, 7, '2026-03-28 16:44:05'),
(3, 10, '2026-03-28 16:44:05'),
(3, 12, '2026-03-28 16:44:05'),
(3, 13, '2026-03-28 16:44:05'),
(3, 14, '2026-03-28 16:44:05'),
(3, 15, '2026-03-28 16:44:05'),
(3, 19, '2026-03-28 16:44:05'),
(4, 6, '2026-03-28 16:44:05'),
(4, 9, '2026-03-28 16:44:05'),
(4, 10, '2026-03-28 16:44:05'),
(4, 12, '2026-03-28 16:44:05'),
(4, 17, '2026-03-28 16:44:05'),
(4, 18, '2026-03-28 16:44:05'),
(5, 6, '2026-03-28 16:44:05'),
(5, 12, '2026-03-28 16:44:05'),
(5, 13, '2026-03-28 16:44:05'),
(5, 14, '2026-03-28 16:44:05');

-- --------------------------------------------------------

--
-- Structure de la table `sessions_caisse`
--

DROP TABLE IF EXISTS `sessions_caisse`;
CREATE TABLE IF NOT EXISTS `sessions_caisse` (
  `id_session` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL COMMENT 'Caissier',
  `id_magasin` int DEFAULT NULL,
  `date_ouverture` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fermeture` timestamp NULL DEFAULT NULL,
  `montant_initial` decimal(10,2) DEFAULT '0.00',
  `montant_especes` decimal(10,2) DEFAULT '0.00',
  `montant_carte` decimal(10,2) DEFAULT '0.00',
  `montant_mobile_money` decimal(10,2) DEFAULT '0.00',
  `montant_total_ventes` decimal(10,2) DEFAULT '0.00',
  `montant_attendu` decimal(10,2) DEFAULT '0.00',
  `difference` decimal(10,2) DEFAULT '0.00',
  `statut` enum('ouverte','fermee','suspendue') COLLATE utf8mb4_unicode_ci DEFAULT 'ouverte',
  `notes_ouverture` text COLLATE utf8mb4_unicode_ci,
  `notes_fermeture` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_session`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sessions_caisse`
--

INSERT INTO `sessions_caisse` (`id_session`, `id_user`, `id_magasin`, `date_ouverture`, `date_fermeture`, `montant_initial`, `montant_especes`, `montant_carte`, `montant_mobile_money`, `montant_total_ventes`, `montant_attendu`, `difference`, `statut`, `notes_ouverture`, `notes_fermeture`) VALUES
(1, 1, NULL, '2026-04-17 19:45:54', NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id_setting` int NOT NULL AUTO_INCREMENT,
  `nom_param` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur_param` text COLLATE utf8mb4_unicode_ci,
  `type_param` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_setting`),
  UNIQUE KEY `nom_param` (`nom_param`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id_setting`, `nom_param`, `valeur_param`, `type_param`, `categorie`, `description`, `date_modification`) VALUES
(1, 'company_name', 'Total Family Multi-Services', 'text', 'general', 'Nom de la société', '2026-04-19 03:29:11'),
(2, 'company_address', 'Rue Principale, Port-au-Prince, Haïti', 'text', 'general', 'Adresse de la société', '2026-04-19 03:29:11'),
(3, 'company_phone', '+509 1234 5678', 'text', 'general', 'Téléphone de contact', '2026-04-19 03:29:11'),
(4, 'company_email', 'contact@totalfamily.ht', 'email', 'general', 'Email de contact', '2026-04-19 03:29:11'),
(5, 'tax_rate', '18', 'decimal', 'general', 'Taux de TVA (%)', '2026-04-19 03:29:11'),
(6, 'default_currency', 'HTG', 'text', 'general', 'Devise par défaut', '2026-04-19 03:29:11'),
(7, 'maintenance_mode', 'false', 'boolean', 'general', 'Mode maintenance', '2026-04-19 03:29:11'),
(8, 'cash_drawer_enabled', 'true', 'boolean', 'cash', 'Activer le tiroir-caisse', '2026-04-19 03:29:11'),
(9, 'cash_drawer_port', 'COM1', 'text', 'cash', 'Port du tiroir-caisse', '2026-04-19 03:29:11'),
(10, 'require_cashier_login', 'true', 'boolean', 'cash', 'Obliger la connexion du caissier', '2026-04-19 03:29:11'),
(11, 'low_stock_alert', 'true', 'boolean', 'stock', 'Activer les alertes stock bas', '2026-04-19 03:29:11'),
(12, 'low_stock_threshold', '20', 'integer', 'stock', 'Seuil alerte stock (%)', '2026-04-19 03:29:11'),
(13, 'default_min_stock', '5', 'integer', 'stock', 'Stock minimum par défaut', '2026-04-19 03:29:11'),
(14, 'auto_update_stock', 'true', 'boolean', 'stock', 'Mise à jour automatique des stocks', '2026-04-19 03:29:11'),
(15, 'printer_type', 'browser', 'text', 'impression', 'Type d\'imprimante', '2026-04-19 03:29:11'),
(16, 'printer_ip', '192.168.1.100', 'text', 'impression', 'Adresse IP de l\'imprimante', '2026-04-19 03:29:11'),
(17, 'printer_port', '9100', 'text', 'impression', 'Port de l\'imprimante', '2026-04-19 03:29:11'),
(18, 'receipt_header', 'MERCI DE VOTRE VISITE', 'text', 'impression', 'En-tête du ticket', '2026-04-19 03:29:11'),
(19, 'receipt_footer', 'Cet article ne peut être échangé sans ticket', 'text', 'impression', 'Pied du ticket', '2026-04-19 03:29:11'),
(20, 'scanner_type', 'keyboard', 'text', 'scanner', 'Type de scanner', '2026-04-19 03:29:11'),
(21, 'scanner_port', 'COM3', 'text', 'scanner', 'Port du scanner', '2026-04-19 03:29:11'),
(22, 'barcode_prefix', '', 'text', 'scanner', 'Prefixe du code-barres', '2026-04-19 03:29:11'),
(23, 'barcode_suffix', 'enter', 'text', 'scanner', 'Suffixe du code-barres', '2026-04-19 03:29:11'),
(24, 'notify_low_stock', 'true', 'boolean', 'notifications', 'Notification stock bas', '2026-04-19 03:29:11'),
(25, 'notify_expiring_products', 'false', 'boolean', 'notifications', 'Notification produits expirés', '2026-04-19 03:29:11'),
(26, 'notify_daily_sales', 'true', 'boolean', 'notifications', 'Rapport quotidien des ventes', '2026-04-19 03:29:11');

-- --------------------------------------------------------

--
-- Structure de la table `taux_change_historique`
--

DROP TABLE IF EXISTS `taux_change_historique`;
CREATE TABLE IF NOT EXISTS `taux_change_historique` (
  `id_taux` int NOT NULL AUTO_INCREMENT,
  `devise` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taux` decimal(10,4) NOT NULL,
  `date_taux` date NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'manuel',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_taux`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `id_role` int NOT NULL,
  `role_niveau` int DEFAULT NULL,
  `id_magasin_attache` int DEFAULT NULL,
  `id_magasin_defaut` int DEFAULT NULL,
  `id_manager` int DEFAULT NULL COMMENT 'ID du superviseur pour les caissiers',
  `est_actif` tinyint(1) DEFAULT '1',
  `notification_email` tinyint(1) DEFAULT '1',
  `notification_sms` tinyint(1) DEFAULT '0',
  `deux_facteurs` tinyint(1) DEFAULT '0',
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`id_role`),
  KEY `idx_users_actif` (`est_actif`),
  KEY `id_manager` (`id_manager`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `email`, `telephone`, `nom`, `prenom`, `adresse`, `id_role`, `role_niveau`, `id_magasin_attache`, `id_magasin_defaut`, `id_manager`, `est_actif`, `notification_email`, `notification_sms`, `deux_facteurs`, `derniere_connexion`, `date_creation`, `date_modification`) VALUES
(1, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@autoparts.com', '123456789', 'Admin', 'Super', NULL, 1, 1, NULL, 1, NULL, 1, 1, 0, 0, NULL, '2026-03-28 16:44:05', '2026-03-31 19:44:52');

-- --------------------------------------------------------

--
-- Structure de la table `user_magasin`
--

DROP TABLE IF EXISTS `user_magasin`;
CREATE TABLE IF NOT EXISTS `user_magasin` (
  `id_user` int NOT NULL,
  `id_magasin` int NOT NULL,
  `role_magasin` enum('gerant','superviseur','caissier','magasinier') COLLATE utf8mb4_unicode_ci DEFAULT 'caissier',
  `date_affectation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`,`id_magasin`),
  KEY `id_magasin` (`id_magasin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_magasin`
--

INSERT INTO `user_magasin` (`id_user`, `id_magasin`, `role_magasin`, `date_affectation`) VALUES
(1, 1, 'gerant', '2026-03-30 16:54:51'),
(1, 2, 'gerant', '2026-03-30 16:54:51'),
(1, 3, 'gerant', '2026-03-30 16:54:51');

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

DROP TABLE IF EXISTS `ventes`;
CREATE TABLE IF NOT EXISTS `ventes` (
  `id_vente` int NOT NULL AUTO_INCREMENT,
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_session` int DEFAULT NULL,
  `id_user` int NOT NULL COMMENT 'Caissier',
  `id_magasin` int DEFAULT NULL,
  `id_client` int DEFAULT NULL,
  `date_vente` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `montant_total_ht` decimal(10,2) NOT NULL,
  `montant_tva` decimal(10,2) NOT NULL,
  `montant_total_ttc` decimal(10,2) NOT NULL,
  `montant_remise` decimal(10,2) DEFAULT '0.00',
  `montant_final` decimal(10,2) NOT NULL,
  `montant_recu` decimal(10,2) DEFAULT NULL,
  `monnaie_rendue` decimal(10,2) DEFAULT NULL,
  `mode_paiement` enum('especes','carte','mobile_money','mixte') COLLATE utf8mb4_unicode_ci DEFAULT 'especes',
  `type_vente` enum('detail','caisse') COLLATE utf8mb4_unicode_ci DEFAULT 'caisse',
  `devise` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'HTG',
  `taux_devise` decimal(10,4) DEFAULT '1.0000',
  `statut` enum('complete','annulee','remboursee') COLLATE utf8mb4_unicode_ci DEFAULT 'complete',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `id_user_annulation` int DEFAULT NULL,
  `raison_annulation` text COLLATE utf8mb4_unicode_ci,
  `date_annulation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_vente`),
  UNIQUE KEY `numero_facture` (`numero_facture`),
  KEY `id_user` (`id_user`),
  KEY `id_user_annulation` (`id_user_annulation`),
  KEY `idx_ventes_date` (`date_vente`),
  KEY `idx_ventes_session` (`id_session`),
  KEY `idx_ventes_statut` (`statut`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id_vente`, `numero_facture`, `id_session`, `id_user`, `id_magasin`, `id_client`, `date_vente`, `montant_total_ht`, `montant_tva`, `montant_total_ttc`, `montant_remise`, `montant_final`, `montant_recu`, `monnaie_rendue`, `mode_paiement`, `type_vente`, `devise`, `taux_devise`, `statut`, `notes`, `id_user_annulation`, `raison_annulation`, `date_annulation`) VALUES
(2, 'FACT-20260417-4840', 1, 1, NULL, NULL, '2026-04-17 23:45:54', 173.80, 31.28, 205.08, 0.00, 205.08, 250.00, 44.92, 'especes', 'caisse', 'HTG', 1.0000, 'complete', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_stocks_alertes`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_stocks_alertes`;
CREATE TABLE IF NOT EXISTS `vue_stocks_alertes` (
`code_barre` varchar(50)
,`emplacement` varchar(50)
,`id_produit` int
,`nom_produit` varchar(200)
,`nombre_lots` bigint
,`plus_proche_peremption` date
,`statut_stock` varchar(9)
,`stock_actuel` int
,`stock_minimum` int
,`stock_securite` int
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_top_ventes`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_top_ventes`;
CREATE TABLE IF NOT EXISTS `vue_top_ventes` (
`chiffre_affaires` decimal(32,2)
,`code_barre` varchar(50)
,`id_produit` int
,`marge` decimal(43,2)
,`nom_categorie` varchar(100)
,`nom_produit` varchar(200)
,`nombre_transactions` bigint
,`quantite_vendue` decimal(32,0)
,`stock_actuel` int
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_ventes_global`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_ventes_global`;
CREATE TABLE IF NOT EXISTS `vue_ventes_global` (
`date_vente` date
,`nombre_transactions` bigint
,`panier_moyen` decimal(14,6)
,`total_carte` decimal(32,2)
,`total_especes` decimal(32,2)
,`total_ht` decimal(32,2)
,`total_mobile_money` decimal(32,2)
,`total_remises` decimal(32,2)
,`total_ttc` decimal(32,2)
,`total_tva` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_ventes_produits`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_ventes_produits`;
CREATE TABLE IF NOT EXISTS `vue_ventes_produits` (
`chiffre_affaires_ht` decimal(32,2)
,`code_barre` varchar(50)
,`cout_achat_total` decimal(42,2)
,`id_produit` int
,`marge_totale` decimal(43,2)
,`nom_categorie` varchar(100)
,`nom_produit` varchar(200)
,`nombre_ventes` bigint
,`prix_moyen_vente` decimal(14,6)
,`quantite_vendue` decimal(32,0)
,`stock_actuel` int
,`stock_minimum` int
);

-- --------------------------------------------------------

--
-- Structure de la vue `vue_stocks_alertes`
--
DROP TABLE IF EXISTS `vue_stocks_alertes`;

DROP VIEW IF EXISTS `vue_stocks_alertes`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_stocks_alertes`  AS SELECT `p`.`id_produit` AS `id_produit`, `p`.`code_barre` AS `code_barre`, `p`.`nom_produit` AS `nom_produit`, `p`.`stock_actuel` AS `stock_actuel`, `p`.`stock_minimum` AS `stock_minimum`, `p`.`stock_securite` AS `stock_securite`, `p`.`emplacement` AS `emplacement`, (case when (`p`.`stock_actuel` = 0) then 'RUPTURE' when (`p`.`stock_actuel` <= `p`.`stock_minimum`) then 'STOCK BAS' when (`p`.`stock_actuel` <= `p`.`stock_securite`) then 'ATTENTION' else 'NORMAL' end) AS `statut_stock`, count(distinct `l`.`id_lot`) AS `nombre_lots`, min(`l`.`date_peremption`) AS `plus_proche_peremption` FROM (`produits` `p` left join `lots_produits` `l` on(((`p`.`id_produit` = `l`.`id_produit`) and (`l`.`quantite_actuelle` > 0)))) GROUP BY `p`.`id_produit` ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_top_ventes`
--
DROP TABLE IF EXISTS `vue_top_ventes`;

DROP VIEW IF EXISTS `vue_top_ventes`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_top_ventes`  AS SELECT `p`.`id_produit` AS `id_produit`, `p`.`nom_produit` AS `nom_produit`, `p`.`code_barre` AS `code_barre`, `c`.`nom_categorie` AS `nom_categorie`, sum(`dv`.`quantite`) AS `quantite_vendue`, sum(`dv`.`sous_total_ht`) AS `chiffre_affaires`, sum((`dv`.`sous_total_ht` - (`dv`.`quantite` * `p`.`prix_achat_ht`))) AS `marge`, count(distinct `v`.`id_vente`) AS `nombre_transactions`, `p`.`stock_actuel` AS `stock_actuel` FROM (((`produits` `p` left join `categories` `c` on((`p`.`id_categorie` = `c`.`id_categorie`))) join `details_vente` `dv` on((`p`.`id_produit` = `dv`.`id_produit`))) join `ventes` `v` on((`dv`.`id_vente` = `v`.`id_vente`))) WHERE ((`v`.`statut` = 'complete') AND (`v`.`date_vente` >= (now() - interval 30 day))) GROUP BY `p`.`id_produit` ORDER BY `chiffre_affaires` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_ventes_global`
--
DROP TABLE IF EXISTS `vue_ventes_global`;

DROP VIEW IF EXISTS `vue_ventes_global`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_ventes_global`  AS SELECT cast(`v`.`date_vente` as date) AS `date_vente`, count(distinct `v`.`id_vente`) AS `nombre_transactions`, sum(`v`.`montant_total_ht`) AS `total_ht`, sum(`v`.`montant_tva`) AS `total_tva`, sum(`v`.`montant_total_ttc`) AS `total_ttc`, sum(`v`.`montant_remise`) AS `total_remises`, avg(`v`.`montant_final`) AS `panier_moyen`, sum((case when (`v`.`mode_paiement` = 'especes') then `v`.`montant_final` else 0 end)) AS `total_especes`, sum((case when (`v`.`mode_paiement` = 'carte') then `v`.`montant_final` else 0 end)) AS `total_carte`, sum((case when (`v`.`mode_paiement` = 'mobile_money') then `v`.`montant_final` else 0 end)) AS `total_mobile_money` FROM `ventes` AS `v` WHERE (`v`.`statut` = 'complete') GROUP BY cast(`v`.`date_vente` as date) ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_ventes_produits`
--
DROP TABLE IF EXISTS `vue_ventes_produits`;

DROP VIEW IF EXISTS `vue_ventes_produits`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_ventes_produits`  AS SELECT `p`.`id_produit` AS `id_produit`, `p`.`code_barre` AS `code_barre`, `p`.`nom_produit` AS `nom_produit`, `c`.`nom_categorie` AS `nom_categorie`, count(`dv`.`id_detail_vente`) AS `nombre_ventes`, sum(`dv`.`quantite`) AS `quantite_vendue`, sum(`dv`.`sous_total_ht`) AS `chiffre_affaires_ht`, sum((`dv`.`quantite` * `p`.`prix_achat_ht`)) AS `cout_achat_total`, sum((`dv`.`sous_total_ht` - (`dv`.`quantite` * `p`.`prix_achat_ht`))) AS `marge_totale`, avg(`dv`.`prix_unitaire_ht`) AS `prix_moyen_vente`, `p`.`stock_actuel` AS `stock_actuel`, `p`.`stock_minimum` AS `stock_minimum` FROM (((`produits` `p` left join `categories` `c` on((`p`.`id_categorie` = `c`.`id_categorie`))) left join `details_vente` `dv` on((`p`.`id_produit` = `dv`.`id_produit`))) left join `ventes` `v` on(((`dv`.`id_vente` = `v`.`id_vente`) and (`v`.`statut` = 'complete')))) GROUP BY `p`.`id_produit` ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `alertes`
--
ALTER TABLE `alertes`
  ADD CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE SET NULL,
  ADD CONSTRAINT `alertes_ibfk_2` FOREIGN KEY (`id_lot`) REFERENCES `lots_produits` (`id_lot`) ON DELETE SET NULL,
  ADD CONSTRAINT `alertes_ibfk_3` FOREIGN KEY (`id_session`) REFERENCES `sessions_caisse` (`id_session`) ON DELETE SET NULL,
  ADD CONSTRAINT `alertes_ibfk_4` FOREIGN KEY (`id_user_traitement`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id_categorie`) ON DELETE SET NULL;

--
-- Contraintes pour la table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`id_modele_vehicule`) REFERENCES `modeles` (`id_modele`) ON DELETE SET NULL;

--
-- Contraintes pour la table `compatibilite_produits`
--
ALTER TABLE `compatibilite_produits`
  ADD CONSTRAINT `compatibilite_produits_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  ADD CONSTRAINT `compatibilite_produits_ibfk_2` FOREIGN KEY (`id_modele`) REFERENCES `modeles` (`id_modele`) ON DELETE CASCADE;

--
-- Contraintes pour la table `details_entrees_stock`
--
ALTER TABLE `details_entrees_stock`
  ADD CONSTRAINT `details_entrees_stock_ibfk_1` FOREIGN KEY (`id_entree`) REFERENCES `entrees_stock` (`id_entree`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_entrees_stock_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `details_entrees_stock_ibfk_3` FOREIGN KEY (`id_lot`) REFERENCES `lots_produits` (`id_lot`);

--
-- Contraintes pour la table `details_inventaire`
--
ALTER TABLE `details_inventaire`
  ADD CONSTRAINT `details_inventaire_ibfk_1` FOREIGN KEY (`id_inventaire`) REFERENCES `inventaires` (`id_inventaire`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_inventaire_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `details_inventaire_ibfk_3` FOREIGN KEY (`id_lot`) REFERENCES `lots_produits` (`id_lot`),
  ADD CONSTRAINT `details_inventaire_ibfk_4` FOREIGN KEY (`id_user_comptage`) REFERENCES `users` (`id_user`);

--
-- Contraintes pour la table `details_vente`
--
ALTER TABLE `details_vente`
  ADD CONSTRAINT `details_vente_ibfk_1` FOREIGN KEY (`id_vente`) REFERENCES `ventes` (`id_vente`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_vente_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `details_vente_ibfk_3` FOREIGN KEY (`id_lot`) REFERENCES `lots_produits` (`id_lot`);

--
-- Contraintes pour la table `details_ventes`
--
ALTER TABLE `details_ventes`
  ADD CONSTRAINT `details_ventes_ibfk_1` FOREIGN KEY (`id_vente`) REFERENCES `ventes` (`id_vente`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_ventes_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`);

--
-- Contraintes pour la table `entrees_stock`
--
ALTER TABLE `entrees_stock`
  ADD CONSTRAINT `entrees_stock_ibfk_1` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE SET NULL,
  ADD CONSTRAINT `entrees_stock_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Contraintes pour la table `inventaires`
--
ALTER TABLE `inventaires`
  ADD CONSTRAINT `inventaires_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Contraintes pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD CONSTRAINT `logs_activite_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Contraintes pour la table `lots_produits`
--
ALTER TABLE `lots_produits`
  ADD CONSTRAINT `lots_produits_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  ADD CONSTRAINT `lots_produits_ibfk_2` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `modeles`
--
ALTER TABLE `modeles`
  ADD CONSTRAINT `modeles_ibfk_1` FOREIGN KEY (`id_marque`) REFERENCES `marques` (`id_marque`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`id_lot`) REFERENCES `lots_produits` (`id_lot`),
  ADD CONSTRAINT `mouvements_stock_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`) ON DELETE SET NULL;

--
-- Contraintes pour la table `roles_permissions`
--
ALTER TABLE `roles_permissions`
  ADD CONSTRAINT `roles_permissions_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_permissions_ibfk_2` FOREIGN KEY (`id_permission`) REFERENCES `permissions` (`id_permission`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions_caisse`
--
ALTER TABLE `sessions_caisse`
  ADD CONSTRAINT `sessions_caisse_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`id_manager`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`id_manager`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Contraintes pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD CONSTRAINT `ventes_ibfk_1` FOREIGN KEY (`id_session`) REFERENCES `sessions_caisse` (`id_session`),
  ADD CONSTRAINT `ventes_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `ventes_ibfk_3` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE SET NULL,
  ADD CONSTRAINT `ventes_ibfk_4` FOREIGN KEY (`id_user_annulation`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `ventes_ibfk_5` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE SET NULL;

DELIMITER $$
--
-- Évènements
--
DROP EVENT IF EXISTS `evt_clean_old_logs`$$
CREATE DEFINER=`root`@`localhost` EVENT `evt_clean_old_logs` ON SCHEDULE EVERY 1 MONTH STARTS '2026-03-28 12:44:05' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Supprimer les logs de plus de 6 mois
    DELETE FROM logs_activite WHERE date_action < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Supprimer les notifications de plus de 3 mois
    DELETE FROM notifications_queue WHERE date_creation < DATE_SUB(NOW(), INTERVAL 3 MONTH);
    
    -- Supprimer les alertes lues de plus de 1 mois
    DELETE FROM alertes WHERE est_lue = 1 AND date_creation < DATE_SUB(NOW(), INTERVAL 1 MONTH);
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
