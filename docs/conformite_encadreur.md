# Conformité aux critiques de l’encadreur — SS’Healthy

État au 24/11/2026, branche `corrections-encadreur`.

## Légende des statuts

- **Corrigée** : la preuve se trouve dans le code, les tests ou les diagrammes du dépôt.
- **Rédigée** : le texte corrigé se trouve dans `docs/rapport_memoire_base.md`. Il reste à le reporter dans le mémoire, qui n’est pas dans le dépôt et n’a donc pas pu être vérifié ici.
- **À faire** : il manque une information ou un travail qui ne peut pas être fait à partir du dépôt.

## Tests (exécution du 24/11/2026)

| Commande | Résultat |
|---|---|
| `php tests/test_fonctions.php` | 44 tests, 0 échec |
| `php tests/test_blocage_connexion.php` | 15 tests, 0 échec |
| `php tests/test_images.php` | 20 tests, 0 échec |
| `php tests/run.php` | 89 assertions réussies |

## Commits de cette série

| Commit | Contenu |
|---|---|
| `802a297` | Règles IMC (arrondi) et créneaux alignés |
| `efe29a4` | Tests de `determinerProfil` et des créneaux |
| `9174ed1` | Blocage après 5 échecs partagé avec la connexion admin |
| `26dd1ab` | Test d’une commande composée uniquement de bowls |
| `772afee` | Transitions de statuts de commande et d’abonnement |
| `a0b8693` | Notes du mémoire alignées sur le comportement vérifié |
| `f406e39` | Protéine premium à 500 F CFA |
| `ea35f9e` | README, schéma SQL sans hash de mot de passe, `.gitignore` |
| `e33c489` | Envoi d’images de plats contrôlé et tests associés |
| `9cfc07b` | Figures 9, 11, 12 et 13 corrigées, encodage des libellés réparé |
| `78613a1` | Création de ce rapport de conformité |
| `182b0c6` | Page d’accueil : titre dégagé de l’image, carte « S’abonner » avec le prix d’appel lu en base |
| `e7318fa` | Extra offert du composeur de bowls : fonio au lieu de quinoa |
| `2745803` | Avertissement santé sur la page IMC |
| `caf560c` | Questions probables du jury et réponses (`docs/preparation_jury.md`) |

## Améliorations hors critiques

Ces changements ne répondent pas directement à une critique, mais renforcent le projet pour la soutenance :

- **Avertissement santé** (`imc.php`, commit `2745803`) : l’application précise qu’elle ne pose aucun diagnostic, que l’IMC ignore la masse musculaire, l’âge et la grossesse, et que les plats « diabète » ne remplacent pas un suivi médical. C’est une réponse prête si le jury pose la question de la responsabilité médicale.
- **Page d’accueil** (`index.php`, `css/style.css`, commit `182b0c6`) : le titre n’est plus masqué par l’image, et « S’abonner » est mis en avant, y compris sur mobile, où le bouton était auparavant caché.
- **Fonio offert** (commit `e7318fa`) : seul le libellé affiché change ; les commandes déjà passées gardent leur historique.

## Tableau de suivi

