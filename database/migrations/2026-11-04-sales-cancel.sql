-- Annulation d'une vente (« Remettre disponible » sur la fiche d'un objet vendu) : la vente est gardée dans la base,
-- avec la date d'annulation (NULL = vente valable), et l'objet redevient « Disponible ».
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).
ALTER TABLE sales
    ADD sa_cancelled_at DATETIME NULL;
