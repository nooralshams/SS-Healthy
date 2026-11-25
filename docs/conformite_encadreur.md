# Conformité aux critiques de l’encadreur — SS’Healthy

État au 25/11/2026, branche `corrections-encadreur`.

## Légende des statuts

- **Corrigée** : la preuve se trouve dans le code, les tests ou les diagrammes du dépôt.
- **Dans le mémoire** : la correction a été retrouvée dans la copie du mémoire (texte ou figure).
- **Rédigée** : le texte corrigé se trouve dans `docs/rapport_memoire_base.md` mais n'a pas été retrouvé dans le mémoire.
- **À faire** : il manque une information ou un travail qui ne peut pas être fait à partir du dépôt.

## Tests (exécution du 25/11/2026)

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
| `0bfa4a8` | Mise à jour de ce rapport |
| `c12d6f9`, `29c63e2` | Déblocage des administrateurs, **annulé** ensuite par `9c2cbb8` et `51463e8` |
| `badb23a` | Menu en rideau vert sous le logo (client ou admin connecté, espace admin) |
| `8f4b3ba` | Objectif nutritionnel choisi seulement après l’enregistrement d’un IMC ; diagramme de classes (0..1), séquence d’inscription et MLD mis à jour |
| `c612486` | Cartes de paiement lisibles, libellés des commandes en clair dans l’admin |
| `6aecc13` | Cases « Objectif Diabète » et « Plat à index glycémique faible » |
| `b42e369` | Bénéficiaire Wave et Orange Money unique : « Salma Marieme Diallo » |
| `4ee748a`, `f3b20e9`, `2267397` | Crédits des images (Pinterest, sources à compléter) et modèle SQL du smoothie |
| `9e63bda` | Espace client, tableau de bord, pictogrammes, panier mobile |
| `96cf802` | Photo actuelle affichée dans la modification d’un plat |

## Améliorations hors critiques

Ces changements ne répondent pas directement à une critique, mais renforcent le projet pour la soutenance :

- **Avertissement santé** (`imc.php`, commit `2745803`) : l’application précise qu’elle ne pose aucun diagnostic, que l’IMC ignore la masse musculaire, l’âge et la grossesse, et que les plats « diabète » ne remplacent pas un suivi médical. C’est une réponse prête si le jury pose la question de la responsabilité médicale.
- **Page d’accueil** (`index.php`, `css/style.css`, commit `182b0c6`) : le titre n’est plus masqué par l’image, et « S’abonner » est mis en avant, y compris sur mobile, où le bouton était auparavant caché.
- **Objectif après l’IMC** (`inscription.php`, `compte.php`, commit `8f4b3ba`) : un nouveau client n’a plus l’objectif « maintien » imposé ; il le choisit après avoir enregistré son IMC. Le diagramme de classes (Utilisateur 0..1 Objectif), la séquence d’inscription (message 9) et le MLD ont été corrigés en même temps.
- **Menu en rideau** (`includes/header.php`, `admin/includes/header.php`, `js/rideau.js`, commit `badb23a`) : présentation uniquement, sans effet sur les diagrammes.
- **Fonio offert** (commit `e7318fa`) : seul le libellé affiché change ; les commandes déjà passées gardent leur historique.

## Partie informatique — état final (25/11/2026)

Critiques 3, 4, 5, 6, 7, 12, 13 et 14. Diagrammes régénérés depuis le code (`docs/diagrammes/png/`, vérification ligne par ligne dans `docs/verification_diagrammes.md`). Tests du 25/11/2026 : `test_fonctions` 44/44, `test_blocage_connexion` 15/15, `run.php` 89 assertions, `test_images` 20/20.

