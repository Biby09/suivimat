<?php
/*
    Choix d'un client dans un formulaire : liste des clients + « + Nouveau client » (prénom et nom affichés à la demande).
    Utilisé par les dialogs de prêt et de vente de la fiche d'un objet ; comportement : scripts/client-picker.js.
    Champs envoyés : client_id (ID ou « new »), client_firstname, client_lastname (lus par includes/client-form.php).

    Variables à définir avant l'inclusion :
    - $clients : Client[] de l'organisation ;
    - $client_picker_id : préfixe des id HTML (ex. « loan » → loan-client, loan-client-firstname…) ;
    - $client_picker_label (optionnel) : libellé de la liste (défaut « Client »).
*/
$client_picker_label = $client_picker_label ?? 'Client';
$prefix = htmlspecialchars($client_picker_id);
?>
<div class="mb-3">
    <label for="<?= $prefix ?>-client" class="form-label"><?= htmlspecialchars($client_picker_label) ?></label>
    <select class="form-select" id="<?= $prefix ?>-client" name="client_id" required>
        <option value="">Choisir un client…</option>
        <?php foreach ($clients as $client): ?>
            <option value="<?= $client->getId() ?>"><?= htmlspecialchars($client->getLastname() . ' ' . $client->getFirstname()) ?></option>
        <?php endforeach; ?>
        <option value="new">+ Nouveau client</option>
    </select>
    <div class="invalid-feedback">Choisissez un client ou ajoutez-en un nouveau.</div>
</div>

<!-- Affiché seulement avec « + Nouveau client » (scripts/client-picker.js) -->
<fieldset class="border rounded p-3 mb-3" data-role="new-client" hidden>
    <legend class="fs-6 fw-semibold float-none w-auto px-1 mb-0">Nouveau client</legend>
    <div class="row g-2">
        <div class="col-sm-6">
            <label for="<?= $prefix ?>-client-firstname" class="form-label">Prénom</label>
            <input type="text" class="form-control" id="<?= $prefix ?>-client-firstname" name="client_firstname" maxlength="50" autocomplete="off">
            <div class="invalid-feedback">Le prénom est requis.</div>
        </div>
        <div class="col-sm-6">
            <label for="<?= $prefix ?>-client-lastname" class="form-label">Nom</label>
            <input type="text" class="form-control" id="<?= $prefix ?>-client-lastname" name="client_lastname" maxlength="50" autocomplete="off">
            <div class="invalid-feedback">Le nom est requis.</div>
        </div>
    </div>
</fieldset>
