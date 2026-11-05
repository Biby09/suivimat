<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/imageManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/sale/saleManager.php';

/*
    Page d'un client : /dashboard/organisation/clients/client.php?client_id=…
    Ouverte depuis la liste des clients (dashboard/organisation/clients/).
    - « Modifier » : prénom et nom (includes/modals/client-form-modal.php) ;
    - prêts en cours : mêmes cartes que la page « Prêts en cours » (includes/loan-card.php), avec le bouton « Retour » ;
    - historique : prêts terminés et achats (ventes non annulées), du plus récent au plus ancien ; un clic ouvre la fiche de l'objet.
*/

try {
    // Lève une exception si le client n'existe pas ou appartient à une organisation dont l'utilisateur n'est pas membre
    $organisation = $organisationManager->getOrganisationOfClient((int) ($_GET['client_id'] ?? 0));
    $client = (new ClientManager($mysqlClient, $organisation))->getClientById((int) $_GET['client_id']);
} catch (Exception $e) {
    http_response_code(404);
    require $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
    exit;
}

$loans = (new LoanManager($mysqlClient, $organisation))->getLoansForClient($client);
// array_filter garde les prêts pour lesquels la fonction renvoie true ; array_values renumérote 0, 1, 2…
$ongoingLoans = array_values(array_filter($loans, fn(Loan $loan) => $loan->isOngoing()));
$pastLoans = array_values(array_filter($loans, fn(Loan $loan) => !$loan->isOngoing()));
// Historique : prêts terminés (date de retour) et achats (date de vente) mélangés, du plus récent au plus ancien
$history = [];
foreach ($pastLoans as $loan) {
    $history[] = ['date' => $loan->getEndDate(), 'loan' => $loan, 'sale' => null];
}
foreach ((new SaleManager($mysqlClient, $organisation))->getSalesForClient($client) as $sale) {
    $history[] = ['date' => $sale->getDate(), 'loan' => null, 'sale' => $sale];
}
// usort trie avec une fonction de comparaison ; <=> (« spaceship ») renvoie -1, 0 ou 1.
// $b <=> $a (et non $a <=> $b) : ordre décroissant. Les dates AAAA-MM-JJ se comparent comme des chaînes.
usort($history, fn(array $a, array $b) => $b['date'] <=> $a['date']);
$coverUrls = (new ImageManager($mysqlClient))->getCoverUrls(array_map(fn(Loan $loan) => $loan->getArticleId(), $ongoingLoans));
$clientsUrl = '/dashboard/organisation/clients/?id=' . $organisation->getId();
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = $client->getFullname() . ' | Clients'; // échappé par head.php
$page_description = 'Prêts du client';
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<!-- app : header de l'application (includes/app-header.php) -->
<body class="app">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

<main class="container py-4">
    <nav aria-label="Chemin d'accès">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item"><a href="<?= $clientsUrl ?>">Clients</a></li>
            <li class="breadcrumb-item active text-break" aria-current="page"><?= htmlspecialchars($client->getFullname()) ?></li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-3 mb-4">
        <span class="client-avatar client-avatar-lg flex-shrink-0" aria-hidden="true">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($client->getFirstname(), 0, 1) . mb_substr($client->getLastname(), 0, 1))) ?>
        </span>
        <div class="flex-grow-1 text-break" style="min-width: 0;">
            <h1 class="fs-3 mb-0"><?= htmlspecialchars($client->getFullname()) ?></h1>
            <?php if ($client->getEmail()): ?>
                <!-- mailto: : ouvre la messagerie de l'appareil avec l'adresse du client ;
                     text-truncate : sur une ligne, « … » si trop longue (le lien garde l'adresse complète) -->
                <a href="mailto:<?= htmlspecialchars($client->getEmail()) ?>" class="d-block text-truncate mb-1" title="<?= htmlspecialchars($client->getEmail()) ?>"><?= htmlspecialchars($client->getEmail()) ?></a>
            <?php endif; ?>
            <p class="text-muted mb-0">
                <?= count($ongoingLoans) ?> prêt<?= count($ongoingLoans) > 1 ? 's' : '' ?> en cours · <?= count($loans) ?> au total
            </p>
        </div>
        <button type="button" class="btn btn-outline-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#client-form-modal">Modifier</button>
    </div>

    <section class="mb-4" aria-labelledby="client-ongoing-title">
        <h2 class="fs-6 text-uppercase text-muted mb-2" id="client-ongoing-title">Prêts en cours</h2>
        <?php if (!$ongoingLoans): ?>
            <p class="text-muted">Aucun prêt en cours.</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-lg-2 g-3">
                <?php foreach ($ongoingLoans as $index => $loan): ?>
                    <?php
                    // Carte du prêt (includes/loan-card.php), sans répéter le nom du client
                    $loanCoverUrl = $coverUrls[$loan->getArticleId()] ?? null;
                    $loanOrder = $index;
                    $loanShowClient = false;
                    include $_SERVER['DOCUMENT_ROOT'] . '/includes/loan-card.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section aria-labelledby="client-history-title">
        <h2 class="fs-6 text-uppercase text-muted mb-2" id="client-history-title">Historique</h2>
        <?php if (!$history): ?>
            <p class="text-muted">Aucun prêt terminé ni achat.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($history as $entry): ?>
                    <?php
                    // Une ligne = un prêt terminé ou un achat : l'objet concerné est lu sur l'un ou l'autre
                    $loan = $entry['loan'];
                    $sale = $entry['sale'];
                    $item = $loan ?? $sale;
                    ?>
                    <a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2"
                        href="/dashboard/organisation/article/?article_id=<?= $item->getArticleId() ?>">
                        <span style="min-width: 0;">
                            <span class="d-block fw-semibold text-truncate"><?= htmlspecialchars($item->getArticleName() ?? 'Objet') ?></span>
                            <span class="d-block small text-muted">
                                <span class="font-monospace"><?= htmlspecialchars($item->getArticleCode() ?? '') ?></span>
                                <?php if ($sale): ?>
                                    · <span class="badge text-bg-secondary">Vendu</span>
                                <?php else: ?>
                                    · du <?= formatDate($loan->getStartDate()) ?> au <?= formatDate($loan->getEndDate()) ?>
                                <?php endif; ?>
                            </span>
                        </span>
                        <?php if ($loan && $loan->getDamages()): ?>
                            <!-- Détail des dégâts (description, photos) : sur la fiche de l'objet -->
                            <span class="badge text-bg-danger flex-shrink-0">Dégâts (<?= count($loan->getDamages()) ?>)</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php
    $client_form_mode = 'edit';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/client-form-modal.php';
    ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
</body>
</html>
