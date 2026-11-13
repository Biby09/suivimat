<?php
/*
    Barre de navigation fixée en bas de l'écran, sur mobile et tablette (moins de 992 px, là où le menu du haut est
    masqué), dans l'esprit d'une application : icône + libellé court, bouton central « Scanner » mis en avant,
    et « Plus » qui ouvre un panneau avec les autres pages (offcanvas Bootstrap).
    Incluse par includes/app-header.php, qui fournit $appCurrent, $appLinks, $appIsActive, $appScanner,
    $appSettingsUrl et $appOnSettings.

    Dans une organisation : Inventaire · Prêts · [Scanner] · Clients · Plus
                            (sans le bouton central si le scanner est masqué, ex. pendant l'initialisation)
    Hors organisation     : Accueil · Compte · Plus
    Styles : style.scss, section « NAVIGATION DU BAS ».
*/

// Icônes Bootstrap Icons (https://icons.getbootstrap.com), en SVG dans la page comme le reste du projet :
// seulement le contenu de la balise <svg> (les <path>), la balise elle-même est écrite par $navIcon
$navIconPaths = [
    'box-seam' => '<path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z"/>',
    'arrow-left-right' => '<path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5m14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5"/>',
    'people' => '<path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>',
    'grid' => '<path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z"/>',
    'person' => '<path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>',
    'list' => '<path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>',
    'gear' => '<path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>',
    'house' => '<path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>',
    'box-arrow-right' => '<path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>',
    'qr-code-scan' => '<path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z"/><path d="M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z"/><path d="M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z"/><path d="M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1H8v1z"/><path d="M12 9h2V8h-2z"/>',
];

/** Balise <svg> complète d'une icône (taille réglée en CSS ; aria-hidden : le libellé suffit aux lecteurs d'écran) */
$navIcon = fn(string $name): string =>
    '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">' . $navIconPaths[$name] . '</svg>';

// Pages hors organisation
$navDashboard = ['label' => 'Tableau de bord', 'short' => 'Accueil', 'icon' => 'grid', 'url' => '/dashboard',
    'active' => $_SERVER['SCRIPT_NAME'] === '/dashboard/index.php'];
$navAccount = ['label' => 'Gérer mon compte', 'short' => 'Compte', 'icon' => 'person', 'url' => '/dashboard/account',
    'active' => str_starts_with($_SERVER['SCRIPT_NAME'], '/dashboard/account/')];

// Barre : les liens de l'organisation (2 avant le bouton central, 1 après), ou les pages hors organisation
if ($appCurrent) {
    $navItems = [];
    foreach ($appLinks as $link) {
        $navItems[] = ['label' => $link['label'], 'short' => $link['short'], 'icon' => $link['icon'],
            'url' => $link['url'], 'active' => $appIsActive($link['sections'])];
    }
    // Panneau « Plus » : paramètres de l'organisation, puis le tableau de bord et le compte
    $navMore = [
        ['label' => "Paramètres de l'organisation", 'icon' => 'gear', 'url' => $appSettingsUrl, 'active' => $appOnSettings],
        $navDashboard,
        $navAccount,
    ];
} else {
    $navItems = [$navDashboard, $navAccount];
    $navMore = [];
}
// « Plus » est actif (en couleur) quand la page affichée est dans le panneau
$navMoreActive = in_array(true, array_column($navMore, 'active'), true);

/** Un bouton de la barre : icône au-dessus, libellé court en dessous */
$navButton = function (array $item) use ($navIcon): void { ?>
    <a class="app-bottom-nav-item<?= $item['active'] ? ' active' : '' ?>" href="<?= htmlspecialchars($item['url']) ?>"
        <?= $item['active'] ? 'aria-current="page"' : '' ?> aria-label="<?= htmlspecialchars($item['label']) ?>">
        <?= $navIcon($item['icon']) ?>
        <span><?= htmlspecialchars($item['short']) ?></span>
    </a>
<?php };
?>
<nav class="app-bottom-nav d-lg-none" aria-label="Navigation principale">
    <?php
    // array_slice : les 2 premiers boutons, avant le bouton central
    foreach (array_slice($navItems, 0, 2) as $item) {
        $navButton($item);
    }
    ?>

    <?php if ($appScanner): ?>
        <!-- Bouton central : scanner rapide (includes/modals/quick-scan-modal.php, inclus par includes/app-header.php) -->
        <button type="button" class="app-bottom-nav-center" data-bs-toggle="modal" data-bs-target="#quick-scan-modal" aria-label="Scanner un objet">
            <span class="app-bottom-nav-center-circle"><?= $navIcon('qr-code-scan') ?></span>
            <span>Scanner</span>
        </button>
    <?php endif; ?>

    <?php
    // Les boutons restants (Clients), après le bouton central
    foreach (array_slice($navItems, 2) as $item) {
        $navButton($item);
    }
    ?>

    <button type="button" class="app-bottom-nav-item<?= $navMoreActive ? ' active' : '' ?>" data-bs-toggle="offcanvas"
        data-bs-target="#app-more-menu" aria-controls="app-more-menu" aria-label="Plus de pages">
        <?= $navIcon('list') ?>
        <span>Plus</span>
    </button>
</nav>

<!-- Panneau « Plus » : s'ouvre depuis le bas de l'écran -->
<div class="offcanvas offcanvas-bottom app-more-menu d-lg-none" tabindex="-1" id="app-more-menu" aria-labelledby="app-more-menu-title">
    <div class="offcanvas-header pb-2">
        <div class="text-truncate">
            <h2 class="offcanvas-title fs-5 mb-0 text-truncate" id="app-more-menu-title"><?= htmlspecialchars($appCurrent?->getName() ?? 'Menu') ?></h2>
            <small class="text-body-secondary"><?= htmlspecialchars($user->getFullname()) ?></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <div class="list-group list-group-flush">
            <?php foreach ($navMore as $item): ?>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3<?= $item['active'] ? ' active' : '' ?>"
                    href="<?= htmlspecialchars($item['url']) ?>" <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                    <?= $navIcon($item['icon']) ?><?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="/">
                <?= $navIcon('house') ?>Site SuiviMat
            </a>
            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 text-danger" href="/auth/logout.php">
                <?= $navIcon('box-arrow-right') ?>Se déconnecter
            </a>
        </div>
        <!-- Liens du footer (masqué sur mobile : includes/app-footer.php) -->
        <ul class="list-inline small text-body-secondary text-center mt-3 mb-0">
            <li class="list-inline-item"><a href="/contact" class="link-secondary">Contact</a></li>
            <li class="list-inline-item"><a href="/mentions-legales.php" class="link-secondary">Mentions légales</a></li>
            <li class="list-inline-item">Version <?= htmlspecialchars(APP_VERSION) ?></li>
        </ul>
    </div>
</div>
