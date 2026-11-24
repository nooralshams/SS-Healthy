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
<?php
// Données d'affichage (lecture seule) : date du jour en français et parts par objectif.
$jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
$mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$date_du_jour = $jours[(int) date('w')] . ' ' . (int) date('j') . ' ' . $mois[(int) date('n') - 1] . ' ' . date('Y');
$max_commandes_objectif = max(array_merge([1], array_map(fn($l) => (int) $l['nombre'], $repartition_commandes)));
?>

<section class="section espace-client">
    <div class="conteneur">
        <div class="ad-banniere apparition" style="--i:0">
            <span class="ec-bulle ec-bulle-1" aria-hidden="true"></span>
            <span class="ec-bulle ec-bulle-2" aria-hidden="true"></span>
            <div class="ad-banniere-texte">
                <p class="ec-surtitre">Espace administrateur</p>
                <h1>Bonjour <?= e($_SESSION['prenom'] ?? 'Admin') ?> !</h1>
                <p>Voici l'essentiel de votre activité aujourd'hui.</p>
                <span class="ad-date"><?= e(ucfirst($date_du_jour)) ?></span>
            </div>
            <div class="ad-banniere-actions">
                <a href="commandes.php" class="btn btn-mangue">Traiter les commandes</a>
                <a href="../index.php" class="btn btn-contour">Voir le site</a>
            </div>
        </div>

        <div class="ad-stats">
            <a href="commandes.php" class="ad-stat apparition<?= $commandes_attente > 0 ? ' ad-alerte' : '' ?>" style="--i:1">
                <?php if ($commandes_attente > 0): ?><span class="ad-pastille-alerte" aria-hidden="true"></span><?php endif; ?>
                <span class="ad-stat-icone ec-teinte-or"><?= icone('recu') ?></span>
                <span class="ad-stat-valeur" data-compteur="<?= $commandes_attente ?>"><?= $commandes_attente ?></span>
                <span class="ad-stat-label">Commandes à traiter</span>
            </a>
            <a href="clients.php" class="ad-stat apparition" style="--i:2; --teinte:#E6EEF8">
                <span class="ad-stat-icone ec-teinte-bleu"><?= icone('utilisateurs') ?></span>
                <span class="ad-stat-valeur" data-compteur="<?= $nb_clients ?>"><?= $nb_clients ?></span>
                <span class="ad-stat-label">Clients</span>
            </a>
            <a href="plats.php" class="ad-stat apparition" style="--i:3; --teinte:#EAF6EF">
                <span class="ad-stat-icone ec-teinte-vert"><?= icone('bol') ?></span>
                <span class="ad-stat-valeur" data-compteur="<?= $nb_plats ?>"><?= $nb_plats ?></span>
                <span class="ad-stat-label">Plats disponibles</span>
            </a>
            <a href="abonnements.php" class="ad-stat apparition" style="--i:4; --teinte:#F3EAF0">
                <span class="ad-stat-icone ec-teinte-prune"><?= icone('eclair') ?></span>
                <span class="ad-stat-valeur" data-compteur="<?= $abonnements_actifs ?>"><?= $abonnements_actifs ?></span>
                <span class="ad-stat-label">Abonnements actifs</span>
            </a>
        </div>

        <div class="ad-actions apparition" style="--i:5">
            <a href="plats.php">Gérer les plats <span aria-hidden="true">→</span></a>
            <a href="commandes.php">Commandes en attente <strong><?= $commandes_attente ?></strong></a>
            <a href="abonnements.php">Abonnements à valider <strong><?= $abonnements_attente ?></strong></a>
            <a href="clients.php">Consulter les clients <span aria-hidden="true">→</span></a>
        </div>

        <div class="ad-grille">
            <div class="ad-carte apparition" style="--i:6">
                <div class="ad-carte-entete">
                    <div>
                        <h2>Répartition des commandes par objectif</h2>
                        <p>Nombre de commandes enregistrées pour chaque profil nutritionnel.</p>
                    </div>
                </div>
                <?php if (empty($repartition_commandes)): ?>
                    <div class="ad-vide">Aucune commande pour le moment.</div>
                <?php else: ?>
                    <ul class="ad-barres">
                        <?php foreach ($repartition_commandes as $n => $ligne): ?>
                            <li>
                                <div class="ad-barre-ligne">
                                    <span><?= e(libelle_objectif($ligne['objectif'], $OBJECTIFS)) ?></span>
                                    <strong><?= (int) $ligne['nombre'] ?></strong>
                                </div>
                                <div class="ad-barre" aria-hidden="true">
                                    <span style="--part: <?= round((int) $ligne['nombre'] / $max_commandes_objectif * 100) ?>%; --i: <?= (int) $n ?>"></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="ad-carte apparition" style="--i:7">
                <div class="ad-carte-entete">
                    <div>
                        <h2>Commandes récentes</h2>
                        <p>Les cinq dernières commandes reçues.</p>
                    </div>
                    <a href="commandes.php" class="btn btn-contour btn-petit">Toutes</a>
                </div>
                <?php if (empty($dernieres_commandes)): ?>
                    <div class="ad-vide">Aucune commande pour le moment.</div>
                <?php else: ?>
                    <div class="ad-commandes">
                        <?php foreach ($dernieres_commandes as $commande): ?>
                            <div class="ad-commande">
                                <span class="ad-commande-num">n° <?= (int) $commande['id'] ?></span>
                                <span class="ad-commande-client">
                                    <strong><?= e($commande['prenom'] . ' ' . $commande['nom']) ?></strong>
                                    <span><?= e(date('d/m/Y H:i', strtotime($commande['date_commande']))) ?></span>
                                </span>
                                <span class="prix"><?= prix_fcfa($commande['total']) ?></span>
                                <span class="badge badge-statut-<?= e($commande['statut']) ?>"><?= e(libelle_statut_commande($commande['statut'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<script src="../js/compteurs.js?v=<?= filemtime(__DIR__ . '/../js/compteurs.js') ?>" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
