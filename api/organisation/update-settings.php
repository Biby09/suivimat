<?php
// Enregistre une partie des paramètres d'une organisation (page dashboard/organisation/settings/, scripts/organisation-settings.js).
// POST : organisation_id, section, puis selon la section :
//   general : name
//   loans   : default_loan_days (vide = indéterminée), reminders_enabled (« 1 » si coché), contract_terms (vide = texte par défaut)
//   lost    : lost_page_enabled (« 1 » si coché), lost_message
// Tous les membres de l'organisation peuvent modifier ses paramètres.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_LOAN_DAYS = 365;
const MAX_LOST_MESSAGE = 500;
const MAX_CONTRACT_TERMS = 5000;

function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Texte facultatif : null si vide (après avoir retiré les espaces au début et à la fin)
function optionalText(string $text): ?string
{
    $text = trim($text);
    return $text === '' ? null : $text;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Méthode non autorisée');
}

try {
    // Vérifie aussi que l'utilisateur est membre de l'organisation
    $organisation = $organisationManager->getOrganisationById((int) filter_input(INPUT_POST, 'organisation_id', FILTER_VALIDATE_INT));
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

// Case à cocher : envoyée (« 1 ») seulement si elle est cochée
$checked = fn(string $name): bool => ($_POST[$name] ?? '') === '1';

switch ($_POST['section'] ?? '') {
    case 'general':
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            sendError(400, 'Le nom est requis et ne peut pas dépasser 100 caractères');
        }
        $organisationManager->rename($organisation, $name);
        break;

    case 'loans':
        $daysText = trim($_POST['default_loan_days'] ?? '');
        $days = $daysText === '' ? null : filter_var($daysText, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => MAX_LOAN_DAYS]]);
        if ($days === false) {
            sendError(400, 'La durée par défaut doit être comprise entre 1 et ' . MAX_LOAN_DAYS . ' jours');
        }
        $terms = optionalText($_POST['contract_terms'] ?? '');
        if ($terms !== null && mb_strlen($terms) > MAX_CONTRACT_TERMS) {
            sendError(400, 'Les conditions ne peuvent pas dépasser ' . MAX_CONTRACT_TERMS . ' caractères');
        }
        $organisationManager->updateLoanSettings($organisation, $days, $checked('reminders_enabled'), $terms);
        break;

    case 'lost':
        $message = optionalText($_POST['lost_message'] ?? '');
        if ($message !== null && mb_strlen($message) > MAX_LOST_MESSAGE) {
            sendError(400, 'Le message ne peut pas dépasser ' . MAX_LOST_MESSAGE . ' caractères');
        }
        $organisationManager->updateLostSettings($organisation, $checked('lost_page_enabled'), $message);
        break;

    default:
        sendError(400, 'Section inconnue');
}

echo json_encode(['success' => true]);
