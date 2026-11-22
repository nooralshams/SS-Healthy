<?php
// =====================================================
// ZIG HEALTHY - Déconnexion de l'espace admin
// =====================================================
// Détruit la session puis renvoie vers la page de connexion admin
// (et non vers le site public, pour rester dans la logique "admin").
require_once __DIR__ . '/../includes/config.php';

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

header('Location: login.php');
exit;
