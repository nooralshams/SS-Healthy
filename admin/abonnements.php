<?php
// =====================================================
// ZIG HEALTHY - Admin : gestion des abonnements
// =====================================================
// Permet à l'administrateur de consulter toutes les souscriptions
// et de faire évoluer leur statut (en_attente -> actif -> termine).
$titre_page = 'Gestion des abonnements';
require_once __DIR__ . '/includes/header.php';

$STATUTS = ['en_attente', 'actif', 'termine', 'annule'];

// Mise à jour du statut d'un abonnement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'statut') {
    $abonnement_id = filter_input(INPUT_POST, 'abonnement_id', FILTER_VALIDATE_INT);
    $statut = $_POST['statut'] ?? '';

    if ($abonnement_id && in_array($statut, $STATUTS, true)) {
        $stmt = $pdo->prepare('UPDATE abonnements SET statut = ? WHERE id = ?');
        $stmt->execute([$statut, $abonnement_id]);
        $_SESSION['message_admin'] = 'Le statut de l\'abonnement #' . $abonnement_id . ' a été mis à jour.';
    }

    header('Location: abonnements.php');
    exit;
}

$abonnements = $pdo->query(
    'SELECT a.id, a.date_souscription, a.date_debut, a.date_fin, a.statut, a.prix, a.adresse_livraison,
            f.nom AS formule, f.nb_repas_jour,
            u.prenom, u.nom, u.email, u.telephone
     FROM abonnements a
     JOIN formules_abonnement f ON f.id = a.formule_id
     JOIN utilisateurs u ON u.id = a.utilisateur_id
     ORDER BY a.date_souscription DESC'
)->fetchAll();
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Abonnements</h1>
        <p class="section-sous-titre">Suivi des souscriptions aux formules 7 jours.</p>

        <?php if (!empty($_SESSION['message_admin'])): ?>
            <div class="alerte alerte-succes"><?= e($_SESSION['message_admin']) ?></div>
            <?php unset($_SESSION['message_admin']); ?>
        <?php endif; ?>

        <?php if (empty($abonnements)): ?>
            <div class="carte-formulaire">
                <h2>Aucun abonnement</h2>
                <p>Les souscriptions des clients apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <table class="tableau">
                <thead>
                    <tr>
                        <th>N°</th><th>Client</th><th>Formule</th><th>Période</th>
                        <th>Livraison</th><th>Prix</th><th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($abonnements as $abonnement): ?>
                        <tr>
                            <td>#<?= (int) $abonnement['id'] ?></td>
                            <td>
                                <strong><?= e($abonnement['prenom'] . ' ' . $abonnement['nom']) ?></strong><br>
                                <small><?= e($abonnement['email']) ?><?= $abonnement['telephone'] ? ' — ' . e($abonnement['telephone']) : '' ?></small>
                            </td>
                            <td><?= e($abonnement['formule']) ?><br><small><?= (int) $abonnement['nb_repas_jour'] ?> repas/jour</small></td>
                            <td>
                                <?= e(date('d/m/Y', strtotime($abonnement['date_debut']))) ?>
                                → <?= e(date('d/m/Y', strtotime($abonnement['date_fin']))) ?>
                            </td>
                            <td><small><?= e($abonnement['adresse_livraison'] ?: 'Non renseignée') ?></small></td>
                            <td class="prix"><?= prix_fcfa($abonnement['prix']) ?></td>
                            <td>
                                <form method="post" class="form-statut">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="statut">
                                    <input type="hidden" name="abonnement_id" value="<?= (int) $abonnement['id'] ?>">
                                    <select name="statut" onchange="this.form.submit()">
                                        <?php foreach ($STATUTS as $statut): ?>
                                            <option value="<?= e($statut) ?>" <?= $abonnement['statut'] === $statut ? 'selected' : '' ?>>
                                                <?= e($statut) ?>
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
