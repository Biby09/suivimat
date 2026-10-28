<?php
// Enregistre le nom, la catégorie, les champs personnalisés et les nouvelles photos d'un article

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
$photoPaths = [];
if (isset($_FILES['photos']) && is_array($_FILES['photos']['error'])) {
    foreach ($_FILES['photos']['error'] as $index => $error) {
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            sendError(413, 'Une photo est trop volumineuse');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['photos']['tmp_name'][$index])) {
            sendError(400, 'L\'envoi d\'une photo a échoué');
        }
        if (!ImageManager::isValidImage($_FILES['photos']['tmp_name'][$index])) {
            sendError(400, 'Seules les photos JPEG, PNG ou WebP sont acceptées');
        }
        $photoPaths[] = $_FILES['photos']['tmp_name'][$index];
    }
}
if (count($photoPaths) > MAX_PHOTOS) {
    sendError(400, 'Vous pouvez ajouter au maximum ' . MAX_PHOTOS . ' photos à la fois');
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

if ($photoPaths) {
    try {
        (new ImageManager($mysqlClient))->addArticleImages($article, $photoPaths);
    } catch (Exception $e) {
        error_log('Enregistrement des photos impossible : ' . $e->getMessage());
        sendError(500, 'L\'objet est enregistré, mais pas ses photos. Réessayez de les ajouter.');
    }
}

echo json_encode(['success' => true]);
