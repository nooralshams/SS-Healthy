# Préparation de la soutenance — questions probables du jury

Chaque réponse est courte, à dire avec vos mots, et renvoie au fichier qui la prouve. Ouvrez ce fichier pendant la démonstration si on vous demande de montrer le code.

## 1. Le projet et ses choix

**Pourquoi ce projet ?**
Les habitants de Ziguinchor ont peu d’offres de repas équilibrés commandables en ligne. SS’Healthy relie un repère simple (l’IMC) à des plats adaptés, avec les moyens de paiement utilisés sur place (espèces, Wave, Orange Money).

**Pourquoi PHP procédural et pas un framework comme Laravel ?**
Choix volontaire pour une Licence : maîtriser les bases (sessions, PDO, requêtes préparées, transactions) sans couche cachée. L’application tourne sur WAMP sans installation supplémentaire. Le code est quand même découpé par rôle : `includes/fonctions.php`, `includes/commande_functions.php`, `includes/bowl_functions.php`, `includes/image_functions.php`. Passer à une architecture MVC fait partie des perspectives.

**Pourquoi MySQL ?**
Les données sont relationnelles (client, commande, lignes, plats, catégories) et l’intégrité compte : clés étrangères, transactions InnoDB. MySQL est fourni avec WAMP.

**Quelle est la différence entre le Visiteur et le Client connecté ?**
Le visiteur consulte le menu, calcule son IMC et remplit son panier sans compte. La connexion n’est exigée qu’au moment de commander (`commande.php:14-18`). C’est une généralisation dans le diagramme de cas d’utilisation : le client connecté hérite de tout ce que fait le visiteur.

## 2. IMC et objectifs

**Comment est calculé l’IMC ?**
Poids (kg) divisé par la taille (m) au carré. Exemple : 56,6 / 1,75² = 18,48.

**Expliquez `determinerProfil()` ligne par ligne.** (`includes/fonctions.php:89`)
1. Si la valeur n’est pas numérique, elle renvoie `invalide`.
2. Elle arrondit l’IMC au dixième.
3. Elle classe : moins de 18,5 = insuffisance, moins de 25 = normal, moins de 30 = surpoids, sinon obésité.
Elle classe un IMC déjà calculé ; elle ne le calcule pas.

**Pourquoi arrondir avant de classer ?**
Pour qu’une seule règle s’applique partout. L’IMC est affiché et enregistré au dixième (`DECIMAL(4,1)`). Sans arrondi, 18,49 serait affiché « 18,5 » mais classé « insuffisance » : l’utilisateur verrait une contradiction. Le cas est testé (`tests/test_fonctions.php`, `tests/run.php:134-135`).

**Votre application donne-t-elle des conseils médicaux ?**
Non. Elle propose des objectifs nutritionnels généraux et le dit clairement : un encadré sur la page IMC précise qu’elle ne pose aucun diagnostic, que l’IMC ignore la masse musculaire, l’âge et la grossesse, et qu’une personne diabétique doit consulter un médecin (`imc.php`, encadré « Information santé importante »).

**Pourquoi un nouveau client n’a-t-il pas d’objectif ?**
Parce que l’objectif doit découler de l’IMC. À l’inscription, la colonne `objectif` reste vide (NULL) ; « Mon compte » affiche « À définir après le calcul de votre IMC ». Le choix se fait sur la page IMC, parmi les objectifs compatibles avec le résultat (`inscription.php`, `compte.php`, `imc.php`). C’est pourquoi le diagramme de classes indique 0..1 entre Utilisateur et Objectif.

**Pourquoi l’objectif « diabète » est-il proposé à tous les profils ?**
Le diabète ne dépend pas de l’IMC : une personne de corpulence normale peut être diabétique. L’objectif correspond à des plats à index glycémique faible ; c’est l’utilisateur qui le choisit (`objectifs_autorises()`, `includes/fonctions.php:34`).

## 3. Commande et données

**Expliquez `enregistrer_commande()` ligne par ligne.** (`includes/commande_functions.php:48`)
1. `beginTransaction()` démarre une transaction.
2. Un `INSERT` crée la commande ; `lastInsertId()` récupère son identifiant.
3. Une boucle insère chaque article : `details_commande` pour un plat, `details_commande_bowls` pour un bowl (composition en JSON).
4. `commit()` valide le tout.
5. En cas d’erreur, `rollBack()` annule tout, puis l’erreur est relancée.
Résultat : jamais de commande à moitié enregistrée. C’est testé (`tests/run.php:277-280`).

**Un client peut-il modifier le prix dans son navigateur ?**
Non. Le panier est stocké dans la session PHP, côté serveur. À chaque affichage et à la validation, les prix des plats sont relus en base (`reconstruire_articles_panier()`), et le prix d’un bowl est calculé par le serveur à partir de `includes/bowl_config.php` (`preparer_selection_bowl()`).

**Que se passe-t-il si un plat devient indisponible pendant que le client commande ?**
La commande est refusée avec un message, car la disponibilité est relue en base au moment de valider (`commande.php`, contrôle avant l’enregistrement).

**Pourquoi un délai minimum de 45 minutes ?**
C’est le temps de préparation. Les créneaux trop courts (« Express », « Sous 30 min ») ont été retirés et sont refusés côté serveur, même si quelqu’un les envoie à la main (`creneau_autorise()`, testé dans `tests/run.php:140-141`).

