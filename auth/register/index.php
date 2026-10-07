<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit();
}

$page_description = "Créez votre compte SuiviMat pour gérer votre matériel et vos prêts.";
$page_title = "Inscription - SuiviMat";
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
                    <h2 class="text-center mb-4">Inscription</h2>
                    <div id="formMessage"></div>
                    <form id="registerForm">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="firstname" class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="firstname" id="firstname"
                                    placeholder="Votre prénom" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="lastname" class="form-label">Nom</label>
                                <input type="text" class="form-control" name="lastname" id="lastname"
                                    placeholder="Votre nom" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email"
                                placeholder="Votre adresse email" maxlength="50" required>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" name="password" id="password"
                                    placeholder="Votre mot de passe" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" name="confirmPassword" id="confirmPassword"
                                    placeholder="Confirmer votre mot de passe" required>
                            </div>
                        </div>
                        <div class="mb-3 d-flex justify-content-center">
                            <div class="g-recaptcha"
                                data-sitekey="<?= htmlspecialchars(getenv('RECAPTCHA_SITE_KEY'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>


                        <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                    </form>
                    <p class="text-center mt-3">
                        Déjà un compte ? <a href="/auth/login">Connectez-vous</a>
                    </p>
                </div>
            </div>
        </section>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script src="https://www.google.com/recaptcha/api.js?hl=fr" async defer></script>
    <script src="/scripts/sendForm.js"></script>
    <script>
        document.getElementById("registerForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            //Controle de la complexité du mot de passe côté client
            // Minimum 8 caractères, au moins une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial
            const password = e.target.password.value;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
            if (!passwordRegex.test(password)) {
                document.getElementById("formMessage").innerHTML = '<div class="alert alert-danger">Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial.</div>';
                return;
            }

            if (e.target.password.value !== e.target.confirmPassword.value) {
                document.getElementById("formMessage").innerHTML = '<div class="alert alert-danger">Les mots de passe ne correspondent pas.</div>';
                return;
            }

            let response = await sendForm(e.target, "submit-register.php",{
                messageContainer: "#formMessage",
                successMessage: "Votre compte a été créé avec succès ! Vous allez être redirigé.",
                errorMessage: "Une erreur est survenue. Veuillez réessayer plus tard.",
                loadingMessage: "Inscription en cours..."
            });

            if (!response.success) {
                grecaptcha.reset();
            }
        });
    </script>


</body>

</html>