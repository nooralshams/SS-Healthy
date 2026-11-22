<?php
// =====================================================
// ZIG HEALTHY - Page de connexion
// =====================================================
// Cette page permet à un utilisateur existant de se connecter au site.
// On vérifie l'email et le mot de passe, puis on crée une session.
// Après 5 échecs consécutifs, le compte est bloqué (voir includes/connexion_functions.php).
$titre_page = 'Connexion';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';
require_once 'includes/connexion_functions.php';

if (est_connecte()) {
    header('Location: compte.php');
    exit;
}

$email = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $mot_de_passe === '') {
        $erreur = 'Veuillez renseigner une adresse e-mail et un mot de passe valides.';
    } else {
        $stmt = $pdo->prepare('SELECT id, prenom, nom, email, mot_de_passe, role, bloque FROM utilisateurs WHERE email = ?');
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur && (int) $utilisateur['bloque'] === 1) {
            // Compte déjà bloqué : on ne teste pas le mot de passe et on ne compte plus d'échec.
            $erreur = 'Ce compte a été bloqué par l’administration.';
        } elseif ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            reinitialiser_tentatives_connexion($pdo, (int) $utilisateur['id']);
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['utilisateur_id'] = (int) $utilisateur['id'];
            $_SESSION['prenom'] = $utilisateur['prenom'];
            $_SESSION['nom'] = $utilisateur['nom'];
            $_SESSION['email'] = $utilisateur['email'];
            $_SESSION['role'] = $utilisateur['role'];

            header('Location: ' . (!empty($_SESSION['retour_apres_connexion']) ? $_SESSION['retour_apres_connexion'] : 'index.php'));
            unset($_SESSION['retour_apres_connexion']);
            exit;
        } else {
            // Mot de passe incorrect (ou e-mail inconnu).
            $vient_d_etre_bloque = $utilisateur
                ? enregistrer_echec_connexion($pdo, (int) $utilisateur['id'])
                : false;

            $erreur = $vient_d_etre_bloque
                ? 'Trop de tentatives échouées : ce compte a été bloqué.'
                : 'Adresse e-mail ou mot de passe incorrect.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section connexion-page">
    <div class="connexion-card">
        <div class="connexion-header">
            <h1>Bon retour !</h1>
            <p>Pas encore de compte ? <a href="inscription.php">Rejoignez-nous</a></p>
        </div>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
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

            <div class="champ connexion-champ">
                <label for="mot_de_passe">MOT DE PASSE</label>
                <div class="champ-input">
                    <span class="champ-icone">◌</span>
                    <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" placeholder="••••••••" required>
                </div>
            </div>

            <p class="connexion-aide"><a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a></p>
            <button type="submit" class="btn btn-connexion">Se connecter <span>→</span></button>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>