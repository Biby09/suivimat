<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
// config/auth.php est inclus plus bas : un objet perdu doit pouvoir s'afficher sans être connecté
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisationManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/imageManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/sale/saleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loss/lossManager.php';

/*
    Fiche d'un objet, deux adresses :
    - /dashboard/organisation/article/?article_id=… : depuis l'inventaire (clic sur une carte), le scanner rapide
      (includes/modals/quick-scan-modal.php) ou la recherche ;
    - /dashboard/organisation/article/?code=XXXX : adresse des codes QR collés sur les objets
      (api/organisation/generate-qr-codes.php), scannés avec n'importe quel téléphone :
        · membre de l'organisation → cette fiche (code pas encore configuré → enregistrement dans l'inventaire) ;
        · objet perdu, visiteur non connecté ou pas membre → page pour contacter l'organisation
          (includes/lost-article-public.php) ;
        · autre objet : non connecté → connexion puis retour ici ; connecté mais pas membre → accès refusé (403).

    Mobile : chemin d'accès, photos (carrousel), nom / catégorie / statut, champs personnalisés, historique des prêts,
    et barre d'actions fixée en bas de l'écran. Grand écran (≥ lg) : photos à gauche, informations et actions à droite,
    pas de barre en bas.
    « Prêter » ouvre le dialog de prêt (client existant ou nouveau, date de sortie, retour prévu optionnel :
    scripts/article-loan.js, enregistré par api/loan/create-loan.php).
    « Enregistrer un retour » ouvre le dialog de retour (date, dégâts éventuels avec description et photos :
    scripts/article-return.js, enregistré par api/loan/return-loan.php).
    « Marquer comme vendu » ouvre le dialog de vente (acheteur : emprunteur actuel par défaut, date de vente :
    scripts/article-sale.js, enregistré par api/sale/sell-article.php). Un objet vendu affiche l'acheteur et la date.
    « Marquer comme perdu » ouvre le dialog de perte (date, remarque) ; un objet perdu affiche un bandeau
    « Perdu depuis le … » et le bouton « Objet retrouvé » qui désactive le mode perdu (scripts/article-lost.js,
    api/loss/mark-lost.php et mark-found.php).
    « Modifier » ouvre le dialog de modification (nom, catégorie, champs personnalisés, photos : scripts/article-edit.js,
    enregistré par api/article/configure-article.php). « Remettre disponible » (objet vendu) n'est pas encore développé :
    il ouvre le modal « bientôt disponible » en bas de page.
    Footer : la ligne discrète de l'application (includes/app-footer.php).
*/

$code = strtoupper(trim($_GET['code'] ?? ''));

