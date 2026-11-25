<?php
// Figure 12 — Séquence : passer une commande.
// Sources : commande.php (l. 13-18 connexion, 46-48 panier relu, 55-104 contrôles et enregistrement),
// includes/commande_functions.php (reconstruire_articles_panier, enregistrer_commande), includes/config.php (CSRF).
require_once __DIR__ . '/lib.php';
$d = new Diagramme(1500, 1560, "Diagramme de séquence — Passer une commande (SS'Healthy)");
$s = new Sequence($d, [
    'client' => [70, 'Client', 'acteur'],
    'nav' => [250, ['Navigateur', 'interface (HTML)'], 'boite'],
    'page' => [520, ['commande.php', 'serveur'], 'boite'],
    'session' => [770, ['Session PHP', 'serveur ($_SESSION)'], 'boite'],
    'fct' => [1040, ['commande_functions.php', 'serveur'], 'boite'],
    'bd' => [1340, ['MySQL', 'zig_healthy_db'], 'base'],
]);
$s->y0debut = 135;

$s->msg('client', 'nav', 'Clique sur « Commander »');
$s->msg('nav', 'page', 'GET commande.php');
$s->msg('page', 'session', 'est_connecte() ? (utilisateur_id)');
$s->fragment('alt', 'client non connecté', 150, 900);
$s->msg('page', 'session', "retour_apres_connexion = 'commande.php'");
$s->msg('page', 'nav', 'Redirection vers connexion.php', true);
$s->msg('client', 'nav', 'Se connecte, puis revient sur commande.php');
$s->sinon('client connecté');
$s->msg('page', 'nav', 'Récapitulatif + formulaire (créneau, mode de retrait, paiement)', true);
$s->fin_fragment();

$s->msg('client', 'nav', 'Choisit créneau, mode de retrait, paiement ; « Valider la commande »');
$s->msg('nav', 'page', 'POST commande.php (+ jeton CSRF)');
$s->msg('page', 'session', 'Vérifie le jeton CSRF (hash_equals) et lit le panier');
$s->msg('page', 'fct', 'reconstruire_articles_panier(panier)');
$s->fragment('loop', 'pour chaque plat du panier', 880, 1445);
$s->msg('fct', 'bd', 'SELECT id, nom, prix, disponible FROM plats WHERE id = ?');
$s->msg('bd', 'fct', 'prix et disponibilité lus en base', true);
$s->fin_fragment();
texte($d, 890, $s->y - 4, 'Bowl : prix calculé côté serveur à l’ajout (bowl_add.php, bowl_config.php)', ['taille' => 11, 'italique' => true, 'couleur' => '#55635A']);
$s->y += 12;
$s->msg('fct', 'page', 'articles + total recalculé', true);
$s->msg('page', 'page', 'Contrôle serveur : plats disponibles, créneau autorisé (≥ 45 min), mode, paiement, adresse');

$s->fragment('alt', 'une donnée est invalide', 150, 1475);
$s->msg('page', 'nav', 'Message d’erreur, formulaire réaffiché', true);
$s->sinon('données valides');
$s->msg('page', 'fct', 'enregistrer_commande(commande, articles)');
$s->msg('fct', 'bd', 'BEGIN (beginTransaction)');
$s->msg('fct', 'bd', 'INSERT INTO commandes (…, statut = en_attente)');
$s->msg('bd', 'fct', 'id de la commande', true);
$s->fragment('loop', 'pour chaque ligne du panier', 880, 1445);
$s->msg('fct', 'bd', 'INSERT INTO details_commande ou details_commande_bowls');
$s->fin_fragment();
$s->fragment('alt', 'toutes les insertions réussissent', 172, 1458);
$s->msg('fct', 'bd', 'COMMIT');
$s->msg('fct', 'page', 'numéro de commande', true);
$s->msg('page', 'session', 'Vider le panier ($_SESSION[\'panier\'] = [])');
$s->msg('page', 'nav', 'Confirmation + instructions de paiement', true);
$s->sinon('exception pendant l’enregistrement');
$s->msg('fct', 'bd', 'ROLLBACK (aucune ligne conservée)');
$s->msg('fct', 'page', 'exception relancée', true);
$s->msg('page', 'nav', '« Une erreur est survenue… » (panier conservé)', true);
$s->fin_fragment();
$s->fin_fragment();
$s->lignes_de_vie($s->y + 10);
$d->h = $s->y + 30;
return $d;
