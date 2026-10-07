<?php
/*
    Scanner de codes QR SuiviMat (caméra + saisie manuelle).
    Optionnel : $scanner_id (string) pour avoir plusieurs scanners sur la même page (défaut : « scanner »).
    Ne pas inclure à l'intérieur d'un <form> : la saisie manuelle contient son propre formulaire.

    Chaque code lu émet l'événement « code-scanned » sur le conteneur (il remonte jusqu'à document) :
        document.addEventListener("code-scanned", (e) => console.log(e.detail.code, e.detail.source));
    detail.source vaut « camera » ou « manual ».

    Contrôle depuis la page : document.getElementById("scanner").scanner.start() / .stop() / .pause() / .resume()
    La caméra ne fonctionne qu'en HTTPS (ou sur localhost).
*/
$scanner_id = $scanner_id ?? 'scanner';
?>
<div class="scanner card" id="<?= htmlspecialchars($scanner_id, ENT_QUOTES, 'UTF-8') ?>" style="scroll-margin-top: 6rem;" data-scanner>
    <div class="card-body">
        <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden mb-3 position-relative" data-role="viewport">
            <video class="w-100 h-100 object-fit-cover" data-role="video" playsinline muted hidden></video>
            <div class="d-flex flex-column align-items-center justify-content-center text-white text-center p-3" data-role="placeholder">
                <p class="mb-3">Scannez les codes QR avec la caméra de votre appareil.</p>
                <button type="button" class="btn btn-light btn-lg" data-role="start">Activer la caméra</button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <p class="small mb-0" data-role="status" aria-live="polite"></p>
            <button type="button" class="btn btn-outline-secondary" data-role="stop" hidden>Arrêter la caméra</button>
        </div>

        <form class="d-flex gap-2" data-role="manual-form" novalidate>
            <!-- font-size ≥ 16px (form-control) : évite le zoom automatique sur iPhone -->
            <label class="visually-hidden" for="<?= htmlspecialchars($scanner_id, ENT_QUOTES, 'UTF-8') ?>-manual">Code de l'objet</label>
            <input type="text" class="form-control text-uppercase font-monospace" id="<?= htmlspecialchars($scanner_id, ENT_QUOTES, 'UTF-8') ?>-manual"
                placeholder="Code à 4 caractères" maxlength="4" autocomplete="off" autocapitalize="characters" spellcheck="false" data-role="manual-input">
            <button type="submit" class="btn btn-primary text-nowrap">Valider</button>
        </form>
    </div>
</div>
<script src="/scripts/scanner.js" defer></script>
