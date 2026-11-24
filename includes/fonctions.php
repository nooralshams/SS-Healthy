<?php
// =====================================================
// ZIG HEALTHY - Fonctions utilitaires
// =====================================================
// Ce fichier regroupe les fonctions réutilisées dans plusieurs pages :
// - échappement HTML pour sécuriser les données affichées,
// - formatage du prix,
// - vérification de connexion / rôle admin,
// - calcul du panier et gestion des créneaux.

/** Sécurise une chaîne avant affichage */
function e($chaine) {
    return htmlspecialchars($chaine ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formate un prix en FCFA */
function prix_fcfa($montant) {
    return '<span class="prix-affichage"><span class="prix-montant">'
        . number_format($montant, 0, ',', ' ')
        . '</span><span class="prix-devise">FCFA</span></span>';
}

/** Retourne le jeton CSRF de la session courante. */
function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

/** Génère le champ CSRF à inclure dans les formulaires POST. */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Retourne les objectifs autorisés pour un IMC, le premier étant conseillé. */
function objectifs_autorises($imc) {
    if (!is_numeric($imc)) {
        return [];
    }

    // Règle unique d'arrondi, identique à determinerProfil() et à mesures_imc.imc (DECIMAL(4,1)).
    $imc = round((float) $imc, 1);

    if ($imc < 18.5) {
        return ['prise_masse', 'diabete'];
    }
    if ($imc < 25) {
        return ['maintien', 'prise_masse', 'diabete'];
    }
    if ($imc < 30) {
        return ['perte_gras', 'diabete'];
    }

    return ['perte_gras', 'diabete'];
}

/** Vérifie si un utilisateur est connecté */
function est_connecte() {
    return isset($_SESSION['utilisateur_id']);
}

/** Vérifie si l'utilisateur connecté est admin */
function est_admin() {
    return est_connecte() && ($_SESSION['role'] ?? '') === 'admin';
}

/** Nombre total d'articles dans le panier */
function nb_articles_panier() {
    if (empty($_SESSION['panier'])) return 0;
    return array_sum(array_column($_SESSION['panier'], 'quantite'));
}

/** Le restaurant est-il ouvert actuellement ? (9h - 21h) */
function est_ouvert() {
    $h = (int) date('G');
    return $h >= HEURE_OUVERTURE && $h < HEURE_FERMETURE;
}

/** Retourne les délais de retrait/livraison proposés à la commande. */
function creneaux_disponibles() {
    return ['Sous 1 h', 'Sous 2 h', 'Pour midi', 'Ce soir'];
}

/** Applique la règle métier : aucun créneau inférieur à 45 minutes. */
function creneau_autorise($creneau) {
    return is_string($creneau)
        && in_array($creneau, ['Sous 1 h', 'Sous 2 h', 'Pour midi', 'Ce soir'], true);
}

/** Classe un IMC selon une règle stable, sur la valeur arrondie au dixième. */
function determinerProfil($imc) {
    if (!is_numeric($imc)) {
        return 'invalide';
    }

    $imcArrondi = round((float) $imc, 1);
    if ($imcArrondi < 18.5) return 'insuffisance';
    if ($imcArrondi < 25) return 'normal';
    if ($imcArrondi < 30) return 'surpoids';
    return 'obesite';
}

/** Badge couleur selon l'objectif */
function badge_objectif($objectif, $OBJECTIFS) {
    $classes = [
        'perte_gras'  => 'badge-perte',
        'prise_masse' => 'badge-masse',
        'diabete'     => 'badge-diabete',
        'maintien'    => 'badge-maintien'
    ];
    $classe = $classes[$objectif] ?? 'badge-maintien';
    return '<span class="badge ' . $classe . '">' . e($OBJECTIFS[$objectif] ?? $objectif) . '</span>';
}

/** Libellé d'un objectif ; « Non défini » tant que le client n'a pas enregistré d'IMC. */
function libelle_objectif($objectif, array $OBJECTIFS): string {
    if ($objectif === null || $objectif === '') {
        return 'Non défini';
    }
    return $OBJECTIFS[$objectif] ?? (string) $objectif;
}
