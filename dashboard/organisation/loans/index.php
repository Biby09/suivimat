<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/imageManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';

/*
    Prêts en cours d'une organisation : /dashboard/organisation/loans/?id=… (lien « Prêts en cours » du header).
    Pensée mobile d'abord : une carte par prêt (photo, objet, client, dates, échéance), une colonne sur mobile, deux sur grand écran.
    - Tri par défaut : urgence (en retard, puis retour prévu le plus proche, puis sans date, les plus anciens d'abord) ;
      aussi par client ou par date de sortie.
    - Filtres : Tous / En retard / Cette semaine (à rendre dans les 7 jours) / Sans date, et recherche objet ou client.
    - Clic sur une carte : fiche de l'objet. Bouton « Retour » : fiche de l'objet avec le dialog de retour ouvert (?retour=1).
    Carte d'un prêt : includes/loan-card.php (aussi utilisée par la page d'un client).
    Filtres, tri et recherche : scripts/list-filter.js, sans recharger la page (tous les prêts en cours sont dans la page).
*/

try {
    // Lève une exception si l'organisation n'existe pas ou si l'utilisateur n'en est pas membre
    $organisation = $organisationManager->getOrganisationById((int) ($_GET['id'] ?? 0));
} catch (Exception $e) {
    http_response_code(404);
    require $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
    exit;
}

$loans = (new LoanManager($mysqlClient, $organisation))->getOngoingLoans();
$coverUrls = (new ImageManager($mysqlClient))->getCoverUrls(array_map(fn(Loan $loan) => $loan->getArticleId(), $loans));

// Nombre de prêts par catégorie d'échéance (Loan::getDueStatus()), pour les pastilles de filtre
$counts = ['all' => count($loans), 'late' => 0, 'soon' => 0, 'none' => 0];
foreach ($loans as $loan) {
    $category = $loan->getDueStatus()['category'];
    if (isset($counts[$category])) {
        $counts[$category]++;
    }
}
$filters = ['all' => 'Tous', 'late' => 'En retard', 'soon' => 'Cette semaine', 'none' => 'Sans date'];
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Prêts en cours | ' . $organisation->getName(); // échappé par head.php
$page_description = "Prêts en cours de l'organisation";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<!-- app : header de l'application (includes/app-header.php) -->
<body class="app">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

<!-- data-list-filter : filtres, recherche et tri par scripts/list-filter.js -->
<main class="container py-4" data-list-filter>
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
        <div class="text-break">
            <p class="text-muted mb-0"><?= htmlspecialchars($organisation->getName()) ?></p>
            <h1 class="fs-3 mb-0">Prêts en cours</h1>
        </div>
        <span class="text-muted" data-role="count" data-one="prêt" data-many="prêts" aria-live="polite"><?= count($loans) ?> prêt<?= count($loans) > 1 ? 's' : '' ?></span>
    </div>

    <?php if (!$loans): ?>
        <div class="text-center py-5">
            <p class="lead mb-2">Aucun prêt en cours.</p>
            <p class="text-muted">Pour prêter un objet, ouvrez sa fiche depuis l'inventaire ou scannez son code QR.</p>
            <a class="btn btn-primary" href="/dashboard/organisation/?id=<?= $organisation->getId() ?>">Aller à l'inventaire</a>
        </div>
    <?php else: ?>
        <!-- Filtres : boutons radio en pastilles, défilables au doigt si l'écran est étroit -->
        <div class="loans-filters d-flex gap-2 overflow-auto pb-2 mb-2" role="radiogroup" aria-label="Filtrer les prêts" data-role="filters">
            <?php foreach ($filters as $value => $label): ?>
                <input type="radio" class="btn-check" name="loans-filter" id="loans-filter-<?= $value ?>" value="<?= $value ?>" autocomplete="off" <?= $value === 'all' ? 'checked' : '' ?>>
                <label class="btn btn-sm rounded-pill text-nowrap <?= $value === 'late' ? 'btn-outline-danger' : 'btn-outline-primary' ?>" for="loans-filter-<?= $value ?>">
                    <?= $label ?> <span class="badge rounded-pill text-bg-light ms-1"><?= $counts[$value] ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- Recherche et tri : l'un sous l'autre sur mobile, côte à côte à partir de md -->
        <div class="row g-2 mb-3">
            <div class="col-md">
                <label for="loans-search" class="visually-hidden">Rechercher un objet, un code ou un client</label>
                <input type="search" class="form-control" id="loans-search" placeholder="Rechercher un objet ou un client…" autocomplete="off" data-role="search">
            </div>
            <div class="col-md-auto">
                <label for="loans-sort" class="visually-hidden">Trier les prêts</label>
                <select class="form-select" id="loans-sort" data-role="sort">
                    <!-- valeur = attribut data-sort-xxx des cartes (includes/loan-card.php) ; « :desc » = ordre décroissant -->
                    <option value="order">Trier par urgence</option>
                    <option value="client">Trier par client (A → Z)</option>
                    <option value="start:desc">Trier par date de sortie (récente)</option>
                </select>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-lg-2 g-3" data-role="list">
            <?php foreach ($loans as $index => $loan): ?>
                <?php
                // Carte du prêt (includes/loan-card.php) ; $index : ordre d'urgence donné par getOngoingLoans()
                $loanCoverUrl = $coverUrls[$loan->getArticleId()] ?? null;
                $loanOrder = $index;
                include $_SERVER['DOCUMENT_ROOT'] . '/includes/loan-card.php';
                ?>
            <?php endforeach; ?>
        </div>

        <div class="text-center text-muted py-5" data-role="no-results" hidden>
            <p class="mb-2">Aucun prêt ne correspond.</p>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-role="reset">Afficher tous les prêts</button>
        </div>
    <?php endif; ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
<script src="<?= asset('/scripts/list-filter.js') ?>" defer></script>
</body>
</html>
