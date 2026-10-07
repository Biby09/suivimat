<?php
/*
    Configuration des objets à la chaîne : scanner + formulaire.
    Chaque code scanné rejoint la file d'attente ; on configure les objets un par un,
    chaque enregistrement est sauvegardé immédiatement puis on passe au suivant.
    Prérequis : $organisation (Organisation) doit être défini avant l'inclusion.
*/
$scanner_id = 'article-setup-scanner';
?>
<div class="row g-4" id="article-setup" data-organisation-id="<?= $organisation->getId() ?>">
    <div class="col-lg-5">
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/scanner.php'; ?>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="fs-5 mb-0">Objets à configurer</h2>
                    <span class="badge text-bg-success" data-role="done-count">0 configuré</span>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-3" data-role="queue" aria-live="polite"></div>

                <div data-role="message" aria-live="polite"></div>

                <p class="text-muted mb-0" data-role="empty">Scannez un code QR ou saisissez un code pour commencer.</p>

                <form data-role="form" style="scroll-margin-top: 6rem;" hidden novalidate>
                    <h3 class="fs-6 mb-3">
                        Objet <span class="font-monospace fw-bold" data-role="current-code"></span>
                        <span class="badge text-bg-warning ms-1" data-role="already-configured" hidden>déjà configuré</span>
                    </h3>

                    <div class="mb-3">
                        <label for="article-setup-name" class="form-label">Nom de l'objet</label>
                        <input type="text" class="form-control" id="article-setup-name" name="name" maxlength="100" required>
                        <div class="invalid-feedback">Le nom est requis.</div>
                    </div>

                    <div class="mb-3">
                        <label for="article-setup-category" class="form-label">Catégorie</label>
                        <select class="form-select" id="article-setup-category" name="category_id">
                            <option value="">Aucune catégorie</option>
                        </select>
                    </div>

                    <div data-role="fields"></div>

                    <!-- Mobile : boutons pleine largeur, « Enregistrer » en premier sous le pouce -->
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="submit" class="btn btn-primary btn-lg order-sm-2" data-role="save">Enregistrer et suivant</button>
                        <button type="button" class="btn btn-outline-secondary order-sm-1" data-role="skip">Retirer de la liste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="/scripts/article-setup.js" defer></script>
