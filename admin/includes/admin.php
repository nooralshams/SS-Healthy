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

/** Nombre d'éléments en attente (pour les badges du menu admin) */
function nb_en_attente($pdo, $table) {
    $tables_autorisees = ['commandes', 'abonnements'];
    if (!in_array($table, $tables_autorisees, true)) {
        return 0;
    }
    return (int) $pdo->query("SELECT COUNT(*) FROM $table WHERE statut = 'en_attente'")->fetchColumn();
}
