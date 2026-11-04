<?php
// Annule une invitation en attente (page « Paramètres », section Membres).
// POST : organisation_id, invitation_id

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

// cancel() ne touche qu'aux invitations de CETTE organisation encore en attente
if (!$invitationManager->cancel($organisation, (int) filter_input(INPUT_POST, 'invitation_id', FILTER_VALIDATE_INT))) {
    sendError(404, 'Cette invitation n\'existe plus ou a déjà reçu une réponse');
}

echo json_encode(['success' => true]);
