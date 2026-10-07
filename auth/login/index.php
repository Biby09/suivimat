<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit();
}
$page_description = "Connectez-vous à votre compte SuiviMat pour gérer votre matériel et vos prêts.";
$page_title = "Connexion - SuiviMat";

?>

<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
        <section class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">

                    <h2 class="text-center mb-4">Connexion</h2>
                    <div id="formMessage"></div>
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Votre adresse email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Votre mot de passe" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                    </form>
                    <p class="text-center mt-3">
                        Pas encore de compte ? <a href="/auth/register">Inscrivez-vous</a>
                    </p>
                </div>
            </div>
        </section>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script src="/scripts/sendForm.js"></script>
    <script>
        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            await sendForm(e.target, "submit-login.php", {
                messageContainer: "#formMessage",
                successMessage: "Vous êtes connecté avec succès !",
                errorMessage: "Erreur de connexion. Veuillez vérifier vos identifiants et réessayer.",
                loadingMessage: "Connexion en cours..."
            });

        });

    </script>
</body>

</html>