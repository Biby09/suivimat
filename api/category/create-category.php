<?php
// Création d'une catégorie et de ses champs personnalisés

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_FIELDS = 20;

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
$fieldNames = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];

if (!$organisationId) {
    sendError(400, 'ID d\'organisation invalide');
}
if ($name === '' || mb_strlen($name) > 50) {
    sendError(400, 'Le nom de la catégorie est requis et ne peut pas dépasser 50 caractères');
}
if ($parentId === false) {
    sendError(400, 'Catégorie parente invalide');
}

// Champs vides ignorés, doublons refusés
$fieldNames = array_values(array_filter(array_map(fn($field) => trim((string) $field), $fieldNames), fn($field) => $field !== ''));
if (count($fieldNames) > MAX_FIELDS) {
    sendError(400, 'Une catégorie ne peut pas avoir plus de ' . MAX_FIELDS . ' champs personnalisés');
}
foreach ($fieldNames as $fieldName) {
    if (mb_strlen($fieldName) > 45) {
        sendError(400, 'Le nom d\'un champ ne peut pas dépasser 45 caractères');
    }
}
if (count(array_unique(array_map('mb_strtolower', $fieldNames))) !== count($fieldNames)) {
    sendError(400, 'Deux champs personnalisés portent le même nom');
}

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
    $category = $categoryManager->createCategory($name, $parentId, $fieldNames);
} catch (Exception $e) {
    error_log('Création de la catégorie impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la création de la catégorie');
}

echo json_encode(['success' => true, 'category_id' => $category->getId()]);
