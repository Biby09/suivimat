<?php
/*
    Liste, création, modification et suppression des catégories d'une organisation, avec leurs champs personnalisés.
    Un clic sur le nom d'une catégorie ouvre le modal de modification.
    Prérequis : $organisation (Organisation) doit être défini avant l'inclusion.
    Après chaque création, modification ou suppression, l'événement « categories-changed » est émis sur document
    (detail.categories : liste à jour), par exemple pour recharger un formulaire d'objet.
*/
?>
<div id="category-setup" data-organisation-id="<?= $organisation->getId() ?>">
    <ul class="list-group mb-3" data-role="list" aria-live="polite"></ul>
    <p class="text-muted" data-role="empty" hidden>Aucune catégorie pour le moment.</p>

    <div class="card">
        <div class="card-body">
            <h3 class="fs-6 mb-3">Nouvelle catégorie</h3>
            <form data-role="form" novalidate>
                <div class="mb-3">
                    <label for="category-setup-name" class="form-label">Nom de la catégorie</label>
                    <input type="text" class="form-control" id="category-setup-name" name="name" maxlength="50" required placeholder="Ex. Tentes">
                    <div class="invalid-feedback">Le nom est requis.</div>
                </div>

                <div class="mb-3">
                    <label for="category-setup-parent" class="form-label">Catégorie parente <span class="text-muted">(optionnel)</span></label>
                    <select class="form-select" id="category-setup-parent" name="parent_id">
                        <option value="">Aucune</option>
                    </select>
                </div>

                <fieldset class="mb-3">
                    <legend class="form-label fs-6 mb-1">Champs personnalisés <span class="text-muted">(optionnel)</span></legend>
                    <p class="form-text mt-0">Informations à renseigner pour chaque objet de cette catégorie. Ex. « Nombre de places », « Taille ».</p>
                    <div class="d-flex flex-column gap-2 mb-2" data-role="fields"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-role="add-field">+ Ajouter un champ</button>
                </fieldset>

                <div data-role="message" aria-live="polite"></div>

                <div class="d-grid d-sm-flex justify-content-sm-end">
                    <button type="submit" class="btn btn-primary btn-lg" data-role="submit">Créer la catégorie</button>
                </div>
            </form>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/edit-category-modal.php'; ?>
</div>
<script src="/scripts/category-setup.js" defer></script>
