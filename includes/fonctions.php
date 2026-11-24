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

/**
 * Pictogramme SVG au trait (24 × 24), dessiné pour SS'Healthy.
 * Il prend la couleur du texte (currentColor) et reste décoratif (aria-hidden).
 */
function icone(string $nom, string $classe = 'icone'): string {
    $traces = [
        'sac'          => '<path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7V6a3 3 0 0 1 6 0v1"/>',
        'eclair'       => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8z"/>',
        'jauge'        => '<path d="M4 18a8 8 0 1 1 16 0"/><path d="M12 18l4-5"/><circle cx="12" cy="18" r="1.2"/>',
        'carte'        => '<rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3 10h18"/><path d="M7 15h4"/>',
        'bol'          => '<path d="M3 12h18a9 9 0 0 1-18 0z"/><path d="M8 8.5c0-1.4 1-1.9 1-3.3M12 8.5c0-1.4 1-1.9 1-3.3M16 8.5c0-1.4 1-1.9 1-3.3"/>',
        'courrier'     => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/>',
        'recu'         => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>',
        'utilisateurs' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M15.5 5.2a3 3 0 0 1 0 5.6"/><path d="M17 14.2a5 5 0 0 1 3.5 4.8"/>',
    ];
    if (!isset($traces[$nom])) {
        return '';
    }
    return '<svg class="' . e($classe) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $traces[$nom] . '</svg>';
}
