<?php
// =====================================================
// ZIG HEALTHY - Admin : gestion des commandes
// =====================================================
// Permet à l'administrateur de consulter toutes les commandes
// et de faire évoluer leur statut (suivi de préparation à livraison).
$titre_page = 'Gestion des commandes';
require_once __DIR__ . '/includes/header.php';

$TRANSITIONS = transitions_statuts_commande();

// Mise à jour du statut d'une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'statut') {
    $commande_id = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
    $statut = $_POST['statut'] ?? '';

    if ($commande_id) {
        $stmt = $pdo->prepare('SELECT statut FROM commandes WHERE id = ?');
        $stmt->execute([$commande_id]);
        $statutActuel = $stmt->fetchColumn();

        if ($statutActuel === false) {
            $_SESSION['erreur_admin'] = 'Commande introuvable.';
        } elseif ($statut === $statutActuel) {
            $_SESSION['erreur_admin'] = 'La commande possède déjà ce statut.';
        } elseif (transition_statut_autorisee($statutActuel, $statut, $TRANSITIONS)) {
            $stmt = $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ? AND statut = ?');
            $stmt->execute([$statut, $commande_id, $statutActuel]);
            if ($stmt->rowCount() === 1) {
                $_SESSION['message_admin'] = 'Le statut de la commande numéro ' . $commande_id . ' a été mis à jour.';
            } else {
                $_SESSION['erreur_admin'] = 'Le statut a changé entre-temps. Rechargez la page et réessayez.';
            }
        } else {
            $_SESSION['erreur_admin'] = 'Cette transition de statut de commande n’est pas autorisée.';
        }
    } else {
        $_SESSION['erreur_admin'] = 'Commande invalide.';
    }

    header('Location: commandes.php');
    exit;
}

$commandes = $pdo->query(
    'SELECT c.id, c.date_commande, c.creneau, c.mode, c.mode_paiement, c.statut, c.total,
            u.prenom, u.nom, u.telephone
     FROM commandes c
     JOIN utilisateurs u ON u.id = c.utilisateur_id
     ORDER BY c.date_commande DESC'
)->fetchAll();

$details_commandes = [];
if ($commandes) {
    $ids = array_map(static fn ($commande) => (int) $commande['id'], $commandes);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT d.commande_id, p.nom, d.quantite, d.prix_unitaire
         FROM details_commande d JOIN plats p ON p.id = d.plat_id
         WHERE d.commande_id IN ($placeholders)"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $detail) {
        $details_commandes[(int) $detail['commande_id']][] = [
            'nom' => $detail['nom'],
            'quantite' => (int) $detail['quantite'],
            'composition' => '',
        ];
    }

    $stmt = $pdo->prepare(
        "SELECT commande_id, nom, composition, quantite, prix_unitaire
         FROM details_commande_bowls WHERE commande_id IN ($placeholders)"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $detail) {
        $composition = json_decode($detail['composition'], true);
        $details_commandes[(int) $detail['commande_id']][] = [
            'nom' => $detail['nom'],
            'quantite' => (int) $detail['quantite'],
            'composition' => is_array($composition) ? $composition : [],
        ];
    }
}
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Commandes</h1>
        <p class="section-sous-titre">Suivi de toutes les commandes des clients.</p>

        <?php if (!empty($_SESSION['message_admin'])): ?>
            <div class="alerte alerte-succes"><?= e($_SESSION['message_admin']) ?></div>
            <?php unset($_SESSION['message_admin']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['erreur_admin'])): ?>
            <div class="alerte alerte-erreur"><?= e($_SESSION['erreur_admin']) ?></div>
            <?php unset($_SESSION['erreur_admin']); ?>
        <?php endif; ?>

        <?php if (empty($commandes)): ?>
            <div class="carte-formulaire">
                <h2>Aucune commande</h2>
                <p>Les commandes des clients apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Commande</th><th>Client</th><th>Date</th><th>Créneau</th>
                        <th>Mode</th><th>Règlement</th><th>Articles</th><th>Total</th><th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td>Numéro <?= (int) $commande['id'] ?></td>
                            <td>
                                <strong><?= e($commande['prenom'] . ' ' . $commande['nom']) ?></strong><br>
                                <small><?= e($commande['telephone'] ?: '') ?></small>
                            </td>
                            <td><?= e(date('d/m/Y H:i', strtotime($commande['date_commande']))) ?></td>
                            <td><?= e($commande['creneau']) ?></td>
                            <td><?= e(libelle_mode_commande($commande['mode'])) ?></td>
                            <td>
                                <?= e(['especes' => 'Espèces', 'wave' => 'Wave', 'orange_money' => 'Orange Money'][$commande['mode_paiement']] ?? 'Espèces') ?>
                                <?php if ($commande['statut'] === 'en_attente' && in_array($commande['mode_paiement'], ['wave', 'orange_money'], true)): ?>
                                    <small class="paiement-verification-requise">À vérifier manuellement</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <details>
                                    <summary><?= count($details_commandes[(int) $commande['id']] ?? []) ?> article(s)</summary>
                                    <ul>
                                        <?php foreach ($details_commandes[(int) $commande['id']] ?? [] as $detail): ?>
                                            <li><strong><?= e($detail['nom']) ?></strong> × <?= (int) $detail['quantite'] ?>
                                                <?php if ($detail['composition']): ?>
                                                    <small><?= e(implode(' · ', array_map(
                                                        static fn ($libelle, $valeur) => $libelle . ' : ' . (is_array($valeur) ? implode(', ', $valeur) : $valeur),
                                                        array_keys($detail['composition']),
                                                        array_values($detail['composition'])
                                                    ))) ?></small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            </td>
                            <td class="prix"><?= prix_fcfa($commande['total']) ?></td>
                            <td>
                                <form method="post" class="form-statut">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="statut">
                                    <input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                                    <select name="statut" onchange="this.form.submit()">
                                        <?php foreach (array_merge([$commande['statut']], $TRANSITIONS[$commande['statut']] ?? []) as $statut): ?>
                                            <option value="<?= e($statut) ?>" <?= $commande['statut'] === $statut ? 'selected' : '' ?>>
                                                <?= e(libelle_statut_commande($statut)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
