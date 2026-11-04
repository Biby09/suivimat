<?php
// Exporte l'inventaire d'une organisation en CSV, lisible par Excel (page « Paramètres », section Export).
// GET : id (organisation)
// Une ligne par objet enregistré : code, nom, catégorie, statut, emprunteur et dates du prêt en cours, informations.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/article/articleManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loanManager.php';

try {
    $organisation = $organisationManager->getOrganisationById((int) ($_GET['id'] ?? 0));
} catch (Exception $e) {
    http_response_code(404);
    exit('Organisation introuvable');
}

$articleManager = new ArticleManager($mysqlClient, $organisation);
// Tous les objets enregistrés : sans filtre de catégorie, de statut ni de recherche, triés par nom
$articles = $articleManager->searchConfiguredArticles(null, false, null, [], 1000000, 0)['articles'];

// Chemin de chaque catégorie (« Tentes › Igloo ») : [id_catégorie => texte]
$categories = [];
foreach ((new CategoryManager($mysqlClient, $organisation))->getAllCategories() as $category) {
    $categories[$category->getId()] = $category;
}
$categoryLabel = function (?int $id) use ($categories): string {
    $names = [];
    while ($id !== null && isset($categories[$id]) && count($names) < 20) {
        array_unshift($names, $categories[$id]->getName());
        $id = $categories[$id]->getParentId();
    }
    return implode(' › ', $names);
};

// Prêt en cours de chaque objet : [id_objet => Loan]
$ongoingLoans = [];
foreach ((new LoanManager($mysqlClient, $organisation))->getOngoingLoans() as $loan) {
    $ongoingLoans[$loan->getArticleId()] = $loan;
}

// Nom du fichier : « inventaire-nom-organisation-2026-11-04.csv » (lettres, chiffres et tirets seulement)
$slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $organisation->getName()) ?: 'organisation')), '-');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventaire-' . $slug . '-' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
// BOM UTF-8 : sans lui, Excel afficherait mal les accents
fwrite($output, "\xEF\xBB\xBF");
// Point-virgule : séparateur attendu par Excel en Suisse et en France (la virgule y sert aux décimales)
$write = fn(array $row) => fputcsv($output, $row, ';', '"', '');

$write(['Code', 'Nom', 'Catégorie', 'Statut', 'Emprunté par', 'Depuis le', 'Retour prévu', 'Informations']);
foreach ($articles as $article) {
    $loan = $ongoingLoans[$article->getId()] ?? null;
    // Informations : « Couleur : Rouge | Capacité : 1l » (valeur par défaut de la catégorie si non remplie)
    $fields = array_map(fn(array $field) => $field['name'] . ' : ' . $field['value'], $articleManager->getDisplayedFields($article));
    $write([
        $article->getCode(),
        $article->getName(),
        $categoryLabel($article->getCategoryId()) ?: 'Sans catégorie',
        $article->getStatus(),
        $loan?->getClientName() ?? '',
        $loan ? formatDate($loan->getStartDate()) : '',
        $loan?->getDueDate() ? formatDate($loan->getDueDate()) : '',
        implode(' | ', $fields),
    ]);
}
fclose($output);
