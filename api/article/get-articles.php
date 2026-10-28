<?php
// Articles configurés de l'organisation, par pages de 30, filtrés par catégorie et recherche.
// Paramètres GET : organisation_id, page (défaut 1), categorie (« none » ou ID, absent = toutes), q (recherche)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/imageManager.php';

header('Content-Type: application/json; charset=utf-8');

const PAGE_SIZE = 30;
const MAX_WORDS = 10;

function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError(405, 'Méthode non autorisée');
}

$organisationId = filter_input(INPUT_GET, 'organisation_id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]) ?: 1;
$category = $_GET['categorie'] ?? '';
$query = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

try {
    $organisation = $organisationManager->getOrganisationById((int) $organisationId);
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

$articleManager = new ArticleManager($mysqlClient, $organisation);
$categoryManager = new CategoryManager($mysqlClient, $organisation);

// Filtre catégorie : la catégorie choisie et toutes ses sous-catégories
$categoryIds = null;
$withoutCategory = $category === 'none';
if (!$withoutCategory && $category !== '') {
    $categoryId = filter_var($category, FILTER_VALIDATE_INT);
    try {
        $categoryManager->getCategoryById((int) $categoryId);
    } catch (Exception $e) {
        sendError(404, 'Catégorie introuvable');
    }
    $categoryIds = [$categoryId, ...$categoryManager->getDescendantIds($categoryId)];
}

$words = array_slice(preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY), 0, MAX_WORDS);
$words = array_map(fn($word) => ['text' => $word, 'category_ids' => $categoryManager->findCategoryIdsByName($word)], $words);

$result = $articleManager->searchConfiguredArticles($categoryIds, $withoutCategory, $words, PAGE_SIZE, ($page - 1) * PAGE_SIZE);
$pages = max(1, (int) ceil($result['total'] / PAGE_SIZE));

// Page au-delà de la dernière (ex. objets supprimés entre-temps) : dernière page
if ($page > $pages) {
    $page = $pages;
    $result = $articleManager->searchConfiguredArticles($categoryIds, $withoutCategory, $words, PAGE_SIZE, ($page - 1) * PAGE_SIZE);
}

$coverUrls = (new ImageManager($mysqlClient))->getCoverUrls(array_map(fn(Article $article) => $article->getId(), $result['articles']));

echo json_encode([
    'success' => true,
    'articles' => array_map(fn(Article $article) => [
        'id' => $article->getId(),
        'code' => $article->getCode(),
        'name' => $article->getName(),
        'category_id' => $article->getCategoryId(),
        'status' => $article->getStatus(),
        'cover_url' => $coverUrls[$article->getId()] ?? null,
    ], $result['articles']),
    'total' => $result['total'],
    'page' => $page,
    'pages' => $pages,
    'page_size' => PAGE_SIZE,
    // Compteurs de l'aside (sans recherche ni filtre) : [id_catégorie => nombre], 0 = sans catégorie
    'counts' => (object) $articleManager->countConfiguredArticlesByCategory(),
]);
