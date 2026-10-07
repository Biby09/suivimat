<?php
// Liste des clients de l'organisation

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';

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

$clients = array_map(fn(Client $client) => [
    'id' => $client->getId(),
    'firstname' => $client->getFirstname(),
    'lastname' => $client->getLastname(),
], (new ClientManager($mysqlClient, $organisation))->getAllClients());

echo json_encode(['success' => true, 'clients' => $clients]);
