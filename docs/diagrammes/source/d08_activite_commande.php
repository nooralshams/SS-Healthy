<?php
// Figure 11 — Activité : commander un repas.
// Sources : menu.php, bowl_add.php (prix du bowl côté serveur), panier.php (sans compte),
// commande.php l. 13-18 (connexion exigée seulement au moment de commander), l. 55-104 (contrôles, enregistrement),
// connexion.php + includes/connexion_functions.php (blocage au 5e échec), includes/commande_functions.php (transaction).
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1200, 1120, "Diagramme d'activité — Commander un repas (SS'Healthy)");
// Couloirs
$d->add('<rect x="30" y="50" width="1140" height="1050" fill="none" stroke="#4A5A50" stroke-width="1.4"/>');
$d->add('<line x1="570" y1="50" x2="570" y2="1100" stroke="#4A5A50" stroke-width="1.4"/>');
$d->add('<line x1="30" y1="84" x2="1170" y2="84" stroke="#4A5A50" stroke-width="1.4"/>');
texte($d, 300, 73, 'Client', ['ancre' => 'middle', 'gras' => true, 'taille' => 16]);
texte($d, 870, 73, 'Serveur (application PHP)', ['ancre' => 'middle', 'gras' => true, 'taille' => 16]);
$C = 300; $S = 860;
$q = fn($b, $txt, $dx = 26, $dy = -10) => texte($d, $b[0] + $b[2] / 2 + $dx, $b[1] + $b[3] / 2 + $dy, $txt, ['taille' => 12, 'italique' => true]);
$g = fn($x, $y, $txt) => texte($d, $x, $y, $txt, ['taille' => 12, 'gras' => true, 'couleur' => '#8A5A00']);

$deb = debut($d, $C, 112);
$a1 = action($d, $C, 170, ['Consulter le menu', '(plats et composeur de bowl)']);
$a2 = action($d, $C, 245, 'Ajouter un plat ou un bowl au panier');
$s1 = action($d, $S, 245, ['Enregistrer l’article dans la session', '(prix du bowl calculé côté serveur)'], 290);
$d1 = decision($d, $C, 320); texte($d, $C + 26, 314, 'Continuer les achats ?', ['taille' => 12, 'italique' => true]);
$a3 = action($d, $C, 400, ['Ouvrir le panier (sans compte)', 'et cliquer sur « Commander »']);
$d2 = decision($d, $S, 400); texte($d, $S - 22, 386, 'Client connecté ?', ['taille' => 12, 'italique' => true, 'ancre' => 'end']);
$s2 = action($d, $S, 470, 'Rediriger vers la page de connexion', 290);
$a4 = action($d, $C, 470, 'Se connecter (ou créer un compte)');
$d3 = decision($d, $S, 550); texte($d, $S - 22, 536, 'Identifiants valides ?', ['taille' => 12, 'italique' => true, 'ancre' => 'end']);
$s3 = action($d, 1035, 550, ['Afficher une erreur', '(blocage au 5e échec)'], 170);
$s4 = action($d, $S, 630, ['Revenir sur commande.php : récapitulatif', 'avec les prix relus en base'], 300);
$a5 = action($d, $C, 700, ['Choisir le créneau, le mode', 'de retrait et le paiement']);
$a6 = action($d, $C, 775, 'Confirmer la commande');
$d4 = decision($d, $S, 775); texte($d, $S - 22, 760, 'Données valides ?', ['taille' => 12, 'italique' => true, 'ancre' => 'end']);
texte($d, $S - 22, 800, '(créneau ≥ 45 min, mode, paiement,', ['taille' => 11, 'couleur' => '#55635A', 'ancre' => 'end']);
texte($d, $S - 22, 814, 'adresse, plats disponibles)', ['taille' => 11, 'couleur' => '#55635A', 'ancre' => 'end']);
$s5 = action($d, 1035, 775, 'Afficher l’erreur', 170);
$s6 = action($d, $S, 860, ['Enregistrer la commande et ses lignes', 'dans une transaction (BEGIN)'], 300);
$d5 = decision($d, $S, 935); texte($d, $S - 22, 921, 'Enregistrement réussi ?', ['taille' => 12, 'italique' => true, 'ancre' => 'end']);
$s7 = action($d, 1035, 935, ['ROLLBACK, message d’erreur', '(panier conservé)'], 190);
$s8 = action($d, $S, 1010, ['COMMIT, vider le panier,', 'afficher la confirmation'], 260);
$a7 = action($d, $C, 1010, ['Payer : espèces à la remise, ou', 'transfert Wave / Orange Money']);
$fin = fin($d, $C, 1072);
$fin2 = fin($d, 1035, 1010);

$f = ['fin' => 'fleche'];
ligne($d, [bas($deb), haut($a1)], $f);
ligne($d, [bas($a1), haut($a2)], $f);
ligne($d, [droite($a2), gauche($s1)], $f);
ligne($d, [bas($s1), [$S, 320], droite($d1)], $f);
ligne($d, [gauche($d1), [110, 320], [110, 170], gauche($a1)], $f); $g(118, 312, '[oui]');
ligne($d, [bas($d1), haut($a3)], $f); $g($C + 8, 352, '[non]');
ligne($d, [droite($a3), gauche($d2)], $f);
ligne($d, [bas($d2), haut($s2)], $f); $g($S + 8, 436, '[non]');
ligne($d, [gauche($s2), droite($a4)], $f);
ligne($d, [bas($a4), [$C, 550], gauche($d3)], $f);
ligne($d, [droite($d3), gauche($s3)], $f); $g($S + 26, 545, '[non]');
ligne($d, [haut($s3), [1035, 440], [380, 440], [380, $a4[1]]], $f);
ligne($d, [bas($d3), haut($s4)], $f); $g($S + 8, 588, '[oui]');
ligne($d, [droite($d2), [1140, 400], [1140, 630], droite($s4)], $f); $g(1060, 393, '[oui]');
ligne($d, [gauche($s4), [$C, 630], haut($a5)], $f);
ligne($d, [bas($a5), haut($a6)], $f);
ligne($d, [droite($a6), gauche($d4)], $f);
ligne($d, [droite($d4), gauche($s5)], $f); $g($S + 26, 770, '[non]');
ligne($d, [haut($s5), [1035, 700], droite($a5)], $f);
ligne($d, [bas($d4), haut($s6)], $f); $g($S + 8, 818, '[oui]');
ligne($d, [bas($s6), haut($d5)], $f);
ligne($d, [droite($d5), gauche($s7)], $f); $g($S + 26, 930, '[non]');
ligne($d, [bas($s7), haut($fin2)], $f);
ligne($d, [bas($d5), haut($s8)], $f); $g($S + 8, 975, '[oui]');
ligne($d, [gauche($s8), droite($a7)], $f);
ligne($d, [bas($a7), haut($fin)], $f);
return $d;
