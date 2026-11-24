<?php
// =====================================================
// ZIG HEALTHY - Espace personnel utilisateur
// =====================================================
// Cette page affiche les informations du compte de l'utilisateur connecté.
// Si la personne n'est pas connectée, elle est redirigée vers la page de connexion.
$titre_page = 'Mon compte';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

if (!est_connecte()) {
    $_SESSION['retour_apres_connexion'] = 'compte.php';
    header('Location: connexion.php');
    exit;
}

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profil') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $objectif = $_POST['objectif'] ?? '';

    // L'objectif ne peut être choisi qu'après l'enregistrement d'un IMC (imc.php).
    $stmt = $pdo->prepare('SELECT imc FROM mesures_imc WHERE utilisateur_id = ? ORDER BY date_mesure DESC LIMIT 1');
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $dernier_imc_profil = $stmt->fetchColumn();
    $imc_enregistre = $dernier_imc_profil !== false;

    if ($prenom === '' || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Veuillez renseigner un prénom, un nom et un e-mail valides.';
    } elseif ($imc_enregistre && !in_array($objectif, objectifs_autorises((float) $dernier_imc_profil), true)) {
        $erreur = 'Cet objectif n’est pas compatible avec votre dernier IMC. Choisissez un objectif autorisé.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
        $stmt->execute([$email, $_SESSION['utilisateur_id']]);

        if ($stmt->fetch()) {
            $erreur = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        } elseif ($imc_enregistre) {
            $stmt = $pdo->prepare(
                'UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, telephone = ?, objectif = ? WHERE id = ?'
            );
            $stmt->execute([$prenom, $nom, $email, $telephone, $objectif, $_SESSION['utilisateur_id']]);
        } else {
            // Sans IMC enregistré, l'objectif reste non défini : seules les informations personnelles changent.
            $stmt = $pdo->prepare(
                'UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?'
            );
            $stmt->execute([$prenom, $nom, $email, $telephone, $_SESSION['utilisateur_id']]);
        }

        if ($erreur === '') {
            $_SESSION['prenom'] = $prenom;
            $_SESSION['nom'] = $nom;
            $_SESSION['email'] = $email;
            $message = 'Vos informations ont été mises à jour.';
        }
    }
}

$stmt = $pdo->prepare('SELECT id, prenom, nom, email, telephone, objectif, date_inscription FROM utilisateurs WHERE id = ?');
$stmt->execute([$_SESSION['utilisateur_id']]);
$utilisateur = $stmt->fetch();

if (!$utilisateur) {
    header('Location: deconnexion.php');
    exit;
}

$stmt = $pdo->prepare('SELECT imc FROM mesures_imc WHERE utilisateur_id = ? ORDER BY date_mesure DESC LIMIT 1');
$stmt->execute([$_SESSION['utilisateur_id']]);
$dernier_imc_profil = $stmt->fetchColumn();
$imc_enregistre_profil = $dernier_imc_profil !== false;
$objectifs_profil = $imc_enregistre_profil ? objectifs_autorises((float) $dernier_imc_profil) : [];

// Historique des commandes et statut de leur préparation.
$stmt = $pdo->prepare(
    'SELECT id, date_commande, creneau, mode, mode_paiement, statut, total
     FROM commandes
     WHERE utilisateur_id = ?
     ORDER BY date_commande DESC'
);
$stmt->execute([$_SESSION['utilisateur_id']]);
$mes_commandes = $stmt->fetchAll();

// Abonnements de l'utilisateur (suivi des souscriptions)
$stmt = $pdo->prepare(
    'SELECT a.id, a.date_souscription, a.date_debut, a.date_fin, a.statut, a.prix,
            f.nom AS formule
     FROM abonnements a
     JOIN formules_abonnement f ON f.id = a.formule_id
     WHERE a.utilisateur_id = ?
     ORDER BY a.date_souscription DESC'
);
$stmt->execute([$_SESSION['utilisateur_id']]);
$mes_abonnements = $stmt->fetchAll();

