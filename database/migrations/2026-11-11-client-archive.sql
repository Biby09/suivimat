-- Archivage des clients : date d'archivage (NULL = client actif).
-- Un client archivé n'est plus proposé pour un nouveau prêt ou une vente, mais son historique (prêts, achats) est conservé.
-- À exécuter une seule fois par base.
ALTER TABLE clients
    ADD cli_archived_at DATETIME NULL AFTER cli_email;
