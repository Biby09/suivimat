// Scanner rapide (includes/modals/quick-scan-modal.php) : un code lu ouvre la fiche de l'objet s'il est déjà enregistré,
// sinon le modal d'enregistrement des objets (includes/modals/article-setup-modal.php) avec ce code
(() => {
    const modal = document.getElementById("quick-scan-modal");
    if (!modal) {
        return;
    }

    const organisationId = modal.dataset.organisationId;
    const scannerElement = modal.querySelector("[data-scanner]");
    const setupModal = document.getElementById("article-setup-modal");

    let busy = false;        // un code est en cours de recherche : les suivants sont ignorés
    let codeToSetup = null;  // code à transmettre au modal d'enregistrement, une fois ce modal-ci fermé

    // Caméra allumée dès l'ouverture : on peut scanner tout de suite
    modal.addEventListener("shown.bs.modal", () => scannerElement.scanner.start());

    // Bootstrap n'empile pas les modals : le modal d'enregistrement s'ouvre quand celui-ci est fermé
    modal.addEventListener("hidden.bs.modal", () => {
        scannerElement.scanner.stop();
        if (!codeToSetup) {
            return;
        }

        const code = codeToSetup;
        codeToSetup = null;
        // { once: true } : l'écouteur se supprime après le premier appel (sinon le code serait renvoyé à chaque ouverture)
        setupModal.addEventListener("shown.bs.modal", () => {
            document.dispatchEvent(new CustomEvent("article-setup-add-code", { detail: { code } }));
        }, { once: true });
        bootstrap.Modal.getOrCreateInstance(setupModal).show();
    });

    async function findArticle(code) {
        const params = new URLSearchParams({ organisation_id: organisationId, code });
        try {
            const response = await fetch(`/api/article/get-article.php?${params}`);
            return await response.json();
        } catch {
            return { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }
    }

    // e.detail : { code, source, scanner } (voir scripts/scanner.js)
    scannerElement.addEventListener("code-scanned", async (e) => {
        if (busy) {
            return;
        }
        const { code, scanner } = e.detail;  // déstructuration : const code = e.detail.code; const scanner = e.detail.scanner;

        busy = true;
        scanner.pause();
        scanner.setStatus(`Recherche de l'objet ${code}…`);
        const data = await findArticle(code);
        busy = false;

        if (!data.success) {
            // Code d'une autre organisation, session expirée… : on peut scanner un autre code
            scanner.setStatus(data.message || `Le code ${code} est introuvable.`, "danger");
            scanner.resume();
            return;
        }

        if (data.article.status === "Non attribué") {
            if (!setupModal) {
                // Page sans le dialog d'enregistrement (ex. fiche d'un objet) : l'inventaire l'ouvre avec ce code
                // (?enregistrer=…, géré par scripts/article-setup-modal.js)
                window.location.href = `/dashboard/organisation/?id=${encodeURIComponent(organisationId)}&enregistrer=${encodeURIComponent(code)}`;
                return;
            }
            codeToSetup = code;
            bootstrap.Modal.getInstance(modal).hide();
            return;
        }

        scanner.setStatus(`Ouverture de « ${data.article.name} »…`, "success");
        window.location.href = `/dashboard/organisation/article/?article_id=${encodeURIComponent(data.article.id)}`;
    });
})();
