<?php
// =====================================================
// ZIG HEALTHY - Calcul de l'IMC
// =====================================================
// Cette page permet à un utilisateur de calculer son indice de masse corporelle
// à partir de son poids et de sa taille, puis d'afficher une interprétation.
$titre_page = 'Calculer mon IMC';
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$poids = '';
$taille = '';
$imc = null;
$interpretation = '';
$classe_imc = '';
$objectifs_disponibles = [];
$objectif_recommande = '';
$objectif_choisi = '';
$erreur = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $poids = str_replace(',', '.', trim($_POST['poids'] ?? ''));
    $taille = str_replace(',', '.', trim($_POST['taille'] ?? ''));

    $poids_num = filter_var($poids, FILTER_VALIDATE_FLOAT);
    $taille_num = filter_var($taille, FILTER_VALIDATE_FLOAT);

    if ($poids_num === false || $poids_num < 20 || $poids_num > 300) {
        $erreur = 'Indiquez un poids compris entre 20 et 300 kg.';
    } elseif ($taille_num === false || $taille_num < 100 || $taille_num > 250) {
        $erreur = 'Indiquez une taille comprise entre 100 et 250 cm.';
    } else {
        $imc = $poids_num / (($taille_num / 100) ** 2);
        $profil_imc = determinerProfil($imc);

        if ($profil_imc === 'insuffisance') {
            $interpretation = 'Insuffisance pondérale';
            $classe_imc = 'imc-bas';
        } elseif ($profil_imc === 'normal') {
            $interpretation = 'Corpulence considérée comme normale';
            $classe_imc = 'imc-normal';
        } elseif ($profil_imc === 'surpoids') {
            $interpretation = 'Surpoids';
            $classe_imc = 'imc-eleve';
        } else {
            $interpretation = 'Obésité';
            $classe_imc = 'imc-tres-eleve';
        }

        $objectifs_disponibles = objectifs_autorises($imc);
        $objectif_recommande = $objectifs_disponibles[0] ?? '';
        $objectif_choisi = $objectif_recommande;

        if (($_POST['action'] ?? '') === 'enregistrer') {
            $objectif_demande = $_POST['objectif'] ?? '';

            if (!in_array($objectif_demande, objectifs_autorises($imc), true)) {
                $erreur = 'Cet objectif n’est pas compatible avec votre catégorie d’IMC. Choisissez un objectif proposé.';
            } else {
                $objectif_choisi = $objectif_demande;
                $_SESSION['objectif_recommande'] = $objectif_choisi;

                if (est_connecte()) {
                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare('UPDATE utilisateurs SET objectif = ? WHERE id = ?');
                        $stmt->execute([$objectif_choisi, $_SESSION['utilisateur_id']]);

                        $stmt = $pdo->prepare(
                            'INSERT INTO mesures_imc (utilisateur_id, poids, taille, imc, interpretation, objectif_recommande)
                             VALUES (?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([
                            $_SESSION['utilisateur_id'],
                            $poids_num,
                            $taille_num,
                            round($imc, 1),
                            $interpretation,
                            $objectif_choisi
                        ]);
                        $pdo->commit();
                        $message = 'Votre résultat IMC et votre objectif ont été enregistrés.';
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $erreur = 'Une erreur est survenue lors de l’enregistrement. Veuillez réessayer.';
                    }
                } else {
                    $message = 'Votre choix est pris en compte pour cette visite. Connectez-vous pour l’enregistrer dans votre profil.';
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section">
    <div class="conteneur">
        <div class="imc-introduction">
            <p class="section-sous-titre">Un repère simple pour mieux comprendre votre profil</p>
            <h1 class="section-titre">Calculez votre IMC</h1>
            <p>L'indice de masse corporelle est calculé à partir du poids et de la taille. Utilisez-le comme un indicateur général, à mettre en perspective avec votre âge, votre morphologie et votre état de santé.</p>
        </div>

        <div class="imc-layout">
            <div class="carte-formulaire imc-formulaire">
                <?php if ($erreur): ?>
                    <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
                <?php endif; ?>
                <?php if ($message): ?>
                    <div class="alerte alerte-succes"><?= e($message) ?></div>
                <?php endif; ?>

                <?php if (!est_connecte()): ?>
                    <div class="alerte alerte-info">
                        💡 <a href="connexion.php">Connectez-vous</a> ou <a href="inscription.php">créez un compte</a>
                        pour enregistrer votre résultat : notre équipe pourra alors vous proposer
                        le régime le plus adapté à votre profil.
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="calculer">
                    <div class="champ">
                        <label for="poids">Votre poids (kg)</label>
                        <input id="poids" name="poids" type="number" min="20" max="300" step="0.1" value="<?= e($poids) ?>" required>
                    </div>
                    <div class="champ">
                        <label for="taille">Votre taille (cm)</label>
                        <input id="taille" name="taille" type="number" min="100" max="250" step="0.1" value="<?= e($taille) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-mangue">Calculer mon IMC</button>
                </form>
            </div>

            <?php if ($imc !== null): ?>
                <div class="imc-resultat <?= e($classe_imc) ?>">
                    <p class="imc-resultat-label">Votre résultat</p>
                    <strong><?= number_format($imc, 1, ',', ' ') ?></strong>
                    <span>IMC</span>
                    <h2><?= e($interpretation) ?></h2>
                    <p>Ce résultat est indicatif. Un professionnel de santé pourra vous aider à l'interpréter selon votre situation personnelle.</p>
                    <form method="post" style="margin-top:16px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="enregistrer">
                        <input type="hidden" name="poids" value="<?= e($poids) ?>">
                        <input type="hidden" name="taille" value="<?= e($taille) ?>">
                        <div class="champ">
                            <label for="objectif">Objectif nutritionnel</label>
                            <select id="objectif" name="objectif" required>
                                <?php foreach ($objectifs_disponibles as $objectif): ?>
                                    <option value="<?= e($objectif) ?>" <?= $objectif_choisi === $objectif ? 'selected' : '' ?>>
                                        <?= e($OBJECTIFS[$objectif] ?? $objectif) ?><?= $objectif === $objectif_recommande ? ' (conseillé)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-plein">Valider mon objectif</button>
                    </form>
                    <?php if ($objectif_choisi !== ''): ?>
                        <p style="margin-top:12px;">
                            <a href="menu.php?objectif=<?= e($objectif_choisi) ?>" class="btn btn-contour">Voir les plats selon cet objectif</a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="imc-reperes">
                    <h2>Les repères</h2>
                    <p><span class="repere repere-bas"></span>Moins de 18,5 : insuffisance pondérale</p>
                    <p><span class="repere repere-normal"></span>18,5 à 24,9 : corpulence normale</p>
                    <p><span class="repere repere-eleve"></span>25 à 29,9 : surpoids</p>
                    <p><span class="repere repere-tres-eleve"></span>30 et plus : obésité</p>
                </div>
            <?php endif; ?>
        </div>

        <aside class="avertissement-sante" role="note" aria-labelledby="titre-avertissement-sante">
            <h2 id="titre-avertissement-sante">Information santé importante</h2>
            <p>SS’Healthy ne pose aucun diagnostic et ne remplace pas un avis médical. L’IMC est un repère général : il ne tient compte ni de la masse musculaire, ni de l’âge, ni d’une grossesse.</p>
            <p>Les plats à index glycémique faible proposés pour l’objectif « diabète » ne remplacent ni un traitement ni un suivi médical. En cas de diabète, de grossesse, de maladie chronique ou de perte de poids inexpliquée, demandez conseil à un médecin ou à un nutritionniste avant de changer votre alimentation.</p>
        </aside>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
