<main>
    <section class="py-5">
        <div class="container">
            <p class="text-muted mb-1">Initialisation de « <?= htmlspecialchars($organisation->getName(), ENT_QUOTES, 'UTF-8') ?> »</p>

            <h1 class="fs-3">Étape 3 : enregistrer vos objets</h1>

            <div class="mb-5">
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/article-setup.php'; ?>
            </div>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-between">
                <a href="/dashboard/organisation/init-orga.php?id=<?= $organisation->getId() ?>&amp;page=4" class="btn btn-primary btn-lg order-sm-2">Suivant</a>
                <a href="/dashboard/organisation/init-orga.php?id=<?= $organisation->getId() ?>&amp;page=2" class="btn btn-outline-secondary order-sm-1">Retour</a>
            </div>
        </div>
    </section>
</main>
