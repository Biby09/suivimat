<main>
    <section class="py-5">
        <div class="container">
            <p class="text-muted mb-1">Initialisation de « <?= htmlspecialchars($organisation->getName(), ENT_QUOTES, 'UTF-8') ?> »</p>

            <h1 class="fs-3">Étape 2 : organiser votre matériel</h1>
            <p>Les catégories regroupent les objets du même type (tentes, câbles, projecteurs…). Pour chacune, vous pouvez définir des champs personnalisés : des informations à renseigner pour chaque objet, comme le nombre de places d'une tente.</p>

            <div class="mb-5">
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/category-setup.php'; ?>
            </div>

            <h2 class="fs-3">Étape 3 : enregistrer vos objets</h2>
            <p>Scannez les codes QR collés sur vos objets, ou saisissez leur code à la main, puis donnez un nom et une catégorie à chacun. Chaque objet est enregistré dès que vous passez au suivant : vous pouvez vous arrêter à tout moment et reprendre plus tard.</p>

            <div class="mb-5">
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/article-setup.php'; ?>
            </div>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-between">
                <a href="/dashboard/organisation/init-orga.php?id=<?= $organisation->getId() ?>&amp;page=3" class="btn btn-primary btn-lg order-sm-2">Suivant</a>
                <a href="/dashboard/organisation/init-orga.php?id=<?= $organisation->getId() ?>&amp;page=1" class="btn btn-outline-secondary order-sm-1">Retour</a>
            </div>
        </div>
    </section>
</main>
