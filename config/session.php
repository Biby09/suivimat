<?php
// Durée de vie d'une session : 1 heure (3600 secondes)
$lifetime = 3600*12; // 12 heures
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// Configure la durée de vie du cookie de session avant le démarrage de la session.
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    if ($elapsed > $lifetime) {
        // La session a expiré
        session_unset();     // Libère toutes les variables de session
        session_destroy();   // Détruit la session
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();     // Démarre une nouvelle session
    }
}

// Mise à jour du timestamp d'activité
$_SESSION['last_activity'] = time();
