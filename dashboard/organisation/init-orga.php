<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('ID d\'organisation manquant ou invalide.');
}

if (!isset($_GET['page']) || !is_numeric($_GET['page'])) {
    $_GET['page'] = 1; // Page par défaut
}

/* Déroulement de l'initialisation de l'organisation :

    Page 1
        Création de code QR pour chaque objet de l'organisation
    Page 2
        Création des catégories et des champs personnalisés pour chaque catégorie
    Page 3
        Enregistrement des objets (scan des codes QR)
    Page 4
        Ajout des clients à l'organisation
*/
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_description = "Initialisation de l'organisation";
$page_title = "Initialisation de l'organisation";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';

try{
    $organisation = $organisationManager->getOrganisationById($_GET['id']);
} catch (Exception $e) {
    http_response_code(404);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
    exit;
}
?>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';


if ($_GET['page'] == 1) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/dashboard/organisation/init-orga/page1.php';
} elseif ($_GET['page'] == 2) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/dashboard/organisation/init-orga/page2.php';
} elseif ($_GET['page'] == 3) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/dashboard/organisation/init-orga/page3.php';
} elseif ($_GET['page'] == 4) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/dashboard/organisation/init-orga/page4.php';
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Page d\'initialisation invalide']);
    exit;
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php';
?>

</body>
</html>