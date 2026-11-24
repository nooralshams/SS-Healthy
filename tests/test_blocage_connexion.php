<?php
// =====================================================
// SS'Healthy - Test T3 : blocage après 5 échecs de connexion
// Utilise une base MySQL temporaire (supprimée à la fin) : zig_healthy_db n'est pas touchée.
// Lancer depuis la racine du projet :  php tests/test_blocage_connexion.php
// Variables facultatives : ZIG_HEALTHY_DB_HOST, _PORT, _USER, _PASS (mêmes valeurs par défaut que config.php).
// =====================================================
require_once __DIR__ . '/../includes/connexion_functions.php';

$hote = getenv('ZIG_HEALTHY_DB_HOST') ?: 'localhost';
$port = getenv('ZIG_HEALTHY_DB_PORT') ?: '3308';
$user = getenv('ZIG_HEALTHY_DB_USER') ?: 'root';
$pass = getenv('ZIG_HEALTHY_DB_PASS') !== false ? getenv('ZIG_HEALTHY_DB_PASS') : '';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

$nomBase = 'test_blocage_' . bin2hex(random_bytes(4));
$pdoAdmin = new PDO("mysql:host=$hote;port=$port;charset=utf8mb4", $user, $pass, $options);
$pdoAdmin->exec("CREATE DATABASE `$nomBase` CHARACTER SET utf8mb4");

$total = 0;
$echecs = 0;
function verifier(string $nom, $obtenu, $attendu): void {
    global $total, $echecs;
    $total++;
    if ($obtenu === $attendu) { echo "  OK   $nom\n"; return; }
    $echecs++;
    echo "  ECHEC $nom\n         attendu : " . json_encode($attendu) . "\n         obtenu  : " . json_encode($obtenu) . "\n";
}

try {
    $pdo = new PDO("mysql:host=$hote;port=$port;dbname=$nomBase;charset=utf8mb4", $user, $pass, $options);
    $pdo->exec('CREATE TABLE utilisateurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        role VARCHAR(10) NOT NULL,
        tentatives_connexion TINYINT UNSIGNED NOT NULL DEFAULT 0,
        bloque TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB');
    $pdo->exec("INSERT INTO utilisateurs (email, role) VALUES ('a@test.sn', 'client'), ('b@test.sn', 'client'), ('c@test.sn', 'admin')");

    $etat = function (int $id) use ($pdo): array {
        $s = $pdo->prepare('SELECT tentatives_connexion AS t, bloque AS b FROM utilisateurs WHERE id = ?');
        $s->execute([$id]);
        $r = $s->fetch();
        return [(int) $r['t'], (int) $r['b']];
    };

    echo "Seuil de blocage\n";
    verifier('le seuil est de 5 échecs', SEUIL_BLOCAGE_CONNEXION, 5);

    echo "\nCompte A : échecs consécutifs\n";
    for ($i = 1; $i <= 4; $i++) {
        $bloque = enregistrer_echec_connexion($pdo, 1);
        verifier("échec $i : compte non bloqué", $bloque, false);
        verifier("échec $i : compteur = $i", $etat(1), [$i, 0]);
    }
    $bloque = enregistrer_echec_connexion($pdo, 1);
    verifier('échec 5 : le compte vient d\'être bloqué', $bloque, true);
    verifier('échec 5 : compteur = 5 et bloque = 1', $etat(1), [5, 1]);

    echo "\nCompte B : une connexion réussie remet le compteur à zéro\n";
    for ($i = 1; $i <= 4; $i++) { enregistrer_echec_connexion($pdo, 2); }
    verifier('4 échecs : pas encore bloqué', $etat(2), [4, 0]);
    reinitialiser_tentatives_connexion($pdo, 2);
    verifier('après connexion réussie : compteur à 0', $etat(2), [0, 0]);
    for ($i = 1; $i <= 4; $i++) { enregistrer_echec_connexion($pdo, 2); }
    verifier('4 nouveaux échecs : toujours pas bloqué', $etat(2), [4, 0]);

    echo "\nIndépendance des comptes\n";
    verifier('le blocage de A n\'a pas bloqué B', $etat(2)[1], 0);

    echo "\nDéblocage\n";
    for ($i = 1; $i <= 5; $i++) { enregistrer_echec_connexion($pdo, 3); }
    verifier('administrateur C bloqué après 5 échecs', $etat(3), [5, 1]);
    verifier('un client ne peut pas être débloqué comme administrateur', debloquer_compte($pdo, 1, 'admin'), false);
    verifier('le client A reste bloqué', $etat(1), [5, 1]);
    verifier('déblocage de l\'administrateur C', debloquer_compte($pdo, 3, 'admin'), true);
    verifier('C : compteur à 0 et débloqué', $etat(3), [0, 0]);
    verifier('déblocage du client A sans rôle imposé', debloquer_compte($pdo, 1), true);
    verifier('A : compteur à 0 et débloqué', $etat(1), [0, 0]);
    verifier('compte inexistant : renvoie false', debloquer_compte($pdo, 99), false);
} finally {
    $pdoAdmin->exec("DROP DATABASE IF EXISTS `$nomBase`");
}

echo "\n$total tests, $echecs échec(s)\n";
exit($echecs === 0 ? 0 : 1);