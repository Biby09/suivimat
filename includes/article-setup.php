<?php
/*
    Configuration des objets à la chaîne : scanner + formulaire.
    Chaque code scanné rejoint la file d'attente ; on configure les objets un par un,
    chaque enregistrement est sauvegardé immédiatement puis on passe au suivant.
    Prérequis : $organisation (Organisation) doit être défini avant l'inclusion.
    Optionnel : $article_setup_categories_button = true affiche un bouton « Modifier les catégories » à côté de la liste
    des catégories (data-role="edit-categories") ; la page gère son clic.
    Utilisé dans l'initialisation (étape 3) et dans le modal includes/modals/article-setup-modal.php.
    Après chaque enregistrement, l'événement « article-configured » est émis (detail.code), par exemple pour recharger une liste d'objets.
*/
$scanner_id = 'article-setup-scanner';
$article_setup_categories_button = $article_setup_categories_button ?? false;
?>
<p>Scannez les codes QR collés sur vos objets, ou saisissez leur code à la main, puis donnez un nom et une catégorie à chacun. Chaque objet est enregistré dès que vous passez au suivant : vous pouvez vous arrêter à tout moment et reprendre plus tard.</p>

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
                        <div class="input-group">
                            <select class="form-select" id="article-setup-category" name="category_id">
                                <option value="">Aucune catégorie</option>
                            </select>
                            <?php if ($article_setup_categories_button): ?>
                                <button type="button" class="btn btn-outline-secondary" data-role="edit-categories" aria-label="Modifier les catégories" title="Modifier les catégories">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div data-role="fields"></div>

                    <div class="mb-3">
                        <label for="article-setup-photos" class="form-label">Photos <span class="text-muted">(optionnel)</span></label>
                        <div class="d-flex flex-wrap gap-2 mb-2" data-role="photos"></div>
                        <!-- Sans attribut name : les photos réduites sont ajoutées à l'envoi par scripts/article-setup.js -->
                        <input type="file" class="form-control" id="article-setup-photos" accept="image/*" multiple data-role="photo-input">
                        <div class="form-text">Ajoutez une ou plusieurs photos de l'objet, prises sur le moment ou depuis votre galerie.</div>
                    </div>

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
