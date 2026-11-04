<?php
/*
    Header de l'application (pages /dashboard), dans le style du header du site public (includes/header.php) :
    - gauche : logo (→ tableau de bord) et sélecteur de l'organisation courante ;
    - milieu (grand écran) : Inventaire, Prêts en cours, Clients de l'organisation ;
    - droite : Scanner (grand écran) et menu du compte ;
    - mobile : menu burger avec les liens, « Scanner un objet » et le compte (le bouton flottant de l'inventaire reste).
    La page doit avoir <body class="app"> (hauteur du header : style.scss, variable --header-height).

    Prérequis : $organisationManager et $user (config/auth.php).
    Optionnel : $organisation (Organisation courante : nom affiché, liens, scanner) ;
                $app_header_scanner = false pour masquer le scanner (ex. pendant l'initialisation d'une organisation).
*/
$appOrganisations = $organisationManager->getAllOrganisationsForUser();
$appCurrent = $organisation ?? null;
$appScanner = $appCurrent !== null && ($app_header_scanner ?? true);

// Liens de l'organisation courante ; « Prêts en cours » et « Clients » : pages pas encore créées
$appLinks = [];
if ($appCurrent) {
    $appLinks = [
        ['label' => 'Inventaire', 'url' => '/dashboard/organisation/?id=' . $appCurrent->getId(), 'script' => '/dashboard/organisation/index.php'],
        ['label' => 'Prêts en cours', 'url' => '/dashboard/organisation/loans/?id=' . $appCurrent->getId(), 'script' => '/dashboard/organisation/loans/index.php'],
        ['label' => 'Clients', 'url' => '/dashboard/organisation/clients/?id=' . $appCurrent->getId(), 'script' => '/dashboard/organisation/clients/index.php'],
    ];
}
?>
<header class="app-header border-bottom border-primary border-5 fixed-top bg-white">
    <nav class="navbar navbar-expand-lg" aria-label="Navigation de l'application">
        <div class="container">
            <!-- Gauche : logo et organisation courante -->
            <div class="d-flex align-items-center gap-3 app-header-start">
                <a href="/dashboard" class="flex-shrink-0" title="Tableau de bord">
                    <img src="/medias/logo.svg" alt="SuiviMat – tableau de bord" height="20">
                </a>

                <div class="dropdown app-org-switch">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100 d-flex align-items-center gap-2 text-start" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Organisation : <?= htmlspecialchars($appCurrent?->getName() ?? 'aucune') ?> (changer)">
                        <!-- Icône « bâtiment » -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="flex-shrink-0" aria-hidden="true"><path d="M4 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM4 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zM7.5 5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zM4.5 8a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/><path d="M2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1zm11 0H3v14h3v-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V15h3z"/></svg>
                        <span class="text-truncate flex-grow-1"><?= htmlspecialchars($appCurrent?->getName() ?? 'Choisir une organisation') ?></span>
                    </button>
                    <ul class="dropdown-menu shadow w-100">
                        <li><h6 class="dropdown-header">Mes organisations</h6></li>
                        <?php foreach ($appOrganisations as $appOrganisation): ?>
                            <?php $isCurrent = $appCurrent?->getId() === $appOrganisation->getId(); ?>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between gap-3 <?= $isCurrent ? 'active' : '' ?>"
                                    href="/dashboard/organisation/?id=<?= $appOrganisation->getId() ?>" <?= $isCurrent ? 'aria-current="true"' : '' ?>>
                                    <span class="text-truncate"><?= htmlspecialchars($appOrganisation->getName()) ?></span>
                                    <?php if ($isCurrent): ?><span aria-hidden="true">✓</span><?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/dashboard">Tableau de bord</a></li>
                    </ul>
                </div>
            </div>

            <!-- Droite : scanner et compte (grand écran) ; burger (mobile). order-lg-last : après les liens du milieu -->
            <div class="d-flex align-items-center gap-2 order-lg-last ms-2">
                <?php if ($appScanner): ?>
                    <!-- Scanner rapide (includes/modals/quick-scan-modal.php, inclus en bas de ce fichier) -->
                    <button type="button" class="btn btn-primary btn-sm d-none d-lg-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#quick-scan-modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z"/><path d="M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z"/><path d="M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z"/><path d="M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1H8v1z"/><path d="M12 9h2V8h-2z"/></svg>
                        Scanner
                    </button>
                <?php endif; ?>

                <div class="dropdown d-none d-lg-block">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu du compte">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/></svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><h6 class="dropdown-header"><?= htmlspecialchars($user->getFullname()) ?></h6></li>
                        <li><a class="dropdown-item" href="/dashboard/account">Gérer mon compte</a></li>
                        <li><a class="dropdown-item" href="/">Site SuiviMat</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/auth/logout.php">Se déconnecter</a></li>
                    </ul>
                </div>

                <!-- Burger animé (styles .burger de style.scss, comme le header du site public) -->
                <button class="navbar-toggler border-0 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#appNavbarContent"
                    aria-controls="appNavbarContent" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="burger" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
            </div>

            <!-- Milieu (grand écran) / contenu du burger (mobile) -->
            <div class="collapse navbar-collapse" id="appNavbarContent">
                <?php if ($appLinks): ?>
                    <ul class="navbar-nav mx-auto mt-3 mt-lg-0 gap-lg-3">
                        <?php foreach ($appLinks as $link): ?>
                            <?php $isActive = $_SERVER['SCRIPT_NAME'] === $link['script']; ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $isActive ? 'active fw-semibold' : '' ?>" href="<?= $link['url'] ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                                    <?= htmlspecialchars($link['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <!-- Mobile : scanner et compte dans le menu -->
                <ul class="navbar-nav d-lg-none border-top mt-2 pt-2">
                    <?php if ($appScanner): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#quick-scan-modal">Scanner un objet</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/dashboard/account">Gérer mon compte</a></li>
                    <li class="nav-item"><a class="nav-link" href="/">Site SuiviMat</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="/auth/logout.php">Se déconnecter</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<?php
if ($appScanner) {
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/quick-scan-modal.php';
}
?>
