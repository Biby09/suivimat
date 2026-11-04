<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

// Initialisation du PDO;

if (($_SERVER['SERVER_NAME'] === 'localhost') || ($_SERVER['SERVER_NAME'] === '192.168.1.101')) {
    $MYSQL_HOST = 'localhost';
    $MYSQL_PORT = 3306;
    $MYSQL_NAME = 'suivimat';
    $MYSQL_USER = 'root';
    $MYSQL_PASSWORD = 'root';

} else {
    $MYSQL_HOST = getenv('DB_HOST');
    $MYSQL_PORT = getenv('DB_PORT');
    $MYSQL_NAME = getenv('DB_NAME');
    $MYSQL_USER = getenv('DB_USER');
    $MYSQL_PASSWORD = getenv('DB_PASSWORD');

    if (!$MYSQL_HOST || !$MYSQL_PORT || !$MYSQL_NAME || !$MYSQL_USER || $MYSQL_PASSWORD === false) {
        error_log('Configuration de base de données manquante');
        die('Erreur de configuration de la base de données.');
    }
}


try {
    $mysqlClient = new PDO(
        sprintf('mysql:host=%s;dbname=%s;port=%s;charset=utf8', $MYSQL_HOST, $MYSQL_NAME, $MYSQL_PORT),
        $MYSQL_USER,
        $MYSQL_PASSWORD
    );

    $mysqlClient->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $exception) {
    error_log('Connexion à la base de données impossible : ' . $exception->getMessage());
    die('Erreur de connexion à la base de données.');
}

date_default_timezone_set('UTC');
$mysqlClient->exec("SET time_zone = '+00:00'");

// Version de l'application, affichée dans les footers (includes/footer.php et includes/app-footer.php)
const APP_VERSION = '0.0.0';

// Dossier des PDF de codes QR générés (api/organisation/generate-qr-codes.php les écrit, qr-pdf.php les sert).
// Dans le dossier temporaire du système, hors du site : aucun fichier n'est accessible directement par une adresse.
// define() et non const : la valeur est calculée par une fonction
define('QR_PDF_DIRECTORY', sys_get_temp_dir() . '/suivimat-qr-pdf');

/**
 * URL d'un fichier statique (JS, CSS) suivie de sa date de modification : « /scripts/x.js?v=1793800000 ».
 * Le numéro change à chaque modification du fichier, le navigateur ne peut donc pas garder une ancienne version en cache.
 * Utilisation : <script src="<?= asset('/scripts/x.js') ?>" defer></script>
 */
function asset(string $path): string
{
    return $path . '?v=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $path);
}

/**
 * Adresse où renvoyer après la connexion (?redirect=…), seulement si elle est interne au site :
 * « /dashboard/account/ » est acceptée ; « https://autre-site.com », « //autre-site.com » ou « /\autre-site.com »
 * sont refusées (sinon un lien piégé pourrait renvoyer vers un faux site juste après la connexion).
 */
function safeRedirect(?string $path, string $default = '/dashboard'): string
{
    $path = (string) $path;
    // Doit commencer par un seul « / » (chemin du site) : « // » et « /\ » mènent vers un autre domaine
    $isInternal = str_starts_with($path, '/') && !str_starts_with($path, '//') && !str_starts_with($path, '/\\');
    return $isInternal ? $path : $default;
}

/**
 * Date de la base (« 2026-11-04 » ou « 2026-11-04 15:30:00 ») au format suisse : « 04.11.2026 »
 */
function formatDate(string $date): string
{
    return date('d.m.Y', strtotime($date));
}

/**
 * Date reçue d'un formulaire (<input type="date"> : AAAA-MM-JJ) : renvoyée telle quelle si elle existe,
 * null sinon (« 2026-02-30 », « demain », texte vide…).
 */
function parseDate(string $text): ?string
{
    // « ! » : les parties absentes (heure) valent 0 ; la date relue doit être identique (PHP changerait le 30 février en 2 mars)
    $date = DateTime::createFromFormat('!Y-m-d', $text);
    return $date && $date->format('Y-m-d') === $text ? $text : null;
}

/**
 * Adresse e-mail facultative reçue d'un formulaire (ex. e-mail d'un client) :
 * null si le champ est vide, l'adresse si elle est valide (100 caractères au plus), false si elle est invalide.
 * string|false|null : type « union », la fonction peut renvoyer l'un de ces trois types.
 */
function parseOptionalEmail(string $text): string|false|null
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }
    return mb_strlen($text) <= 100 ? filter_var($text, FILTER_VALIDATE_EMAIL) : false;
}

/**
 * Envoie un e-mail en texte brut (UTF-8). $replyTo : adresse à laquelle « Répondre » écrira.
 * En local (localhost), l'e-mail n'est PAS envoyé : il est écrit dans le journal d'erreurs de PHP (error_log),
 * pour tester sans écrire à de vraies personnes. Renvoie true si l'envoi (ou l'écriture locale) a réussi.
 */
function sendMail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    // Retours à la ligne interdits dans les en-têtes : sinon on pourrait y injecter d'autres destinataires
    $replyTo = $replyTo !== null ? str_replace(["\r", "\n"], '', $replyTo) : null;
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n" . ($replyTo ? "Reply-To: $replyTo\r\n" : '');
    // Sujet encodé (=?UTF-8?B?…?=) : les accents s'affichent correctement dans les messageries
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
        error_log("[sendMail local, non envoyé] À : $to | Sujet : $subject | Répondre à : $replyTo\n$body");
        return true;
    }

    return mail($to, $encodedSubject, $body, $headers);
}

// Initialisation des managers principaux
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/userManager.php';
$userManager = new UserManager($mysqlClient);