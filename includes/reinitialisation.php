<?php
// =====================================================
// ZIG HEALTHY - Réinitialisation de mot de passe
// =====================================================

/** Crée ou remplace le jeton de réinitialisation d'un utilisateur. */
function creer_jeton_reinitialisation(PDO $pdo, int $utilisateurId): string {
    $jeton = bin2hex(random_bytes(32));
    $empreinte = hash('sha256', $jeton);
    $stmt = $pdo->prepare(
        'INSERT INTO jetons_reinitialisation (utilisateur_id, empreinte, expire_le, cree_le)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())
         ON DUPLICATE KEY UPDATE empreinte = VALUES(empreinte), expire_le = VALUES(expire_le), cree_le = NOW()'
    );
    $stmt->execute([$utilisateurId, $empreinte]);

    return $jeton;
}

/** Envoie le lien de réinitialisation à l'adresse du compte. */
function envoyer_lien_reinitialisation(string $email, string $jeton): bool {
    $urlBase = getenv('ZIG_HEALTHY_BASE_URL') ?: 'http://localhost/zig_healthy';
    $url = filter_var($urlBase, FILTER_VALIDATE_URL);
    $composants = $url ? parse_url($url) : false;
    if (!$composants || !in_array($composants['scheme'] ?? '', ['http', 'https'], true) || empty($composants['host'])) {
        error_log('URL de base invalide pour la réinitialisation du mot de passe.');
        return false;
    }

    $serveur = getenv('ZIG_HEALTHY_SMTP_HOST') ?: 'smtp.gmail.com';
    $port = filter_var(getenv('ZIG_HEALTHY_SMTP_PORT') ?: '587', FILTER_VALIDATE_INT);
    $utilisateurSmtp = getenv('ZIG_HEALTHY_SMTP_USER');
    $motDePasseSmtp = getenv('ZIG_HEALTHY_SMTP_PASS');
    $chiffrement = strtolower(getenv('ZIG_HEALTHY_SMTP_ENCRYPTION') ?: 'tls');
    $expediteur = getenv('ZIG_HEALTHY_MAIL_FROM') ?: $utilisateurSmtp;
    if (!$port || $port < 1 || $port > 65535
        || !is_string($utilisateurSmtp) || !filter_var($utilisateurSmtp, FILTER_VALIDATE_EMAIL)
        || !is_string($motDePasseSmtp) || $motDePasseSmtp === ''
        || !is_string($expediteur) || !filter_var($expediteur, FILTER_VALIDATE_EMAIL)
        || !in_array($chiffrement, ['tls', 'ssl'], true)) {
        error_log('Configuration SMTP incomplète ou invalide pour la réinitialisation du mot de passe.');
        return false;
    }

    $autoloader = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoloader)) {
        error_log('Dépendances Composer absentes : exécutez composer install.');
        return false;
    }
    require_once $autoloader;

    $lien = rtrim($urlBase, '/') . '/reinitialiser_mot_de_passe.php?token=' . rawurlencode($jeton);
    $sujet = 'Réinitialisation de votre mot de passe SS’Healthy';
    $message = "Bonjour,\n\nPour choisir un nouveau mot de passe, ouvrez ce lien (valable une heure) :\n{$lien}\n\n"
        . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.\n";

    $courriel = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $courriel->isSMTP();
        $courriel->Host = $serveur;
        $courriel->SMTPAuth = true;
        $courriel->Username = $utilisateurSmtp;
        $courriel->Password = $motDePasseSmtp;
        $courriel->Timeout = 15;
        $courriel->SMTPSecure = $chiffrement === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $courriel->Port = $port;
        $courriel->CharSet = 'UTF-8';
        $courriel->setFrom($expediteur, 'SS Healthy');
        $courriel->addAddress($email);
        $courriel->Subject = $sujet;
        $courriel->Body = $message;
        $courriel->send();
    } catch (PHPMailer\PHPMailer\Exception $erreur) {
        error_log('Échec de l’envoi du courriel de réinitialisation : ' . $courriel->ErrorInfo);
        return false;
    }

    return true;
}

/** Vérifie qu'un jeton est encore utilisable sans le consommer. */
function jeton_reinitialisation_valide(PDO $pdo, string $jeton): bool {
    if (!preg_match('/^[a-f0-9]{64}$/', $jeton)) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM jetons_reinitialisation WHERE empreinte = ? AND expire_le > NOW()');
    $stmt->execute([hash('sha256', $jeton)]);

    return (bool) $stmt->fetchColumn();
}

/** Met à jour le mot de passe si le jeton existe encore et n'est pas expiré. */
function reinitialiser_mot_de_passe(PDO $pdo, string $jeton, string $motDePasse): bool {
    if (!preg_match('/^[a-f0-9]{64}$/', $jeton)) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT utilisateur_id FROM jetons_reinitialisation
             WHERE empreinte = ? AND expire_le > NOW() FOR UPDATE'
        );
        $stmt->execute([hash('sha256', $jeton)]);
        $jetonValide = $stmt->fetch();
        if (!$jetonValide) {
            $pdo->commit();
            return false;
        }

        $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $stmt->execute([password_hash($motDePasse, PASSWORD_DEFAULT), (int) $jetonValide['utilisateur_id']]);
        $stmt = $pdo->prepare('DELETE FROM jetons_reinitialisation WHERE utilisateur_id = ?');
        $stmt->execute([(int) $jetonValide['utilisateur_id']]);
        $pdo->commit();

        return true;
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erreur;
    }
}
