(() => {
    const section = document.querySelector('.builder-section');
    if (!section) return;

    const config = JSON.parse(section.dataset.bowlConfig || '{}');
    const card = section.querySelector('.builder-card');
    const content = section.querySelector('[data-builder-content]');
    const stepName = section.querySelector('[data-builder-step-name]');
    const stepNumber = section.querySelector('[data-builder-step-number]');
    const progress = section.querySelector('[data-builder-progress]');
    const progressTrack = section.querySelector('[role="progressbar"]');
    const total = section.querySelector('[data-builder-total]');
    const backButton = section.querySelector('[data-builder-back]');
    const nextButton = section.querySelector('[data-builder-next]');
    const nextLabel = section.querySelector('[data-builder-next-label]');
    const nextIcon = section.querySelector('[data-builder-next-icon]');
    const stepNames = ['TAILLE', 'BASE', 'PROTÉINE', 'LÉGUMES', 'SAUCE', 'EXTRAS'];
    const money = new Intl.NumberFormat('fr-FR');
    const optionIcon = selected => selected
        ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>'
        : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>';

    const state = {
        step: 0,
        size: 'small',
        bases: [],
        proteins: [],
        vegetables: [],
        sauce: '',
        quinoaSelection: false,
        extraUnits: 0,
        selectionOrder: [],
        errors: [],
        notice: '',
        busy: false,
    };

    const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);
    const formatPrice = amount => `<span class="prix-affichage"><span class="prix-montant">${money.format(amount)}</span><span class="prix-devise">FCFA</span></span>`;
    const capacity = () => config.sizes[state.size].units + state.extraUnits;
    const usedUnits = () => state.bases.length + state.proteins.length + state.vegetables.length;
    const remaining = () => Math.max(0, capacity() - usedUnits());
    const premiumCount = () => state.proteins.filter(id => config.proteins[id].premium).length;
    const price = () => config.sizes[state.size].price
        + premiumCount() * config.extras.premium_protein_price
        + state.extraUnits * config.extras.extra_unit_price;

    const itemText = (label, meta, selected, disabled, value, group, priceAmount = null) => `
        <button type="button" class="builder-option${selected ? ' is-selected' : ''}${disabled ? ' is-disabled' : ''}"
            data-builder-group="${escapeHtml(group)}" data-builder-value="${escapeHtml(value)}"
            aria-pressed="${selected ? 'true' : 'false'}" ${disabled ? 'disabled' : ''}>
            <span class="builder-option-copy"><strong>${escapeHtml(label)}</strong><small>${escapeHtml(meta)}</small></span>
            ${priceAmount !== null ? `<span class="builder-option-price">+${formatPrice(priceAmount)}</span>` : ''}
            <span class="builder-option-check" aria-hidden="true">${optionIcon(selected)}</span>
        </button>`;

    const optionGrid = options => `<div class="builder-option-grid">${options.join('')}</div>`;

    const unitHint = detail => `
        <p class="builder-hint"><span>Il vous reste ${remaining()} unité${remaining() > 1 ? 's' : ''}.</span> ${detail || ''}</p>`;

    const errorMarkup = () => state.errors.length ? `
        <div class="builder-error" role="alert">
            <span class="builder-error-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 7v6"></path><circle cx="12" cy="17" r=".8"></circle></svg></span>
            <ul>${state.errors.map(error => `<li>${escapeHtml(error)}</li>`).join('')}</ul>
        </div>` : '';

    const noticeMarkup = () => state.notice
        ? `<p class="builder-notice" role="status">${escapeHtml(state.notice)}</p>`
        : '';

    const choicesMarkup = () => {
        if (state.step === 0) {
            const options = Object.entries(config.sizes).map(([id, size]) => `
                <button type="button" class="builder-option builder-size-option${state.size === id ? ' is-selected' : ''}"
                    data-builder-group="size" data-builder-value="${escapeHtml(id)}" aria-pressed="${state.size === id}">
                    <span class="builder-option-copy"><strong>${escapeHtml(size.label)}</strong><small>${size.units} INGRÉDIENTS AU CHOIX</small></span>
                    <span class="builder-option-price">${formatPrice(size.price)}</span>
                    <span class="builder-option-check" aria-hidden="true">${state.size === id ? optionIcon(true) : ''}</span>
                </button>`);
            return optionGrid(options);
        }

        if (state.step === 1) {
            const options = Object.entries(config.bases).map(([id, label]) =>
                itemText(label, '100G PORTION', state.bases.includes(id), remaining() === 0 && !state.bases.includes(id), id, 'bases'));
            return unitHint('Choisissez une base pour composer votre bowl.') + optionGrid(options);
        }

        if (state.step === 2) {
            const standard = [];
            const premium = [];
            Object.entries(config.proteins).forEach(([id, protein]) => {
                const option = itemText(
                    protein.label,
                    protein.premium ? 'PREMIUM · 100G PORTION' : '100G PORTION',
                    state.proteins.includes(id),
                    remaining() === 0 && !state.proteins.includes(id),
                    id,
                    'proteins',
                    protein.premium ? config.extras.premium_protein_price : null
                );
                (protein.premium ? premium : standard).push(option);
            });
            return unitHint('Les protéines premium ajoutent un supplément.')
                + `<h4 class="builder-group-label">STANDARD</h4>${optionGrid(standard)}`
                + `<h4 class="builder-group-label">PREMIUM</h4>${optionGrid(premium)}`;
        }

        if (state.step === 3) {
            const options = Object.entries(config.vegetables).map(([id, label]) =>
                itemText(label, '70G PORTION', state.vegetables.includes(id), remaining() === 0 && !state.vegetables.includes(id), id, 'vegetables'));
            return unitHint('Chaque légume compte pour une unité.') + optionGrid(options);
        }

        if (state.step === 4) {
            const options = Object.entries(config.sauces).map(([id, label]) => `
                <button type="button" class="builder-option${state.sauce === id ? ' is-selected' : ''}"
                    data-builder-group="sauce" data-builder-value="${escapeHtml(id)}" aria-pressed="${state.sauce === id}">
                    <span class="builder-option-copy"><strong>${escapeHtml(label)}</strong><small>SAUCE OFFERTE</small></span>
                    <span class="builder-option-check" aria-hidden="true">${optionIcon(state.sauce === id)}</span>
                </button>`);
            return `<p class="builder-hint"><span>Le choix d'une sauce est obligatoire et gratuit</span> (ne consomme pas d'unités).</p>${optionGrid(options)}`;
        }

        return `
            <p class="builder-hint"><span>Personnalisez votre bowl avec les extras de votre choix.</span></p>
            <div class="builder-extras-list">
                <div class="builder-extra-card${state.quinoaSelection ? ' is-selected' : ''}">
                    <span class="builder-extra-sparkles" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"></path><path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"></path></svg>
                    </span>
                    <span class="builder-extra-copy"><strong>Fonio Sélection</strong><small>OFFERT</small></span>
                    <button type="button" class="builder-extra-toggle${state.quinoaSelection ? ' is-on' : ''}"
                        data-builder-group="quinoaSelection" aria-pressed="${state.quinoaSelection}"
                        aria-label="${state.quinoaSelection ? 'Retirer' : 'Ajouter'} Fonio Sélection"><span></span></button>
                </div>
                <div class="builder-extra-card builder-extra-units">
                    <span class="builder-extra-copy"><strong>Unités supplémentaires</strong><small>+${formatPrice(config.extras.extra_unit_price)} / unité</small></span>
                    <div class="builder-stepper" aria-label="Unités supplémentaires">
                        <button type="button" class="builder-stepper-minus" data-builder-stepper="minus" aria-label="Retirer une unité" ${state.extraUnits === 0 ? 'disabled' : ''}><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path></svg></button>
                        <output>${state.extraUnits}</output>
                        <button type="button" class="builder-stepper-plus" data-builder-stepper="plus" aria-label="Ajouter une unité" ${state.extraUnits >= config.extras.max_extra_units ? 'disabled' : ''}><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg></button>
                    </div>
                </div>
            </div>
            <p class="builder-extra-capacity">${usedUnits()} ingrédient${usedUnits() === 1 ? '' : 's'} sélectionné${usedUnits() === 1 ? '' : 's'} · ${capacity()} unités disponibles</p>`;
    };

    const render = () => {
        const stepContent = [
            ['Quelle taille souhaitez-vous ?', 'Choisissez la taille de votre bowl.'],
            ['Choisissez votre base', 'Chaque base compte pour une unité.'],
            ['Quelle protéine choisissez-vous ?', 'Sélectionnez une ou plusieurs protéines.'],
            ['Ajoutez vos légumes', 'Composez selon vos envies et les unités restantes.'],
            ['Choisissez votre sauce', 'Une sauce est requise pour finaliser votre bowl.'],
            ['La touche finale', 'Ajoutez un extra ou quelques unités supplémentaires.'],
        ][state.step];

        section.classList.toggle('builder-first-step', state.step === 0);
        stepName.textContent = stepNames[state.step];
        stepNumber.textContent = String(state.step + 1);
        progress.style.width = `${((state.step + 1) / stepNames.length) * 100}%`;
        progressTrack.setAttribute('aria-valuenow', String(state.step + 1));
        total.querySelector('[data-builder-price-value]').textContent = money.format(price());
        backButton.hidden = state.step === 0;
        nextLabel.textContent = state.step === stepNames.length - 1 ? 'Ajouter au panier' : 'Continuer';
        nextIcon.innerHTML = state.step === stepNames.length - 1
            ? '<path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.6L21 8H7"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle>'
            : '<path d="M5 12h14m-6-6 6 6-6 6"></path>';
        nextButton.disabled = state.busy;
        content.innerHTML = `
            <div class="builder-question">
                <h3>${stepContent[0]}</h3>
                <p>${stepContent[1]}</p>
            </div>
            ${errorMarkup()}${noticeMarkup()}${choicesMarkup()}`;
    };

    const setSelected = (group, id, selected) => {
        const list = state[group];
        if (selected) {
            if (!list.includes(id)) {
                list.push(id);
                state.selectionOrder.push(`${group}:${id}`);
            }
        } else {
            state[group] = list.filter(value => value !== id);
            state.selectionOrder = state.selectionOrder.filter(value => value !== `${group}:${id}`);
        }
    };

    const trimSelections = () => {
        let removed = false;
        while (usedUnits() > capacity() && state.selectionOrder.length) {
            const latest = state.selectionOrder.pop();
            const separator = latest.indexOf(':');
            const group = latest.slice(0, separator);
            const id = latest.slice(separator + 1);
            state[group] = state[group].filter(value => value !== id);
            removed = true;
        }
        if (removed) {
            state.notice = 'La nouvelle limite a retiré les derniers ingrédients sélectionnés.';
        }
    };

    const validateStep = () => {
        if (state.step === 1 && state.bases.length === 0) return ['Une base est requise.'];
        if (state.step === 2 && state.proteins.length === 0) return ['Une protéine est requise.'];
        if (state.step === 4 && !state.sauce) return ['Choisissez une sauce.'];
        if (state.step === 5) {
            const errors = [];
            if (state.bases.length === 0) errors.push('Une base est requise.');
            if (state.proteins.length === 0) errors.push('Une protéine est requise.');
            if (!state.sauce) errors.push('Choisissez une sauce.');
            return errors;
        }
        return [];
    };

    const payload = () => ({
        size: state.size,
        bases: state.bases,
        proteins: state.proteins,
        vegetables: state.vegetables,
        sauce: state.sauce,
        extras: { quinoaSelection: state.quinoaSelection },
        extraUnits: state.extraUnits,
    });

    const updateCartCount = count => {
        const cartLink = document.querySelector('.lien-panier');
        if (!cartLink) return;
        let badge = cartLink.querySelector('.panier-compte');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'panier-compte';
            cartLink.appendChild(badge);
        }
        badge.textContent = String(count);
    };

    const addToCart = async () => {
        state.busy = true;
        state.errors = [];
        state.notice = '';
        render();
        try {
            const form = new FormData();
            form.set('selection_json', JSON.stringify(payload()));
            form.set('csrf_token', section.dataset.csrfToken || '');
            const response = await fetch('bowl_add.php', { method: 'POST', body: form, credentials: 'same-origin' });
            const result = await response.json();
            if (!response.ok || !result.ok) {
                state.errors = [result.message || 'Impossible d’ajouter ce bowl au panier.'];
                state.step = 5;
                state.busy = false;
                render();
                return;
            }
            updateCartCount(result.count);
            Object.assign(state, {
                step: 0,
                size: 'small',
                bases: [],
                proteins: [],
                vegetables: [],
                sauce: '',
                quinoaSelection: false,
                extraUnits: 0,
                selectionOrder: [],
                errors: [],
                notice: `${result.name} ajouté au panier.`,
                busy: false,
            });
            render();
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            state.busy = false;
            state.errors = ['Une erreur réseau empêche l’ajout au panier. Réessayez.'];
            render();
        }
    };

    content.addEventListener('click', event => {
        const option = event.target.closest('[data-builder-group]');
        if (option) {
            const group = option.dataset.builderGroup;
            const id = option.dataset.builderValue;
            state.errors = [];
            state.notice = '';

            if (group === 'size') {
                state.size = id;
                trimSelections();
            } else if (group === 'sauce') {
                state.sauce = state.sauce === id ? '' : id;
            } else if (group === 'bases' || group === 'proteins' || group === 'vegetables') {
                setSelected(group, id, !state[group].includes(id));
            } else if (group === 'quinoaSelection') {
                state.quinoaSelection = !state.quinoaSelection;
            }
            render();
            return;
        }

        const stepper = event.target.closest('[data-builder-stepper]');
        if (stepper) {
            state.notice = '';
            state.errors = [];
            if (stepper.dataset.builderStepper === 'plus') {
                state.extraUnits = Math.min(config.extras.max_extra_units, state.extraUnits + 1);
            } else {
                state.extraUnits = Math.max(0, state.extraUnits - 1);
                trimSelections();
            }
            render();
        }
    });

    backButton.addEventListener('click', () => {
        if (state.step === 0) return;
        state.step--;
        state.errors = [];
        state.notice = '';
        render();
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    nextButton.addEventListener('click', async () => {
        if (state.busy) return;
        const errors = validateStep();
        if (errors.length) {
            state.errors = errors;
            render();
            content.querySelector('.builder-error')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (state.step === stepNames.length - 1) {
            await addToCart();
            return;
        }
        state.step++;
        state.errors = [];
        state.notice = '';
        render();
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    const synchroniserVisibilite = () => {
        const bounds = section.getBoundingClientRect();
        const visible = bounds.bottom > 0 && bounds.top < window.innerHeight;
        section.classList.toggle('builder-in-view', visible);
        if (visible) section.classList.add('builder-animated');
        document.body.classList.toggle('builder-active', visible);
    };

    window.addEventListener('scroll', synchroniserVisibilite, { passive: true });
    document.addEventListener('scroll', synchroniserVisibilite, { passive: true });
    window.addEventListener('resize', synchroniserVisibilite);
    synchroniserVisibilite();

    render();
})();
