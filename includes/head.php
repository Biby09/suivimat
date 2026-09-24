<?php
$site_name = 'SuiviMat';
$site_url = 'https://suivimat.florianlovis.ch';
$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$page_title = $page_title ?? 'SuiviMat | Gestion du matériel de prêt';
$page_description = $page_description ?? 'SuiviMat simplifie la gestion et le suivi du matériel de prêt pour les associations et petites entreprises.';
$canonical_url = $canonical_url ?? $site_url . $request_path;
$private_path = preg_match('#^/(api|dashboard|scanner|login|register)(/|$)#', $request_path) === 1;
$robots_directive = $robots_directive ?? ($private_path ? 'noindex, nofollow' : 'index, follow');
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= htmlspecialchars($robots_directive, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') ?>">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_CH">
    <meta property="og:site_name" content="<?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') ?>">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="stylesheet" href="/style/css/style.css">
    <script src="/style/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
