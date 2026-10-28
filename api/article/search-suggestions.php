<?php
// Propositions affichées pendant la saisie dans la recherche : catégories et objets correspondants.
// Paramètres GET : organisation_id, q

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_CATEGORIES = 3;
const MAX_ARTICLES = 6;
const MAX_WORDS = 10;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$organisationId = filter_input(INPUT_GET, 'organisation_id', FILTER_VALIDATE_INT);
$query = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

try {
    $organisation = $organisationManager->getOrganisationById((int) $organisationId);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Organisation introuvable']);
    exit;
}

if ($query === '') {
    echo json_encode(['success' => true, 'categories' => [], 'articles' => []]);
    exit;
}

$categoryManager = new CategoryManager($mysqlClient, $organisation);

// Catégories dont le nom contient la saisie complète (sans leurs sous-catégories)
$stmt = $mysqlClient->prepare('SELECT id_category FROM categories WHERE idx_organisation = :organisation AND cat_name LIKE :text ORDER BY cat_name LIMIT ' . MAX_CATEGORIES);
$stmt->execute(['organisation' => $organisation->getId(), 'text' => '%' . ArticleManager::escapeLike($query) . '%']);
$categoryIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

// Objets : mêmes règles que la recherche validée avec Entrée
$words = array_slice(preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY), 0, MAX_WORDS);
$words = array_map(fn($word) => ['text' => $word, 'category_ids' => $categoryManager->findCategoryIdsByName($word)], $words);
$result = (new ArticleManager($mysqlClient, $organisation))->searchConfiguredArticles(null, false, $words, MAX_ARTICLES, 0);

echo json_encode([
    'success' => true,
    'categories' => $categoryIds,
    'articles' => array_map(fn(Article $article) => [
        'code' => $article->getCode(),
        'name' => $article->getName(),
        'category_id' => $article->getCategoryId(),
    ], $result['articles']),
    'total' => $result['total'],
]);
