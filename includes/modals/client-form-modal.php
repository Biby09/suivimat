<?php
/*
    Dialog de création ou de modification d'un client (prénom, nom, e-mail facultatif) ; comportement : scripts/client-form-modal.js.
    Variables à définir avant l'inclusion :
    - $client_form_mode : 'create' (page Clients, ouvre ensuite la page du nouveau client) ou 'edit' (page d'un client) ;
    - $organisation (mode create) ou $client (mode edit, Client dont les valeurs pré-remplissent le formulaire).
    Ouverture : <button data-bs-toggle="modal" data-bs-target="#client-form-modal">
*/
$isEdit = $client_form_mode === 'edit';
?>
<div class="modal fade" id="client-form-modal" tabindex="-1" aria-labelledby="client-form-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form class="modal-content" novalidate data-role="client-form"
            data-url="<?= $isEdit ? '/api/client/update-client.php' : '/api/client/create-client.php' ?>" data-after="<?= $isEdit ? 'reload' : 'open' ?>">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="client-form-modal-label"><?= $isEdit ? 'Modifier le client' : 'Nouveau client' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div data-role="message" aria-live="polite"></div>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="client_id" value="<?= $client->getId() ?>">
                <?php else: ?>
                    <input type="hidden" name="organisation_id" value="<?= $organisation->getId() ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="client-form-firstname" class="form-label">Prénom</label>
                        <!-- value : valeur d'origine, que form.reset() remet à chaque ouverture -->
                        <input type="text" class="form-control" id="client-form-firstname" name="firstname" maxlength="50" required autocomplete="off"
                            value="<?= $isEdit ? htmlspecialchars($client->getFirstname()) : '' ?>">
                        <div class="invalid-feedback">Le prénom est requis.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="client-form-lastname" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="client-form-lastname" name="lastname" maxlength="50" required autocomplete="off"
                            value="<?= $isEdit ? htmlspecialchars($client->getLastname()) : '' ?>">
                        <div class="invalid-feedback">Le nom est requis.</div>
                    </div>
                    <div class="col-12">
                        <label for="client-form-email" class="form-label">E-mail <span class="text-muted">(facultatif)</span></label>
                        <input type="email" class="form-control" id="client-form-email" name="email" maxlength="100" autocomplete="off"
                            value="<?= $isEdit ? htmlspecialchars($client->getEmail() ?? '') : '' ?>">
                        <div class="invalid-feedback">L'adresse e-mail n'est pas valide.</div>
                        <div class="form-text">Pour contacter le client, par exemple en cas de retard.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary" data-role="save"><?= $isEdit ? 'Enregistrer' : 'Ajouter le client' ?></button>
            </div>
        </form>
    </div>
</div>
<script src="<?= asset('/scripts/client-form-modal.js') ?>" defer></script>
