<main>
    <section class="py-5">
        <div class="container">
            <h1>Bienvenue dans votre organisation</h1>

            <p class="lead">Votre organisation est créée. Quelques étapes vont vous permettre de la configurer.</p>

            <h2 class="fs-4 mt-4">Étape 1 : étiqueter votre matériel</h2>
            <p>Chaque objet de votre organisation est identifié par un code QR unique. Imprimez ces codes, puis collez-en un sur chaque objet : il suffira ensuite de le scanner pour retrouver l'objet dans SuiviMat.</p>
            <p>Sous chaque code QR figure aussi un code court. Il permet d'identifier l'objet sans scanner, ou de le noter à la main s'il n'est pas possible d'y coller une étiquette.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#download-qr-modal">Télécharger les codes QR</button>
            <a href="/dashboard/organisation/init-orga.php?id=<?= $organisation->getId() ?>&page=2" class="btn btn-outline-secondary">Plus tard</a>
        </div>
    </section>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/download-qr-modal.php'; ?>

    <!-- Modal marche à suivre, affiché après la génération du PDF -->
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
                        <li>Cliquez sur <strong>Suivant</strong> pour continuer la configuration de votre organisation.</li>
                    </ol>
                    <p class="small text-muted mb-0">Le PDF ne s'est pas ouvert ? <a href="#" id="qr-pdf-link" target="_blank" rel="noopener">Ouvrir le PDF</a></p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    <a class="btn btn-primary" href="/dashboard/organisation/init-orga.php?id=<?= $organisation->getId() ?>&amp;page=2">Suivant</a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Après la génération des codes, la page d'initialisation affiche la marche à suivre
    document.addEventListener("qr-codes-generated", (e) => {
        e.preventDefault();
        document.getElementById("qr-pdf-link").href = e.detail.pdfUrl;
        bootstrap.Modal.getOrCreateInstance(document.getElementById("qr-next-steps-modal")).show();
    });
</script>
