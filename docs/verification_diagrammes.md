# Vérification des diagrammes contre le code — SS'Healthy

Vérification du 25/11/2026, branche `corrections-encadreur`. Chaque diagramme a été redessiné **après** relecture du code ou du schéma cité ; en cas de désaccord, c'est le diagramme qui a été corrigé, jamais le code.

- PNG (fond blanc, résolution ×2) : `docs/diagrammes/png/`
- Sources relisibles : `docs/diagrammes/source/dXX_*.php` (chaque fichier cite en tête le code qu'il reproduit)
- Régénération : `bash docs/diagrammes/generer.sh`
- `diagrammes_SSHealthy.drawio` est l'ancienne version (8 pages, sans les diagrammes d'états) : les PNG de `docs/diagrammes/png/` le remplacent.

| Diagramme | Fichier(s) de code ou SQL vérifié(s) | Conforme | Écart éventuel |
|---|---|---|---|
| 01 Cas d'utilisation (fig. 9) | `menu.php`, `plat.php`, `imc.php`, `panier.php`, `bowl_add.php`, `inscription.php`, `connexion.php`, `mot_de_passe_oublie.php`, `contact.php`, `abonnements.php`, `commande.php` l. 13-18, `compte.php`, `deconnexion.php`, `admin/*.php` (dashboard, plats, categories, commandes, abonnements, clients, messages, profil, logout) | oui | — |
| 02 Déploiement | `includes/config.php` l. 33-37 (PDO, port 3308, `zig_healthy_db`), `includes/reinitialisation.php` (PHPMailer, variables `ZIG_HEALTHY_SMTP_*`), `includes/image_functions.php` (`images/plats/`), WAMP : Apache 2.4.65, PHP 8.0.30, MySQL 8.4.7 | oui | — |
| 03 Classes (fig. 13) | `bd/zig_healthy_db.sql`, `bd/migration_imc.sql` (12 tables) | oui | Le « mode de retrait » s'appelle `mode` dans `commandes` ; `poids` et `taille` sont dans `mesures_imc`, pas dans `utilisateurs` : le diagramme reprend les noms du SQL. 1..* entre `Commande` et `LigneCommande` : garanti par l'application (panier vide refusé, `commande.php` l. 21-24), pas par une contrainte SQL |
| 04 Séquence inscription | `inscription.php` l. 12-49, `includes/config.php` l. 20-30 (CSRF), `includes/fonctions.php` l. 24-31 | oui | — |
| 05 Séquence commande (fig. 12) | `commande.php` l. 13-18, 46-48, 55-104 ; `includes/commande_functions.php` l. 3-47 (`reconstruire_articles_panier`), l. 48-109 (`enregistrer_commande` : `beginTransaction`, `commit`, `rollBack`) ; `bowl_add.php` l. 19-37 | oui | Le prix d'un bowl n'est pas relu en base : il est calculé côté serveur à l'ajout (`bowl_config.php`) et conservé en session ; le diagramme l'indique. « Vider le panier » porte le numéro 24 car les messages ont été renumérotés après l'ajout de BEGIN, COMMIT et ROLLBACK |
| 06 MCD | `bd/zig_healthy_db.sql`, `bd/migration_imc.sql` | oui | OBJECTIF est représenté comme domaine de valeurs (ENUM) et n'est pas traduit en table |
| 07 MLD | `docs/dictionnaire_donnees.md` (généré à partir du SQL), `bd/zig_healthy_db.sql`, `bd/migration_imc.sql` | oui | — |
| 08 Activité (fig. 11) | `menu.php`, `bowl_add.php`, `panier.php`, `commande.php` l. 13-18 et 55-104, `connexion.php` + `includes/connexion_functions.php` l. 8, l. 18-24, `includes/commande_functions.php` l. 48-109 | oui | — |
| 09 États commande | `bd/zig_healthy_db.sql` (ENUM `statut`, défaut `en_attente`), `admin/includes/admin.php` (`transitions_statuts_commande`), `admin/commandes.php` l. 13-35 | oui | — |
| 10 États abonnement | `bd/zig_healthy_db.sql` (table `abonnements`, défaut `en_attente`), `abonnements.php` l. 49, `admin/includes/admin.php` (`transitions_statuts_abonnement`), `admin/abonnements.php` l. 15-27 | oui | — |

## Écarts trouvés dans les anciennes figures du mémoire (corrigés par les nouveaux PNG)

- Figure 12 (séquence de commande) : ni `BEGIN`, ni `COMMIT`, ni `ROLLBACK` ; panier vidé par les « Fonctions PHP » au lieu de la session côté serveur.
- Figure 15 du mémoire (classes) : `objectif : string` au lieu d'une énumération reliée en 0..1 ; `DetailCommande` en 1..* direct, faux pour une commande composée uniquement de bowls.
