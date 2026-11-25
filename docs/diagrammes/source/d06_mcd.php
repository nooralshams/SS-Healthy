<?php
// MCD (Merise). Sources : bd/zig_healthy_db.sql, bd/migration_imc.sql. Identifiants soulignés.
// OBJECTIF est un domaine de valeurs (ENUM) : il n'est pas traduit en table (voir MLD).
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1700, 1170, "Modèle conceptuel de données (MCD) — SS'Healthy");
$d->decalage = 30;
$c = fn($x, $y, $t) => texte($d, $x, $y, $t, ['taille' => 12, 'gras' => true, 'couleur' => '#8A5A00']);

$mes = entite($d, 40, 70, 230, 'MESURE_IMC', ['id', 'poids', 'taille', 'imc', 'interpretation', 'date_mesure']);
$usr = entite($d, 560, 70, 260, 'UTILISATEUR', ['id', 'nom', 'prenom', 'email', 'mot_de_passe', 'telephone', 'role', 'bloque', 'tentatives_connexion', 'date_inscription']);
$jet = entite($d, 1000, 70, 260, 'JETON_REINITIALISATION', ['id', 'empreinte', 'expire_le', 'cree_le']);
$msg = entite($d, 1390, 70, 270, 'MESSAGE_CONTACT', ['id', 'nom', 'email', 'telephone', 'message', 'statut', 'date_message']);
$obj = entite($d, 40, 480, 230, 'OBJECTIF', ['code', '(perte_gras, prise_masse,', 'diabete, maintien)'], 'domaine ENUM, sans table');
$cmd = entite($d, 560, 470, 260, 'COMMANDE', ['id', 'date_commande', 'creneau', 'mode', 'mode_paiement', 'adresse_livraison', 'statut', 'total']);
$abo = entite($d, 1000, 430, 250, 'ABONNEMENT', ['id', 'date_souscription', 'date_debut', 'date_fin', 'statut', 'adresse_livraison', 'prix']);
$for = entite($d, 1390, 430, 270, 'FORMULE_ABONNEMENT', ['id', 'nom', 'description', 'nb_repas_jour', 'prix', 'duree_jours', 'icone', 'actif']);
$bowl = entite($d, 560, 900, 260, 'BOWL_COMMANDE', ['id', 'nom', 'composition', 'quantite', 'prix_unitaire']);
$plat = entite($d, 1000, 820, 250, 'PLAT', ['id', 'nom', 'description', 'prix', 'calories', 'proteines', 'glucides', 'lipides', 'ig_faible', 'image', 'disponible', 'date_ajout']);
$cat = entite($d, 1390, 860, 270, 'CATEGORIE', ['id', 'nom', 'description']);

// EFFECTUER
association($d, 415, 150, 'EFFECTUER', [], 58);
ligne($d, [[270, 150], [357, 150]]); ligne($d, [[473, 150], [560, 150]]);
$c(278, 142, '1,1'); $c(526, 142, '0,n');
// DEMANDER
association($d, 910, 130, 'DEMANDER', [], 58);
ligne($d, [[820, 130], [852, 130]]); ligne($d, [[968, 130], [1000, 130]]);
$c(824, 122, '0,1'); $c(970, 122, '1,1');
// ENVOYER
association($d, 1200, 30, 'ENVOYER', [], 58);
ligne($d, [[760, 70], [760, 30], [1142, 30]]); ligne($d, [[1258, 30], [1525, 30], [1525, 70]]);
$c(766, 62, '0,n'); $c(1531, 62, '0,1');
// VISER
association($d, 415, 380, 'VISER', [], 50);
ligne($d, [[600, $usr[1] + $usr[3]], [600, 380], [465, 380]]); ligne($d, [[365, 380], [200, 380], [200, 480]]);
$c(606, $usr[1] + $usr[3] + 16, '0,1'); $c(206, 472, '0,n');
// RECOMMANDER
association($d, 100, 380, 'RECOMMANDER', [], 60);
ligne($d, [[100, $mes[1] + $mes[3]], [100, 360]]); ligne($d, [[100, 400], [100, 480]]);
$c(106, $mes[1] + $mes[3] + 16, '1,1'); $c(106, 472, '0,n');
// PASSER
association($d, 720, 380, 'PASSER', [], 50);
ligne($d, [[720, $usr[1] + $usr[3]], [720, 360]]); ligne($d, [[720, 400], [720, 470]]);
$c(726, $usr[1] + $usr[3] + 16, '0,n'); $c(726, 462, '1,1');
// SOUSCRIRE
association($d, 1100, 340, 'SOUSCRIRE', [], 58);
ligne($d, [[820, 240], [1100, 240], [1100, 320]]); ligne($d, [[1100, 360], [1100, 430]]);
$c(826, 232, '0,n'); $c(1106, 422, '1,1');
// DEFINIR
association($d, 1320, 520, 'DÉFINIR', [], 52);
ligne($d, [[1250, 520], [1268, 520]]); ligne($d, [[1372, 520], [1390, 520]]);
$c(1252, 512, '1,1'); $c(1356, 545, '0,n');
// CONTENIR (details_commande)
association($d, 910, 730, 'CONTENIR', ['quantite', 'prix_unitaire'], 62);
ligne($d, [[820, 640], [910, 640], [910, 694]]); ligne($d, [[910, 766], [910, 900], [1000, 900]]);
$c(826, 632, '0,n'); $c(962, 892, '0,n');
// INCLURE (details_commande_bowls)
association($d, 690, 790, 'INCLURE', [], 50);
ligne($d, [[690, $cmd[1] + $cmd[3]], [690, 770]]); ligne($d, [[690, 810], [690, 900]]);
$c(696, $cmd[1] + $cmd[3] + 16, '0,n'); $c(696, 892, '1,1');
// CLASSER
association($d, 1320, 920, 'CLASSER', [], 52);
ligne($d, [[1250, 920], [1268, 920]]); ligne($d, [[1372, 920], [1390, 920]]);
$c(1252, 912, '1,1'); $c(1356, 945, '0,n');
// CONVENIR (plat_objectifs)
association($d, 420, 1110, 'CONVENIR', [], 58);
ligne($d, [[155, $obj[1] + $obj[3]], [155, 1110], [362, 1110]]); ligne($d, [[478, 1110], [1125, 1110], [1125, $plat[1] + $plat[3]]]);
$c(161, $obj[1] + $obj[3] + 16, '0,n'); $c(1131, $plat[1] + $plat[3] + 16, '0,n');
texte($d, 40, 1150, 'Une commande peut ne contenir que des bowls : la cardinalité de COMMANDE dans CONTENIR est donc 0,n (au moins une ligne au total, plat ou bowl).', ['taille' => 11, 'italique' => true, 'couleur' => '#55635A']);
return $d;
