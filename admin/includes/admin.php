<?php
// =====================================================
// ZIG HEALTHY - Fonctions communes de l'espace admin
// =====================================================
// Ce fichier est inclus par le header de l'admin.
// Il regroupe les utilitaires propres à l'administration :
// libellés des statuts, compteurs, etc.
// Il suppose que config.php et fonctions.php sont déjà chargés.

/** Libellés lisibles des statuts de commandes */
function libelle_statut_commande($statut) {
    $libelles = [
        'en_attente'     => 'En attente',
        'confirmee'      => 'Confirmée',
        'en_preparation' => 'En préparation',
        'prete'          => 'Prête',
        'livree'         => 'Livrée',
        'annulee'        => 'Annulée'
    ];
    return $libelles[$statut] ?? $statut;
}

/** Libellés lisibles des modes de récupération */
function libelle_mode_commande($mode) {
    $libelles = ['sur_place' => 'Sur place', 'a_emporter' => 'À emporter', 'livraison' => 'Livraison'];
    return $libelles[$mode] ?? $mode;
}

/** Libellés lisibles des statuts d'abonnements */
function libelle_statut_abonnement($statut) {
    $libelles = [
        'en_attente' => 'En attente',
        'actif'      => 'Actif',
        'termine'    => 'Terminé',
        'annule'     => 'Annulé'
    ];
    return $libelles[$statut] ?? $statut;
}

/** Statuts accessibles depuis chaque étape du parcours d'une commande. */
function transitions_statuts_commande(): array {
    return [
        'en_attente' => ['confirmee', 'annulee'],
        'confirmee' => ['en_preparation', 'annulee'],
        'en_preparation' => ['prete', 'annulee'],
        'prete' => ['livree', 'annulee'],
        'livree' => [],
        'annulee' => [],
    ];
}

/** Statuts accessibles depuis chaque étape du parcours d'un abonnement. */
function transitions_statuts_abonnement(): array {
    return [
        'en_attente' => ['actif', 'annule'],
        'actif' => ['termine', 'annule'],
        'termine' => [],
        'annule' => [],
    ];
}

/** Indique si le statut demandé est une transition autorisée. */
function transition_statut_autorisee($courant, $nouveau, array $transitions): bool {
    return in_array($nouveau, $transitions[$courant] ?? [], true);
}

/** Nombre d'éléments en attente (pour les badges du menu admin) */
function nb_en_attente($pdo, $table) {
    $tables_autorisees = ['commandes', 'abonnements'];
    if (!in_array($table, $tables_autorisees, true)) {
        return 0;
    }
    return (int) $pdo->query("SELECT COUNT(*) FROM $table WHERE statut = 'en_attente'")->fetchColumn();
}
