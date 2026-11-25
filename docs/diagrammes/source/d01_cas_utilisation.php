<?php
// Figure 9 — Cas d'utilisation.
// Sources : pages publiques (menu.php, plat.php, imc.php, panier.php, bowl_add.php, inscription.php, connexion.php,
// mot_de_passe_oublie.php, contact.php, abonnements.php), pages client (commande.php, compte.php, deconnexion.php),
// admin/ (dashboard, plats, categories, commandes, abonnements, clients, messages, profil, logout).
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1500, 1010, "Diagramme de cas d'utilisation — SS'Healthy");
// Cadre du système
$d->add('<rect x="200" y="55" width="1150" height="935" rx="8" fill="none" stroke="' . COUL_TRAIT . '" stroke-width="1.8"/>');
texte($d, 775, 80, "Système SS'Healthy", ['ancre' => 'middle', 'gras' => true, 'taille' => 15]);

acteur($d, 90, 210, 'Visiteur');
acteur($d, 90, 690, ['Client', 'connecté']);
acteur($d, 1430, 480, 'Administrateur');
// Généralisation Client connecté → Visiteur
ligne($d, [[90, 688], [90, 330]], ['fin' => 'heritage']);

$C1 = 450; $C2 = 790; $C3 = 1110;
$vis = [];
foreach ([['Consulter le menu', 'et la fiche d’un plat'], 'Calculer son IMC', 'Gérer le panier', 'Composer un bowl', 'Créer un compte', 'Réinitialiser son mot de passe', 'Envoyer un message de contact', 'Consulter les formules d’abonnement'] as $i => $t) {
    $vis[$i] = cas($d, $C1, 115 + $i * 47, $t, 160, 21);
}
$connecter = cas($d, $C2, 520, 'Se connecter', 120, 22);
$deconnecter = cas($d, $C2, 600, 'Se déconnecter', 120, 22);
$cli = [];
foreach (['Passer une commande', 'Suivre ses commandes', 'Gérer son profil', ['Enregistrer son IMC', 'et choisir son objectif'], 'Souscrire un abonnement'] as $i => $t) {
    $cli[$i] = cas($d, $C1, 690 + $i * 62, $t, 160, is_array($t) ? 26 : 22);
}
$creneau = cas($d, $C2, 690, ['Choisir le créneau, le mode', 'de retrait et le paiement'], 140, 27);
$adm = [];
$listeAdm = ['Consulter le tableau de bord', 'Gérer les plats', 'Gérer les catégories', ['Gérer les commandes', '(statuts, paiements mobiles)'], 'Gérer les abonnements', ['Gérer les clients', '(bloquer / débloquer)'], 'Consulter les messages', 'Modifier son profil'];
$ys = [120, 175, 230, 292, 680, 742, 805, 860];
foreach ($listeAdm as $i => $t) $adm[$i] = cas($d, $C3, $ys[$i], $t, 150, is_array($t) ? 27 : 22);

// Visiteur → cas publics
foreach ($vis as $b) ligne($d, [[112, 262], gauche($b)]);
ligne($d, [[112, 262], [250, 520], gauche($connecter)]);
// Client connecté → cas client
foreach ($cli as $b) ligne($d, [[112, 740], gauche($b)]);
ligne($d, [[112, 740], [250, 600], gauche($deconnecter)]);
// Administrateur
foreach ($adm as $b) ligne($d, [[1408, 530], droite($b)]);
ligne($d, [[1408, 530], droite($connecter)]);
ligne($d, [[1408, 530], droite($deconnecter)]);
// include / extend
ligne($d, [droite($cli[0]), gauche($creneau)], ['pointille' => true, 'fin' => 'ouverte']);
texte($d, 640, 682, '«include»', ['ancre' => 'middle', 'taille' => 11, 'italique' => true]);
ligne($d, [haut($vis[3], .78), bas($vis[2], .78)], ['pointille' => true, 'fin' => 'ouverte']);
texte($d, $C1 + 135, 233, '«extend»', ['taille' => 11, 'italique' => true]);
texte($d, 210, 975, 'La connexion n’est exigée qu’au moment de commander, de suivre ses commandes, de gérer son profil ou de souscrire (commande.php l. 13-18).', ['taille' => 11, 'italique' => true, 'couleur' => '#55635A']);
return $d;
