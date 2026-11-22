-- Migration re-executable : ajoute le mode de reglement si absent.
SET @has_mode_paiement = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commandes' AND COLUMN_NAME = 'mode_paiement'
);
SET @sql_mode_paiement = IF(
    @has_mode_paiement = 0,
    "ALTER TABLE commandes ADD COLUMN mode_paiement ENUM('especes','wave','orange_money') NOT NULL DEFAULT 'especes' AFTER mode",
    'SELECT 1'
);
PREPARE stmt_mode_paiement FROM @sql_mode_paiement;
EXECUTE stmt_mode_paiement;
DEALLOCATE PREPARE stmt_mode_paiement;

CREATE TABLE IF NOT EXISTS messages_contact (
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