**Comment évoluent les statuts d’une commande ?**
En attente → confirmée → en préparation → prête → livrée, avec annulation possible avant la livraison. Le serveur refuse tout saut d’étape (`transitions_statuts_commande()`, `admin/includes/admin.php:35`).

**Pourquoi le paiement Wave / Orange Money n’est-il pas automatique ?**
Une intégration officielle demande un compte marchand et une API que nous n’avions pas. Le paiement est donc vérifié par l’administrateur, et l’application ne demande jamais de données bancaires. C’est une perspective d’amélioration.

**Votre diagramme de classes ne colle pas exactement à vos tables. Pourquoi ?**
Il les reflète, avec deux choix de modélisation. `Objectif` est une énumération, comme l’ENUM en base. `LigneCommande` est une classe abstraite qui regroupe les lignes de plats et de bowls : une commande contient au moins une ligne (1..*), mais une commande de bowls seuls n’a aucune ligne dans `details_commande`.

## 4. Sécurité

**Comment protégez-vous les mots de passe ?**
Ils sont hachés avec `password_hash()` et vérifiés avec `password_verify()`. On ne stocke jamais le mot de passe lui-même (`inscription.php:36`, `connexion.php:35`).

**Et les injections SQL ?**
Toutes les requêtes qui reçoivent des données utilisateur sont des requêtes préparées PDO, avec des paramètres `?`.

**Qu’est-ce que le CSRF et comment le bloquez-vous ?**
C’est une attaque où un autre site fait envoyer un formulaire à votre place. Chaque formulaire contient un jeton aléatoire stocké en session ; le serveur le compare avec `hash_equals()` et refuse la requête (code 419) s’il ne correspond pas (`includes/config.php:21-31`).

**Que se passe-t-il après 5 mauvais mots de passe ?**
Le compte est bloqué ; une connexion réussie remet le compteur à zéro. La règle est la même pour les clients et l’administrateur (`includes/connexion_functions.php`, 15 tests dans `tests/test_blocage_connexion.php`). L’administrateur peut débloquer un client depuis `admin/clients.php`.

**Et si c’est l’administrateur qui est bloqué ?**
Limite connue : il n’existe pas d’écran pour débloquer un administrateur. Il faut remettre `bloque` et `tentatives_connexion` à 0 dans phpMyAdmin. À présenter comme une amélioration à prévoir.

**Comment sécurisez-vous l’envoi d’images ?**
Taille limitée à 2 Mo, extension autorisée (jpg, png, webp), contenu vérifié comme une vraie image, cohérence entre l’extension et le contenu, et nom de fichier aléatoire : un fichier `.php` ne peut pas être déposé (`includes/image_functions.php`, 20 tests dans `tests/test_images.php`).

**Comment fonctionne « mot de passe oublié » ?**
Un lien à usage unique est envoyé par e-mail. Seule son empreinte SHA-256 est stockée en base, et il expire au bout d’une heure (`includes/reinitialisation.php`).

**Et la session ?**
Le cookie est `HttpOnly` (illisible par JavaScript) et `SameSite=Lax` ; l’identifiant de session est régénéré à la connexion (`session_regenerate_id(true)`), ce qui empêche la fixation de session.

## 5. Tests et qualité

**Comment avez-vous testé l’application ?**
Quatre suites automatiques, soit 168 vérifications : `tests/test_fonctions.php` (44), `tests/test_blocage_connexion.php` (15), `tests/test_images.php` (20), `tests/run.php` (89). `run.php` crée une base temporaire, importe le schéma et les migrations, passe des commandes complètes, puis supprime la base.

**Pourquoi le cas 56,6 kg / 175 cm ?**
Il donne 18,48, arrondi à 18,5 : exactement la limite entre insuffisance et corpulence normale. C’est là que les erreurs d’arrondi apparaissent.

## 6. Enquête

**Votre échantillon est-il représentatif ?**
Pas entièrement, et je l’indique dans les limites (3.4) : aucune question ne vérifiait que le répondant vit ou travaille à Ziguinchor, et les tranches de budget de la question 6 laissent des trous. Les résultats sont des tendances, pas des certitudes.

**L’hypothèse 2 est-elle confirmée ?**
Non, elle est non tranchée : 33,9 % pour la qualité contre 30,7 % pour le prix, un écart trop faible pour conclure.

**D’où vient le prix de 1 800 à 2 500 FCFA ?**
C’est le budget que les répondants jugent nécessaire, pas un prix testé. Je le présente comme une hypothèse à vérifier.

## 7. Bilan et perspectives

**Qu’est-ce qui n’est pas terminé ?**
Paiement mobile automatique, mise en ligne, écran de déblocage administrateur, application installable (PWA).

**Que feriez-vous avec plus de temps ?**
Intégrer l’API marchande Wave ou Orange Money, passer à une architecture MVC, mettre en ligne avec HTTPS, ajouter des notifications de statut au client.

**Qu’avez-vous appris ?**
À répondre avec vos propres mots. Pistes : sécuriser une application web, garder un code testé et cohérent avec sa documentation, mener une enquête et en reconnaître les limites.

## Conseils pour la démonstration

- Suivez l’ordre demandé : IMC → menu → panier → connexion → commande → changement de statut dans l’admin → statut mis à jour côté client.
- Préparez un compte client et un compte admin de démonstration, avec des mots de passe gardés hors du projet.
- Ayez la vidéo de secours prête et testée.
- Si une question vous surprend, dites « je vais vous montrer dans le code » et ouvrez le fichier cité ci-dessus.
