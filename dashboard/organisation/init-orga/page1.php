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

    <?php
    // Marche à suivre, affichée après la génération du PDF
    $qr_next_steps_url = '/dashboard/organisation/init-orga.php?id=' . $organisation->getId() . '&page=2';
    $qr_next_steps_hint = 'Cliquez sur <strong>Suivant</strong> pour continuer la configuration de votre organisation.';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/qr-next-steps-modal.php';
    ?>
</main>

<script>
    // Après la génération des codes, la page d'initialisation affiche la marche à suivre
    document.addEventListener("qr-codes-generated", (e) => {
        e.preventDefault();
        document.getElementById("qr-pdf-link").href = e.detail.pdfUrl;
        bootstrap.Modal.getOrCreateInstance(document.getElementById("qr-next-steps-modal")).show();
    });
</script>
