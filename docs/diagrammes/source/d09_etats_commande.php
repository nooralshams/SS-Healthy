<?php
// États d'une commande. Sources : bd/zig_healthy_db.sql (ENUM statut, défaut en_attente),
// includes/commande_functions.php (création), admin/includes/admin.php transitions_statuts_commande(), admin/commandes.php.
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1320, 640, "Diagramme d'états-transitions — Commande (SS'Healthy)");
$f = ['fin' => 'fleche'];
$l = fn($x, $y, $t, $o = []) => texte($d, $x, $y, $t, ['taille' => 12, 'fond' => true] + $o);
$i = debut($d, 50, 150);
$att = etat($d, 290, 150, 'en_attente', 'statut par défaut', 200);
$con = etat($d, 600, 150, 'confirmee', null, 180);
$pre = etat($d, 880, 150, 'en_preparation', null, 190);
$prt = etat($d, 1140, 150, 'prete', null, 160);
$liv = etat($d, 1140, 350, 'livree', null, 160);
$ann = etat($d, 700, 470, 'annulee', null, 180);
$f1 = fin($d, 1140, 470); $f2 = fin($d, 700, 580);
ligne($d, [droite($i), gauche($att)], $f); $l(40, 118, 'commande enregistrée', ['taille' => 11]);
ligne($d, [droite($att), gauche($con)], $f); $l(400, 140, 'confirmer', ['gras' => true]); texte($d, 400, 172, '(transfert vérifié', ['taille' => 11, 'couleur' => '#55635A']); texte($d, 400, 186, 'si Wave / OM)', ['taille' => 11, 'couleur' => '#55635A']);
ligne($d, [droite($con), gauche($pre)], $f); $l(700, 140, 'préparer', ['gras' => true]);
ligne($d, [droite($pre), gauche($prt)], $f); $l(983, 140, 'marquer prête', ['gras' => true, 'taille' => 11]);
ligne($d, [bas($prt), haut($liv)], $f); $l(1150, 255, 'marquer livrée', ['gras' => true]);
ligne($d, [bas($liv), haut($f1)], $f);
ligne($d, [bas($att), [290, 470], gauche($ann)], $f);
ligne($d, [bas($con), [600, 400], [660, 400], [660, $ann[1]]], $f);
ligne($d, [bas($pre), [880, 400], [740, 400], [740, $ann[1]]], $f);
ligne($d, [[1100, 170], [1100, 290], [960, 290], [960, 470], droite($ann)], $f);
$l(298, 330, 'annuler', ['gras' => true]); $l(608, 300, 'annuler', ['gras' => true]); $l(888, 300, 'annuler', ['gras' => true]); $l(968, 400, 'annuler', ['gras' => true]);
ligne($d, [bas($ann), haut($f2)], $f);
texte($d, 40, 625, 'Toutes les transitions sont déclenchées par l’administrateur (admin/commandes.php). Le serveur refuse toute transition absente de transitions_statuts_commande() ; livree et annulee sont finaux.', ['taille' => 11, 'italique' => true, 'couleur' => '#55635A']);
return $d;
