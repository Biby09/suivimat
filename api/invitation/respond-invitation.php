<?php
// Réponse à une invitation reçue (tableau de bord, scripts/invitations.js).
// POST : invitation_id, action (« accept » : devenir membre, « reject » : refuser)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php'; // $invitationManager : invitations de l'utilisateur connecté

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

$invitationId = filter_input(INPUT_POST, 'invitation_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$invitationId || !in_array($action, ['accept', 'reject'], true)) {
    sendError(400, 'Requête invalide');
}

try {
    // Seulement une invitation adressée à l'utilisateur connecté et encore en attente
    $organisationId = $invitationManager->respond($invitationId, $action === 'accept');
} catch (Exception $e) {
    sendError(404, 'Cette invitation n\'existe plus ou a déjà reçu une réponse');
}

// Acceptée : la page peut ouvrir l'inventaire de l'organisation rejointe
echo json_encode([
    'success' => true,
    'redirect' => $action === 'accept' ? '/dashboard/organisation/?id=' . $organisationId : null,
]);
