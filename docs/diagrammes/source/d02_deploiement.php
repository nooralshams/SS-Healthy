<?php
// Déploiement (démonstration locale).
// Sources : wampmanager.conf (Apache 2.4.65), php -v (PHP 8.0.30), C:\wamp64\bin\mysql\mysql8.4.7,
// includes/config.php (PDO, port 3308, base zig_healthy_db), includes/reinitialisation.php (SMTP par variables d'environnement),
// includes/image_functions.php (images/plats/), README (http://localhost:8080/zig_healthy/).
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1400, 690, "Diagramme de déploiement — SS'Healthy (démonstration locale)");
$client = noeud($d, 40, 150, 300, 230, 'device', 'Poste du client');
artefact($d, 65, 205, 250, ['Navigateur web', 'ordinateur ou téléphone'], 'execution environment');
artefact($d, 65, 280, 250, ['Pages HTML / CSS / JS', 'css/style.css, js/*.js'], 'artifact');

$serveur = noeud($d, 470, 110, 560, 520, 'device', 'Poste de développement — Windows 11, WAMP');
artefact($d, 500, 165, 500, ['Apache 2.4.65 + PHP 8.0.30', 'écoute sur le port 8080'], 'execution environment');
artefact($d, 520, 235, 225, ['Pages PHP', 'index.php, menu.php, panier.php,', 'commande.php, compte.php…'], 'artifact');
artefact($d, 760, 235, 225, ['Espace admin', 'admin/*.php'], 'artifact');
artefact($d, 520, 335, 225, ['Fonctions', 'includes/*.php (PDO, CSRF,', 'IMC, commande, images)'], 'artifact');
artefact($d, 760, 335, 225, ['Images des plats', 'images/plats/ (envoi contrôlé)'], 'artifact');
artefact($d, 500, 470, 500, ['MySQL 8.4.7', 'port 3308'], 'execution environment');
artefact($d, 520, 540, 465, ['Base zig_healthy_db', '12 tables (bd/zig_healthy_db.sql + 4 migrations)'], 'artifact');

$smtp = noeud($d, 1120, 150, 240, 140, 'device', 'Serveur SMTP (externe)');
texte($d, 1240, 225, 'e-mail de réinitialisation', ['ancre' => 'middle', 'taille' => 12]);
texte($d, 1240, 243, 'du mot de passe (PHPMailer)', ['ancre' => 'middle', 'taille' => 12]);
texte($d, 1240, 261, 'paramètres : ZIG_HEALTHY_SMTP_*', ['ancre' => 'middle', 'taille' => 11, 'couleur' => '#55635A']);

$mobile = noeud($d, 1120, 420, 240, 150, 'device', 'Wave / Orange Money');
texte($d, 1240, 495, 'transfert fait par le client', ['ancre' => 'middle', 'taille' => 12]);
texte($d, 1240, 513, 'dans l’application mobile ;', ['ancre' => 'middle', 'taille' => 12]);
texte($d, 1240, 531, 'aucune connexion avec le site', ['ancre' => 'middle', 'taille' => 12]);
texte($d, 1240, 549, '(vérification manuelle par l’admin)', ['ancre' => 'middle', 'taille' => 11, 'couleur' => '#55635A']);

ligne($d, [[340, 215], [470, 215]], ['epaisseur' => 2]);
texte($d, 405, 205, 'HTTP', ['ancre' => 'middle', 'taille' => 12, 'gras' => true]);
texte($d, 405, 238, 'localhost:8080', ['ancre' => 'middle', 'taille' => 11]);
ligne($d, [[750, 420], [750, 470]], ['epaisseur' => 2]);
texte($d, 760, 450, 'PDO (pdo_mysql), TCP 3308', ['taille' => 12, 'gras' => true]);
ligne($d, [[1030, 220], [1120, 220]], ['epaisseur' => 2, 'pointille' => true]);
texte($d, 1075, 210, 'SMTP', ['ancre' => 'middle', 'taille' => 12, 'gras' => true]);
texte($d, 40, 670, 'Démonstration en local : aucun hébergement en ligne. Les paiements mobiles ne passent par aucune API ; la commande reste « en attente » jusqu’à la vérification par l’administrateur.', ['taille' => 11, 'italique' => true, 'couleur' => '#55635A']);
return $d;
