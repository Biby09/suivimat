<?php
// Modification d'une catégorie et de ses champs personnalisés

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
$name = trim($_POST['name'] ?? '');
$parentId = ($_POST['parent_id'] ?? '') === '' ? null : filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
// Champs existants : fields[id_champ][name|default] ; nouveaux champs : new_fields[i][name|default]
$rawFields = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];
$rawNewFields = is_array($_POST['new_fields'] ?? null) ? $_POST['new_fields'] : [];

if (!$organisationId) {
    sendError(400, 'ID d\'organisation invalide');
}
if (!$categoryId) {
    sendError(400, 'ID de catégorie invalide');
}
if ($name === '' || mb_strlen($name) > 50) {
    sendError(400, 'Le nom de la catégorie est requis et ne peut pas dépasser 50 caractères');
}
if ($parentId === false) {
    sendError(400, 'Catégorie parente invalide');
}

// readCategoryField(), checkCategoryFields() et MAX_FIELDS
require $_SERVER['DOCUMENT_ROOT'] . '/includes/category-fields.php';

// Un champ existant sans nom est refusé (pour le supprimer, on le retire du formulaire) ; nouveaux champs sans nom ignorés
$fieldsById = [];
foreach ($rawFields as $fieldId => $rawField) {
    if (filter_var($fieldId, FILTER_VALIDATE_INT) === false) {
        sendError(400, 'Champ personnalisé invalide');
    }
    $field = readCategoryField($rawField);
    if ($field['name'] === '') {
        sendError(400, 'Le nom d\'un champ existant ne peut pas être vide');
    }
    $fieldsById[(int) $fieldId] = $field;
}
$newFields = array_values(array_filter(array_map('readCategoryField', $rawNewFields), fn($field) => $field['name'] !== ''));

// Vérification sur l'ensemble : existants + nouveaux (doublons entre les deux compris)
checkCategoryFields([...array_values($fieldsById), ...$newFields]);

try {
    $organisation = $organisationManager->getOrganisationById($organisationId);
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

$categoryManager = new CategoryManager($mysqlClient, $organisation);

try {
    $categoryManager->getCategoryById($categoryId);
} catch (Exception $e) {
    sendError(404, 'Catégorie introuvable');
}

if ($categoryManager->categoryNameExists($name, $categoryId)) {
    sendError(409, 'Une catégorie « ' . $name . ' » existe déjà');
}

// La catégorie parente doit appartenir à la même organisation et ne pas créer de boucle
if ($parentId !== null) {
    try {
        $categoryManager->getCategoryById($parentId);
    } catch (Exception $e) {
        sendError(404, 'Catégorie parente introuvable');
    }
    if ($parentId === $categoryId || in_array($parentId, $categoryManager->getDescendantIds($categoryId), true)) {
        sendError(400, 'Une catégorie ne peut pas être rangée dans elle-même ou dans une de ses sous-catégories');
    }
}

// Les champs modifiés doivent appartenir à cette catégorie
$existingFieldIds = array_column($categoryManager->getAdditionalFieldsByCategory()[$categoryId] ?? [], 'id');
if (array_diff(array_keys($fieldsById), $existingFieldIds)) {
    sendError(400, 'Champ personnalisé introuvable');
}

try {
    $categoryManager->updateCategory($categoryId, $name, $parentId, $fieldsById, $newFields);
} catch (Exception $e) {
    error_log('Modification de la catégorie impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la modification de la catégorie');
}

echo json_encode(['success' => true]);
