<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login/?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitationManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisationManager.php';

$user = $userManager->getUserById($_SESSION['user_id']);
$invitationManager = new InvitationManager($mysqlClient, $user);
$organisationManager = new OrganisationManager($mysqlClient, $user);