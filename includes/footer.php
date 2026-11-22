</main>
<!--
    Pied de page commun à toutes les pages.
    Il contient le logo, les liens utiles, l'adresse et les informations
    de contact du restaurant.
-->
<footer class="site-footer">
    <div class="conteneur footer-grille">
        <div>
            <p class="footer-logo"><span class="logo-marque">SS’Healthy</span></p>
            <p class="footer-description">Votre dose quotidienne de fraîcheur à Ziguinchor. Des repas sains livrés chez vous.</p>
        </div>
        <div>
            <p class="footer-titre">Produits</p>
            <a href="menu.php">Menus</a>
            <a href="abonnements.php">Abonnements</a>
        </div>
        <div>
            <p class="footer-titre">Société</p>
            <a href="concept.php">Concept</a>
            <a href="contact.php">Contact</a>
        </div>
        <div>
            <p class="footer-titre">Légal</p>
            <a href="cgv.php">Conditions générales de vente</a>
            <a href="confidentialite.php">Confidentialité</a>
        </div>
    </div>
    <div class="conteneur footer-bas">
        <p class="footer-copy">© <?= date('Y') ?> SS’Healthy. Tous droits réservés.</p>
        <p class="footer-contact-bas">Ziguinchor, Sénégal <span>•</span> 09h00 — 21h00 <span>•</span> noorshams@sshealthy.sn</p>
    </div>
</footer>
<nav class="barre-mobile-actions" aria-label="Actions rapides">
    <a class="btn btn-contour" href="abonnements.php">S'abonner</a>
    <a class="btn btn-plein" href="menu.php">Commander</a>
</nav>
<script>
(() => {
    const header = document.querySelector('.site-header');
    const toggle = header?.querySelector('.menu-toggle');
    const nav = header?.querySelector('#menu-principal');

    if (!header || !toggle || !nav) return;

    const fermerMenu = () => {
        header.classList.remove('menu-ouvert');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Ouvrir le menu');
    };

    toggle.addEventListener('click', () => {
        const ouvert = toggle.getAttribute('aria-expanded') === 'true';
        header.classList.toggle('menu-ouvert', !ouvert);
        toggle.setAttribute('aria-expanded', String(!ouvert));
        toggle.setAttribute('aria-label', ouvert ? 'Ouvrir le menu' : 'Fermer le menu');
    });

    nav.querySelectorAll('a').forEach(lien => lien.addEventListener('click', fermerMenu));
    document.addEventListener('keydown', evenement => {
        if (evenement.key === 'Escape') fermerMenu();
    });
})();
</script>
</body>
</html>
