// Menu en rideau sous le logo : site public (client ou administrateur connecté)
// et espace d'administration.
(() => {
    const header = document.querySelector('.site-header--connecte');
    const bouton = header?.querySelector('.rideau-toggle');
    const rideau = header?.querySelector('.nav-rideau');

    if (!header || !bouton || !rideau) return;

    const ouvrir = ouvert => {
        header.classList.toggle('rideau-ouvert', ouvert);
        bouton.setAttribute('aria-expanded', String(ouvert));
    };

    bouton.addEventListener('click', () => ouvrir(bouton.getAttribute('aria-expanded') !== 'true'));
    rideau.querySelectorAll('a').forEach(lien => lien.addEventListener('click', () => ouvrir(false)));
    document.addEventListener('click', evenement => {
        if (!header.contains(evenement.target)) ouvrir(false);
    });
    document.addEventListener('keydown', evenement => {
        if (evenement.key === 'Escape' && bouton.getAttribute('aria-expanded') === 'true') {
            ouvrir(false);
            bouton.focus();
        }
    });
})();
