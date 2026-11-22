-- =====================================================
-- ZIG HEALTHY - Migration : suivi des mesures IMC
-- =====================================================
-- À exécuter dans phpMyAdmin pour permettre à l'administrateur
-- de voir les résultats IMC des clients et de leur proposer
-- le régime (objectif) le plus adapté.
-- =====================================================

-- Historique des calculs d'IMC effectués par les clients connectés.
-- Chaque calcul est lié à l'utilisateur et mémorise le poids, la taille,
-- le résultat et l'objectif nutritionnel recommandé automatiquement.
CREATE TABLE IF NOT EXISTS mesures_imc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    poids DECIMAL(5,1) NOT NULL COMMENT 'Poids en kg',
    taille DECIMAL(5,1) NOT NULL COMMENT 'Taille en cm',
    imc DECIMAL(4,1) NOT NULL COMMENT 'Indice de masse corporelle calculé',
    interpretation VARCHAR(50) NOT NULL,
    objectif_recommande ENUM('perte_gras','prise_masse','diabete','maintien') NOT NULL,
    date_mesure DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;
