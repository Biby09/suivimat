<?php
// Supprime le compte de l'utilisateur connecté (page « Mon compte », zone « Supprimer mon compte », scripts/account.js).
// POST : password (confirmation)
// Refusé si l'utilisateur est le seul membre d'une organisation : elle n'aurait plus personne pour la gérer.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php'; // $user : utilisateur connecté

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

if (!$user->passwordVerify($_POST['password'] ?? '')) {
    sendError(403, 'Mot de passe incorrect');
}

$soleOrganisations = $userManager->getSoleMemberOrganisationNames($user);
if ($soleOrganisations) {
    sendError(409, 'Vous êtes le seul membre de : ' . implode(', ', $soleOrganisations) . '. Invitez quelqu\'un d\'autre dans ces organisations avant de supprimer votre compte.');
}

try {
    $userManager->deleteUser($user);
} catch (Exception $e) {
    error_log('Suppression du compte impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la suppression du compte');
}

// Déconnexion : la session ne doit plus désigner un compte qui n'existe plus
$_SESSION = [];
session_destroy();

echo json_encode(['success' => true, 'redirect' => '/']);
