# Rapport de vérification technique — SS’Healthy

Audit en lecture seule du code et des fichiers SQL. Aucun fichier du projet n’a été modifié, hormis la création de ce rapport. Les tests ont utilisé leurs bases temporaires; la base de travail `zig_healthy_db` n’a pas été inspectée directement.

## 1. Résumé

- Le blocage client à cinq échecs est testé et fonctionne; la page d’administration conserve toutefois une implémentation divergente qui bloque trop tôt.
- Les créneaux proposés ne correspondent pas aux créneaux acceptés et les deux fonctions de profil IMC n’arrondissent pas selon la même règle.
- `tests/test_fonctions.php` est absent; le test de blocage passe et `tests/run.php` réussit avec 73 assertions, y compris l’annulation de transaction.
- Plusieurs fonctions annoncées ne sont pas trouvées; les transitions de statuts restent libres parmi toutes les valeurs autorisées.
- Des identifiants de démonstration sont présents dans le README et un compte administrateur avec hash est semé dans le SQL; aucune valeur secrète n’est reproduite ici.

## 2. Tableau des vérifications

| N° | Vérification | Résultat | Preuve (fichier:ligne) | Recommandation |
|---|---|---|---|---|
| A1 | Constante de seuil à 5; affectation SQL de `bloque` avant le compteur; remise à zéro | CONFORME | `includes/connexion_functions.php:8,18-35`; test `tests/test_blocage_connexion.php:48-69` | Garder l’ordre des affectations et couvrir aussi le chemin de connexion administrateur. |
| A2 | Connexion client: charge le helper; compte bloqué, succès, échec; messages; session, CSRF et retour | CONFORME pour `connexion.php` | `connexion.php:9-11,21-57`; succès et redirection `:36-46`; compte bloqué et branche d’échec `:32-57` | Réutiliser le même helper dans la connexion administrateur; voir aussi le risque signalé ci-dessous. |
| A3 | Déblocage administratif remet le compteur à zéro | CONFORME | `admin/clients.php:10-19`, notamment `:17` | Conserver la remise à zéro, qui évite un reblocage immédiat après déblocage. |
| A4.1 | Arrondi et seuils des deux fonctions IMC | NON CONFORME | `includes/fonctions.php:34-49` compare l’IMC brut; `:88-98` arrondit avant les seuils 18,5 / 25 / 30 | Appliquer une règle commune et ajouter des tests de frontière à `objectifs_autorises()`. |
| A4.2 | Créneaux disponibles comparés aux créneaux autorisés | NON CONFORME | `includes/fonctions.php:77-85`; contrôle d’abord la liste puis l’autorisation dans `commande.php:71-74` | Retirer du formulaire les deux choix rejetés ou modifier la règle métier, puis tester le parcours HTTP. |
| A4.3 | « Pour midi » et « Ce soir » dépendent-ils de l’heure ? | CONFORME à l’affirmation | `includes/fonctions.php:82-85` ne vérifie que l’appartenance à une liste; aucun calcul d’heure ou de date n’est effectué | Clarifier si ces options doivent être disponibles à toute heure. |
| A5.1 | Existence et exécution de `tests/test_blocage_connexion.php` | CONFORME | `tests/test_blocage_connexion.php:1-6,48-86` | Conserver ce test d’intégration. |
| A5.2 | Existence et exécution de `tests/test_fonctions.php`; résultat attendu de 44 tests | NON TROUVÉ | Le fichier est absent de l’inventaire de `tests/`; seuls `test_blocage_connexion.php` et `run.php` sont présents. Aucun numéro de ligne ne s’applique au fichier absent. | Créer ce fichier de test ou corriger la documentation des tests attendus. |
| A5.3 | Test d’annulation de transaction dans `tests/run.php` | CONFORME | `tests/run.php:223-243` provoque une erreur d’insertion, vérifie la fermeture de la transaction et l’absence de commande persistée | Ajouter éventuellement le cas à un fichier de test dédié si celui-ci est créé. |
| A6.1 | Authentification avant affichage du formulaire et contrôles côté serveur | CONFORME | `commande.php:12-24` redirige vers la connexion avant l’affichage; validations `:55-80`; formulaire après traitement `:101` | Garder les validations serveur, indépendamment des contrôles HTML. |
| A6.2 | Origine et calcul du prix du bowl | CONFORME au calcul à l’ajout; prix ensuite conservé en session | `bowl_add.php:19-25,28-38`; calcul `includes/bowl_functions.php:3-65`; tarifs `includes/bowl_config.php:4-10`; reprise de `prix` de session `includes/commande_functions.php:15-22` | Si le prix doit rester actualisé jusqu’au paiement, recalculer/valider le bowl à la commande. |
| A6.3 | Transaction, détails plats/bowls, panier vidé et statut par défaut | CONFORME | Transaction et écritures `includes/commande_functions.php:48-108`; panier vidé après succès `commande.php:83-94`; défaut SQL `bd/zig_healthy_db.sql:99-108` | Ajouter un test dédié pour une commande composée uniquement de bowls. |
| A6.4 | Commande composée seulement de bowls possible ? | CONFORME d’après le flux de code; cas non testé séparément | `commande.php:47-48,83-94`; insertion de bowls sans insertion de plat `includes/commande_functions.php:67-98`; table bowl `bd/zig_healthy_db.sql:129-139` | Ajouter un test d’intégration « bowl seul »; la suite actuelle teste un panier mixte (`tests/run.php:194-218`). |
| A7 | CSRF: génération, vérification POST, HTTP 419, formulaires POST | CONFORME pour les formulaires PHP analysés | `includes/config.php:21-29`; champ `includes/fonctions.php:23-30`; scanner des formulaires `tests/run.php:23-42`; `php tests/run.php` passe | Garder le contrôle commun; le scanner est une vérification statique par expression régulière et non un parseur HTML complet. Aucun formulaire POST manquant n’a été signalé par ce scanner. |
| A8.1 | Cohérence des 12 tables entre schéma complet et migrations | À CLARIFIER selon le parcours d’installation | 11 tables sont créées dans `bd/zig_healthy_db.sql:16,30,47,61,85,99,119,129,141,224,242`; `mesures_imc` est ajoutée par `bd/migration_imc.sql:11-21`. Abonnements: schéma `:224-255`, migration `bd/migration_abonnements.sql:12-34`; bowls et contact sont aussi déclarés dans le schéma et leurs migrations `IF NOT EXISTS`. | Documenter une seule séquence d’installation. Ne pas exécuter les migrations historiques sur une base fraîche. |
| A8.2 | `tentatives_connexion` dans le schéma complet | CONFORME | `bd/zig_healthy_db.sql:25-26` | Garder le schéma complet cohérent avec le helper de connexion. |
| A8.3 | ENUM des statuts | CONFORME | Commandes: `en_attente`, `confirmee`, `en_preparation`, `prete`, `livree`, `annulee` (`bd/zig_healthy_db.sql:107`); abonnements: `en_attente`, `actif`, `termine`, `annule` (`bd/zig_healthy_db.sql:249`, `bd/migration_abonnements.sql:31`). | Garder les listes PHP synchronisées avec ces ENUM. |
| A8.4 | Encodage du plat « Bœuf sauté prise de masse » | NON CONFORME | `bd/zig_healthy_db.sql:180` contient une variante avec `?`; `bd/migration_categories_repas.sql:24-25` contient la variante corrompue et la variante correctement encodée | Corriger le libellé corrompu et vérifier les données déjà importées. |
| B1 | Bandeau d’actualité | NON TROUVÉ | `includes/header.php:57-63` affiche un bandeau d’horaires; aucun bandeau d’actualité trouvé dans le code applicatif, notamment `index.php:1-25` | Ne pas annoncer cette fonctionnalité sans implémentation et preuve. |
| B2 | Répartition des commandes par profil dans le tableau de bord | CONFORME avec réserve sémantique | Requête `admin/dashboard.php:15-22`; affichage `:72-84` | La requête groupe par l’objectif actuel du compte utilisateur, pas par un profil figé au moment de chaque commande. Renommer le libellé ou enregistrer un snapshot si l’historique est requis. |
| B3 | Plats épuisés restant visibles au menu avec mention | NON TROUVÉ | `menu.php:20-25` filtre `p.disponible = 1`; aucune mention « épuisé » n’est affichée dans le parcours du menu | Retirer cette affirmation ou implémenter l’affichage des plats indisponibles avec un libellé explicite. |
| B4 | Calcul énergétique selon Mifflin–St Jeor | NON TROUVÉ | `imc.php:27-35` calcule l’IMC à partir du poids et de la taille; `includes/fonctions.php:88-98` classe l’IMC. Aucune formule Mifflin–St Jeor trouvée dans le code applicatif. | Retirer cette fonctionnalité du mémoire tant qu’elle n’est pas implémentée et testée. |
| B5 | Validation et renommage d’images envoyées dans l’administration | NON TROUVÉ | `admin/plats.php:14-39,81-94` ajoute/modifie les champs du plat sans réception d’image; aucune occurrence applicative de `move_uploaded_file`, `finfo`, `getimagesize` ou `type=file` trouvée | Ne pas annoncer l’envoi d’images; si ajouté, contrôler taille/type réel/extension et renommer côté serveur. |
| B6.1 | Transitions de statut des commandes | NON CONFORME au parcours strict | Valeurs admises `admin/commandes.php:10,17-19`; la requête ne contrôle que l’ID et la valeur, pas le statut précédent. Formulaire avec la liste complète `:134` et suivantes. | Implémenter une matrice de transitions, y compris la règle d’annulation avant livraison. |
| B6.2 | Transitions de statut des abonnements | NON CONFORME au parcours strict | Valeurs `admin/abonnements.php:10,17-19`; seule la valeur cible est vérifiée; formulaire `:82-84` | Implémenter les transitions autorisées plutôt que permettre tout changement vers toute valeur d’ENUM. |
| B7 | Remise à zéro des tentatives après réinitialisation du mot de passe | À CLARIFIER | `includes/reinitialisation.php:96-120` modifie le mot de passe et supprime le jeton, sans mettre à jour `tentatives_connexion`; le commentaire de `includes/connexion_functions.php:3` indique que le déblocage est réservé à l’administration | Décider explicitement si une réinitialisation doit remettre le compteur à zéro; elle ne débloque pas actuellement le compte. |
| B8 | Identifiants sensibles ou configuration d’accès dans les fichiers | NON CONFORME | README: lignes 136-137 (compte de démonstration en clair), ligne 121 (exemple SMTP avec valeur de démonstration); SQL: ligne 163 (compte initial avec hash); `includes/config.php:36-37` (configuration de connexion par variables et valeurs de repli); SMTP lu depuis l’environnement dans `includes/reinitialisation.php:33-42`. Aucun secret SMTP réel n’a été trouvé dans les fichiers inspectés. Valeurs omises volontairement. | Retirer les identifiants en clair du dépôt, renouveler le compte de démonstration s’il est encore actif, et garder les secrets SMTP hors des fichiers versionnés. |
| A9 | Connexion administrateur: cohérence avec le nouveau helper et le seuil 5 | NON CONFORME | `admin/login.php:32-62`; ligne `:58` incrémente le compteur avant le calcul de blocage `:59-60`, ce qui déclenche le seuil au 4e échec avec l’évaluation MySQL de gauche à droite; un compte déjà bloqué est encore incrémenté et le message est réaffecté à `:62` | Faire utiliser le helper commun et préserver le message de compte bloqué. |

