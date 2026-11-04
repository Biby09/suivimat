// Page « Mon compte » (dashboard/account/index.php).
// Chaque formulaire [data-role='account-form'] est envoyé à son API (data-url) ; succès :
// - data-success : message affiché dans la carte (data-reset : formulaire vidé, ex. mots de passe) ;
// - sinon, la réponse de l'API indique où aller (redirect), ex. suppression du compte.
(() => {
    function showMessage(form, type, text) {
        const message = form.querySelector("[data-role='message']");
        message.innerHTML = "";
        if (!text) {
            return;
        }
        const alert = document.createElement("div");
        alert.className = `alert alert-${type} py-2`;
        alert.textContent = text;
        message.appendChild(alert);
    }

    // ---- Informations personnelles : mot de passe demandé si l'e-mail change ----
    const emailInput = document.getElementById("account-email");
    const emailPassword = document.querySelector("[data-role='email-password']");
    if (emailInput) {
        emailInput.addEventListener("input", () => {
            const changed = emailInput.value.trim().toLowerCase() !== emailInput.dataset.original.toLowerCase();
            emailPassword.hidden = !changed;
            emailPassword.querySelector("input").required = changed;
        });
    }

    // ---- Nouveau mot de passe : règles cochées pendant la saisie, confirmation identique ----
    const newPassword = document.querySelector("[data-role='new-password']");
    const confirmPassword = document.querySelector("[data-role='confirm-password']");
    const rules = [...document.querySelectorAll("[data-rule]")];

    function checkPassword() {
        let allValid = true;
        rules.forEach((rule) => {
            // new RegExp(texte) : l'expression régulière est écrite dans l'attribut data-rule (mêmes règles que le serveur)
            const valid = new RegExp(rule.dataset.rule).test(newPassword.value);
            allValid = allValid && valid;
            rule.classList.toggle("text-success", valid);
            rule.classList.toggle("text-muted", !valid);
            rule.querySelector("[data-role='rule-icon']").textContent = valid ? "✓" : "○";
        });
        // setCustomValidity("…") rend le champ invalide (envoi bloqué) ; "" le rend valide
        newPassword.setCustomValidity(allValid ? "" : "rules");
        confirmPassword.setCustomValidity(confirmPassword.value === newPassword.value ? "" : "mismatch");
    }

    if (newPassword) {
        newPassword.addEventListener("input", checkPassword);
        confirmPassword.addEventListener("input", checkPassword);
        // Afficher les mots de passe : type « password » (points) ↔ « text » (en clair)
        document.querySelector("[data-role='show-passwords']").addEventListener("change", (e) => {
            newPassword.form.querySelectorAll("input[name$='password']").forEach((input) => {
                input.type = e.target.checked ? "text" : "password";
            });
        });
    }

    // ---- Envoi des formulaires ----
    document.querySelectorAll("[data-role='account-form']").forEach((form) => {
        const saveButton = form.querySelector("[data-role='save']");

        // Dialog de suppression : vidé à chaque ouverture
        form.closest(".modal")?.addEventListener("show.bs.modal", () => {
            form.reset();
            form.classList.remove("was-validated");
            showMessage(form, null);
            saveButton.disabled = false;
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (form.contains(newPassword)) {
                checkPassword();
            }
            form.classList.add("was-validated");
            if (!form.checkValidity()) {
                form.querySelector(":invalid:not(form)")?.focus();
                return;
            }

            saveButton.disabled = true;
            showMessage(form, "info", "Enregistrement…");
            let result;
            try {
                const response = await fetch(form.dataset.url, { method: "POST", body: new FormData(form) });
                result = await response.json();
            } catch {
                result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
            }
            saveButton.disabled = false;

            if (!result.success) {
                showMessage(form, "danger", result.message || "Erreur lors de l'enregistrement.");
                return;
            }
            if (result.redirect) {
                window.location.href = result.redirect;
                return;
            }

            showMessage(form, "success", form.dataset.success);
            form.classList.remove("was-validated");
            if (form.hasAttribute("data-reset")) {
                form.reset();
                checkPassword();
            }
            // E-mail enregistré : il devient la nouvelle adresse de référence, le mot de passe n'est plus demandé
            if (form.contains(emailInput)) {
                emailInput.dataset.original = emailInput.value.trim();
                emailPassword.hidden = true;
                emailPassword.querySelector("input").required = false;
                emailPassword.querySelector("input").value = "";
            }
        });
    });
})();
