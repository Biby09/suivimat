<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_POST['firstname'], $_POST['lastname'], $_POST['email'], $_POST['password'], $_POST['confirmPassword'], $_POST['g-recaptcha-response'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis.']);
    exit;
}

$firstname = trim($_POST['firstname']);
$lastname = trim($_POST['lastname']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'];
$confirmPassword = $_POST['confirmPassword'];

if (!$email || empty($password) || empty($firstname) || empty($lastname)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email, prénom, nom et mot de passe sont requis.']);
    exit;
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.']);
    exit;
}

//Création de l'utilisateur
$user = new User();
$user->setFirstname($firstname);
$user->setLastname($lastname);
$user->setEmail($email);

try {
    $user->setPassword($password);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Vérification du reCAPTCHA
$token = $_POST['g-recaptcha-response'] ?? '';

if ($token === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Veuillez valider le captcha.']);
    exit;
}

$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'secret' => getenv('RECAPTCHA_SECRET_KEY'),
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'],
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
]);
$result = json_decode(curl_exec($ch) ?: '', true);

if (empty($result['success'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Captcha invalide, veuillez réessayer.']);
    exit;
}

try {
    $userManager->getUserByEmail($email);
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Un compte avec cet email existe déjà.']);
    exit;

} catch (Exception $e) {
    // L'utilisateur n'existe pas, on peut continuer
}

// Enregistrement de l'utilisateur
try {
    $user = $userManager->createUser($user);
    http_response_code(201);
    $_SESSION['user_id'] = $user->getId();
    echo json_encode(['success' => true, 'message' => 'Inscription réussie. Vous allez être redirigé.', 'redirect' => '/dashboard']);

} catch (Exception $e) {
    error_log('Erreur lors de la création de l\'utilisateur : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer plus tard.']);
}