<?php
// Suppression d'une catégorie et de ses champs personnalisés

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

header('Content-Type: application/json; charset=utf-8');

function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Méthode non autorisée');
}

$organisationId = filter_input(INPUT_POST, 'organisation_id', FILTER_VALIDATE_INT);
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

if (!$organisationId) {
    sendError(400, 'ID d\'organisation invalide');
}
if (!$categoryId) {
    sendError(400, 'ID de catégorie invalide');
}

try {
    $organisation = $organisationManager->getOrganisationById($organisationId);
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

$categoryManager = new CategoryManager($mysqlClient, $organisation);

try {
    $category = $categoryManager->getCategoryById($categoryId);
} catch (Exception $e) {
    sendError(404, 'Catégorie introuvable');
}

// On ne supprime pas une catégorie encore utilisée : les objets et sous-catégories doivent d'abord être déplacés
$subcategoryCount = $categoryManager->countSubcategories($categoryId);
if ($subcategoryCount > 0) {
    sendError(409, 'La catégorie « ' . $category->getName() . ' » contient ' . $subcategoryCount . ' sous-catégorie(s). Déplacez-les ou supprimez-les d\'abord.');
}

$articleCount = $categoryManager->countArticles($categoryId);
if ($articleCount > 0) {
    sendError(409, 'La catégorie « ' . $category->getName() . ' » contient ' . $articleCount . ' objet(s). Changez-les de catégorie d\'abord.');
}

try {
    $categoryManager->deleteCategory($categoryId);
} catch (Exception $e) {
    error_log('Suppression de la catégorie impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la suppression de la catégorie');
}

echo json_encode(['success' => true]);
