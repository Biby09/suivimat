<?php
// Création d'une catégorie et de ses champs personnalisés
// POST : organisation_id, name, parent_id (optionnel), fields[i][name] et fields[i][default] (valeur par défaut, optionnelle)

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
$name = trim($_POST['name'] ?? '');
$parentId = ($_POST['parent_id'] ?? '') === '' ? null : filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
$rawFields = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];

if (!$organisationId) {
    sendError(400, 'ID d\'organisation invalide');
}
if ($name === '' || mb_strlen($name) > 50) {
    sendError(400, 'Le nom de la catégorie est requis et ne peut pas dépasser 50 caractères');
}
if ($parentId === false) {
    sendError(400, 'Catégorie parente invalide');
}

// readCategoryField(), checkCategoryFields() et MAX_FIELDS
require $_SERVER['DOCUMENT_ROOT'] . '/includes/category-fields.php';

// Champs sans nom ignorés (ligne laissée vide), puis vérification : nombre, longueurs, doublons
$fields = array_values(array_filter(array_map('readCategoryField', $rawFields), fn($field) => $field['name'] !== ''));
checkCategoryFields($fields);

try {
    $organisation = $organisationManager->getOrganisationById($organisationId);
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

$categoryManager = new CategoryManager($mysqlClient, $organisation);

if ($categoryManager->categoryNameExists($name)) {
    sendError(409, 'Une catégorie « ' . $name . ' » existe déjà');
}

// La catégorie parente doit appartenir à la même organisation
if ($parentId !== null) {
    try {
        $categoryManager->getCategoryById($parentId);
    } catch (Exception $e) {
        sendError(404, 'Catégorie parente introuvable');
    }
}

try {
    $category = $categoryManager->createCategory($name, $parentId, $fields);
} catch (Exception $e) {
    error_log('Création de la catégorie impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la création de la catégorie');
}

echo json_encode(['success' => true, 'category_id' => $category->getId()]);
