<?php
// Codes QR d'une organisation en PDF (planches d'étiquettes ou liste des codes), dialog includes/modals/download-qr-modal.php.
// POST : organisation_id, export_type (labels | codes), mode :
//   new     : object_count nouveaux codes, créés pour l'occasion (nombre entier de feuilles d'étiquettes) ;
//   reprint : codes existants réimprimés (étiquette abîmée, ancienne adresse…), sans en créer :
//             reprint_scope = all (tous les objets enregistrés) ou codes (reprint_codes : codes séparés par des espaces / virgules)
// Planches : sheet_format (A4, A5, Letter ou custom avec sheet_width / sheet_height), label_columns, label_rows.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

const MAX_OBJECTS = 1000;
const SHEET_FORMATS = [
    'A4' => [210, 297],
    'A5' => [148, 210],
    'Letter' => [216, 279],
];

function sendError(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Méthode non autorisée');
}

$organisationId = filter_input(INPUT_POST, 'organisation_id', FILTER_VALIDATE_INT);
$exportType = $_POST['export_type'] ?? '';
$reprint = ($_POST['mode'] ?? 'new') === 'reprint';
$objectCount = filter_input(INPUT_POST, 'object_count', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => MAX_OBJECTS]]);

if (!$organisationId) {
    sendError(400, 'ID d\'organisation invalide');
}
if (!in_array($exportType, ['labels', 'codes'], true)) {
    sendError(400, 'Type de téléchargement invalide');
}
if (!$reprint && !$objectCount) {
    sendError(400, 'Le nombre d\'objets doit être compris entre 1 et ' . MAX_OBJECTS);
}

// Vérifie aussi que l'utilisateur est membre de l'organisation
try {
    $organisation = $organisationManager->getOrganisationById($organisationId);
} catch (Exception $e) {
    sendError(404, 'Organisation introuvable');
}

// Paramètres des planches d'étiquettes
if ($exportType === 'labels') {
    $sheetFormat = $_POST['sheet_format'] ?? '';
    $columns = filter_input(INPUT_POST, 'label_columns', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
    $rows = filter_input(INPUT_POST, 'label_rows', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);

    if (isset(SHEET_FORMATS[$sheetFormat])) {
        [$sheetWidth, $sheetHeight] = SHEET_FORMATS[$sheetFormat];
    } elseif ($sheetFormat === 'custom') {
        $sheetWidth = filter_input(INPUT_POST, 'sheet_width', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 50, 'max_range' => 500]]);
        $sheetHeight = filter_input(INPUT_POST, 'sheet_height', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 50, 'max_range' => 500]]);
        if (!$sheetWidth || !$sheetHeight) {
            sendError(400, 'Les dimensions de la feuille doivent être comprises entre 50 et 500 mm');
        }
    } else {
        sendError(400, 'Format de feuille invalide');
    }

    if (!$columns || !$rows) {
        sendError(400, 'Le nombre de colonnes (1 à 10) ou de lignes (1 à 20) est invalide');
    }
    // Nouveaux codes : feuilles complètes (on n'imprime pas d'étiquettes vides) ; réimpression : nombre libre
    if (!$reprint && $objectCount % ($columns * $rows) !== 0) {
        sendError(400, 'Le nombre d\'objets doit être un multiple de ' . ($columns * $rows) . ' (étiquettes par feuille)');
    }

    // Dimensions d'une étiquette et de son contenu (en mm) : logo, code QR, puis code lisible
    $padding = 2;
    $textHeight = 5;
    $logoGap = 1;
    $logoRatio = 151.78 / 28.23; // largeur / hauteur de medias/logo.svg
    $labelWidth = $sheetWidth / $columns;
    $labelHeight = $sheetHeight / $rows;
    // Le code QR occupe au plus 60 % de la place disponible, plafonné à 25 mm ; le logo fait la même largeur
    $availableHeight = ($labelHeight - 2 * $padding - $textHeight - $logoGap) / (1 + 1 / $logoRatio);
    $qrSize = min(25, 0.6 * min($labelWidth - 2 * $padding, $availableHeight));
    $logoHeight = $qrSize / $logoRatio;

    if ($qrSize < 12) {
        sendError(400, 'Les étiquettes sont trop petites pour contenir un code QR lisible');
    }
}

$articleManager = new ArticleManager($mysqlClient, $organisation);

