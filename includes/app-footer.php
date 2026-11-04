<?php
/*
    Footer de l'application (pages /dashboard) : une ligne discrète, à la place du grand footer du site public
    (includes/footer.php). Garde l'accès aux mentions légales et au contact depuis toutes les pages.
    APP_VERSION : config/init.php
*/
?>
<footer class="app-footer border-top bg-white py-3 mt-4">
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
