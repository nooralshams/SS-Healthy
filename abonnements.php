<?php
// =====================================================
// ZIG HEALTHY - Page d'abonnements
// =====================================================
// Cette page affiche les formules d'abonnement du restaurant.
// Les formules proviennent de la table formules_abonnement (base de données).
// Un utilisateur connecté peut souscrire : la souscription est enregistrée
// dans la table abonnements avec le statut "en_attente" (suivi par l'admin).
$titre_page = 'Abonnements';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$message = '';
$erreur = '';

// -----------------------------------------------------
// Traitement de la souscription à une formule
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'souscrire') {
    if (!est_connecte()) {
        // On mémorise la page pour y revenir après la connexion
        $_SESSION['retour_apres_connexion'] = 'abonnements.php';
        header('Location: connexion.php');
        exit;
    }

    $formule_id = filter_input(INPUT_POST, 'formule_id', FILTER_VALIDATE_INT);
    $date_debut = trim($_POST['date_debut'] ?? '');
    $adresse_livraison = trim($_POST['adresse_livraison'] ?? '');

    // Vérification de la formule demandée
    $stmt = $pdo->prepare('SELECT id, nom, prix, duree_jours FROM formules_abonnement WHERE id = ? AND actif = 1');
    $stmt->execute([$formule_id]);
    $formule = $stmt->fetch();

    // Vérification de la date de début (format AAAA-MM-JJ, pas dans le passé)
    $date_obj = DateTime::createFromFormat('Y-m-d', $date_debut);
    $date_valide = $date_obj && $date_obj->format('Y-m-d') === $date_debut && $date_obj->format('Y-m-d') >= date('Y-m-d');

    if (!$formule) {
        $erreur = 'La formule choisie est introuvable ou n\'est plus disponible.';
    } elseif (!$date_valide) {
        $erreur = 'Veuillez choisir une date de début valide (aujourd\'hui ou plus tard).';
    } else {
        // La date de fin est calculée à partir de la durée de la formule
        $date_fin = clone $date_obj;
        $date_fin->modify('+' . ((int) $formule['duree_jours'] - 1) . ' days');

        $stmt = $pdo->prepare('INSERT INTO abonnements (utilisateur_id, formule_id, date_debut, date_fin, adresse_livraison, prix)
                               VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['utilisateur_id'],
            $formule['id'],
            $date_debut,
            $date_fin->format('Y-m-d'),
            $adresse_livraison !== '' ? $adresse_livraison : null,
            $formule['prix']
        ]);

        $message = 'Votre demande d\'abonnement à la formule « ' . $formule['nom'] . ' » a bien été enregistrée. '
                 . 'Vous pouvez suivre son statut dans votre espace « Mon compte ».';
    }
}

// Récupération des formules actives depuis la base de données
$formules = $pdo->query('SELECT id, nom, description, nb_repas_jour, prix, duree_jours, icone
                         FROM formules_abonnement
                         WHERE actif = 1
                         ORDER BY prix ASC')->fetchAll();

require_once 'includes/header.php';
?>

<section class="section concept-page">
    <div class="conteneur">
        <article class="abonnement-page" id="abonnements">
            <div class="abonnement-intro-block">
                <span class="abonnement-badge">PROGRAMMES</span>
                <h2>Abonnements</h2>
                <p>Plus besoin de réfléchir à vos repas. Choisissez votre formule et on s'occupe du reste.</p>
                <div class="abonnement-delivery-pill">
                    <span class="abonnement-delivery-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M2.5 8.5h10.5v7H2.5z"/>
                            <path d="M13 10.5h3.5l2.5 2.8v3.2H13z"/>
                            <circle cx="7.2" cy="17.5" r="1.7"/>
                            <circle cx="17.8" cy="17.5" r="1.7"/>
                            <path d="M5 8.5V6.2h8v2.3"/>
                        </svg>
                    </span>
                    Les frais de livraison sont partagés : le client règle 50%, et nous prenons en charge les 50% restants.
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alerte alerte-succes"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
            <?php endif; ?>

            <div class="abonnement-plans">
                <h3>Formules 7 jours</h3>

                <div class="abonnement-cards">
                    <?php foreach ($formules as $formule): ?>
                        <div class="abonnement-card abonnement-card-muted">
                            <div class="abonnement-icone-wrap">
                                <span><?= e($formule['icone']) ?></span>
                            </div>
                            <div class="abonnement-card-body">
                                <h4><?= e($formule['nom']) ?></h4>
                                <ul class="abonnement-avantages">
                                    <li><span class="check">✓</span> <?= (int) $formule['nb_repas_jour'] ?> repas par jour</li>
                                    <?php if ($formule['description']): ?>
                                        <li><span class="check">✓</span> <?= e($formule['description']) ?></li>
                                    <?php endif; ?>
                                    <li class="delivery"><span class="delivery-ico">⏱</span> 50% à la charge du client</li>
                                </ul>
                                <div class="abonnement-prix">
                                    <span class="prix-montant"><?= number_format((float) $formule['prix'], 0, ',', ' ') ?></span>
                                    <span class="prix-devise">FCFA</span>
                                </div>
                                <div class="abonnement-fcfa">par <?= (int) $formule['duree_jours'] ?> jours</div>

                                <?php if (est_connecte()): ?>
                                    <form method="post" class="abonnement-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="souscrire">
                                        <input type="hidden" name="formule_id" value="<?= (int) $formule['id'] ?>">
                                        <div class="champ">
                                            <label for="date_debut_<?= (int) $formule['id'] ?>">Date de début</label>
                                            <input id="date_debut_<?= (int) $formule['id'] ?>" name="date_debut" type="date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="champ">
                                            <label for="adresse_<?= (int) $formule['id'] ?>">Adresse de livraison (facultatif)</label>
                                            <input id="adresse_<?= (int) $formule['id'] ?>" name="adresse_livraison" type="text" maxlength="255" placeholder="Quartier, rue, repère…">
                                        </div>
                                        <button type="submit" class="abonnement-btn">Souscrire <span>›</span></button>
                                    </form>
                                <?php else: ?>
                                    <a href="connexion.php" class="abonnement-btn">Commander <span>›</span></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
