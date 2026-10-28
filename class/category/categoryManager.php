<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/category.php';
class CategoryManager{

    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    public function getCategoryById(int $id): Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id_category = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $category = new Category();
            $category->hydrate($ligne);
            return $category;

        } else {
            throw new Exception("Category not found with ID: " . $id);
        }
    }

    public function getAllCategories(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE idx_organisation = :organisation ORDER BY cat_name');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $categories = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $category = new Category();
            $category->hydrate($ligne);
            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * $excludeId : catégorie à ignorer (celle qu'on renomme)
     */
    public function categoryNameExists(string $name, ?int $excludeId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE idx_organisation = :organisation AND cat_name = :name AND id_category <> :exclude');
        $stmt->execute(['organisation' => $this->organisation->getId(), 'name' => $name, 'exclude' => $excludeId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * IDs des catégories dont le nom contient $text, ou dont une catégorie parente le contient
     * (un objet rangé dans « Tentes › Igloo » est trouvé en cherchant « tentes »)
     */
    public function findCategoryIdsByName(string $text): array
    {
        $stmt = $this->pdo->prepare('SELECT id_category FROM categories WHERE idx_organisation = :organisation AND cat_name LIKE :text');
        $stmt->execute(['organisation' => $this->organisation->getId(), 'text' => '%' . ArticleManager::escapeLike($text) . '%']);

        $ids = [];
        foreach (array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)) as $id) {
            array_push($ids, $id, ...$this->getDescendantIds($id));
        }
        return array_values(array_unique($ids));
    }

    /**
     * IDs de toutes les sous-catégories (directes ou non) d'une catégorie
     */
    public function getDescendantIds(int $id): array
    {
        $childrenByParent = [];
        foreach ($this->getAllCategories() as $category) {
            $childrenByParent[$category->getParentId() ?? 0][] = $category->getId();
        }

        $descendants = [];
        $toVisit = $childrenByParent[$id] ?? [];
        while ($toVisit) {
            $childId = array_pop($toVisit);
            if (!in_array($childId, $descendants, true)) {
                $descendants[] = $childId;
                array_push($toVisit, ...($childrenByParent[$childId] ?? []));
            }
        }

        return $descendants;
    }

    public function countSubcategories(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE idx_cat_parent = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);

        return (int) $stmt->fetchColumn();
    }

    public function countArticles(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM articles WHERE idx_category = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Modifie une catégorie et ses champs personnalisés. Tout ou rien.
     * $fields : champs conservés [id_champ => nouveau nom] ; les champs absents sont supprimés avec leurs valeurs.
     * $newFieldNames : champs à ajouter.
     */
    public function updateCategory(int $id, string $name, ?int $parentId, array $fields, array $newFieldNames): Category
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('UPDATE categories SET cat_name = :name, idx_cat_parent = :parent WHERE id_category = :id AND idx_organisation = :organisation');
            $stmt->execute(['name' => $name, 'parent' => $parentId, 'id' => $id, 'organisation' => $this->organisation->getId()]);

            $stmt = $this->pdo->prepare('SELECT id_af FROM additional_fields WHERE idx_category = :category');
            $stmt->execute(['category' => $id]);
            $removedFieldIds = array_diff(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), array_keys($fields));

            $deleteValues = $this->pdo->prepare('DELETE FROM additional_fields_value WHERE idx_additional_fields = :field');
            $deleteField = $this->pdo->prepare('DELETE FROM additional_fields WHERE id_af = :field AND idx_category = :category');
            foreach ($removedFieldIds as $fieldId) {
                $deleteValues->execute(['field' => $fieldId]);
                $deleteField->execute(['field' => $fieldId, 'category' => $id]);
            }

            $stmt = $this->pdo->prepare('UPDATE additional_fields SET ad_name = :name WHERE id_af = :field AND idx_category = :category');
            foreach ($fields as $fieldId => $fieldName) {
                $stmt->execute(['name' => $fieldName, 'field' => $fieldId, 'category' => $id]);
            }

            $stmt = $this->pdo->prepare('INSERT INTO additional_fields (ad_name, idx_category) VALUES (:name, :category)');
            foreach ($newFieldNames as $fieldName) {
                $stmt->execute(['name' => $fieldName, 'category' => $id]);
            }

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $this->getCategoryById($id);
    }

    /**
     * Supprime une catégorie et ses champs personnalisés. Tout ou rien.
     * La catégorie ne doit plus contenir d'objets ni de sous-catégories.
     */
    public function deleteCategory(int $id): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('DELETE additional_fields_value FROM additional_fields_value JOIN additional_fields ON additional_fields.id_af = additional_fields_value.idx_additional_fields WHERE additional_fields.idx_category = :category');
            $stmt->execute(['category' => $id]);

            $stmt = $this->pdo->prepare('DELETE FROM additional_fields WHERE idx_category = :category');
            $stmt->execute(['category' => $id]);

            $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id_category = :id AND idx_organisation = :organisation');
            $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Crée une catégorie et ses champs personnalisés. Tout ou rien.
     */
    public function createCategory(string $name, ?int $parentId, array $fieldNames): Category
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('INSERT INTO categories (idx_organisation, cat_name, idx_cat_parent) VALUES (:organisation, :name, :parent)');
            $stmt->execute(['organisation' => $this->organisation->getId(), 'name' => $name, 'parent' => $parentId]);
            $categoryId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('INSERT INTO additional_fields (ad_name, idx_category) VALUES (:name, :category)');
            foreach ($fieldNames as $fieldName) {
                $stmt->execute(['name' => $fieldName, 'category' => $categoryId]);
            }

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $this->getCategoryById($categoryId);
    }

    /**
     * Champs personnalisés des catégories de l'organisation, groupés par catégorie :
     * [id_catégorie => [['id' => ..., 'name' => ...], ...]]
     */
    public function getAdditionalFieldsByCategory(): array
    {
        $stmt = $this->pdo->prepare('SELECT additional_fields.id_af, additional_fields.ad_name, additional_fields.idx_category FROM additional_fields JOIN categories ON categories.id_category = additional_fields.idx_category WHERE categories.idx_organisation = :organisation ORDER BY additional_fields.id_af');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $fields = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fields[(int) $ligne['idx_category']][] = ['id' => (int) $ligne['id_af'], 'name' => $ligne['ad_name']];
        }

        return $fields;
    }
}
