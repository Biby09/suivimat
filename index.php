<?php
$page_title = 'SuiviMat | Accueil';
$page_description = 'Bienvenue sur SuiviMat, votre application de suivi de matériel de prêt.';
?>

<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/header.php'; ?>

    <main style="margin-top: 0 !important;">
        <!-- HERO -->
        <section class="bg-gradient-primary text-white py-5 text-center">
            <div class="container">
                <h1 class="mb-4">Le suivi de votre matériel de prêt, rapidement.</h1>
                <p class="lead mb-4">Inscrivez votre matériel, enregistrez les prêts et suivez chaque objet dans un espace partagé avec votre organisation.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="d-flex gap-2 justify-content-center">
                        <a class="btn btn-light" href="/register">Créer un compte</a>
                        <a class="btn btn-outline-light" href="/about.php">En savoir plus</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2 justify-content-center">
                        <a class="btn btn-light" href="/dashboard">Mon espace</a>
                        <a class="btn btn-outline-light" href="/about.php">En savoir plus</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- AVANTAGES -->
        <section class="homepage-features py-5">
            <div class="container">
                <h2 class="text-center mb-5">Pourquoi utiliser cette plateforme ?</h2>
                <div class="row">
                    <article class="homepage-feature col-6 col-md-3">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <h3>Clarté</h3>
                        <p>Retrouvez tout votre inventaire, ses statuts et son historique depuis un seul tableau de bord.
                        </p>
                    </article>
                    <article class="homepage-feature col-6 col-md-3">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <polyline points="12 7 12 12 15 14"></polyline>
                            </svg>
                        </div>
                        <h3>Rapidité</h3>
                        <p>Scanner le code QR d’un objet pour l’indentifier et enregistrer un prêt en quelques secondes.</p>
                    </article>
                    <article class="homepage-feature col-6 col-md-3">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 9l9-5 9 5-9 5-9-5z"></path>
                                <path d="M3 9v8l9 5 9-5V9"></path>
                                <path d="M12 14v8"></path>
                            </svg>
                        </div>
                        <h3>Organisation</h3>
                        <p>Classez vos objets par catégories, avec prix et photo de référence pour chaque type de matériel.</p>
                    </article>
                    <article class="homepage-feature col-6 col-md-3">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"></path>
                                <path d="M9 12l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h3>Simplicité</h3>
                        <p>Une interface intuitive pour gérer votre matériel simplement, sans perte de temps.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- FONCTIONNALITES -->
        <section class="py-5 bg-light text-center">
            <div class="container">
                <h2 class="mb-5">Ce que vous pouvez faire</h2>
                <ul class="list-unstyled d-flex flex-wrap justify-content-center gap-4">
                    <li class="flex-grow-1 bg-white py-2 px-2 rounded">Enregistrer un prêt et son retour</li>
                    <li class="flex-grow-1 bg-white py-2 px-2 rounded">Accèder aux données d'un objet via son code QR</li>
                    <li class="flex-grow-1 bg-white py-2 px-2 rounded">Enregistrer les dégâts et enregistrer des photos</li>
                    <li class="flex-grow-1 bg-white py-2 px-2 rounded">Générer un contrat de prêt en PDF</li>
                    <li class="flex-grow-1 bg-white py-2 px-2 rounded">Suivre le statut de chaque objet</li>
                    <li class="flex-grow-1 bg-white py-2 px-2 rounded">Organiser votre matériel par catégories</li>
                </ul>
            </div>
        </section>

        <!-- POUR QUI -->
        <section class="py-5 text-center">
            <div class="container">
                <h2 class="mb-4">Pour qui ?</h2>
                <p class="lead mb-5">La plateforme s’adapte aux organisations qui souhaitent gérer leur matériel de prêt facilement :</p>
                <ul class="list-unstyled d-flex flex-column flex-md-row justify-content-center gap-4">
                    <li class="flex-grow-1 p-2 border border-primary rounded">Associations sportives</li>
                    <li class="flex-grow-1 p-2 border border-primary rounded">Groupe de jeunes</li>
                    <li class="flex-grow-1 p-2 border border-primary rounded">Associations étudiantes</li>
                    <li class="flex-grow-1 p-2 border border-primary rounded">Sociétés locales</li>
                    <li class="flex-grow-1 p-2 border border-primary rounded">Clubs et organisations diverses</li>
                    <li class="flex-grow-1 p-2 border border-primary rounded">Écoles</li>
                </ul>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="py-5 bg-primary text-white text-center">
            <div class="container">
                <h2 class="mb-4">Commencez dès aujourd'hui</h2>
                <p class="lead mb-4">Simplifiez la gestion du matériel de votre association en quelques clics.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-light btn-lg" href="/register">Créer un compte</a>
                <?php else: ?>
                    <a class="btn btn-light btn-lg" href="/dashboard">Mon espace</a>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>