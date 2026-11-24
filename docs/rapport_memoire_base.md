# Conception et réalisation d’une application web de commande de repas équilibrés à Ziguinchor — cas de SS’Healthy

## Page de garde

- Université : **À compléter**
- UFR : **À compléter**
- Département : **À compléter**
- Établissement : **À compléter**
- Étudiant(e) : **À compléter**
- Encadreur : **À compléter**
- Année universitaire : **À compléter**

## Remerciements

À compléter avec les personnes ayant réellement contribué au projet.

## 3.4 Limites de l’enquête

Le questionnaire ne vérifie pas explicitement que chaque répondant vit ou travaille à Ziguinchor. Les tranches de la question 6 ne couvrent pas entièrement les intervalles proposés : elles laissent notamment des trous entre 2 500 et 3 000 FCFA, puis entre 4 500 et 5 000 FCFA. Ces deux limites peuvent introduire un biais de représentativité géographique et empêcher une estimation continue du budget.

La fourchette observée de 1 800 à 2 500 FCFA correspond à une réponse de budget nécessaire, et non à un prix acceptable. Elle doit donc être présentée comme une hypothèse à tester lors d’une enquête ultérieure.

## 3.5.3 Résultat principal

À 45,7 %, la modalité observée est la réponse la plus fréquente. Cette proportion ne constitue pas à elle seule une majorité absolue.

## Hypothèse 2

Avec 33,9 % pour le critère qualité contre 30,7 % pour le critère prix, l’hypothèse de priorité du prix n’est pas confirmée. Elle est **non tranchée**, car l’écart entre les deux réponses est faible et le questionnaire ne permet pas d’établir une différence statistiquement significative ni un classement stable.

## 4.7 Calendrier

Le tableau 26 retient une livraison de la version minimale à partir de la 16e semaine. La section doit donc être cohérente avec cette échéance : la version minimale n’est pas annoncée dès la fin du deuxième mois. La phase de développement minimal reste planifiée pour les semaines 15 à 16.

## 5.5.3 Captures de réalisation

Insérer les captures suivantes, prises sur l’application réellement lancée :

1. Page d’accueil.
2. Catalogue des plats et filtres.
3. Composeur de bowl.
4. Panier et prix recalculés.
5. Finalisation de commande avec créneau et mode de retrait.
6. Tableau de bord client.
7. Tableau de bord administrateur.
8. Gestion des plats et des commandes.

Lien du dépôt GitHub : **À compléter — URL publique du dépôt**.

Les maquettes restent dans la partie conception, section 5.9.

## 6. Tests

### Tableau 24 — Test de la limite IMC

La règle retenue utilise la valeur arrondie au dixième avant la classification. La mesure de frontière est donc testée avec 56,6 kg et 175 cm :

IMC = 56,6 / 1,75² = 18,48, arrondi à 18,5.

Résultat attendu : corpulence normale. Une valeur non arrondie de 18,49 peut donc être affichée comme 18,5 et classée comme normale ; cela évite une contradiction entre l’arrondi affiché et la catégorie.

Le tableau 22 doit présenter une ligne de test par cas de `determinerProfil` :

| Cas | Valeur IMC | Profil attendu |
|---|---:|---|
| T1 | 17,0 | Insuffisance pondérale |
| T2 | 18,49 | Corpulence normale |
| T3 | 24,9 | Corpulence normale |
| T4 | 25,0 | Surpoids |
| T5 | 29,9 | Surpoids |
| T6 | 30,0 | Obésité |

Ces six cas sont couverts par `tests/test_fonctions.php`. Le test de frontière 56,6 kg / 175 cm est également présent dans `tests/run.php`.

`tests/run.php` vérifie l’annulation d’une transaction lorsqu’un plat inexistant est soumis et vérifie que les créneaux `Express` et `Sous 30 min` sont refusés côté serveur. Un panier composé uniquement d’un bowl personnalisé est aussi testé.

Les résultats de test à reporter dans le mémoire doivent être ceux de l’exécution finale avant dépôt; ne pas transformer ces tests automatisés en résultats d’enquête.

