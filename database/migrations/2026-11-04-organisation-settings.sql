-- Paramètres d'une organisation (page dashboard/organisation/settings/) et éléments liés.
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).

-- Réglages de l'organisation
ALTER TABLE organisations
    ADD org_lost_page_enabled TINYINT(1) NOT NULL DEFAULT 1,     -- page publique « objet perdu » (activée par défaut)
    ADD org_lost_message VARCHAR(500) NULL,                      -- message affiché sur cette page (ex. où déposer l'objet)
    ADD org_default_loan_days SMALLINT UNSIGNED NULL,            -- durée de prêt par défaut en jours (NULL = indéterminée)
    ADD org_reminders_enabled TINYINT(1) NOT NULL DEFAULT 0,     -- rappel par e-mail au client 2 jours avant l'échéance
    ADD org_contract_terms TEXT NULL;                            -- conditions imprimées sur le contrat de prêt

-- Date d'envoi du rappel d'un prêt (NULL = pas encore envoyé), pour ne l'envoyer qu'une fois
ALTER TABLE loans
    ADD lo_reminder_sent_at DATETIME NULL;

-- Invitations : identifiant automatique (il ne l'était pas), date et auteur de l'invitation
ALTER TABLE invitations
    MODIFY id_invitation INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ADD inv_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD idx_invited_by INT UNSIGNED NULL;                        -- membre qui a invité (NULL si son compte a été supprimé)
