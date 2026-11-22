<?php
// =====================================================
// ZIG HEALTHY - Demande de réinitialisation du mot de passe
// =====================================================
$titre_page = 'Mot de passe oublié';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';
require_once 'includes/reinitialisation.php';

if (est_connecte()) {
    header('Location: compte.php');
    exit;
}

$email = '';
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Veuillez saisir une adresse e-mail valide.';
    } else {
        $stmt = $pdo->prepare('SELECT id, email FROM utilisateurs WHERE email = ?');
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur) {
            $jeton = creer_jeton_reinitialisation($pdo, (int) $utilisateur['id']);
            if (!envoyer_lien_reinitialisation($utilisateur['email'], $jeton)) {
                $stmt = $pdo->prepare('DELETE FROM jetons_reinitialisation WHERE utilisateur_id = ?');
                $stmt->execute([(int) $utilisateur['id']]);
            }
        }

        $message = 'Si cette adresse est associée à un compte, un lien de réinitialisation sera envoyé. Vérifiez aussi vos courriers indésirables. Si vous ne le recevez pas, contactez-nous.';
        $email = '';
    }
}

require_once 'includes/header.php';
?>

<section class="section connexion-page">
    <div class="connexion-card">
        <div class="connexion-header">
            <h1>Mot de passe oublié ?</h1>
            <p>Indiquez l’adresse e-mail associée à votre compte.</p>
        </div>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alerte alerte-info"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="post" class="connexion-form">
            <?= csrf_field() ?>
            <div class="champ connexion-champ">
                <label for="email">VOTRE EMAIL</label>
                <div class="champ-input">
                    <span class="champ-icone">✉</span>
                    <input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" placeholder="nom@example.com" required>
                </div>
            </div>
            <button type="submit" class="btn btn-connexion">Envoyer le lien <span>→</span></button>
        </form>
        <p class="connexion-aide"><a href="connexion.php">Retour à la connexion</a></p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
