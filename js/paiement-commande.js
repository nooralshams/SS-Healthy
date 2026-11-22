document.querySelectorAll('[data-copy-value]').forEach((bouton) => {
    bouton.addEventListener('click', () => {
        const valeur = bouton.getAttribute('data-copy-value');
        const champ = document.getElementById(bouton.getAttribute('data-copy-target'));
        const statut = bouton.closest('.coordonnees-transfert')?.querySelector('[data-copy-status]');

        if (!valeur || !champ || !statut) return;

        const afficherResultatCopie = (copie) => {
            statut.textContent = copie
                ? 'Numéro copié.'
                : 'Maintenez appuyé sur le numéro pour le copier.';
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(valeur).then(
                () => afficherResultatCopie(true),
                () => afficherResultatCopie(false)
            );
            return;
        }

        champ.focus();
        champ.select();
        afficherResultatCopie(document.execCommand('copy'));
    });
});
