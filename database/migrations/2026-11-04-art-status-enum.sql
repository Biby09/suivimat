-- Statuts des objets (articles.art_status) : liste fermée grâce à un ENUM.
--   'Non attribué' : code QR créé mais objet pas encore configuré (hors inventaire)
--   'Disponible', 'Emprunté', 'Vendu', 'Perdu' : objets de l'inventaire
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).

-- 1. Anciens libellés convertis : sinon le ALTER TABLE échouerait (valeur absente de l'ENUM)
UPDATE articles SET art_status = 'Emprunté' WHERE art_status = 'Prêté';
UPDATE articles SET art_status = 'Disponible' WHERE art_status = 'Pas disponible';

-- 2. Colonne limitée aux statuts connus, obligatoire (NOT NULL)
ALTER TABLE articles
    MODIFY art_status ENUM('Non attribué', 'Disponible', 'Emprunté', 'Vendu', 'Perdu') NOT NULL DEFAULT 'Disponible';
