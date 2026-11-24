<?php
ob_start();
// =====================================================
// ZIG HEALTHY - En-tête de l'espace admin
// =====================================================
// Inclus en haut de chaque page du dossier admin/.
// Il charge la configuration, protège l'accès (rôle admin obligatoire)
// et affiche la navigation propre à l'administration.
// ATTENTION : ce fichier est dans admin/includes/, il faut donc remonter
// DEUX niveaux (../../) pour atteindre les includes du site.
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';
require_once __DIR__ . '/admin.php';

// Protection : seul un administrateur connecté peut voir ces pages.
// Sinon, on l'envoie vers la page de connexion dédiée à l'admin.
if (!est_admin()) {
    header('Location: login.php');
    exit;
}

// Page courante pour surligner le lien actif du menu admin
$page_courante = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre_page) ? e($titre_page) . ' — ' : '' ?>Admin — <?= SITE_NOM ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?= filemtime(__DIR__ . '/../../css/style.css') ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../../css/admin.css') ?>">
    <script src="../js/password-toggle.js" defer></script>
    <script src="../js/rideau.js?v=<?= filemtime(__DIR__ . '/../../js/rideau.js') ?>" defer></script>
</head>
<body>
<header class="site-header admin-header site-header--connecte">
    <div class="conteneur nav-conteneur">
        <div class="logo-zone">
            <a href="dashboard.php" class="logo">
                <span class="logo-marque">SS’</span><span class="logo-accent">Healthy</span>
                <span class="admin-badge">Admin</span>
            </a>
            <button class="rideau-toggle" type="button" aria-controls="menu-admin" aria-expanded="false">
                Menu <span class="rideau-fleche" aria-hidden="true">▾</span>
            </button>
        </div>
        <nav class="nav-principale nav-rideau" id="menu-admin" aria-label="Navigation de l’administration">
            <a href="dashboard.php" <?= $page_courante === 'dashboard.php' ? 'class="actif"' : '' ?>>Tableau de bord</a>
            <a href="plats.php" <?= $page_courante === 'plats.php' ? 'class="actif"' : '' ?>>Plats</a>
            <a href="categories.php" <?= $page_courante === 'categories.php' ? 'class="actif"' : '' ?>>Catégories</a>
            <a href="commandes.php" <?= $page_courante === 'commandes.php' ? 'class="actif"' : '' ?>>Commandes</a>
            <a href="abonnements.php" <?= $page_courante === 'abonnements.php' ? 'class="actif"' : '' ?>>Abonnements</a>
            <a href="messages.php" <?= $page_courante === 'messages.php' ? 'class="actif"' : '' ?>>Messages</a>
            <a href="clients.php" <?= $page_courante === 'clients.php' ? 'class="actif"' : '' ?>>Clients</a>
            <a href="profil.php" <?= $page_courante === 'profil.php' ? 'class="actif"' : '' ?>>Profil</a>
            <a href="../index.php">Voir le site</a>
            <form method="post" action="logout.php" class="nav-logout-form admin-logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="lien-connexion">Déconnexion</button>
            </form>
        </nav>
    </div>
</header>
<main>
