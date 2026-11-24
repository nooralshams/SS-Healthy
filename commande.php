<?php
// =====================================================
// ZIG HEALTHY - Finalisation de la commande
// =====================================================
// Cette page permet au client connecté de valider son panier :
// choix du délai, du mode de récupération et du règlement, puis
// enregistrement de la commande et de son détail en base.
$titre_page = 'Finaliser ma commande';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';
require_once 'includes/commande_functions.php';

// Il faut être connecté pour commander
if (!est_connecte()) {
    $_SESSION['retour_apres_connexion'] = 'commande.php';
    header('Location: connexion.php');
    exit;
}

// Un panier vide ne peut pas être validé
if (empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit;
}

$erreur = '';
$confirmation = null;
$creneaux = creneaux_disponibles();
$paiements_mobiles = [
    'wave' => [
        'nom' => 'Wave',
        'numero' => PAIEMENT_NUMERO,
        'beneficiaire' => PAIEMENT_BENEFICIAIRE,
        'classe' => 'wave',
    ],
    'orange_money' => [
        'nom' => 'Orange Money',
        'numero' => PAIEMENT_NUMERO,
        'beneficiaire' => PAIEMENT_BENEFICIAIRE,
        'classe' => 'orange',
    ],
];

// -----------------------------------------------------
// Reconstitution du panier depuis la base (prix à jour)
// -----------------------------------------------------
$panier_reconstruit = reconstruire_articles_panier($pdo, $_SESSION['panier']);
$articles = $panier_reconstruit['articles'];
$total = $panier_reconstruit['total'];

