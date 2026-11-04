// Mode « perdu » de la fiche d'un objet (dashboard/organisation/article/index.php) :
// - dialog « Marquer comme perdu » (date de la perte, remarque) → api/loss/mark-lost.php ;
// - dialog « Objet retrouvé » (confirmation) → api/loss/mark-found.php.
// Après l'enregistrement, la page est rechargée (statut, bandeau et boutons à jour).
(() => {
    function showMessage(container, type, text) {
        container.innerHTML = "";
        if (!text) {
            return;
        }
        const alert = document.createElement("div");
        alert.className = `alert alert-${type} py-2`;
        alert.textContent = text;
        container.appendChild(alert);
    }

    // Date du jour de l'appareil, AAAA-MM-JJ (même méthode que scripts/article-loan.js)
    function today() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
    }

    // Envoie le formulaire à l'API ; succès → rechargement de la page, échec → message dans le dialog
    async function submitForm(form, url, waitingText) {
        const message = form.querySelector("[data-role='message']");
        const saveButton = form.querySelector("[data-role='save']");
        const formData = new FormData(form);
        formData.append("article_id", form.dataset.articleId);

        saveButton.disabled = true;
        showMessage(message, "info", waitingText);
        let result;
        try {
            const response = await fetch(url, { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage(message, "danger", result.message || "Erreur lors de l'enregistrement.");
            return;
        }
        window.location.reload();
    }

    // ---- Marquer comme perdu (objet disponible ou emprunté) ----
    const lostModal = document.getElementById("article-lost-modal");
    if (lostModal) {
        const form = lostModal.querySelector("[data-role='lost-form']");
        const dateInput = form.elements["loss_date"];
        const loanStart = form.dataset.loanStart; // début du prêt en cours ; vide si l'objet n'est pas prêté

        lostModal.addEventListener("show.bs.modal", () => {
            form.reset();
            form.classList.remove("was-validated");
            showMessage(form.querySelector("[data-role='message']"), null);
            form.querySelector("[data-role='save']").disabled = false;
            dateInput.value = today();
            dateInput.min = loanStart; // le prêt est clôturé à cette date : pas avant son début
            dateInput.max = today();
        });

        dateInput.addEventListener("input", () => dateInput.setCustomValidity(""));
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            form.classList.add("was-validated");
            dateInput.setCustomValidity(loanStart && dateInput.value && dateInput.value < loanStart ? "before-loan" : "");
            if (!form.checkValidity()) {
                form.querySelector(":invalid:not(form)")?.focus();
                return;
            }
            submitForm(form, "/api/loss/mark-lost.php", "Enregistrement de la perte…");
        });
    }

    // ---- Objet retrouvé (objet perdu) ----
    const foundModal = document.getElementById("article-found-modal");
    if (foundModal) {
        const form = foundModal.querySelector("[data-role='found-form']");
        foundModal.addEventListener("show.bs.modal", () => {
            showMessage(form.querySelector("[data-role='message']"), null);
            form.querySelector("[data-role='save']").disabled = false;
        });
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            submitForm(form, "/api/loss/mark-found.php", "Enregistrement…");
        });
    }
})();
