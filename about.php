<?php
$page_title = 'Gestion du matériel pour associations | SuiviMat';
$page_description = 'Découvrez comment SuiviMat aide les associations à gérer le matériel, les prêts et les retours.';
?>
<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';

?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main style="margin-top: 0 !important;">
        <!-- HERO ABOUT -->
        <section class="bg-primary text-white py-5 text-center bg-gradient-primary">
            <div class="container">
                <h1 class="mb-4">Une gestion du matériel pensée pour les organisations</h1>
                <p class="lead">SuiviMat suit votre matériel, enregistre les prêts et retours, et centralise les informations pour une gestion efficace.</p>
            </div>
        </section>

        <!-- HISTOIRE -->
        <section class="py-5">
            <div class="container text-center">
                <h2 class="mb-4">Pourquoi ce site existe</h2>
                <p class="lead mb-3">La gestion du matériel au sein des associations peut être complexe, impliquant plusieurs acteurs et de nombreux objets à suivre.</p>
                <p class="lead">SuiviMat centralise toutes les informations relatives au matériel, aux prêts et aux retours, facilitant ainsi la gestion pour toutes les parties impliquées.</p>
            </div>
        </section>

        <!-- FONCTIONNALITES -->
        <section class="py-5 bg-light">
            <div class="container">
                        <h2 class="text-center mb-5">Une plateforme qui vous soutient à chaque étape</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Ajouter et étiqueter votre matériel</h3>
                                <p class="card-text">Ajoutez facilement votre matériel à la plateforme et étiquetez-le à l'aide des code QR imprimables depuis l'application pour un suivi précis et efficace.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Gérer les prêts et retours</h3>
                                <p class="card-text">Enregistrez rapidement les prêts et les retours de votre matériel, en gardant une trace précise de chaque emprunt.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Enregistrer des dégâts</h3>
                                <p class="card-text">Documentez les dommages subis par le matériel et ajoutez des photos pour un suivi précis.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Générer des contrats de prêt</h3>
                                <p class="card-text">Générez facilement des contrats de prêt en PDF pour formaliser les emprunts de votre matériel.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- VISION -->
        <section class="py-5 text-center">
            <div class="container">
                <h2 class="mb-4">Notre objectif</h2>
                <p class="lead">Faciliter la gestion du matériel au sein des associations en offrant un suivi clair et structuré des prêts, retours et dommages.</p>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-5 bg-primary text-white text-center">
            <div class="container">
                <h2 class="mb-4">Prêt à simplifier la gestion de votre association ?</h2>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-light btn-lg" href="/register">Créer un compte</a>
                <?php else: ?>
                    <a class="btn btn-light btn-lg" href="/dashboard">Mon espace</a>
                <?php endif; ?>
            </div>
        </section>

    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
</body>

</html>