-- =====================================================
-- Organisation du menu : petit-déjeuner, déjeuner, dîner,
-- jus naturels sans sucre ajouté et snacks healthy
-- =====================================================
USE zig_healthy_db;

UPDATE categories SET nom = 'Petit-déjeuner', description = 'Options équilibrées pour commencer la journée' WHERE id = 1;
UPDATE categories SET nom = 'Déjeuner', description = 'Plats complets à savourer à midi' WHERE id = 2;
UPDATE categories SET nom = 'Dîner', description = 'Plats légers et équilibrés pour le soir' WHERE id = 3;
UPDATE categories SET nom = 'Jus naturels', description = 'Boissons naturelles préparées sans sucre ajouté' WHERE id = 4;
UPDATE categories SET nom = 'Snacks healthy', description = 'Petites faims saines et équilibrées' WHERE id = 5;

-- Répartition des petits-déjeuners, déjeuners et dîners existants
UPDATE plats SET categorie_id = 1 WHERE nom IN (
    'Bowl avoine & mangue',
    'Omelette blanche & avocat',
    'Wrap de poulet équilibré'
);

UPDATE plats SET categorie_id = 2 WHERE nom IN (
    'Poulet grillé & légumes vapeur',
    'Poisson braisé light',
    'Mafé léger au poulet',
    'B?uf sauté prise de masse',
    'Bœuf sauté prise de masse',
    'Salade niébé & thon',
    'Bowl avocat-crevettes',
    'Salade de poulet fraîche',
    'Concombre farci au thon',
    'Fonio au poulet'
);

UPDATE plats SET categorie_id = 3 WHERE nom IN (
    'Crevettes grillées & légumes'
);

-- Plats recommandés, si ils ne sont pas déjà présents
INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 1, 'Wrap de poulet équilibré', 'Wrap au poulet, œufs durs, fromage blanc, tomates et salade', 3000, 420, 36.0, 34.0, 13.0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Wrap de poulet équilibré');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 2, 'Salade de poulet fraîche', 'Poulet, maïs, avocat, tomates fraîches et concombre', 3500, 430, 38.0, 28.0, 16.0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Salade de poulet fraîche');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 2, 'Concombre farci au thon', 'Concombre garni de thon léger, salade fraîche et fines herbes', 2800, 300, 29.0, 18.0, 9.0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Concombre farci au thon');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 2, 'Fonio au poulet', 'Fonio au poulet accompagné d’une salade de chou, oignons et tomates', 3500, 470, 37.0, 50.0, 12.0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Fonio au poulet');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 3, 'Crevettes grillées & légumes', 'Crevettes, brocolis et pommes de terre grillées', 4000, 460, 34.0, 42.0, 14.0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Crevettes grillées & légumes');

-- Jus naturels sans sucre ajouté
INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 4, 'Jus naturel bissap gingembre', 'Bissap naturel préparé avec du gingembre, sans sucre ajouté', 1200, 45, 0.5, 10.0, 0.2, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Jus naturel bissap gingembre');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 4, 'Jus naturel orange citron', 'Orange fraîche pressée avec citron, sans sucre ajouté', 1500, 90, 2.0, 20.0, 0.5, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Jus naturel orange citron');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 4, 'Jus vert detox', 'Concombre, menthe, citron vert et gingembre, sans sucre ajouté', 1500, 70, 2.0, 15.0, 0.5, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Jus vert detox');

-- Snacks healthy
INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 5, 'Energy balls dattes cajou', 'Boules maison à base de dattes, noix de cajou et graines', 1500, 210, 5.0, 28.0, 9.0, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Energy balls dattes cajou');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 5, 'Yaourt grec fruits frais', 'Yaourt nature, fruits frais et graines sans sucre ajouté', 1800, 180, 14.0, 20.0, 5.0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Yaourt grec fruits frais');

INSERT INTO plats (categorie_id, nom, description, prix, calories, proteines, glucides, lipides, ig_faible)
SELECT 5, 'Guacamole crudités', 'Guacamole maison accompagné de bâtonnets de légumes frais', 2000, 220, 4.0, 18.0, 14.0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM plats WHERE nom = 'Guacamole crudités');

-- Associations aux objectifs nutritionnels
INSERT IGNORE INTO plat_objectifs (plat_id, objectif)
SELECT p.id, o.objectif
FROM plats p
CROSS JOIN (
    SELECT 'perte_gras' AS objectif
    UNION ALL SELECT 'maintien'
) o
WHERE p.nom IN (
    'Wrap de poulet équilibré',
    'Salade de poulet fraîche',
    'Concombre farci au thon',
    'Fonio au poulet',
    'Crevettes grillées & légumes',
    'Jus naturel bissap gingembre',
    'Jus naturel orange citron',
    'Jus vert detox',
    'Energy balls dattes cajou',
    'Yaourt grec fruits frais',
    'Guacamole crudités'
);
