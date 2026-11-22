<?php
// =====================================================
// ZIG HEALTHY - Admin : tableau de bord
// =====================================================
// Vue rapide de l'activité et accès direct aux tâches prioritaires.
$titre_page = 'Tableau de bord';
require_once __DIR__ . '/includes/header.php';

// Indicateurs prioritaires
$nb_clients      = (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'")->fetchColumn();
$nb_plats        = (int) $pdo->query('SELECT COUNT(*) FROM plats WHERE disponible = 1')->fetchColumn();
$abonnements_actifs = (int) $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif'")->fetchColumn();
$abonnements_attente = (int) $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'en_attente'")->fetchColumn();
$commandes_attente   = (int) $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'")->fetchColumn();
$repartition_commandes = $pdo->query(
    "SELECT u.objectif, COUNT(*) AS nombre
     FROM commandes c
     JOIN utilisateurs u ON u.id = c.utilisateur_id
     GROUP BY u.objectif
     ORDER BY nombre DESC"
)->fetchAll();

// Activité récente
$dernieres_commandes = $pdo->query(
    'SELECT c.id, c.date_commande, c.statut, c.total, u.prenom, u.nom
     FROM commandes c
     JOIN utilisateurs u ON u.id = c.utilisateur_id
     ORDER BY c.date_commande DESC
     LIMIT 5'
)->fetchAll();
?>

<section class="section">
    <div class="conteneur">
        <div class="admin-dashboard-entete">
            <div>
                <p class="admin-dashboard-surtitre">ESPACE ADMINISTRATEUR</p>
                <h1 class="section-titre">Bonjour <?= e($_SESSION['prenom'] ?? 'Admin') ?></h1>
                <p class="section-sous-titre">Voici l'essentiel de votre activité aujourd'hui.</p>
            </div>
            <a href="../index.php" class="btn btn-contour">Voir le site</a>
        </div>

        <div class="admin-stats">
            <a href="commandes.php" class="stat-carte stat-prioritaire <?= $commandes_attente > 0 ? 'stat-alerte' : '' ?>">
                <span class="stat-valeur"><?= $commandes_attente ?></span>
                <span class="stat-label">Commandes à traiter</span>
            </a>
            <a href="clients.php" class="stat-carte">
                <span class="stat-valeur"><?= $nb_clients ?></span>
                <span class="stat-label">Clients</span>
            </a>
            <a href="plats.php" class="stat-carte">
                <span class="stat-valeur"><?= $nb_plats ?></span>
                <span class="stat-label">Plats disponibles</span>
            </a>
            <a href="abonnements.php" class="stat-carte">
                <span class="stat-valeur"><?= $abonnements_actifs ?></span>
                <span class="stat-label">Abonnements actifs</span>
            </a>
        </div>

        <div class="admin-dashboard-actions">
            <a href="plats.php">Gérer les plats <span>→</span></a>
            <a href="commandes.php">Commandes en attente <strong><?= $commandes_attente ?></strong></a>
            <a href="abonnements.php">Abonnements à valider <strong><?= $abonnements_attente ?></strong></a>
            <a href="clients.php">Consulter les clients <span>→</span></a>
        </div>

        <div class="admin-dashboard-section-titre">
            <div>
                <h2>Répartition des commandes par objectif</h2>
                <p>Nombre de commandes enregistrées pour chaque profil nutritionnel.</p>
            </div>
        </div>
        <table class="tableau">
            <thead><tr><th>Objectif</th><th>Commandes</th></tr></thead>
            <tbody>
                <?php foreach ($repartition_commandes as $ligne): ?>
                    <tr>
                        <td><?= e($OBJECTIFS[$ligne['objectif']] ?? $ligne['objectif']) ?></td>
                        <td><?= (int) $ligne['nombre'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="admin-dashboard-section-titre">
            <div>
                <h2>Commandes récentes</h2>
                <p>Les cinq dernières commandes reçues.</p>
            </div>
            <a href="commandes.php" class="btn btn-contour">Toutes les commandes</a>
        </div>
        <?php if (empty($dernieres_commandes)): ?>
            <div class="admin-dashboard-vide">Aucune commande pour le moment.</div>
        <?php else: ?>
            <table class="tableau admin-dashboard-tableau">
                <thead>
                    <tr><th>Commande</th><th>Client</th><th>Date</th><th>Total</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($dernieres_commandes as $commande): ?>
                        <tr>
                            <td>Numéro <?= (int) $commande['id'] ?></td>
                            <td><?= e($commande['prenom'] . ' ' . $commande['nom']) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($commande['date_commande']))) ?></td>
                            <td><?= prix_fcfa($commande['total']) ?></td>
                            <td><span class="badge badge-statut-<?= e($commande['statut']) ?>"><?= e($commande['statut']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
