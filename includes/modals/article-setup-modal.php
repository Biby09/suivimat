<?php
/*
    Modal d'enregistrement des objets (scanner + formulaire, voir includes/article-setup.php).
    Prérequis : $organisation (Organisation) doit être défini avant l'inclusion.
    Ouverture : <button data-bs-toggle="modal" data-bs-target="#article-setup-modal">
    La caméra est coupée à la fermeture du modal.

    Le lien « Télécharger de nouveaux codes QR » enchaîne, comme à la première étape de l'initialisation :
    modal de téléchargement → marche à suivre → retour à ce modal pour scanner les nouveaux codes.
    Le bouton crayon à côté de la liste des catégories ouvre de la même façon le modal des catégories
    (includes/modals/category-setup-modal.php), puis revient ici ; la liste des catégories du formulaire est mise à jour.
    Bootstrap n'empile pas les modals : chacun est ouvert quand le précédent est fermé.
*/
?>
<div class="modal fade" id="article-setup-modal" tabindex="-1" aria-labelledby="article-setup-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="article-setup-modal-label">Enregistrer des objets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <?php
                $article_setup_categories_button = true;
                include $_SERVER['DOCUMENT_ROOT'] . '/includes/article-setup.php';
                ?>
            </div>
            <div class="modal-footer py-2 justify-content-between">
                <p class="small mb-0">
                    Plus de codes disponibles ?
                    <a href="#" data-role="open-download-qr">Télécharger de nouveaux codes QR</a>
                </p>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Terminer</button>
            </div>
        </div>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/download-qr-modal.php';

$qr_next_steps_url = null;
$qr_next_steps_hint = 'Cliquez sur <strong>Suivant</strong> pour revenir à l\'enregistrement et scanner vos nouveaux codes.';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/qr-next-steps-modal.php';

// include_once : la page peut aussi l'inclure pour son propre bouton
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/category-setup-modal.php';
?>

<script>
    (() => {
        const setupModal = document.getElementById("article-setup-modal");
        const downloadModal = document.getElementById("download-qr-modal");
        const nextStepsModal = document.getElementById("qr-next-steps-modal");
        const categoriesModal = document.getElementById("category-setup-modal");
        const show = (element) => bootstrap.Modal.getOrCreateInstance(element).show();

        // Vrai pendant l'enchaînement lancé depuis ce modal ; les codes générés ouvrent la marche à suivre
        let qrFlow = false;
        let generated = false;
        // Vrai pendant l'aller-retour vers le modal des catégories
        let categoriesFlow = false;

        setupModal.addEventListener("hidden.bs.modal", () => {
            setupModal.querySelector("[data-scanner]").scanner.stop();
            if (qrFlow) {
                show(downloadModal);
            } else if (categoriesFlow) {
                show(categoriesModal);
            }
        });

        setupModal.querySelector("[data-role='edit-categories']").addEventListener("click", () => {
            categoriesFlow = true;
            bootstrap.Modal.getInstance(setupModal).hide();
        });

        // Ignoré quand le modal des catégories se ferme le temps de modifier une catégorie (scripts/category-setup.js)
        categoriesModal.addEventListener("hidden.bs.modal", () => {
            if (categoriesFlow && !categoriesModal.dataset.editing) {
                categoriesFlow = false;
                show(setupModal);
            }
        });

        setupModal.querySelector("[data-role='open-download-qr']").addEventListener("click", (e) => {
            e.preventDefault();
            qrFlow = true;
            generated = false;
            bootstrap.Modal.getInstance(setupModal).hide();
        });

        document.addEventListener("qr-codes-generated", (e) => {
            if (!qrFlow) {
                return;
            }
            // download-qr-modal.js ferme alors le modal de téléchargement
            e.preventDefault();
            generated = true;
            document.getElementById("qr-pdf-link").href = e.detail.pdfUrl;
        });

        // Annulé : retour direct à l'enregistrement ; codes générés : marche à suivre d'abord
        downloadModal.addEventListener("hidden.bs.modal", () => {
            if (qrFlow) {
                show(generated ? nextStepsModal : setupModal);
                qrFlow = generated;
            }
        });

        nextStepsModal.addEventListener("hidden.bs.modal", () => {
            if (qrFlow) {
                qrFlow = false;
                show(setupModal);
            }
        });
    })();
</script>
