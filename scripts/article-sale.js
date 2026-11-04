// Dialog « Marquer comme vendu » de la fiche d'un objet (dashboard/organisation/article/index.php).
// Acheteur : l'emprunteur actuel si l'objet est prêté (modifiable), sinon à choisir ou à créer ; date de vente (aujourd'hui).
// Enregistrement : api/sale/sell-article.php, puis rechargement de la page (statut « Vendu », acheteur affiché).
(() => {
    const modal = document.getElementById("article-sale-modal");
    if (!modal) {
        return; // objet ni disponible ni emprunté : pas de vente possible
    }

    const form = modal.querySelector("[data-role='sale-form']");
    const message = form.querySelector("[data-role='message']");
    const dateInput = form.elements["sale_date"];
    const saveButton = form.querySelector("[data-role='save']");
    const clientPicker = window.clientPicker(form); // scripts/client-picker.js
    const borrowerId = form.dataset.borrowerId;     // emprunteur actuel ; vide si l'objet n'est pas prêté
    const loanStart = form.dataset.loanStart;       // début du prêt en cours (AAAA-MM-JJ) ; vide sinon

    function showMessage(type, text) {
        message.innerHTML = "";
        if (!text) {
            return;
        }
        const alert = document.createElement("div");
        alert.className = `alert alert-${type} py-2`;
        alert.textContent = text;
        message.appendChild(alert);
    }

    // Date du jour de l'appareil, AAAA-MM-JJ (même méthode que scripts/article-loan.js)
    function today() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
    }

    // À chaque ouverture : emprunteur actuel (ou aucun client), vente aujourd'hui
    function reset() {
        form.reset();
        form.classList.remove("was-validated");
        showMessage(null);
        saveButton.disabled = false;
        clientPicker.select(borrowerId);
        dateInput.value = today();
        // Objet prêté : la vente ne peut pas être avant le début du prêt (il est clôturé à cette date)
        dateInput.min = loanStart;
    }

    async function save() {
        form.classList.add("was-validated");
        // Le navigateur ne bloque pas l'envoi d'une date avant « min » tapée au clavier : vérification explicite
        dateInput.setCustomValidity(loanStart && dateInput.value && dateInput.value < loanStart ? "before-loan" : "");
        if (!form.checkValidity()) {
            form.querySelector(":invalid:not(form, fieldset)")?.focus();
            return;
        }

        const formData = new FormData(form);
        formData.append("article_id", form.dataset.articleId);

        saveButton.disabled = true;
        showMessage("info", "Enregistrement de la vente…");
        let result;
        try {
            const response = await fetch("/api/sale/sell-article.php", { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage("danger", result.message || "Erreur lors de l'enregistrement de la vente.");
            return;
        }

        window.location.reload();
    }

    dateInput.addEventListener("input", () => dateInput.setCustomValidity(""));
    modal.addEventListener("show.bs.modal", reset);
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        save();
    });
})();
