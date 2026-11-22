<?php
// =====================================================
// ZIG HEALTHY - Admin : mon profil
// =====================================================
// Permet à l'administrateur de consulter et modifier ses informations
// personnelles (nom, e-mail, téléphone) et son mot de passe.
$titre_page = 'Mon profil';
require_once __DIR__ . '/includes/header.php';

$message = '';
$erreur = '';

// -----------------------------------------------------
// Mise à jour des informations du profil
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profil') {
    $prenom    = trim($_POST['prenom'] ?? '');
    $nom       = trim($_POST['nom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');

    if ($prenom === '' || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Veuillez renseigner un prénom, un nom et un e-mail valides.';
    } else {
        // Vérifier que l'e-mail n'est pas déjà utilisé par un autre compte
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
        $stmt->execute([$email, $_SESSION['utilisateur_id']]);

        if ($stmt->fetch()) {
            $erreur = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        } else {
            $stmt = $pdo->prepare('UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?');
            $stmt->execute([$prenom, $nom, $email, $telephone, $_SESSION['utilisateur_id']]);

            // On met à jour la session pour refléter les nouvelles informations
            $_SESSION['prenom'] = $prenom;
            $_SESSION['nom'] = $nom;
            $_SESSION['email'] = $email;

            $message = 'Vos informations ont été mises à jour.';
        }
    }
}

// -----------------------------------------------------
// Changement du mot de passe
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mot_de_passe') {
    $actuel      = $_POST['mot_de_passe_actuel'] ?? '';
    $nouveau     = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation_mot_de_passe'] ?? '';

    $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $hash_actuel = $stmt->fetchColumn();

    if (!password_verify($actuel, $hash_actuel)) {
        $erreur = 'Le mot de passe actuel est incorrect.';
    } elseif (strlen($nouveau) < 8) {
        $erreur = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } elseif ($nouveau !== $confirmation) {
        $erreur = 'La confirmation ne correspond pas au nouveau mot de passe.';
    } else {
        $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $stmt->execute([password_hash($nouveau, PASSWORD_DEFAULT), $_SESSION['utilisateur_id']]);
        $message = 'Votre mot de passe a été modifié.';
    }
}

// Informations actuelles de l'administrateur
$stmt = $pdo->prepare('SELECT prenom, nom, email, telephone, date_inscription FROM utilisateurs WHERE id = ?');
$stmt->execute([$_SESSION['utilisateur_id']]);
$admin = $stmt->fetch();
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Mon profil</h1>
        <p class="section-sous-titre">Vos informations d'administrateur.</p>

        <?php if ($message): ?>
            <div class="alerte alerte-succes"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <div class="carte-formulaire admin-form-plat" style="margin-left:0;">
            <h2>Informations personnelles</h2>
            <p><small>Compte créé le <?= e(date('d/m/Y', strtotime($admin['date_inscription']))) ?></small></p>

            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profil">

                <div class="admin-grille-2">
                    <div class="champ">
                        <label for="prenom">Prénom *</label>
                        <input id="prenom" name="prenom" type="text" maxlength="50" value="<?= e($admin['prenom']) ?>" required>
                    </div>
                    <div class="champ">
                        <label for="nom">Nom *</label>
                        <input id="nom" name="nom" type="text" maxlength="50" value="<?= e($admin['nom']) ?>" required>
                    </div>
                </div>

                <div class="champ">
                    <label for="email">Adresse e-mail *</label>
                    <input id="email" name="email" type="email" maxlength="100" value="<?= e($admin['email']) ?>" required>
                </div>

                <div class="champ">
                    <label for="telephone">Téléphone</label>
                    <input id="telephone" name="telephone" type="text" maxlength="20" value="<?= e($admin['telephone'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-plein">Enregistrer</button>
            </form>
        </div>

        <div class="carte-formulaire admin-form-plat" style="margin-left:0;">
            <h2>Changer le mot de passe</h2>

            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mot_de_passe">

                <div class="champ">
                    <label for="mot_de_passe_actuel">Mot de passe actuel *</label>
                    <input id="mot_de_passe_actuel" name="mot_de_passe_actuel" type="password" autocomplete="current-password" required>
                </div>

                <div class="admin-grille-2">
                    <div class="champ">
                        <label for="nouveau_mot_de_passe">Nouveau mot de passe *</label>
                        <input id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="champ">
                        <label for="confirmation_mot_de_passe">Confirmation *</label>
                        <input id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" type="password" autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-mangue">Modifier le mot de passe</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
