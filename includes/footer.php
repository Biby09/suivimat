<?php
/*
    Footer du site public (accueil, à propos, contact, connexion, page d'un objet perdu, erreurs).
    L'application (/dashboard) utilise une version d'une ligne : includes/app-footer.php. APP_VERSION : config/init.php
*/
?>
<footer class="bg-primary text-white py-4">
    <div class="container">
        <div class="text-center">
            <div class="row text-start mb-5 mt-3">
                <div class="col-12 col-md d-flex flex-column justify-content-center align-items-start">
                    <a href="/"><img src="/medias/logo-blanc.svg" alt="SuiviMat" height="25"></a>
                </div>
                <div class="col-12 col-md footer-divider d-flex flex-column justify-content-center align-items-start">
                    <nav class="d-flex flex-column gap-2">
                        <a href="/" class="text-white">Accueil</a>
                        <a href="/about.php" class="text-white">A propos</a>
                        <a href="/contact" class="text-white">Contact</a>
                        <a href="/mentions-legales.php" class="text-white">Mentions légales</a>
                    </nav>

                </div>
                <div class="col-12 col-md footer-divider d-flex flex-column justify-content-center align-items-start">
                    <p class="mb-0">WebApp réalisée par Florian Lovis dans le cadre d'un travail pratique individuel à blanc au <a href="https://www.cpnv.ch" class="text-white" target="_blank"
                            rel="noopener noreferrer">Centre Professionnel du nord Vaudois (CPNV)</a>.</p>
                </div>
            </div>

            <p class="mb-0">© <?php echo date("Y"); ?> CPNV. Tous droits réservés.</p>

            <!-- « Mentions légales » est déjà dans la colonne de liens ci-dessus : pas de doublon ici -->
            <p class="small mb-0">
                Version <?= htmlspecialchars(APP_VERSION) ?> &middot;
                <a href="https://github.com/Biby09/suivimat" class="link-light" target="_blank" rel="noopener noreferrer" style="color: white">GitHub</a>
            </p>

        </div>
    </div>
</footer>
