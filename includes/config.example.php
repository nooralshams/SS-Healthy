<?php
// =====================================================
// ZIG HEALTHY - Exemple de configuration (aucun secret)
// =====================================================
// Ce fichier n'est chargé par aucune page. Il documente les réglages.
//
// includes/config.php ne contient aucun mot de passe : il lit les paramètres
// de connexion dans des variables d'environnement et prend, à défaut, les
// valeurs d'une installation WAMP locale (root sans mot de passe, port 3308).
//
// Pour une autre installation, définir ces variables d'environnement
// (par exemple dans la configuration Apache : SetEnv NOM valeur)
// plutôt que de modifier config.php :
//
//   ZIG_HEALTHY_DB_HOST   hôte MySQL            (défaut : localhost)
//   ZIG_HEALTHY_DB_PORT   port MySQL            (défaut : 3308)
//   ZIG_HEALTHY_DB_NAME   nom de la base        (défaut : zig_healthy_db)
//   ZIG_HEALTHY_DB_USER   utilisateur MySQL     (défaut : root)
//   ZIG_HEALTHY_DB_PASS   mot de passe MySQL    (défaut : vide)
//
// Ne jamais écrire de mot de passe réel dans un fichier du dépôt.

define('DB_HOST', getenv('ZIG_HEALTHY_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('ZIG_HEALTHY_DB_PORT') ?: '3308');
define('DB_NAME', getenv('ZIG_HEALTHY_DB_NAME') ?: 'zig_healthy_db');
define('DB_USER', getenv('ZIG_HEALTHY_DB_USER') ?: 'root');
define('DB_PASS', getenv('ZIG_HEALTHY_DB_PASS') !== false ? getenv('ZIG_HEALTHY_DB_PASS') : '');

// Paiements mobiles : nom et numéro affichés aux clients (informations publiques).
define('PAIEMENT_BENEFICIAIRE', 'Salma Marieme Diallo');
define('PAIEMENT_NUMERO', '[numéro Wave / Orange Money du restaurant]');
