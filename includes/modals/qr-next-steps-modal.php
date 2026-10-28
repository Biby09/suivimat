<?php
/*
    Modal « Vos codes sont prêts » : marche à suivre affichée après la génération des codes QR.
    Paramètres optionnels, à définir avant l'inclusion :
        $qr_next_steps_url  : lien du bouton « Suivant » ; sans lien, « Suivant » ferme simplement le modal
        $qr_next_steps_hint : dernière étape de la marche à suivre
    La page renseigne le lien de secours vers le PDF : document.getElementById("qr-pdf-link").href = pdfUrl
*/
$qr_next_steps_url = $qr_next_steps_url ?? null;
$qr_next_steps_hint = $qr_next_steps_hint ?? 'Cliquez sur <strong>Suivant</strong> pour continuer.';
?>
<div class="modal fade" id="qr-next-steps-modal" tabindex="-1" aria-labelledby="qr-next-steps-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="qr-next-steps-modal-label">Vos codes sont prêts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Le PDF s'est ouvert dans un nouvel onglet. Les codes sont déjà réservés pour votre organisation.</p>
                <ol class="mb-3">
                    <li class="mb-2">Imprimez le PDF à <strong>taille réelle (100 %)</strong>, sans mise à l'échelle, pour que les étiquettes tombent bien sur votre papier.</li>
                    <li class="mb-2">Collez une étiquette sur chaque objet à suivre.</li>
                    <li><?= $qr_next_steps_hint ?></li>
                </ol>
                <p class="small text-muted mb-0">Le PDF ne s'est pas ouvert ? <a href="#" id="qr-pdf-link" target="_blank" rel="noopener">Ouvrir le PDF</a></p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <?php if ($qr_next_steps_url): ?>
                    <a class="btn btn-primary" href="<?= htmlspecialchars($qr_next_steps_url, ENT_QUOTES, 'UTF-8') ?>">Suivant</a>
                <?php else: ?>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Suivant</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
