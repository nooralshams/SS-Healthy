<?php
// Boite de reception des messages du formulaire public.
$titre_page = 'Messages de contact';
require_once __DIR__ . '/includes/header.php';

$messages = $pdo->query(
    'SELECT m.id, m.nom, m.email, m.telephone, m.message, m.statut, m.date_message, u.prenom, u.nom AS nom_compte
     FROM messages_contact m
     LEFT JOIN utilisateurs u ON u.id = m.utilisateur_id
     ORDER BY m.date_message DESC'
)->fetchAll();
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Messages de contact</h1>
        <p class="section-sous-titre">Demandes envoyées depuis le formulaire public.</p>
        <?php if (!$messages): ?>
            <div class="carte-formulaire"><p>Aucun message reçu pour le moment.</p></div>
        <?php else: ?>
            <div class="admin-messages-list">
                <?php foreach ($messages as $message): ?>
                    <article class="admin-message">
                        <div class="admin-message-heading">
                            <h2><?= e($message['nom']) ?></h2>
                            <time datetime="<?= e(date(DATE_ATOM, strtotime($message['date_message']))) ?>"><?= e(date('d/m/Y H:i', strtotime($message['date_message']))) ?></time>
                        </div>
                        <p><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>
                            <?= $message['telephone'] ? ' · ' . e($message['telephone']) : '' ?></p>
                        <p class="admin-message-body"><?= nl2br(e($message['message'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
