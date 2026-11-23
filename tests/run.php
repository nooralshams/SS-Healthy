<?php
require_once __DIR__ . '/../includes/bowl_config.php';
require_once __DIR__ . '/../includes/bowl_functions.php';
require_once __DIR__ . '/../includes/commande_functions.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../admin/includes/admin.php';

$assertions = 0;
$assert = static function ($condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$_SESSION['csrf_token'] = 'test-csrf-token';
$assert(strpos(csrf_field(), 'test-csrf-token') !== false, 'Le champ de formulaire CSRF ne contient pas le jeton de session.');
$prixHtml = prix_fcfa(2500);
$assert(strpos($prixHtml, '<span class="prix-affichage">') === 0
    && strpos($prixHtml, '<span class="prix-montant">2 500</span>') !== false
    && strpos($prixHtml, '<span class="prix-devise">FCFA</span>') !== false,
    'Le format HTML du prix doit séparer uniformément le montant et la devise.');

$postFormPattern = <<<'REGEX'
~<form\b[^>]*\bmethod\s*=\s*["']post["'][^>]*>(.*?)</form>~is
REGEX;
$projectIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__)));
$postFormCount = 0;
foreach ($projectIterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php'
        || strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false
        || strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }
    $source = file_get_contents($file->getPathname());
    preg_match_all($postFormPattern, $source, $forms);
    foreach ($forms[1] as $formBody) {
        $postFormCount++;
        $assert(strpos($formBody, 'csrf_field()') !== false || strpos($formBody, 'csrf_token') !== false,
            'Formulaire POST sans jeton CSRF : ' . $file->getPathname());
    }
}
$assert($postFormCount >= 20, 'Le test CSRF n’a pas trouvé tous les formulaires POST du projet.');

$mysqlHost = getenv('TEST_DB_HOST') ?: '127.0.0.1';
$mysqlPort = getenv('TEST_DB_PORT') ?: '3308';
$mysqlUser = getenv('TEST_DB_USER') ?: 'root';
$mysqlPass = getenv('TEST_DB_PASS') !== false ? getenv('TEST_DB_PASS') : '';
$mysqlBin = getenv('MYSQL_BIN') ?: 'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysql.exe';
if (!is_file($mysqlBin)) {
    $mysqlBin = 'mysql';
}

$testDatabase = 'zig_healthy_test_' . bin2hex(random_bytes(5));
if (!preg_match('/^zig_healthy_test_[a-f0-9]{10}$/', $testDatabase)) {
    throw new RuntimeException('Nom de base de test invalide.');
}

$server = new PDO(
    "mysql:host={$mysqlHost};port={$mysqlPort};charset=utf8mb4",
    $mysqlUser,
    $mysqlPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$created = false;

$executeMysqlScript = static function (string $database, string $sql) use ($mysqlBin, $mysqlHost, $mysqlPort, $mysqlUser, $mysqlPass): void {
    $command = [
        $mysqlBin,
        '--host=' . $mysqlHost,
        '--port=' . $mysqlPort,
        '--user=' . $mysqlUser,
        '--default-character-set=utf8mb4',
        $database,
    ];
    $environment = getenv();
    if ($mysqlPass !== '') {
        $environment['MYSQL_PWD'] = $mysqlPass;
    }
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), $environment);
    if (!is_resource($process)) {
        throw new RuntimeException('Impossible de lancer la CLI MySQL.');
    }
    fwrite($pipes[0], $sql);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) {
        throw new RuntimeException('Échec MySQL : ' . trim($stderr . ' ' . $stdout));
    }
};

