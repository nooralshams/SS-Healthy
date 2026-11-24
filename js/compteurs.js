// Compteurs animés : chaque élément [data-compteur] monte de 0 jusqu'à sa valeur.
// Utilisé par l'espace client (compte.php) et le tableau de bord admin.
(() => {
    const elements = document.querySelectorAll('[data-compteur]');
    if (!elements.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const format = new Intl.NumberFormat('fr-FR');
    const duree = 1100;

    const animer = element => {
        const cible = parseInt(element.dataset.compteur, 10) || 0;
        const debut = performance.now();
        const etape = maintenant => {
            const t = Math.min((maintenant - debut) / duree, 1);
            const adouci = 1 - Math.pow(1 - t, 3);
            element.textContent = format.format(Math.round(cible * adouci));
            if (t < 1) requestAnimationFrame(etape);
        };
        element.textContent = '0';
        requestAnimationFrame(etape);
    };

    if (!('IntersectionObserver' in window)) {
        elements.forEach(animer);
        return;
    }
    const observateur = new IntersectionObserver(entrees => {
        entrees.forEach(entree => {
            if (entree.isIntersecting) {
                animer(entree.target);
                observateur.unobserve(entree.target);
            }
        });
    }, { threshold: 0.4 });
    elements.forEach(element => observateur.observe(element));
})();
