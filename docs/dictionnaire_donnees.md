# Dictionnaire de données — SS'Healthy

Généré le 25/11/2026 à partir du schéma réel : `bd/zig_healthy_db.sql` puis `migration_imc.sql`, `migration_bowls_personnalises.sql`, `migration_paiement_contact.sql` et `migration_reinitialisation_mot_de_passe.sql` (même ordre que `tests/run.php`). Base temporaire créée puis supprimée ; `zig_healthy_db` n'est pas lue.

**12 tables** : `abonnements`, `categories`, `commandes`, `details_commande`, `details_commande_bowls`, `formules_abonnement`, `jetons_reinitialisation`, `messages_contact`, `mesures_imc`, `plat_objectifs`, `plats`, `utilisateurs`.

Valeurs fixées par le code : créneaux « Sous 1 h », « Sous 2 h », « Pour midi », « Ce soir » (`includes/fonctions.php`, fonction `creneaux_disponibles()`, colonne `commandes.creneau`) ; IMC arrondi à une décimale avant classement (`round($imc, 1)`) ; compte bloqué au 5e échec de connexion (`SEUIL_BLOCAGE_CONNEXION = 5`).

## Table `abonnements`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `utilisateur_id` | `int` | non |  | Index, → `utilisateurs.id` (suppression : CASCADE) |  |
| `formule_id` | `int` | non |  | Index, → `formules_abonnement.id` (suppression : NO ACTION) |  |
| `date_souscription` | `datetime` | oui | CURRENT_TIMESTAMP |  |  |
| `date_debut` | `date` | non |  |  |  |
| `date_fin` | `date` | non |  |  |  |
| `statut` | `enum('en_attente','actif','termine','annule')` | oui | en_attente |  |  |
| `adresse_livraison` | `varchar(255)` | oui |  |  |  |
| `prix` | `decimal(10,0)` | non |  |  | Prix payé en FCFA au moment de la souscription |

## Table `categories`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `nom` | `varchar(50)` | non |  |  |  |
| `description` | `text` | oui |  |  |  |

## Table `commandes`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `utilisateur_id` | `int` | non |  | Index, → `utilisateurs.id` (suppression : CASCADE) |  |
| `date_commande` | `datetime` | oui | CURRENT_TIMESTAMP |  |  |
| `creneau` | `varchar(20)` | non |  |  | Délai souhaité pour le retrait ou la livraison |
| `mode` | `enum('sur_place','a_emporter','livraison')` | oui | a_emporter |  |  |
| `mode_paiement` | `enum('especes','wave','orange_money')` | non | especes |  |  |
| `adresse_livraison` | `varchar(255)` | oui |  |  |  |
| `statut` | `enum('en_attente','confirmee','en_preparation','prete','livree','annulee')` | oui | en_attente |  |  |
| `total` | `decimal(10,0)` | non | 0 |  |  |

## Table `details_commande`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `commande_id` | `int` | non |  | Index, → `commandes.id` (suppression : CASCADE) |  |
| `plat_id` | `int` | non |  | Index, → `plats.id` (suppression : NO ACTION) |  |
| `quantite` | `int` | non | 1 |  |  |
| `prix_unitaire` | `decimal(10,0)` | non |  |  |  |

## Table `details_commande_bowls`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `commande_id` | `int` | non |  | Index, → `commandes.id` (suppression : CASCADE) |  |
| `nom` | `varchar(120)` | non |  |  |  |
| `composition` | `text` | non |  |  | Composition serialisee en JSON |
| `quantite` | `int` | non | 1 |  |  |
| `prix_unitaire` | `decimal(10,0)` | non |  |  |  |

## Table `formules_abonnement`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `nom` | `varchar(100)` | non |  |  |  |
| `description` | `text` | oui |  |  |  |
| `nb_repas_jour` | `int` | non | 1 |  | Nombre de repas par jour |
| `prix` | `decimal(10,0)` | non |  |  | Prix en FCFA |
| `duree_jours` | `int` | non | 7 |  | Durée de la formule en jours |
| `icone` | `varchar(10)` | oui | ⚡ |  |  |
| `actif` | `tinyint(1)` | oui | 1 |  | 1 = visible sur le site |

