# Rapport des corrections — 4 points

Branche `corrections-encadreur`. Les tests passent après chaque point : `test_fonctions` 44/44, `test_blocage_connexion` 15/15, `test_images` 20/20, `run.php` 89 assertions. Les tests utilisent une base temporaire ; `zig_healthy_db` n'est pas touchée.

## Point 1 — Cases « Objectifs compatibles » du formulaire de plat

**Constat.** Les deux cases « glycémiques » n'écrivent pas la même donnée :

| Case (ancien libellé) | Donnée écrite |
|---|---|
| Perte de gras | `plat_objectifs.objectif = 'perte_gras'` |
| Prise de masse | `plat_objectifs.objectif = 'prise_masse'` |
| Faible index glycémique | `plat_objectifs.objectif = 'diabete'` |
| Garder la ligne | `plat_objectifs.objectif = 'maintien'` |
| Index glycémique faible (adapté diabète) | colonne `plats.ig_faible` (0 ou 1) |

**Ce qui a été fait.** Les deux cases sont gardées et renommées, dans le formulaire d'ajout et dans celui de modification : « Objectif Diabète » et « Plat à index glycémique faible ». Le libellé vu par les clients (`$OBJECTIFS['diabete']`, « Faible index glycémique ») ne change pas.

- Fichier modifié : `admin/plats.php`
- Commit : `6aecc13` — *fix: clarify diabetes objective and low-GI checkboxes in dish forms*

**Texte pour le mémoire (3 lignes).**
> Le formulaire d'ajout d'un plat proposait deux cases « glycémiques » dont les libellés se ressemblaient, alors qu'elles enregistrent deux données distinctes.
> La première associe le plat à l'objectif « diabète » (table `plat_objectifs`), qui sert au filtrage par profil ; la seconde marque le plat comme à index glycémique faible (colonne `ig_faible`), information affichée sur sa fiche.
> Les deux ont été conservées et renommées « Objectif Diabète » et « Plat à index glycémique faible » dans les écrans d'ajout et de modification.

**À valider par Salma.** Faut-il aussi renommer le libellé client « Faible index glycémique » (menu, IMC, Mon compte, badges) ? Ce libellé apparaît peut-être dans le mémoire.

## Point 2 — Calories du « Smoothie Cajou, Amande & Épices Douces » (id 20)

**Constat.**

| Calories | Protéines | Glucides | Lipides | Estimation P×4 + G×4 + L×9 |
|---|---|---|---|---|
| 1650 kcal | 42 g | 20 g | 135 g | 1463 kcal (écart 13 %) |

Les calories sont cohérentes avec les macros, mais les macros elles-mêmes sont invraisemblables pour un smoothie : 135 g de lipides correspondent à environ 300 g de noix de cajou. Recalculer à partir des macros donnerait 1463 kcal, ce qui serait tout aussi faux.

**Ce qui a été fait.** Rien n'a été modifié. `bd/correction_calories_smoothie.sql` n'est pas créé : il faut d'abord les vraies valeurs.

**Autres plats dont l'écart dépasse 30 %** (liste seulement, rien de modifié) :

| id | Plat | kcal saisies | Estimation | Écart |
|---|---|---|---|---|
| 27 | Smoothie Bouye, Orange & Menthe | 160 | 84 | +91 % |
| 26 | Soleil Carotte, Orange & Gingembre | 90 | 200 | −55 % (30 g de protéines dans un jus) |
| 19 | Fraîcheur Kiwi & Menthe | 130 | 97 | +34 % |
| 13 | Assiette Fonio Doré, Poulet Rôti & Légumes Fondants | 690 | 521 | +32 % |

Le plat 8 (Crevettes, Patate Douce & Asperges) est à la limite : −29,6 %.

**À fournir par Salma.** La recette du smoothie (grammes de chaque ingrédient) ou la source de ses valeurs nutritionnelles : [À COMPLÉTER PAR SALMA]. Faut-il traiter les plats 13, 19, 26 et 27 de la même façon ?

## Point 3 — Orthographe du bénéficiaire Wave / Orange Money

**Constat.** `commande.php` écrivait « Salma Marième Diallo » pour Wave et « Salma Marieme Diallo » pour Orange Money, avec le numéro recopié deux fois.

**Ce qui a été fait.** Deux constantes dans `includes/config.php` : `PAIEMENT_BENEFICIAIRE` (« Salma Marieme Diallo ») et `PAIEMENT_NUMERO` (numéro inchangé). Les deux moyens de paiement les utilisent. Le nom n'est plus écrit qu'à un seul endroit du code. La ligne admin de `bd/zig_healthy_db.sql` concerne le compte et non le paiement : elle n'a pas été modifiée.