## 7. Sécurité

Chaque formulaire POST contient un jeton CSRF généré dans la session PHP :

```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_POST['csrf_token'] ?? '';
if (!is_string($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(419);
    exit('Votre session de formulaire a expiré.');
}
```

Le code vérifie le jeton avec `hash_equals()` et renvoie le statut HTTP 419 en cas d’échec. Le créneau et le mode de retrait sont également revalidés côté serveur dans `commande.php` avant l’appel transactionnel.

Les images de plats envoyées depuis `admin/plats.php` sont contrôlées par `includes/image_functions.php` avant d’être enregistrées (extrait simplifié) :

```php
if ($taille <= 0 || $taille > TAILLE_MAX_IMAGE_PLAT) { /* refus : 2 Mo maximum */ }
$extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
if (!isset(EXTENSIONS_IMAGE_PLAT[$extension])) { /* refus : jpg, jpeg, png, webp */ }
$mime = getimagesize($fichier['tmp_name'])['mime'];          // contenu réel
// finfo_file() doit donner le même type ; l’extension doit correspondre au contenu
$nom = 'plat_' . bin2hex(random_bytes(16)) . '.' . $extension; // nom aléatoire
move_uploaded_file($fichier['tmp_name'], $dossier . '/' . $nom);
```

Le nom envoyé par l’utilisateur n’est jamais réutilisé, ce qui empêche le dépôt d’un fichier `.php`. Ces règles sont couvertes par `tests/test_images.php` (20 tests).

Le bandeau présent dans `includes/header.php` concerne les horaires; aucun bandeau d’actualité n’a été identifié. Ne pas le présenter comme une actualité dynamique.

Le tableau de bord admin regroupe les commandes par objectif client. Les captures de réalisation doivent montrer cette répartition avec des données de test lisibles.

Les plats dont `disponible` vaut 0 sont exclus du menu par la requête de `menu.php`. Ne pas affirmer qu’un plat épuisé reste affiché dans le menu.

Aucun calcul des besoins énergétiques selon Mifflin–St Jeor n’a été identifié dans le code examiné. Retirer cette affirmation ou implémenter et tester la formule.

Le fichier `diagrammes_SSHealthy.drawio` contient des diagrammes de conception, mais pas de diagrammes d’état distincts pour le cycle de commande et celui d’abonnement. Ajouter ces diagrammes si le mémoire les annonce.

## Préparation de la soutenance

### Démonstration en direct

Répéter le parcours complet dans cet ordre, avec un compte client de démonstration et des données de test :

1. Saisir le poids et la taille, calculer l’IMC et expliquer le profil affiché.
2. Ouvrir le menu, montrer les plats proposés pour l’objectif et en ajouter un au panier.
3. Vérifier la quantité et le total dans le panier.
4. Se connecter avec le compte client de démonstration.
5. Valider la commande avec un créneau autorisé (au moins 45 minutes), un mode de retrait et un moyen de paiement.
6. Passer à l’espace administrateur dans une autre fenêtre ou un autre profil de navigateur, ouvrir la gestion des commandes et changer le statut de la commande.
7. Revenir au compte client et montrer le statut mis à jour dans l’historique.

Avant la soutenance, vérifier le serveur Apache, MySQL, la connexion à la base, les comptes de démonstration, un plat disponible et l’accès aux espaces client et administrateur. Ne pas afficher ni utiliser des identifiants personnels ou des données réelles.

Enregistrer une courte vidéo de secours du même parcours, avec les étapes et résultats lisibles. La garder localement et tester sa lecture; l’utiliser uniquement si un problème technique empêche la démonstration en direct.

### Explication des fonctions

Le nom réellement présent dans le projet est `enregistrer_commande()` (et non `enregistrer()`). Dans `includes/commande_functions.php`, son déroulement est :

