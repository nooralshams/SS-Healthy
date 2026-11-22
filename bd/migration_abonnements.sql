-- =====================================================
-- ZIG HEALTHY - Migration : suivi des abonnements
-- =====================================================
-- À exécuter dans phpMyAdmin UNIQUEMENT si la base zig_healthy_db
-- a déjà été importée avec l'ancienne version du script.
-- (Ces tables sont déjà incluses dans zig_healthy_db.sql complet.)
-- =====================================================

USE zig_healthy_db;

-- Référentiel des formules d'abonnement proposées par le restaurant.
CREATE TABLE IF NOT EXISTS formules_abonnement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    nb_repas_jour INT NOT NULL DEFAULT 1 COMMENT 'Nombre de repas par jour',
    prix DECIMAL(10,0) NOT NULL COMMENT 'Prix en FCFA',
    duree_jours INT NOT NULL DEFAULT 7 COMMENT 'Durée de la formule en jours',
    icone VARCHAR(10) DEFAULT '⚡',
    actif TINYINT(1) DEFAULT 1 COMMENT '1 = visible sur le site'
) ENGINE=InnoDB;

-- Souscription d'un client à une formule (suivi par statut).
CREATE TABLE IF NOT EXISTS abonnements (
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

-- Ajoute uniquement les formules manquantes : le script peut être relancé sans doublons.
INSERT INTO formules_abonnement (nom, description, nb_repas_jour, prix, duree_jours, icone)
SELECT 'Déjeuner seul', 'Un repas équilibré chaque jour', 1, 15000, 7, '⚡'
WHERE NOT EXISTS (SELECT 1 FROM formules_abonnement WHERE nb_repas_jour = 1);

INSERT INTO formules_abonnement (nom, description, nb_repas_jour, prix, duree_jours, icone)
SELECT 'Petit-déjeuner + Déjeuner', 'Deux repas équilibrés par jour', 2, 30000, 7, '✓'
WHERE NOT EXISTS (SELECT 1 FROM formules_abonnement WHERE nb_repas_jour = 2);

INSERT INTO formules_abonnement (nom, description, nb_repas_jour, prix, duree_jours, icone)
SELECT 'Petit-déjeuner + Déjeuner + Dîner', 'Trois repas équilibrés par jour', 3, 50000, 7, '◎'
WHERE NOT EXISTS (SELECT 1 FROM formules_abonnement WHERE nb_repas_jour = 3);

-- Met à jour les tarifs affichés, sans modifier le prix historique des abonnements déjà souscrits.
UPDATE formules_abonnement
SET nom = 'Déjeuner seul', description = 'Un repas équilibré chaque jour', prix = 15000, duree_jours = 7, icone = '⚡'
WHERE nb_repas_jour = 1;

UPDATE formules_abonnement
SET nom = 'Petit-déjeuner + Déjeuner', description = 'Deux repas équilibrés par jour', prix = 30000, duree_jours = 7, icone = '✓'
WHERE nb_repas_jour = 2;

UPDATE formules_abonnement
SET nom = 'Petit-déjeuner + Déjeuner + Dîner', description = 'Trois repas équilibrés par jour', prix = 50000, duree_jours = 7, icone = '◎'
WHERE nb_repas_jour = 3;