| # | Critique | Statut | Preuve (fichier et ligne) | Reste à faire |
|---|---|---|---|---|
| 3 | Diagramme d'activité (fig. 11) | Corrigée | `docs/diagrammes/png/08_Activite_commande.png` (commit `6c1ce90`) : couloirs Client / Serveur, début, fin, décisions ; panier sans compte, puis connexion (`commande.php` l. 13-18), puis créneau ; libellés « Ajouter un plat ou un bowl au panier » et « Confirmer la commande » | Insérer le PNG à la place de la figure 11 |
| 4 | Diagramme de séquence (fig. 12) | Corrigée | `05_Sequence_commande.png` (commit `4ba6185`) : session PHP côté serveur ; `SELECT … FROM plats` dans un `loop` (`includes/commande_functions.php` l. 26) ; `BEGIN` / `COMMIT` / `ROLLBACK` (l. 49, 101, 105) ; `alt` pour l'erreur ; « Vider le panier » (`commande.php` l. 92) | Insérer le PNG à la place de la figure 12 |
| 5 | Diagramme de classes (fig. 13) | Corrigée | `03_Diagramme_de_classes.png` (commit `9985e23`) : 12 tables ; énumération `Objectif` reliée en 0..1 ; `mode`, `disponible`, `poids`, `taille` ; `Commande` 1 ◆ 1..* `LigneCommande` ← `DetailsCommande`, `DetailBowl` | Insérer le PNG (figure 15 dans le mémoire actuel) |
| 6 | Cas d'utilisation (fig. 9) | Corrigée | `01_Cas_d_utilisation.png` (commit `327cf55`) : cadre du système, généralisation Client connecté → Visiteur, Gérer le panier, Gérer son profil, Gérer les catégories, Consulter le tableau de bord, Se déconnecter, Administrateur relié à Se connecter | Insérer le PNG à la place de la figure 9 |
| 7 | Fonctionnalités non prouvées | Corrigée (code et preuves) | Blocage au 5e échec : `includes/connexion_functions.php` l. 8 (`SEUIL_BLOCAGE_CONNEXION = 5`) et l. 18-24 ; `tests/test_blocage_connexion.php` l. 48, 56-58. Répartition par objectif : `admin/dashboard.php` l. 16-19 et 92 | Rédaction : tableaux 19 à 21 du mémoire à aligner sur `docs/dictionnaire_donnees.md` |
| 12 | Test IMC 56,6 kg / 175 cm, règle unique | Corrigée | Règle : `includes/fonctions.php` l. 89 (`determinerProfil`, arrondi au dixième). Tableau IMC : `tests/test_fonctions.php` l. 21-31 (cas T1 à T6) ; 56,6 kg / 175 cm = 18,48 → 18,5 « normal » : `tests/run.php` l. 135 | — |
| 13 | Tests `determinerProfil`, annulation, créneau | Corrigée | Annulation : `tests/run.php` l. 260-280 (erreur sur une ligne → `rollBack`, aucune commande conservée). Créneau invalide « Express » et « Sous 30 min » : `tests/run.php` l. 140 ; contrôle `creneau_autorise()` : `includes/fonctions.php` l. 83 | — |
| 14 | Sécurité : serveur, CSRF, images | Corrigée | Contrôle serveur : `commande.php` l. 71 (créneau dans la liste), l. 73 (≥ 45 min), l. 75 (mode de retrait), l. 77 (paiement), l. 79 (adresse si livraison). CSRF : `includes/config.php` l. 20-30 (`hash_equals`, code 419), `includes/fonctions.php` l. 24-31 (`csrf_field`). Images : `includes/image_functions.php` l. 14 (2 Mo), l. 26-77 (extension, `getimagesize`, `finfo`), l. 81 (nom aléatoire), utilisés par `admin/plats.php` l. 35 et 97 ; `tests/test_images.php` (20/20) | — |

## Tableau de suivi

Statut vérifié le 25/11/2026 dans le code et dans la copie du mémoire `Memoire 1SShealthy Ziguinchor corrigé - avec captures.docx` (figures extraites et texte relu automatiquement). « Dans le mémoire » signifie que la correction y a été retrouvée.

