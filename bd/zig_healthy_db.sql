-- =====================================================
-- ZIG HEALTHY - Base de données
-- Mémoire de licence IAGE - ISM Ziguinchor
-- À importer dans phpMyAdmin (WAMP, port 3308)
-- =====================================================

CREATE DATABASE IF NOT EXISTS zig_healthy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zig_healthy_db;

-- =====================================================
-- TABLE UTILISATEURS
-- =====================================================
-- Stocke les informations des clients et de l'administrateur.
-- Un utilisateur possède un objectif nutritionnel et un rôle.
-- =====================================================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    objectif ENUM('perte_gras','prise_masse','diabete','maintien') DEFAULT 'maintien',
    role ENUM('client','admin') DEFAULT 'client',
    bloque TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = compte bloqué',
    tentatives_connexion TINYINT UNSIGNED NOT NULL DEFAULT 0,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE jetons_reinitialisation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL UNIQUE,
    empreinte CHAR(64) NOT NULL UNIQUE,
    expire_le DATETIME NOT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jetons_reinitialisation_utilisateur
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_jetons_reinitialisation_expiration (expire_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE CATEGORIES
-- =====================================================
-- Permet de classer les plats selon leur type : petit-déjeuner,
-- repas, salades, boissons, encas. Cette table sert à filtrer le menu.
-- =====================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- =====================================================
-- TABLE PLATS
-- =====================================================
-- Représente chaque plat du restaurant.
-- Chaque plat est associé à une catégorie, possède des valeurs nutritionnelles
-- et peut être compatible avec un ou plusieurs objectifs : perte de gras,
-- prise de masse, diabète ou maintien.
-- =====================================================
CREATE TABLE plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,0) NOT NULL COMMENT 'Prix en FCFA',
    calories INT NOT NULL COMMENT 'kcal par portion',
    proteines DECIMAL(5,1) NOT NULL COMMENT 'grammes',
    glucides DECIMAL(5,1) NOT NULL COMMENT 'grammes',
    lipides DECIMAL(5,1) NOT NULL COMMENT 'grammes',
    ig_faible TINYINT(1) DEFAULT 0 COMMENT 'Index glycémique faible (adapté diabète)',
    image VARCHAR(255) DEFAULT 'defaut.jpg',
    disponible TINYINT(1) DEFAULT 1,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE PLAT_OBJECTIFS
