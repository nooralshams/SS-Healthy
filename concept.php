<?php
// =====================================================
// ZIG HEALTHY - Page concept
// =====================================================
// Cette page décrit le concept du service : commande, abonnements,
// paiement à la livraison et la promesse nutritionnelle du restaurant.
$titre_page = 'Notre concept';
require_once 'includes/header.php';
?>

<section class="section concept-page">
    <div class="conteneur">
        <div class="concept-introduction">
            <p class="concept-label">Notre concept</p>
            <h1>Comment ça<br>marche&nbsp;?</h1>
            <p>Faites le choix d'une alimentation équilibrée sans effort. Découvrez des options concrètes pour revitaliser votre quotidien.</p>
        </div>

        <div class="concept-etapes">
            <article class="concept-etape concept-etape-commande">
                <div class="concept-etape-principal">
                    <div class="concept-commande-icone" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M3.5 6.5h2l2 9h9.5l2.2-7.5H7.2"/>
                            <circle cx="10" cy="17.5" r="1.2"/>
                            <circle cx="16.5" cy="17.5" r="1.2"/>
                        </svg>
                    </div>

                    <h2>Le plaisir<br>à la carte</h2>
                    <p>Pensé pour vos envies spontanées, en toute simplicité.</p>
                    <div class="concept-actions">
                        <div class="concept-action"><span>1</span><div><strong>Laissez-vous tenter</strong><p>Découvrez nos plats frais et leurs bienfaits nutritionnels en un coup d'œil.</p></div></div>
                        <div class="concept-action"><span>2</span><div><strong>Composez selon vos goûts</strong><p>Piochez dans nos suggestions ou imaginez votre bowl sur mesure.</p></div></div>
                        <div class="concept-action"><span>3</span><div><strong>Savourez l'instant</strong><p>Renseignez votre adresse de livraison, notre équipe en cuisine prend le relais.</p></div></div>
                    </div>
                    <a href="menu.php" class="btn btn-plein concept-cta">Voir le menu maintenant</a>
                </div>
                <div class="concept-etape-visuel concept-visuel-fraicheur">
                    <div class="concept-fraicheur-card"><span class="concept-fraicheur-icone">✓</span><strong>FRAÎCHEUR GARANTIE</strong><p>« Chaque plat est cuisiné à la commande avec des ingrédients frais. »</p></div>
                </div>
            </article>

            <article class="concept-paiement" id="paiement">
                <div class="paiement-contenu">
                    <div class="paiement-icone paiement-icone-carte" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M2.5 8.5h10.5v7H2.5z"/>
                            <path d="M13 10.5h3.5l2.5 2.8v3.2H13z"/>
                            <circle cx="7.2" cy="17.5" r="1.7"/>
                            <circle cx="17.8" cy="17.5" r="1.7"/>
                            <path d="M5 8.5V6.2h8v2.3"/>
                        </svg>
                    </div>
                <h2>Livraison aux<br>frais du client</h2>
                    <p>La livraison est calculée selon votre zone et réglée à la réception du repas, en toute transparence.</p>
                    <div class="paiement-methodes">
                        <span class="paiement-methode paiement-methode-cash">CASH</span>
                        <span class="paiement-methode paiement-methode-wave">WAVE</span>
                        <span class="paiement-methode paiement-methode-orange">ORANGE MONEY</span>
                    </div>
                </div>
                <div class="paiement-visuel">
                    <div class="paiement-securite"><span class="paiement-bouclier" aria-hidden="true">✓</span><strong>Simple &amp; Sûr</strong><small>Zéro risque, 100% confiance.</small></div>
                </div>
            </article>

            <section class="concept-final-cta">
                <h2>Envie de bien manger sain&nbsp;?</h2>
                <div class="concept-final-actions">
                    <a href="menu.php" class="btn btn-plein">Voir la carte <span aria-hidden="true">→</span></a>
                    <a href="abonnements.php" class="btn btn-contour">S'abonner</a>
                </div>
            </section>

            
        </div>

    
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
