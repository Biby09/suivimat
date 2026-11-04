<?php
// Modifie le prénom, le nom et l'e-mail d'un client (bouton « Modifier » de la page d'un client, scripts/client-form-modal.js)
// POST : client_id, firstname, lastname, email (facultatif : vide = aucun)

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

$clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$email = parseOptionalEmail($_POST['email'] ?? ''); // null si vide, false si invalide (config/init.php)

if ($firstname === '' || $lastname === '') {
    sendError(400, 'Le prénom et le nom sont requis');
}
if (mb_strlen($firstname) > 50 || mb_strlen($lastname) > 50) {
    sendError(400, 'Le prénom et le nom ne peuvent pas dépasser 50 caractères');
}
if ($email === false) {
    sendError(400, 'L\'adresse e-mail est invalide');
}

try {
    // Vérifie que l'utilisateur est membre de l'organisation du client
    $organisation = $organisationManager->getOrganisationOfClient((int) $clientId);
    $clientManager = new ClientManager($mysqlClient, $organisation);
    $client = $clientManager->getClientById((int) $clientId);
} catch (Exception $e) {
    sendError(404, 'Client introuvable');
}

try {
    $clientManager->updateClient($client, $firstname, $lastname, $email);
} catch (Exception $e) {
    error_log('Modification du client impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la modification du client');
}

echo json_encode(['success' => true, 'client_id' => $client->getId()]);
