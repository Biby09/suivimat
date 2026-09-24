<header class="border-bottom border-primary border-5 fixed-top bg-white">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a href="/"><img src="/medias/logo.svg" alt="SuiviMat" height="20"></a>
            <div class="d-flex align-items-center gap-2 order-lg-last">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-secondary btn-sm" href="/login">Se connecter</a>
                <?php else: ?>
                    <a class="btn btn-secondary btn-sm" href="/dashboard">Mon espace</a>
                    <div class="dropdown d-none d-lg-block">
                        <button class="btn btn-outline-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu du compte">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                            </svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/dashboard/account.php">Gérer mon compte</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/logout">Se déconnecter</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
                <button class="navbar-toggler border-0 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="burger" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto mt-3 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">A propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact">Contact</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <ul class="navbar-nav d-lg-none border-top mt-2 pt-2">
                        <li class="nav-item">
                            <a class="nav-link" href="/dashboard/account.php">Gérer mon compte</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="/logout">Se déconnecter</a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>