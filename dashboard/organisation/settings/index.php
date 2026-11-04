<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php'; // $user, $organisationManager, $invitationManager

/*
    Paramètres d'une organisation : /dashboard/organisation/settings/?id=… (engrenage à côté du sélecteur d'organisation
    dans le header). Tous les membres peuvent les modifier. Pensée mobile d'abord : une carte par sujet, sommaire en pastilles.
    - Général : nom de l'organisation
    - Membres : liste, invitation par e-mail, invitations en attente (annuler), retirer un membre, quitter
    - Prêts : durée par défaut, rappel au client 2 jours avant l'échéance (cron/send-loan-reminders.php), conditions du contrat
    - Objets perdus : page publique activée ou non, message affiché
    - Catégories (includes/category-setup.php)
    - Codes QR : nouveaux codes et réimpression (includes/modals/download-qr-modal.php)
    - Export de l'inventaire en CSV (api/organisation/export-inventory.php)
    - Supprimer l'organisation (confirmation par son nom)
    Formulaires et boutons : scripts/organisation-settings.js → api/organisation/*.php
*/

try {
    $organisation = $organisationManager->getOrganisationById((int) ($_GET['id'] ?? 0));
} catch (Exception $e) {
    http_response_code(404);
    require $_SERVER['DOCUMENT_ROOT'] . '/error/404.php';
    exit;
}

$members = $organisationManager->getMembers($organisation);
$invitations = $invitationManager->getPendingInvitationsForOrganisation($organisation);
$isLastMember = count($members) <= 1;
$sections = [
    'general' => 'Général', 'members' => 'Membres', 'loans' => 'Prêts', 'lost' => 'Objets perdus',
    'categories' => 'Catégories', 'qr' => 'Codes QR', 'export' => 'Export', 'danger' => 'Supprimer',
];

