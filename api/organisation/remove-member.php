<?php
// Retire un membre de l'organisation, ou quitte l'organisation si user_id est l'utilisateur connecté
// (page « Paramètres », section Membres).
// POST : organisation_id, user_id
// Le dernier membre ne peut pas partir : l'organisation n'aurait plus personne (il peut la supprimer à la place).

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

$memberId = (int) filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

try {
    $organisation = $organisationManager->getOrganisationById((int) filter_input(INPUT_POST, 'organisation_id', FILTER_VALIDATE_INT));
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

if (!$organisationManager->isMember($organisation, $memberId)) {
    sendError(404, 'Cette personne ne fait pas partie de l\'organisation');
}
if (count($organisationManager->getMembers($organisation)) <= 1) {
    sendError(409, 'Vous êtes le dernier membre : invitez quelqu\'un d\'autre avant de partir, ou supprimez l\'organisation');
}

$organisationManager->removeMember($organisation, $memberId);

// Quitter : l'organisation n'est plus accessible, retour au tableau de bord
$leaving = $memberId === $user->getId();
echo json_encode(['success' => true, 'redirect' => $leaving ? '/dashboard' : null]);
