<?php
// Liste des catégories de l'organisation avec leurs champs personnalisés

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$organisationId = filter_input(INPUT_GET, 'organisation_id', FILTER_VALIDATE_INT);

try {
    $organisation = $organisationManager->getOrganisationById((int) $organisationId);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Organisation introuvable']);
    exit;
}

$categoryManager = new CategoryManager($mysqlClient, $organisation);
$fieldsByCategory = $categoryManager->getAdditionalFieldsByCategory();

$categories = array_map(fn(Category $category) => [
    'id' => $category->getId(),
    'name' => $category->getName(),
    'parent_id' => $category->getParentId(),
    'fields' => $fieldsByCategory[$category->getId()] ?? [],
], $categoryManager->getAllCategories());

echo json_encode(['success' => true, 'categories' => $categories]);
