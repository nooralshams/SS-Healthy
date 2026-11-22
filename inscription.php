<?php
// =====================================================
// ZIG HEALTHY - Page d'inscription
// =====================================================
// Cette page permet à un nouvel utilisateur de créer un compte.
// Elle suit le même style que la page de connexion pour garder
// une expérience cohérente dans le parcours d'authentification.
$titre_page = 'Inscription';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

if (est_connecte()) {
    header('Location: compte.php');
    exit;
}

$prenom = $nom = $email = $telephone = '';
$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if ($prenom === '' || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $mot_de_passe === '') {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreur = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO utilisateurs (prenom, nom, email, telephone, mot_de_passe, objectif, role)
                VALUES (?, ?, ?, ?, ?, "maintien", "client")');
            $ok = $stmt->execute([$prenom, $nom, $email, $telephone, $hash]);

            if ($ok) {
                $success = 'Compte créé avec succès.';
                $_SESSION['retour_apres_connexion'] = 'compte.php';
                header('Location: connexion.php');
                exit;
            }

            $erreur = 'Une erreur est survenue lors de la création du compte.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section connexion-page">
    <div class="connexion-card inscription-card">
        <div class="connexion-header">
            <h1>Bienvenue parmi nous !</h1>
            <p>Déjà un compte ? <a href="connexion.php">Connectez-vous</a></p>
        </div>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <form method="post" class="connexion-form inscription-form">
            <?= csrf_field() ?>
            <div class="form-row two-cols">
                <div class="champ connexion-champ">
                    <label for="prenom">PRÉNOM</label>
                    <div class="champ-input">
                        <span class="champ-icone">◔</span>
                        <input id="prenom" name="prenom" type="text" value="<?= e($prenom) ?>" placeholder="Votre prénom" required>
                    </div>
                </div>

                <div class="champ connexion-champ">
                    <label for="nom">NOM</label>
                    <div class="champ-input">
                        <span class="champ-icone">◔</span>
                        <input id="nom" name="nom" type="text" value="<?= e($nom) ?>" placeholder="Votre nom" required>
                    </div>
                </div>
            </div>

            <div class="champ connexion-champ">
                <label for="email">EMAIL PROFESSIONNEL OU PERSONNEL</label>
                <div class="champ-input">
                    <span class="champ-icone">✉</span>
                    <input id="email" name="email" type="email" value="<?= e($email) ?>" placeholder="votre@email.com" required>
                </div>
            </div>

            <div class="form-row two-cols">
                <div class="champ connexion-champ">
                    <label for="telephone">TÉLÉPHONE</label>
                    <div class="champ-input">
                        <span class="champ-icone">◍</span>
                        <input id="telephone" name="telephone" type="tel" value="<?= e($telephone) ?>" placeholder="77 000 00 00">
                    </div>
                </div>

                <div class="champ connexion-champ">
                    <label for="mot_de_passe">MOT DE PASSE</label>
                    <div class="champ-input">
                        <span class="champ-icone">◐</span>
                        <input id="mot_de_passe" name="mot_de_passe" type="password" placeholder="••••••••" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-connexion">Créer mon compte <span>→</span></button>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