- Fichiers modifiés : `includes/config.php`, `commande.php`
- Commit : `b42e369` — *fix: single payee name and number constant for Wave and Orange Money*

## Point 4 — Image de la « Salade d'aubergines méditerranéenne » (id 33)

**Constat.** Le plat utilise `images/plats/plat_fe72e3c18e3eaa00b37690d5e376ed17.jpg`, nom donné par l'envoi depuis l'admin. Le fichier `images/plats/aubergine.jpeg`, qui n'est utilisé par aucun plat, est probablement la copie d'origine. Les deux fichiers ont été ajoutés au dépôt (privé) à la demande de Salma. Aucune image du menu n'a de source indiquée dans le projet. Salma précise que toutes les photos et toutes les recettes viennent de Pinterest : la colonne source porte « Pinterest » pour les 27 plats, avec le site d'origine à retrouver.

**Ce qui a été fait.** Création de `docs/credits_images.md`, qui recense les 27 plats, `images/sport.jpg` et les fichiers non utilisés. Auteur, lien et licence sont marqués [À COMPLÉTER PAR SALMA]. Le fichier signale aussi un problème de casse : `Balls.jpeg` et `Shrimp.jpeg` en base, contre `balls.jpeg` et `shrimp.jpeg` sur le disque. Ces deux images ne s'afficheraient pas sur un hébergeur Linux.

- Fichier créé : `docs/credits_images.md`
- Commit : `4ee748a` — *docs: add image credits table with sources to complete*

**Remplacer la photo par une photo personnelle (3 lignes).**
> Photographier le plat à la lumière du jour, en paysage, puis l'envoyer depuis Admin → Plats → Modifier ; l'application lui donne un nouveau nom de fichier.
> Supprimer ensuite `aubergine.jpeg` et l'ancien `plat_fe72…jpg` (git rm), puis commiter.
> Dans `docs/credits_images.md`, indiquer « Salma Marieme Diallo », « photo personnelle », et passer le statut à « Remplacée ».

**À valider par Salma.** Auteur, lien et licence de chaque image, ou décision de la remplacer.

## Travaux liés : preuves de réalisation demandées par l'encadreur

- **Défauts d'affichage corrigés** (commit `c612486`). Sur la page de commande, « Transfert mobile » ne chevauche plus « Orange Money ». Dans Admin → Commandes, le mode et le statut s'affichent en clair (« À emporter », « En préparation ») au lieu des codes internes. Fichiers : `css/style.css` (2 lignes), `admin/includes/admin.php`, `admin/commandes.php`.
- **Captures.** 8 captures de l'application en fonctionnement, prises avec des clients et des commandes de démonstration, sont dans `Documents\Memoire\Captures_realisation\`.
- **Mémoire.** Une copie, `Memoire 1SShealthy Ziguinchor corrigé - avec captures.docx`, a été créée ; l'original n'est pas modifié. Section 6.5 : 7 nouvelles figures, la capture du tableau de bord remplacée par la version actuelle, figures renumérotées de 28 à 40, et numéros de page du sommaire, des listes et de la table finale recalculés par Word. Les maquettes restent en 5.9.
- **Phrase corrigée en 5.9.** La phrase disait que la répartition des commandes par objectif « ne figure pas » dans le tableau de bord réalisé. Or elle y figure (`admin/dashboard.php`). La phrase a été corrigée dans la copie.
- **GitHub.** Dépôt privé : https://github.com/Nooralshams/SS-Healthy (branche `corrections-encadreur`). Le lien est inséré dans la section 6.5 de la copie du mémoire. Reste à inviter l'encadreur (Settings → Collaborators).

## Résumé

1. Point 1 : les deux cases sont deux données distinctes (objectif « diabete » et colonne `ig_faible`), gardées et renommées (`6aecc13`).
2. Point 2 : les macros du smoothie sont incohérentes (135 g de lipides) ; aucune valeur n'est modifiée sans la recette.
3. Point 2 : 4 autres plats dépassent 30 % d'écart (13, 19, 26, 27), listés sans modification.
4. Point 3 : un seul nom de bénéficiaire et un seul numéro, en constantes (`b42e369`).
5. Point 4 : `docs/credits_images.md` recense toutes les images, sources à compléter (`4ee748a`).
6. Affichage du paiement et des commandes admin corrigé (`c612486`) ; tests 44/15/20/89 au vert.
7. Captures insérées dans une copie du mémoire (figures 28 à 40, pages recalculées).
8. À fournir : recette du smoothie, sources des images, invitation de l'encadreur sur le dépôt GitHub privé.
