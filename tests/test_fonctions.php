<?php

require_once __DIR__ . '/../includes/fonctions.php';

$total = 0;
$echecs = 0;
$verifier = static function (string $nom, $obtenu, $attendu) use (&$total, &$echecs): void {
    $total++;
    if ($obtenu === $attendu) {
        echo "  OK   $nom\n";
        return;
    }

    $echecs++;
    echo "  ECHEC $nom\n";
    echo '         attendu : ' . var_export($attendu, true) . "\n";
    echo '         obtenu  : ' . var_export($obtenu, true) . "\n";
};

echo "Profils IMC\n";
$casProfils = [
    [17.0, 'insuffisance'],
    [18.49, 'normal'],
    [24.9, 'normal'],
    [25.0, 'surpoids'],
    [29.9, 'surpoids'],
    [30.0, 'obesite'],
];
foreach ($casProfils as $index => [$imc, $profil]) {
    $verifier('cas T' . ($index + 1), determinerProfil($imc), $profil);
}
$verifier('IMC manquant invalide', determinerProfil(null), 'invalide');
$verifier('IMC non numérique invalide', determinerProfil('inconnu'), 'invalide');
$verifier('IMC tableau invalide', determinerProfil([]), 'invalide');

echo "\nObjectifs autorisés et arrondi\n";
$verifier('IMC manquant sans objectif', objectifs_autorises(null), []);
$verifier('IMC non numérique sans objectif', objectifs_autorises('inconnu'), []);
$verifier('IMC tableau sans objectif', objectifs_autorises([]), []);
$casObjectifs = [
    [18.44, ['prise_masse', 'diabete']],
    [18.49, ['maintien', 'prise_masse', 'diabete']],
    [18.5, ['maintien', 'prise_masse', 'diabete']],
    [24.94, ['maintien', 'prise_masse', 'diabete']],
    [24.95, ['perte_gras', 'diabete']],
    [25.0, ['perte_gras', 'diabete']],
    [29.94, ['perte_gras', 'diabete']],
    [29.95, ['perte_gras', 'diabete']],
    [30.0, ['perte_gras', 'diabete']],
];
foreach ($casObjectifs as $index => [$imc, $objectifs]) {
    $verifier('cas objectif ' . ($index + 1), objectifs_autorises($imc), $objectifs);
}
$verifier('18,49 est classé comme normal après arrondi', determinerProfil(18.49) === 'normal'
    && in_array('maintien', objectifs_autorises(18.49), true), true);
$verifier('18,45 arrondi au seuil 18,5', determinerProfil(18.45), 'normal');
$verifier('valeur numérique en chaîne reconnue', objectifs_autorises('25'), ['perte_gras', 'diabete']);
$verifier('24,95 franchit le seuil 25 après arrondi', determinerProfil(24.95), 'surpoids');
$verifier('29,95 franchit le seuil 30 après arrondi', determinerProfil(29.95), 'obesite');

echo "\nCréneaux\n";
$creneaux = ['Sous 1 h', 'Sous 2 h', 'Pour midi', 'Ce soir'];
$verifier('liste des créneaux proposés', creneaux_disponibles(), $creneaux);
foreach ($creneaux as $creneau) {
    $verifier('créneau accepté : ' . $creneau, creneau_autorise($creneau), true);
}
$verifier('Express refusé', creneau_autorise('Express'), false);
$verifier('Sous 30 min refusé', creneau_autorise('Sous 30 min'), false);
$verifier('valeur nulle refusée', creneau_autorise(null), false);
$verifier('tableau refusé', creneau_autorise([]), false);
$verifier('comparaison sensible à la casse', creneau_autorise('sous 1 h'), false);
$verifier('espace superflu refusé', creneau_autorise(' Sous 1 h'), false);
$verifier('entier refusé comme créneau', creneau_autorise(1), false);

echo "\nÉchappement, jeton CSRF et panier\n";
$verifier('échappement HTML', e('<script>'), '&lt;script&gt;');
$verifier('valeur nulle échappée en chaîne vide', e(null), '');
$_SESSION['csrf_token'] = 'jeton-test';
$verifier('lecture du jeton CSRF', csrf_token(), 'jeton-test');
$verifier('champ CSRF présent', strpos(csrf_field(), 'name="csrf_token"') !== false, true);
$_SESSION['panier'] = [];
$verifier('panier vide sans article', nb_articles_panier(), 0);
$_SESSION['panier'] = [
    ['quantite' => 2],
    ['quantite' => 3],
];
$verifier('total des quantités du panier', nb_articles_panier(), 5);

echo "\n$total tests, $echecs échec(s)\n";
exit($echecs === 0 ? 0 : 1);