if ($code !== '') {
    // ---- Arrivée par un code QR ----
    $codeOrganisation = preg_match('/^[A-Z0-9]{4}$/', $code) ? OrganisationManager::findByArticleCode($mysqlClient, $code) : null;
    if ($codeOrganisation === null) {
        http_response_code(404);
        require $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
        exit;
    }
    $article = (new ArticleManager($mysqlClient, $codeOrganisation))->getArticleByCode($code);

    // Membre de l'organisation de l'objet ? (getOrganisationById lève une exception sinon)
    $isMember = false;
    if (isset($_SESSION['user_id'])) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php'; // $user, $organisationManager
        try {
            $organisationManager->getOrganisationById($codeOrganisation->getId());
            $isMember = true;
        } catch (Exception $e) {
            $isMember = false;
        }
    }

    if (!$isMember) {
        // Page publique seulement si l'organisation l'a laissée activée (Paramètres › Objets perdus) ; sinon : connexion
        if ($article->getStatus() === 'Perdu' && $codeOrganisation->isLostPageEnabled()) {
            // Page publique : nom et photo de l'objet, formulaire pour écrire à l'organisation
            $organisation = $codeOrganisation;
            require $_SERVER['DOCUMENT_ROOT'] . '/includes/lost-article-public.php';
            exit;
        }
        if (!isset($_SESSION['user_id'])) {
            // Connexion, puis retour sur cette même adresse (?code=…)
            header('Location: /auth/login/?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        http_response_code(403);
        require $_SERVER['DOCUMENT_ROOT'] . '/error/403.php';
        exit;
    }

    // Code pas encore configuré : l'inventaire ouvre « Enregistrer des objets » avec ce code (scripts/article-setup-modal.js)
    if ($article->getStatus() === 'Non attribué') {
        header('Location: /dashboard/organisation/?id=' . $codeOrganisation->getId() . '&enregistrer=' . urlencode($code));
        exit;
    }
    $articleId = $article->getId();
} else {
    $articleId = filter_input(INPUT_GET, 'article_id', FILTER_VALIDATE_INT);
}

// ---- Fiche de l'objet : réservée aux membres de son organisation ----
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php'; // redirige vers la connexion si besoin

try {
    // Lève une exception si l'article n'existe pas ou appartient à une organisation dont l'utilisateur n'est pas membre
    $organisation = $organisationManager->getOrganisationOfArticle((int) $articleId);
    $articleManager = new ArticleManager($mysqlClient, $organisation);
    $article = $articleManager->getArticleById((int) $articleId);
} catch (Exception $e) {
    $article = null;
}

// Code QR pas encore configuré : pas de fiche (il s'enregistre depuis l'inventaire)
if ($article === null || $article->getStatus() === 'Non attribué') {
    http_response_code(404);
    require $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
    exit;
}

$categoryManager = new CategoryManager($mysqlClient, $organisation);
$categoryPath = $article->getCategoryId() !== null ? $categoryManager->getCategoryPath($article->getCategoryId()) : [];
$images = (new ImageManager($mysqlClient))->getArticleImages($article);
// Champs de la catégorie et des parentes : valeur de l'objet, sinon valeur par défaut du champ
$fields = $articleManager->getDisplayedFields($article);
$loans = (new LoanManager($mysqlClient, $organisation))->getLoansForArticle($article);
$status = $article->getStatus();

// Prêt en cours (le plus récent sans date de retour) pour le dialog de retour ; null si aucun
$ongoingLoan = null;
foreach ($loans as $loan) {
    if ($loan->isOngoing()) {
        $ongoingLoan = $loan;
        break; // les prêts sont triés du plus récent au plus ancien : le premier trouvé suffit
    }
}

// Objet perdu : date et remarque de la perte en cours
$loss = $status === 'Perdu' ? (new LossManager($mysqlClient, $organisation))->getCurrentLoss($article) : null;

// Objet vendu : acheteur et date de la vente
$sale = $status === 'Vendu' ? (new SaleManager($mysqlClient, $organisation))->getLatestSale($article) : null;

// Clients de l'organisation pour les dialogs de prêt et de vente (triés par nom de famille)
$clients = (new ClientManager($mysqlClient, $organisation))->getAllClients();

// Données du dialog de modification, lues par scripts/article-edit.js (balise <script type="application/json"> plus bas)
$fieldsByCategory = $categoryManager->getAdditionalFieldsByCategory();
$editData = [
    'organisationId' => $organisation->getId(),
    'code' => $article->getCode(),
    'name' => $article->getName(),
    'categoryId' => $article->getCategoryId(),
    // (object) : un tableau vide devient {} en JSON (et pas []), le JS attend toujours un objet
    'fieldValues' => (object) $articleManager->getAdditionalFieldValues($article),
    'images' => array_map(fn(Image $image) => ['id' => $image->getId(), 'url' => $image->getUrl()], $images),
    'categories' => array_map(fn(Category $category) => [
        'id' => $category->getId(),
        'name' => $category->getName(),
        'parent_id' => $category->getParentId(),
        'fields' => $fieldsByCategory[$category->getId()] ?? [],
    ], $categoryManager->getAllCategories()),
];

$inventoryUrl = '/dashboard/organisation/?id=' . $organisation->getId();
// « Câbles › Jack » ; array_map applique la fonction à chaque catégorie et renvoie le tableau des noms
$categoryLabel = $categoryPath ? implode(' › ', array_map(fn(Category $category) => $category->getName(), $categoryPath)) : 'Sans catégorie';

// formatDate() (« 2026-11-04 » → « 04.11.2026 ») : config/init.php

/**
 * Boutons d'action : principal (2/3 de la largeur) + menu « Plus d'actions » (1/3).
 * Affichés deux fois : dans la barre du bas sur mobile, sous le nom de l'objet sur grand écran.
 * $dropup : le menu s'ouvre vers le haut (barre en bas de l'écran). $code : code de l'objet (réimpression de l'étiquette).
 */
function renderActions(string $status, bool $dropup, string $code): void
{
    // Chaque bouton ouvre le modal « bientôt disponible » ; data-action donne son titre (script en bas de page)
    $soon = 'data-bs-toggle="modal" data-bs-target="#article-soon-modal"';
    ?>
    <div class="row g-2">
        <div class="col-8">
            <?php if ($status === 'Emprunté'): ?>
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#article-return-modal">Enregistrer un retour</button>
            <?php elseif ($status === 'Disponible'): ?>
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#article-loan-modal">Prêter</button>
            <?php elseif ($status === 'Perdu'): ?>
                <!-- Désactive le mode perdu (scan du code QR d'un objet perdu par un membre) -->
                <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#article-found-modal">Objet retrouvé</button>
            <?php else: ?>
                <!-- Vendu : on ne peut pas le prêter -->
                <button type="button" class="btn btn-primary w-100" disabled>Prêter</button>
            <?php endif; ?>
        </div>
        <div class="col-4 <?= $dropup ? 'dropup' : 'dropdown' ?>">
            <button type="button" class="btn btn-outline-primary w-100 px-1 text-nowrap" data-bs-toggle="dropdown" aria-expanded="false">Plus d'actions</button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#article-edit-modal">Modifier</button></li>
                <!-- data-reprint-code : le dialog des codes QR s'ouvre en réimpression de ce code (scripts/download-qr-modal.js) -->
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#download-qr-modal" data-reprint-code="<?= htmlspecialchars($code) ?>">Réimprimer l'étiquette</button></li>
                <?php if ($status === 'Vendu'): ?>
                    <li><button type="button" class="dropdown-item" <?= $soon ?> data-action="Remettre disponible">Remettre disponible</button></li>
                <?php endif; ?>
                <?php if ($status === 'Perdu'): ?>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#article-found-modal">Objet retrouvé</button></li>
                <?php endif; ?>
                <?php if ($status === 'Disponible' || $status === 'Emprunté'): ?>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#article-sale-modal">Marquer comme vendu</button></li>
                <?php endif; ?>
                <?php if ($status === 'Disponible' || $status === 'Emprunté'): ?>
                    <li><button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#article-lost-modal">Marquer comme perdu</button></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = $article->getName() . ' | ' . $organisation->getName(); // échappé par head.php
$page_description = "Fiche de l'objet : photos, informations et historique des prêts";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<!-- app : header de l'application (includes/app-header.php) ;
     has-action-bar : sur mobile, espace en bas de page pour que la barre d'actions fixe ne cache pas l'historique -->
<body class="app has-action-bar">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

<main class="container py-4">
    <!-- Chemin d'accès : organisation › catégories parentes › objet ; chaque lien ouvre l'inventaire filtré -->
    <nav aria-label="Chemin d'accès">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item"><a href="<?= $inventoryUrl ?>"><?= htmlspecialchars($organisation->getName()) ?></a></li>
            <?php if (!$categoryPath): ?>
                <li class="breadcrumb-item"><a href="<?= $inventoryUrl ?>&categorie=none">Sans catégorie</a></li>
            <?php endif; ?>
            <?php foreach ($categoryPath as $category): ?>
                <li class="breadcrumb-item"><a href="<?= $inventoryUrl ?>&categorie=<?= $category->getId() ?>"><?= htmlspecialchars($category->getName()) ?></a></li>
            <?php endforeach; ?>
            <li class="breadcrumb-item active text-break" aria-current="page"><?= htmlspecialchars($article->getName()) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Photos -->
        <div class="col-lg-5">
            <?php if (!$images): ?>
                <div class="ratio ratio-4x3 bg-light rounded">
                    <div class="d-flex flex-column align-items-center justify-content-center text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/></svg>
                        <span class="small mt-2">Aucune photo</span>
                    </div>
                </div>
            <?php else: ?>
                <!-- Carrousel Bootstrap : défilement au doigt (swipe) ou avec les flèches, pas de défilement automatique -->
                <div id="article-photos" class="carousel slide bg-light rounded overflow-hidden">
                    <?php if (count($images) > 1): ?>
                        <div class="carousel-indicators">
                            <?php foreach ($images as $index => $image): ?>
                                <button type="button" data-bs-target="#article-photos" data-bs-slide-to="<?= $index ?>"
                                    <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Photo <?= $index + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="ratio ratio-4x3">
                                    <!-- object-fit-contain : l'objet est visible en entier, sans être recadré -->
                                    <img src="<?= htmlspecialchars($image->getUrl()) ?>" class="object-fit-contain" alt="Photo <?= $index + 1 ?> de <?= htmlspecialchars($article->getName()) ?>"
                                        <?= $index > 0 ? 'loading="lazy"' : '' ?>>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#article-photos" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Photo précédente</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#article-photos" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Photo suivante</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nom, catégorie, statut, actions (grand écran) et champs personnalisés -->
        <div class="col-lg-7">
            <h1 class="fs-3 mb-1 text-break"><?= htmlspecialchars($article->getName()) ?></h1>
            <p class="text-muted mb-2"><?= htmlspecialchars($categoryLabel) ?></p>
            <div class="d-flex flex-wrap gap-2 <?= $sale || $loss ? 'mb-2' : 'mb-4' ?>">
                <span class="badge fs-6 <?= ArticleManager::STATUS_BADGES[$status] ?? 'text-bg-secondary' ?>"><?= htmlspecialchars($status) ?></span>
                <span class="badge fs-6 text-bg-light border font-monospace" title="Code de l'objet"><?= htmlspecialchars($article->getCode()) ?></span>
            </div>
            <?php if ($sale): ?>
                <p class="mb-4">Vendu à <strong><?= htmlspecialchars($sale->getClientName()) ?></strong> le <?= formatDate($sale->getDate()) ?></p>
            <?php endif; ?>
            <?php if ($status === 'Perdu'): ?>
                <!-- Mode perdu : quelqu'un qui scanne le code QR sans être membre voit la page de contact -->
                <div class="alert alert-danger mb-4">
                    <div class="fw-semibold">Objet perdu<?= $loss ? ' depuis le ' . formatDate($loss->getDate()) : '' ?></div>
                    <?php if ($loss?->getComment()): ?>
                        <div class="small mt-1"><?= nl2br(htmlspecialchars($loss->getComment())) ?></div>
                    <?php endif; ?>
                    <div class="small mt-1">Une personne qui scanne son code QR peut vous contacter par e-mail.</div>
                </div>
            <?php endif; ?>

            <!-- Grand écran : actions ici ; mobile : dans la barre fixée en bas (fin de page) -->
            <div class="d-none d-lg-block mb-4">
                <?php renderActions($status, false, $article->getCode()); ?>
            </div>

            <section aria-labelledby="article-fields-title">
                <h2 class="fs-6 text-uppercase text-muted mb-2" id="article-fields-title">Informations</h2>
                <?php if (!$fields): ?>
                    <p class="text-muted mb-0">Aucune information complémentaire.</p>
                <?php else: ?>
                    <!-- Liste de définitions : <dt> = nom du champ, <dd> = sa valeur -->
                    <dl class="row mb-0">
                        <?php foreach ($fields as $field): ?>
                            <dt class="col-5 fw-semibold text-break"><?= htmlspecialchars($field['name']) ?></dt>
                            <dd class="col-7 text-break">
                                <?= htmlspecialchars($field['value']) ?>
                                <?php if ($field['isDefault']): ?>
                                    <!-- Pas de valeur propre à l'objet : valeur par défaut de la catégorie -->
                                    <span class="text-muted small">(par défaut)</span>
                                <?php endif; ?>
                            </dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- Historique des prêts, du plus récent au plus ancien -->
    <section class="mt-4" aria-labelledby="article-loans-title">
        <h2 class="fs-6 text-uppercase text-muted mb-2" id="article-loans-title">Historique des prêts</h2>
        <?php if (!$loans): ?>
            <p class="text-muted">Cet objet n'a encore jamais été prêté.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($loans as $loan): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <div class="text-break">
                            <div class="fw-semibold"><?= htmlspecialchars($loan->getClientName()) ?></div>
                            <div class="small text-muted">
                                <?php if ($loan->isOngoing()): ?>
                                    Depuis le <?= formatDate($loan->getStartDate()) ?>
                                    <?php if ($loan->isLate()): ?>
                                        <span class="badge text-bg-danger ms-1">En retard</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-warning ms-1">En cours</span>
                                    <?php endif; ?>
                                    <br>
                                    <?= $loan->getDueDate() !== null ? 'Retour prévu le ' . formatDate($loan->getDueDate()) : 'Date de retour indéterminée' ?>
                                <?php else: ?>
                                    Du <?= formatDate($loan->getStartDate()) ?> au <?= formatDate($loan->getEndDate()) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-1 flex-shrink-0">
                            <!-- Contrat de prêt en PDF, dans un nouvel onglet (api/loan/contract.php) -->
                            <a class="btn btn-sm btn-outline-secondary text-nowrap" href="/api/loan/contract.php?loan_id=<?= $loan->getId() ?>" target="_blank" rel="noopener">Contrat</a>
                            <?php if ($loan->isOngoing()): ?>
                                <!-- Prêt en cours : ouvre le dialog de prêt en mode « modification », pré-rempli avec ces data-* (scripts/article-loan.js) -->
                                <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" data-bs-toggle="modal" data-bs-target="#article-loan-modal"
                                    data-loan-id="<?= $loan->getId() ?>" data-client-id="<?= $loan->getClientId() ?>"
                                    data-start-date="<?= $loan->getStartDate() ?>" data-due-date="<?= $loan->getDueDate() ?>">
                                    Modifier
                                </button>
                            <?php endif; ?>
                            <?php if ($loan->getDamages()): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-bs-toggle="modal" data-bs-target="#damage-modal-<?= $loan->getId() ?>">
                                    Dégâts (<?= count($loan->getDamages()) ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Un modal par prêt avec dégâts : description, date et photos de chaque dégât -->
    <?php foreach ($loans as $loan): ?>
        <?php if (!$loan->getDamages()) continue; ?>
        <div class="modal fade" id="damage-modal-<?= $loan->getId() ?>" tabindex="-1" aria-labelledby="damage-modal-<?= $loan->getId() ?>-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6" id="damage-modal-<?= $loan->getId() ?>-label">
                            Dégâts · prêt à <?= htmlspecialchars($loan->getClientName()) ?> du <?= formatDate($loan->getStartDate()) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <?php foreach ($loan->getDamages() as $damage): ?>
                            <div class="mb-4">
                                <p class="small text-muted mb-1">Constaté le <?= formatDate($damage->getDate()) ?></p>
                                <!-- nl2br : les retours à la ligne de la description deviennent des <br> -->
                                <p class="mb-2"><?= nl2br(htmlspecialchars($damage->getDescription())) ?></p>
                                <?php if ($damage->getImageUrls()): ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($damage->getImageUrls() as $index => $url): ?>
                                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">
                                                <img src="<?= htmlspecialchars($url) ?>" width="96" height="96" class="rounded border object-fit-cover" alt="Photo <?= $index + 1 ?> du dégât" loading="lazy">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Retour de l'objet : date de retour, dégâts éventuels (description + photos) -->
    <?php if ($status === 'Emprunté'): ?>
    <div class="modal fade" id="article-return-modal" tabindex="-1" aria-labelledby="article-return-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <form class="modal-content" novalidate data-role="return-form" data-article-id="<?= $article->getId() ?>"
                data-start-date="<?= $ongoingLoan?->getStartDate() ?>">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6 text-truncate" id="article-return-modal-label">Retour de « <?= htmlspecialchars($article->getName()) ?> »</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div data-role="message" aria-live="polite"></div>

                    <?php if ($ongoingLoan): ?>
                        <!-- Rappel du prêt en cours -->
                        <div class="bg-body-tertiary rounded p-3 mb-3">
                            <div class="small text-muted">Prêté à</div>
                            <div class="fw-semibold mb-1"><?= htmlspecialchars($ongoingLoan->getClientName()) ?></div>
                            <div class="small">
                                Depuis le <?= formatDate($ongoingLoan->getStartDate()) ?> ·
                                <?= $ongoingLoan->getDueDate() !== null ? 'retour prévu le ' . formatDate($ongoingLoan->getDueDate()) : 'retour indéterminé' ?>
                                <?php if ($ongoingLoan->isLate()): ?><span class="badge text-bg-danger ms-1">En retard</span><?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning py-2">Aucun prêt en cours n'est enregistré pour cet objet : il redeviendra simplement disponible.</div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="return-end-date" class="form-label">Date de retour</label>
                        <input type="date" class="form-control" id="return-end-date" name="end_date" required>
                        <div class="invalid-feedback">Choisissez une date de retour, au plus tôt le jour de la sortie.</div>
                    </div>

                    <?php if ($ongoingLoan): ?>
                        <!-- État de l'objet : deux boutons radio (btn-check) ; « Dégâts constatés » affiche la description et les photos -->
                        <fieldset class="mb-3">
                            <legend class="form-label fs-6 mb-2">État de l'objet</legend>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="damaged" id="return-no-damage" value="0" autocomplete="off" checked>
                                    <label class="btn btn-outline-success w-100" for="return-no-damage">Aucun dégât</label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="damaged" id="return-damaged" value="1" autocomplete="off">
                                    <label class="btn btn-outline-danger w-100" for="return-damaged">Dégâts constatés</label>
                                </div>
                            </div>
                        </fieldset>

                        <div data-role="damage" hidden>
                            <div class="mb-3">
                                <label for="return-damage-description" class="form-label">Description des dégâts</label>
                                <textarea class="form-control" id="return-damage-description" name="damage_description" rows="3" maxlength="2000"
                                    placeholder="Ex. coin gauche déchiré sur 10 cm"></textarea>
                                <div class="invalid-feedback">Décrivez les dégâts constatés.</div>
                            </div>
                            <div class="mb-1">
                                <label for="return-damage-photos" class="form-label">Photos des dégâts <span class="text-muted">(optionnel)</span></label>
                                <div class="d-flex flex-wrap gap-2 mb-2" data-role="photos"></div>
                                <!-- Sans attribut name : les photos réduites sont ajoutées à l'envoi par scripts/article-return.js -->
                                <input type="file" class="form-control" id="return-damage-photos" accept="image/*" multiple data-role="photo-input">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" data-role="save">Enregistrer le retour</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Perte de l'objet : date (aujourd'hui par défaut), remarque optionnelle -->
    <?php if ($status === 'Disponible' || $status === 'Emprunté'): ?>
    <div class="modal fade" id="article-lost-modal" tabindex="-1" aria-labelledby="article-lost-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <form class="modal-content" novalidate data-role="lost-form" data-article-id="<?= $article->getId() ?>"
                data-loan-start="<?= $ongoingLoan?->getStartDate() ?>">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6 text-truncate" id="article-lost-modal-label">Marquer « <?= htmlspecialchars($article->getName()) ?> » comme perdu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div data-role="message" aria-live="polite"></div>

                    <?php if ($ongoingLoan): ?>
                        <div class="alert alert-info py-2">
                            Actuellement prêté à <strong><?= htmlspecialchars($ongoingLoan->getClientName()) ?></strong>
                            depuis le <?= formatDate($ongoingLoan->getStartDate()) ?> : le prêt sera clôturé à la date de la perte.
                        </div>
                    <?php endif; ?>

                    <p class="small text-muted">Si quelqu'un trouve l'objet et scanne son code QR, une page l'invitera à vous écrire.
                        Le message sera envoyé par e-mail aux membres de l'organisation.</p>

                    <div class="mb-3">
                        <label for="lost-date" class="form-label">Date de la perte</label>
                        <input type="date" class="form-control" id="lost-date" name="loss_date" required>
                        <div class="invalid-feedback">Choisissez une date<?= $ongoingLoan ? ', au plus tôt le jour du début du prêt' : '' ?>.</div>
                    </div>

                    <div class="mb-1">
                        <label for="lost-comment" class="form-label">Remarque <span class="text-muted">(optionnel)</span></label>
                        <textarea class="form-control" id="lost-comment" name="comment" rows="3" maxlength="1000"
                            placeholder="Ex. oublié au camp d'été, dernière fois vu dans la tente 3"></textarea>
                        <div class="form-text">Visible uniquement par les membres de l'organisation.</div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger" data-role="save">Marquer comme perdu</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Objet retrouvé : confirmation, puis désactivation du mode perdu (l'objet redevient disponible) -->
    <?php if ($status === 'Perdu'): ?>
    <div class="modal fade" id="article-found-modal" tabindex="-1" aria-labelledby="article-found-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" novalidate data-role="found-form" data-article-id="<?= $article->getId() ?>">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6" id="article-found-modal-label">Objet retrouvé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div data-role="message" aria-live="polite"></div>
                    <p class="mb-0">« <?= htmlspecialchars($article->getName()) ?> » a été retrouvé ? Le mode perdu sera désactivé et l'objet redeviendra disponible.</p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success" data-role="save">Objet retrouvé</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vente de l'objet : acheteur (emprunteur actuel par défaut, ou client existant / nouveau), date de vente -->
    <?php if ($status === 'Disponible' || $status === 'Emprunté'): ?>
    <div class="modal fade" id="article-sale-modal" tabindex="-1" aria-labelledby="article-sale-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <form class="modal-content" novalidate data-role="sale-form" data-article-id="<?= $article->getId() ?>"
                data-borrower-id="<?= $ongoingLoan?->getClientId() ?>" data-loan-start="<?= $ongoingLoan?->getStartDate() ?>">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6 text-truncate" id="article-sale-modal-label">Vendre « <?= htmlspecialchars($article->getName()) ?> »</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div data-role="message" aria-live="polite"></div>

                    <?php if ($ongoingLoan): ?>
                        <div class="alert alert-info py-2">
                            Actuellement prêté à <strong><?= htmlspecialchars($ongoingLoan->getClientName()) ?></strong>
                            depuis le <?= formatDate($ongoingLoan->getStartDate()) ?> : le prêt sera clôturé à la date de vente.
                        </div>
                    <?php endif; ?>

                    <?php
                    // Acheteur : emprunteur actuel choisi d'office par scripts/article-sale.js (data-borrower-id)
                    $client_picker_id = 'sale';
                    $client_picker_label = 'Acheteur';
                    include $_SERVER['DOCUMENT_ROOT'] . '/includes/client-picker.php';
                    ?>

                    <div class="mb-1">
                        <label for="sale-date" class="form-label">Date de vente</label>
                        <input type="date" class="form-control" id="sale-date" name="sale_date" required>
                        <div class="invalid-feedback">Choisissez une date de vente<?= $ongoingLoan ? ', au plus tôt le jour du début du prêt' : '' ?>.</div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" data-role="save">Marquer comme vendu</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Prêt de l'objet : client (existant ou nouveau), date de sortie, date de retour prévue (optionnelle) -->
    <div class="modal fade" id="article-loan-modal" tabindex="-1" aria-labelledby="article-loan-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <!-- data-default-days : durée de prêt par défaut de l'organisation (Paramètres › Prêts), vide = indéterminée -->
            <form class="modal-content" novalidate data-role="loan-form" data-article-id="<?= $article->getId() ?>"
                data-default-days="<?= $organisation->getDefaultLoanDays() ?? '' ?>">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6 text-truncate" id="article-loan-modal-label">Prêter « <?= htmlspecialchars($article->getName()) ?> »</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div data-role="message" aria-live="polite"></div>

                    <?php
                    // Liste des clients + « + Nouveau client » (comportement : scripts/client-picker.js)
                    $client_picker_id = 'loan';
                    $client_picker_label = 'Client';
                    include $_SERVER['DOCUMENT_ROOT'] . '/includes/client-picker.php';
                    ?>

                    <div class="mb-3">
                        <label for="loan-start-date" class="form-label">Date de sortie</label>
                        <input type="date" class="form-control" id="loan-start-date" name="start_date" required>
                        <div class="invalid-feedback">La date de sortie est requise.</div>
                    </div>

                    <div class="mb-1">
                        <label for="loan-due-date" class="form-label">Date de retour prévue</label>
                        <input type="date" class="form-control" id="loan-due-date" name="due_date" aria-describedby="loan-due-help">
                        <div class="invalid-feedback">Choisissez une date de retour, au plus tôt le jour de la sortie.</div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="loan-due-unknown" data-role="due-unknown">
                            <label class="form-check-label" for="loan-due-unknown">Date de retour indéterminée</label>
                        </div>
                        <div class="form-text" id="loan-due-help">Décochez « indéterminée » pour indiquer quand l'objet doit revenir.</div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" data-role="save">Prêter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modification de l'objet : nom, catégorie, champs personnalisés et photos (le code et l'historique ne se modifient pas ici) -->
    <div class="modal fade" id="article-edit-modal" tabindex="-1" aria-labelledby="article-edit-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
            <!-- Le <form> est le contenu du modal : le bouton « Enregistrer » du pied est donc dans le formulaire -->
            <form class="modal-content" novalidate data-role="edit-form">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6" id="article-edit-modal-label">
                        Modifier l'objet <span class="font-monospace"><?= htmlspecialchars($article->getCode()) ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div data-role="message" aria-live="polite"></div>

                    <div class="mb-3">
                        <label for="article-edit-name" class="form-label">Nom de l'objet</label>
                        <input type="text" class="form-control" id="article-edit-name" name="name" maxlength="100" required>
                        <div class="invalid-feedback">Le nom est requis.</div>
                    </div>

                    <div class="mb-3">
                        <label for="article-edit-category" class="form-label">Catégorie</label>
                        <select class="form-select" id="article-edit-category" name="category_id">
                            <option value="">Aucune catégorie</option>
                        </select>
                    </div>

                    <!-- Champs personnalisés de la catégorie choisie, créés par scripts/article-edit.js -->
                    <div data-role="fields"></div>

                    <div class="mb-1">
                        <label for="article-edit-photos" class="form-label">Photos</label>
                        <div class="d-flex flex-wrap gap-2 mb-2" data-role="photos"></div>
                        <!-- Sans attribut name : les photos réduites sont ajoutées à l'envoi par scripts/article-edit.js -->
                        <input type="file" class="form-control" id="article-edit-photos" accept="image/*" multiple data-role="photo-input">
                        <div class="form-text">Les photos retirées ne sont supprimées qu'à l'enregistrement.</div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" data-role="save">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <!-- JSON_HEX_TAG : « < » et « > » encodés, un nom contenant « </script> » ne peut pas fermer la balise -->
    <script type="application/json" id="article-edit-data"><?= json_encode($editData, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <!-- Actions pas encore développées -->
    <div class="modal fade" id="article-soon-modal" tabindex="-1" aria-labelledby="article-soon-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6" id="article-soon-modal-label">Bientôt disponible</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Cette action sera disponible prochainement.</p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Réimpression de l'étiquette (« Plus d'actions ») : dialog des codes QR, ouvert en mode réimpression
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/download-qr-modal.php';
    ?>
</main>

<!-- Mobile : barre d'actions fixée en bas de l'écran (masquée à partir de lg) -->
<div class="article-action-bar fixed-bottom bg-body border-top shadow-sm d-lg-none">
    <div class="container py-2">
        <?php renderActions($status, true, $article->getCode()); ?>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>

<!-- resize-photo.js avant article-edit.js : les scripts « defer » s'exécutent dans l'ordre de la page -->
<script src="<?= asset('/scripts/resize-photo.js') ?>" defer></script>
<script src="<?= asset('/scripts/category-fields.js') ?>" defer></script>
<script src="<?= asset('/scripts/article-edit.js') ?>" defer></script>
<script src="<?= asset('/scripts/client-picker.js') ?>" defer></script>
<script src="<?= asset('/scripts/article-loan.js') ?>" defer></script>
<script src="<?= asset('/scripts/article-sale.js') ?>" defer></script>
<script src="<?= asset('/scripts/article-lost.js') ?>" defer></script>
<script src="<?= asset('/scripts/article-return.js') ?>" defer></script>
<script>
    // Titre du modal « bientôt disponible » = action cliquée.
    // show.bs.modal : événement Bootstrap juste avant l'ouverture ; e.relatedTarget = bouton qui l'a ouvert
    document.getElementById("article-soon-modal").addEventListener("show.bs.modal", (e) => {
        e.target.querySelector(".modal-title").textContent = e.relatedTarget?.dataset.action ?? "Bientôt disponible";
    });
</script>
</body>
</html>
