<?php
// Invite une personne à rejoindre l'organisation (page « Paramètres », section Membres).
// POST : organisation_id, email
// La personne doit déjà avoir un compte SuiviMat ; elle reçoit un e-mail et voit l'invitation sur son tableau de bord.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php'; // $user, $userManager, $organisationManager, $invitationManager

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

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    sendError(400, 'Indiquez une adresse e-mail valide');
}

try {
    $organisation = $organisationManager->getOrganisationById((int) filter_input(INPUT_POST, 'organisation_id', FILTER_VALIDATE_INT));
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

try {
    $invitee = $userManager->getUserByEmail($email);
} catch (Exception $e) {
    sendError(404, 'Aucun compte SuiviMat n\'utilise cette adresse. Demandez à la personne de créer un compte, puis invitez-la.');
}

if ($organisationManager->isMember($organisation, $invitee->getId())) {
    sendError(409, $invitee->getFullname() . ' fait déjà partie de l\'organisation');
}
if ($invitationManager->hasPendingInvitation($organisation, $invitee->getId())) {
    sendError(409, $invitee->getFullname() . ' a déjà une invitation en attente');
}

try {
    $invitationManager->invite($organisation, $invitee);
} catch (Exception $e) {
    error_log('Invitation impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'invitation');
}

// E-mail à la personne invitée (en local : écrit dans le journal, pas envoyé — voir sendMail() dans config/init.php)
$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
$dashboardUrl = ($isHttps ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/dashboard';
sendMail(
    $invitee->getEmail(),
    'Invitation à rejoindre ' . $organisation->getName() . ' sur SuiviMat',
    "Bonjour " . $invitee->getFirstname() . ",\n\n"
        . $user->getFullname() . " vous invite à rejoindre l'organisation « " . $organisation->getName() . " » sur SuiviMat.\n\n"
        . "Connectez-vous pour accepter ou refuser l'invitation : " . $dashboardUrl . "\n",
    $user->getEmail()
);

echo json_encode(['success' => true, 'message' => 'Invitation envoyée à ' . $invitee->getFullname()]);
