<?php
// =====================================================
// ZIG HEALTHY - Déconnexion
// =====================================================
// Cette page termine la session utilisateur et redirige vers l'accueil.
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametres = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $parametres['path'], $parametres['domain'], $parametres['secure'], $parametres['httponly']);
}

session_destroy();
header('Location: index.php');
exit;
