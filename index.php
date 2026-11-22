<?php
// =====================================================
// ZIG HEALTHY - Page d'accueil
// =====================================================
// Cette page présente les services, les plats disponibles et le concept
// de SS'Healthy, avec des actions adaptées aux écrans mobiles et desktop.
$titre_page = 'Accueil';
require_once 'includes/header.php';

// Récupère trois plats disponibles pour les mettre en avant sur la page d'accueil.
$stmt = $pdo->query("SELECT p.*, c.nom AS categorie FROM plats p
                     JOIN categories c ON c.id = p.categorie_id
                     WHERE p.disponible = 1 ORDER BY p.id LIMIT 3");
$plats_vedette = $stmt->fetchAll();
$plat_favori = $plats_vedette[0] ?? null;
$image_favori = $plat_favori
    ? 'images/plats/' . $plat_favori['image']
    : '';
$image_favori_disponible = $plat_favori
    && $plat_favori['image'] !== 'defaut.jpg'
    && file_exists($image_favori);
?>

<section class="hero hero-accueil">
    <div class="conteneur hero-accueil-contenu">
        <div class="hero-texte">
            <div class="hero-badge"><span>✦</span> Ziguinchor, notre meilleure cuisine</div>
            <h1>Savoureux.<br>Équilibré.<br><span class="souligne">Immédiat.</span></h1>
            <p>Des plats équilibrés, préparés avec des produits locaux, pour prendre soin de vous sans renoncer au goût.</p>
            <div class="hero-actions">
                <a href="menu.php" class="btn btn-plein">Découvrir le menu</a>
                <a href="abonnements.php" class="btn btn-mangue hero-abonnement">S'abonner</a>
                <a href="imc.php" class="btn btn-contour">Calculer mon IMC</a>
            </div>
        </div>
        <div class="hero-visuel">
            <div class="hero-image-frame">
                <div class="hero-panel-contenu">
                    <img src="images/sport.jpg" alt="Femme en mouvement pendant une course">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section avantages-section" aria-labelledby="titre-avantages">
    <div class="conteneur">
        <h2 class="section-titre" id="titre-avantages">Bien manger, simplement</h2>
        <div class="grille-avantages">
            <article class="avantage-item">
                <span class="avantage-icone avantage-icone-horloge" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </span>
                <div><h3>Livraison Express</h3><p>Livré partout à Ziguinchor de 9h à 21h.</p></div>
            </article>
            <article class="avantage-item">
                <span class="avantage-icone avantage-icone-feuille" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M20 4c-8 0-14 3-14 10a6 6 0 0 0 6 6c7 0 10-6 8-16Z"></path><path d="M4 21c3-5 6-8 11-11"></path></svg>
                </span>
                <div><h3>Produits Frais</h3><p>Ingrédients sélectionnés chaque matin.</p></div>
            </article>
            <article class="avantage-item">
                <span class="avantage-icone avantage-icone-paiement" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 3 20 6v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-3Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                </span>
                <div><h3>Paiement Facile</h3><p>Wave, Orange Money ou espèces à la livraison.</p></div>
            </article>
        </div>
    </div>
</section>


        <h2 class="favorites-title" id="favorites-title">Nos plats </h2>
        <a class="favorites-menu-link" href="menu.php">
            Voir toute la carte
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
        </a>
       
    </div>
</section>

<section class="section section-plats" id="plats">
    <div class="conteneur">
        <p class="section-sous-titre">Frais, équilibrés et préparés avec soin</p>
        
        <div class="grille-plats">
            <?php foreach ($plats_vedette as $plat): ?>
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
                    <h3><?= e($plat['nom']) ?></h3>
                    <span class="plat-prix-badge"><?= prix_fcfa($plat['prix']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <p style="text-align:center;margin-top:34px;">
            <a href="menu.php" class="btn btn-contour">Voir tout le menu</a>
        </p>
    </div>
</section>

<section class="section concept-accueil" id="concept">
    <div class="conteneur concept-accueil-contenu">
        <div>
            
            <h2 class="section-titre">Tout commence par <em>une rencontre.</em></h2>
            <p>Chaque repas est cuisiné le jour même avec des produits frais, pour une alimentation saine, savoureuse et accessible à Ziguinchor.</p>
        </div>
        <a href="concept.php" class="btn btn-contour">Notre concept</a>
    </div>
</section>

<section class="section routine-section" id="abonnement">
    <div class="conteneur routine-contenu">
        <p class="section-sous-titre">Vos repas, déjà organisés</p>
        <h2 class="section-titre">Simplifiez votre routine</h2>
        <p>Choisissez une formule d'abonnement et gardez le plaisir d'une alimentation équilibrée au quotidien.</p>
        <a href="abonnements.php" class="btn btn-mangue">Choisir mon abonnement</a>
    </div>
</section>

<section class="zone-service" aria-label="Zone de service">
    <div class="conteneur zone-service-contenu">
        <span class="zone-service-icone" aria-hidden="true">⌖</span>
        <div><p class="section-sous-titre">Livraison locale à Ziguinchor </p><h2>Présents dans toute la commune de Ziguinchor.</h2></div>
        <a href="contact.php" class="btn btn-contour">Nous contacter</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
