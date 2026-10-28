<?php
// Recherche d'un article de l'organisation par son code (utilisé après un scan)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/imageManager.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$organisationId = filter_input(INPUT_GET, 'organisation_id', FILTER_VALIDATE_INT);
$code = strtoupper(trim($_GET['code'] ?? ''));

if (!$organisationId || !preg_match('/^[A-Z0-9]{4}$/', $code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code invalide']);
    exit;
}

try {
    $organisation = $organisationManager->getOrganisationById($organisationId);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Organisation introuvable']);
    exit;
}

$articleManager = new ArticleManager($mysqlClient, $organisation);

try {
    $article = $articleManager->getArticleByCode($code);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Le code ' . $code . ' n\'appartient pas à cette organisation']);
    exit;
}

echo json_encode([
    'success' => true,
    'article' => [
        'id' => $article->getId(),
        'code' => $article->getCode(),
        'name' => $article->getName(),
        'category_id' => $article->getCategoryId(),
        'status' => $article->getStatus(),
        'fields' => $articleManager->getAdditionalFieldValues($article),
        'images' => array_map(fn(Image $image) => ['id' => $image->getId(), 'url' => $image->getUrl()], (new ImageManager($mysqlClient))->getArticleImages($article)),
    ],
]);