| # | Critique | Statut | Preuve | Reste à faire |
|---|---|---|---|---|
| 1 | Page de garde et remerciements | À faire | `docs/rapport_memoire_base.md` l. 3-15 : champs « À compléter » | Renseigner l’université, l’UFR, le département, l’établissement, l’encadreur, l’année et les remerciements |
| 2 | Captures, lien GitHub, maquettes en 5.9 | À faire | `docs/rapport_memoire_base.md` l. 35-50 : liste des 8 captures et emplacement des maquettes | Prendre les captures sur l’application lancée. Le dépôt n’a pas encore de remote : créer le dépôt GitHub et ajouter son URL |
| 3 | Diagramme d’activité (fig. 11) | Corrigée | `diagrammes_SSHealthy.drawio`, page « Activité - commande » (commit `9cfc07b`) : couloirs Client / Serveur, nœuds de début et de fin, décisions. Le créneau est choisi après la connexion, comme dans le code (`commande.php:14-18` redirige vers la connexion). Les libellés « Ajouter un plan » et « Commander la base » sont remplacés par « Ajouter un plat ou un bowl au panier » et « Confirmer la commande » | Remplacer la figure 11 dans le mémoire et mettre le tableau 13 et les maquettes dans le même ordre (connexion, puis créneau) |
| 4 | Diagramme de séquence (fig. 12) | Corrigée | Page « Séquence - commande » (commit `9cfc07b`) : session PHP placée sur le serveur (messages 3 et 17) ; lecture des prix en base (4-5 et 9-10) ; `BEGIN` (12), `COMMIT` (16), `ROLLBACK` (19) ; fragments `loop` et `alt`. Le message « Vider le panier (session PHP) » porte maintenant le numéro 17, car les messages ont été renumérotés | Remplacer la figure 12 dans le mémoire |
| 5 | Diagramme de classes (fig. 13) | Corrigée | Page « Diagramme de classes » (commit `9cfc07b`) : association `Utilisateur` 0..* — 1 `Objectif` (énumération, comme l’ENUM en base) ; `mode_retrait`, `disponible`, `poids`, `taille` présents ; attributs repris de `bd/zig_healthy_db.sql` et `bd/migration_imc.sql`. La multiplicité 1..* est placée entre `Commande` et `LigneCommande`, classe abstraite dont héritent `DetailsCommande` et `DetailBowl` | Remplacer la figure 13 dans le mémoire et justifier ce choix : une commande composée uniquement de bowls n’a aucune ligne `details_commande` (cas testé dans le commit `26dd1ab`), donc 1..* directement vers `DetailsCommande` serait faux |
| 6 | Diagramme de cas d’utilisation (fig. 9) | Corrigée | Page « Cas d’utilisation » (commit `9cfc07b`) : cadre du système, généralisation Client connecté → Visiteur, cas Gérer le panier, Gérer son profil, Gérer les catégories, Consulter le tableau de bord, Se déconnecter, et Administrateur relié à Se connecter | Remplacer la figure 9 dans le mémoire |
| 7 | Fonctionnalités non prouvées | Corrigée (preuves), Rédigée (retraits) | Blocage : `includes/connexion_functions.php:8` (`SEUIL_BLOCAGE_CONNEXION = 5`) et `tests/test_blocage_connexion.php` (15/15). Répartition par objectif : `admin/dashboard.php:16-19, 72`. Absents du code : bandeau d’actualité (seul `includes/header.php:57` affiche les horaires), plats épuisés visibles (`menu.php:25` filtre sur `disponible = 1`), Mifflin-St Jeor. Consignes de retrait : `docs/rapport_memoire_base.md` l. 172-178 | Dans le mémoire, retirer le bandeau d’actualité, les plats épuisés visibles et Mifflin-St Jeor. Joindre la sortie du test de blocage et une capture du tableau de bord |
| 8 | Hypothèse 2 « non tranchée » | Rédigée | `docs/rapport_memoire_base.md` l. 27-29 | Reporter dans le mémoire |
| 9 | Fourchette de prix comme hypothèse à tester | Rédigée | `docs/rapport_memoire_base.md` l. 21 | Reporter dans le mémoire |
| 10 | Deux limites de l’enquête (3.4) | Rédigée | `docs/rapport_memoire_base.md` l. 17-19 | Reporter dans le mémoire |
| 11 | 3.5.3 : « la réponse la plus fréquente » | Rédigée | `docs/rapport_memoire_base.md` l. 23-25 | Reporter dans le mémoire |
| 12 | Test IMC 56,6 kg / 175 cm et règle unique | Corrigée (code), Rédigée (texte) | Une seule règle, l’arrondi au dixième avant classement : `includes/fonctions.php:89` (commit `802a297`). Tests : `tests/run.php:134-135`, `tests/test_fonctions.php:23, 54`. Texte : `docs/rapport_memoire_base.md` l. 54-60 | Reporter le tableau 24 corrigé dans le mémoire |
| 13 | Tests `determinerProfil`, annulation de transaction, créneau de moins de 45 min | Corrigée | Cas T1 à T6 : `tests/test_fonctions.php` (commit `efe29a4`). Annulation : `tests/run.php:277-280`. Créneau : `tests/run.php:140-141` | Reporter les résultats ci-dessus dans le mémoire |
| 14 | Sécurité : contrôles côté serveur, CSRF, images | Corrigée | Créneau, mode de retrait et paiement : `commande.php:71-78`. CSRF : `includes/config.php:22-27`, `includes/fonctions.php:24-29`. Images : `includes/image_functions.php:26-77` (taille 2 Mo, extension, type réel par `getimagesize` et `finfo`, cohérence extension/contenu) et `:80-82` (nom aléatoire), utilisés par `admin/plats.php:35, 97` ; tests : `tests/test_images.php` (commit `e33c489`). Extraits : `docs/rapport_memoire_base.md` l. 81-108 | Reporter les extraits dans le mémoire. Tester une fois l’envoi d’une image depuis l’interface admin |
| 15 | Calendrier 4.7 et tableau 26 | Rédigée | `docs/rapport_memoire_base.md` l. 31-33 : la 16e semaine est retenue | Reporter dans le mémoire |
| 16 | Sources | À faire | `docs/rapport_memoire_base.md` l. 180-190 : consignes seulement | Vérifier STEPS 2015 et ANSD dans les publications originales et citer la page. Supprimer les phrases de réserve. Citer ou retirer Pressman, Sommerville et la loi 2008-08. Rédiger « Travaux connexes » à partir de sources réelles |
| 17 | Style | À faire | `docs/rapport_memoire_base.md` l. 192-194 : consigne seulement | Relire le mémoire |
| 18 | Coquilles | Rédigée | `docs/rapport_memoire_base.md` l. 196-202. Aucune de ces coquilles n’apparaît dans le code PHP | Corriger dans le mémoire ; décider du sens de « de la livraison » (1.3.1) |
| 19 | Liste des sigles | Rédigée | `docs/rapport_memoire_base.md` l. 159-170 | Vérifier que DER signifie bien « Diagramme Entité-Relation » dans le mémoire. Aucune PWA n’est implémentée (ni manifest ni service worker) : ne citer PWA qu’en perspective |

## Soutenance

- Parcours de démonstration et vidéo de secours : `docs/rapport_memoire_base.md` l. 120-136.
- La fonction appelée `enregistrer()` dans la critique s’appelle en réalité `enregistrer_commande()` (`includes/commande_functions.php:48`). Explication ligne par ligne : l. 138-149.
- `determinerProfil()` se trouve dans `includes/fonctions.php:89`. Explication : l. 151-157.
- Questions probables du jury, avec des réponses qui renvoient au code : `docs/preparation_jury.md`.

## Bilan

- **Corrigées dans le dépôt :** 3, 4, 5, 6, 13, 14, ainsi que le code des critiques 7 et 12.
- **Rédigées, à reporter dans le mémoire :** 7 (retraits), 8, 9, 10, 11, 12 (texte), 15, 18, 19.
- **À faire, avec des informations ou une relecture propres au mémoire :** 1, 2, 16, 17.