## Table `jetons_reinitialisation`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `utilisateur_id` | `int` | non |  | Unique, → `utilisateurs.id` (suppression : CASCADE) |  |
| `empreinte` | `char(64)` | non |  | Unique |  |
| `expire_le` | `datetime` | non |  | Index |  |
| `cree_le` | `datetime` | non | CURRENT_TIMESTAMP |  |  |

## Table `messages_contact`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `utilisateur_id` | `int` | oui |  | Index, → `utilisateurs.id` (suppression : SET NULL) |  |
| `nom` | `varchar(100)` | non |  |  |  |
| `email` | `varchar(190)` | non |  |  |  |
| `telephone` | `varchar(30)` | oui |  |  |  |
| `message` | `text` | non |  |  |  |
| `statut` | `enum('nouveau','traite')` | non | nouveau | Index |  |
| `date_message` | `datetime` | non | CURRENT_TIMESTAMP |  |  |

## Table `mesures_imc`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `utilisateur_id` | `int` | non |  | Index, → `utilisateurs.id` (suppression : CASCADE) |  |
| `poids` | `decimal(5,1)` | non |  |  | Poids en kg |
| `taille` | `decimal(5,1)` | non |  |  | Taille en cm |
| `imc` | `decimal(4,1)` | non |  |  | Indice de masse corporelle calculé |
| `interpretation` | `varchar(50)` | non |  |  |  |
| `objectif_recommande` | `enum('perte_gras','prise_masse','diabete','maintien')` | non |  |  |  |
| `date_mesure` | `datetime` | oui | CURRENT_TIMESTAMP |  |  |

## Table `plat_objectifs`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `plat_id` | `int` | non |  | Clé primaire, → `plats.id` (suppression : CASCADE) |  |
| `objectif` | `enum('perte_gras','prise_masse','diabete','maintien')` | non |  | Clé primaire |  |

## Table `plats`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `categorie_id` | `int` | non |  | Index, → `categories.id` (suppression : CASCADE) |  |
| `nom` | `varchar(100)` | non |  |  |  |
| `description` | `text` | oui |  |  |  |
| `prix` | `decimal(10,0)` | non |  |  | Prix en FCFA |
| `calories` | `int` | non |  |  | kcal par portion |
| `proteines` | `decimal(5,1)` | non |  |  | grammes |
| `glucides` | `decimal(5,1)` | non |  |  | grammes |
| `lipides` | `decimal(5,1)` | non |  |  | grammes |
| `ig_faible` | `tinyint(1)` | oui | 0 |  | Index glycémique faible (adapté diabète) |
| `image` | `varchar(255)` | oui | defaut.jpg |  |  |
| `disponible` | `tinyint(1)` | oui | 1 |  |  |
| `date_ajout` | `datetime` | oui | CURRENT_TIMESTAMP |  |  |

## Table `utilisateurs`

| Colonne | Type MySQL | Nul | Défaut | Clé / contrainte | Commentaire |
|---|---|---|---|---|---|
| `id` | `int` | non |  | Clé primaire, auto-incrémentée |  |
| `nom` | `varchar(50)` | non |  |  |  |
| `prenom` | `varchar(50)` | non |  |  |  |
| `email` | `varchar(100)` | non |  | Unique |  |
| `mot_de_passe` | `varchar(255)` | non |  |  |  |
| `telephone` | `varchar(20)` | oui |  |  |  |
| `objectif` | `enum('perte_gras','prise_masse','diabete','maintien')` | oui |  |  | Défini après le calcul de l’IMC |
| `role` | `enum('client','admin')` | oui | client |  |  |
| `bloque` | `tinyint(1)` | non | 0 |  | 1 = compte bloqué |
| `tentatives_connexion` | `tinyint unsigned` | non | 0 |  |  |
| `date_inscription` | `datetime` | oui | CURRENT_TIMESTAMP |  |  |
