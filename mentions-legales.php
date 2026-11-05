<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$page_title = 'Mentions légales | SuiviMat';
$page_description = 'Mentions légales et protection des données de SuiviMat.';
?>
<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Mentions légales</h1>

                <h2 class="fs-4 mt-5">Éditeur du site</h2>
                <p>
                    SuiviMat est une application web réalisée par Florian Lovis dans le cadre d'un travail pratique
                    individuel
                    au <a href="https://www.cpnv.ch" target="_blank" rel="noopener noreferrer">Centre Professionnel du
                        Nord Vaudois (CPNV)</a>.
                </p>
                <ul>
                    <li>Responsable du site : Florian Lovis</li>
                    <li>Contact : webmaster@florianlovis.ch ou le <a href="/contact">formulaire de contact</a></li>
                </ul>

                <h2 class="fs-4 mt-5">Hébergement</h2>
                <p>
                    Le site est hébergé par un prestataire d'hébergement web tiers. Les coordonnées de l'hébergeur
                    peuvent être communiquées sur simple demande auprès de l'éditeur, à l'adresse de contact ci-dessus.
                </p>

                <h2 class="fs-4 mt-5">Protection des données</h2>
                <p>
                    Le traitement des données personnelles par SuiviMat est soumis au droit suisse, en particulier à la
                    <a href="https://www.fedlex.admin.ch/eli/cc/2022/491/fr" target="_blank" rel="noopener noreferrer">loi fédérale sur la protection des données (LPD)</a>
                    et à son ordonnance d'application (OPDo), en vigueur depuis le 1<sup>er</sup> septembre 2023.
                </p>
                <p>
                    Les données sont traitées uniquement pour faire fonctionner l'application : gérer les comptes, les organisations,
                    le matériel et ses prêts. Elles ne sont ni vendues, ni louées, ni utilisées à des fins publicitaires.
                </p>
                <p>
                    Le site et ses données sont hébergés en Suisse, sur les serveurs d'un hébergeur suisse (voir « Hébergement »).
                    Les données enregistrées dans l'application ne sont pas transférées à l'étranger. Seules exceptions : la vérification
                    anti-robot de la page d'inscription et la bibliothèque de lecture des codes QR, fournies par des services externes
                    (voir « Services externes »), ainsi que les e-mails envoyés par l'application (aux membres d'une organisation ou à ses
                    clients), qui sont remis aux fournisseurs de messagerie des destinataires.
                </p>

                <h3 class="fs-5 mt-4">Données enregistrées</h3>
                <ul>
                    <li><strong>Compte utilisateur</strong> : prénom, nom, adresse e-mail et mot de passe. Le mot de
                        passe n'est jamais
                        enregistré en clair : seule une empreinte chiffrée (hachage) est conservée.</li>
                    <li><strong>Organisations</strong> : nom de l'organisation et liste de ses membres.</li>
                    <li><strong>Matériel</strong> : nom, catégorie, informations complémentaires et photos des objets,
                        ainsi que l'historique
                        des prêts, retours, dégâts, ventes et pertes.</li>
                    <li><strong>Clients des organisations</strong> : prénom, nom et, facultativement, adresse e-mail des
                        personnes à qui le matériel est prêté ou vendu, saisis par les membres de l'organisation. L'adresse
                        e-mail sert uniquement à envoyer un rappel avant la date de retour prévue, si l'organisation a activé
                        cette option.</li>
                </ul>
                <p>
                    Les photos sont réduites et converties au format JPEG lors de l'envoi : leurs métadonnées, dont la
                    position GPS
                    éventuelle, sont supprimées.
                </p>

                <h3 class="fs-5 mt-4">Objets perdus</h3>
                <p>
                    Lorsqu'une personne trouve un objet perdu et scanne son code QR, elle peut écrire à l'organisation
                    propriétaire.
                    Son message, son adresse e-mail et, si elle les indique, son nom et son numéro de téléphone sont
                    transmis par e-mail
                    aux membres de l'organisation pour organiser le retour de l'objet. Ils ne sont pas enregistrés dans
                    l'application.
                </p>

                <h3 class="fs-5 mt-4">Cookies</h3>
                <p>
                    SuiviMat dépose un seul cookie, nécessaire au fonctionnement : le cookie de session, qui maintient
                    la connexion pendant 12 heures au maximum. Aucun cookie publicitaire ni outil de mesure d'audience n'est utilisé.
                </p>
                <p>
                    Sur la page d'inscription uniquement, le service Google reCAPTCHA (voir ci-dessous) peut déposer ses propres
                    cookies.
                </p>

                <!-- id : lien « En savoir plus » de la page d'inscription (auth/register/) -->
                <h3 class="fs-5 mt-4" id="services-externes">Services externes</h3>
                <p>
                    <strong>Google reCAPTCHA</strong> : pour empêcher la création automatique de comptes par des robots, la page
                    d'inscription utilise le service reCAPTCHA de Google Ireland Limited (Gordon House, Barrow Street, Dublin 4, Irlande).
                    Il est chargé uniquement sur cette page. Pour vérifier que l'inscription est faite par une personne, Google reçoit
                    notamment l'adresse IP de l'appareil, des informations sur le navigateur et sur l'utilisation de la page, et peut
                    déposer des cookies. Ces données peuvent être transférées aux États-Unis. Elles sont traitées par Google selon sa
                    <a href="https://policies.google.com/privacy?hl=fr" target="_blank" rel="noopener noreferrer">politique de confidentialité</a>
                    et ses <a href="https://policies.google.com/terms?hl=fr" target="_blank" rel="noopener noreferrer">conditions d'utilisation</a>.
                    Les autres pages, et en particulier l'application, n'utilisent pas reCAPTCHA.
                </p>
                <p>
                    <strong>jsDelivr</strong> : pour lire les codes QR, le navigateur utilise sa fonction intégrée lorsqu'elle existe.
                    Sinon, la bibliothèque jsQR est chargée depuis le service
                    <a href="https://www.jsdelivr.com" target="_blank" rel="noopener noreferrer">jsDelivr</a>,
                    qui reçoit alors l'adresse IP de l'appareil.
                </p>

                <h3 class="fs-5 mt-4">Durée de conservation et droits</h3>
                <p>
                    Les données sont conservées pendant la durée d'utilisation de l'application et peuvent être supprimées à tout moment par l'utilisateur.
                </p>
                <p>
                    Vous pouvez demander l'accès à vos données, leur correction ou leur suppression en contactant l'éditeur à l'adresse de contact ci-dessus.
                </p>

                <h2 class="fs-4 mt-5">Propriété intellectuelle</h2>
                <p>
                    Le code source de SuiviMat est disponible sur
                    <a href="https://github.com/Biby09/suivimat" target="_blank" rel="noopener noreferrer">GitHub</a>. Le code reste la propriété de l'éditeur.</p>
                </p>

                <p class="text-body-secondary small mt-5 mb-0">Dernière mise à jour : 04.11.2026
                </p>
            </div>
        </div>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
</body>

</html>