<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';

/*
    Clients d'une organisation : /dashboard/organisation/clients/?id=… (lien « Clients » du header).
    Pensée mobile d'abord : une ligne par client (initiales, nom, prêts en cours et au total, badge « En retard »).
    - Tri par défaut : nom de famille ; aussi par nombre de prêts en cours.
    - Filtres : Tous / Avec un prêt en cours / En retard / Archivés, et recherche par nom (scripts/list-filter.js).
      Les clients archivés n'apparaissent que dans le filtre « Archivés » (filtre exclusif, data-exclusive).
    - Clic sur un client : sa page (client.php : modification, prêts en cours, historique).
    - « + Nouveau client » : dialog includes/modals/client-form-modal.php, puis ouverture de la page du nouveau client.
*/

try {
    // Lève une exception si l'organisation n'existe pas ou si l'utilisateur n'en est pas membre
    $organisation = $organisationManager->getOrganisationById((int) ($_GET['id'] ?? 0));
} catch (Exception $e) {
    http_response_code(404);
    require $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
    exit;
}

$rows = (new ClientManager($mysqlClient, $organisation))->getClientsWithLoanCounts();

// Nombre de clients par filtre (« Tous » : clients actifs seulement)
$counts = ['all' => 0, 'ongoing' => 0, 'late' => 0, 'archived' => 0];
foreach ($rows as $row) {
    if ($row['client']->isArchived()) {
        $counts['archived']++;
        continue; // continue : passe directement au client suivant
    }
    $counts['all']++;
    $counts['ongoing'] += $row['ongoing'] > 0 ? 1 : 0;
    $counts['late'] += $row['late'] > 0 ? 1 : 0;
}
$filters = ['all' => 'Tous', 'ongoing' => 'Avec un prêt en cours', 'late' => 'En retard'];
if ($counts['archived'] > 0) {
    $filters['archived'] = 'Archivés'; // pastille affichée seulement s'il y a des clients archivés
}

// « Jean Exemple » → « JE » (mb_substr : une lettre accentuée compte pour un caractère)
function initials(Client $client): string
{
    return mb_strtoupper(mb_substr($client->getFirstname(), 0, 1) . mb_substr($client->getLastname(), 0, 1));
}

// Résumé des prêts d'un client : « 2 prêts en cours · 5 au total »
function loansSummary(array $row): string
{
    if ($row['total'] === 0) {
        return 'Aucun prêt pour le moment';
    }
    $ongoing = $row['ongoing'] === 0 ? 'Aucun prêt en cours' : $row['ongoing'] . ' prêt' . ($row['ongoing'] > 1 ? 's' : '') . ' en cours';
    return $ongoing . ' · ' . $row['total'] . ' au total';
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Clients | ' . $organisation->getName(); // échappé par head.php
$page_description = "Clients de l'organisation";
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
            <h1 class="fs-3 mb-0">Clients</h1>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#client-form-modal">+ Nouveau client</button>
    </div>

    <?php if (!$rows): ?>
        <div class="text-center py-5">
            <p class="lead mb-2">Aucun client pour le moment.</p>
            <p class="text-muted">Les clients sont les personnes à qui vous prêtez du matériel. Vous pouvez aussi en ajouter au moment d'un prêt.</p>
        </div>
    <?php else: ?>
        <!-- Filtres : boutons radio en pastilles, défilables au doigt si l'écran est étroit -->
        <div class="loans-filters d-flex gap-2 overflow-auto pb-2 mb-2" role="radiogroup" aria-label="Filtrer les clients" data-role="filters">
            <?php foreach ($filters as $value => $label): ?>
                <input type="radio" class="btn-check" name="clients-filter" id="clients-filter-<?= $value ?>" value="<?= $value ?>" autocomplete="off" <?= $value === 'all' ? 'checked' : '' ?> <?= $value === 'archived' ? 'data-exclusive' : '' ?>>
                <label class="btn btn-sm rounded-pill text-nowrap <?= $value === 'late' ? 'btn-outline-danger' : ($value === 'archived' ? 'btn-outline-secondary' : 'btn-outline-primary') ?>" for="clients-filter-<?= $value ?>">
                    <?= $label ?> <span class="badge rounded-pill text-bg-light ms-1"><?= $counts[$value] ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md">
                <label for="clients-search" class="visually-hidden">Rechercher un client par nom ou e-mail</label>
                <input type="search" class="form-control" id="clients-search" placeholder="Rechercher un client (nom, e-mail)…" autocomplete="off" data-role="search">
            </div>
            <div class="col-md-auto">
                <label for="clients-sort" class="visually-hidden">Trier les clients</label>
                <select class="form-select" id="clients-sort" data-role="sort">
                    <!-- valeur = attribut data-sort-xxx des lignes ; « :desc » = ordre décroissant -->
                    <option value="order">Trier par nom (A → Z)</option>
                    <option value="ongoing:desc">Trier par prêts en cours</option>
                </select>
            </div>
        </div>

        <p class="small text-muted mb-2" data-role="count" data-one="client" data-many="clients" aria-live="polite"><?= $counts['all'] ?> client<?= $counts['all'] > 1 ? 's' : '' ?></p>

        <div class="list-group" data-role="list">
            <?php foreach ($rows as $index => $row): ?>
                <?php
                $client = $row['client'];
                // Catégories pour les filtres : « ongoing » s'il a un prêt en cours, « late » s'il est en retard, « archived » s'il est archivé
                $categories = $client->isArchived() ? 'archived' : trim(($row['ongoing'] > 0 ? 'ongoing ' : '') . ($row['late'] > 0 ? 'late' : ''));
                ?>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3"
                    href="/dashboard/organisation/clients/client.php?client_id=<?= $client->getId() ?>"
                    data-item data-categories="<?= $categories ?>" data-search="<?= htmlspecialchars(mb_strtolower($client->getFullname() . ' ' . ($client->getEmail() ?? ''))) ?>"
                    data-sort-order="<?= $index ?>" data-sort-ongoing="<?= $row['ongoing'] ?>">
                    <span class="client-avatar flex-shrink-0" aria-hidden="true"><?= htmlspecialchars(initials($client)) ?></span>
                    <span class="flex-grow-1" style="min-width: 0;">
                        <span class="d-block fw-semibold text-truncate"><?= htmlspecialchars($client->getLastname() . ' ' . $client->getFirstname()) ?></span>
                        <!-- Pas de text-truncate : le résumé passe à la ligne plutôt que d'être coupé à côté du badge « En retard » -->
                        <span class="d-block small text-muted"><?= loansSummary($row) ?></span>
                    </span>
                    <?php if ($client->isArchived()): ?>
                        <span class="badge text-bg-secondary flex-shrink-0">Archivé</span>
                    <?php elseif ($row['late'] > 0): ?>
                        <span class="badge text-bg-danger flex-shrink-0">En retard</span>
                    <?php endif; ?>
                    <!-- Chevron « › » : indique qu'une page s'ouvre -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="flex-shrink-0 text-muted" aria-hidden="true"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/></svg>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center text-muted py-5" data-role="no-results" hidden>
            <p class="mb-2">Aucun client ne correspond.</p>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-role="reset">Afficher tous les clients</button>
        </div>
    <?php endif; ?>

    <?php
    $client_form_mode = 'create';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/client-form-modal.php';
    ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
<script src="<?= asset('/scripts/list-filter.js') ?>" defer></script>
</body>
</html>
