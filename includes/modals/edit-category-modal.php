<?php
/*
    Modal de modification / suppression d'une catégorie.
    Inclus par includes/category-setup.php, qui le remplit et l'ouvre (scripts/category-setup.js).
*/
?>
<div class="modal fade" id="edit-category-modal" tabindex="-1" aria-labelledby="edit-category-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form data-role="edit-form" novalidate>
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6" id="edit-category-modal-label">Modifier la catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-category-name" class="form-label">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="edit-category-name" name="name" maxlength="50" required>
                        <div class="invalid-feedback">Le nom est requis.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-category-parent" class="form-label">Catégorie parente <span class="text-muted">(optionnel)</span></label>
                        <select class="form-select" id="edit-category-parent" name="parent_id">
                            <option value="">Aucune</option>
                        </select>
                    </div>

                    <fieldset class="mb-3">
                        <legend class="form-label fs-6 mb-1">Champs personnalisés</legend>
                        <p class="form-text mt-0">Supprimer un champ efface aussi les valeurs déjà saisies pour ce champ.</p>
                        <div class="d-flex flex-column gap-2 mb-2" data-role="edit-fields"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-role="edit-add-field">+ Ajouter un champ</button>
                    </fieldset>

                    <div data-role="edit-message" aria-live="polite"></div>

                    <div class="alert alert-danger mb-0" data-role="delete-confirm" hidden>
                        <p class="mb-2">Supprimer définitivement la catégorie « <strong data-role="delete-name"></strong> » et ses champs personnalisés ?</p>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-role="delete-cancel">Annuler</button>
                            <button type="button" class="btn btn-sm btn-danger" data-role="delete-confirm-button">Supprimer</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 justify-content-between">
                    <button type="button" class="btn btn-outline-danger" data-role="delete">Supprimer</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" data-role="edit-submit">Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
