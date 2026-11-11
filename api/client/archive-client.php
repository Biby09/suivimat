<?php
// Archive un client ou le réactive (bouton « Archiver » / « Réactiver » de la page d'un client, scripts/client-archive.js)
// POST : client_id, archived (« 1 » = archiver, « 0 » = réactiver)
// Le client et son historique restent dans la base : il n'est simplement plus proposé pour un prêt ou une vente.

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
$archived = $_POST['archived'] ?? '';

if (!in_array($archived, ['0', '1'], true)) {
    sendError(400, 'Action invalide');
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
    $client = $clientManager->setArchived($client, $archived === '1');
} catch (ClientHasOngoingLoansException $e) {
    sendError(409, 'Ce client a encore un prêt en cours : enregistrez d\'abord le retour du matériel');
} catch (Exception $e) {
    error_log('Archivage du client impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'archivage du client');
}

echo json_encode(['success' => true, 'client_id' => $client->getId(), 'archived' => $client->isArchived()]);
