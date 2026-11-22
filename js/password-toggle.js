document.querySelectorAll('input[type="password"]').forEach((input) => {
    const bouton = document.createElement('button');
    bouton.type = 'button';
    bouton.className = 'password-toggle';
    bouton.textContent = 'Afficher';
    bouton.setAttribute('aria-label', 'Afficher le mot de passe');
    bouton.setAttribute('aria-pressed', 'false');

    if (input.parentElement.classList.contains('champ-input')) {
        input.parentElement.appendChild(bouton);
    } else {
        const conteneur = document.createElement('div');
        conteneur.className = 'password-input-control';
        input.parentNode.insertBefore(conteneur, input);
        conteneur.append(input, bouton);
    }

    bouton.addEventListener('click', () => {
        const afficher = input.type === 'password';
        input.type = afficher ? 'text' : 'password';
        bouton.textContent = afficher ? 'Masquer' : 'Afficher';
        bouton.setAttribute('aria-label', afficher ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        bouton.setAttribute('aria-pressed', String(afficher));
    });
});
