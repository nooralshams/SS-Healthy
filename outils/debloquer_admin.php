<?php
// =====================================================
// SS'Healthy - Débloquer un administrateur (ligne de commande)
// =====================================================
// À utiliser quand le seul administrateur est bloqué après cinq échecs :
// personne ne peut alors se connecter pour le débloquer depuis admin/clients.php.
// Depuis la racine du projet :  php outils/debloquer_admin.php adresse@email
// Ce script refuse de s'exécuter depuis un navigateur.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$email = $argv[1] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage : php outils/debloquer_admin.php adresse@email\n");
    exit(2);
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/connexion_functions.php';

$stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND role = 'admin'");
$stmt->execute([$email]);
$adminId = (int) $stmt->fetchColumn();

if ($adminId === 0) {
    fwrite(STDERR, "Aucun administrateur ne correspond à cette adresse.\n");
    exit(1);
}

debloquer_compte($pdo, $adminId, 'admin');
echo "Le compte administrateur est débloqué. Il peut se reconnecter.\n";
