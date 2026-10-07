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

    public function categoryNameExists(string $name): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE idx_organisation = :organisation AND cat_name = :name');
        $stmt->execute(['organisation' => $this->organisation->getId(), 'name' => $name]);

        return (int) $stmt->fetchColumn() > 0;
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
