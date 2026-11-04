<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php'; // $user, $userManager, $organisationManager

/*
    Gestion du compte personnel : /dashboard/account (lien « Gérer mon compte » du menu du compte).
    Pensée mobile d'abord : une carte par sujet, les unes sous les autres.
    - Informations personnelles : prénom, nom, e-mail (le mot de passe actuel est demandé si l'e-mail change)
      → api/account/update-profile.php
    - Mot de passe : actuel, nouveau (règles cochées pendant la saisie), confirmation → api/account/update-password.php
    - Mes organisations : liens vers chacune
    - Se déconnecter
    - Supprimer mon compte : confirmation par mot de passe ; impossible si l'utilisateur est le seul membre d'une organisation
      → api/account/delete-account.php
    Comportement des formulaires : scripts/account.js
*/

$organisations = $organisationManager->getAllOrganisationsForUser();
$soleOrganisations = $userManager->getSoleMemberOrganisationNames($user);

// Règles du mot de passe : mêmes que User::setPassword() ; clé = expression régulière testée par scripts/account.js
$passwordRules = [
    '.{8,}' => 'Au moins 8 caractères',
    '[A-Z]' => 'Une lettre majuscule',
    '[a-z]' => 'Une lettre minuscule',
    '[0-9]' => 'Un chiffre',
    '[\W_]' => 'Un caractère spécial (ex. ! ? - _ @)',
];
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Mon compte | SuiviMat';
$page_description = 'Gestion du compte personnel';
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>
<!-- app : header de l'application (includes/app-header.php) -->
<body class="app">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fs-3 mb-4">Mon compte</h1>

            <!-- Informations personnelles -->
            <form class="card mb-4" novalidate data-role="account-form" data-url="/api/account/update-profile.php" data-success="Informations enregistrées.">
                <div class="card-body">
                    <h2 class="fs-5 mb-3">Informations personnelles</h2>
                    <div data-role="message" aria-live="polite"></div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="account-firstname" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="account-firstname" name="firstname" maxlength="50" required autocomplete="given-name"
                                value="<?= htmlspecialchars($user->getFirstname()) ?>">
                            <div class="invalid-feedback">Le prénom est requis.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="account-lastname" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="account-lastname" name="lastname" maxlength="50" required autocomplete="family-name"
                                value="<?= htmlspecialchars($user->getLastname()) ?>">
                            <div class="invalid-feedback">Le nom est requis.</div>
                        </div>
                        <div class="col-12">
                            <label for="account-email" class="form-label">E-mail</label>
                            <!-- data-original : adresse actuelle ; si elle change, le mot de passe actuel est demandé (scripts/account.js) -->
                            <input type="email" class="form-control" id="account-email" name="email" maxlength="50" required autocomplete="email"
                                value="<?= htmlspecialchars($user->getEmail()) ?>" data-original="<?= htmlspecialchars($user->getEmail()) ?>">
                            <div class="invalid-feedback">Indiquez une adresse e-mail valide.</div>
                            <div class="form-text">Elle sert à vous connecter.</div>
                        </div>
                        <div class="col-12" data-role="email-password" hidden>
                            <label for="account-email-password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="account-email-password" name="current_password" autocomplete="current-password">
                            <div class="invalid-feedback">Le mot de passe est nécessaire pour changer d'adresse e-mail.</div>
                            <div class="form-text">Nécessaire pour changer l'adresse de connexion.</div>
                        </div>
                    </div>
                    <div class="d-grid d-sm-flex justify-content-sm-end mt-3">
                        <button type="submit" class="btn btn-primary" data-role="save">Enregistrer</button>
                    </div>
                </div>
            </form>

            <!-- Mot de passe -->
            <form class="card mb-4" novalidate data-role="account-form" data-url="/api/account/update-password.php" data-success="Mot de passe modifié." data-reset>
                <div class="card-body">
                    <h2 class="fs-5 mb-3">Mot de passe</h2>
                    <div data-role="message" aria-live="polite"></div>
                    <div class="mb-3">
                        <label for="account-current-password" class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="account-current-password" name="current_password" required autocomplete="current-password">
                        <div class="invalid-feedback">Le mot de passe actuel est requis.</div>
                    </div>
                    <div class="mb-3">
                        <label for="account-new-password" class="form-label">Nouveau mot de passe</label>
                        <!-- autocomplete="new-password" : le gestionnaire de mots de passe du navigateur peut en proposer un -->
                        <input type="password" class="form-control" id="account-new-password" name="new_password" required autocomplete="new-password"
                            aria-describedby="account-password-rules" data-role="new-password">
                        <div class="invalid-feedback">Le mot de passe ne respecte pas toutes les règles ci-dessous.</div>
                        <!-- Règles : chaque ligne se coche pendant la saisie (scripts/account.js) -->
                        <ul class="list-unstyled small mt-2 mb-0" id="account-password-rules">
                            <?php foreach ($passwordRules as $pattern => $label): ?>
                                <li class="text-muted" data-rule="<?= htmlspecialchars($pattern) ?>">
                                    <span aria-hidden="true" data-role="rule-icon">○</span> <?= htmlspecialchars($label) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label for="account-confirm-password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control" id="account-confirm-password" name="confirm_password" required autocomplete="new-password" data-role="confirm-password">
                        <div class="invalid-feedback">Les deux mots de passe ne sont pas identiques.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="account-show-passwords" data-role="show-passwords">
                        <label class="form-check-label" for="account-show-passwords">Afficher les mots de passe</label>
                    </div>
                    <div class="d-grid d-sm-flex justify-content-sm-end">
                        <button type="submit" class="btn btn-primary" data-role="save">Changer le mot de passe</button>
                    </div>
                </div>
            </form>

            <!-- Organisations -->
            <section class="card mb-4" aria-labelledby="account-organisations-title">
                <div class="card-body">
                    <h2 class="fs-5 mb-3" id="account-organisations-title">Mes organisations</h2>
                    <?php if (!$organisations): ?>
                        <p class="text-muted mb-0">Vous ne faites partie d'aucune organisation. <a href="/dashboard">Créer une organisation</a></p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($organisations as $organisationItem): ?>
                                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                                    href="/dashboard/organisation/?id=<?= $organisationItem->getId() ?>">
                                    <span class="text-truncate"><?= htmlspecialchars($organisationItem->getName()) ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="flex-shrink-0 text-muted" aria-hidden="true"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/></svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Session -->
            <section class="card mb-4" aria-labelledby="account-session-title">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h2 class="fs-5 mb-1" id="account-session-title">Session</h2>
                        <p class="small text-muted mb-0">Connecté en tant que <?= htmlspecialchars($user->getEmail()) ?></p>
                    </div>
                    <a class="btn btn-outline-secondary" href="/auth/logout.php">Se déconnecter</a>
                </div>
            </section>

            <!-- Zone dangereuse -->
            <section class="card border-danger" aria-labelledby="account-delete-title">
                <div class="card-body">
                    <h2 class="fs-5 text-danger mb-2" id="account-delete-title">Supprimer mon compte</h2>
                    <p class="small mb-3">Votre compte et votre accès aux organisations seront supprimés définitivement.
                        Le matériel et l'historique des organisations restent à leurs autres membres.</p>
                    <?php if ($soleOrganisations): ?>
                        <div class="alert alert-warning small mb-0">
                            Vous êtes le seul membre de <strong><?= htmlspecialchars(implode(', ', $soleOrganisations)) ?></strong>.
                            Invitez une autre personne dans <?= count($soleOrganisations) > 1 ? 'ces organisations' : 'cette organisation' ?>
                            avant de supprimer votre compte, sinon plus personne ne pourrait les gérer.
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#account-delete-modal">Supprimer mon compte</button>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <?php if (!$soleOrganisations): ?>
        <!-- Confirmation de la suppression : mot de passe demandé -->
        <div class="modal fade" id="account-delete-modal" tabindex="-1" aria-labelledby="account-delete-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" novalidate data-role="account-form" data-url="/api/account/delete-account.php">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6" id="account-delete-modal-label">Supprimer mon compte</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div data-role="message" aria-live="polite"></div>
                        <p>Cette action est <strong>définitive</strong>. Pour confirmer, saisissez votre mot de passe.</p>
                        <label for="account-delete-password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="account-delete-password" name="password" required autocomplete="current-password">
                        <div class="invalid-feedback">Le mot de passe est requis.</div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger" data-role="save">Supprimer définitivement</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
<script src="<?= asset('/scripts/account.js') ?>" defer></script>
</body>
</html>
