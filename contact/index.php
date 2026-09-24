<!DOCTYPE html>
<html lang="fr">

<?php
$page_title = 'Contacter SuiviMat';
$page_description = 'Une question sur SuiviMat ? Contactez notre équipe au sujet de la gestion du matériel associatif.';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

    <main>
        <section class="py-5">
            <div class="container">
                <h1 class="text-center mb-4">Contactez-nous</h1>
                <p class="text-center mb-5">Vous avez des questions, des suggestions ou besoin d'aide ? N'hésitez pas à
                    nous contacter !</p>
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div id="formMessage"></div>
                        <form id="contactForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Votre nom"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email"
                                    placeholder="Votre adresse email" maxlength="50" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Sujet</label>
                                <input type="text" class="form-control" name="subject" id="subject" maxlength="100"
                                    placeholder="Sujet de votre message" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" name="message" id="message" rows="5"
                                    placeholder="Votre message" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Envoyer</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script src="/scripts/sendForm.js"></script>
    <script>
        document.getElementById("contactForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            await sendForm(e.target, "submit-contact.php", {
                messageContainer: "#formMessage",
                successMessage: "Votre message a été envoyé avec succès !",
                errorMessage: "Une erreur est survenue. Veuillez réessayer plus tard.",
                loadingMessage: "Envoi en cours..."
            });

        });
    </script>
</body>

</html>