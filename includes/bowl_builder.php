<?php
require_once __DIR__ . '/bowl_config.php';
?>
<section class="builder-section" id="composer-bowl" aria-labelledby="builder-title"
    data-bowl-config="<?= e(json_encode($BOWL_CONFIG, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>"
    data-csrf-token="<?= e(csrf_token()) ?>">
    <div class="conteneur builder-container">
        <div class="builder-card" aria-live="polite">
            <div class="builder-step-header">
                <div class="builder-step-meta">
                    <span class="builder-step-name" data-builder-step-name>TAILLE</span>
                    <span class="builder-step-count"><span data-builder-step-number>1</span> / 6</span>
                </div>
                <div class="builder-progress-track" role="progressbar" aria-label="Progression du composeur" aria-valuemin="1" aria-valuemax="6" aria-valuenow="1">
                    <span class="builder-progress-fill" data-builder-progress></span>
                </div>
            </div>

            <div class="builder-step-content" data-builder-content></div>
        </div>
    </div>

    <div class="builder-action-bar" data-builder-actions>
        <button class="builder-back-button" type="button" data-builder-back hidden>Retour</button>
        <div class="builder-action-total"><span>Total</span><strong data-builder-total><span data-builder-price-value>2 500</span><span class="prix-devise">FCFA</span></strong></div>
        <button class="builder-next-button" type="button" data-builder-next>
            <span data-builder-next-label>Continuer</span>
            <svg data-builder-next-icon viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"></path></svg>
        </button>
    </div>
</section>
