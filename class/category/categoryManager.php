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
}