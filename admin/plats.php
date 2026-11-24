<?php
// =====================================================
// ZIG HEALTHY - Admin : gestion des plats
// =====================================================
// Permet à l'administrateur de voir le menu complet,
// d'ajouter un plat (avec une image contrôlée) et d'activer/désactiver
// la disponibilité d'un plat.
$titre_page = 'Gestion des plats';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/image_functions.php';

$message = '';
$erreur = '';
$dossier_images = __DIR__ . '/../images/plats';

// -----------------------------------------------------
// Ajout d'un nouveau plat
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $categorie_id = filter_input(INPUT_POST, 'categorie_id', FILTER_VALIDATE_INT);
    $nom          = trim($_POST['nom'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $prix         = filter_input(INPUT_POST, 'prix', FILTER_VALIDATE_INT);
    $calories     = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_INT);
    $proteines    = filter_input(INPUT_POST, 'proteines', FILTER_VALIDATE_FLOAT);
    $glucides     = filter_input(INPUT_POST, 'glucides', FILTER_VALIDATE_FLOAT);
    $lipides      = filter_input(INPUT_POST, 'lipides', FILTER_VALIDATE_FLOAT);
    $ig_faible    = isset($_POST['ig_faible']) ? 1 : 0;
    $objectifs    = $_POST['objectifs'] ?? [];

    if (!$categorie_id || $nom === '' || !$prix || !$calories
        || $proteines === false || $glucides === false || $lipides === false) {
        $erreur = 'Veuillez remplir correctement tous les champs obligatoires.';
    } else {
        $envoi = enregistrer_image_plat($_FILES['image'] ?? [], $dossier_images);
        if (!$envoi['ok'] && !$envoi['absent']) {
            $erreur = $envoi['erreur'];
        }
    }

    if ($erreur === '') {
        $image = $envoi['ok'] ? $envoi['nom'] : 'defaut.jpg';
        $stmt = $pdo->prepare(
            'INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$categorie_id, $nom, $description, $prix, $calories, $proteines, $glucides, $lipides, $ig_faible, $image]);
        $plat_id = (int) $pdo->lastInsertId();

        // Association du plat aux objectifs nutritionnels cochés
        $objectifs_valides = ['perte_gras', 'prise_masse', 'diabete', 'maintien'];
        $stmt_obj = $pdo->prepare('INSERT INTO plat_objectifs (plat_id, objectif) VALUES (?, ?)');
        foreach ($objectifs as $objectif) {
            if (in_array($objectif, $objectifs_valides, true)) {
                $stmt_obj->execute([$plat_id, $objectif]);
            }
        }

        $_SESSION['message_admin'] = 'Le plat « ' . $nom . ' » a été ajouté au menu.';
        header('Location: plats.php');
        exit;
    }
}

// -----------------------------------------------------
// Activation / désactivation d'un plat
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disponibilite') {
    $plat_id = filter_input(INPUT_POST, 'plat_id', FILTER_VALIDATE_INT);
    if ($plat_id) {
        $pdo->prepare('UPDATE plats SET disponible = 1 - disponible WHERE id = ?')->execute([$plat_id]);
        $_SESSION['message_admin'] = 'La disponibilité du plat #' . $plat_id . ' a été modifiée.';
    }
    header('Location: plats.php');
    exit;
}

