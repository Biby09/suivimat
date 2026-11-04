<?php
/*
    Lecture et vérification des champs du dialog de prêt, communs à api/loan/create-loan.php et api/loan/update-loan.php.
    Prérequis : la fonction sendError(int $status, string $message) de l'API appelante.
    POST : client_id (ID d'un client, ou « new » avec client_firstname et client_lastname),
           start_date (AAAA-MM-JJ), due_date (AAAA-MM-JJ, optionnel : vide = retour indéterminé)

    include partage les variables avec le fichier qui l'inclut : après l'inclusion, l'API dispose de
    $clientId, $newClient, $firstname, $lastname, $startDate et $dueDate (déjà vérifiés).
*/

// Client : $clientId, $newClient, $firstname, $lastname
require $_SERVER['DOCUMENT_ROOT'] . '/includes/client-form.php';

$startDate = parseDate(trim($_POST['start_date'] ?? '')); // config/init.php
$dueText = trim($_POST['due_date'] ?? '');
$dueDate = $dueText === '' ? null : parseDate($dueText);

if ($startDate === null) {
    sendError(400, 'La date de sortie est invalide');
}
if ($dueText !== '' && $dueDate === null) {
    sendError(400, 'La date de retour prévue est invalide');
}
if ($dueDate !== null && $dueDate < $startDate) {
    sendError(400, 'La date de retour prévue ne peut pas être avant la date de sortie');
}