// Données d'affichage de l'espace client (lecture seule).
$stmt = $pdo->prepare('SELECT imc, interpretation, date_mesure FROM mesures_imc WHERE utilisateur_id = ? ORDER BY date_mesure DESC LIMIT 1');
$stmt->execute([$_SESSION['utilisateur_id']]);
$derniere_mesure = $stmt->fetch();

$libelles_statut_commande = [
    'en_attente' => 'En attente', 'confirmee' => 'Confirmée', 'en_preparation' => 'En préparation',
    'prete' => 'Prête', 'livree' => 'Livrée', 'annulee' => 'Annulée',
];
$libelles_statut_abonnement = ['en_attente' => 'En attente', 'actif' => 'Actif', 'termine' => 'Terminé', 'annule' => 'Annulé'];
$libelles_mode = ['sur_place' => 'Sur place', 'a_emporter' => 'À emporter', 'livraison' => 'Livraison'];
$libelles_paiement = ['especes' => 'Espèces', 'wave' => 'Wave', 'orange_money' => 'Orange Money'];
$etapes_commande = ['en_attente', 'confirmee', 'en_preparation', 'prete', 'livree'];

$derniere_commande = $mes_commandes[0] ?? null;
$abonnements_actifs = count(array_filter($mes_abonnements, fn($a) => $a['statut'] === 'actif'));
$total_depense = array_sum(array_map(
    fn($c) => $c['statut'] === 'annulee' ? 0 : (int) $c['total'],
    $mes_commandes
));
$initiales = mb_strtoupper(mb_substr($utilisateur['prenom'], 0, 1) . mb_substr($utilisateur['nom'], 0, 1));

require_once 'includes/header.php';
?>