1. `beginTransaction()` ouvre une transaction afin que la commande et ses lignes soient enregistrées ensemble.
2. Une requête préparée insère les informations de la commande; `lastInsertId()` récupère son identifiant.
3. Les requêtes préparées pour les détails de plats et de bowls sont exécutées selon le type de chaque article.
4. La composition d’un bowl est convertie en JSON; une erreur d’encodage déclenche une exception.
5. `commit()` valide l’ensemble, puis l’identifiant est retourné.
6. En cas d’exception, `rollBack()` annule les écritures si la transaction est toujours ouverte, puis l’erreur est relancée pour être traitée par la page appelante.

Le contrôle des champs, du créneau, du mode de retrait et la reconstitution du panier avec les prix en base ont lieu dans `commande.php` avant l’appel à `enregistrer_commande()`.

Dans `includes/fonctions.php`, `determinerProfil($imc)` :

1. vérifie que la valeur est numérique; sinon, retourne `invalide`;
2. convertit la valeur en nombre et l’arrondit au dixième;
3. classe le résultat : inférieur à 18,5 (`insuffisance`), inférieur à 25 (`normal`), inférieur à 30 (`surpoids`), sinon (`obesite`).

Par exemple, 18,49 est arrondi à 18,5 et classé `normal`, conformément à la règle utilisée par le projet. Expliquer aussi que cette fonction classe l’IMC; elle ne calcule pas l’IMC à partir du poids et de la taille.

## Liste des sigles

| Sigle | Signification |
|---|---|
| API | Application Programming Interface |
| CSRF | Cross-Site Request Forgery |
| DER | Diagramme Entité-Relation |
| JSON | JavaScript Object Notation |
| MoSCoW | Must, Should, Could, Won’t |
| PWA | Progressive Web App |

Les sigles AJAX, MNT et PME ne sont pas retenus.

## Fonctionnalités à ne conserver dans le texte qu’avec une preuve

- Blocage après cinq échecs : le helper commun et `tests/test_blocage_connexion.php` testent le seuil de cinq échecs; `connexion.php` et `admin/login.php` utilisent ce helper. Joindre le résultat du test et la capture correspondante.
- Bandeau d’actualité : non trouvé; le bandeau de `includes/header.php` affiche les horaires. Retirer cette fonctionnalité du périmètre annoncé ou l’implémenter.
- Répartition des commandes par objectif : la requête et l’affichage sont présents dans le tableau de bord administrateur; joindre une capture avec des données de test.
- Plats épuisés restant visibles : retirer cette affirmation si le menu continue d’appliquer `WHERE p.disponible = 1`.
- Calcul des besoins énergétiques selon Mifflin–St Jeor : aucune implémentation vérifiée; retirer ou ajouter le code et les tests.

## Sources

Les données STEPS 2015 et ANSD doivent être vérifiées sur leurs publications originales, avec indication de l’édition, de la page ou du tableau cité.

Ne conserver dans le texte que des formulations vérifiées. Les réserves personnelles telles que « si notre lecture est exacte », « nous n’avons pas pu vérifier ce chiffre » ou « si nous avons bien suivi l’actualité » doivent être supprimées.

Pressman, Sommerville et la loi 2008-08 ne doivent être cités dans le texte que si leurs idées ou dispositions sont effectivement utilisées. Sinon, les retirer de la bibliographie.

## 1. Travaux connexes

Cette section présente les solutions comparables relatives à la commande de repas, au suivi nutritionnel et aux applications PWA. Les sources consultées doivent être identifiées, avec les points communs, les différences et l’apport à SS’Healthy.

## Style et qualité rédactionnelle

La rédaction finale doit rester impersonnelle. Supprimer les anecdotes, les questions rhétoriques, les expressions « honnêtement », « les médecins de notre entourage », les émojis et toute digression sans lien avec l’analyse.

## Coquilles à corriger

- « Stock dans la session » : remplacer par « Stockage dans la session » ou par « Stock dans la base de données », selon le besoin.
- « Verifie » : « Vérifie ».
- « Tout les jours » : « Tous les jours ».
- « de la livraison » : vérifier le sens et supprimer la répétition ou le complément incorrect.
- « version manuscrite » : « version minimale ».
