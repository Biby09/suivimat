<?php
/*
    Modal « Scanner un objet » : accès rapide au scanner depuis l'inventaire.
    Prérequis : $organisation (Organisation) défini, et le modal includes/modals/article-setup-modal.php inclus sur la page.
    Ouverture : <button data-bs-toggle="modal" data-bs-target="#quick-scan-modal">
    La caméra s'allume à l'ouverture et s'éteint à la fermeture.

    Code lu (scripts/quick-scan-modal.js) :
    - objet déjà enregistré : ouverture de sa fiche (/dashboard/organisation/article/?article_id=…) ;
    - code pas encore enregistré : ce modal se ferme et le modal « Enregistrer des objets » s'ouvre avec ce code.
*/
?>
<!-- quick-scan-modal (classe) : sur mobile, la caméra occupe toute la hauteur et la saisie du code est en bas (style.scss) -->
<div class="modal fade quick-scan-modal" id="quick-scan-modal" tabindex="-1" aria-labelledby="quick-scan-modal-label" aria-hidden="true"
    data-organisation-id="<?= $organisation->getId() ?>">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="quick-scan-modal-label">Scanner un objet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <?php
                $scanner_id = 'quick-scan-scanner';
                include $_SERVER['DOCUMENT_ROOT'] . '/includes/scanner.php';
                ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= asset('/scripts/quick-scan-modal.js') ?>" defer></script>
