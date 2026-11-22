<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/bowl_config.php';
require_once __DIR__ . '/includes/bowl_functions.php';

header('Content-Type: application/json; charset=utf-8');

$repondre = static function (int $code, array $donnees): void {
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $repondre(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
}

$selection = json_decode($_POST['selection_json'] ?? '', true);
$bowl = preparer_selection_bowl($selection, $BOWL_CONFIG);
if (!$bowl['ok']) {
    $repondre(422, ['ok' => false, 'message' => $bowl['message']]);
}
$taille = $bowl['size'];
$prixTotal = $bowl['price'];
$composition = $bowl['composition'];

if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$ligneId = 'bowl_' . bin2hex(random_bytes(6));
$_SESSION['panier'][$ligneId] = [
    'type' => 'bowl_personnalise',
    'plat_id' => null,
    'nom' => 'Bowl personnalisé (' . $taille['label'] . ')',
    'prix' => $prixTotal,
    'description' => $composition,
    'quantite' => 1,
];

$repondre(200, [
    'ok' => true,
    'message' => 'Votre bowl a été ajouté au panier.',
    'count' => nb_articles_panier(),
    'name' => $_SESSION['panier'][$ligneId]['nom'],
    'price' => $prixTotal,
]);
