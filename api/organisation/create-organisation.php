<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_POST['organisation_name']) || empty(trim($_POST['organisation_name']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Le nom de l\'organisation est requis']);
    exit;
}

$organisationName = trim($_POST['organisation_name']);

if (mb_strlen($organisationName) > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Le nom de l\'organisation ne peut pas dépasser 100 caractères']);
    exit;
}

try {
    $newOrganisation = $organisationManager->createOrganisation($organisationName);
    echo json_encode(['success' => true, 'organisation_id' => $newOrganisation->getId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la création de l\'organisation: ' . $e->getMessage()]);
}