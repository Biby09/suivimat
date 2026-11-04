-- Pertes d'objets : quand un objet a été perdu, remarque éventuelle, et quand il a été retrouvé.
-- articles.art_status vaut 'Perdu' tant que la perte en cours (los_found_date NULL) n'est pas terminée.
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).
CREATE TABLE losses (
    id_loss INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idx_article INT UNSIGNED NOT NULL,
    los_date DATE NOT NULL,              -- date de la perte
    los_comment VARCHAR(1000) NULL,      -- où / comment (visible seulement par les membres de l'organisation)
    los_found_date DATE NULL,            -- date à laquelle l'objet a été retrouvé (NULL = toujours perdu)
    PRIMARY KEY (id_loss),
    KEY idx_article_idx (idx_article),
    CONSTRAINT losses_idx_article FOREIGN KEY (idx_article) REFERENCES articles (id_article)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