if ($reprint) {
    if (($_POST['reprint_scope'] ?? '') === 'all') {
        // Tous les objets enregistrés (pas les codes encore vierges), triés par nom
        $codes = array_map(fn(Article $article) => $article->getCode(),
            $articleManager->searchConfiguredArticles(null, false, null, [], MAX_OBJECTS, 0)['articles']);
    } else {
        // « AB12, CD34 ef56 » → ['AB12', 'CD34', 'EF56'] (sans doublons)
        $codes = array_values(array_unique(preg_split('/[\s,;]+/', strtoupper($_POST['reprint_codes'] ?? ''), -1, PREG_SPLIT_NO_EMPTY)));
        foreach ($codes as $code) {
            try {
                $articleManager->getArticleByCode($code); // le code doit appartenir à cette organisation
            } catch (Exception $e) {
                sendError(404, 'Le code ' . $code . ' n\'appartient pas à cette organisation');
            }
        }
    }
    if (!$codes) {
        sendError(400, 'Aucun code à réimprimer');
    }
    if (count($codes) > MAX_OBJECTS) {
        sendError(400, 'Vous pouvez réimprimer au maximum ' . MAX_OBJECTS . ' codes à la fois');
    }
} else {
    try {
        $codes = $articleManager->createBlankArticles($objectCount);
    } catch (Exception $e) {
        error_log('Génération des codes impossible : ' . $e->getMessage());
        sendError(500, 'Erreur lors de la génération des codes');
    }
}

if ($exportType === 'labels') {
    // Le code QR renvoie vers la fiche de l'article (dashboard/organisation/article/index.php), qui le retrouve par son code :
    // membre de l'organisation → fiche ; objet perdu scanné par quelqu'un d'autre → page pour contacter l'organisation
    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $articleBaseUrl = ($isHttps ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/dashboard/organisation/article/?code=';

    $pdf = new TCPDF($sheetWidth > $sheetHeight ? 'L' : 'P', 'mm', [$sheetWidth, $sheetHeight], true, 'UTF-8');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetFont('helvetica', 'B', 10);

    // Logo dessiné une seule fois dans un modèle, puis réutilisé sur chaque étiquette (nécessite une page ouverte)
    $pdf->AddPage();
    $logoTemplate = $pdf->startTemplate($qrSize, $logoHeight);
    $pdf->ImageSVG($_SERVER['DOCUMENT_ROOT'] . '/medias/logo.svg', 0, 0, $qrSize, $logoHeight);
    $pdf->endTemplate();

    $labelsPerSheet = $columns * $rows;
    foreach ($codes as $index => $code) {
        if ($index > 0 && $index % $labelsPerSheet === 0) {
            $pdf->AddPage();
        }

        $position = $index % $labelsPerSheet;
        $labelX = ($position % $columns) * $labelWidth;
        $labelY = intdiv($position, $columns) * $labelHeight;

        // Logo, code QR et code lisible centrés dans l'étiquette
        $contentX = $labelX + ($labelWidth - $qrSize) / 2;
        $contentY = $labelY + ($labelHeight - $logoHeight - $logoGap - $qrSize - $textHeight) / 2;
        $qrY = $contentY + $logoHeight + $logoGap;
        $pdf->printTemplate($logoTemplate, $contentX, $contentY, $qrSize, $logoHeight);
        $pdf->write2DBarcode($articleBaseUrl . urlencode($code), 'QRCODE,M', $contentX, $qrY, $qrSize, $qrSize);
        $pdf->SetXY($labelX, $qrY + $qrSize);
        $pdf->Cell($labelWidth, $textHeight, $code, 0, 0, 'C');
    }
} else {
    // Liste des codes uniquement, sur A4
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Codes des objets - ' . $organisation->getName(), 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 6, count($codes) . ($reprint ? ' codes réimprimés le ' : ' codes générés le ') . date('d.m.Y'), 0, 1);
    $pdf->Ln(4);

    $pdf->SetFont('courier', 'B', 14);
    $columnCount = 6;
    $cellWidth = 180 / $columnCount;
    foreach ($codes as $index => $code) {
        $pdf->Cell($cellWidth, 10, $code, 1, ($index + 1) % $columnCount === 0 ? 1 : 0, 'C');
    }
}

$pdf->Output('codes-qr-' . $organisation->getId() . '-' . date('Ymd-His') . '.pdf', 'I');
