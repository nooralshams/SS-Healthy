<?php
// =====================================================
// ZIG HEALTHY - Admin : liste des clients
// =====================================================
// Affiche tous les clients inscrits avec le nombre de leurs
// commandes et d'abonnements, pour un suivi rapide de la clientèle.
// Elle liste aussi les administrateurs, pour débloquer un administrateur
// bloqué après cinq échecs de connexion.
$titre_page = 'Clients';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/connexion_functions.php';

// Déblocage d'un administrateur par un autre administrateur.
// Seul le déblocage est proposé : un administrateur ne peut pas en bloquer un autre.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'debloquer_admin') {
    $admin_id = filter_input(INPUT_POST, 'admin_id', FILTER_VALIDATE_INT);
    if ($admin_id && debloquer_compte($pdo, $admin_id, 'admin')) {
        $_SESSION['message_admin'] = 'Le compte administrateur a été débloqué.';
    }
    header('Location: clients.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'blocage') {
    $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
    $bloque = ($_POST['bloque'] ?? '0') === '1' ? 1 : 0;

    if ($client_id) {
        // Le compteur d'échecs de connexion est remis à zéro avec le blocage ou le déblocage :
        // sans cela, un compte débloqué serait rebloqué dès le premier mot de passe erroné.
        $pdo->prepare('UPDATE utilisateurs SET bloque = ?, tentatives_connexion = 0 WHERE id = ? AND role = ?')
            ->execute([$bloque, $client_id, 'client']);
        $_SESSION['message_admin'] = $bloque === 1
            ? 'Le compte client a été bloqué.'
            : 'Le compte client a été débloqué.';
        header('Location: clients.php');
        exit;
    }
}

$clients = $pdo->query(
    "SELECT u.id, u.prenom, u.nom, u.email, u.telephone, u.objectif, u.bloque, u.date_inscription,
            (SELECT COUNT(*) FROM commandes c WHERE c.utilisateur_id = u.id)   AS nb_commandes,
            (SELECT COUNT(*) FROM abonnements a WHERE a.utilisateur_id = u.id) AS nb_abonnements,
            (SELECT m.imc FROM mesures_imc m WHERE m.utilisateur_id = u.id ORDER BY m.date_mesure DESC LIMIT 1)               AS dernier_imc,
            (SELECT m.interpretation FROM mesures_imc m WHERE m.utilisateur_id = u.id ORDER BY m.date_mesure DESC LIMIT 1)    AS interpretation_imc,
            (SELECT m.date_mesure FROM mesures_imc m WHERE m.utilisateur_id = u.id ORDER BY m.date_mesure DESC LIMIT 1)       AS date_mesure_imc
     FROM utilisateurs u
     WHERE u.role = 'client'
     ORDER BY u.date_inscription DESC"
)->fetchAll();

$administrateurs = $pdo->query(
    "SELECT id, prenom, nom, email, bloque, tentatives_connexion
     FROM utilisateurs
     WHERE role = 'admin'
     ORDER BY nom, prenom"
)->fetchAll();
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Clients</h1>
        <p class="section-sous-titre">Tous les clients inscrits. L'IMC est un repère informatif, distinct de leur objectif choisi.</p>

        <?php if (!empty($_SESSION['message_admin'])): ?>
            <div class="alerte alerte-succes"><?= e($_SESSION['message_admin']) ?></div>
            <?php unset($_SESSION['message_admin']); ?>
        <?php endif; ?>

        <?php if (empty($clients)): ?>
            <div class="carte-formulaire">
                <h2>Aucun client</h2>
                <p>Les clients inscrits apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Client</th><th>Contact</th><th>Objectif</th><th>Dernier IMC</th>
                        <th>Inscrit le</th><th>Commandes</th><th>Abonnements</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><strong><?= e($client['prenom'] . ' ' . $client['nom']) ?></strong></td>
                            <td>
                                <?= e($client['email']) ?><br>
                                <small><?= e($client['telephone'] ?: 'Tél. non renseigné') ?></small>
                            </td>
                            <td><?= e($OBJECTIFS[$client['objectif']] ?? $client['objectif']) ?></td>
                            <td>
                                <?php if ($client['dernier_imc'] !== null): ?>
                                    <strong><?= e(number_format((float) $client['dernier_imc'], 1, ',', '')) ?></strong>
                                    — <?= e($client['interpretation_imc']) ?><br>
                                    <small>le <?= e(date('d/m/Y', strtotime($client['date_mesure_imc']))) ?></small>
                                <?php else: ?>
                                    <small>Non mesuré</small>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('d/m/Y', strtotime($client['date_inscription']))) ?></td>
                            <td><?= (int) $client['nb_commandes'] ?></td>
                            <td><?= (int) $client['nb_abonnements'] ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="blocage">
                                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                                    <input type="hidden" name="bloque" value="<?= (int) $client['bloque'] ? '0' : '1' ?>">
                                    <button type="submit" class="btn <?= $client['bloque'] ? 'btn-rouge' : 'btn-vert' ?>" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir <?= $client['bloque'] ? 'débloquer' : 'bloquer' ?> ce client ?')">
                                        <?= $client['bloque'] ? 'Débloquer' : 'Bloquer' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2 class="section-titre" style="margin-top:48px;">Administrateurs</h2>
        <p class="section-sous-titre">Un administrateur est bloqué après cinq mots de passe erronés. Un autre administrateur peut le débloquer ici.</p>
        <table class="tableau">
            <thead>
                <tr><th>Administrateur</th><th>E-mail</th><th>État</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($administrateurs as $admin): ?>
                    <tr>
                        <td><strong><?= e($admin['prenom'] . ' ' . $admin['nom']) ?></strong><?= (int) $admin['id'] === (int) $_SESSION['utilisateur_id'] ? ' <small>(vous)</small>' : '' ?></td>
                        <td><?= e($admin['email']) ?></td>
                        <td>
                            <?php if ((int) $admin['bloque'] === 1): ?>
                                <strong>Bloqué</strong>
                            <?php else: ?>
                                Actif<?= (int) $admin['tentatives_connexion'] > 0 ? ' <small>(' . (int) $admin['tentatives_connexion'] . ' échec(s) récent(s))</small>' : '' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $admin['bloque'] === 1): ?>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="debloquer_admin">
                                    <input type="hidden" name="admin_id" value="<?= (int) $admin['id'] ?>">
                                    <button type="submit" class="btn btn-rouge"
                                            onclick="return confirm('Débloquer ce compte administrateur ?')">Débloquer</button>
                                </form>
                            <?php else: ?>
                                <small>—</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:12px;"><small>Si le seul administrateur est bloqué, personne ne peut se connecter pour le débloquer : utilisez sur le serveur la commande <code>php outils/debloquer_admin.php adresse@email</code>.</small></p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>