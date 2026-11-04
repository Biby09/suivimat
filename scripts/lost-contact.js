// Formulaire de la page publique d'un objet perdu (includes/lost-article-public.php) :
// le message est envoyé par e-mail aux membres de l'organisation (api/article/contact-lost.php).
(() => {
    const form = document.querySelector("[data-role='lost-contact-form']");
    if (!form) {
        return;
    }

    const message = form.querySelector("[data-role='message']");
    const saveButton = form.querySelector("[data-role='save']");
    const sent = document.querySelector("[data-role='sent']");

    function showMessage(type, text) {
        message.innerHTML = "";
        const alert = document.createElement("div");
        alert.className = `alert alert-${type} py-2`;
        alert.textContent = text;
        message.appendChild(alert);
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        form.classList.add("was-validated");
        if (!form.checkValidity()) {
            form.querySelector(":invalid:not(form)")?.focus();
            return;
        }

        const formData = new FormData(form);
        formData.append("code", form.dataset.code);

        saveButton.disabled = true;
        showMessage("info", "Envoi en cours…");
        let result;
        try {
            const response = await fetch("/api/article/contact-lost.php", { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Vérifiez votre connexion." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage("danger", result.message || "Le message n'a pas pu être envoyé.");
            return;
        }

        // Envoyé : le formulaire laisse place au remerciement
        form.hidden = true;
        sent.hidden = false;
        sent.scrollIntoView({ behavior: "smooth", block: "center" });
    });
})();
