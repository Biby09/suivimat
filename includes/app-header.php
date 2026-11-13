<?php
/*
    Header de l'application (pages /dashboard), dans le style du header du site public (includes/header.php) :
    - gauche : logo (→ tableau de bord) et sélecteur de l'organisation courante ;
    - milieu (grand écran) : Inventaire, Prêts en cours, Clients de l'organisation ;
    - droite : Scanner et menu du compte (grand écran) ;
    - mobile et tablette (moins de 992 px) : logo et organisation en haut, navigation fixée en bas de l'écran
      (includes/app-bottom-nav.php, incluse à la fin de ce fichier).
    La page doit avoir <body class="app"> (hauteur du header : style.scss, variable --header-height).

    Prérequis : $organisationManager et $user (config/auth.php).
    Optionnel : $organisation (Organisation courante : nom affiché, liens, scanner) ;
                $app_header_scanner = false pour masquer le scanner (ex. pendant l'initialisation d'une organisation).
*/
$appOrganisations = $organisationManager->getAllOrganisationsForUser();
$appCurrent = $organisation ?? null;
$appScanner = $appCurrent !== null && ($app_header_scanner ?? true);
$appSettingsUrl = $appCurrent ? '/dashboard/organisation/settings/?id=' . $appCurrent->getId() : null;
$appOnSettings = str_starts_with($_SERVER['SCRIPT_NAME'], '/dashboard/organisation/settings/');

// Liens de l'organisation courante. 'sections' : débuts de chemin des pages où le lien est actif (en gras),
// ex. la page d'un client (clients/client.php) active « Clients », la fiche d'un objet active « Inventaire ».
// 'short' et 'icon' : libellé court et icône de la barre du bas sur mobile (includes/app-bottom-nav.php)
$appLinks = [];
if ($appCurrent) {
    $appLinks = [
        ['label' => 'Inventaire', 'short' => 'Inventaire', 'icon' => 'box-seam',
            'url' => '/dashboard/organisation/?id=' . $appCurrent->getId(),
            'sections' => ['/dashboard/organisation/index.php', '/dashboard/organisation/article/']],
        ['label' => 'Prêts en cours', 'short' => 'Prêts', 'icon' => 'arrow-left-right',
            'url' => '/dashboard/organisation/loans/?id=' . $appCurrent->getId(),
            'sections' => ['/dashboard/organisation/loans/']],
        ['label' => 'Clients', 'short' => 'Clients', 'icon' => 'people',
            'url' => '/dashboard/organisation/clients/?id=' . $appCurrent->getId(),
            'sections' => ['/dashboard/organisation/clients/']],
    ];
}

/** Le lien est-il actif ? Vrai si le fichier de la page commence par un de ses chemins (str_starts_with) */
$appIsActive = function (array $sections): bool {
    foreach ($sections as $section) {
        if (str_starts_with($_SERVER['SCRIPT_NAME'], $section)) {
            return true;
        }
    }
    return false;
};
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

                <?php if ($appSettingsUrl): ?>
                    <!-- Engrenage : paramètres de l'organisation courante (plein sur la page des paramètres).
                         Grand écran seulement : sur mobile, dans le panneau « Plus » de la barre du bas -->
                    <a class="btn <?= $appOnSettings ? 'btn-secondary' : 'btn-outline-secondary' ?> flex-shrink-0 d-none d-lg-flex align-items-center px-2"
                        href="<?= $appSettingsUrl ?>" title="Paramètres de l'organisation" aria-label="Paramètres de l'organisation"
                        <?= $appOnSettings ? 'aria-current="page"' : '' ?>>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/></svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Droite : scanner et compte (grand écran ; sur mobile, dans la barre du bas). order-lg-last : après les liens du milieu -->
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

            </div>

            <!-- Milieu (grand écran, dès 992 px ; masqué en dessous : barre du bas) -->
            <div class="collapse navbar-collapse" id="appNavbarContent">
                <?php if ($appLinks): ?>
                    <ul class="navbar-nav mx-auto gap-lg-3">
                        <?php foreach ($appLinks as $link): ?>
                            <?php $isActive = $appIsActive($link['sections']); ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $isActive ? 'active fw-semibold' : '' ?>" href="<?= $link['url'] ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                                    <?= htmlspecialchars($link['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
<?php
include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-bottom-nav.php';

if ($appScanner) {
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/quick-scan-modal.php';
}
?>
