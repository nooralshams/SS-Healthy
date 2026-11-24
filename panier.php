<?php
// =====================================================
// ZIG HEALTHY - Gestion du panier
// =====================================================
// Cette page gère les opérations du panier : ajout, modification,
// suppression, vidage et affichage du résumé du total.
$titre_page = 'Mon panier';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $plat_id = filter_input(INPUT_POST, 'plat_id', FILTER_VALIDATE_INT);
    $ligne_id = isset($_POST['ligne_id']) ? (string) $_POST['ligne_id'] : '';

    if ($action === 'vider') {
        $_SESSION['panier'] = [];
        $_SESSION['message_panier'] = 'Votre panier a été vidé.';
    } elseif ($action === 'supprimer' && $ligne_id !== '' && array_key_exists($ligne_id, $_SESSION['panier'])) {
        unset($_SESSION['panier'][$ligne_id]);
        $_SESSION['message_panier'] = 'Le plat a été retiré du panier.';
    } elseif ($action === 'supprimer' && $plat_id) {
        foreach ($_SESSION['panier'] as $cle => $article) {
            if ((int) ($article['plat_id'] ?? 0) === $plat_id) {
                unset($_SESSION['panier'][$cle]);
            }
        }
        $_SESSION['message_panier'] = 'Le plat a été retiré du panier.';
    } elseif ($action === 'modifier' && ($ligne_id !== '' || $plat_id)) {
        $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
        $quantite = $quantite !== false && $quantite !== null ? $quantite : 1;

        if ($ligne_id !== '' && array_key_exists($ligne_id, $_SESSION['panier'])) {
            if ($quantite < 1) {
                unset($_SESSION['panier'][$ligne_id]);
            } else {
                $_SESSION['panier'][$ligne_id]['quantite'] = min(20, $quantite);
            }
        } else {
            foreach ($_SESSION['panier'] as $cle => &$article) {
                if ((int) ($article['plat_id'] ?? 0) === $plat_id) {
                    if ($quantite < 1) {
                        unset($_SESSION['panier'][$cle]);
                    } else {
                        $article['quantite'] = min(20, $quantite);
                    }
                    break;
                }
            }
            unset($article);
        }
        $_SESSION['message_panier'] = 'La quantité a été mise à jour.';
    }

    header('Location: panier.php');
    exit;
}

$articles = [];
$total = 0;