### Sorties exactes des commandes demandées

Commande `php tests\test_blocage_connexion.php` :

```text
Seuil de blocage
  OK   le seuil est de 5 échecs

Compte A : échecs consécutifs
  OK   échec 1 : compte non bloqué
  OK   échec 1 : compteur = 1
  OK   échec 2 : compte non bloqué
  OK   échec 2 : compteur = 2
  OK   échec 3 : compte non bloqué
  OK   échec 3 : compteur = 3
  OK   échec 4 : compte non bloqué
  OK   échec 4 : compteur = 4
  OK   échec 5 : le compte vient d'être bloqué
  OK   échec 5 : compteur = 5 et bloque = 1

Compte B : une connexion réussie remet le compteur à zéro
  OK   4 échecs : pas encore bloqué
  OK   après connexion réussie : compteur à 0
  OK   4 nouveaux échecs : toujours pas bloqué

Indépendance des comptes
  OK   le blocage de A n'a pas bloqué B

15 tests, 0 échec(s)
```

Commande `php tests\test_fonctions.php` :

```text
Could not open input file: tests\test_fonctions.php
```

Le résultat attendu de 44 tests et de deux échecs n’est donc pas vérifiable dans cette copie, car le fichier correspondant n’existe pas. Le test existant `tests/run.php` a également été exécuté :

