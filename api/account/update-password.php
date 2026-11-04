<?php
// Change le mot de passe de l'utilisateur connecté (page « Mon compte », scripts/account.js).
// POST : current_password, new_password, confirm_password

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

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($newPassword !== $confirmPassword) {
    sendError(400, 'Les deux nouveaux mots de passe ne sont pas identiques');
}
if ($newPassword === $currentPassword) {
    sendError(400, 'Le nouveau mot de passe doit être différent de l\'actuel');
}

try {
    $userManager->updateUserPassword($user, $currentPassword, $newPassword);
} catch (InvalidArgumentException $e) {
    // Règles du mot de passe (User::setPassword) : le message est déjà en français
    sendError(400, $e->getMessage());
} catch (Exception $e) {
    sendError(403, 'Mot de passe actuel incorrect');
}

// Nouvel identifiant de session : une session volée avant le changement ne fonctionne plus
session_regenerate_id(true);

echo json_encode(['success' => true]);