<section class="section espace-client">
    <div class="conteneur">

        <div class="ec-banniere apparition" style="--i:0">
            <span class="ec-bulle ec-bulle-1" aria-hidden="true"></span>
            <span class="ec-bulle ec-bulle-2" aria-hidden="true"></span>
            <div class="ec-avatar" aria-hidden="true"><?= e($initiales) ?></div>
            <div class="ec-banniere-texte">
                <p class="ec-surtitre">Mon espace SS’Healthy</p>
                <h1>Bonjour <?= e($utilisateur['prenom']) ?> !</h1>
                <p>Retrouvez vos commandes, vos abonnements et votre profil nutritionnel.</p>
                <div class="ec-pastilles">
                    <span class="ec-pastille">
                        Objectif : <strong><?= e(libelle_objectif($utilisateur['objectif'], $OBJECTIFS)) ?></strong>
                    </span>
                    <span class="ec-pastille">Membre depuis le <?= e(date('d/m/Y', strtotime($utilisateur['date_inscription']))) ?></span>
                </div>
            </div>
            <a href="menu.php" class="btn btn-mangue ec-banniere-action">Commander un repas</a>
        </div>

        <?php if ($message): ?>
            <div class="alerte alerte-succes"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <div class="ec-stats">
            <a href="<?= empty($mes_commandes) ? "#ma-derniere-commande" : "#mes-commandes" ?>" class="ec-stat apparition" style="--i:1">
                <span class="ec-stat-icone ec-teinte-vert"><?= icone('sac') ?></span>
                <span class="ec-stat-valeur" data-compteur="<?= count($mes_commandes) ?>"><?= count($mes_commandes) ?></span>
                <span class="ec-stat-label">Commande<?= count($mes_commandes) > 1 ? 's' : '' ?></span>
            </a>
            <a href="#mes-abonnements" class="ec-stat apparition" style="--i:2">
                <span class="ec-stat-icone ec-teinte-or"><?= icone('eclair') ?></span>
                <span class="ec-stat-valeur" data-compteur="<?= $abonnements_actifs ?>"><?= $abonnements_actifs ?></span>
                <span class="ec-stat-label">Abonnement<?= $abonnements_actifs > 1 ? 's' : '' ?> actif<?= $abonnements_actifs > 1 ? 's' : '' ?></span>
            </a>
            <a href="imc.php" class="ec-stat apparition" style="--i:3">
                <span class="ec-stat-icone ec-teinte-bleu"><?= icone('jauge') ?></span>
                <?php if ($derniere_mesure): ?>
                    <span class="ec-stat-valeur"><?= e(number_format((float) $derniere_mesure['imc'], 1, ',', '')) ?></span>
                    <span class="ec-stat-label">IMC · <?= e($derniere_mesure['interpretation']) ?></span>
                <?php else: ?>
                    <span class="ec-stat-valeur">—</span>
                    <span class="ec-stat-label">Calculer mon IMC</span>
                <?php endif; ?>
            </a>
            <div class="ec-stat apparition" style="--i:4">
                <span class="ec-stat-icone ec-teinte-prune"><?= icone('carte') ?></span>
                <span class="ec-stat-valeur"><span data-compteur="<?= $total_depense ?>"><?= number_format($total_depense, 0, ',', ' ') ?></span> <small>FCFA</small></span>
                <span class="ec-stat-label">Total de mes commandes</span>
            </div>
        </div>

        <div class="ec-grille">
            <div class="ec-carte apparition" id="ma-derniere-commande" style="--i:5">
                <h2>Ma dernière commande</h2>
                <?php if (!$derniere_commande): ?>
                    <div class="ec-vide">
                        <p>Vous n’avez pas encore passé de commande. Dès votre première commande, vous pourrez suivre ici sa préparation, étape par étape.</p>
                    </div>
                <?php else: ?>
                    <?php $statut = $derniere_commande['statut']; $position = array_search($statut, $etapes_commande, true); ?>
                    <p class="ec-commande-resume">
                        Commande n° <?= (int) $derniere_commande['id'] ?> · <?= e(date('d/m/Y à H:i', strtotime($derniere_commande['date_commande']))) ?>
                        · <strong><?= prix_fcfa($derniere_commande['total']) ?></strong>
                    </p>
                    <?php if ($statut === 'annulee'): ?>
                        <p class="ec-commande-annulee">Cette commande a été annulée.</p>
                    <?php else: ?>
                        <ol class="ec-etapes" style="--progression: <?= $position === false ? 0 : $position / (count($etapes_commande) - 1) ?>">
                            <?php foreach ($etapes_commande as $i => $etape): ?>
                                <li class="<?= $position !== false && $i < $position ? 'fait' : '' ?><?= $i === $position ? ' en-cours' : '' ?>">
                                    <span class="ec-etape-point" aria-hidden="true"></span>
                                    <span class="ec-etape-nom"><?= e($libelles_statut_commande[$etape]) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    <p class="ec-commande-details">
                        <?= e($libelles_mode[$derniere_commande['mode']] ?? $derniere_commande['mode']) ?>
                        · <?= e($derniere_commande['creneau']) ?>
                        · <?= e($libelles_paiement[$derniere_commande['mode_paiement']] ?? 'Espèces') ?>
                    </p>
                <?php endif; ?>

                <div class="ec-raccourcis">
                    <a href="menu.php"><?= icone('bol') ?> Menu</a>
                    <a href="imc.php"><?= icone('jauge') ?> Mon IMC</a>
                    <a href="abonnements.php"><?= icone('eclair') ?> Formules</a>
                    <a href="contact.php"><?= icone('courrier') ?> Contact</a>
                </div>
            </div>

            <div class="ec-carte apparition" style="--i:6">
                <h2>Mes informations</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profil">
                    <div class="admin-grille-2">
                        <div class="champ">
                            <label for="prenom">Prénom *</label>
                            <input id="prenom" name="prenom" type="text" maxlength="50" value="<?= e($utilisateur['prenom']) ?>" required>
                        </div>
                        <div class="champ">
                            <label for="nom">Nom *</label>
                            <input id="nom" name="nom" type="text" maxlength="50" value="<?= e($utilisateur['nom']) ?>" required>
                        </div>
                    </div>
                    <div class="champ">
                        <label for="email">Adresse e-mail *</label>
                        <input id="email" name="email" type="email" maxlength="100" value="<?= e($utilisateur['email']) ?>" required>
                    </div>
                    <div class="admin-grille-2">
                        <div class="champ">
                            <label for="telephone">Téléphone</label>
                            <input id="telephone" name="telephone" type="text" maxlength="20" value="<?= e($utilisateur['telephone']) ?>">
                        </div>
                        <div class="champ">
                            <?php if ($imc_enregistre_profil): ?>
                                <label for="objectif">Objectif nutritionnel</label>
                                <select id="objectif" name="objectif" required>
                                    <?php if (!in_array($utilisateur['objectif'], $objectifs_profil, true)): ?>
                                        <option value="" selected disabled><?= $utilisateur['objectif'] === null ? 'Choisissez votre objectif' : 'Votre objectif actuel est incompatible avec votre IMC' ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($objectifs_profil as $cle): ?>
                                        <?php $libelle = $OBJECTIFS[$cle]; ?>
                                        <option value="<?= e($cle) ?>" <?= $utilisateur['objectif'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <span class="champ-libelle">Objectif nutritionnel</span>
                                <p class="objectif-a-definir">
                                    À définir après le calcul de votre IMC.
                                    <a href="imc.php">Calculer mon IMC</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ec-actions-formulaire">
                        <button type="submit" class="btn btn-plein">Enregistrer mes informations</button>
                    </div>
                </form>
                <form method="post" action="deconnexion.php" class="ec-deconnexion">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-contour">Se déconnecter</button>
                </form>
            </div>
        </div>

        <?php if (!empty($mes_commandes)): ?>
            <h2 class="ec-titre-section apparition" id="mes-commandes" style="--i:7">Historique de mes commandes</h2>
            <div class="ec-liste">
                <?php foreach ($mes_commandes as $n => $commande): ?>
                    <article class="ec-ligne apparition" style="--i:<?= min(8 + $n, 14) ?>">
                        <div class="ec-ligne-principal">
                            <strong>Commande n° <?= (int) $commande['id'] ?></strong>
                            <span><?= e(date('d/m/Y H:i', strtotime($commande['date_commande']))) ?></span>
                        </div>
                        <div class="ec-ligne-details">
                            <span><?= e($libelles_mode[$commande['mode']] ?? $commande['mode']) ?></span>
                            <span><?= e($commande['creneau']) ?></span>
                            <span><?= e($libelles_paiement[$commande['mode_paiement']] ?? 'Espèces') ?></span>
                        </div>
                        <span class="prix"><?= prix_fcfa($commande['total']) ?></span>
                        <span class="badge badge-statut-<?= e($commande['statut']) ?>"><?= e($libelles_statut_commande[$commande['statut']] ?? $commande['statut']) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="ec-titre-section apparition" id="mes-abonnements" style="--i:8">Mes abonnements</h2>
        <?php if (empty($mes_abonnements)): ?>
            <div class="ec-abonnement-vide apparition" style="--i:9">
                <p>Vous n'avez pas encore d'abonnement : vos repas équilibrés de la semaine, sans y penser.</p>
                <a href="abonnements.php" class="btn btn-mangue">Découvrir les formules</a>
            </div>
        <?php else: ?>
            <div class="ec-abonnements">
                <?php foreach ($mes_abonnements as $n => $abonnement): ?>
                    <article class="ec-abonnement apparition statut-<?= e($abonnement['statut']) ?>" style="--i:<?= min(9 + $n, 14) ?>">
                        <span class="badge badge-statut-<?= e($abonnement['statut']) ?>"><?= e($libelles_statut_abonnement[$abonnement['statut']] ?? $abonnement['statut']) ?></span>
                        <h3><?= e($abonnement['formule']) ?></h3>
                        <p>Du <?= e(date('d/m/Y', strtotime($abonnement['date_debut']))) ?> au <?= e(date('d/m/Y', strtotime($abonnement['date_fin']))) ?></p>
                        <span class="prix"><?= prix_fcfa($abonnement['prix']) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<script src="js/compteurs.js?v=<?= filemtime(__DIR__ . '/js/compteurs.js') ?>" defer></script>

<?php require_once 'includes/footer.php'; ?>
