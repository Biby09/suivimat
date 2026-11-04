// Enchaînement des modals autour du modal d'enregistrement des objets (includes/modals/article-setup-modal.php)
(() => {
    const setupModal = document.getElementById("article-setup-modal");
    const downloadModal = document.getElementById("download-qr-modal");
    const nextStepsModal = document.getElementById("qr-next-steps-modal");
    const categoriesModal = document.getElementById("category-setup-modal");
    const show = (element) => bootstrap.Modal.getOrCreateInstance(element).show();

    // Vrai pendant l'enchaînement lancé depuis ce modal ; les codes générés ouvrent la marche à suivre
    let qrFlow = false;
    let generated = false;
    // Vrai pendant l'aller-retour vers le modal des catégories
    let categoriesFlow = false;

    setupModal.addEventListener("hidden.bs.modal", () => {
        setupModal.querySelector("[data-scanner]").scanner.stop();
        if (qrFlow) {
            show(downloadModal);
        } else if (categoriesFlow) {
            show(categoriesModal);
        }
    });

    setupModal.querySelector("[data-role='edit-categories']").addEventListener("click", () => {
        categoriesFlow = true;
        bootstrap.Modal.getInstance(setupModal).hide();
    });

    // Ignoré quand le modal des catégories se ferme le temps de modifier une catégorie (scripts/category-setup.js)
    categoriesModal.addEventListener("hidden.bs.modal", () => {
        if (categoriesFlow && !categoriesModal.dataset.editing) {
            categoriesFlow = false;
            show(setupModal);
        }
    });

    setupModal.querySelector("[data-role='open-download-qr']").addEventListener("click", (e) => {
        e.preventDefault();
        qrFlow = true;
        generated = false;
        bootstrap.Modal.getInstance(setupModal).hide();
    });

    document.addEventListener("qr-codes-generated", (e) => {
        if (!qrFlow) {
            return;
        }
        // download-qr-modal.js ferme alors le modal de téléchargement
        e.preventDefault();
        generated = true;
        document.getElementById("qr-pdf-link").href = e.detail.pdfUrl;
    });

    // Annulé : retour direct à l'enregistrement ; codes générés : marche à suivre d'abord
    downloadModal.addEventListener("hidden.bs.modal", () => {
        if (qrFlow) {
            show(generated ? nextStepsModal : setupModal);
            qrFlow = generated;
        }
    });

    nextStepsModal.addEventListener("hidden.bs.modal", () => {
        if (qrFlow) {
            qrFlow = false;
            show(setupModal);
        }
    });
})();
