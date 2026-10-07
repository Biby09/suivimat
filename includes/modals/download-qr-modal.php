<?php
/*
    Modal de téléchargement des codes QR d'une organisation.
    Prérequis : $organisation (Organisation) doit être défini avant l'inclusion.
    Ouverture : <button data-bs-toggle="modal" data-bs-target="#download-qr-modal">

    Après la génération, l'événement « qr-codes-generated » (detail.pdfUrl) est émis.
    Si la page l'intercepte avec event.preventDefault(), le modal se ferme et la page gère la suite ;
    sinon, le modal reste ouvert et affiche un lien vers le PDF.
*/
?>
<!-- Modal paramètres de téléchargement des codes QR -->
<div class="modal fade" id="download-qr-modal" tabindex="-1" aria-labelledby="download-qr-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="download-qr-modal-label">Téléchargement des codes QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="download-qr-form" method="post" action="/api/organisation/generate-qr-codes.php">
                    <input type="hidden" name="organisation_id" value="<?= $organisation->getId() ?>">

                    <fieldset class="mb-4">
                        <legend class="fs-6 fw-bold">Type de téléchargement</legend>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="export_type" id="export-labels" value="labels" checked>
                            <label class="form-check-label" for="export-labels">Planches d'étiquettes à imprimer (PDF)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="export_type" id="export-codes" value="codes">
                            <label class="form-check-label" for="export-codes">Codes uniquement</label>
                        </div>
                    </fieldset>

                    <div id="labels-settings">
                        <div class="alert alert-info small">
                            Les planches sont prévues pour être imprimées sur du <strong>papier autocollant prédécoupé</strong>.
                            Indiquez le format de vos feuilles et le nombre d'étiquettes qu'elles contiennent.
                            Si vous n'avez pas de papier autocollant, choisissez « Codes uniquement » pour obtenir la liste des codes uniques de chaque objet.
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label for="sheet-format" class="form-label">Format de la feuille</label>
                                <select class="form-select" id="sheet-format" name="sheet_format">
                                    <option value="A4" data-width="210" data-height="297" selected>A4 (210 × 297 mm)</option>
                                    <option value="A5" data-width="148" data-height="210">A5 (148 × 210 mm)</option>
                                    <option value="Letter" data-width="216" data-height="279">Letter (216 × 279 mm)</option>
                                    <option value="custom">Personnalisé</option>
                                </select>
                            </div>
                            <div class="col-6" id="custom-size-width" hidden>
                                <label for="sheet-width" class="form-label">Largeur (mm)</label>
                                <input type="number" class="form-control" id="sheet-width" name="sheet_width" min="50" max="500" value="210">
                            </div>
                            <div class="col-6" id="custom-size-height" hidden>
                                <label for="sheet-height" class="form-label">Hauteur (mm)</label>
                                <input type="number" class="form-control" id="sheet-height" name="sheet_height" min="50" max="500" value="297">
                            </div>
                            <div class="col-6">
                                <label for="label-columns" class="form-label">Étiquettes par ligne (colonnes)</label>
                                <input type="number" class="form-control" id="label-columns" name="label_columns" min="1" max="10" value="3" required>
                            </div>
                            <div class="col-6">
                                <label for="label-rows" class="form-label">Étiquettes par colonne (lignes)</label>
                                <input type="number" class="form-control" id="label-rows" name="label_rows" min="1" max="20" value="6" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="object-count" class="form-label">Nombre d'objets à étiqueter</label>
                        <input type="number" class="form-control" id="object-count" name="object_count" min="18" max="1000" step="18" value="18" required aria-describedby="object-count-help">
                        <div id="object-count-help" class="form-text"></div>
                    </div>

                    <div id="download-qr-message" aria-live="polite"></div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary" form="download-qr-form" id="download-qr-submit">Télécharger</button>
            </div>
        </div>
    </div>
</div>

<script src="/scripts/download-qr-modal.js" defer></script>