-- =====================================================
-- Table de liaison entre plats et objectifs.
-- Un plat peut correspondre à plusieurs profils nutritionnels.
-- Exemple : un repas peut convenir à la perte de gras et au maintien.
-- =====================================================
CREATE TABLE plat_objectifs (
    plat_id INT NOT NULL,
    objectif ENUM('perte_gras','prise_masse','diabete','maintien') NOT NULL,
    PRIMARY KEY (plat_id, objectif),
    FOREIGN KEY (plat_id) REFERENCES plats(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE COMMANDES
-- =====================================================
-- Représente une commande passée par un client.
-- Le statut permet de suivre la commande du début à la livraison.
-- Le total est stocké à la commande pour simplifier le calcul du résumé.
-- =====================================================
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    creneau VARCHAR(20) NOT NULL COMMENT 'Délai souhaité pour le retrait ou la livraison',
    mode ENUM('sur_place','a_emporter','livraison') DEFAULT 'a_emporter',
    mode_paiement ENUM('especes','wave','orange_money') NOT NULL DEFAULT 'especes',
    adresse_livraison VARCHAR(255),
    statut ENUM('en_attente','confirmee','en_preparation','prete','livree','annulee') DEFAULT 'en_attente',
    total DECIMAL(10,0) NOT NULL DEFAULT 0,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE DETAILS_COMMANDE
-- =====================================================
-- Contient les lignes d'une commande.
-- Chaque ligne représente un plat commandé avec sa quantité et son prix unitaire.
-- Cela permet de reconstruire précisément le panier et la facture.
-- =====================================================
CREATE TABLE details_commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    plat_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(10,0) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (plat_id) REFERENCES plats(id)
) ENGINE=InnoDB;

CREATE TABLE details_commande_bowls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    nom VARCHAR(120) NOT NULL,
    composition TEXT NOT NULL COMMENT 'Composition serialisee en JSON',
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(10,0) NOT NULL,
    CONSTRAINT fk_details_bowls_commande
        FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    INDEX idx_details_bowls_commande (commande_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages_contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telephone VARCHAR(30) NULL,
    message TEXT NOT NULL,
    statut ENUM('nouveau','traite') NOT NULL DEFAULT 'nouveau',
    date_message DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_contact_utilisateur
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_messages_contact_statut_date (statut, date_message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DONNÉES DE TEST
-- =====================================================
-- Ces données permettent d'avoir un site fonctionnel immédiatement après
-- l'import du script SQL. Elles créent un compte admin, plusieurs catégories,
-- des plats de démonstration et les associations objectifs/plat.

INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, objectif, role) VALUES
('Diallo', 'Salma Marieme', 'salmamariamadiallo@gmail.com', '!A_DEFINIR', '77 000 00 00', 'maintien', 'admin');

INSERT INTO categories (nom, description) VALUES
('Petit-déjeuner', 'Options équilibrées pour commencer la journée'),
('Déjeuner', 'Plats complets à savourer à midi'),
('Dîner', 'Plats légers et équilibrés pour le soir'),
('Jus naturels', 'Boissons naturelles préparées sans sucre ajouté'),
('Snacks healthy', 'Petites faims saines et équilibrées');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible) VALUES
(1, 'Bowl avoine & mangue', 'Flocons d''avoine, lait végétal, mangue fraîche de Casamance, graines de chia', 2000, 380, 12.0, 58.0, 9.0, 1),
(1, 'Omelette blanche & avocat', '4 blancs d''œufs, avocat, tomates cerises, pain complet', 2500, 340, 26.0, 22.0, 15.0, 1),
(1, 'Wrap de poulet équilibré', 'Wrap au poulet, œufs durs, fromage blanc, tomates et salade', 3000, 420, 36.0, 34.0, 13.0, 1),
(2, 'Poulet grillé & légumes vapeur', 'Filet de poulet mariné au citron, brocoli, carottes, riz complet', 3500, 520, 45.0, 48.0, 12.0, 1),
(2, 'Poisson braisé light', 'Thiof braisé, salade verte, patate douce rôtie', 4000, 480, 42.0, 38.0, 14.0, 1),
(2, 'Mafé léger au poulet', 'Version allégée du mafé : pâte d''arachide dosée, poulet sans peau, riz complet', 3500, 610, 38.0, 55.0, 22.0, 0),
(2, 'Bœuf sauté prise de masse', 'Bœuf maigre sauté, riz complet double portion, haricots niébé', 4500, 780, 52.0, 82.0, 18.0, 0),
(2, 'Salade de poulet fraîche', 'Poulet, maïs, avocat, tomates fraîches et concombre', 3500, 430, 38.0, 28.0, 16.0, 1),
(2, 'Concombre farci au thon', 'Concombre garni de thon léger, salade fraîche et fines herbes', 2800, 300, 29.0, 18.0, 9.0, 1),
(2, 'Fonio au poulet', 'Fonio au poulet accompagné d’une salade de chou, oignons et tomates', 3500, 470, 37.0, 50.0, 12.0, 1),
(3, 'Crevettes grillées & légumes', 'Crevettes, brocolis et pommes de terre grillées', 4000, 460, 34.0, 42.0, 14.0, 1),
(3, 'Salade niébé & thon', 'Haricots niébé, thon, oignons rouges, poivrons, vinaigrette citron', 2500, 390, 32.0, 35.0, 11.0, 1),
(4, 'Bissap zéro sucre', 'Infusion de bissap naturelle, gingembre, sans sucre ajouté', 1000, 15, 0.0, 3.0, 0.0, 1),
(4, 'Jus naturel bissap gingembre', 'Bissap naturel préparé avec du gingembre, sans sucre ajouté', 1200, 45, 0.5, 10.0, 0.2, 1),
(4, 'Jus naturel orange citron', 'Orange fraîche pressée avec citron, sans sucre ajouté', 1500, 90, 2.0, 20.0, 0.5, 1),
(4, 'Jus vert detox', 'Concombre, menthe, citron vert et gingembre, sans sucre ajouté', 1500, 70, 2.0, 15.0, 0.5, 1),
(4, 'Smoothie vert détox', 'Moringa, concombre, citron vert, menthe', 1500, 90, 3.0, 18.0, 1.0, 1),
(5, 'Energy balls dattes-cajou', '3 boules énergétiques maison, dattes et noix de cajou', 1500, 210, 5.0, 28.0, 9.0, 0),
(5, 'Yaourt grec fruits frais', 'Yaourt nature, fruits frais et graines sans sucre ajouté', 1800, 180, 14.0, 20.0, 5.0, 1),
(5, 'Guacamole crudités', 'Guacamole maison accompagné de bâtonnets de légumes frais', 2000, 220, 4.0, 18.0, 14.0, 1);

-- Pour corriger une base déjà importée, exécuter manuellement :
-- UPDATE plats SET nom = 'Bœuf sauté prise de masse' WHERE nom = 'B?uf sauté prise de masse';

INSERT INTO plat_objectifs (plat_id, objectif) VALUES
(1,'maintien'),(1,'diabete'),
(2,'perte_gras'),(2,'diabete'),(2,'maintien'),
(3,'perte_gras'),(3,'prise_masse'),(3,'diabete'),(3,'maintien'),
(4,'perte_gras'),(4,'diabete'),(4,'maintien'),
(5,'maintien'),(5,'prise_masse'),
(6,'prise_masse'),
(7,'perte_gras'),(7,'diabete'),(7,'maintien'),
(8,'perte_gras'),(8,'maintien'),(8,'diabete'),
(9,'perte_gras'),(9,'diabete'),(9,'maintien'),
(10,'perte_gras'),(10,'diabete'),(10,'maintien'),
(11,'prise_masse'),
(12,'maintien'),(12,'prise_masse'),
(13,'perte_gras'),(13,'maintien'),
(14,'perte_gras'),(14,'maintien'),
(15,'perte_gras'),(15,'maintien'),
(16,'perte_gras'),(16,'maintien'),
(17,'perte_gras'),(17,'maintien'),
(18,'perte_gras'),(18,'maintien'),
(19,'perte_gras'),(19,'maintien'),
(20,'perte_gras'),(20,'maintien');

-- =====================================================
-- TABLE FORMULES_ABONNEMENT
-- =====================================================
-- Référentiel des formules d'abonnement proposées par le restaurant.
-- La page abonnements.php lit cette table : les cartes affichées
-- proviennent donc de la base de données et plus du HTML en dur.
-- =====================================================
CREATE TABLE formules_abonnement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    nb_repas_jour INT NOT NULL DEFAULT 1 COMMENT 'Nombre de repas par jour',
    prix DECIMAL(10,0) NOT NULL COMMENT 'Prix en FCFA',
    duree_jours INT NOT NULL DEFAULT 7 COMMENT 'Durée de la formule en jours',
    icone VARCHAR(10) DEFAULT '⚡',
    actif TINYINT(1) DEFAULT 1 COMMENT '1 = visible sur le site'
) ENGINE=InnoDB;

-- =====================================================
-- TABLE ABONNEMENTS
-- =====================================================
-- Représente la souscription d'un client à une formule.
-- Le statut permet le suivi : en_attente -> actif -> termine (ou annule).
-- Le prix est figé au moment de la souscription (historique fidèle).
-- =====================================================
CREATE TABLE abonnements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    formule_id INT NOT NULL,
    date_souscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut ENUM('en_attente','actif','termine','annule') DEFAULT 'en_attente',
    adresse_livraison VARCHAR(255),
    prix DECIMAL(10,0) NOT NULL COMMENT 'Prix payé en FCFA au moment de la souscription',
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (formule_id) REFERENCES formules_abonnement(id)
) ENGINE=InnoDB;

-- Formules proposées (reprises de la page abonnements)
INSERT INTO formules_abonnement (nom, description, nb_repas_jour, prix, duree_jours, icone) VALUES
('Déjeuner seul', 'Un repas équilibré chaque jour', 1, 15000, 7, '⚡'),
('Petit-déjeuner + Déjeuner', 'Deux repas équilibrés par jour', 2, 30000, 7, '✓'),
('Petit-déjeuner + Déjeuner + Dîner', 'Trois repas équilibrés par jour', 3, 50000, 7, '◎');
