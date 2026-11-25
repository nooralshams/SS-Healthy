<?php
// États d'un abonnement. Sources : bd/zig_healthy_db.sql, table abonnements (ENUM statut, défaut en_attente),
// abonnements.php (création en_attente), admin/includes/admin.php transitions_statuts_abonnement(), admin/abonnements.php.
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1040, 520, "Diagramme d'états-transitions — Abonnement (SS'Healthy)");
$f = ['fin' => 'fleche'];
$l = fn($x, $y, $t, $o = []) => texte($d, $x, $y, $t, ['taille' => 12, 'fond' => true] + $o);
$i = debut($d, 60, 150);
$att = etat($d, 260, 150, 'en_attente', 'statut par défaut', 200);
$act = etat($d, 560, 150, 'actif', null, 160);
$ter = etat($d, 860, 150, 'termine', null, 160);
$ann = etat($d, 560, 360, 'annule', null, 160);
$f1 = fin($d, 860, 290); $f2 = fin($d, 560, 460);
ligne($d, [droite($i), gauche($att)], $f); $l(66, 128, 'souscription', ['taille' => 11]); texte($d, 66, 180, '(abonnements.php)', ['taille' => 11, 'couleur' => '#55635A']);
ligne($d, [droite($att), gauche($act)], $f); $l(380, 140, 'valider', ['gras' => true]);
ligne($d, [droite($act), gauche($ter)], $f); $l(665, 140, 'terminer', ['gras' => true]);
ligne($d, [bas($ter), haut($f1)], $f);
ligne($d, [bas($att), [260, 360], gauche($ann)], $f); $l(268, 300, 'annuler', ['gras' => true]);
ligne($d, [bas($act), haut($ann)], $f); $l(570, 260, 'annuler', ['gras' => true]);
ligne($d, [bas($ann), haut($f2)], $f);
texte($d, 40, 505, 'Transitions déclenchées par l’administrateur (admin/abonnements.php), contrôlées par transitions_statuts_abonnement() ; termine et annule sont finaux.', ['taille' => 11, 'italique' => true, 'couleur' => '#55635A']);
return $d;
