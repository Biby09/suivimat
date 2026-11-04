// Dialog de prêt de la fiche d'un objet (dashboard/organisation/article/index.php), deux modes :
// - « Prêter » (bouton principal) : nouveau prêt → api/loan/create-loan.php ;
// - « Modifier » (prêt en cours dans l'historique, attributs data-loan-id…) : correction du client, des dates,
//   prolongation → api/loan/update-loan.php.
// Client existant ou nouveau, date de sortie (aujourd'hui par défaut), date de retour prévue ou « indéterminée ».
// Après l'enregistrement, la page est rechargée (statut et historique à jour).
(() => {
    const modal = document.getElementById("article-loan-modal");
    if (!modal) {
        return;
    }

    const form = modal.querySelector("[data-role='loan-form']");
    const message = form.querySelector("[data-role='message']");
    const clientPicker = window.clientPicker(form); // scripts/client-picker.js
    const startInput = form.elements["start_date"];
    const dueInput = form.elements["due_date"];
    const dueUnknown = form.querySelector("[data-role='due-unknown']");
    const saveButton = form.querySelector("[data-role='save']");
    const title = modal.querySelector(".modal-title");
    const createTitle = title.textContent;  // « Prêter « nom de l'objet » », écrit par la page

    let loanId = null; // prêt modifié ; null : nouveau prêt

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

    // Date du jour de l'appareil + `days` jours, au format AAAA-MM-JJ (celui des <input type="date">).
    // toISOString() donnerait la date en UTC : la veille entre minuit et 1-2 h du matin en Suisse.
    function today(days = 0) {
        const date = new Date();
        date.setDate(date.getDate() + days); // le 30 + 5 jours passe correctement au mois suivant
        // padStart(2, "0") : « 4 » → « 04 » ; getMonth() commence à 0 (janvier = 0)
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    }

    // Durée de prêt par défaut de l'organisation (Paramètres › Prêts) ; 0 = indéterminée
    const defaultDays = parseInt(form.dataset.defaultDays) || 0;

    // Retour indéterminé : champ date vidé et désactivé (un champ désactivé n'est pas envoyé avec le formulaire)
    function updateDueDate() {
        const unknown = dueUnknown.checked;
        dueInput.disabled = unknown;
        dueInput.required = !unknown;
        if (unknown) {
            dueInput.value = "";
        }
        // Le retour ne peut pas être avant la sortie : le calendrier grise les jours précédents
        dueInput.min = startInput.value;
    }

    // À chaque ouverture. e.relatedTarget : bouton qui a ouvert le dialog.
    // Bouton « Modifier » d'un prêt (data-loan-id) : formulaire pré-rempli avec ses valeurs ;
    // sinon : formulaire vierge, sortie aujourd'hui, retour indéterminé.
    function reset(e) {
        // dataset : attributs data-* du bouton ; data-loan-id → dataset.loanId (tirets → majuscule)
        const loan = e.relatedTarget?.dataset.loanId ? e.relatedTarget.dataset : null;
        loanId = loan?.loanId ?? null;

        form.reset();
        form.classList.remove("was-validated");
        showMessage(null);
        saveButton.disabled = false;
        title.textContent = loan ? "Modifier le prêt en cours" : createTitle;
        saveButton.textContent = loan ? "Enregistrer" : "Prêter";

        if (loan) {
            clientPicker.select(loan.clientId);
            startInput.value = loan.startDate;
            dueUnknown.checked = !loan.dueDate;  // pas de date prévue → « indéterminée » cochée
            dueInput.value = loan.dueDate;
        } else {
            clientPicker.select(""); // aucun client choisi (« + Nouveau client » s'il n'y en a aucun)
            startInput.value = today();
            // Durée par défaut : retour prévu pré-rempli (aujourd'hui + N jours) ; sinon « indéterminée »
            dueUnknown.checked = defaultDays === 0;
            dueInput.value = defaultDays ? today(defaultDays) : "";
        }
        updateDueDate();
    }

    async function save() {
        form.classList.add("was-validated");
        // Le navigateur ne vérifie pas « retour ≥ sortie » tout seul : setCustomValidity rend le champ invalide
        dueInput.setCustomValidity(!dueInput.disabled && dueInput.value && dueInput.value < startInput.value ? "before-start" : "");
        if (!form.checkValidity()) {
            form.querySelector(":invalid")?.focus();
            return;
        }

        const formData = new FormData(form);
        formData.append("article_id", form.dataset.articleId);
        if (loanId) {
            formData.append("loan_id", loanId);
        }

        saveButton.disabled = true;
        showMessage("info", "Enregistrement du prêt…");
        let result;
        try {
            const url = loanId ? "/api/loan/update-loan.php" : "/api/loan/create-loan.php";
            const response = await fetch(url, { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage("danger", result.message || "Erreur lors de l'enregistrement du prêt.");
            return;
        }

        window.location.reload();
    }

    dueUnknown.addEventListener("change", () => {
        updateDueDate();
        if (!dueUnknown.checked) {
            dueInput.focus();
        }
    });
    startInput.addEventListener("change", updateDueDate);
    // Une date corrigée efface l'erreur « retour avant la sortie » posée par setCustomValidity
    dueInput.addEventListener("input", () => dueInput.setCustomValidity(""));
    modal.addEventListener("show.bs.modal", reset);
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        save();
    });
})();
