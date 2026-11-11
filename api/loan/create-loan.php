<?php
// Prête un article (dialog « Prêter » de la fiche d'un objet, scripts/article-loan.js).
// POST : article_id, client_id (ID d'un client, ou « new » avec client_firstname et client_lastname),
//        start_date (AAAA-MM-JJ), due_date (AAAA-MM-JJ, optionnel : vide = retour indéterminé)
// Nouveau client + prêt : tout ou rien (si le prêt échoue, le client n'est pas créé).

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
// Client et dates : $clientId, $newClient, $firstname, $lastname, $startDate, $dueDate
require $_SERVER['DOCUMENT_ROOT'] . '/includes/loan-form.php';

try {
    // Vérifie aussi que l'utilisateur est membre de l'organisation de l'article
    $organisation = $organisationManager->getOrganisationOfArticle((int) $articleId);
    $article = (new ArticleManager($mysqlClient, $organisation))->getArticleById((int) $articleId);
} catch (Exception $e) {
    sendError(404, 'Objet introuvable');
}

if ($article->getStatus() !== 'Disponible') {
    sendError(409, 'Cet objet n\'est pas disponible (statut : ' . $article->getStatus() . ')');
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
    // Utilise la transaction ouverte ci-dessus (voir LoanManager::createLoan)
    (new LoanManager($mysqlClient, $organisation))->createLoan($article, $client, $startDate, $dueDate);
    $mysqlClient->commit();
} catch (ArticleNotAvailableException $e) {
    // Prêté entre-temps par quelqu'un d'autre
    $mysqlClient->rollBack();
    sendError(409, 'Cet objet vient d\'être prêté par quelqu\'un d\'autre');
} catch (Exception $e) {
    $mysqlClient->rollBack();
    error_log('Création du prêt impossible : ' . $e->getMessage());
    sendError(500, 'Erreur lors de l\'enregistrement du prêt');
}

echo json_encode(['success' => true]);