foreach ($_SESSION['panier'] as $ligne_id => $article) {
    $quantite = filter_var($article['quantite'] ?? null, FILTER_VALIDATE_INT);

    if (!$quantite || $quantite < 1) {
        continue;
    }

    if (($article['type'] ?? '') === 'bowl_personnalise') {
        $plat = [
            'id' => null,
            'nom' => $article['nom'] ?? 'Bowl personnalisé',
            'prix' => max(0, (int) ($article['prix'] ?? 0)),
            'image' => null,
            'disponible' => 1,
        ];
        $description = is_array($article['description'] ?? null) ? $article['description'] : [];
    } else {
        $plat_id = filter_var($article['plat_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$plat_id) {
            continue;
        }
        $stmt = $pdo->prepare("SELECT id, nom, prix, image, disponible
                               FROM plats
                               WHERE id = ?");
        $stmt->execute([$plat_id]);
        $plat = $stmt->fetch();
        $description = [];
    }

    if (!$plat) {
        continue;
    }

    $sous_total = (int) $plat['prix'] * $quantite;
    $articles[] = [
        'plat' => $plat,
        'ligne_id' => (string) $ligne_id,
        'type' => ($article['type'] ?? '') === 'bowl_personnalise' ? 'bowl_personnalise' : 'plat',
        'description' => $description,
        'quantite' => $quantite,
        'sous_total' => $sous_total
    ];
    $total += $sous_total;
}

require_once 'includes/header.php';
?>

<section class="section<?= empty($articles) ? ' cart-empty-page' : '' ?>">
    <div class="conteneur">
        <?php if (empty($articles)): ?>
            <div class="cart-empty">
                <?php if (!empty($_SESSION['message_panier'])): ?>
                    <div class="alerte alerte-succes" role="status"><?= e($_SESSION['message_panier']) ?></div>
                    <?php unset($_SESSION['message_panier']); ?>
                <?php endif; ?>
                <div class="cart-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M5 8h14l1 13H4L5 8Z"></path>
                        <path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
                        <path d="M9 12a3 3 0 0 0 6 0"></path>
                    </svg>
                </div>
                <h1 class="cart-empty-title">C’est l’heure de se faire du bien !</h1>
                <p class="cart-empty-text">Ajoutez des plats équilibrés à votre panier pour commencer votre commande.</p>
                <a href="menu.php#choix-menu" class="cart-empty-btn">Découvrir le menu</a>
            </div>
        <?php else: ?>
            <h1 class="section-titre">Mon panier</h1>
            <p class="section-sous-titre">Vérifiez votre sélection avant de passer commande.</p>
            <?php if (!empty($_SESSION['message_panier'])): ?>
                <div class="alerte alerte-succes">
                    <?= e($_SESSION['message_panier']) ?>
                </div>
                <?php unset($_SESSION['message_panier']); ?>
            <?php endif; ?>
            <div class="panier-layout">
                <div>
                    <table class="tableau panier-tableau">
                        <thead>
                            <tr>
                                <th>Plat</th>
                                <th>Prix</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td class="panier-cellule-plat">
                                        <strong><?= e($article['plat']['nom']) ?></strong>
                                        <?php if ($article['type'] === 'bowl_personnalise'): ?>
                                            <ul class="panier-bowl-details">
                                                <?php foreach ($article['description'] as $libelle => $valeur): ?>
                                                    <?php if ($valeur !== [] && $valeur !== ''): ?>
                                                        <li><strong><?= e($libelle) ?> :</strong> <?= e(is_array($valeur) ? implode(', ', $valeur) : $valeur) ?></li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <?php if (!(int) $article['plat']['disponible']): ?>
                                            <small class="plat-indisponible">Actuellement indisponible</small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Prix"><?= prix_fcfa($article['plat']['prix']) ?></td>
                                    <td data-label="Quantité">
                                        <form method="post" class="form-quantite">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="modifier">
                                            <input type="hidden" name="ligne_id" value="<?= e($article['ligne_id']) ?>">
                                            <?php if ($article['plat']['id']): ?><input type="hidden" name="plat_id" value="<?= (int) $article['plat']['id'] ?>"><?php endif; ?>
                                            <input type="number" name="quantite" min="0" max="20" value="<?= (int) $article['quantite'] ?>" aria-label="Quantité de <?= e($article['plat']['nom']) ?>">
                                            <button type="submit" class="btn btn-contour btn-petit">Mettre à jour</button>
                                        </form>
                                    </td>
                                    <td class="prix" data-label="Sous-total"><?= prix_fcfa($article['sous_total']) ?></td>
                                    <td class="panier-cellule-action">
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="ligne_id" value="<?= e($article['ligne_id']) ?>">
                                            <?php if ($article['plat']['id']): ?><input type="hidden" name="plat_id" value="<?= (int) $article['plat']['id'] ?>"><?php endif; ?>
                                            <button type="submit" class="lien-supprimer">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <form method="post" class="form-vider-panier">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="vider">
                        <button type="submit" class="btn btn-contour">Vider le panier</button>
                    </form>
                </div>

                <aside class="resume-panier">
                    <h2>Résumé</h2>
                    <p><span>Total</span> <strong class="prix"><?= prix_fcfa($total) ?></strong></p>
                    <a href="commande.php" class="btn btn-mangue">Passer la commande</a>
                    <a href="menu.php" class="btn btn-contour">Continuer mes achats</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
