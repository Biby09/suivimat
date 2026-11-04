<?php
// Passe un objet en mode « perdu » (dialog « Marquer comme perdu » de la fiche d'un objet, scripts/article-lost.js).
// POST : article_id, loss_date (AAAA-MM-JJ), comment (optionnel : où / comment l'objet a été perdu)
// Objet prêté : le prêt en cours est clôturé à la date de la perte.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loss/lossManager.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_COMMENT = 1000;

function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Méthode non autorisée');
}

$articleId = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
$lossDate = parseDate(trim($_POST['loss_date'] ?? '')); // config/init.php
$comment = trim($_POST['comment'] ?? '');

if ($lossDate === null) {
    sendError(400, 'La date de la perte est invalide');
}
if (mb_strlen($comment) > MAX_COMMENT) {
    sendError(400, 'La remarque ne peut pas dépasser ' . MAX_COMMENT . ' caractères');
}

try {
    // Vérifie aussi que l'utilisateur est membre de l'organisation de l'article
    $organisation = $organisationManager->getOrganisationOfArticle((int) $articleId);
    $article = (new ArticleManager($mysqlClient, $organisation))->getArticleById((int) $articleId);
} catch (Exception $e) {
    sendError(404, 'Objet introuvable');
}

if (!in_array($article->getStatus(), ['Disponible', 'Emprunté'], true)) {
    sendError(409, 'Cet objet ne peut pas être marqué comme perdu (statut : ' . $article->getStatus() . ')');
}

// Prêt en cours : clôturé à la date de la perte, qui ne peut donc pas être avant la sortie
$ongoingLoan = (new LoanManager($mysqlClient, $organisation))->getOngoingLoan($article);
if ($ongoingLoan !== null && $lossDate < $ongoingLoan->getStartDate()) {
    sendError(400, 'La date de la perte ne peut pas être avant le début du prêt en cours (' . date('d.m.Y', strtotime($ongoingLoan->getStartDate())) . ')');
}

try {
    (new LossManager($mysqlClient, $organisation))->markLost($article, $lossDate, $comment === '' ? null : $comment, $ongoingLoan);
} catch (LossConflictException $e) {
    sendError(409, 'Cet objet vient d\'être modifié par quelqu\'un d\'autre : rechargez la page');
} catch (Exception $e) {
    error_log('Déclaration de perte impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'enregistrement de la perte');
}

echo json_encode(['success' => true]);
