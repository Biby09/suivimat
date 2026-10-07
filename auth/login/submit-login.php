<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if (isset($_SESSION['user_id'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/auth/logout.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}


if (!isset($_POST['email'], $_POST['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
    exit;
}

$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'];

if (!$email || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email ou mot de passe invalide']);
    exit;
}

$user = $userManager->getUserByEmail($email);

if (! $user->passwordVerify($password)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
    exit;
}

$_SESSION['user_id'] = $user->getId();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Connexion réussie', 'redirect' => $_GET['redirect'] ?? '/dashboard']);
exit;