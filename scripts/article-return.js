// Dialog « Enregistrer un retour » de la fiche d'un objet (dashboard/organisation/article/index.php).
// Date de retour (aujourd'hui par défaut), état de l'objet ; « Dégâts constatés » : description obligatoire + photos.
// Enregistrement : api/loan/return-loan.php, puis rechargement de la page (statut « Disponible », historique à jour).
(() => {
    const modal = document.getElementById("article-return-modal");
    if (!modal) {
        return; // objet pas emprunté : pas de dialog de retour sur la page
    }

    const form = modal.querySelector("[data-role='return-form']");
    const message = form.querySelector("[data-role='message']");
    const endInput = form.elements["end_date"];
    const damagedRadio = form.querySelector("#return-damaged");          // absent sans prêt en cours
    const damageSection = form.querySelector("[data-role='damage']");
    const descriptionInput = form.elements["damage_description"];
    const photosContainer = form.querySelector("[data-role='photos']");
    const photoInput = form.querySelector("[data-role='photo-input']");
    const saveButton = form.querySelector("[data-role='save']");
    const startDate = form.dataset.startDate; // date de sortie du prêt (AAAA-MM-JJ), vide sans prêt en cours
    const MAX_PHOTOS = 10;

    let photos = []; // { blob, url }

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

    // « Dégâts constatés » : description affichée et obligatoire
    function updateDamage() {
        if (!damagedRadio) {
            return;
        }
        const damaged = damagedRadio.checked;
        damageSection.hidden = !damaged;
        descriptionInput.required = damaged;
        if (damaged) {
            descriptionInput.focus();
        }
    }

    function renderPhotos() {
        if (!photosContainer) {
            return;
        }
        photosContainer.innerHTML = "";
        photos.forEach((photo, index) => {
            const wrapper = document.createElement("div");
            wrapper.className = "position-relative";

            const img = document.createElement("img");
            img.src = photo.url;
            img.alt = `Photo ${index + 1} des dégâts`;
            img.width = 88;
            img.height = 88;
            img.className = "rounded border object-fit-cover";

            const remove = document.createElement("button");
            remove.type = "button";
            remove.className = "btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-1 lh-sm";
            remove.setAttribute("aria-label", `Retirer la photo ${index + 1}`);
            remove.textContent = "×";
            remove.addEventListener("click", () => {
                URL.revokeObjectURL(photo.url);
                photos.splice(photos.indexOf(photo), 1);
                renderPhotos();
            });

            wrapper.append(img, remove);
            photosContainer.appendChild(wrapper);
        });
        photoInput.disabled = photos.length >= MAX_PHOTOS;
    }

    async function addPhotos(files) {
        const accepted = [...files].filter((file) => file.type.startsWith("image/"));
        const room = MAX_PHOTOS - photos.length;
        if (accepted.length > room) {
            showMessage("warning", `Vous pouvez ajouter au maximum ${MAX_PHOTOS} photos.`);
        }

        photoInput.disabled = true;
        for (const file of accepted.slice(0, room)) {
            const blob = await window.resizePhoto(file); // scripts/resize-photo.js
            photos.push({ blob, url: URL.createObjectURL(blob) });
        }
        photoInput.value = "";
        renderPhotos();
    }

    // À chaque ouverture : retour aujourd'hui, aucun dégât, pas de photo
    function reset() {
        form.reset();
        form.classList.remove("was-validated");
        showMessage(null);
        saveButton.disabled = false;
        endInput.value = today();
        // Le calendrier grise les jours avant la sortie et après aujourd'hui
        endInput.min = startDate;
        endInput.max = today();
        photos.forEach((photo) => URL.revokeObjectURL(photo.url));
        photos = [];
        renderPhotos();
        updateDamage();
    }

    async function save() {
        form.classList.add("was-validated");
        // Retour avant la sortie : refusé (le navigateur ne compare pas deux champs tout seul)
        endInput.setCustomValidity(startDate && endInput.value && endInput.value < startDate ? "before-start" : "");
        if (!form.checkValidity()) {
            form.querySelector(":invalid:not(form, fieldset)")?.focus();
            return;
        }

        // FormData(form) : end_date, damaged (bouton radio coché) et damage_description
        const formData = new FormData(form);
        formData.append("article_id", form.dataset.articleId);
        if (damagedRadio?.checked) {
            photos.forEach((photo, index) => formData.append("photos[]", photo.blob, `degat-${index + 1}.jpg`));
        }

        saveButton.disabled = true;
        showMessage("info", "Enregistrement du retour…");
        let result;
        try {
            const response = await fetch("/api/loan/return-loan.php", { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage("danger", result.message || "Erreur lors de l'enregistrement du retour.");
            return;
        }

        window.location.reload();
    }

    // Un écouteur sur chacun des deux boutons radio « État de l'objet »
    form.querySelectorAll("[name='damaged']").forEach((radio) => radio.addEventListener("change", updateDamage));
    endInput.addEventListener("input", () => endInput.setCustomValidity(""));
    photoInput?.addEventListener("change", () => addPhotos(photoInput.files));
    modal.addEventListener("show.bs.modal", reset);
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        save();
    });
})();
