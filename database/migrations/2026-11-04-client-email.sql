-- Adresse e-mail d'un client, optionnelle (NULL = non renseignée) : pour le contacter, par exemple en cas de retard.
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).
ALTER TABLE clients
    ADD cli_email VARCHAR(100) NULL AFTER cli_lastname;
