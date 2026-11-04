<?php
// Contrat de prêt en PDF (lien « Contrat » de l'historique des prêts, fiche d'un objet).
// GET : loan_id
// Contenu : organisation, emprunteur, objet (catégorie, informations), dates, conditions de l'organisation
// (page « Paramètres » ; texte par défaut sinon), signatures.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/clientManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

// Conditions utilisées quand l'organisation n'a pas écrit les siennes
const DEFAULT_CONTRACT_TERMS = "L'emprunteur s'engage à prendre soin du matériel et à l'utiliser conformément à sa destination.\n"
    . "Le matériel doit être rendu complet, propre et dans l'état où il a été reçu, au plus tard à la date de retour prévue.\n"
    . "Tout dégât, perte ou vol doit être signalé sans délai à l'organisation.";

$loanId = (int) filter_input(INPUT_GET, 'loan_id', FILTER_VALIDATE_INT);

try {
    // Vérifie que l'utilisateur est membre de l'organisation de l'objet prêté
    $organisation = $organisationManager->getOrganisationOfLoan($loanId);
    $loan = (new LoanManager($mysqlClient, $organisation))->getLoanById($loanId);
    $articleManager = new ArticleManager($mysqlClient, $organisation);
    $article = $articleManager->getArticleById($loan->getArticleId());
    $client = (new ClientManager($mysqlClient, $organisation))->getClientById($loan->getClientId());
} catch (Exception $e) {
    http_response_code(404);
    exit('Prêt introuvable');
}

$categoryPath = $article->getCategoryId() !== null ? (new CategoryManager($mysqlClient, $organisation))->getCategoryPath($article->getCategoryId()) : [];
$categoryLabel = $categoryPath ? implode(' › ', array_map(fn(Category $category) => $category->getName(), $categoryPath)) : 'Sans catégorie';
$fields = $articleManager->getDisplayedFields($article);
$terms = $organisation->getContractTerms() ?? DEFAULT_CONTRACT_TERMS;

// Le contenu est écrit en HTML simple, que TCPDF sait mettre en page ; h() échappe chaque valeur
$h = fn(?string $text): string => htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
$row = fn(string $label, string $value): string => '<tr><td width="35%"><b>' . $h($label) . '</b></td><td width="65%">' . $value . '</td></tr>';

$objectRows = $row('Objet', $h($article->getName()))
    . $row('Code', $h($article->getCode()))
    . $row('Catégorie', $h($categoryLabel));
foreach ($fields as $field) {
    $objectRows .= $row($field['name'], $h($field['value']));
}

$loanRows = $row('Date de sortie', formatDate($loan->getStartDate()))
    . $row('Retour prévu', $loan->getDueDate() ? formatDate($loan->getDueDate()) : 'Date indéterminée');
if ($loan->getEndDate()) {
    $loanRows .= $row('Rendu le', formatDate($loan->getEndDate()));
}

$html = '<h1>Contrat de prêt</h1>'
    . '<p>' . $h($organisation->getName()) . ' · prêt n° ' . $loan->getId() . ' · établi le ' . date('d.m.Y') . '</p>'
    . '<h3>Emprunteur</h3><table cellpadding="3">' . $row('Nom', $h($client->getFullname()))
    . ($client->getEmail() ? $row('E-mail', $h($client->getEmail())) : '') . '</table>'
    . '<h3>Matériel prêté</h3><table cellpadding="3">' . $objectRows . '</table>'
    . '<h3>Prêt</h3><table cellpadding="3">' . $loanRows . '</table>'
    // nl2br : chaque retour à la ligne des conditions devient un saut de ligne dans le PDF
    . '<h3>Conditions</h3><p>' . nl2br($h($terms)) . '</p>'
    . '<br><br><table cellpadding="4"><tr>'
    . '<td width="50%">Pour ' . $h($organisation->getName()) . '<br><br><br>Signature : ____________________<br>Lieu et date : _________________</td>'
    . '<td width="50%">L\'emprunteur<br><br><br>Signature : ____________________<br>Lieu et date : _________________</td>'
    . '</tr></table>';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetTitle('Contrat de prêt n° ' . $loan->getId());
$pdf->AddPage();
// Logo de 35 mm de large ; hauteur selon ses proportions (largeur / hauteur de medias/logo.svg, comme les étiquettes QR)
$pdf->ImageSVG($_SERVER['DOCUMENT_ROOT'] . '/medias/logo.svg', 20, 12, 35, 35 / (151.78 / 28.23));
$pdf->SetY(22);
$pdf->SetFont('helvetica', '', 10);
$pdf->writeHTML($html);

// « I » : affiché dans le navigateur (le nom est utilisé si on l'enregistre)
$pdf->Output('contrat-pret-' . $loan->getId() . '.pdf', 'I');
