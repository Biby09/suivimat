<?php
// Sert un PDF de codes QR généré par generate-qr-codes.php, avec son nom de fichier
// (« etiquettes-qr-mon-association-2026-11-04-1932.pdf ») : c'est ce nom que le navigateur propose à l'enregistrement.
// GET : token (renvoyé par generate-qr-codes.php). Seule la session qui a généré le PDF peut l'ouvrir.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : ''; // ?token[]= donnerait un tableau
$fileName = $_SESSION['qr_pdfs'][$token] ?? null;
// Le jeton doit être exactement 32 caractères hexadécimaux : il ne peut pas contenir « ../ » pour sortir du dossier
$path = QR_PDF_DIRECTORY . '/' . $token . '.pdf';

if ($fileName === null || !preg_match('/^[a-f0-9]{32}$/', $token) || !is_file($path)) {
    http_response_code(404);
    exit('PDF introuvable ou expiré : générez à nouveau les codes.');
}

header('Content-Type: application/pdf');
// inline : affiché dans le navigateur ; filename : nom proposé quand on l'enregistre
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
readfile($path);
