<?php
// =====================================================
// ZIG HEALTHY - Page de contact
// =====================================================
// Cette page présente le formulaire de contact et les informations
// pratiques du restaurant pour répondre au besoin de contact.
$titre_page = 'Contact';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$erreur_contact = '';
$nom_contact = '';
$email_contact = '';
$telephone_contact = '';
$texte_contact = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_contact = trim($_POST['nom'] ?? '');
    $email_contact = trim($_POST['email'] ?? '');
    $telephone_contact = trim($_POST['telephone'] ?? '');
    $texte_contact = trim($_POST['message'] ?? '');
    $champ_piege = trim($_POST['website'] ?? '');

    if ($champ_piege !== '') {
        $_SESSION['message_contact'] = 'Votre message a bien été envoyé.';
        header('Location: contact.php#formulaire');
        exit;
    }

    if ($nom_contact === '' || mb_strlen($nom_contact) > 100) {
        $erreur_contact = 'Indiquez un nom de 100 caractères maximum.';
    } elseif (!filter_var($email_contact, FILTER_VALIDATE_EMAIL) || mb_strlen($email_contact) > 190) {
        $erreur_contact = 'Indiquez une adresse e-mail valide.';
    } elseif (mb_strlen($telephone_contact) > 30) {
        $erreur_contact = 'Le numéro de téléphone est trop long.';
    } elseif (mb_strlen($texte_contact) < 10 || mb_strlen($texte_contact) > 5000) {
        $erreur_contact = 'Le message doit contenir entre 10 et 5 000 caractères.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO messages_contact (utilisateur_id, nom, email, telephone, message)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            est_connecte() ? $_SESSION['utilisateur_id'] : null,
            $nom_contact,
            $email_contact,
            $telephone_contact !== '' ? $telephone_contact : null,
            $texte_contact,
        ]);
        $_SESSION['message_contact'] = 'Merci, votre message a bien été envoyé.';
        header('Location: contact.php#formulaire');
        exit;
    }
}

$message_contact = $_SESSION['message_contact'] ?? '';
unset($_SESSION['message_contact']);
require_once 'includes/header.php';
?>

<section class="contact-page">
    <div class="conteneur contact-layout">
        <div class="contact-texte">
            <div class="contact-badge">ON RESTE EN CONTACT</div>

            <h1>Parlons de<br>votre santé.</h1>

            <p>
                Une question sur nos menus, une allergie particulière ou besoin d'un conseil sur votre abonnement ?
                Notre équipe est à votre écoute.
            </p>

            <div class="contact-info-list">
                <div class="contact-info-card">
                    <div class="contact-icon green">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                    </div>
                    <div class="contact-info-text">
                        <span>ADRESSE</span>
                        <strong>Ziguinchor, Sénégal</strong>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-icon green">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 9.5A5 5 0 0 1 12 5a5 5 0 0 1 5 4.5v5A5 5 0 0 1 12 19a5 5 0 0 1-5-4.5v-5Z"/>
                            <path d="M8.5 9.5h7"/>
                            <path d="M8.5 14.5h4.5"/>
                        </svg>
                    </div>
                    <div class="contact-info-text">
                        <span>WHATSAPP</span>
                        <strong>+221 77 018 82 98</strong>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-icon blue">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 4.5h10a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2Z"/>
                            <path d="M8.5 8.5h7"/>
                            <path d="M8.5 12h4"/>
                        </svg>
                    </div>
                    <div class="contact-info-text">
                        <span>TÉLÉPHONE</span>
                        <strong>+221 77 018 82 98</strong>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-icon orange">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v7A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-7Z"/>
                            <path d="m5.5 8 6.5 5 6.5-5"/>
                        </svg>
                    </div>
                    <div class="contact-info-text">
                        <span>HORAIRES</span>
                        <strong>Tous les jours • 9h à 21h</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-form-panel" id="formulaire">
            <form class="contact-form" method="post" action="contact.php#formulaire">
                <?= csrf_field() ?>
                <?php if ($erreur_contact): ?>
                    <div class="alerte alerte-erreur" role="alert"><?= e($erreur_contact) ?></div>
                <?php endif; ?>
                <?php if ($message_contact): ?>
                    <div class="alerte alerte-succes" role="status"><?= e($message_contact) ?></div>
                <?php endif; ?>
                <div class="champ contact-champ contact-honeypot" aria-hidden="true">
                    <label for="website">Ne pas remplir</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>
                <div class="champ contact-champ">
                    <label for="nom">NOM COMPLET</label>
                    <input id="nom" name="nom" type="text" maxlength="100" value="<?= e($nom_contact) ?>" placeholder="Votre nom" required>
                </div>

                <div class="champ contact-champ">
                    <label for="email">EMAIL</label>
                    <input id="email" name="email" type="email" maxlength="190" value="<?= e($email_contact) ?>" placeholder="votre@email.com" required>
                </div>

                <div class="champ contact-champ">
                    <label for="telephone">TÉLÉPHONE</label>
                    <input id="telephone" name="telephone" type="tel" maxlength="30" value="<?= e($telephone_contact) ?>" placeholder="77 000 00 00">
                </div>

                <div class="champ contact-champ">
                    <label for="message">MESSAGE</label>
                    <textarea id="message" name="message" rows="3" maxlength="5000" placeholder="Dites-nous tout..." required><?= e($texte_contact) ?></textarea>
                </div>

                <button type="submit" class="btn btn-contact-submit">
                    Envoyer mon message <span>✈</span>
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
