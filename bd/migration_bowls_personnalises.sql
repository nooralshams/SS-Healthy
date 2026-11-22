-- Enregistre les bowls personnalises sans les faire passer pour un plat catalogue.
CREATE TABLE IF NOT EXISTS details_commande_bowls (
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
