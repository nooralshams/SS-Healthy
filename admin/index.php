<?php
// =====================================================
// ZIG HEALTHY - Admin : point d'entrée
// =====================================================
// Redirige avant tout affichage HTML pour conserver les en-têtes HTTP.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';

header('Location: ' . (est_admin() ? 'dashboard.php' : 'login.php'));
exit;
