// Dialogs « Nouveau client » (page Clients) et « Modifier le client » (page d'un client).
// Chaque formulaire [data-role='client-form'] indique :
//   data-url : API à appeler (api/client/create-client.php ou update-client.php) ;
//   data-after="open" : après l'enregistrement, ouvrir la page du client (client_id renvoyé par l'API) ;
//                       sinon la page est rechargée.
// Les champs cachés du formulaire (organisation_id ou client_id) sont envoyés avec prénom et nom.
document.querySelectorAll("[data-role='client-form']").forEach((form) => {
    const modal = form.closest(".modal");
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

    // À chaque ouverture : valeurs d'origine (une modification annulée est oubliée), curseur dans le prénom
    modal.addEventListener("show.bs.modal", () => {
        form.reset();
        form.classList.remove("was-validated");
        showMessage(null);
        saveButton.disabled = false;
    });
    modal.addEventListener("shown.bs.modal", () => form.elements["firstname"].focus());

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        form.classList.add("was-validated");
        if (!form.checkValidity()) {
            form.querySelector(":invalid:not(form)")?.focus();
            return;
        }

        saveButton.disabled = true;
        showMessage("info", "Enregistrement…");
        let result;
        try {
            const response = await fetch(form.dataset.url, { method: "POST", body: new FormData(form) });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage("danger", result.message || "Erreur lors de l'enregistrement.");
            return;
        }

        if (form.dataset.after === "open") {
            window.location.href = `/dashboard/organisation/clients/client.php?client_id=${encodeURIComponent(result.client_id)}`;
        } else {
            window.location.reload();
        }
    });
});
