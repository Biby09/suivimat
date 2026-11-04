-- Valeur par défaut d'un champ personnalisé de catégorie (optionnelle : NULL = aucune).
-- Pré-remplie dans le formulaire d'un objet quand on lui choisit cette catégorie (même longueur max que les valeurs).
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).
ALTER TABLE additional_fields
    ADD ad_default VARCHAR(200) NULL AFTER ad_name;
