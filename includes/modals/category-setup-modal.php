<?php
/*
    Modal de gestion des catégories (liste, création, modification, suppression : voir includes/category-setup.php).
    Prérequis : $organisation (Organisation) doit être défini avant l'inclusion.
    Ouverture : <button data-bs-toggle="modal" data-bs-target="#category-setup-modal">
    Chaque changement émet « categories-changed » sur document.
*/
?>
<div class="modal fade" id="category-setup-modal" tabindex="-1" aria-labelledby="category-setup-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="category-setup-modal-label">Modifier les catégories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Cliquez sur le nom d'une catégorie pour la modifier ou la supprimer.</p>
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/category-setup.php'; ?>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Terminer</button>
            </div>
        </div>
    </div>
</div>
