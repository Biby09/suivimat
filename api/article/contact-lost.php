<?php
// Message de la personne qui a trouvé un objet perdu (page publique includes/lost-article-public.php) :
// envoyé par e-mail à tous les membres de l'organisation, « Répondre » écrit directement à la personne.
// PUBLIC : accessible sans être connecté. POST : code, name (optionnel), email, phone (optionnel), message,
//          website (champ piège invisible, doit rester vide)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
// Pas de config/auth.php : la page doit fonctionner sans être connecté
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisationManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_MESSAGE = 2000;
const MIN_DELAY = 60; // secondes entre deux messages d'un même visiteur

function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Méthode non autorisée');
}

$code = strtoupper(trim($_POST['code'] ?? ''));
$name = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = mb_substr(trim($_POST['phone'] ?? ''), 0, 30);
$message = trim($_POST['message'] ?? '');

// Champ piège : invisible pour un humain, rempli par les robots qui complètent tous les champs
if (($_POST['website'] ?? '') !== '') {
    echo json_encode(['success' => true]); // le robot croit avoir réussi, rien n'est envoyé
    exit;
}
if (!preg_match('/^[A-Z0-9]{4}$/', $code)) {
    sendError(400, 'Code invalide');
}
if (!$email) {
    sendError(400, 'Indiquez une adresse e-mail valide pour que l\'organisation puisse vous répondre');
}
if ($message === '' || mb_strlen($message) > MAX_MESSAGE) {
    sendError(400, 'Le message est requis et ne peut pas dépasser ' . MAX_MESSAGE . ' caractères');
}

// Un message par minute et par visiteur (session) : limite les envois en masse
$lastSent = $_SESSION['lost_contact_sent_at'] ?? 0;
if (time() - $lastSent < MIN_DELAY) {
    sendError(429, 'Vous venez d\'envoyer un message. Réessayez dans une minute.');
}

$organisation = OrganisationManager::findByArticleCode($mysqlClient, $code);
if ($organisation === null) {
    sendError(404, 'Objet introuvable');
}
$article = (new ArticleManager($mysqlClient, $organisation))->getArticleByCode($code);

// Seuls les objets perdus peuvent être signalés : le formulaire ne sert pas à écrire à n'importe quelle organisation
// … et seulement si l'organisation a laissé la page « objet perdu » activée (Paramètres › Objets perdus)
if ($article->getStatus() !== 'Perdu' || !$organisation->isLostPageEnabled()) {
    sendError(409, 'Cet objet n\'est pas signalé comme perdu');
}

$recipients = $userManager->getEmailsOfOrganisation($organisation->getId());
$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
$articleUrl = ($isHttps ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/dashboard/organisation/article/?article_id=' . $article->getId();

$subject = 'Objet perdu retrouvé : ' . $article->getName() . ' (' . $article->getCode() . ')';
$body = "Bonjour,\n\n"
    . "Quelqu'un a scanné le code QR de l'objet perdu « " . $article->getName() . " » (" . $article->getCode() . ") "
    . "de l'organisation « " . $organisation->getName() . " » et vous a écrit :\n\n"
    . $message . "\n\n"
    . "Nom : " . ($name !== '' ? $name : '(non indiqué)') . "\n"
    . "E-mail : " . $email . "\n"
    . "Téléphone : " . ($phone !== '' ? $phone : '(non indiqué)') . "\n\n"
    . "Répondez directement à cet e-mail pour lui écrire.\n"
    . "Fiche de l'objet : " . $articleUrl . "\n";

$sent = 0;
foreach ($recipients as $recipient) {
    if (sendMail($recipient, $subject, $body, $email)) { // config/init.php
        $sent++;
    }
}

if ($sent === 0) {
    error_log('Message objet perdu : aucun e-mail envoyé pour ' . $code);
    sendError(500, 'Le message n\'a pas pu être envoyé. Réessayez plus tard.');
}

$_SESSION['lost_contact_sent_at'] = time();
echo json_encode(['success' => true]);