// « Jean Exemple » → « JE »
$initials = fn(User $member): string => mb_strtoupper(mb_substr($member->getFirstname(), 0, 1) . mb_substr($member->getLastname(), 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Paramètres | ' . $organisation->getName(); // échappé par head.php
$page_description = "Paramètres de l'organisation";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<!-- app : header de l'application (includes/app-header.php) -->
<body class="app">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

<main class="container py-4" id="organisation-settings" data-organisation-id="<?= $organisation->getId() ?>">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <p class="text-muted mb-0"><?= htmlspecialchars($organisation->getName()) ?></p>
            <h1 class="fs-3 mb-3">Paramètres de l'organisation</h1>

            <!-- Sommaire : pastilles défilables au doigt, chacune mène à sa section (ancre #…) -->
            <nav class="loans-filters d-flex gap-2 overflow-auto pb-2 mb-3" aria-label="Sections des paramètres">
                <?php foreach ($sections as $anchor => $label): ?>
                    <a class="btn btn-sm rounded-pill text-nowrap <?= $anchor === 'danger' ? 'btn-outline-danger' : 'btn-outline-primary' ?>" href="#<?= $anchor ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </nav>

            <!-- ===== Général ===== -->
            <form class="card mb-4 settings-section" id="general" novalidate data-role="settings-form" data-url="/api/organisation/update-settings.php" data-success="Nom enregistré." data-reload>
                <input type="hidden" name="section" value="general">
                <div class="card-body">
                    <h2 class="fs-5 mb-3">Général</h2>
                    <div data-role="message" aria-live="polite"></div>
                    <label for="settings-name" class="form-label">Nom de l'organisation</label>
                    <input type="text" class="form-control" id="settings-name" name="name" maxlength="100" required value="<?= htmlspecialchars($organisation->getName()) ?>">
                    <div class="invalid-feedback">Le nom est requis.</div>
                    <div class="d-grid d-sm-flex justify-content-sm-end mt-3">
                        <button type="submit" class="btn btn-primary" data-role="save">Enregistrer</button>
                    </div>
                </div>
            </form>

            <!-- ===== Membres ===== -->
            <section class="card mb-4 settings-section" id="members" aria-labelledby="settings-members-title">
                <div class="card-body">
                    <h2 class="fs-5 mb-1" id="settings-members-title">Membres</h2>
                    <p class="small text-muted">Tous les membres peuvent gérer le matériel, les prêts et ces paramètres.</p>
                    <div data-role="members-message" aria-live="polite"></div>

                    <ul class="list-group mb-3">
                        <?php foreach ($members as $member): ?>
                            <?php $isMe = $member->getId() === $user->getId(); ?>
                            <li class="list-group-item d-flex align-items-center gap-3">
                                <span class="client-avatar flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($initials($member)) ?></span>
                                <span class="flex-grow-1" style="min-width: 0;">
                                    <span class="d-block fw-semibold text-truncate">
                                        <?= htmlspecialchars($member->getFullname()) ?>
                                        <?php if ($isMe): ?><span class="badge text-bg-light border ms-1">Vous</span><?php endif; ?>
                                    </span>
                                    <span class="d-block small text-muted text-truncate"><?= htmlspecialchars($member->getEmail()) ?></span>
                                </span>
                                <?php if (!$isLastMember): ?>
                                    <!-- data-member-action : bouton géré par scripts/organisation-settings.js (confirmation, puis appel à l'API) -->
                                    <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" data-member-action="remove" data-user-id="<?= $member->getId() ?>"
                                        data-confirm="<?= htmlspecialchars($isMe ? 'Quitter « ' . $organisation->getName() . ' » ? Vous n\'y aurez plus accès.' : 'Retirer ' . $member->getFullname() . ' de l\'organisation ?') ?>">
                                        <?= $isMe ? 'Quitter' : 'Retirer' ?>
                                    </button>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($isLastMember): ?>
                        <p class="small text-muted">Vous êtes le seul membre : invitez quelqu'un pour partager la gestion du matériel.</p>
                    <?php endif; ?>

                    <?php if ($invitations): ?>
                        <h3 class="fs-6 text-uppercase text-muted mt-3 mb-2">Invitations en attente</h3>
                        <ul class="list-group mb-3">
                            <?php foreach ($invitations as $invitation): ?>
                                <li class="list-group-item d-flex align-items-center justify-content-between gap-2">
                                    <span style="min-width: 0;">
                                        <span class="d-block text-truncate"><?= htmlspecialchars($invitation->getUserName()) ?></span>
                                        <span class="d-block small text-muted text-truncate">
                                            <?= htmlspecialchars($invitation->getUserEmail()) ?> · invité le <?= formatDate($invitation->getCreatedAt()) ?>
                                        </span>
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" data-member-action="cancel-invitation"
                                        data-invitation-id="<?= $invitation->getId() ?>" data-confirm="Annuler l'invitation de <?= htmlspecialchars($invitation->getUserName()) ?> ?">Annuler</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <form novalidate data-role="settings-form" data-url="/api/organisation/invite-member.php" data-reload>
                        <h3 class="fs-6 text-uppercase text-muted mt-3 mb-2">Inviter un membre</h3>
                        <div data-role="message" aria-live="polite"></div>
                        <label for="settings-invite-email" class="form-label visually-hidden">Adresse e-mail de la personne à inviter</label>
                        <div class="input-group">
                            <input type="email" class="form-control" id="settings-invite-email" name="email" maxlength="50" required placeholder="Adresse e-mail" autocomplete="off">
                            <button type="submit" class="btn btn-primary" data-role="save">Inviter</button>
                        </div>
                        <div class="form-text">La personne doit avoir un compte SuiviMat. Elle reçoit un e-mail et accepte l'invitation depuis son tableau de bord.</div>
                    </form>
                </div>
            </section>

            <!-- ===== Prêts ===== -->
            <form class="card mb-4 settings-section" id="loans" novalidate data-role="settings-form" data-url="/api/organisation/update-settings.php" data-success="Paramètres des prêts enregistrés.">
                <input type="hidden" name="section" value="loans">
                <div class="card-body">
                    <h2 class="fs-5 mb-3">Prêts</h2>
                    <div data-role="message" aria-live="polite"></div>

                    <div class="mb-4">
                        <label for="settings-loan-days" class="form-label">Durée de prêt par défaut</label>
                        <div class="input-group" style="max-width: 16rem;">
                            <input type="number" class="form-control" id="settings-loan-days" name="default_loan_days" min="1" max="365" inputmode="numeric"
                                placeholder="Indéterminée" value="<?= $organisation->getDefaultLoanDays() ?? '' ?>">
                            <span class="input-group-text">jours</span>
                        </div>
                        <div class="form-text">Pré-remplit la date de retour prévue quand on prête un objet (modifiable). Vide : date indéterminée.</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <!-- value="1" : envoyé seulement si la case est cochée -->
                            <input class="form-check-input" type="checkbox" role="switch" id="settings-reminders" name="reminders_enabled" value="1"
                                <?= $organisation->areRemindersEnabled() ? 'checked' : '' ?>>
                            <label class="form-check-label" for="settings-reminders">Rappel automatique par e-mail</label>
                        </div>
                        <div class="form-text">Le client reçoit un e-mail 2 jours avant la date de retour prévue, s'il a une adresse e-mail.</div>
                    </div>

                    <div class="mb-1">
                        <label for="settings-contract" class="form-label">Conditions du contrat de prêt</label>
                        <textarea class="form-control" id="settings-contract" name="contract_terms" rows="5" maxlength="5000"
                            placeholder="L'emprunteur s'engage à prendre soin du matériel…"><?= htmlspecialchars($organisation->getContractTerms() ?? '') ?></textarea>
                        <div class="form-text">Imprimées sur le contrat PDF (lien « Contrat » dans l'historique d'un objet). Vide : conditions par défaut (prendre soin du matériel, le rendre en bon état à la date prévue, signaler tout dégât).</div>
                    </div>

                    <div class="d-grid d-sm-flex justify-content-sm-end mt-3">
                        <button type="submit" class="btn btn-primary" data-role="save">Enregistrer</button>
                    </div>
                </div>
            </form>

            <!-- ===== Objets perdus ===== -->
            <form class="card mb-4 settings-section" id="lost" novalidate data-role="settings-form" data-url="/api/organisation/update-settings.php" data-success="Paramètres des objets perdus enregistrés.">
                <input type="hidden" name="section" value="lost">
                <div class="card-body">
                    <h2 class="fs-5 mb-3">Objets perdus</h2>
                    <div data-role="message" aria-live="polite"></div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="settings-lost-page" name="lost_page_enabled" value="1"
                                <?= $organisation->isLostPageEnabled() ? 'checked' : '' ?>>
                            <label class="form-check-label" for="settings-lost-page">Page « objet perdu » pour la personne qui le trouve</label>
                        </div>
                        <div class="form-text">Quand quelqu'un scanne le code QR d'un objet perdu, il voit le nom et la photo de l'objet et peut vous écrire.
                            Désactivée : il arrive sur la page de connexion, sans aucune information.</div>
                    </div>
                    <label for="settings-lost-message" class="form-label">Message affiché sur cette page <span class="text-muted">(facultatif)</span></label>
                    <textarea class="form-control" id="settings-lost-message" name="lost_message" rows="3" maxlength="500"
                        placeholder="Ex. Vous pouvez aussi déposer l'objet à notre local, rue de la Gare 12, le samedi matin."><?= htmlspecialchars($organisation->getLostMessage() ?? '') ?></textarea>
                    <div class="d-grid d-sm-flex justify-content-sm-end mt-3">
                        <button type="submit" class="btn btn-primary" data-role="save">Enregistrer</button>
                    </div>
                </div>
            </form>

            <!-- ===== Catégories ===== -->
            <section class="card mb-4 settings-section" id="categories" aria-labelledby="settings-categories-title">
                <div class="card-body">
                    <h2 class="fs-5 mb-3" id="settings-categories-title">Catégories</h2>
                    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/category-setup.php'; ?>
                </div>
            </section>

            <!-- ===== Codes QR ===== -->
            <section class="card mb-4 settings-section" id="qr" aria-labelledby="settings-qr-title">
                <div class="card-body">
                    <h2 class="fs-5 mb-2" id="settings-qr-title">Codes QR</h2>
                    <p class="small text-muted">Téléchargez de nouveaux codes pour étiqueter du matériel, ou réimprimez des codes existants
                        (étiquette abîmée ou perdue). Pour un seul objet : « Réimprimer l'étiquette » dans les actions de sa fiche.</p>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#download-qr-modal">Télécharger des codes QR</button>
                </div>
            </section>

            <!-- ===== Export ===== -->
            <section class="card mb-4 settings-section" id="export" aria-labelledby="settings-export-title">
                <div class="card-body">
                    <h2 class="fs-5 mb-2" id="settings-export-title">Export de l'inventaire</h2>
                    <p class="small text-muted">Tableau des objets enregistrés (code, nom, catégorie, statut, prêt en cours, informations), à ouvrir avec Excel ou un tableur.</p>
                    <a class="btn btn-outline-primary" href="/api/organisation/export-inventory.php?id=<?= $organisation->getId() ?>" download>Télécharger l'inventaire (CSV)</a>
                </div>
            </section>

            <!-- ===== Zone dangereuse ===== -->
            <section class="card border-danger settings-section" id="danger" aria-labelledby="settings-danger-title">
                <div class="card-body">
                    <h2 class="fs-5 text-danger mb-2" id="settings-danger-title">Supprimer l'organisation</h2>
                    <p class="small mb-3">L'organisation et <strong>toutes ses données</strong> (objets, photos, prêts, clients, catégories) seront supprimées
                        définitivement, pour tous ses membres.</p>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete-organisation-modal">Supprimer l'organisation</button>
                </div>
            </section>
        </div>
    </div>

    <!-- Confirmation de la suppression : le nom de l'organisation doit être tapé -->
    <div class="modal fade" id="delete-organisation-modal" tabindex="-1" aria-labelledby="delete-organisation-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" novalidate data-role="settings-form" data-url="/api/organisation/delete-organisation.php">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6" id="delete-organisation-modal-label">Supprimer l'organisation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div data-role="message" aria-live="polite"></div>
                    <p>Cette action est <strong>définitive</strong>. Pour confirmer, tapez le nom de l'organisation :
                        <strong class="user-select-all"><?= htmlspecialchars($organisation->getName()) ?></strong></p>
                    <label for="delete-organisation-name" class="form-label visually-hidden">Nom de l'organisation</label>
                    <!-- data-expected : le formulaire n'est envoyé que si le nom tapé est exactement celui-ci (scripts/organisation-settings.js) -->
                    <input type="text" class="form-control" id="delete-organisation-name" name="confirm_name" required autocomplete="off"
                        data-expected="<?= htmlspecialchars($organisation->getName()) ?>">
                    <div class="invalid-feedback">Le nom ne correspond pas.</div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger" data-role="save">Supprimer définitivement</button>
                </div>
            </form>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/download-qr-modal.php'; ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
<script src="<?= asset('/scripts/organisation-settings.js') ?>" defer></script>
</body>
</html>
