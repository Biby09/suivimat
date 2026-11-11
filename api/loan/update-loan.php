<?php
// Modifie un prêt en cours (bouton « Modifier » de l'historique de la fiche d'un objet, scripts/article-loan.js) :
// mauvais client, mauvaise date, prolongation…
// POST : article_id, loan_id, puis les champs du dialog de prêt (includes/loan-form.php) :
//        client_id (ou « new » avec client_firstname et client_lastname), start_date, due_date (optionnel)
// Nouveau client + modification : tout ou rien.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';

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
$loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);
// Client et dates : $clientId, $newClient, $firstname, $lastname, $startDate, $dueDate
require $_SERVER['DOCUMENT_ROOT'] . '/includes/loan-form.php';

try {
    // Vérifie que l'utilisateur est membre de l'organisation de l'article…
    $organisation = $organisationManager->getOrganisationOfArticle((int) $articleId);
    $loanManager = new LoanManager($mysqlClient, $organisation);
    // … et que le prêt appartient à la même organisation (getLoanById) et au même article
    $loan = $loanManager->getLoanById((int) $loanId);
} catch (Exception $e) {
    sendError(404, 'Prêt introuvable');
}

if ($loan->getArticleId() !== (int) $articleId) {
    sendError(404, 'Prêt introuvable');
}
if (!$loan->isOngoing()) {
    sendError(409, 'Ce prêt est terminé : il ne peut plus être modifié');
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
    $loanManager->updateLoan($loan, $client, $startDate, $dueDate);
    $mysqlClient->commit();
} catch (Exception $e) {
    $mysqlClient->rollBack();
    error_log('Modification du prêt impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de la modification du prêt');
}

echo json_encode(['success' => true]);
