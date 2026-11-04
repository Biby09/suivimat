-- Ventes d'objets : à qui et quand un objet a été vendu (articles.art_status passe alors à 'Vendu').
-- À exécuter une seule fois par base (déjà appliqué en local le 2026-11-04).
CREATE TABLE sales (
    id_sale INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idx_article INT UNSIGNED NOT NULL,
    idx_client INT UNSIGNED NOT NULL,   -- acheteur
    sa_date DATE NOT NULL,              -- date de vente
    PRIMARY KEY (id_sale),
    KEY idx_article_idx (idx_article),
    KEY idx_client_idx (idx_client),
    CONSTRAINT sales_idx_article FOREIGN KEY (idx_article) REFERENCES articles (id_article),
    CONSTRAINT sales_idx_client FOREIGN KEY (idx_client) REFERENCES clients (id_client)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
