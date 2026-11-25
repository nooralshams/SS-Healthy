<?php
// MLD (schéma relationnel). Source : bd/zig_healthy_db.sql + bd/migration_imc.sql (12 tables).
// Clé primaire soulignée, clé étrangère précédée de #. Flèche : de la clé étrangère vers la table référencée.
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1720, 1320, "Modèle logique de données (MLD) — SS'Healthy, 12 tables");
$d->decalage = 28;
$pk = fn($t) => [$t, ['souligne' => true, 'gras' => true]];
$ENUM_OBJ = "ENUM('perte_gras','prise_masse','diabete','maintien')";

$mes = classe($d, 40, 60, 270, 'mesures_imc', [$pk('id INT'), '#utilisateur_id INT', 'poids DECIMAL(5,1)', 'taille DECIMAL(5,1)', 'imc DECIMAL(4,1)', 'interpretation VARCHAR(50)', 'objectif_recommande ENUM objectif', 'date_mesure DATETIME'], ['stereo' => 'table']);
$usr = classe($d, 350, 60, 300, 'utilisateurs', [$pk('id INT'), 'nom VARCHAR(50)', 'prenom VARCHAR(50)', 'email VARCHAR(100) UNIQUE', 'mot_de_passe VARCHAR(255)', 'telephone VARCHAR(20) NULL', 'objectif ENUM objectif NULL', "role ENUM('client','admin')", 'bloque TINYINT(1)', 'tentatives_connexion TINYINT', 'date_inscription DATETIME'], ['stereo' => 'table']);
$jet = classe($d, 720, 60, 290, 'jetons_reinitialisation', [$pk('id INT'), '#utilisateur_id INT UNIQUE', 'empreinte CHAR(64) UNIQUE', 'expire_le DATETIME', 'cree_le DATETIME'], ['stereo' => 'table']);
$msg = classe($d, 1080, 60, 290, 'messages_contact', [$pk('id INT'), '#utilisateur_id INT NULL', 'nom VARCHAR(100)', 'email VARCHAR(190)', 'telephone VARCHAR(30) NULL', 'message TEXT', "statut ENUM('nouveau','traite')", 'date_message DATETIME'], ['stereo' => 'table']);

$cmd = classe($d, 350, 500, 330, 'commandes', [$pk('id INT'), '#utilisateur_id INT', 'date_commande DATETIME', 'creneau VARCHAR(20)', "mode ENUM('sur_place','a_emporter','livraison')", "mode_paiement ENUM('especes','wave','orange_money')", 'adresse_livraison VARCHAR(255) NULL', 'statut ENUM(en_attente, confirmee,', '   en_preparation, prete, livree, annulee)', 'total DECIMAL(10,0)'], ['stereo' => 'table']);
$abo = classe($d, 740, 470, 290, 'abonnements', [$pk('id INT'), '#utilisateur_id INT', '#formule_id INT', 'date_souscription DATETIME', 'date_debut DATE', 'date_fin DATE', "statut ENUM(en_attente, actif,", '   termine, annule)', 'adresse_livraison VARCHAR(255) NULL', 'prix DECIMAL(10,0)'], ['stereo' => 'table']);
$for = classe($d, 1090, 470, 270, 'formules_abonnement', [$pk('id INT'), 'nom VARCHAR(100)', 'description TEXT', 'nb_repas_jour INT', 'prix DECIMAL(10,0)', 'duree_jours INT', 'icone VARCHAR(10)', 'actif TINYINT(1)'], ['stereo' => 'table']);

$bowl = classe($d, 350, 960, 300, 'details_commande_bowls', [$pk('id INT'), '#commande_id INT', 'nom VARCHAR(120)', 'composition TEXT (JSON)', 'quantite INT', 'prix_unitaire DECIMAL(10,0)'], ['stereo' => 'table']);
$det = classe($d, 720, 960, 280, 'details_commande', [$pk('id INT'), '#commande_id INT', '#plat_id INT', 'quantite INT', 'prix_unitaire DECIMAL(10,0)'], ['stereo' => 'table']);
$plat = classe($d, 1090, 820, 270, 'plats', [$pk('id INT'), '#categorie_id INT', 'nom VARCHAR(100)', 'description TEXT', 'prix DECIMAL(10,0)', 'calories INT', 'proteines DECIMAL(5,1)', 'glucides DECIMAL(5,1)', 'lipides DECIMAL(5,1)', 'ig_faible TINYINT(1)', 'image VARCHAR(255)', 'disponible TINYINT(1)', 'date_ajout DATETIME'], ['stereo' => 'table']);
$cat = classe($d, 1430, 860, 250, 'categories', [$pk('id INT'), 'nom VARCHAR(50)', 'description TEXT'], ['stereo' => 'table']);
$po = classe($d, 1430, 1160, 250, 'plat_objectifs', [$pk('#plat_id INT'), $pk('objectif ENUM objectif')], ['stereo' => 'table']);

$f = ['fin' => 'fleche'];
ligne($d, [[310, 150], [350, 150]], $f);                                     // mesures_imc → utilisateurs
ligne($d, [[720, 110], [650, 110]], $f);                                     // jetons → utilisateurs
ligne($d, [[1225, 60], [1225, 40], [600, 40], [600, 60]], $f);               // messages → utilisateurs
ligne($d, [[515, 500], [515, $usr[1] + $usr[3]]], $f);                       // commandes → utilisateurs
ligne($d, [[820, 470], [820, 420], [700, 420], [700, 250], [650, 250]], $f); // abonnements → utilisateurs
ligne($d, [[1030, 560], [1090, 560]], $f);                                   // abonnements → formules
ligne($d, [[500, 960], [500, $cmd[1] + $cmd[3]]], $f);                       // bowls → commandes
ligne($d, [[730, 960], [730, 690], [680, 690]], $f);                         // details → commandes
ligne($d, [[1000, 1040], [1090, 1040]], $f);                                 // details → plats
ligne($d, [[1360, 900], [1430, 900]], $f);                // plats → categories
ligne($d, [[1555, 1160], [1555, 1110], [1300, 1110], [1300, $plat[1] + $plat[3]]], $f); // plat_objectifs → plats
$y = 1190;
foreach (['Domaine « objectif » : ' . $ENUM_OBJ . ',', 'utilisé par utilisateurs.objectif, mesures_imc.objectif_recommande et plat_objectifs.objectif (pas de table objectif).', 'Suppression : CASCADE pour les clés vers utilisateurs, commandes, categories et plats (plat_objectifs) ; NO ACTION pour details_commande.plat_id et abonnements.formule_id ; SET NULL pour messages_contact.utilisateur_id.'] as $i => $l)
    texte($d, 40, $y + $i * 18, $l, ['taille' => 12, 'italique' => $i > 0, 'couleur' => $i ? '#55635A' : ENCRE]);
return $d;