// -----------------------------------------------------
// Modification d'un plat
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifier') {
    $plat_id = filter_input(INPUT_POST, 'plat_id', FILTER_VALIDATE_INT);
    $categorie_id = filter_input(INPUT_POST, 'categorie_id', FILTER_VALIDATE_INT);
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = filter_input(INPUT_POST, 'prix', FILTER_VALIDATE_INT);
    $calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_INT);
    $proteines = filter_input(INPUT_POST, 'proteines', FILTER_VALIDATE_FLOAT);
    $glucides = filter_input(INPUT_POST, 'glucides', FILTER_VALIDATE_FLOAT);
    $lipides = filter_input(INPUT_POST, 'lipides', FILTER_VALIDATE_FLOAT);
    $ig_faible = isset($_POST['ig_faible']) ? 1 : 0;

    if (!$plat_id || !$categorie_id || $nom === '' || !$prix || !$calories
        || $proteines === false || $glucides === false || $lipides === false) {
        $erreur = 'Veuillez remplir correctement tous les champs du plat.';
    } else {
        $envoi = enregistrer_image_plat($_FILES['image'] ?? [], $dossier_images);
        if (!$envoi['ok'] && !$envoi['absent']) {
            $erreur = $envoi['erreur'];
        }
    }

    if ($erreur === '') {
        $stmt_ancienne = $pdo->prepare('SELECT image FROM plats WHERE id = ?');
        $stmt_ancienne->execute([$plat_id]);
        $ancienne_image = $stmt_ancienne->fetchColumn();

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE plats SET categorie_id = ?, nom = ?, description = ?, prix = ?, calories = ?, proteines = ?, glucides = ?, lipides = ?, ig_faible = ? WHERE id = ?');
            $stmt->execute([$categorie_id, $nom, $description, $prix, $calories, $proteines, $glucides, $lipides, $ig_faible, $plat_id]);
            if ($envoi['ok']) {
                $pdo->prepare('UPDATE plats SET image = ? WHERE id = ?')->execute([$envoi['nom'], $plat_id]);
            }

            $pdo->prepare('DELETE FROM plat_objectifs WHERE plat_id = ?')->execute([$plat_id]);
            $objectifs_valides = ['perte_gras', 'prise_masse', 'diabete', 'maintien'];
            $stmt_obj = $pdo->prepare('INSERT INTO plat_objectifs (plat_id, objectif) VALUES (?, ?)');
            foreach ($_POST['objectifs'] ?? [] as $objectif) {
                if (in_array($objectif, $objectifs_valides, true)) {
                    $stmt_obj->execute([$plat_id, $objectif]);
                }
            }

            $pdo->commit();
            if ($envoi['ok']) {
                supprimer_image_plat($ancienne_image ?: null, $dossier_images);
            }
            $_SESSION['message_admin'] = 'Le plat « ' . $nom . ' » a été modifié.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($envoi['ok']) {
                supprimer_image_plat($envoi['nom'], $dossier_images);
            }
            $erreur = 'Impossible de modifier ce plat.';
        }
    }
}

// -----------------------------------------------------
// Suppression d'un plat
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    $plat_id = filter_input(INPUT_POST, 'plat_id', FILTER_VALIDATE_INT);
    if ($plat_id) {
        try {
            $pdo->prepare('DELETE FROM plats WHERE id = ?')->execute([$plat_id]);
            $_SESSION['message_admin'] = 'Le plat #' . $plat_id . ' a été supprimé.';
            header('Location: plats.php');
            exit;
        } catch (PDOException $e) {
            $erreur = 'Ce plat ne peut pas être supprimé car il est déjà utilisé dans une commande.';
        }
    }
}

$categories = $pdo->query('SELECT id, nom FROM categories ORDER BY nom')->fetchAll();

$plats = $pdo->query(
    'SELECT p.id, p.categorie_id, p.nom, p.description, p.prix, p.calories,
            p.proteines, p.glucides, p.lipides, p.ig_faible, p.disponible, c.nom AS categorie
     FROM plats p
     JOIN categories c ON c.id = p.categorie_id
     ORDER BY c.nom, p.nom'
)->fetchAll();

foreach ($plats as &$plat) {
    $stmt_objectifs = $pdo->prepare('SELECT objectif FROM plat_objectifs WHERE plat_id = ?');
    $stmt_objectifs->execute([$plat['id']]);
    $plat['objectifs'] = array_column($stmt_objectifs->fetchAll(), 'objectif');
}
unset($plat);

