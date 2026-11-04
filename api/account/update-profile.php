<?php
// Modifie les informations personnelles de l'utilisateur connecté (page « Mon compte », scripts/account.js).
// POST : firstname, lastname, email, current_password (obligatoire seulement si l'e-mail change :
//        il sert à se connecter, on vérifie donc que c'est bien le titulaire du compte qui le modifie)

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

$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$currentPassword = $_POST['current_password'] ?? '';

if ($firstname === '' || $lastname === '') {
    sendError(400, 'Le prénom et le nom sont requis');
}
if (mb_strlen($firstname) > 50 || mb_strlen($lastname) > 50) {
    sendError(400, 'Le prénom et le nom ne peuvent pas dépasser 50 caractères');
}
if (!$email || mb_strlen($email) > 50) {
    sendError(400, 'L\'adresse e-mail est invalide (50 caractères au maximum)');
}

// strcasecmp : comparaison sans tenir compte des majuscules (« Jean@x.ch » = « jean@x.ch ») ; 0 = identiques
$emailChanged = strcasecmp($email, $user->getEmail()) !== 0;
if ($emailChanged && !$user->passwordVerify($currentPassword)) {
    sendError(403, 'Mot de passe actuel incorrect : il est nécessaire pour changer d\'adresse e-mail');
}

$user->setFirstname($firstname);
$user->setLastname($lastname);
$user->setEmail($email);

try {
    $userManager->updateUser($user);
} catch (EmailAlreadyUsedException $e) {
    sendError(409, 'Cette adresse e-mail est déjà utilisée par un autre compte');
} catch (Exception $e) {
    error_log('Modification du compte impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'enregistrement');
}

echo json_encode(['success' => true]);
