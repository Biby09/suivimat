<?php
// Remet disponible un objet vendu : la vente est annulée mais gardée dans la base
// (dialog « Remettre disponible » de la fiche d'un objet, scripts/article-unsell.js).
// POST : article_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/sale/saleManager.php';

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

$articleId = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);

try {
    // Vérifie aussi que l'utilisateur est membre de l'organisation de l'article
    $organisation = $organisationManager->getOrganisationOfArticle((int) $articleId);
    $article = (new ArticleManager($mysqlClient, $organisation))->getArticleById((int) $articleId);
} catch (Exception $e) {
    sendError(404, 'Objet introuvable');
}

try {
    (new SaleManager($mysqlClient, $organisation))->cancelSale($article);
} catch (SaleNotCancellableException $e) {
    sendError(409, 'Cet objet n\'est plus vendu : rechargez la page');
} catch (Exception $e) {
    error_log('Annulation de la vente impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'enregistrement');
}

echo json_encode(['success' => true]);
