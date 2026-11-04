-- degats.deg_date était un varchar(45) dont la valeur par défaut était le TEXTE 'CURRENT_TIMESTAMP' (pas la date du jour).
-- Devient une vraie date et heure, remplie automatiquement à l'enregistrement d'un dégât.
-- À exécuter une seule fois par base, quand la table degats est encore vide (déjà appliqué en local le 2026-11-04).
ALTER TABLE degats
    MODIFY deg_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
