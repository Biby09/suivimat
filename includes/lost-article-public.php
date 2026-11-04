<?php
/*
    Page publique d'un objet perdu : affichée quand quelqu'un scanne son code QR sans être membre de l'organisation
    (non connecté, ou connecté à un autre compte). Incluse par dashboard/organisation/article/index.php (?code=…).
    Montre seulement le nom et la photo de l'objet et le nom de l'organisation (pas les prêts, clients ni remarques),
    avec un formulaire envoyé par e-mail aux membres de l'organisation (api/article/contact-lost.php, scripts/lost-contact.js).
    Prérequis : $article (Article), $organisation (Organisation), $mysqlClient (PDO).
*/
$images = (new ImageManager($mysqlClient))->getArticleImages($article);
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Objet perdu | SuiviMat';
$page_description = "Vous avez trouvé un objet perdu : contactez son propriétaire.";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="alert alert-warning">
                <h1 class="fs-4 mb-1">Vous avez trouvé un objet perdu</h1>
                <p class="mb-0">Merci ! Cet objet appartient à <strong><?= htmlspecialchars($organisation->getName()) ?></strong>.
                    Écrivez-lui ci-dessous pour convenir de son retour.</p>
            </div>

            <div class="card mb-4">
                <div class="row g-0 align-items-center">
                    <div class="col-4">
                        <div class="ratio ratio-1x1 bg-light rounded-start overflow-hidden">
                            <?php if ($images): ?>
                                <img src="<?= htmlspecialchars($images[0]->getUrl()) ?>" class="object-fit-cover" alt="Photo de l'objet">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center text-secondary small">Pas de photo</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="card-body">
                            <h2 class="fs-5 mb-1 text-break"><?= htmlspecialchars($article->getName()) ?></h2>
                            <span class="badge text-bg-light border font-monospace"><?= htmlspecialchars($article->getCode()) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <form class="card" novalidate data-role="lost-contact-form" data-code="<?= htmlspecialchars($article->getCode()) ?>">
                <div class="card-body">
                    <h2 class="fs-5 mb-3">Contacter <?= htmlspecialchars($organisation->getName()) ?></h2>
                    <div data-role="message" aria-live="polite"></div>

                    <div class="mb-3">
                        <label for="lost-contact-message" class="form-label">Message</label>
                        <textarea class="form-control" id="lost-contact-message" name="message" rows="4" maxlength="2000" required
                            placeholder="Ex. J'ai trouvé cet objet au parc des Rives, je peux le déposer…"></textarea>
                        <div class="invalid-feedback">Écrivez un message.</div>
                    </div>
                    <div class="mb-3">
                        <label for="lost-contact-email" class="form-label">Votre e-mail</label>
                        <input type="email" class="form-control" id="lost-contact-email" name="email" maxlength="100" required autocomplete="email">
                        <div class="invalid-feedback">Indiquez une adresse e-mail valide.</div>
                        <div class="form-text">L'organisation vous répondra à cette adresse.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="lost-contact-name" class="form-label">Votre nom <span class="text-muted">(optionnel)</span></label>
                            <input type="text" class="form-control" id="lost-contact-name" name="name" maxlength="100" autocomplete="name">
                        </div>
                        <div class="col-sm-6">
                            <label for="lost-contact-phone" class="form-label">Téléphone <span class="text-muted">(optionnel)</span></label>
                            <input type="tel" class="form-control" id="lost-contact-phone" name="phone" maxlength="30" autocomplete="tel">
                        </div>
                    </div>
                    <!-- Champ piège : caché aux humains (et aux lecteurs d'écran), rempli par les robots → message ignoré -->
                    <div class="visually-hidden" aria-hidden="true">
                        <label for="lost-contact-website">Site web</label>
                        <input type="text" id="lost-contact-website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" data-role="save">Envoyer le message</button>
                </div>
            </form>

            <!-- Affiché à la place du formulaire après l'envoi -->
            <div class="alert alert-success" data-role="sent" hidden>
                <h2 class="fs-5">Message envoyé</h2>
                <p class="mb-0">Merci ! <?= htmlspecialchars($organisation->getName()) ?> a reçu votre message et vous répondra par e-mail.</p>
            </div>
        </div>
    </div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
<script src="<?= asset('/scripts/lost-contact.js') ?>" defer></script>
</body>
</html>
