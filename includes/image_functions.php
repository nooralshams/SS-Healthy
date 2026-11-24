<?php
// =====================================================
// ZIG HEALTHY - Contrôle des images de plats envoyées
// =====================================================
// Règles appliquées à chaque fichier envoyé par l'administrateur :
//   1. l'envoi PHP s'est terminé sans erreur ;
//   2. la taille est comprise entre 1 octet et 2 Mo ;
//   3. l'extension annoncée est jpg, jpeg, png ou webp ;
//   4. le contenu réel est une image (getimagesize + finfo) de type
//      JPEG, PNG ou WebP, cohérent avec l'extension annoncée ;
//   5. le fichier est renommé avec un nom aléatoire : le nom choisi
//      par l'utilisateur n'est jamais réutilisé sur le serveur.

const TAILLE_MAX_IMAGE_PLAT = 2 * 1024 * 1024;
const TYPES_IMAGE_PLAT = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
const EXTENSIONS_IMAGE_PLAT = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];

/**
 * Vérifie une entrée de $_FILES sans la déplacer.
 * Retourne ['ok' => bool, 'absent' => bool, 'erreur' => ?string, 'extension' => ?string].
 */
function verifier_image_plat(array $fichier): array {
    $resultat = ['ok' => false, 'absent' => false, 'erreur' => null, 'extension' => null];
    $code = $fichier['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($code === UPLOAD_ERR_NO_FILE) {
        $resultat['absent'] = true;
        return $resultat;
    }
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        $resultat['erreur'] = 'L’image dépasse la taille maximale de 2 Mo.';
        return $resultat;
    }
    if ($code !== UPLOAD_ERR_OK || !is_string($fichier['tmp_name'] ?? null) || !is_file($fichier['tmp_name'])) {
        $resultat['erreur'] = 'L’envoi de l’image a échoué.';
        return $resultat;
    }

    $taille = (int) ($fichier['size'] ?? 0);
    if ($taille <= 0 || $taille > TAILLE_MAX_IMAGE_PLAT || filesize($fichier['tmp_name']) > TAILLE_MAX_IMAGE_PLAT) {
        $resultat['erreur'] = 'L’image doit faire au maximum 2 Mo.';
        return $resultat;
    }

    $extension = strtolower(pathinfo((string) ($fichier['name'] ?? ''), PATHINFO_EXTENSION));
    if (!isset(EXTENSIONS_IMAGE_PLAT[$extension])) {
        $resultat['erreur'] = 'Formats acceptés : JPG, PNG ou WebP.';
        return $resultat;
    }

    $infos = @getimagesize($fichier['tmp_name']);
    $mime = is_array($infos) ? ($infos['mime'] ?? '') : '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReel = $finfo ? finfo_file($finfo, $fichier['tmp_name']) : false;
        if ($finfo) finfo_close($finfo);
        if ($mimeReel !== $mime) {
            $mime = '';
        }
    }
    if (!isset(TYPES_IMAGE_PLAT[$mime])) {
        $resultat['erreur'] = 'Le fichier envoyé n’est pas une image JPG, PNG ou WebP valide.';
        return $resultat;
    }
    if (EXTENSIONS_IMAGE_PLAT[$extension] !== TYPES_IMAGE_PLAT[$mime]) {
        $resultat['erreur'] = 'L’extension du fichier ne correspond pas à son contenu.';
        return $resultat;
    }

    $resultat['ok'] = true;
    $resultat['extension'] = TYPES_IMAGE_PLAT[$mime];
    return $resultat;
}

/** Nom de fichier aléatoire, sans rapport avec le nom envoyé. */
function nom_image_plat(string $extension): string {
    return 'plat_' . bin2hex(random_bytes(16)) . '.' . $extension;
}

/**
 * Vérifie puis déplace l'image envoyée dans $dossier.
 * Retourne ['ok' => bool, 'absent' => bool, 'erreur' => ?string, 'nom' => ?string].
 */
function enregistrer_image_plat(array $fichier, string $dossier): array {
    $verification = verifier_image_plat($fichier);
    $resultat = ['ok' => false, 'absent' => $verification['absent'], 'erreur' => $verification['erreur'], 'nom' => null];
    if (!$verification['ok']) {
        return $resultat;
    }
    if (!is_uploaded_file($fichier['tmp_name'])) {
        $resultat['erreur'] = 'L’envoi de l’image a échoué.';
        return $resultat;
    }

    $nom = nom_image_plat($verification['extension']);
    if (!move_uploaded_file($fichier['tmp_name'], rtrim($dossier, '/\\') . DIRECTORY_SEPARATOR . $nom)) {
        $resultat['erreur'] = 'Impossible d’enregistrer l’image.';
        return $resultat;
    }

    $resultat['ok'] = true;
    $resultat['nom'] = $nom;
    return $resultat;
}

/** Supprime une image précédemment envoyée par l'administration (jamais les images d'origine). */
function supprimer_image_plat(?string $nom, string $dossier): void {
    if (is_string($nom) && preg_match('/^plat_[0-9a-f]{32}\.(jpg|png|webp)$/', $nom)) {
        $chemin = rtrim($dossier, '/\\') . DIRECTORY_SEPARATOR . $nom;
        if (is_file($chemin)) {
            unlink($chemin);
        }
    }
}
