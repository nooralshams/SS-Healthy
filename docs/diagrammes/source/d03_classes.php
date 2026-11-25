<?php
// Figure 13 (mémoire : figure 15) — Diagramme de classes métier.
// Sources : bd/zig_healthy_db.sql (11 tables), bd/migration_imc.sql (mesures_imc) — 12 tables au total.
// Noms d'attributs = noms des colonnes SQL. Les clés étrangères sont représentées par les associations.
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1720, 1330, "Diagramme de classes métier — SS'Healthy (12 tables)");
$d->decalage = 28;
$it = ['italique' => true];

$mesure = classe($d, 40, 60, 260, 'MesureIMC', ['id : int', 'poids : decimal (kg)', 'taille : decimal (cm)', 'imc : decimal (1 décimale)', 'interpretation : string', 'date_mesure : datetime'], ['stereo' => 'table mesures_imc']);
$user = classe($d, 340, 60, 310, 'Utilisateur', ['id : int', 'nom : string', 'prenom : string', 'email : string {unique}', 'mot_de_passe : string (hash)', 'telephone : string [0..1]', 'role : {client, admin}', 'bloque : bool', 'tentatives_connexion : int', 'date_inscription : datetime'], ['stereo' => 'table utilisateurs']);
$jeton = classe($d, 720, 60, 270, 'JetonReinitialisation', ['id : int', 'empreinte : char(64) {unique}', 'expire_le : datetime', 'cree_le : datetime'], ['stereo' => 'table jetons_reinitialisation']);
$msg = classe($d, 1080, 60, 270, 'MessageContact', ['id : int', 'nom : string', 'email : string', 'telephone : string [0..1]', 'message : text', 'statut : {nouveau, traite}', 'date_message : datetime'], ['stereo' => 'table messages_contact']);

$obj = classe($d, 40, 520, 260, 'Objectif', ['perte_gras', 'prise_masse', 'diabete', 'maintien'], ['stereo' => 'enumeration']);
$cmd = classe($d, 340, 460, 330, 'Commande', ['id : int', 'date_commande : datetime', 'creneau : {Sous 1 h, Sous 2 h, Pour midi, Ce soir}', 'mode : {sur_place, a_emporter, livraison}', 'mode_paiement : {especes, wave, orange_money}', 'adresse_livraison : string [0..1]', 'statut : {en_attente, confirmee, en_preparation,', '     prete, livree, annulee}', 'total : decimal (FCFA)'], ['stereo' => 'table commandes']);
$abo = classe($d, 720, 420, 300, 'Abonnement', ['id : int', 'date_souscription : datetime', 'date_debut : date', 'date_fin : date', 'statut : {en_attente, actif, termine, annule}', 'adresse_livraison : string [0..1]', 'prix : decimal (FCFA)'], ['stereo' => 'table abonnements']);
$formule = classe($d, 1080, 420, 270, 'FormuleAbonnement', ['id : int', 'nom : string', 'description : text', 'nb_repas_jour : int', 'prix : decimal (FCFA)', 'duree_jours : int', 'icone : string', 'actif : bool'], ['stereo' => 'table formules_abonnement']);

$ligne = classe($d, 380, 850, 250, 'LigneCommande', ['quantite : int', 'prix_unitaire : decimal (FCFA)'], ['abstraite' => true, 'stereo' => 'abstraite']);
$det = classe($d, 720, 1010, 270, 'DetailsCommande', ['id : int'], ['stereo' => 'table details_commande']);
$bowl = classe($d, 340, 1010, 300, 'DetailBowl', ['id : int', 'nom : string', 'composition : text (JSON)'], ['stereo' => 'table details_commande_bowls']);
$plat = classe($d, 1080, 760, 270, 'Plat', ['id : int', 'nom : string', 'description : text', 'prix : decimal (FCFA)', 'calories : int', 'proteines : decimal', 'glucides : decimal', 'lipides : decimal', 'ig_faible : bool', 'image : string', 'disponible : bool', 'date_ajout : datetime'], ['stereo' => 'table plats']);
$cat = classe($d, 1420, 800, 260, 'Categorie', ['id : int', 'nom : string', 'description : text'], ['stereo' => 'table categories']);

$m = fn($x, $y, $t, $o = []) => texte($d, $x, $y, $t, ['taille' => 12, 'gras' => true] + $o);
$n = fn($x, $y, $t, $o = []) => texte($d, $x, $y, $t, ['taille' => 12, 'italique' => true, 'couleur' => '#55635A'] + $o);