// -----------------------------------------------------
// Validation de la commande
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($articles)) {
    $creneau = trim($_POST['creneau'] ?? '');
    $mode = $_POST['mode'] ?? '';
    $mode_paiement = $_POST['mode_paiement'] ?? '';
    $adresse_livraison = trim($_POST['adresse_livraison'] ?? '');

    // Un plat indisponible dans le panier bloque la commande
    $plat_indisponible = false;
    foreach ($articles as $article) {
        if (!$article['est_bowl'] && !(int) $article['plat']['disponible']) {
            $plat_indisponible = true;
            break;
        }
    }

    if ($plat_indisponible) {
        $erreur = 'Un plat de votre panier n\'est plus disponible. Retirez-le avant de valider.';
    } elseif (!in_array($creneau, $creneaux, true)) {
        $erreur = 'Veuillez choisir un délai valide.';
    } elseif (!creneau_autorise($creneau)) {
        $erreur = 'La préparation nécessite au moins 45 minutes.';
    } elseif (!in_array($mode, ['sur_place', 'a_emporter', 'livraison'], true)) {
        $erreur = 'Veuillez choisir un mode de récupération.';
    } elseif (!in_array($mode_paiement, ['especes', 'wave', 'orange_money'], true)) {
        $erreur = 'Veuillez choisir un mode de paiement valide.';
    } elseif ($mode === 'livraison' && $adresse_livraison === '') {
        $erreur = 'L\'adresse de livraison est obligatoire pour une livraison.';
    } else {
        try {
            $commande_id = enregistrer_commande($pdo, [
                'utilisateur_id' => (int) $_SESSION['utilisateur_id'],
                'creneau' => $creneau,
                'mode' => $mode,
                'mode_paiement' => $mode_paiement,
                'adresse_livraison' => $mode === 'livraison' ? $adresse_livraison : null,
                'total' => $total,
            ], $articles);

            $_SESSION['panier'] = [];
            $confirmation = ['id' => $commande_id, 'creneau' => $creneau, 'total' => $total, 'mode_paiement' => $mode_paiement];
        } catch (Throwable $e) {
            error_log('Enregistrement commande SS Healthy : ' . $e->getMessage());
            $erreur = 'Une erreur est survenue lors de l\'enregistrement. Réessayez.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section">
    <div class="conteneur">
        <?php if ($confirmation): ?>
            <?php if (isset($paiements_mobiles[$confirmation['mode_paiement']])): ?>
                <?php
                $paiement = $paiements_mobiles[$confirmation['mode_paiement']];
                $reference_paiement = trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? ''))
                    . ' - commande numéro ' . (int) $confirmation['id'];
                ?>
                <section class="confirmation-paiement">
                    <div class="confirmation-paiement-entete">
                        <span class="confirmation-paiement-statut">Commande numéro <?= (int) $confirmation['id'] ?> · En attente de vérification</span>
                        <h1>Comment payer avec <?= e($paiement['nom']) ?> ?</h1>
                        <p>Effectuez le transfert depuis l’application <?= e($paiement['nom']) ?>. La commande sera confirmée après vérification par notre équipe.</p>
                    </div>

                    <div class="instructions-transfert instructions-transfert-<?= e($paiement['classe']) ?>">
                        <p class="instructions-transfert-titre">Instructions <?= e($paiement['nom']) ?></p>
                        <h2>Comment payer ?</h2>
                        <ol class="instructions-transfert-etapes">
                            <li>Ouvrez l’application <?= e($paiement['nom']) ?> sur votre téléphone.</li>
                            <li>Choisissez l’option pour envoyer de l’argent.</li>
                            <li>Vérifiez le numéro et le nom du bénéficiaire ci-dessous.</li>
                            <li>Transférez le montant exact de <strong><?= prix_fcfa($confirmation['total']) ?></strong>.</li>
                            <li>Indiquez cette référence : <strong><?= e($reference_paiement) ?></strong>.</li>
                            <li>Confirmez le transfert dans votre application.</li>
                        </ol>

                        <div class="coordonnees-transfert">
                            <span class="coordonnees-transfert-titre">Numéro <?= e($paiement['nom']) ?></span>
                            <div class="coordonnees-transfert-numero">
                                <input id="numero-paiement" type="text" value="<?= e($paiement['numero']) ?>" readonly aria-label="Numéro de paiement">
                                <button type="button" class="btn-copier-transfert" data-copy-value="<?= e($paiement['numero']) ?>" data-copy-target="numero-paiement">Copier</button>
                            </div>
                            <p>Bénéficiaire : <strong><?= e($paiement['beneficiaire']) ?></strong></p>
                            <span class="copie-transfert-statut" data-copy-status role="status" aria-live="polite"></span>
                        </div>

                        <div class="alerte-transfert">
                            <strong>Important</strong>
                            <p>La commande reste en attente jusqu’à la vérification du transfert par SS’Healthy. Ne faites pas un second transfert pour la même commande.</p>
                        </div>
                    </div>

                    <p class="confirmation-paiement-actions">
                        <a href="compte.php" class="btn btn-plein">Voir mon compte</a>
                        <a href="menu.php" class="btn btn-contour">Retour au menu</a>
                    </p>
                </section>
            <?php else: ?>
                <div class="carte-formulaire">
                    <h2>✅ Commande enregistrée</h2>
                    <p>Votre commande numéro <strong><?= (int) $confirmation['id'] ?></strong> est en attente de confirmation par SS’Healthy.</p>
                    <p><strong>Créneau souhaité :</strong> <?= e($confirmation['creneau']) ?><br>
                             <strong>Total :</strong> <?= prix_fcfa($confirmation['total']) ?><br>
                             <strong>Mode de paiement :</strong> Espèces à la remise</p>
                    <p>Notre équipe la préparera dans les meilleurs délais. Vous pouvez suivre son avancement depuis votre espace.</p>
                    <p style="margin-top:20px;">
                        <a href="compte.php" class="btn btn-plein">Voir mon compte</a>
                        <a href="menu.php" class="btn btn-contour">Retour au menu</a>
                    </p>
                </div>
            <?php endif; ?>
        <?php elseif (empty($articles)): ?>
            <div class="carte-formulaire panier-vide">
                <h2>Votre panier est vide</h2>
                <p>Ajoutez des plats avant de passer commande.</p>
                <a href="menu.php" class="btn btn-plein">Découvrir le menu</a>
            </div>
        <?php else: ?>
            <h1 class="section-titre">Finaliser ma commande</h1>
            <p class="section-sous-titre">Vérifiez votre sélection et choisissez vos options de retrait ou de livraison.</p>

            <?php if ($erreur): ?>
                <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
            <?php endif; ?>

            <div class="panier-layout">
                <div>
                    <section class="commande-recapitulatif" aria-labelledby="titre-recapitulatif">
                        <div class="commande-recap-entete">
                            <div>
                                <h2 id="titre-recapitulatif">Récapitulatif de votre commande</h2>
                                <p><?= count($articles) ?> article<?= count($articles) > 1 ? 's' : '' ?></p>
                            </div>
                        </div>

                        <div class="commande-recap-lignes">
                            <?php foreach ($articles as $article): ?>
                                <article class="commande-recap-ligne">
                                    <div class="commande-recap-description">
                                        <h3><?= e($article['plat']['nom']) ?></h3>
                                        <span class="commande-recap-quantite">Quantité : × <?= (int) $article['quantite'] ?></span>
                                        <?php if ($article['est_bowl']): ?>
                                            <small><?= e(implode(' · ', array_filter(array_map(
                                                static fn ($libelle, $valeur) => $valeur === [] ? null : $libelle . ' : ' . (is_array($valeur) ? implode(', ', $valeur) : $valeur),
                                                array_keys($article['composition']),
                                                array_values($article['composition'])
                                            )))) ?></small>
                                        <?php elseif (!(int) $article['plat']['disponible']): ?>
                                            <small class="plat-indisponible">Actuellement indisponible</small>
                                        <?php endif; ?>
                                    </div>
                                    <strong class="commande-recap-prix"><?= prix_fcfa((int) $article['plat']['prix'] * $article['quantite']) ?></strong>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="commande-recap-total">
                            <span>Total à régler</span>
                            <strong class="prix"><?= prix_fcfa($total) ?></strong>
                        </div>
                    </section>
                </div>

                <aside class="resume-panier">
                    <h2>Finalisation</h2>

                    <form method="post">
                        <?= csrf_field() ?>
                        <div class="champ">
                            <label for="creneau">Créneau souhaité *</label>
                            <select id="creneau" name="creneau" required>
                                <option value="" selected disabled>Choisissez un délai</option>
                                <?php foreach ($creneaux as $c): ?>
                                    <option value="<?= e($c) ?>"><?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="champ">
                            <label for="mode">Mode de récupération *</label>
                            <select id="mode" name="mode" required>
                                <option value="a_emporter">À emporter</option>
                                <option value="sur_place">Sur place</option>
                                <option value="livraison">Livraison</option>
                            </select>
                        </div>

                        <div class="champ">
                            <span class="mode-paiement-label">Comment réglez-vous ? *</span>
                            <div class="mode-paiement-choix" role="radiogroup" aria-label="Mode de paiement">
                                <label class="mode-paiement-option">
                                    <input type="radio" name="mode_paiement" value="especes" required checked>
                                    <span class="mode-paiement-carte">
                                        <span class="mode-paiement-icone" aria-hidden="true">▣</span>
                                        <strong>Espèces</strong>
                                        <small>À la remise</small>
                                        <span class="mode-paiement-coche" aria-hidden="true">✓</span>
                                    </span>
                                </label>
                                <label class="mode-paiement-option">
                                    <input type="radio" name="mode_paiement" value="wave" required>
                                    <span class="mode-paiement-carte">
                                        <span class="mode-paiement-icone" aria-hidden="true">◈</span>
                                        <strong>Wave</strong>
                                        <small>Transfert mobile</small>
                                        <span class="mode-paiement-coche" aria-hidden="true">✓</span>
                                    </span>
                                </label>
                                <label class="mode-paiement-option">
                                    <input type="radio" name="mode_paiement" value="orange_money" required>
                                    <span class="mode-paiement-carte">
                                        <span class="mode-paiement-icone" aria-hidden="true">▤</span>
                                        <strong>Orange Money</strong>
                                        <small>Transfert mobile</small>
                                        <span class="mode-paiement-coche" aria-hidden="true">✓</span>
                                    </span>
                                </label>
                            </div>
                            <small>Les transferts se font dans l’application mobile. La commande reste en attente jusqu’à vérification par notre équipe.</small>
                        </div>

                        <div class="champ">
                            <label for="adresse_livraison">Adresse (si livraison)</label>
                            <input id="adresse_livraison" name="adresse_livraison" type="text" maxlength="255" placeholder="Quartier, rue, repère…">
                        </div>

                        <button type="submit" class="btn btn-mangue">Valider la commande</button>
                    </form>

                    <a href="panier.php" class="btn btn-contour">Modifier mon panier</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="js/paiement-commande.js" defer></script>
<?php require_once 'includes/footer.php'; ?>
