<?php
// Marque un objet comme vendu (dialog « Marquer comme vendu » de la fiche d'un objet, scripts/article-sale.js).
// POST : article_id, client_id (acheteur : ID d'un client, ou « new » avec client_firstname et client_lastname),
//        sale_date (AAAA-MM-JJ)
// Objet prêté : le prêt en cours est clôturé à la date de vente. Nouvel acheteur + vente : tout ou rien.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';
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
$saleDate = parseDate(trim($_POST['sale_date'] ?? '')); // config/init.php
// Acheteur : $clientId, $newClient, $firstname, $lastname
require $_SERVER['DOCUMENT_ROOT'] . '/includes/client-form.php';

if ($saleDate === null) {
    sendError(400, 'La date de vente est invalide');
}

try {
    // Vérifie aussi que l'utilisateur est membre de l'organisation de l'article
    $organisation = $organisationManager->getOrganisationOfArticle((int) $articleId);
    $article = (new ArticleManager($mysqlClient, $organisation))->getArticleById((int) $articleId);
} catch (Exception $e) {
    sendError(404, 'Objet introuvable');
}

if (!in_array($article->getStatus(), ['Disponible', 'Emprunté'], true)) {
    sendError(409, 'Cet objet ne peut pas être vendu (statut : ' . $article->getStatus() . ')');
}

// Prêt en cours : clôturé à la date de vente, qui ne peut donc pas être avant la sortie
$ongoingLoan = (new LoanManager($mysqlClient, $organisation))->getOngoingLoan($article);
if ($ongoingLoan !== null && $saleDate < $ongoingLoan->getStartDate()) {
    sendError(400, 'La date de vente ne peut pas être avant le début du prêt en cours (' . date('d.m.Y', strtotime($ongoingLoan->getStartDate())) . ')');
}

$clientManager = new ClientManager($mysqlClient, $organisation);
if (!$newClient) {
    try {
        $client = $clientManager->getClientById((int) $clientId); // client de la même organisation uniquement
    } catch (Exception $e) {
        sendError(404, 'Client introuvable');
    }
    if ($client->isArchived()) {
        sendError(409, 'Ce client est archivé : réactivez-le depuis sa page pour l\'utiliser');
    }
}

$mysqlClient->beginTransaction();
try {
    if ($newClient) {
        $client = $clientManager->createClient($firstname, $lastname, $clientEmail);
    }
    (new SaleManager($mysqlClient, $organisation))->sellArticle($article, $client, $saleDate, $ongoingLoan);
    $mysqlClient->commit();
} catch (ArticleNotSellableException $e) {
    $mysqlClient->rollBack();
    sendError(409, 'Cet objet vient d\'être modifié par quelqu\'un d\'autre : rechargez la page');
} catch (Exception $e) {
    $mysqlClient->rollBack();
    error_log('Vente impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'enregistrement de la vente');
}

echo json_encode(['success' => true]);
