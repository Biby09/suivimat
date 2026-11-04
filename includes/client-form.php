<?php
/*
    Lecture et vérification du client choisi dans un dialog (sélecteur includes/client-picker.php) :
    utilisé par le prêt (includes/loan-form.php) et la vente (api/sale/sell-article.php).
    Prérequis : la fonction sendError(int $status, string $message) de l'API appelante.
    POST : client_id (ID d'un client, ou « new » avec client_firstname, client_lastname et client_email facultatif)

    Après l'inclusion, l'API dispose de $clientId, $newClient, $firstname, $lastname et $clientEmail
    (null si non renseigné), déjà vérifiés.
*/

$clientId = $_POST['client_id'] ?? '';
$newClient = $clientId === 'new';
$firstname = trim($_POST['client_firstname'] ?? '');
$lastname = trim($_POST['client_lastname'] ?? '');
$clientEmail = $newClient ? parseOptionalEmail($_POST['client_email'] ?? '') : null; // config/init.php

if ($newClient) {
    if ($firstname === '' || $lastname === '') {
        sendError(400, 'Le prénom et le nom du nouveau client sont requis');
    }
    if (mb_strlen($firstname) > 50 || mb_strlen($lastname) > 50) {
        sendError(400, 'Le prénom et le nom ne peuvent pas dépasser 50 caractères');
    }
    if ($clientEmail === false) {
        sendError(400, 'L\'adresse e-mail du nouveau client est invalide');
    }
} elseif (filter_var($clientId, FILTER_VALIDATE_INT) === false) {
    sendError(400, 'Choisissez un client');
}
