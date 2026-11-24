<?php
// =====================================================
// SS'Healthy - Fonctions de la page de connexion
// =====================================================
// Règle : après SEUIL_BLOCAGE_CONNEXION échecs consécutifs, le compte est bloqué.
// Un compte bloqué ne peut être débloqué que par l’administration
// (admin/clients.php) ou, si le seul administrateur est bloqué, par
// l’outil en ligne de commande outils/debloquer_admin.php.

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

/**
 * Débloque un compte : bloque = 0 et compteur d'échecs remis à zéro.
 * Si $role est fourni, seul un compte de ce rôle peut être débloqué.
 * Retourne true si le compte visé existe.
 */
function debloquer_compte(PDO $pdo, int $utilisateurId, ?string $role = null): bool {
    $condition = 'id = ?' . ($role !== null ? ' AND role = ?' : '');
    $parametres = $role !== null ? [$utilisateurId, $role] : [$utilisateurId];

    $pdo->prepare("UPDATE utilisateurs SET bloque = 0, tentatives_connexion = 0 WHERE $condition")
        ->execute($parametres);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE $condition");
    $stmt->execute($parametres);
    return (int) $stmt->fetchColumn() === 1;
}
