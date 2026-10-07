<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

// Initialisation du PDO;

if ($_SERVER['SERVER_NAME'] === 'localhost') {
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

// Initialisation des managers principaux
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/userManager.php';
$userManager = new UserManager($mysqlClient);