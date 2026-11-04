<?php
// Supprime l'organisation et toutes ses données (page « Paramètres », zone « Supprimer l'organisation »).
// POST : organisation_id, confirm_name (doit être exactement le nom de l'organisation : évite une suppression par erreur)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

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

try {
    $organisation = $organisationManager->getOrganisationById((int) filter_input(INPUT_POST, 'organisation_id', FILTER_VALIDATE_INT));
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

if (trim($_POST['confirm_name'] ?? '') !== $organisation->getName()) {
    sendError(400, 'Le nom saisi ne correspond pas au nom de l\'organisation');
}

try {
    $organisationManager->deleteOrganisation($organisation);
} catch (Exception $e) {
    error_log('Suppression de l\'organisation impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la suppression de l\'organisation');
}

echo json_encode(['success' => true, 'redirect' => '/dashboard']);