```text
OK : 73 assertions réussies (prix, unités, panier mixte, checkout, migrations et installation fraîche).
```

Dans `tests/run.php`, l’assertion de liste conserve les deux créneaux refusés et une assertion vérifie leur rejet (`:131-140`). Le test d’enregistrement appelle directement `enregistrer_commande()` avec « Express » (`:208-218`); il ne teste pas le contrôleur `commande.php` et ne contredit donc pas son rejet serveur.

## 3. Écarts avec ce que j’affirme

- Le fichier de tests de fonctions annoncé n’existe pas; seul le test de blocage dédié est présent.
- `objectifs_autorises()` n’applique pas le même arrondi au dixième que `determinerProfil()`. Une valeur proche de 18,5 ou 25 peut donc produire une catégorie et des objectifs autorisés différents.
- Le formulaire propose encore « Express » et « Sous 30 min », mais `commande.php` les refuse.
- Le SQL contient une variante mal encodée du nom du plat au lieu d’un libellé uniformément correct.
- La répartition du tableau de bord est calculée selon l’objectif actuel du client et non son profil à la date de chaque commande.
- Les plats indisponibles sont exclus du menu; aucun calcul Mifflin–St Jeor, aucune validation d’image envoyée et aucun bandeau d’actualité n’ont été trouvés.
- Les panneaux administratifs valident que la nouvelle valeur de statut appartient à la liste, mais pas que la transition depuis le statut actuel est permise.
- L’implémentation de connexion de l’administrateur ne suit pas le helper vérifié pour la connexion client; son ordre d’affectation SQL diffère.
- Le prix d’un plat catalogue est relu en base à la commande; le prix d’un bowl est calculé côté serveur lors de l’ajout puis conservé en session et repris à la commande, sans lecture de prix du bowl depuis la base.
- Le schéma complet contient la colonne des tentatives, mais `migration_blocage.sql` ajoute aussi des colonnes déjà présentes dans ce schéma; elle est réservée aux bases historiques.

