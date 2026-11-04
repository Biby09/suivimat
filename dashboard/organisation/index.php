<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('ID d\'organisation manquant ou invalide.');
}

try {
    $organisation = $organisationManager->getOrganisationById($_GET['id']);
} catch (Exception $e) {
    http_response_code(404);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
    exit;
}

$organisationManager->markAsOpened($organisation);

/*
    Inventaire de l'organisation : tous les objets configurés.
    Filtre par statut (tous / disponibles / empruntés / vendus / perdus) et par catégorie (aside), et recherche dans les noms d'objets, les codes et les noms de catégories.
    Les objets sont chargés par pages de 30 (api/article/get-articles.php) ; la recherche n'est appliquée qu'avec Entrée,
    des propositions s'affichent pendant la saisie (api/article/search-suggestions.php).
    Un objet choisi dans les propositions, ou une recherche validée qui ne trouve qu'un objet, ouvre directement sa fiche.
    « Modifier » (à côté des catégories) ouvre le modal includes/modals/category-setup-modal.php.
    « Enregistrer des objets » ouvre le modal includes/modals/article-setup-modal.php ; la liste se recharge après chaque objet enregistré.
    « Scanner » (dans le header sur grand écran, bouton flottant en bas sur mobile) ouvre
    includes/modals/quick-scan-modal.php, inclus par le header de l'application (includes/app-header.php).
    Les données sont chargées et filtrées par scripts/organisation-articles.js ;
    le filtre courant est gardé dans l'URL (?statut=…&categorie=…&q=…&page=…).
*/
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = $organisation->getName() . ' | SuiviMat'; // échappé par head.php
$page_description = "Inventaire du matériel de l'organisation";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<!-- app : header de l'application (includes/app-header.php : organisation courante, inventaire, scanner) -->
<!-- has-action-bar : sur mobile, espace en bas de page pour que le bouton flottant « Scanner » ne cache pas le footer -->
<body class="app has-action-bar">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

