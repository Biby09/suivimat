<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$name = trim($_POST['organisation_name'] ?? '');

if ($name === '' || mb_strlen($name) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le nom doit contenir entre 1 et 100 caractères']);
    exit;
}

try {
    $organisation = $organisationManager->createOrganisation($name);
} catch (Exception $exception) {
    error_log('Création d\'organisation impossible : ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de l\'organisation']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Organisation créée', 'redirect' => '/dashboard/organisation/?id=' . $organisation->getId()]);
exit;
