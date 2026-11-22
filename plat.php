<?php
// =====================================================
// ZIG HEALTHY - Fiche produit
// =====================================================
// Cette page affiche les détails d'un plat sélectionné et permet de
// l'ajouter au panier avec une quantité précise.
$titre_page = 'Détail du plat';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$plat = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT p.*, c.nom AS categorie
                           FROM plats p
                           JOIN categories c ON c.id = p.categorie_id
                           WHERE p.id = ? AND p.disponible = 1");
    $stmt->execute([$id]);
    $plat = $stmt->fetch();
}

if (!$plat) {
    http_response_code(404);
    $titre_page = 'Plat introuvable';
    require_once 'includes/header.php';
    ?>
    <section class="section">
        <div class="conteneur">
            <h1 class="section-titre">Plat introuvable</h1>
            <p class="section-sous-titre">Ce plat n'est plus disponible ou le lien est incorrect.</p>
            <a href="menu.php" class="btn btn-plein">Retour au menu</a>
        </div>
    </section>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$objectifs = $pdo->prepare("SELECT objectif FROM plat_objectifs WHERE plat_id = ?");
$objectifs->execute([$plat['id']]);
$objectifs_plat = $objectifs->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
    $quantite = $quantite !== false && $quantite !== null ? $quantite : 1;
    $quantite = max(1, min(20, $quantite));

    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    $article_trouve = false;
    foreach ($_SESSION['panier'] as &$article) {
        if ((int) ($article['plat_id'] ?? 0) === (int) $plat['id']) {
            $article['quantite'] = min(20, (int) $article['quantite'] + $quantite);
            $article_trouve = true;
            break;
        }
    }
    unset($article);

    if (!$article_trouve) {
        $_SESSION['panier'][] = [
            'plat_id' => (int) $plat['id'],
            'quantite' => $quantite
        ];
    }

    $_SESSION['message_panier'] = $plat['nom'] . ' a été ajouté au panier.';
    header('Location: plat.php?id=' . $plat['id']);
    exit;
}

$chemin_img = 'images/plats/' . $plat['image'];
require_once 'includes/header.php';
?>

<section class="section">
    <div class="conteneur">
        <?php if (!empty($_SESSION['message_panier'])): ?>
            <div class="alerte alerte-succes">
                <?= e($_SESSION['message_panier']) ?>
            </div>
            <?php unset($_SESSION['message_panier']); ?>
        <?php endif; ?>

        <a href="menu.php" class="filtre">← Retour au menu</a>
        <div class="fiche-plat">
            <div>
                <?php if ($plat['image'] !== 'defaut.jpg' && file_exists($chemin_img)): ?>
                    <img class="fiche-plat-image" src="<?= e($chemin_img) ?>" alt="<?= e($plat['nom']) ?>">
                <?php else: ?>
                    <div class="fiche-plat-image plat-image-defaut">🥗</div>
                <?php endif; ?>
            </div>

            <div class="fiche-plat-contenu">
                <p class="section-sous-titre"><?= e($plat['categorie']) ?></p>
                <h1 class="section-titre"><?= e($plat['nom']) ?></h1>
                <p><?= e($plat['description']) ?></p>

                <div class="macros fiche-plat-macros">
                    <span class="macro"><b><?= e($plat['calories']) ?></b> kcal</span>
                    <span class="macro"><b><?= e($plat['proteines']) ?>g</b> Protéines</span>
                    <span class="macro"><b><?= e($plat['glucides']) ?>g</b> Glucides</span>
                    <span class="macro"><b><?= e($plat['lipides']) ?>g</b> Lipides</span>
                </div>

                <div class="fiche-plat-badges">
                    <?php foreach ($objectifs_plat as $objectif): ?>
                        <?= badge_objectif($objectif, $OBJECTIFS) ?>
                    <?php endforeach; ?>
                    <?php if ((int) $plat['ig_faible'] === 1): ?>
                        <span class="badge badge-diabete">Index glycémique faible</span>
                    <?php endif; ?>
                </div>

                <div class="fiche-plat-achat">
                    <span class="prix"><?= prix_fcfa($plat['prix']) ?></span>
                    <form method="post">
                        <?= csrf_field() ?>
                        <label for="quantite">Quantité</label>
                        <input id="quantite" name="quantite" type="number" min="1" max="20" value="1">
                        <button type="submit" class="btn btn-mangue">Ajouter au panier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
