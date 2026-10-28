<main>
    <section class="py-5">
        <div class="container">
            <p class="text-muted mb-1">Initialisation de « <?= htmlspecialchars($organisation->getName(), ENT_QUOTES, 'UTF-8') ?> »</p>

            <h1 class="fs-3">Étape 4 : ajouter vos clients</h1>
            <p>Les clients sont les personnes à qui vous prêtez du matériel. Enregistrez dès maintenant celles que vous connaissez : vous pourrez les choisir rapidement lors d'un prêt. Vous pourrez aussi en ajouter plus tard, au moment d'enregistrer un prêt.</p>

            <div class="mb-5">
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/client-setup.php'; ?>
            </div>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-between">
                <a href="/dashboard/organisation/?id=<?= $organisation->getId() ?>" class="btn btn-primary btn-lg order-sm-2">Terminer la configuration</a>
                <a href="/dashboard/organisation/init-orga.php?id=<?= $organisation->getId() ?>&amp;page=3" class="btn btn-outline-secondary order-sm-1">Retour</a>
            </div>
        </div>
    </section>
</main>
