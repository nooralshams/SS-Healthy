# SS’Healthy

SS’Healthy est une application web PHP/MySQL de commande de repas. Elle comprend un calcul d’IMC, un menu, un panier avec bowls personnalisés, la prise de commande et un espace d’administration.

## Prérequis

- WAMP avec Apache et MySQL démarrés
- PHP avec les extensions PDO et `pdo_mysql`
- MySQL accessible sur le port configuré dans `includes/config.php` (port local du projet : `3308`)
- La CLI `mysql` et Composer pour exécuter les tests d’intégration

## Installation locale

1. Placer le projet dans le répertoire web de WAMP, par exemple `C:\wamp64\www\zig_healthy`.
2. Démarrer Apache et MySQL.
3. Importer `bd/zig_healthy_db.sql` dans phpMyAdmin. Le script crée la base `zig_healthy_db` et le schéma principal.
4. Sélectionner la base et importer, dans cet ordre, les migrations nécessaires à l’installation actuelle :
   - `bd/migration_imc.sql`
   - `bd/migration_bowls_personnalises.sql`
   - `bd/migration_paiement_contact.sql`
   - `bd/migration_reinitialisation_mot_de_passe.sql`
5. Vérifier la configuration locale de la base dans `includes/config.php`. Ne pas publier de mots de passe, comptes de démonstration ni paramètres SMTP.
6. Définir le mot de passe du compte administrateur : le script SQL ne contient aucun mot de passe, et le compte admin importé ne peut pas se connecter tant que cette étape n’est pas faite.
   - Générer un hash en local (saisir le mot de passe au clavier, puis Entrée) :
     `php -r "echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT), PHP_EOL;"`
   - Dans phpMyAdmin, exécuter `UPDATE utilisateurs SET mot_de_passe = '<hash généré>' WHERE role = 'admin';`
   - Ne conserver ce hash dans aucun fichier du projet.
7. Ouvrir `http://localhost:8080/zig_healthy/`.

Les scripts `migration_categories_repas.sql`, `migration_blocage.sql` et `migration_abonnements.sql` concernent des bases historiques. Ne les appliquez pas à l’installation fraîche ci-dessus sans vérifier leur compatibilité avec le schéma déjà importé.

Les transferts Wave et Orange Money ne sont pas traités par un prestataire de paiement intégré : leur vérification est manuelle. N’entrez pas de données de carte bancaire dans l’application.

## Fonctionnalités principales

- Menu filtrable et recommandation d’objectifs à partir de l’IMC.
- Panier de plats du catalogue et composeur de bowls personnalisés.
- Relecture en base des prix des plats du catalogue au passage de la commande.
- Créneaux de commande acceptés côté serveur : `Sous 1 h`, `Sous 2 h`, `Pour midi`, `Ce soir`.
- Commandes et abonnements suivis par l’administration, avec des transitions de statut contrôlées côté serveur.
- Formulaires protégés par jeton CSRF.

### Tarifs du composeur de bowls

| Taille | Prix |
|---|---:|
| Small | 2 500 F CFA |
| Medium | 3 000 F CFA |
| Large | 3 500 F CFA |

La sélection de fonio est offerte. Une protéine premium ajoute 500 F CFA et chaque unité supplémentaire coûte 500 F CFA.

## Diagrammes et documentation

`diagrammes_SSHealthy.drawio` contient les diagrammes de conception. Pour les ouvrir, utiliser diagrams.net puis **File → Open From → Device**. L’exportation PNG ou PDF se fait depuis le menu **File → Export as**.

Les diagrammes sont des vues de conception et doivent rester synchronisés avec le code et le schéma SQL. Le code PHP de l’application est principalement procédural.

`docs/rapport_memoire_base.md` est un document de travail avec des éléments à vérifier ; il ne remplace pas le mémoire final.

## Tests

Depuis la racine du projet :

```powershell
php tests/test_fonctions.php
php tests/test_blocage_connexion.php
php tests/test_images.php
php tests/run.php
```

- `test_fonctions.php` vérifie les règles d’IMC, les objectifs, les créneaux et quelques fonctions utilitaires, sans base de données.
- `test_blocage_connexion.php` crée et supprime une base temporaire pour vérifier le blocage après cinq échecs et le déblocage d’un compte.
- `test_images.php` vérifie le contrôle des images de plats envoyées depuis l’administration (taille, extension, type réel, renommage), sans base de données.
- `run.php` crée une base temporaire, exécute les tests d’intégration (schéma, migrations, panier, commande, transactions et règles métier), puis supprime cette base temporaire. Il nécessite un compte MySQL autorisé à créer et supprimer des bases de test.

Ces tests ne doivent pas être exécutés contre une base de production. Ils n’ont pas besoin du compte administrateur de l’application.

## Structure du projet

- `admin/` : pages d’administration.
- `bd/` : schéma SQL et migrations.
- `css/`, `js/`, `images/` : ressources de l’interface.
- `docs/` : documents de travail.
- `includes/` : configuration et fonctions partagées.
- `outils/` : scripts en ligne de commande (déblocage d’un administrateur), inaccessibles depuis le navigateur.
- `tests/` : tests unitaires et d’intégration.
- `diagrammes_SSHealthy.drawio` : diagrammes de conception modifiables.

## Sécurité et configuration

Ne publiez jamais de secrets, de comptes de démonstration, de mots de passe SMTP ou de configuration locale. Configurez les valeurs propres à votre environnement dans les paramètres locaux et renouvelez tout identifiant qui aurait déjà été partagé ou déposé dans un dépôt public.
