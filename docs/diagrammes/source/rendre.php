<?php
// Usage : php rendre.php <dossier_sortie> d05_sequence_commande [autres…]
// Produit NOM.svg et NOM.html ; le PNG est fait par Edge (voir rendre.sh).
$sortie = $argv[1];
foreach (array_slice($argv, 2) as $nom) {
    $d = require __DIR__ . "/$nom.php";
    $svg = $d->svg();
    file_put_contents("$sortie/$nom.svg", $svg);
    file_put_contents("$sortie/$nom.html", '<!DOCTYPE html><html><head><meta charset="utf-8"><style>html,body{margin:0;padding:0;background:#fff;overflow:hidden}</style></head><body>' . $svg . '</body></html>');
    file_put_contents("$sortie/$nom.taille", $d->w . ',' . ($d->h + $d->decalage));
    echo "$nom : {$d->w} x {$d->h}\n";
}
