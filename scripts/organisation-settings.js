// Page « Paramètres de l'organisation » (dashboard/organisation/settings/index.php).
// - Formulaires [data-role='settings-form'] : envoyés à leur API (data-url) avec organisation_id ;
//   succès → message (data-success), rechargement (data-reload, ex. liste des membres) ou redirection (réponse de l'API).
// - Boutons [data-member-action] : retirer / quitter (remove), annuler une invitation (cancel-invitation), après confirmation.
// Après un rechargement, le message de succès est réaffiché dans la bonne section grâce à sessionStorage.
(() => {
    const page = document.getElementById("organisation-settings");
    if (!page) {
        return;
    }
    const organisationId = page.dataset.organisationId;
    const FLASH_KEY = "organisation-settings-flash";

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

    async function post(url, formData) {
        formData.append("organisation_id", organisationId);
        try {
            const response = await fetch(url, { method: "POST", body: formData });
            return await response.json();
        } catch {
            return { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }
    }

    // Rechargement de la page en gardant un message à afficher ensuite dans la section `sectionId`.
    // sessionStorage : petite mémoire de l'onglet, qui survit au rechargement (et disparaît à la fermeture de l'onglet)
    function reloadWithMessage(sectionId, text) {
        sessionStorage.setItem(FLASH_KEY, JSON.stringify({ sectionId, text }));
        window.location.reload();
    }

    // ---- Formulaires ----
    document.querySelectorAll("[data-role='settings-form']").forEach((form) => {
        const message = form.querySelector("[data-role='message']");
        const saveButton = form.querySelector("[data-role='save']");
        const section = form.closest(".settings-section") ?? form;

        // Confirmation par le nom (suppression de l'organisation) : le nom tapé doit être exactement celui attendu
        const expected = form.querySelector("[data-expected]");
        expected?.addEventListener("input", () => {
            expected.setCustomValidity(expected.value.trim() === expected.dataset.expected ? "" : "mismatch");
        });

        // Dialog : vidé à chaque ouverture
        form.closest(".modal")?.addEventListener("show.bs.modal", () => {
            form.reset();
            form.classList.remove("was-validated");
            showMessage(message, null);
            expected?.setCustomValidity("mismatch");
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            form.classList.add("was-validated");
            if (!form.checkValidity()) {
                form.querySelector(":invalid:not(form)")?.focus();
                return;
            }

            saveButton.disabled = true;
            showMessage(message, "info", "Enregistrement…");
            const result = await post(form.dataset.url, new FormData(form));
            saveButton.disabled = false;

            if (!result.success) {
                showMessage(message, "danger", result.message || "Erreur lors de l'enregistrement.");
                return;
            }
            if (result.redirect) {
                window.location.href = result.redirect;
                return;
            }
            const text = result.message || form.dataset.success || "Enregistré.";
            if (form.hasAttribute("data-reload")) {
                reloadWithMessage(section.id, text);
                return;
            }
            form.classList.remove("was-validated");
            showMessage(message, "success", text);
        });
    });

    // ---- Membres et invitations ----
    const membersMessage = page.querySelector("[data-role='members-message']");
    document.querySelectorAll("[data-member-action]").forEach((button) => {
        button.addEventListener("click", async () => {
            // confirm() : fenêtre « OK / Annuler » du navigateur ; false si l'utilisateur annule
            if (!confirm(button.dataset.confirm)) {
                return;
            }

            const formData = new FormData();
            let url;
            if (button.dataset.memberAction === "remove") {
                url = "/api/organisation/remove-member.php";
                formData.append("user_id", button.dataset.userId);
            } else {
                url = "/api/organisation/cancel-invitation.php";
                formData.append("invitation_id", button.dataset.invitationId);
            }

            button.disabled = true;
            const result = await post(url, formData);
            if (!result.success) {
                button.disabled = false;
                showMessage(membersMessage, "danger", result.message || "Erreur lors de l'opération.");
                return;
            }
            if (result.redirect) {
                window.location.href = result.redirect; // l'utilisateur a quitté l'organisation
                return;
            }
            reloadWithMessage("members", button.dataset.memberAction === "remove" ? "Membre retiré." : "Invitation annulée.");
        });
    });

    // ---- Message gardé avant un rechargement ----
    const flash = JSON.parse(sessionStorage.getItem(FLASH_KEY) ?? "null");
    if (flash) {
        sessionStorage.removeItem(FLASH_KEY);
        const section = document.getElementById(flash.sectionId);
        const container = section?.querySelector("[data-role='members-message'], [data-role='message']");
        if (container) {
            showMessage(container, "success", flash.text);
            section.scrollIntoView({ block: "start" });
        }
    }
})();
