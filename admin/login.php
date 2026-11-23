<?php
// =====================================================
// ZIG HEALTHY - Connexion administrateur
// =====================================================
// Page d'authentification réservée à l'administrateur du restaurant.
// Contrairement à connexion.php (clients), seuls les comptes avec le
// rôle "admin" sont acceptés ici. Après connexion, l'administrateur
// est redirigé directement vers le tableau de bord.
$titre_page = 'Connexion administrateur';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/connexion_functions.php';

// Un admin déjà connecté va directement au tableau de bord
if (est_admin()) {
    header('Location: dashboard.php');
    exit;
}

// Un client déjà connecté (mais pas admin) n'a rien à faire ici
if (est_connecte()) {
    header('Location: ../index.php');
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
        // On ne cherche QUE les comptes avec le rôle admin
        $stmt = $pdo->prepare("SELECT id, prenom, nom, email, mot_de_passe, role, bloque FROM utilisateurs WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur && (int) $utilisateur['bloque'] === 1) {
            $erreur = 'Ce compte administrateur est bloqué.';
        } elseif ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            reinitialiser_tentatives_connexion($pdo, (int) $utilisateur['id']);
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['utilisateur_id'] = (int) $utilisateur['id'];
            $_SESSION['prenom'] = $utilisateur['prenom'];
            $_SESSION['nom'] = $utilisateur['nom'];
            $_SESSION['email'] = $utilisateur['email'];
            $_SESSION['role'] = $utilisateur['role'];

            header('Location: dashboard.php');
            exit;
        } else {
            if ($utilisateur) {
                $vient_d_etre_bloque = enregistrer_echec_connexion($pdo, (int) $utilisateur['id']);
                $erreur = $vient_d_etre_bloque
                    ? 'Trop de tentatives échouées : ce compte administrateur a été bloqué.'
                    : 'Identifiants administrateur incorrects.';
            } else {
                $erreur = 'Identifiants administrateur incorrects.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titre_page) ?> — <?= SITE_NOM ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
    <script src="../js/password-toggle.js" defer></script>
</head>
<body>
<main>
<section class="section connexion-page">
    <div class="connexion-card">
        <div class="connexion-header">
            <span class="admin-badge">Espace réservé</span>
            <h1>Administration</h1>
            <p>Connexion réservée à l'administrateur du restaurant.</p>
        </div>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <form method="post" class="connexion-form">
            <?= csrf_field() ?>
            <div class="champ connexion-champ">
                <label for="email">EMAIL ADMINISTRATEUR</label>
                <div class="champ-input">
                    <span class="champ-icone">✉</span>
                    <input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" placeholder="admin@example.com" required>
                </div>
            </div>

            <div class="champ connexion-champ">
                <label for="mot_de_passe">MOT DE PASSE</label>
                <div class="champ-input">
                    <span class="champ-icone">◌</span>
                    <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-connexion">Accéder au tableau de bord <span>→</span></button>
        </form>

        <p class="connexion-aide" style="margin-top:18px;"><a href="../mot_de_passe_oublie.php">Mot de passe oublié ?</a></p>
        <p style="text-align:center; margin-top:18px;"><a href="../index.php">← Retour au site</a></p>
    </div>
</section>
</main>
</body>
</html>
