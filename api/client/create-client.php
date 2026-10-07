<?php
// Ajout d'un client à l'organisation

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';

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
$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');

if (!$organisationId) {
    sendError(400, 'ID d\'organisation invalide');
}
if ($firstname === '' || $lastname === '') {
    sendError(400, 'Le prénom et le nom sont requis');
}
if (mb_strlen($firstname) > 50 || mb_strlen($lastname) > 50) {
    sendError(400, 'Le prénom et le nom ne peuvent pas dépasser 50 caractères');
}

try {
    $organisation = $organisationManager->getOrganisationById($organisationId);
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

try {
    $client = (new ClientManager($mysqlClient, $organisation))->createClient($firstname, $lastname);
} catch (Exception $e) {
    error_log('Création du client impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'ajout du client');
}

echo json_encode(['success' => true, 'client_id' => $client->getId()]);
