<?php
// =====================================================
// ZIG HEALTHY - Choix d'un nouveau mot de passe
// =====================================================
$titre_page = 'Nouveau mot de passe';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';
require_once 'includes/reinitialisation.php';

if (est_connecte()) {
    header('Location: compte.php');
    exit;
}

$jeton = $_GET['token'] ?? ($_POST['token'] ?? '');
$erreur = '';
$success = false;
$jetonValide = is_string($jeton) && jeton_reinitialisation_valide($pdo, $jeton);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (!$jetonValide) {
        $erreur = 'Ce lien de réinitialisation est invalide ou a expiré. Faites une nouvelle demande.';
    } elseif (strlen($motDePasse) < 8) {
        $erreur = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } elseif ($motDePasse !== $confirmation) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } elseif (reinitialiser_mot_de_passe($pdo, $jeton, $motDePasse)) {
        $success = true;
    } else {
        $erreur = 'Ce lien de réinitialisation est invalide ou a expiré. Faites une nouvelle demande.';
        $jetonValide = false;
    }
}

require_once 'includes/header.php';
?>

<section class="section connexion-page">
    <div class="connexion-card">
        <div class="connexion-header">
            <h1>Nouveau mot de passe</h1>
            <p>Choisissez un mot de passe sécurisé pour votre compte.</p>
        </div>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alerte alerte-succes">Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.</div>
            <p class="connexion-aide"><a href="connexion.php">Se connecter</a></p>
        <?php elseif ($jetonValide): ?>
            <form method="post" class="connexion-form">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($jeton) ?>">
                <div class="champ connexion-champ">
                    <label for="mot_de_passe">NOUVEAU MOT DE PASSE</label>
                    <div class="champ-input">
                        <span class="champ-icone">◌</span>
                        <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>
                <div class="champ connexion-champ">
                    <label for="confirmation">CONFIRMER LE MOT DE PASSE</label>
                    <div class="champ-input">
                        <span class="champ-icone">◌</span>
                        <input id="confirmation" name="confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-connexion">Modifier le mot de passe <span>→</span></button>
            </form>
        <?php else: ?>
            <p class="connexion-aide"><a href="mot_de_passe_oublie.php">Demander un nouveau lien</a></p>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