<main class="container pt-4 organisation-page" id="organisation-articles" data-organisation-id="<?= $organisation->getId() ?>">
    <div class="row g-4">
        <!-- Colonne de gauche : titre, recherche et catégories, fixée sous le header sur grand écran ; catégories en panneau latéral sur mobile -->
        <div class="col-lg-3">
            <div class="organisation-sidebar">
                <div class="d-flex flex-wrap flex-lg-column align-items-center align-items-lg-stretch justify-content-between gap-2 mb-3">
                    <div class="text-break">
                        <p class="text-muted mb-0">Inventaire</p>
                        <h1 class="fs-3 mb-0"><?= htmlspecialchars($organisation->getName(), ENT_QUOTES, 'UTF-8') ?></h1>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#article-setup-modal">+ Enregistrer des objets</button>
                </div>

                <!-- Recherche (scanner : bouton du header sur grand écran, bouton flottant en bas sur mobile) -->
                <form class="mb-3" role="search" data-role="search-form">
                    <label for="organisation-search" class="visually-hidden">Rechercher un objet, un code ou une catégorie (Entrée pour valider)</label>
                    <div class="input-group flex-nowrap">
                        <!-- Propositions pendant la saisie : modèle « combobox » ARIA, liste sous le champ -->
                        <div class="position-relative flex-grow-1">
                            <input type="search" class="form-control rounded-end-0" id="organisation-search" name="q" placeholder="Rechercher…" autocomplete="off"
                                role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="organisation-suggestions">
                            <ul class="dropdown-menu organisation-suggestions shadow mt-1" id="organisation-suggestions" role="listbox" aria-label="Propositions" data-role="suggestions"></ul>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary" aria-label="Rechercher" title="Rechercher">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <div class="d-flex gap-2 d-lg-none">
                    <button class="btn btn-outline-secondary flex-grow-1 text-truncate" type="button" data-bs-toggle="offcanvas" data-bs-target="#organisation-categories" aria-controls="organisation-categories">
                        Filtres : <span class="fw-bold" data-role="current-category-short">Tous les objets</span>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#category-setup-modal" aria-label="Modifier les catégories" title="Modifier les catégories">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                    </button>
                </div>

                <aside class="offcanvas-lg offcanvas-start organisation-aside" tabindex="-1" id="organisation-categories" aria-labelledby="organisation-categories-label">
                    <div class="offcanvas-header">
                        <h2 class="offcanvas-title fs-5" id="organisation-categories-label">Filtres</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#organisation-categories" aria-label="Fermer"></button>
                    </div>
                    <div class="offcanvas-body flex-column">
                        <!-- Filtre par statut : boutons radio stylés en groupe de boutons Bootstrap.
                             Titre en <h2> avec les mêmes classes que « Catégories » pour une apparence identique -->
                        <div class="mb-3" role="radiogroup" aria-labelledby="status-filter-label" data-role="status-filter">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h2 class="fs-6 text-uppercase text-muted mb-0" id="status-filter-label">Statut</h2>
                            </div>
                            <!-- Grille de 2 colonnes (5 boutons ne tiennent pas sur une ligne) ; « Tous » occupe toute la première ligne -->
                            <div class="organisation-status-filter">
                                <input type="radio" class="btn-check" name="statut" id="statut-tous" value="all" autocomplete="off" checked>
                                <label class="btn btn-sm btn-outline-primary organisation-status-filter-all" for="statut-tous">Tous</label>

                                <input type="radio" class="btn-check" name="statut" id="statut-disponible" value="disponible" autocomplete="off">
                                <label class="btn btn-sm btn-outline-primary" for="statut-disponible">Disponibles</label>

                                <input type="radio" class="btn-check" name="statut" id="statut-emprunte" value="emprunte" autocomplete="off">
                                <label class="btn btn-sm btn-outline-primary" for="statut-emprunte">Empruntés</label>

                                <input type="radio" class="btn-check" name="statut" id="statut-vendu" value="vendu" autocomplete="off">
                                <label class="btn btn-sm btn-outline-primary" for="statut-vendu">Vendus</label>

                                <input type="radio" class="btn-check" name="statut" id="statut-perdu" value="perdu" autocomplete="off">
                                <label class="btn btn-sm btn-outline-primary" for="statut-perdu">Perdus</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="fs-6 text-uppercase text-muted mb-0">Catégories</h2>
                            <button class="btn btn-sm btn-outline-secondary d-none d-lg-flex align-items-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#category-setup-modal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                Modifier
                            </button>
                        </div>
                        <nav aria-label="Catégories">
                            <ul class="list-unstyled mb-0" data-role="categories"></ul>
                        </nav>
                    </div>
                </aside>
            </div>
        </div>

        <section class="col-lg-9 organisation-results" aria-labelledby="organisation-results-title">
            <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-3">
                <h2 class="fs-5 mb-0" id="organisation-results-title" data-role="results-title">Tous les objets</h2>
                <span class="text-muted" data-role="results-count" aria-live="polite"></span>
            </div>

            <div data-role="message" aria-live="polite"></div>

            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3" data-role="articles" aria-busy="false"></div>

            <nav class="mt-4" aria-label="Pages de résultats" data-role="pagination" hidden>
                <ul class="pagination justify-content-center flex-wrap mb-0"></ul>
            </nav>

            <div class="text-center text-muted py-5" data-role="no-results" hidden>
                <p class="mb-2">Aucun objet ne correspond à votre recherche.</p>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-role="reset">Afficher tous les objets</button>
            </div>

            <div class="text-center py-5" data-role="empty" hidden>
                <p class="lead mb-2">Aucun objet enregistré pour le moment.</p>
                <p class="text-muted">Scannez les codes QR collés sur votre matériel pour l'ajouter à l'inventaire.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#article-setup-modal">Enregistrer des objets</button>
            </div>
        </section>
    </div>

    <!-- Mobile : bouton « Scanner » flottant, en bas au milieu de l'écran -->
    <button type="button" class="btn btn-primary btn-lg rounded-pill shadow position-fixed bottom-0 start-50 translate-middle-x mb-3 z-3 d-flex d-lg-none align-items-center gap-2"
        data-bs-toggle="modal" data-bs-target="#quick-scan-modal">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z"/><path d="M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z"/><path d="M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z"/><path d="M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1H8v1z"/><path d="M12 9h2V8h-2z"/></svg>
        Scanner
    </button>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/article-setup-modal.php'; ?>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/category-setup-modal.php'; ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
<!-- asset() (config/init.php) : ?v=… change à chaque modification du fichier, pas d'ancienne version en cache -->
<script src="<?= asset('/scripts/organisation-articles.js') ?>" defer></script>
</body>
</html>
