<?php
// =====================================================
// ZIG HEALTHY - Configuration globale
// =====================================================
// Ce fichier initialise la session PHP, la connexion à la base de données
// et les constantes du site (nom, horaires, paramètres de connexion).
// Il est inclus en premier dans presque toutes les pages du site.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $cookie_params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookie_params['lifetime'],
        'path' => $cookie_params['path'],
        'domain' => $cookie_params['domain'],
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!is_string($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        http_response_code(419);
        exit('Votre session de formulaire a expiré. Rechargez la page et réessayez.');
    }
}

define('DB_HOST', getenv('ZIG_HEALTHY_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('ZIG_HEALTHY_DB_PORT') ?: '3308');
define('DB_NAME', getenv('ZIG_HEALTHY_DB_NAME') ?: 'zig_healthy_db');
define('DB_USER', getenv('ZIG_HEALTHY_DB_USER') ?: 'root');
define('DB_PASS', getenv('ZIG_HEALTHY_DB_PASS') !== false ? getenv('ZIG_HEALTHY_DB_PASS') : '');

define('SITE_NOM', 'SS’Healthy');
define('HEURE_OUVERTURE', 9);       // 09h
define('HEURE_FERMETURE', 21);      // 21h

// Coordonnées affichées pour les paiements mobiles (Wave et Orange Money)
define('PAIEMENT_BENEFICIAIRE', 'Salma Marieme Diallo');
define('PAIEMENT_NUMERO', '+221 77 018 82 98');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log('Connexion MySQL SS Healthy : ' . $e->getMessage());
    http_response_code(500);
    die('Le service est temporairement indisponible. Réessayez plus tard.');
}

// Libellés des objectifs nutritionnels
$OBJECTIFS = [
    'perte_gras'  => 'Perte de gras',
    'prise_masse' => 'Prise de masse',
    'diabete'     => 'Faible index glycémique',
    'maintien'    => 'Garder la ligne'
];
