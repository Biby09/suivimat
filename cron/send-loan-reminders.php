<?php
/*
    Tâche planifiée (cron) à lancer une fois par jour : rappel par e-mail au client 2 jours avant la date de retour prévue.
    Envoyé seulement si l'organisation a activé les rappels (Paramètres › Prêts) et si le client a une adresse e-mail ;
    un seul rappel par prêt (loans.lo_reminder_sent_at), renvoyé si la date de retour change (LoanManager::updateLoan).

    Lancement (ligne de commande uniquement, jamais depuis le navigateur : le dossier cron/ est bloqué par son .htaccess) :
        php cron/send-loan-reminders.php
    En local, les e-mails sont écrits dans le journal au lieu d'être envoyés (sendMail(), config/init.php) :
        SERVER_NAME=localhost php cron/send-loan-reminders.php
    Exemple de configuration cron chez l'hébergeur (tous les jours à 8 h) :
        0 8 * * * cd /chemin/du/site && php cron/send-loan-reminders.php
    En production, les identifiants de la base doivent être disponibles en variables d'environnement (DB_HOST, DB_NAME…),
    comme pour le site (config/init.php).
*/

// PHP_SAPI vaut « cli » en ligne de commande : sécurité supplémentaire si le .htaccess n'était pas appliqué
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script se lance en ligne de commande.');
}

// En ligne de commande, il n'y a pas de serveur web : DOCUMENT_ROOT est fixé à la racine du site (dossier parent de cron/)
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

const REMINDER_DAYS = 2;

// Prêts en cours dont le retour prévu tombe dans les 2 prochains jours, sans rappel envoyé.
// « entre aujourd'hui et dans 2 jours » (et pas « dans exactement 2 jours ») : si la tâche n'a pas tourné un jour,
// le rappel part quand même le lendemain.
$stmt = $mysqlClient->prepare('SELECT loans.id_loan, loans.lo_due_date, clients.cli_firstname, clients.cli_email,
        articles.art_name, articles.art_code, organisations.id_organisation, organisations.org_name
    FROM loans
    JOIN articles ON articles.id_article = loans.idx_article
    JOIN clients ON clients.id_client = loans.idx_client
    JOIN organisations ON organisations.id_organisation = articles.idx_organisation
    WHERE loans.lo_end_date IS NULL
        AND loans.lo_reminder_sent_at IS NULL
        AND loans.lo_due_date BETWEEN CURDATE() AND CURDATE() + INTERVAL ' . REMINDER_DAYS . ' DAY
        AND organisations.org_reminders_enabled = 1
        AND clients.cli_email IS NOT NULL');
$stmt->execute();
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$markSent = $mysqlClient->prepare('UPDATE loans SET lo_reminder_sent_at = NOW() WHERE id_loan = :loan');
$sent = 0;

foreach ($loans as $loan) {
    // « Répondre » écrit aux membres de l'organisation (plusieurs adresses séparées par des virgules)
    $replyTo = implode(', ', $userManager->getEmailsOfOrganisation((int) $loan['id_organisation']));
    $body = "Bonjour " . $loan['cli_firstname'] . ",\n\n"
        . "Petit rappel : l'objet « " . $loan['art_name'] . " » (" . $loan['art_code'] . ") prêté par " . $loan['org_name']
        . " est à rendre le " . formatDate($loan['lo_due_date']) . ".\n\n"
        . "Si vous avez besoin de le garder plus longtemps, répondez simplement à cet e-mail.\n\n"
        . "Merci et à bientôt,\n" . $loan['org_name'] . "\n";

    if (sendMail($loan['cli_email'], 'Rappel : retour de « ' . $loan['art_name'] . ' » le ' . formatDate($loan['lo_due_date']), $body, $replyTo ?: null)) {
        $markSent->execute(['loan' => $loan['id_loan']]);
        $sent++;
    } else {
        error_log('Rappel non envoyé pour le prêt ' . $loan['id_loan']);
    }
}

echo date('Y-m-d H:i') . ' : ' . $sent . ' rappel(s) envoyé(s) sur ' . count($loans) . " prêt(s) à rappeler\n";
