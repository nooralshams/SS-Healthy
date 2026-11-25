<?php
// Séquence : inscription d'un client.
// Sources : inscription.php (l. 12-49), includes/config.php (l. 22-29, jeton CSRF), includes/fonctions.php (csrf_field).
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1300, 1000, "Diagramme de séquence — Inscription d'un client (SS'Healthy)");
$s = new Sequence($d, [
    'v' => [70, 'Visiteur', 'acteur'],
    'nav' => [260, ['Navigateur', 'interface (HTML)'], 'boite'],
    'page' => [560, ['inscription.php', 'serveur (config.php, session PHP)'], 'boite'],
    'bd' => [1100, ['MySQL', 'table utilisateurs'], 'base'],
]);
$s->y0debut = 135;
$s->msg('v', 'nav', 'Ouvre « Créer un compte »');
$s->msg('nav', 'page', 'GET inscription.php');
$s->msg('page', 'nav', 'Formulaire (prénom, nom, e-mail, téléphone, mot de passe) + jeton CSRF', true);
$s->msg('v', 'nav', 'Saisit ses informations et valide');
$s->msg('nav', 'page', 'POST inscription.php (+ jeton CSRF)');
$s->msg('page', 'page', 'Vérifie le jeton CSRF (hash_equals) ; sinon arrêt, code 419');
$s->msg('page', 'page', 'Contrôle : prénom, nom, e-mail valide (filter_var), mot de passe');
$s->fragment('alt', 'champ manquant ou e-mail invalide', 150, 1240);
$s->msg('page', 'nav', '« Veuillez remplir tous les champs obligatoires. »', true);
$s->sinon('champs valides');
$s->msg('page', 'bd', 'SELECT id FROM utilisateurs WHERE email = ?');
$s->msg('bd', 'page', 'résultat', true);
$s->fragment('alt', 'adresse déjà utilisée', 172, 1225);
$s->msg('page', 'nav', '« Cette adresse e-mail est déjà utilisée. »', true);
$s->sinon('adresse libre');
$s->msg('page', 'page', 'password_hash(mot de passe) — le mot de passe n’est jamais stocké en clair');
$s->msg('page', 'bd', "INSERT INTO utilisateurs (…, objectif = NULL, role = 'client')");
$s->msg('bd', 'page', 'OK', true);
$s->msg('page', 'nav', 'Redirection vers connexion.php (retour vers compte.php après connexion)', true);
$s->fin_fragment();
$s->fin_fragment();
texte($d, 20, $s->y + 6, 'L’objectif nutritionnel n’est pas demandé à l’inscription : il est choisi après l’enregistrement d’un IMC (imc.php, compte.php).', ['taille' => 12, 'italique' => true, 'couleur' => '#55635A']);
$s->y += 20;
$s->lignes_de_vie($s->y - 32);
$d->h = $s->y + 20;
return $d;
