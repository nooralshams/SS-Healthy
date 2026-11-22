<?php
// =====================================================
// SS'Healthy - Fonctions de la page de connexion
// =====================================================
// Règle : après SEUIL_BLOCAGE_CONNEXION échecs consécutifs, le compte est bloqué.
// Un compte bloqué ne peut être débloqué que par l'administration.

const SEUIL_BLOCAGE_CONNEXION = 5;

/**
 * Enregistre un échec de connexion et bloque le compte au 5e échec.
 * Retourne true si le compte vient d'être bloqué.
 *
 * L'affectation de « bloque » est volontairement placée AVANT celle du compteur :
 * MySQL évalue les affectations de gauche à droite, donc la condition lit
 * encore l'ancienne valeur de tentatives_connexion.
 */
function enregistrer_echec_connexion(PDO $pdo, int $utilisateurId): bool {
    $pdo->prepare(
        'UPDATE utilisateurs
         SET bloque = CASE WHEN tentatives_connexion + 1 >= ? THEN 1 ELSE bloque END,
             tentatives_connexion = tentatives_connexion + 1
         WHERE id = ?'
    )->execute([SEUIL_BLOCAGE_CONNEXION, $utilisateurId]);

    $stmt = $pdo->prepare('SELECT bloque FROM utilisateurs WHERE id = ?');
    $stmt->execute([$utilisateurId]);

    return (int) $stmt->fetchColumn() === 1;
}

/** Remet à zéro le compteur d'échecs après une connexion réussie. */
function reinitialiser_tentatives_connexion(PDO $pdo, int $utilisateurId): void {
    $pdo->prepare('UPDATE utilisateurs SET tentatives_connexion = 0 WHERE id = ?')
        ->execute([$utilisateurId]);
}