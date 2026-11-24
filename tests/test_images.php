<?php
// Tests du contrôle des images de plats (includes/image_functions.php).
// Aucun accès à la base : les fichiers sont créés dans un dossier temporaire puis supprimés.

require_once __DIR__ . '/../includes/image_functions.php';

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

$dossier = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zig_images_' . bin2hex(random_bytes(4));
mkdir($dossier);

$creer = static function (string $nom, string $contenu) use ($dossier): string {
    $chemin = $dossier . DIRECTORY_SEPARATOR . $nom;
    file_put_contents($chemin, $contenu);
    return $chemin;
};
$image = static function (string $format): string {
    $img = imagecreatetruecolor(4, 4);
    ob_start();
    if ($format === 'jpg') imagejpeg($img);
    if ($format === 'png') imagepng($img);
    if ($format === 'webp') imagewebp($img);
    imagedestroy($img);
    return (string) ob_get_clean();
};
$fichier = static function (string $nom, string $chemin, int $erreur = UPLOAD_ERR_OK, ?int $taille = null): array {
    return ['name' => $nom, 'tmp_name' => $chemin, 'error' => $erreur, 'size' => $taille ?? (is_file($chemin) ? filesize($chemin) : 0)];
};

$jpg = $creer('a', $image('jpg'));
$png = $creer('b', $image('png'));
$webp = $creer('c', $image('webp'));
$texte = $creer('d', "<?php echo 'pas une image';");

echo "Images acceptées\n";
$r = verifier_image_plat($fichier('plat.jpg', $jpg));
$verifier('JPEG en .jpg accepté', [$r['ok'], $r['extension']], [true, 'jpg']);
$r = verifier_image_plat($fichier('PLAT.JPEG', $jpg));
$verifier('JPEG en .JPEG accepté (casse ignorée)', [$r['ok'], $r['extension']], [true, 'jpg']);
$r = verifier_image_plat($fichier('plat.png', $png));
$verifier('PNG accepté', [$r['ok'], $r['extension']], [true, 'png']);
$r = verifier_image_plat($fichier('plat.webp', $webp));
$verifier('WebP accepté', [$r['ok'], $r['extension']], [true, 'webp']);

echo "\nImages refusées\n";
$r = verifier_image_plat([]);
$verifier('aucun fichier : absent, sans erreur', [$r['ok'], $r['absent'], $r['erreur']], [false, true, null]);
$verifier('erreur d’envoi partiel refusée', verifier_image_plat($fichier('plat.jpg', $jpg, UPLOAD_ERR_PARTIAL))['ok'], false);
$verifier('dépassement de upload_max_filesize refusé', verifier_image_plat($fichier('plat.jpg', $jpg, UPLOAD_ERR_INI_SIZE))['ok'], false);
$verifier('taille supérieure à 2 Mo refusée', verifier_image_plat($fichier('plat.jpg', $jpg, UPLOAD_ERR_OK, TAILLE_MAX_IMAGE_PLAT + 1))['ok'], false);
$verifier('fichier vide refusé', verifier_image_plat($fichier('plat.jpg', $creer('e', '')))['ok'], false);
$verifier('extension .php refusée', verifier_image_plat($fichier('plat.php', $png))['ok'], false);
$verifier('extension .gif refusée', verifier_image_plat($fichier('plat.gif', $png))['ok'], false);
$verifier('texte renommé en .jpg refusé (contenu)', verifier_image_plat($fichier('plat.jpg', $texte))['ok'], false);
$verifier('PNG annoncé en .jpg refusé (incohérence)', verifier_image_plat($fichier('plat.jpg', $png))['ok'], false);
$verifier('fichier temporaire inexistant refusé', verifier_image_plat($fichier('plat.jpg', $dossier . '/absent'))['ok'], false);

echo "\nEnregistrement et renommage\n";
$nom = nom_image_plat('png');
$verifier('nom aléatoire au format plat_<32 hex>.png', (bool) preg_match('/^plat_[0-9a-f]{32}\.png$/', $nom), true);
$verifier('deux noms générés sont différents', nom_image_plat('png') !== nom_image_plat('png'), true);
$r = enregistrer_image_plat($fichier('plat.png', $png), $dossier);
$verifier('fichier non issu d’un envoi HTTP refusé (is_uploaded_file)', [$r['ok'], $r['nom']], [false, null]);

$aGarder = $creer('photo.jpeg', 'x');
supprimer_image_plat('photo.jpeg', $dossier);
$verifier('une image d’origine n’est jamais supprimée', is_file($aGarder), true);
$aSupprimer = $creer($nom, 'x');
supprimer_image_plat($nom, $dossier);
$verifier('une image envoyée remplacée est supprimée', is_file($aSupprimer), false);
supprimer_image_plat('../a', $dossier);
$verifier('un chemin avec ../ est ignoré', is_file($dossier . DIRECTORY_SEPARATOR . 'a'), true);

foreach (glob($dossier . DIRECTORY_SEPARATOR . '*') as $f) unlink($f);
rmdir($dossier);

echo "\n$total tests, $echecs échec(s)\n";
exit($echecs === 0 ? 0 : 1);
