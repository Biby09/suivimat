<?php
// Enregistre le retour d'un objet emprunté (dialog « Enregistrer un retour » de la fiche, scripts/article-return.js).
// POST : article_id, end_date (AAAA-MM-JJ), damaged (« 1 » si dégâts constatés),
//        damage_description (requise si damaged), photos[] (photos du dégât, optionnelles)
// Retour + dégât + photos : tout ou rien.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/imageManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_PHOTOS = 10;
const MAX_DESCRIPTION = 2000;

function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Méthode non autorisée');
}

// post_max_size dépassé : PHP vide $_POST et $_FILES
if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    sendError(413, 'Les photos sont trop volumineuses');
}

$articleId = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
$endDate = parseDate(trim($_POST['end_date'] ?? '')); // config/init.php
$damaged = ($_POST['damaged'] ?? '') === '1';
$description = trim($_POST['damage_description'] ?? '');

if ($endDate === null) {
    sendError(400, 'La date de retour est invalide');
}
if ($damaged && $description === '') {
    sendError(400, 'Décrivez les dégâts constatés');
}
if (mb_strlen($description) > MAX_DESCRIPTION) {
    sendError(400, 'La description ne peut pas dépasser ' . MAX_DESCRIPTION . ' caractères');
}

try {
    $photoPaths = $damaged ? ImageManager::getUploadedPhotos('photos', MAX_PHOTOS) : [];
} catch (InvalidArgumentException $e) {
    sendError(400, $e->getMessage());
}

try {
    // Vérifie aussi que l'utilisateur est membre de l'organisation de l'article
    $organisation = $organisationManager->getOrganisationOfArticle((int) $articleId);
    $article = (new ArticleManager($mysqlClient, $organisation))->getArticleById((int) $articleId);
} catch (Exception $e) {
    sendError(404, 'Objet introuvable');
}

if ($article->getStatus() !== 'Emprunté') {
    sendError(409, 'Cet objet n\'est pas emprunté (statut : ' . $article->getStatus() . ')');
}

$loanManager = new LoanManager($mysqlClient, $organisation);
$loan = $loanManager->getOngoingLoan($article);

if ($loan === null && $damaged) {
    sendError(409, 'Aucun prêt en cours n\'est enregistré pour cet objet : les dégâts ne peuvent pas y être rattachés');
}
if ($loan !== null && $endDate < $loan->getStartDate()) {
    sendError(400, 'La date de retour ne peut pas être avant la date de sortie (' . date('d.m.Y', strtotime($loan->getStartDate())) . ')');
}

$mysqlClient->beginTransaction();
try {
    $damageId = $loanManager->returnLoan($article, $loan, $endDate, $damaged ? $description : null);
    if ($damageId !== null && $photoPaths) {
        (new ImageManager($mysqlClient))->addArticleImages($article, $photoPaths, $damageId);
    }
    $mysqlClient->commit();
} catch (LoanAlreadyReturnedException $e) {
    $mysqlClient->rollBack();
    sendError(409, 'Le retour de cet objet vient d\'être enregistré par quelqu\'un d\'autre');
} catch (Exception $e) {
    $mysqlClient->rollBack();
    error_log('Enregistrement du retour impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'enregistrement du retour');
}

echo json_encode(['success' => true]);
