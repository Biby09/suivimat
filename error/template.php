<?php
// Gabarit commun des pages d'erreur : définir $error_code, $error_title et $error_message avant l'inclusion
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
http_response_code($error_code);
$page_title = $error_title . ' | SuiviMat';
$page_description = $error_message;
$robots_directive = 'noindex, nofollow';
?>
<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="d-flex align-items-center py-5" style="min-height: 70vh;">
        <div class="container text-center">
            <p class="display-1 fw-bold text-primary mb-0"><?= (int) $error_code ?></p>
            <h1 class="mb-3"><?= htmlspecialchars($error_title, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="lead mb-4"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                <a class="btn btn-primary btn-lg" href="/">Retour à l'accueil</a>
                <?php if ($error_code === 401): ?>
                    <a class="btn btn-outline-primary btn-lg" href="/auth/login">Se connecter</a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-outline-primary btn-lg" href="/dashboard">Mon espace</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-lg" href="/contact">Nous contacter</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
</body>

</html>
