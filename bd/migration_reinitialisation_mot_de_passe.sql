-- Migration : jetons temporaires de réinitialisation des mots de passe
USE zig_healthy_db;

CREATE TABLE IF NOT EXISTS jetons_reinitialisation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL UNIQUE,
    empreinte CHAR(64) NOT NULL UNIQUE,
    expire_le DATETIME NOT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jetons_reinitialisation_utilisateur
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_jetons_reinitialisation_expiration (expire_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
