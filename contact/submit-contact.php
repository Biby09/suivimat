<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false]);
    exit;
}

$name = trim($_POST['name']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);

if (!$email || empty($subject) || empty($message)) {
    echo json_encode(["success" => false]);
    exit;
}

$message_content = "Nom : $name\nEmail : $email\n\n Objet: $subject\nMessage :\n$message";

$success = mail('webmaster@florianlovis.ch', $subject, $message_content, "Reply-To: $email\r\n");

echo json_encode(["success" => $success]);