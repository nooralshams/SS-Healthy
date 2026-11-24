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

require_once 'includes/header.php';
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Bonjour <?= e($utilisateur['prenom']) ?></h1>
        <p class="section-sous-titre">Gérez vos informations et retrouvez votre espace SS’Healthy.</p>

        <?php if ($message): ?>
            <div class="alerte alerte-succes"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <div class="carte-formulaire">
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
                <button type="submit" class="btn btn-plein">Enregistrer mes informations</button>
            </form>
            <form method="post" action="deconnexion.php" style="margin-top:20px;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-contour">Se déconnecter</button>
            </form>
        </div>

        <h2>Mes commandes</h2>
        <?php if (empty($mes_commandes)): ?>
            <p>Vous n'avez pas encore passé de commande. <a href="menu.php">Découvrir le menu</a></p>
        <?php else: ?>
            <table class="tableau">
                <thead>
                    <tr><th>Commande</th><th>Date</th><th>Créneau</th><th>Mode</th><th>Règlement</th><th>Total</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($mes_commandes as $commande): ?>
                        <tr>
                            <td>Numéro <?= (int) $commande['id'] ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($commande['date_commande']))) ?></td>
                            <td><?= e($commande['creneau']) ?></td>
                            <td><?= e($commande['mode']) ?></td>
                            <td><?= e(['especes' => 'Espèces', 'wave' => 'Wave', 'orange_money' => 'Orange Money'][$commande['mode_paiement']] ?? 'Espèces') ?></td>
                            <td class="prix"><?= prix_fcfa($commande['total']) ?></td>
                            <td><span class="badge badge-statut-<?= e($commande['statut']) ?>"><?= e($commande['statut']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Mes abonnements</h2>
        <?php if (empty($mes_abonnements)): ?>
            <p>Vous n'avez pas encore d'abonnement. <a href="abonnements.php">Découvrir les formules</a></p>
        <?php else: ?>
            <table class="tableau">
                <thead>
                    <tr><th>Formule</th><th>Période</th><th>Prix</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($mes_abonnements as $abonnement): ?>
                        <tr>
                            <td><strong><?= e($abonnement['formule']) ?></strong></td>
                            <td>
                                <?= e(date('d/m/Y', strtotime($abonnement['date_debut']))) ?>
                                → <?= e(date('d/m/Y', strtotime($abonnement['date_fin']))) ?>
                            </td>
                            <td class="prix"><?= prix_fcfa($abonnement['prix']) ?></td>
                            <td><span class="badge badge-statut-<?= e($abonnement['statut']) ?>"><?= e($abonnement['statut']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