// Dans l'admin, l'objectif « diabete » est nommé explicitement pour ne pas le confondre
// avec la case « Plat à index glycémique faible » (colonne plats.ig_faible).
$libelles_objectifs_admin = array_merge($OBJECTIFS, ['diabete' => 'Objectif Diabète']);
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Plats</h1>
        <p class="section-sous-titre">Ajoutez des plats et gérez leur disponibilité.</p>

        <?php if (!empty($_SESSION['message_admin'])): ?>
            <div class="alerte alerte-succes"><?= e($_SESSION['message_admin']) ?></div>
            <?php unset($_SESSION['message_admin']); ?>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <details class="admin-ajout">
            <summary class="btn btn-plein">+ Ajouter un plat</summary>
            <form method="post" enctype="multipart/form-data" class="carte-formulaire admin-form-plat">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="ajouter">

                <div class="champ">
                    <label for="nom">Nom du plat *</label>
                    <input id="nom" name="nom" type="text" maxlength="100" required>
                </div>

                <div class="champ">
                    <label for="categorie_id">Catégorie *</label>
                    <select id="categorie_id" name="categorie_id" required>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?= (int) $categorie['id'] ?>"><?= e($categorie['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="champ">
                    <label for="image">Image (JPG, PNG ou WebP, 2 Mo maximum)</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="admin-grille-2">
                    <div class="champ">
                        <label for="prix">Prix (FCFA) *</label>
                        <input id="prix" name="prix" type="number" min="0" step="50" required>
                    </div>
                    <div class="champ">
                        <label for="calories">Calories (kcal) *</label>
                        <input id="calories" name="calories" type="number" min="0" required>
                    </div>
                </div>

                <div class="admin-grille-3">
                    <div class="champ">
                        <label for="proteines">Protéines (g) *</label>
                        <input id="proteines" name="proteines" type="number" min="0" step="0.1" required>
                    </div>
                    <div class="champ">
                        <label for="glucides">Glucides (g) *</label>
                        <input id="glucides" name="glucides" type="number" min="0" step="0.1" required>
                    </div>
                    <div class="champ">
                        <label for="lipides">Lipides (g) *</label>
                        <input id="lipides" name="lipides" type="number" min="0" step="0.1" required>
                    </div>
                </div>

                <div class="champ">
                    <label>Objectifs compatibles</label>
                    <?php foreach ($libelles_objectifs_admin as $cle => $libelle): ?>
                        <label class="admin-check">
                            <input type="checkbox" name="objectifs[]" value="<?= e($cle) ?>"> <?= e($libelle) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="champ">
                    <label class="admin-check">
                        <input type="checkbox" name="ig_faible" value="1"> Plat à index glycémique faible
                    </label>
                </div>

                <button type="submit" class="btn btn-plein">Enregistrer le plat</button>
            </form>
        </details>

        <table class="tableau">
            <thead>
                <tr><th>Plat</th><th>Catégorie</th><th>Prix</th><th>Calories</th><th>Disponible</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($plats as $plat): ?>
                    <tr>
                        <td><strong><?= e($plat['nom']) ?></strong></td>
                        <td><?= e($plat['categorie']) ?></td>
                        <td class="prix"><?= prix_fcfa($plat['prix']) ?></td>
                        <td><?= (int) $plat['calories'] ?> kcal</td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="disponibilite">
                                <input type="hidden" name="plat_id" value="<?= (int) $plat['id'] ?>">
                                <button type="submit" class="btn btn-contour btn-petit">
                                    <?= (int) $plat['disponible'] ? '✓ Disponible' : '✗ Indisponible' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <details class="admin-edit-plat">
                                <summary class="btn btn-modifier btn-petit">Modifier</summary>
                                <form method="post" enctype="multipart/form-data" class="carte-formulaire admin-form-plat">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="modifier">
                                    <input type="hidden" name="plat_id" value="<?= (int) $plat['id'] ?>">
                                    <div class="champ"><label>Nom *</label><input name="nom" value="<?= e($plat['nom']) ?>" required></div>
                                    <div class="champ">
                                        <label>Catégorie *</label>
                                        <select name="categorie_id" required>
                                            <?php foreach ($categories as $categorie): ?>
                                                <option value="<?= (int) $categorie['id'] ?>" <?= (int) $categorie['id'] === (int) $plat['categorie_id'] ? 'selected' : '' ?>><?= e($categorie['nom']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="champ"><label>Description</label><textarea name="description" rows="3"><?= e($plat['description']) ?></textarea></div>
                                    <div class="champ"><label>Nouvelle image (JPG, PNG ou WebP, 2 Mo maximum)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
                                    <div class="admin-grille-2">
                                        <div class="champ"><label>Prix (FCFA) *</label><input type="number" name="prix" value="<?= (int) $plat['prix'] ?>" min="0" required></div>
                                        <div class="champ"><label>Calories *</label><input type="number" name="calories" value="<?= (int) $plat['calories'] ?>" min="0" required></div>
                                    </div>
                                    <div class="admin-grille-3">
                                        <div class="champ"><label>Protéines *</label><input type="number" name="proteines" value="<?= e($plat['proteines']) ?>" step="0.1" min="0" required></div>
                                        <div class="champ"><label>Glucides *</label><input type="number" name="glucides" value="<?= e($plat['glucides']) ?>" step="0.1" min="0" required></div>
                                        <div class="champ"><label>Lipides *</label><input type="number" name="lipides" value="<?= e($plat['lipides']) ?>" step="0.1" min="0" required></div>
                                    </div>
                                    <div class="champ">
                                        <label>Objectifs compatibles</label>
                                        <?php foreach ($libelles_objectifs_admin as $cle => $libelle): ?>
                                            <label class="admin-check"><input type="checkbox" name="objectifs[]" value="<?= e($cle) ?>" <?= in_array($cle, $plat['objectifs'], true) ? 'checked' : '' ?>> <?= e($libelle) ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                    <label class="admin-check"><input type="checkbox" name="ig_faible" value="1" <?= (int) $plat['ig_faible'] ? 'checked' : '' ?>> Plat à index glycémique faible</label>
                                    <button type="submit" class="btn btn-plein btn-petit">Enregistrer les modifications</button>
                                </form>
                            </details>
                            <form method="post" class="admin-actions">
                                <?= csrf_field() ?>
                                <input type="hidden" name="plat_id" value="<?= (int) $plat['id'] ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <button type="submit" class="btn btn-supprimer btn-petit" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce plat ?');">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
