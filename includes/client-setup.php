<?php
/*
    Liste et ajout des clients d'une organisation (les personnes qui empruntent le matériel).
    Prérequis : $organisation (Organisation) doit être défini avant l'inclusion.
    Après chaque ajout, l'événement « clients-changed » est émis sur document (detail.clients : liste à jour).
*/
?>
<div id="client-setup" data-organisation-id="<?= $organisation->getId() ?>">
    <div class="card mb-3">
        <div class="card-body">
            <h3 class="fs-6 mb-3">Nouveau client</h3>
            <form data-role="form" novalidate>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="client-setup-firstname" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="client-setup-firstname" name="firstname" maxlength="50" autocomplete="off" autocapitalize="words" required>
                        <div class="invalid-feedback">Le prénom est requis.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="client-setup-lastname" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="client-setup-lastname" name="lastname" maxlength="50" autocomplete="off" autocapitalize="words" required>
                        <div class="invalid-feedback">Le nom est requis.</div>
                    </div>
                </div>

                <div data-role="message" aria-live="polite"></div>

                <div class="d-grid d-sm-flex justify-content-sm-end">
                    <button type="submit" class="btn btn-primary btn-lg" data-role="submit">Ajouter le client</button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="fs-6 mb-0">Clients enregistrés</h3>
        <span class="badge text-bg-secondary" data-role="count">0</span>
    </div>
    <ul class="list-group" data-role="list" aria-live="polite"></ul>
    <p class="text-muted" data-role="empty" hidden>Aucun client pour le moment.</p>
</div>
<script src="/scripts/client-setup.js" defer></script>
