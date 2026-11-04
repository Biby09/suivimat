<?php
/*
    Lecture et vérification du client choisi dans un dialog (sélecteur includes/client-picker.php) :
    utilisé par le prêt (includes/loan-form.php) et la vente (api/sale/sell-article.php).
    Prérequis : la fonction sendError(int $status, string $message) de l'API appelante.
    POST : client_id (ID d'un client, ou « new » avec client_firstname et client_lastname)

    Après l'inclusion, l'API dispose de $clientId, $newClient, $firstname et $lastname (déjà vérifiés).
*/

$clientId = $_POST['client_id'] ?? '';
$newClient = $clientId === 'new';
$firstname = trim($_POST['client_firstname'] ?? '');
$lastname = trim($_POST['client_lastname'] ?? '');

if ($newClient) {
    if ($firstname === '' || $lastname === '') {
        sendError(400, 'Le prénom et le nom du nouveau client sont requis');
    }
    if (mb_strlen($firstname) > 50 || mb_strlen($lastname) > 50) {
        sendError(400, 'Le prénom et le nom ne peuvent pas dépasser 50 caractères');
    }
} elseif (filter_var($clientId, FILTER_VALIDATE_INT) === false) {
    sendError(400, 'Choisissez un client');
}
