<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/article.php';
class ArticleManager
{

    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    public function getArticleById(int $id): ?Article
    {
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id_article = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $article = new Article();
            $article->hydrate($ligne);
            return $article;
        } else {

            throw new Exception("Article not found with ID: " . $id);

        }

    }

    public function getArticleByCode(string $code): Article
    {
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE art_code = :code AND idx_organisation = :organisation');
        $stmt->execute(['code' => $code, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $article = new Article();
            $article->hydrate($ligne);
            return $article;
        } else {
            throw new Exception("Article not found with code: " . $code);
        }
    }

    /**
     * Recherche paginée parmi les articles configurés, triés par nom. Retourne ['articles' => Article[], 'total' => int].
     * $categoryIds : null = toutes les catégories, sinon IDs autorisés (une catégorie et ses sous-catégories)
     * $withoutCategory : uniquement les articles sans catégorie
     * $words : chaque mot doit apparaître dans le nom ou le code de l'article, ou dans une de ses catégories :
     *          [['text' => mot, 'category_ids' => IDs des catégories dont le nom (ou celui d'une parente) contient le mot], ...]
     * La collation de la base ignore la casse et les accents.
     */
    public function searchConfiguredArticles(?array $categoryIds, bool $withoutCategory, array $words, int $limit, int $offset): array
    {
        $where = ["idx_organisation = :organisation", "art_status <> 'Non attribué'"];
        $params = ['organisation' => $this->organisation->getId()];

        if ($withoutCategory) {
            $where[] = 'idx_category IS NULL';
        } elseif ($categoryIds !== null) {
            $where[] = $categoryIds ? 'idx_category IN (' . self::placeholders('category', $categoryIds, $params) . ')' : '0';
        }

        foreach ($words as $index => $word) {
            $params["word$index"] = '%' . self::escapeLike($word['text']) . '%';
            $condition = "art_name LIKE :word$index OR art_code LIKE :word$index";
            if ($word['category_ids']) {
                $condition .= ' OR idx_category IN (' . self::placeholders("word{$index}_category", $word['category_ids'], $params) . ')';
            }
            $where[] = "($condition)";
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM articles WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // LIMIT et OFFSET sont des entiers validés : insérés directement (PDO les mettrait entre guillemets)
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE $whereSql ORDER BY art_name, art_code LIMIT " . max(0, $limit) . ' OFFSET ' . max(0, $offset));
        $stmt->execute($params);
        $articles = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $article = new Article();
            $article->hydrate($ligne);
            $articles[] = $article;
        }

        return ['articles' => $articles, 'total' => $total];
    }

    /**
     * Nombre d'articles configurés par catégorie : [id_catégorie => nombre], 0 pour « sans catégorie »
     */
    public function countConfiguredArticlesByCategory(): array
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(idx_category, 0), COUNT(*) FROM articles WHERE idx_organisation = :organisation AND art_status <> 'Non attribué' GROUP BY idx_category");
        $stmt->execute(['organisation' => $this->organisation->getId()]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    // « 50% » doit chercher le caractère %, pas n'importe quelle suite
    public static function escapeLike(string $text): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
    }

    // Paramètres nommés pour une clause IN : « :prefix0, :prefix1… », ajoutés à $params
    private static function placeholders(string $prefix, array $values, array &$params): string
    {
        $names = [];
        foreach (array_values($values) as $index => $value) {
            $params["$prefix$index"] = (int) $value;
            $names[] = ":$prefix$index";
        }
        return implode(', ', $names);
    }

    /**
     * Valeurs des champs personnalisés d'un article : [id_champ => valeur]
     */
    public function getAdditionalFieldValues(Article $article): array
    {
        $stmt = $this->pdo->prepare('SELECT idx_additional_fields, afv_value FROM additional_fields_value WHERE idx_article = :article');
        $stmt->execute(['article' => $article->getId()]);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Enregistre le nom, la catégorie et les champs personnalisés d'un article.
     * $fields : [id_champ => valeur] ; seuls les champs de la catégorie choisie sont conservés.
     * Un article « Non attribué » passe à « Disponible ».
     */
    public function configureArticle(Article $article, string $name, ?int $categoryId, array $fields): void
    {
        $allowedFields = [];
        if ($categoryId !== null) {
            $stmt = $this->pdo->prepare('SELECT id_af FROM additional_fields WHERE idx_category = :category');
            $stmt->execute(['category' => $categoryId]);
            $allowedFields = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("UPDATE articles SET art_name = :name, idx_category = :category, art_status = IF(art_status = 'Non attribué', 'Disponible', art_status) WHERE id_article = :id AND idx_organisation = :organisation");
            $stmt->execute([
                'name' => $name,
                'category' => $categoryId,
                'id' => $article->getId(),
                'organisation' => $this->organisation->getId(),
            ]);

            // Les valeurs sont remplacées entièrement : la catégorie a pu changer
            $stmt = $this->pdo->prepare('DELETE FROM additional_fields_value WHERE idx_article = :article');
            $stmt->execute(['article' => $article->getId()]);

            $stmt = $this->pdo->prepare('INSERT INTO additional_fields_value (idx_article, idx_additional_fields, afv_value) VALUES (:article, :field, :value)');
            foreach ($fields as $fieldId => $value) {
                if (in_array((int) $fieldId, $allowedFields, true) && $value !== '') {
                    $stmt->execute(['article' => $article->getId(), 'field' => (int) $fieldId, 'value' => $value]);
                }
            }

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Crée $count articles vierges (sans nom ni catégorie), chacun avec un code unique.
     * Retourne la liste des codes créés. Tout ou rien : en cas d'erreur, aucun article n'est créé.
     */
    public function createBlankArticles(int $count): array
    {
        // Sans 0/O, 1/I/L pour éviter les confusions à la lecture
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $codes = [];

        $stmt = $this->pdo->prepare("INSERT INTO articles (idx_organisation, art_code, art_status) VALUES (:organisation, :code, 'Non attribué')");

        $this->pdo->beginTransaction();

        try {
            while (count($codes) < $count) {
                $code = '';
                for ($i = 0; $i < 4; $i++) {
                    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }

                try {
                    $stmt->execute(['organisation' => $this->organisation->getId(), 'code' => $code]);
                    $codes[] = $code;
                } catch (PDOException $exception) {
                    // Code déjà existant (contrainte UNIQUE) : on en tire un autre
                    if (($exception->errorInfo[1] ?? null) !== 1062) {
                        throw $exception;
                    }
                }
            }

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $codes;
    }
}