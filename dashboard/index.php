<?php
// $_SESSION['user_id'] = null;
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php' ?>

<!DOCTYPE html>
<html lang="en">
<?php
$page_title = "Dashboard";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="container py-5">
        <h1 class="mb-4">Bienvenue sur votre tableau de bord, <?= htmlspecialchars($user->getFullname()) ?> !</h1>
        <p class="lead">Ici, vous pouvez gérer votre matériel et organiser vos associations.</p>

        <div class="row g-4 mt-4">
            <div class="col-md-6 col-lg-6">
                <div class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <h2 class="card-title">Mes organisations</h2>
                        <?php
                        $userOrganizations = $organisationManager->getAllOrganisationsForUser();
                        if (!empty($userOrganizations)) {
                            echo '<p class="card-text">Vous appartenez aux organisations suivantes :</p>';
                            foreach ($userOrganizations as $org): ?>
                                <div class="card mb-3 border-start border-primary border-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center h-100">
                                            <p class="card-title mb-0 fs-5 fw-bold text-primary">
                                                <?= htmlspecialchars($org->getName()) ?>
                                            </p>
                                            <a href="/dashboard/organisation/?id=<?= $org->getId() ?>"
                                                class="btn btn-sm btn-outline-primary">Accéder →</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;
                        } else {
                            echo '<p class="card-text">Vous n\'appartenez à aucune organisation.</p>';
                        }
                        ?>
                        <button class="btn btn-primary" id="create-organisation-button" data-bs-toggle='modal'
                            data-bs-target="#createOrganisationModal">Créer une
                            organisation</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-6 d-flex flex-column gap-4">
                <div class="card text-decoration-none text-dark flex-grow-1">
                    <div class="card-body text-center">
                        <h2 class="card-title">Mon compte</h2>
                        <p class="card-text">Gérez vos informations personnelles et les paramètres de votre compte.</p>
                        <a href="/dashboard/account" class="btn btn-primary">Gérer mon compte →</a>
                    </div>
                </div>
                <div class="card text-decoration-none text-dark flex-grow-1">
                    <div class="card-body text-center">
                        <h2 class="card-title">Invitations</h2>
                        <?php
                        $invitations = $invitationManager->getAllInvitationsForUser();
                        if (empty($invitations)) {
                            echo '<p class="card-text">Vous n\'avez aucune invitation en attente.</p>';
                        } else {
                            foreach ($invitations as $invitation) {
                                $org = $organisationManager->getOrganisationById($invitation->organisation_id); ?>
                                <div class="card mb-3 border-start border-secondary border-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h3 class="card-title mb-0 text-secondary text-start text-break flex-grow-1"
                                                style="min-width: 0;">
                                                <?= htmlspecialchars($org->getName()) ?>
                                            </h3>
                                            <div>
                                                <button class="btn btn-sm btn-success me-2 respond-invitation-accept"
                                                    data-invitation-id=<?= $invitation->id ?>>Accepter</button>
                                                <button class="btn btn-sm btn-danger respond-invitation-reject"
                                                    data-invitation-id=<?= $invitation->id ?>>Refuser</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pour la création de nouvelles organisations -->
        <div class="modal fade" id="createOrganisationModal" tabindex="-1"
            aria-labelledby="createOrganisationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6" id="createOrganisationModalLabel">Créer une nouvelle organisation
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <form id="create-organisation-form">
                            <div class="mb-3">
                                <label for="organisation-name" class="form-label">Nom de l'organisation</label>
                                <input type="text" class="form-control" id="organisation-name" name="organisation_name"
                                    maxlength="100" required>
                            </div>
                            <div id="create-organisation-message"></div>
                        </form>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" form="create-organisation-form">Créer</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script src="/scripts/sendForm.js"></script>
    <script>
        document.getElementById("create-organisation-form").addEventListener("submit", async (e) => {
            e.preventDefault();

            $result = await sendForm(e.target, "/api/organisation/create-organisation.php", {
                messageContainer: "#create-organisation-message",
                successMessage: "Organisation créée avec succès !",
                errorMessage: "Erreur lors de la création de l'organisation.",
                loadingMessage: "Création en cours..."
            });

            if ($result.success) {
                window.location.href = "/dashboard/organisation/init-orga.php?id=" + $result.organisation_id;
            }
        });
    </script>
</body>

</html>