// Utilisateur — MesureIMC
ligne($d, [[340, 150], [300, 150]]); $m(304, 143, '0..*'); $m(330, 168, '1');
// Utilisateur — Objectif (0..1)
ligne($d, [[380, $user[1] + $user[3]], [380, 395], [210, 395], [210, $obj[1]]]); $m(386, $user[1] + $user[3] + 16, '0..*'); $m(216, $obj[1] - 6, '0..1'); $n(240, 388, 'vise (utilisateurs.objectif)');
// MesureIMC — Objectif (objectif_recommande)
ligne($d, [[110, $mesure[1] + $mesure[3]], [110, $obj[1]]]); $m(116, $mesure[1] + $mesure[3] + 16, '0..*'); $m(116, $obj[1] - 6, '1'); $n(116, 330, 'recommande', []); $n(116, 345, '(objectif_recommande)', []);
// Utilisateur — Commande
ligne($d, [[520, $user[1] + $user[3]], [520, $cmd[1]]]); $m(526, $user[1] + $user[3] + 16, '1'); $m(526, $cmd[1] - 6, '0..*'); $n(526, 360, 'passe');
// Utilisateur — Jeton
ligne($d, [[650, 110], [720, 110]]); $m(656, 104, '1'); $m(694, 104, '0..1'); $n(685, 128, 'demande', ['ancre' => 'middle']);
// Utilisateur — MessageContact
ligne($d, [[600, 60], [600, 42], [1215, 42], [1215, 60]]); $m(606, 56, '0..1'); $m(1221, 56, '0..*'); $n(900, 37, 'envoie (utilisateur_id facultatif)', ['ancre' => 'middle']);
// Utilisateur — Abonnement
ligne($d, [[650, 230], [690, 230], [690, 380], [860, 380], [860, $abo[1]]]); $m(656, 224, '1'); $m(866, $abo[1] - 6, '0..*'); $n(700, 372, 'souscrit');
// FormuleAbonnement — Abonnement
ligne($d, [[$abo[0] + $abo[2], 500], [$formule[0], 500]]); $m($abo[0] + $abo[2] + 6, 494, '0..*'); $m($formule[0] - 12, 494, '1'); $n(1050, 520, 'définit', ['ancre' => 'middle']);
// Commande ◆— LigneCommande (1..*)
ligne($d, [[505, $cmd[1] + $cmd[3]], [505, $ligne[1]]], ['debut' => 'losange']); $m(511, $cmd[1] + $cmd[3] + 26, '1'); $m(511, $ligne[1] - 6, '1..*'); $n(560, ($cmd[1] + $cmd[3] + $ligne[1]) / 2 + 4, 'contient');
// Héritage
$yb = 975;
ligne($d, [[505, $yb], [505, $ligne[1] + $ligne[3]]], ['fin' => 'heritage']);
ligne($d, [[490, $yb], [855, $yb]]);
ligne($d, [[490, $yb], [490, $bowl[1]]]);
ligne($d, [[855, $yb], [855, $det[1]]]);
// DetailsCommande — Plat
ligne($d, [[$det[0] + $det[2], 1045], [1180, 1045], [1180, $plat[1] + $plat[3]]]); $m($det[0] + $det[2] + 6, 1039, '0..*'); $m(1186, $plat[1] + $plat[3] + 16, '1'); $n(1010, 1065, 'concerne');
// Categorie — Plat
ligne($d, [[$cat[0], 860], [$plat[0] + $plat[2], 860]]); $m($cat[0] - 12, 854, '1'); $m($plat[0] + $plat[2] + 6, 854, '0..*'); $n(1385, 880, 'classe', ['ancre' => 'middle']);
// Plat — Objectif (table plat_objectifs)
ligne($d, [[170, $obj[1] + $obj[3]], [170, 1230], [1300, 1230], [1300, $plat[1] + $plat[3]]]); $m(176, $obj[1] + $obj[3] + 16, '0..*'); $m(1306, $plat[1] + $plat[3] + 16, '0..*'); $n(700, 1223, 'convient à', ['ancre' => 'middle']);
ligne($d, [[860, 1230], [860, 1262]], ['pointille' => true]);
$po = classe($d, 760, 1262, 200, 'PlatObjectif', ['plat_id : int', 'objectif : Objectif'], ['stereo' => 'table plat_objectifs']);
$d->h = 1262 + $po[3] + 16;
texte($d, 1010, 1292, 'Classe d’association : clé primaire (plat_id, objectif).', ['taille' => 12, 'italique' => true, 'couleur' => '#55635A']);
texte($d, 1010, 1310, 'Objectif : ENUM SQL, sans table propre.', ['taille' => 12, 'italique' => true, 'couleur' => '#55635A']);
return $d;