| # | Critique | Statut | Preuve | Reste à faire |
|---|---|---|---|---|
| 1 | Page de garde et remerciements | Dans le mémoire | Page de garde : ISI Ziguinchor, M. David DIONE, M. Assane GUEYE, 2025-2026 ; section Remerciements présente | Harmoniser l’orthographe du nom : « MAREME SALMA DIALLO » sur la page de garde, « Salma Marieme Diallo » dans l’application |
| 2 | Captures, lien GitHub, maquettes en 5.9 | Dans le mémoire | Section 6.5 : 9 captures (figures 28 à 36), lien `https://github.com/Nooralshams/SS-Healthy` (dépôt privé) ; maquettes restées en 5.9 ; commits `c612486`, `724d42e` | Inviter l’encadreur sur le dépôt (Settings → Collaborators) |
| 3 | Diagramme d’activité (fig. 11) | Dans le mémoire | Figure 11 : couloirs Client / Système / Administrateur, début et fin, décisions, connexion avant le choix du créneau | — |
| 4 | Diagramme de séquence (fig. 12) | Corrigée (PNG régénéré), **à insérer dans le mémoire** | `diagrammes_SSHealthy.drawio` (commit `9cfc07b`) : `BEGIN`, `COMMIT`, `ROLLBACK`, session côté serveur. La figure 12 du mémoire est l’ancienne version (aucune transaction) | Exporter la page « Séquence - commande » et remplacer la figure 12 |
| 5 | Diagramme de classes (fig. 15 dans le mémoire) | Corrigée (PNG régénéré), **à insérer dans le mémoire** | Dépôt : énumération `Objectif` en 0..1 (commit `8f4b3ba`), `LigneCommande` abstraite. La figure du mémoire garde `objectif : string` et `DetailCommande` en 1..* | Exporter la page « Diagramme de classes » (après `8f4b3ba`) et remplacer la figure ; justifier `LigneCommande` |
| 6 | Diagramme de cas d’utilisation (fig. 9) | Dans le mémoire | Cadre du système, généralisation Visiteur ← Client, Se déconnecter, Gérer son profil, Consulter le tableau de bord | — |
| 7 | Fonctionnalités non prouvées | Dans le mémoire, **sauf le dictionnaire de données** | Blocage au 5e échec : `includes/connexion_functions.php:8`, `tests/test_blocage_connexion.php` (15/15). Répartition par objectif : `admin/dashboard.php` et figure 33. Bandeau d’actualité absent. Mifflin-St Jeor présenté en 1.2.3 comme non intégré au prototype | Tableau 19 du mémoire : retirer `sexe`, `date_naissance`, `niveau_activite`, `objectif_code` ; tableaux 20 et 21 : remplacer la table `objectif` par l’ENUM et corriger `mode`, `statut`, `creneau`. Référence exacte : `docs/dictionnaire_donnees.md` |
| 8 | Hypothèse 2 « non tranchée » | Dans le mémoire | « La deuxième hypothèse reste non tranchée » (chapitre 3) | — |
| 9 | Fourchette de prix comme hypothèse à tester | Dans le mémoire | « fourchette de 1 800 à 2 500 FCFA, retenue comme hypothèse » | — |
| 10 | Deux limites de l’enquête (3.4) | Dans le mémoire (titre) | Section 3.4 « Les limites de l’instrument » | Vérifier que les deux limites demandées y figurent |
| 11 | 3.5.3 : « la réponse la plus fréquente » | Dans le mémoire | Formulation retrouvée | — |
| 12 | Test IMC 56,6 kg / 175 cm et règle unique | Dans le mémoire | Code : `includes/fonctions.php` (`round($imc, 1)`). Mémoire : « 56,6 kg donne un IMC de 18,48… arrondi au dixième » | — |
| 13 | Tests `determinerProfil`, annulation, créneau | Dans le mémoire | `determinerProfil`, `rollBack` et la limite de 45 min sont cités ; `tests/test_fonctions.php`, `tests/run.php` | — |
| 14 | Sécurité : serveur, CSRF, images | Dans le mémoire | CSRF cité 5 fois ; `includes/image_functions.php`, `tests/test_images.php` (20/20) | Tester une fois l’envoi d’une image depuis l’admin |
| 15 | Calendrier 4.7 et tableau 26 | **À vérifier** | La section 4.7 est devenue « Priorisation des besoins » ; le planning est au tableau 27 (7.1.1) ; aucune mention de la 16e semaine retrouvée | Vérifier que la date de livraison de la version minimale est cohérente dans le tableau 27 |
| 16 | Sources | **Partiel** | STEPS 2015, ANSD et loi 2008-08 cités ; Pressman et Sommerville retirés ; section 1.6 « Travaux connexes » présente | Vérifier les pages citées et la réalité des sources de la 1.6 ; citer les sites d’origine des recettes et photos (`docs/credits_images.md`) |
| 17 | Style | **À faire (relecture humaine)** | — | Relire le mémoire |
| 18 | Coquilles | **À vérifier** | « de la livraison » n’apparaît plus | Relire les coquilles listées dans `docs/rapport_memoire_base.md` |
| 19 | Liste des sigles | Dans le mémoire | DER = « Dépense énergétique de repos » (utilisé en 1.2.3) ; PWA seulement en perspective | — |

## Soutenance

- Parcours de démonstration et vidéo de secours : `docs/rapport_memoire_base.md` l. 120-136. **La vidéo n’a pas été trouvée sur l’ordinateur.**
- La fonction appelée `enregistrer()` dans la critique s’appelle en réalité `enregistrer_commande()` (`includes/commande_functions.php`).
- `determinerProfil()` se trouve dans `includes/fonctions.php`.
- Questions probables du jury : `docs/preparation_jury.md`.

## Bilan

- **Corrigées et retrouvées dans le mémoire :** 1, 2, 3, 6, 8, 9, 11, 12, 13, 14, 19.
- **Corrigées dans le dépôt, à reporter dans le mémoire :** 4 et 5 (remplacer deux figures), 7 (dictionnaire de données).
- **À vérifier ou relire par Salma :** 10, 15, 16, 17, 18.