try {
    $server->exec("CREATE DATABASE `{$testDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;

    $schema = file_get_contents(__DIR__ . '/../bd/zig_healthy_db.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS zig_healthy_db[^;]*;\s*/i', '', $schema);
    $schema = preg_replace('/USE zig_healthy_db;\s*/i', '', $schema);
    $executeMysqlScript($testDatabase, $schema);

    foreach (['migration_imc.sql', 'migration_bowls_personnalises.sql', 'migration_paiement_contact.sql', 'migration_reinitialisation_mot_de_passe.sql'] as $migration) {
        $migrationSql = file_get_contents(__DIR__ . '/../bd/' . $migration);
        $migrationSql = preg_replace('/^USE\s+zig_healthy_db\s*;\s*/mi', '', $migrationSql);
        $executeMysqlScript($testDatabase, $migrationSql);
    }

    $pdo = new PDO(
        "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$testDatabase};charset=utf8mb4",
        $mysqlUser,
        $mysqlPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $assert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('details_commande_bowls','messages_contact','mesures_imc','jetons_reinitialisation')")->fetchColumn() === 4, 'Le schéma frais ou ses migrations sont incomplets.');
    $assert((bool) $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commandes' AND COLUMN_NAME = 'mode_paiement'")->fetchColumn(), 'La colonne du règlement manque dans le schéma frais.');
    $formules = $pdo->query('SELECT nom, nb_repas_jour, prix, duree_jours FROM formules_abonnement WHERE actif = 1 ORDER BY nb_repas_jour')->fetchAll();
    $assert(count($formules) === 3, 'L’installation fraîche doit proposer trois formules hebdomadaires.');
    $assert($formules[0]['nom'] === 'Déjeuner seul' && (int) $formules[0]['prix'] === 15000 && (int) $formules[0]['nb_repas_jour'] === 1 && (int) $formules[0]['duree_jours'] === 7, 'La formule Déjeuner seul est incorrecte.');
    $assert($formules[1]['nom'] === 'Petit-déjeuner + Déjeuner' && (int) $formules[1]['prix'] === 30000 && (int) $formules[1]['nb_repas_jour'] === 2 && (int) $formules[1]['duree_jours'] === 7, 'La formule Petit-déjeuner + Déjeuner est incorrecte.');
    $assert($formules[2]['nom'] === 'Petit-déjeuner + Déjeuner + Dîner' && (int) $formules[2]['prix'] === 50000 && (int) $formules[2]['nb_repas_jour'] === 3 && (int) $formules[2]['duree_jours'] === 7, 'La formule complète est incorrecte.');
    $stmtMessage = $pdo->prepare('INSERT INTO messages_contact (nom, email, message) VALUES (?, ?, ?)');
    $stmtMessage->execute(['Test automatisé', 'test@example.test', 'Message de test pour la boîte de réception.']);
    $assert((int) $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE email = 'test@example.test'")->fetchColumn() === 1, 'Les messages de contact ne peuvent pas être enregistrés.');

    $assert($BOWL_CONFIG['sizes']['small']['price'] === 2500, 'Le tarif Small attendu est 2 500 F CFA.');
    $assert($BOWL_CONFIG['sizes']['medium']['price'] === 3000, 'Le tarif Medium attendu est 3 000 F CFA.');
    $assert($BOWL_CONFIG['sizes']['large']['price'] === 3500, 'Le tarif Large attendu est 3 500 F CFA.');
    $assert(creneaux_disponibles() === ['Sous 1 h', 'Sous 2 h', 'Pour midi', 'Ce soir'], 'Les délais proposés doivent tous être autorisés côté serveur.');
    $assert(determinerProfil(17.0) === 'insuffisance', 'La classification 17 doit être insuffisance pondérale.');
    $assert(determinerProfil(18.49) === 'normal', '18,49 arrondi à 18,5 doit être corpulence normale.');
    $assert(determinerProfil(56.6 / (1.75 * 1.75)) === 'normal', 'Le cas 56,6 kg / 175 cm doit tester la limite 18,5.');
    $assert(determinerProfil(24.9) === 'normal', 'La classification 24,9 doit être normale.');
    $assert(determinerProfil(25.0) === 'surpoids', 'La classification 25 doit être surpoids.');
    $assert(determinerProfil(29.9) === 'surpoids', 'La classification 29,9 doit être surpoids.');
    $assert(determinerProfil(30.0) === 'obesite', 'La classification 30 doit être obésité.');
    $assert(!creneau_autorise('Express') && !creneau_autorise('Sous 30 min'), 'Les créneaux envoyés à moins de 45 minutes doivent être refusés.');
    $assert(creneau_autorise('Sous 1 h'), 'Un créneau d’au moins 45 minutes doit être accepté.');
    $transitionsCommandes = transitions_statuts_commande();
    $transitionsAbonnements = transitions_statuts_abonnement();
    $assert(transition_statut_autorisee('en_attente', 'confirmee', $transitionsCommandes), 'Une commande en attente doit pouvoir être confirmée.');
    $assert(transition_statut_autorisee('prete', 'livree', $transitionsCommandes), 'Une commande prête doit pouvoir être livrée.');
    $assert(transition_statut_autorisee('en_preparation', 'annulee', $transitionsCommandes), 'Une commande doit pouvoir être annulée avant sa livraison.');
    $assert(!transition_statut_autorisee('en_attente', 'prete', $transitionsCommandes), 'Une commande ne doit pas sauter les étapes intermédiaires.');
    $assert(!transition_statut_autorisee('livree', 'annulee', $transitionsCommandes), 'Une commande livrée doit être terminale.');
    $assert(!transition_statut_autorisee('annulee', 'confirmee', $transitionsCommandes), 'Une commande annulée doit être terminale.');
    $assert(transition_statut_autorisee('en_attente', 'actif', $transitionsAbonnements), 'Un abonnement en attente doit pouvoir être activé.');
    $assert(transition_statut_autorisee('actif', 'termine', $transitionsAbonnements), 'Un abonnement actif doit pouvoir être terminé.');
    $assert(transition_statut_autorisee('actif', 'annule', $transitionsAbonnements), 'Un abonnement doit pouvoir être annulé avant sa fin.');
    $assert(!transition_statut_autorisee('en_attente', 'termine', $transitionsAbonnements), 'Un abonnement ne doit pas sauter l’activation.');
    $assert(!transition_statut_autorisee('termine', 'actif', $transitionsAbonnements), 'Un abonnement terminé doit être terminal.');
    $assert(!transition_statut_autorisee('annule', 'actif', $transitionsAbonnements), 'Un abonnement annulé doit être terminal.');
    $assert(!transition_statut_autorisee('en_attente', ['confirmee'], $transitionsCommandes), 'Un statut reçu sous forme de tableau doit être refusé.');

    $selection = [
        'size' => 'small',
        'bases' => ['fonio'],
        'proteins' => ['lentilles'],
        'vegetables' => ['avocat'],
        'sauce' => 'citron',
        'extras' => ['quinoaSelection' => true],
        'extraUnits' => 0,
    ];
    $bowl = preparer_selection_bowl($selection, $BOWL_CONFIG);
    $assert($bowl['ok'] && $bowl['price'] === 2500, 'Le quinoa offert ou le prix Small est incorrect.');
    $assert($bowl['used_units'] === 3 && $bowl['available_units'] === 3, 'Le décompte d’unités Small est incorrect.');

    $overLimit = $selection;
    $overLimit['vegetables'] = ['avocat', 'carottes'];
    $assert(!preparer_selection_bowl($overLimit, $BOWL_CONFIG)['ok'], 'La limite d’unités Small doit refuser un quatrième ingrédient.');

    $withExtraUnit = $selection;
    $withExtraUnit['vegetables'] = ['avocat', 'carottes'];
    $withExtraUnit['extraUnits'] = 1;
    $extraResult = preparer_selection_bowl($withExtraUnit, $BOWL_CONFIG);
    $assert($extraResult['ok'] && $extraResult['price'] === 3000 && $extraResult['available_units'] === 4, 'L’unité supplémentaire doit coûter 500 F CFA et ouvrir une unité.');

    $premium = $selection;
    $premium['proteins'] = ['dorade'];
    $premiumResult = preparer_selection_bowl($premium, $BOWL_CONFIG);
    $assert($premiumResult['ok'] && $premiumResult['price'] === 3200, 'La protéine premium doit ajouter 700 F CFA.');

    $missingSauce = $selection;
    $missingSauce['sauce'] = '';
    $assert(!preparer_selection_bowl($missingSauce, $BOWL_CONFIG)['ok'], 'Une sauce est obligatoire.');

    $userId = (int) $pdo->query('SELECT id FROM utilisateurs ORDER BY id LIMIT 1')->fetchColumn();
    $plat = $pdo->query('SELECT id, prix FROM plats WHERE disponible = 1 ORDER BY id LIMIT 1')->fetch();
    $assert($userId > 0 && $plat, 'Les données initiales ne contiennent pas le client admin ou un plat de test.');

    require_once __DIR__ . '/../includes/reinitialisation.php';
    $jetonReinitialisation = creer_jeton_reinitialisation($pdo, $userId);
    $assert(reinitialiser_mot_de_passe($pdo, $jetonReinitialisation, 'motdepasse-test-2026'), 'Un jeton valide doit permettre de changer le mot de passe.');
    $stmtMotDePasse = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
    $stmtMotDePasse->execute([$userId]);
    $assert(password_verify('motdepasse-test-2026', $stmtMotDePasse->fetchColumn()), 'Le nouveau mot de passe doit être enregistré sous forme hachée.');
    $assert(!reinitialiser_mot_de_passe($pdo, $jetonReinitialisation, 'motdepasse-test-2026'), 'Un jeton déjà utilisé ne doit plus fonctionner.');
    $jetonExpire = creer_jeton_reinitialisation($pdo, $userId);
    $stmtExpiration = $pdo->prepare('UPDATE jetons_reinitialisation SET expire_le = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE utilisateur_id = ?');
    $stmtExpiration->execute([$userId]);
    $assert(!jeton_reinitialisation_valide($pdo, $jetonExpire), 'Un jeton expiré ne doit pas être accepté.');
    $assert(!reinitialiser_mot_de_passe($pdo, $jetonExpire, 'motdepasse-test-2026'), 'Un jeton expiré ne doit pas modifier le mot de passe.');

    $panier = [
        ['plat_id' => (int) $plat['id'], 'quantite' => 2],
        'bowl_test' => [
            'type' => 'bowl_personnalise',
            'plat_id' => null,
            'nom' => 'Bowl personnalisé (Small)',
            'prix' => $bowl['price'],
            'description' => $bowl['composition'],
            'quantite' => 1,
        ],
    ];
    $reconstruit = reconstruire_articles_panier($pdo, $panier);
    $prixAttendu = ((int) $plat['prix'] * 2) + $bowl['price'];
    $assert(count($reconstruit['articles']) === 2, 'Le panier mixte doit contenir le plat et le bowl.');
    $assert($reconstruit['total'] === $prixAttendu, 'Le total du panier mixte est incorrect.');

    $commandeId = enregistrer_commande($pdo, [
        'utilisateur_id' => $userId,
        'creneau' => 'Sous 1 h',
        'mode' => 'livraison',
        'mode_paiement' => 'wave',
        'adresse_livraison' => 'Ziguinchor, test automatisé',
        'total' => $reconstruit['total'],
    ], $reconstruit['articles']);
    $assert($commandeId > 0, 'Le checkout doit enregistrer une commande.');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM details_commande WHERE commande_id = {$commandeId}")->fetchColumn() === 1, 'La ligne de plat doit être enregistrée.');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM details_commande_bowls WHERE commande_id = {$commandeId}")->fetchColumn() === 1, 'La ligne de bowl doit être enregistrée séparément.');
    $stmt = $pdo->prepare('SELECT mode_paiement, creneau, statut, total FROM commandes WHERE id = ?');
    $stmt->execute([$commandeId]);
    $commandeEnregistree = $stmt->fetch();
    $assert($commandeEnregistree['mode_paiement'] === 'wave' && $commandeEnregistree['creneau'] === 'Sous 1 h' && $commandeEnregistree['statut'] === 'en_attente' && (int) $commandeEnregistree['total'] === $prixAttendu, 'Le mode de paiement, le délai, le statut en attente ou le total du checkout est incorrect.');

    $bowlsSeuls = reconstruire_articles_panier($pdo, [
        'bowl_seul' => [
            'type' => 'bowl_personnalise',
            'nom' => 'Bowl personnalisé (Small)',
            'prix' => $bowl['price'],
            'description' => $bowl['composition'],
            'quantite' => 1,
        ],
    ]);
    $assert(count($bowlsSeuls['articles']) === 1 && $bowlsSeuls['total'] === $bowl['price'], 'Un panier composé uniquement de bowls doit être reconstruit avec son total.');
    $commandeBowlsSeuls = enregistrer_commande($pdo, [
        'utilisateur_id' => $userId,
        'creneau' => 'Sous 1 h',
        'mode' => 'sur_place',
        'mode_paiement' => 'especes',
        'adresse_livraison' => null,
        'total' => $bowlsSeuls['total'],
    ], $bowlsSeuls['articles']);
    $assert((int) $pdo->query("SELECT COUNT(*) FROM details_commande WHERE commande_id = {$commandeBowlsSeuls}")->fetchColumn() === 0, 'Un panier bowl-only ne doit pas créer de détail de plat catalogue.');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM details_commande_bowls WHERE commande_id = {$commandeBowlsSeuls}")->fetchColumn() === 1, 'Un panier bowl-only doit enregistrer son détail de bowl.');

    $nombreCommandesAvantAnnulation = (int) $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn();
    $annulationDetectee = false;
    try {
        enregistrer_commande($pdo, [
            'utilisateur_id' => $userId,
            'creneau' => 'Sous 1 h',
            'mode' => 'sur_place',
            'mode_paiement' => 'especes',
            'adresse_livraison' => null,
            'total' => 100,
        ], [[
            'plat' => ['id' => 999999999, 'nom' => 'Plat invalide', 'prix' => 100, 'disponible' => 1],
            'quantite' => 1,
            'est_bowl' => false,
            'composition' => [],
        ]]);
    } catch (Throwable $e) {
        $annulationDetectee = !$pdo->inTransaction();
    }
    $assert($annulationDetectee, 'Une erreur dans une ligne doit annuler la transaction.');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn() === $nombreCommandesAvantAnnulation, 'La transaction annulée ne doit laisser aucune commande.');

    echo "OK : {$assertions} assertions réussies (prix, unités, panier mixte, checkout, migrations et installation fraîche).\n";
} finally {
    if ($created) {
        $server->exec("DROP DATABASE IF EXISTS `{$testDatabase}`");
    }
}
