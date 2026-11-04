-- Date de retour PRÉVUE d'un prêt (optionnelle : NULL = indéterminée), choisie au moment du prêt.
-- À ne pas confondre avec lo_end_date = date de retour RÉELLE (NULL = l'objet n'est pas encore rendu).
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).
ALTER TABLE loans
    ADD lo_due_date DATE NULL AFTER lo_start_date;