## 4. Risques (gravité)

### Élevée

- **Compte de démonstration publié en clair** dans le README. Sa validité actuelle n’a pas été testée; s’il est encore actif et réutilisé ailleurs, son exposition augmente le risque d’accès non autorisé.
- **Connexion administrateur incohérente** : le compteur est incrémenté avant l’évaluation du seuil, ce qui déclenche le blocage au 4e échec avec l’évaluation MySQL de gauche à droite; les tentatives continuent aussi après un compte déjà bloqué et le message est écrasé.

### Moyenne

- **Transitions de statuts non contraintes** : une valeur autorisée peut être choisie depuis n’importe quel statut, y compris un retour en arrière ou une annulation après livraison.
- **Formulaire de commande incohérent** : deux délais proposés échouent côté serveur; « Pour midi » et « Ce soir » sont acceptés sans rapport avec l’heure actuelle.
- **Règles de profil IMC divergentes** près des seuils, avec impact possible sur les objectifs recommandés.
- **Migrations non interchangeables** : `migration_blocage.sql` échoue si ses colonnes existent déjà dans une installation fraîche; une mauvaise séquence d’import peut interrompre l’installation.
- **Valeur de statut du tableau de bord ambiguë** : les commandes historiques sont regroupées suivant l’objectif actuel du compte, sans historique d’objectif attaché à la commande.

### Faible / fonctionnel

- Les fonctions d’actualité, de visibilité des plats épuisés, de calcul Mifflin–St Jeor et d’envoi d’images ne sont pas prouvées par une implémentation.
- Le processus de réinitialisation du mot de passe laisse le compteur d’échecs inchangé; la politique de déblocage après récupération doit être décidée.
- Le prix d’un bowl est calculé par le serveur à l’ajout, mais provient ensuite de la session et n’est pas recalculé à la finalisation.
- Le libellé mal encodé du plat peut causer un nom incorrect ou une migration de catégorie incomplète.

## 5. Corrections proposées — non appliquées

1. Réutiliser `includes/connexion_functions.php` dans `admin/login.php` et ne pas incrémenter un compte déjà bloqué — `admin/login.php`.
2. Aligner l’arrondi de `objectifs_autorises()` sur `determinerProfil()` et ajouter les tests des limites 18,5, 25 et 30 — `includes/fonctions.php`, `tests/test_fonctions.php`.
3. Retirer les choix Express et Sous 30 min du formulaire, ou décider d’une règle qui les rend acceptables — `includes/fonctions.php`, `commande.php`.
4. Ajouter le fichier de tests de fonctions annoncé, ou mettre à jour les consignes de test — `tests/test_fonctions.php`.
5. Ajouter des tests de commande avec bowl seul et de validation serveur de chaque champ — `tests/run.php`.
6. Définir et appliquer les transitions permises à partir du statut courant — `admin/commandes.php`, `admin/abonnements.php`.
7. Choisir la politique de compteur après réinitialisation du mot de passe — `includes/reinitialisation.php`.
8. Retirer les identifiants de démonstration du README et renouveler le compte s’il est encore actif — `README.md`, compte local concerné.
9. Corriger l’encodage du nom du plat et harmoniser les scripts de migration — `bd/zig_healthy_db.sql`, `bd/migration_categories_repas.sql`.
10. Séparer clairement migrations historiques et installation fraîche, et ajouter une vérification d’état avant les `ALTER TABLE` non réexécutables — `bd/migration_blocage.sql`, `README.md`.
11. Renommer la statistique en fonction de l’objectif actuel, ou enregistrer le profil au moment de la commande — `admin/dashboard.php`, schéma de base si nécessaire.
12. Retirer du mémoire les fonctionnalités non implémentées, ou les développer avec leurs tests et preuves — `docs/rapport_memoire_base.md`, pages concernées.
13. Si les prix de bowls doivent refléter les tarifs courants, reconstruire leur prix à la validation à partir de la sélection enregistrée — `includes/commande_functions.php`, `includes/bowl_functions.php`.
