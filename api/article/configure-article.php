<?php
// Enregistre le nom, la catégorie, les champs personnalisés et les nouvelles photos d'un article,
// et supprime les photos demandées (delete_photos[] : ID des images, utilisé par la fiche de l'objet)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/imageManager.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_PHOTOS = 10; // par enregistrement

function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Méthode non autorisée');
}

// post_max_size dépassé : PHP vide $_POST et $_FILES
if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    sendError(413, 'Les photos sont trop volumineuses');
}

$organisationId = filter_input(INPUT_POST, 'organisation_id', FILTER_VALIDATE_INT);
$code = strtoupper(trim($_POST['code'] ?? ''));
$name = trim($_POST['name'] ?? '');
$categoryId = ($_POST['category_id'] ?? '') === '' ? null : filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$fields = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];
$deletePhotoIds = is_array($_POST['delete_photos'] ?? null) ? $_POST['delete_photos'] : [];

if (!$organisationId || !preg_match('/^[A-Z0-9]{4}$/', $code)) {
    sendError(400, 'Code invalide');
}
if ($name === '' || mb_strlen($name) > 100) {
    sendError(400, 'Le nom est requis et ne peut pas dépasser 100 caractères');
}
if ($categoryId === false) {
    sendError(400, 'Catégorie invalide');
}

$fields = array_map(fn($value) => trim((string) $value), $fields);
foreach ($fields as $value) {
    if (mb_strlen($value) > 200) {
        sendError(400, 'Un champ personnalisé ne peut pas dépasser 200 caractères');
    }
}

// Photos : photos[] (envoi multiple), réduites côté navigateur avant l'envoi
try {
    $photoPaths = ImageManager::getUploadedPhotos('photos', MAX_PHOTOS);
} catch (InvalidArgumentException $e) {
    sendError(400, $e->getMessage());
}

try {
    $organisation = $organisationManager->getOrganisationById($organisationId);
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

$articleManager = new ArticleManager($mysqlClient, $organisation);

try {
    $article = $articleManager->getArticleByCode($code);
} catch (Exception $e) {
    sendError(404, 'Le code ' . $code . ' n\'appartient pas à cette organisation');
}

// La catégorie doit appartenir à la même organisation
if ($categoryId !== null) {
    try {
        (new CategoryManager($mysqlClient, $organisation))->getCategoryById($categoryId);
    } catch (Exception $e) {
        sendError(404, 'Catégorie introuvable');
    }
}

try {
    $articleManager->configureArticle($article, $name, $categoryId, $fields);
} catch (Exception $e) {
    error_log('Configuration de l\'article impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'enregistrement de l\'objet');
}

if ($deletePhotoIds) {
    try {
        (new ImageManager($mysqlClient))->deleteArticleImages($article, $deletePhotoIds);
    } catch (Exception $e) {
        error_log('Suppression des photos impossible : ' . $e->getMessage());
        sendError(500, 'L\'objet est enregistré, mais les photos n\'ont pas pu être supprimées.');
    }
}

if ($photoPaths) {
    try {
        (new ImageManager($mysqlClient))->addArticleImages($article, $photoPaths);
    } catch (Exception $e) {
        error_log('Enregistrement des photos impossible : ' . $e->getMessage());
        sendError(500, 'L\'objet est enregistré, mais pas ses photos. Réessayez de les ajouter.');
    }
}

echo json_encode(['success' => true]);
