<?php
/*
    Footer de l'application (pages /dashboard) : une ligne discrète, à la place du grand footer du site public
    (includes/footer.php). Garde l'accès aux mentions légales et au contact depuis toutes les pages.
    Grand écran seulement : sur mobile et tablette, ces liens sont dans le panneau « Plus » de la barre du bas
    (includes/app-bottom-nav.php).
    APP_VERSION : config/init.php
*/
?>
<!-- Pas de classe mt-* : margin-top: auto (style.scss, règle « footer ») pousse le footer en bas des pages courtes ;
     une classe Bootstrap mt-* (aussi !important, mais plus spécifique) l'en empêcherait -->
<footer class="app-footer border-top bg-white py-3 d-none d-lg-block">
    <div class="container">
        <!-- Liste à puces « · » : les éléments passent à la ligne proprement sur un petit écran -->
        <ul class="list-inline small text-body-secondary text-center mb-0">
            <li class="list-inline-item">© <?= date('Y') ?> SuiviMat</li>
            <li class="list-inline-item">Version <?= htmlspecialchars(APP_VERSION) ?></li>
            <li class="list-inline-item"><a href="/contact" class="link-secondary">Contact</a></li>
            <li class="list-inline-item"><a href="/mentions-legales.php" class="link-secondary">Mentions légales</a></li>
            <li class="list-inline-item"><a href="https://github.com/Biby09/suivimat" class="link-secondary" target="_blank" rel="noopener noreferrer">GitHub</a></li>
        </ul>
    </div>
</footer>
