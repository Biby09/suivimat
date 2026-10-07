// Liste et ajout des clients (includes/client-setup.php)
(() => {
    const root = document.getElementById("client-setup");
    if (!root) {
        return;
    }

    const organisationId = root.dataset.organisationId;
    const form = root.querySelector("[data-role='form']");
    const message = root.querySelector("[data-role='message']");
    const submitButton = root.querySelector("[data-role='submit']");
    const list = root.querySelector("[data-role='list']");
    const empty = root.querySelector("[data-role='empty']");
    const count = root.querySelector("[data-role='count']");

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

    async function fetchJson(url, options = {}) {
        try {
            const response = await fetch(url, options);
            return await response.json();
        } catch {
            return { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }
    }

    function render(clients, highlightId = null) {
        list.innerHTML = "";
        empty.hidden = clients.length > 0;
        list.hidden = clients.length === 0;
        count.textContent = clients.length;

        clients.forEach((client) => {
            const item = document.createElement("li");
            item.className = "list-group-item";
            if (client.id === highlightId) {
                item.classList.add("list-group-item-success");
            }
            item.textContent = `${client.lastname} ${client.firstname}`;
            list.appendChild(item);
        });
    }

    async function loadClients(highlightId = null) {
        const data = await fetchJson(`/api/client/get-clients.php?organisation_id=${encodeURIComponent(organisationId)}`);
        if (!data.success) {
            showMessage("danger", data.message || "Impossible de charger les clients.");
            return null;
        }
        render(data.clients, highlightId);
        return data.clients;
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        form.classList.add("was-validated");
        if (!form.checkValidity()) {
            return;
        }

        const formData = new FormData(form);
        formData.append("organisation_id", organisationId);

        submitButton.disabled = true;
        const data = await fetchJson("/api/client/create-client.php", { method: "POST", body: formData });
        submitButton.disabled = false;

        if (!data.success) {
            showMessage("danger", data.message || "Erreur lors de l'ajout du client.");
            return;
        }

        showMessage("success", `${formData.get("firstname")} ${formData.get("lastname")} a été ajouté.`);
        form.reset();
        form.classList.remove("was-validated");
        // Prêt pour la saisie suivante
        form.elements["firstname"].focus();

        const clients = await loadClients(data.client_id);
        if (clients) {
            document.dispatchEvent(new CustomEvent("clients-changed", { detail: { clients } }));
        }
    });

    loadClients();
})();
