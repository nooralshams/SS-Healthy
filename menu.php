<?php
// =====================================================
// ZIG HEALTHY - Catalogue des plats
// =====================================================
// Cette page affiche la liste des plats disponibles avec filtres par objectif
// et par catégorie. Elle permet aussi de naviguer vers la fiche d'un plat.
$titre_page = 'Notre menu';
require_once 'includes/header.php';

// ---- Filtres de recherche sur le menu ----
$objectif_par_defaut = $_SESSION['objectif_recommande'] ?? '';
if (est_connecte()) {
    $stmt = $pdo->prepare('SELECT objectif FROM utilisateurs WHERE id = ?');
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $objectif_par_defaut = $stmt->fetchColumn() ?: $objectif_par_defaut;
}
$objectif_actif  = $_GET['objectif'] ?? $objectif_par_defaut;
$categorie_active = isset($_GET['categorie']) ? (int) $_GET['categorie'] : 0;
$menu_verrouille = $objectif_actif !== '' && array_key_exists($objectif_actif, $OBJECTIFS);

$sql = "SELECT DISTINCT p.*, c.nom AS categorie
        FROM plats p
        JOIN categories c ON c.id = p.categorie_id
        LEFT JOIN plat_objectifs po ON po.plat_id = p.id
        WHERE p.disponible = 1";
$params = [];

if ($objectif_actif !== '' && array_key_exists($objectif_actif, $OBJECTIFS)) {
    $sql .= " AND po.objectif = ?";
    $params[] = $objectif_actif;
}
if ($categorie_active > 0) {
    $sql .= " AND p.categorie_id = ?";
    $params[] = $categorie_active;
}
$sql .= " ORDER BY c.id, p.nom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$plats = $stmt->fetchAll();

// Objectifs de chaque plat (pour les badges)
$objectifs_plats = [];
foreach ($pdo->query("SELECT plat_id, objectif FROM plat_objectifs") as $ligne) {
    $objectifs_plats[$ligne['plat_id']][] = $ligne['objectif'];
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();

/** Construit une URL de filtre en conservant l'autre paramètre */
function url_filtre($objectif, $categorie) {
    $p = [];
    if ($objectif !== '') $p['objectif'] = $objectif;
    if ($categorie > 0)   $p['categorie'] = $categorie;
    return 'menu.php' . ($p ? '?' . http_build_query($p) : '');
}
?>

<section class="section concept-page">
    <div class="conteneur">
        <article class="menu-page" id="menu">
            <div class="abonnement-intro-block">
                <span class="abonnement-badge">NOTRE MENU</span>
               
                <h2 class="builder-title menu-builder-title" id="builder-title">Vos choix,<br><em>votre repas.</em></h2>
                <p class="section-sous-titre">Des recettes pour chaque objectif. Trouvez rapidement vos plats préférés en filtrant par objectif nutritionnel ou par catégorie.</p>
            </div>

            <section class="menu-choix-parcours menu-choix-bowl" id="choix-menu" aria-label="Créer un bowl personnalisé">
                <article class="menu-choix-card">
                    <p class="menu-choix-label">À composer</p>
                    <h2>Créez votre bowl</h2>
                    <p>Choisissez vos ingrédients, votre sauce et vos extras.</p>
                    <a class="menu-choix-button menu-choix-button-builder" href="#composer-bowl">
                        <span>Composer mon bowl</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14m-7-7 7 7 7-7"></path></svg>
                    </a>
                </article>
            </section>

            <?php require __DIR__ . '/includes/bowl_builder.php'; ?>

            <section class="menu-choix-parcours menu-choix-plats" aria-label="Choisir un plat du menu">
                <article class="menu-choix-card">
                    <p class="menu-choix-label">Déjà préparés</p>
                    <h2>Choisissez un plat</h2>
                    <p>Parcourez les plats enregistrés au menu et commandez votre favori.</p>
                    <a class="menu-choix-button menu-choix-button-outline" href="#plats-disponibles">Voir les plats</a>
                </article>
            </section>

        <!-- Filtres par objectif -->
        <div class="filtres">
            <?php if ($menu_verrouille): ?>
                <span class="filtre actif"><?= e($OBJECTIFS[$objectif_actif]) ?></span>
            <?php else: ?>
                <a href="<?= url_filtre('', $categorie_active) ?>" class="filtre <?= $objectif_actif === '' ? 'actif' : '' ?>">Tous les objectifs</a>
                <?php foreach ($OBJECTIFS as $cle => $libelle): ?>
                    <a href="<?= url_filtre($cle, $categorie_active) ?>" class="filtre <?= $objectif_actif === $cle ? 'actif' : '' ?>"><?= e($libelle) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!$menu_verrouille): ?>
        <!-- Filtres par catégorie -->
        <div class="filtres">
            <a href="<?= url_filtre($objectif_actif, 0) ?>" class="filtre <?= $categorie_active === 0 ? 'actif' : '' ?>">Toutes les catégories</a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url_filtre($objectif_actif, $cat['id']) ?>" class="filtre <?= $categorie_active === (int)$cat['id'] ? 'actif' : '' ?>"><?= e($cat['nom']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="section-sous-titre" style="margin-top:12px;">Les autres objectifs et catégories sont verrouillés selon votre profil IMC recommandé.</p>
        <?php endif; ?>

        <?php if (empty($plats)): ?>
            <div id="plats-disponibles"><p>Aucun plat ne correspond à ces filtres pour le moment.</p></div>
        <?php else: ?>
        <div class="grille-plats" id="plats-disponibles">
            <?php foreach ($plats as $plat): ?>
            <a class="carte-plat" href="plat.php?id=<?= $plat['id'] ?>" aria-label="Voir <?= e($plat['nom']) ?>">
                <div class="plat-visuel">
                    <?php $chemin_img = 'images/plats/' . $plat['image']; ?>
                    <?php if ($plat['image'] !== 'defaut.jpg' && file_exists($chemin_img)): ?>
                        <img src="<?= e($chemin_img) ?>" alt="<?= e($plat['nom']) ?>">
                    <?php else: ?>
                        <div class="plat-image-defaut">🥗</div>
                    <?php endif; ?>
                </div>
                <div class="carte-plat-corps">
                    <div style="margin-bottom:8px;">
                        <?php foreach ($objectifs_plats[$plat['id']] ?? [] as $obj): ?>
                            <?= badge_objectif($obj, $OBJECTIFS) ?>
                        <?php endforeach; ?>
                    </div>
                    <h3><?= e($plat['nom']) ?></h3>
                    <span class="plat-prix-badge"><?= prix_fcfa($plat['prix']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script src="js/bowl-builder.js?v=<?= filemtime(__DIR__ . '/js/bowl-builder.js') ?>" defer></script>
<?php require_once 'includes/footer.php'; ?>
