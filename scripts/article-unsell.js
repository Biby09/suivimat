// Dialog « Remettre disponible » d'un objet vendu (dashboard/organisation/article/index.php) → api/sale/cancel-sale.php.
// La vente est annulée (gardée dans la base) et l'objet redevient disponible ; la page est ensuite rechargée.
(() => {
    const modal = document.getElementById("article-unsell-modal");
    if (!modal) {
        return; // objet pas vendu : pas de dialog
    }

    const form = modal.querySelector("[data-role='unsell-form']");
    const message = form.querySelector("[data-role='message']");
    const saveButton = form.querySelector("[data-role='save']");

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

    modal.addEventListener("show.bs.modal", () => {
        showMessage(null);
        saveButton.disabled = false;
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append("article_id", form.dataset.articleId);

        saveButton.disabled = true;
        showMessage("info", "Enregistrement…");
        let result;
        try {
            const response = await fetch("/api/sale/cancel-sale.php", { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage("danger", result.message || "Erreur lors de l'enregistrement.");
            return;
        }
        window.location.reload();
    });
})();
