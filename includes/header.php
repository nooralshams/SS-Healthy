<?php
// =====================================================
// ZIG HEALTHY - En-tête global
// =====================================================
// Cet en-tête est inclus dans toutes les pages du site.
// Il charge les dépendances communes, définit le titre de la page et
// affiche la navigation ainsi que le bandeau horaires.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fonctions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($titre_page) ? e($titre_page) . ' — ' : '' ?><?= SITE_NOM ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
    <script src="js/password-toggle.js" defer></script>
    <script src="js/rideau.js?v=<?= filemtime(__DIR__ . '/../js/rideau.js') ?>" defer></script>
</head>
<body>
<?php $nav_rideau = est_connecte(); // client ou administrateur connecté : menu en rideau sous le logo ?>
<header class="site-header<?= $nav_rideau ? ' site-header--connecte' : '' ?>">
    <div class="conteneur nav-conteneur">
        <div class="logo-zone">
            <a href="index.php" class="logo">
                <span class="logo-marque">SS’</span><span class="logo-accent">Healthy</span>
            </a>
            <?php if ($nav_rideau): ?>
                <button class="rideau-toggle" type="button" aria-controls="menu-principal" aria-expanded="false">
                    Menu <span class="rideau-fleche" aria-hidden="true">▾</span>
                </button>
            <?php endif; ?>
        </div>
        <nav class="nav-principale<?= $nav_rideau ? ' nav-rideau' : '' ?>" id="menu-principal" aria-label="Navigation principale">
            <a href="menu.php">Menu</a>
            <a href="abonnements.php">Abonnements</a>
            <a href="concept.php">Concept</a>
            <a href="contact.php">Contact</a>
            <?php if (est_connecte()): ?>
                <a href="compte.php">Mon compte</a>
                <?php if (est_admin()): ?><a href="admin/index.php">Admin</a><?php endif; ?>
                    <form method="post" action="deconnexion.php" class="nav-logout-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="lien-connexion">Déconnexion</button>
                    </form>
            <?php else: ?>
                <a href="connexion.php" class="lien-connexion">Connexion</a>
            <?php endif; ?>
        </nav>
        <div class="nav-actions">
            <a href="panier.php" class="lien-panier" aria-label="Voir le panier">
                <span class="icone-panier">🛒</span>
                <?php if (nb_articles_panier() > 0): ?>
                    <span class="panier-compte"><?= nb_articles_panier() ?></span>
                <?php endif; ?>
            </a>
            <a href="menu.php" class="btn btn-commander">Commander</a>
            <?php if (!$nav_rideau): ?>
                <button class="menu-toggle" type="button" aria-controls="menu-principal" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span aria-hidden="true">☰</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="bandeau-horaires">
        <?php if (est_ouvert()): ?>
            🟢 Ouvert maintenant — tous les jours de 9h à 21h
        <?php else: ?>
            🔴 Fermé actuellement — ouvert tous les jours de 9h à 21h
        <?php endif; ?>
    </div>
</header>
<main>
