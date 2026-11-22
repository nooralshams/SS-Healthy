<?php
// =====================================================
// ZIG HEALTHY - Admin : gestion des catégories
// =====================================================
$titre_page = 'Gestion des catégories';
require_once __DIR__ . '/includes/header.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter' || $action === 'modifier') {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($nom === '') {
            $erreur = 'Le nom de la catégorie est obligatoire.';
        } elseif ($action === 'ajouter') {
            $pdo->prepare('INSERT INTO categories (nom, description) VALUES (?, ?)')
                ->execute([$nom, $description]);
            $_SESSION['message_admin'] = 'La catégorie a été ajoutée.';
            header('Location: categories.php');
            exit;
        } else {
            $categorie_id = filter_input(INPUT_POST, 'categorie_id', FILTER_VALIDATE_INT);
            if ($categorie_id) {
                $pdo->prepare('UPDATE categories SET nom = ?, description = ? WHERE id = ?')
                    ->execute([$nom, $description, $categorie_id]);
                $_SESSION['message_admin'] = 'La catégorie a été modifiée.';
                header('Location: categories.php');
                exit;
            }
        }
    } elseif ($action === 'supprimer') {
        $categorie_id = filter_input(INPUT_POST, 'categorie_id', FILTER_VALIDATE_INT);
        if ($categorie_id) {
            try {
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$categorie_id]);
                $_SESSION['message_admin'] = 'La catégorie a été supprimée.';
            } catch (PDOException $e) {
                $erreur = 'Cette catégorie contient des plats. Elle ne peut pas être supprimée.';
            }
        }
    }
}

$categories = $pdo->query('SELECT id, nom, description FROM categories ORDER BY nom')->fetchAll();
?>

<section class="section">
    <div class="conteneur">
        <h1 class="section-titre">Catégories</h1>

        <?php if (!empty($_SESSION['message_admin'])): ?>
            <div class="alerte alerte-succes"><?= e($_SESSION['message_admin']) ?></div>
            <?php unset($_SESSION['message_admin']); ?>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
        <?php endif; ?>

        <details class="admin-ajout">
            <summary class="btn btn-plein">+ Ajouter une catégorie</summary>
            <form method="post" class="carte-formulaire admin-form-plat">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="ajouter">
                <div class="champ">
                    <label for="nom">Nom *</label>
                    <input id="nom" name="nom" type="text" maxlength="50" required>
                </div>
                <div class="champ">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-plein">Enregistrer</button>
            </form>
        </details>

        <table class="tableau">
            <thead><tr><th>Catégorie</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $categorie): ?>
                    <tr>
                        <td><strong><?= e($categorie['nom']) ?></strong></td>
                        <td><?= e($categorie['description']) ?></td>
                        <td>
                            <details>
                                <summary class="btn btn-contour btn-petit">Modifier</summary>
                                <form method="post" class="carte-formulaire">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="modifier">
                                    <input type="hidden" name="categorie_id" value="<?= (int) $categorie['id'] ?>">
                                    <div class="champ"><label>Nom</label><input name="nom" value="<?= e($categorie['nom']) ?>" required></div>
                                    <div class="champ"><label>Description</label><textarea name="description" rows="2"><?= e($categorie['description']) ?></textarea></div>
                                    <button type="submit" class="btn btn-plein btn-petit">Enregistrer</button>
                                </form>
                            </details>
                            <form method="post" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="categorie_id" value="<?= (int) $categorie['id'] ?>">
                                <button type="submit" class="btn btn-contour btn-petit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
