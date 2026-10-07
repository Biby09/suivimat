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
}