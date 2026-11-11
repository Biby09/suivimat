// Dialog « Archiver / Réactiver le client » (dashboard/organisation/clients/client.php) → api/client/archive-client.php.
// data-archived du formulaire : « 1 » = archiver, « 0 » = réactiver. La page est ensuite rechargée.
(() => {
    const modal = document.getElementById("client-archive-modal");
    if (!modal) {
        return;
    }

    const form = modal.querySelector("[data-role='archive-form']");
    const message = form.querySelector("[data-role='message']");
    const saveButton = form.querySelector("[data-role='save']"); // absent si le client a un prêt en cours

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
        if (saveButton) {
            saveButton.disabled = false;
        }
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (!saveButton) {
            return; // archivage impossible (prêt en cours) : rien à envoyer
        }

        const formData = new FormData();
        formData.append("client_id", form.dataset.clientId);
        formData.append("archived", form.dataset.archived);

        saveButton.disabled = true;
        showMessage("info", "Enregistrement…");
        let result;
        try {
            const response = await fetch("/api/client/archive-client.php", { method: "POST", body: formData });
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
