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
    Filtre par catégorie (aside) et recherche dans les noms d'objets, les codes et les noms de catégories.
    Les objets sont chargés par pages de 30 (api/article/get-articles.php) ; la recherche n'est appliquée qu'avec Entrée,
    des propositions s'affichent pendant la saisie (api/article/search-suggestions.php).
    « Modifier » (à côté des catégories) ouvre le modal includes/modals/category-setup-modal.php.
    « Enregistrer des objets » ouvre le modal includes/modals/article-setup-modal.php ; la liste se recharge après chaque objet enregistré.
    Les données sont chargées et filtrées par scripts/organisation-articles.js ;
    le filtre courant est gardé dans l'URL (?categorie=…&q=…&page=…).
*/
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = $organisation->getName() . ' | SuiviMat'; // échappé par head.php
$page_description = "Inventaire du matériel de l'organisation";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

<main class="container py-4" id="organisation-articles" data-organisation-id="<?= $organisation->getId() ?>">
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

                <form class="mb-3" role="search" data-role="search-form">
                    <label for="organisation-search" class="visually-hidden">Rechercher un objet, un code ou une catégorie (Entrée pour valider)</label>
                    <div class="input-group">
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
                        Catégories : <span class="fw-bold" data-role="current-category-short">Tous les objets</span>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#category-setup-modal" aria-label="Modifier les catégories" title="Modifier les catégories">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                    </button>
                </div>

                <aside class="offcanvas-lg offcanvas-start organisation-aside" tabindex="-1" id="organisation-categories" aria-labelledby="organisation-categories-label">
                    <div class="offcanvas-header">
                        <h2 class="offcanvas-title fs-5" id="organisation-categories-label">Catégories</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#organisation-categories" aria-label="Fermer"></button>
                    </div>
                    <div class="offcanvas-body flex-column">
                        <div class="d-none d-lg-flex justify-content-between align-items-center mb-2">
                            <h2 class="fs-6 text-uppercase text-muted mb-0">Catégories</h2>
                            <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#category-setup-modal">
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

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/article-setup-modal.php'; ?>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/category-setup-modal.php'; ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
<script src="/scripts/organisation-articles.js" defer></script>
</body>
</html>
