// Dialog « Modifier l'objet » de la fiche d'un objet (dashboard/organisation/article/index.php).
// Données initiales : balise <script type="application/json" id="article-edit-data"> de la page.
// Enregistrement : api/article/configure-article.php, puis rechargement de la page pour afficher les nouvelles valeurs.
(() => {
    const modal = document.getElementById("article-edit-modal");
    if (!modal) {
        return;
    }

    const data = JSON.parse(document.getElementById("article-edit-data").textContent);
    const form = modal.querySelector("[data-role='edit-form']");
    const message = form.querySelector("[data-role='message']");
    const nameInput = form.elements["name"];
    const categorySelect = form.elements["category_id"];
    const fieldsContainer = form.querySelector("[data-role='fields']");
    const photosContainer = form.querySelector("[data-role='photos']");
    const photoInput = form.querySelector("[data-role='photo-input']");
    const saveButton = form.querySelector("[data-role='save']");
    const MAX_NEW_PHOTOS = 10; // limite de l'API par enregistrement

    let fieldValues = {};          // { id_champ: valeur } ; garde les saisies quand on change de catégorie
    let deletedIds = new Set();    // photos existantes à supprimer à l'enregistrement
    let newPhotos = [];            // nouvelles photos : { blob, url }

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

    // ---- Catégories ----

    const categoryById = (id) => data.categories.find((c) => c.id === id);

    // « Parent › Enfant » (garde-fou : 20 niveaux au maximum)
    function categoryLabel(category) {
        const names = [];
        for (let c = category; c && names.length < 20; c = categoryById(c.parent_id)) {
            names.unshift(c.name);
        }
        return names.join(" › ");
    }

    function renderCategoryOptions() {
        categorySelect.length = 1; // garde l'option « Aucune catégorie »
        data.categories
            .map((category) => ({ id: category.id, label: categoryLabel(category) }))
            // localeCompare : tri alphabétique qui respecte les accents (« É » avec les « E »)
            .sort((a, b) => a.label.localeCompare(b.label, "fr"))
            .forEach(({ id, label }) => categorySelect.add(new Option(label, id)));
    }

    // Mémorise ce qui est tapé dans les champs affichés avant de les remplacer
    function rememberFieldValues() {
        fieldsContainer.querySelectorAll("input").forEach((input) => {
            fieldValues[input.dataset.fieldId] = input.value;
        });
    }

    // Champs personnalisés de la catégorie choisie et de ses parentes (scripts/category-fields.js)
    function renderFields() {
        fieldsContainer.innerHTML = "";

        window.categoryFields(Number(categorySelect.value), data.categories).forEach((field) => {
            const wrapper = document.createElement("div");
            wrapper.className = "mb-3";

            const label = document.createElement("label");
            label.className = "form-label";
            label.htmlFor = `article-edit-field-${field.id}`;
            label.textContent = field.name;
            if (field.inheritedFrom) {
                // Champ hérité d'une catégorie parente : son nom en gris après le libellé
                const from = document.createElement("span");
                from.className = "text-muted small ms-1";
                from.textContent = `(${field.inheritedFrom})`;
                label.appendChild(from);
            }

            const input = document.createElement("input");
            input.type = "text";
            input.className = "form-control";
            input.id = label.htmlFor;
            input.name = `fields[${field.id}]`; // PHP reçoit $_POST['fields'] = [id_champ => valeur]
            input.dataset.fieldId = field.id;
            input.maxLength = 200;
            input.value = fieldValues[field.id] ?? "";
            // Valeur par défaut : affichée en gris dans le champ vide (pas copiée dans l'objet ; laissé vide,
            // le champ continue d'afficher la valeur par défaut de la catégorie, même si elle change plus tard)
            input.placeholder = field.default ? `Par défaut : ${field.default}` : "";

            wrapper.append(label, input);
            fieldsContainer.appendChild(wrapper);
        });
    }

    // ---- Photos ----

    function thumbnail(url, alt, faded = false) {
        const wrapper = document.createElement("div");
        wrapper.className = "position-relative";
        const img = document.createElement("img");
        img.src = url;
        img.alt = alt;
        img.width = 88;
        img.height = 88;
        img.className = `rounded border object-fit-cover${faded ? " opacity-25" : ""}`;
        wrapper.appendChild(img);
        return wrapper;
    }

    function cornerButton(label, ariaLabel, className, onClick) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `btn btn-sm ${className} position-absolute top-0 end-0 m-1 py-0 px-1 lh-sm`;
        button.setAttribute("aria-label", ariaLabel);
        button.textContent = label;
        button.addEventListener("click", onClick);
        return button;
    }

    // Photos enregistrées (× pour retirer, ↺ pour garder finalement), puis nouvelles photos
    function renderPhotos() {
        photosContainer.innerHTML = "";

        data.images.forEach((image, index) => {
            const deleted = deletedIds.has(image.id);
            const item = thumbnail(image.url, `Photo ${index + 1}${deleted ? " (sera supprimée)" : ""}`, deleted);
            item.appendChild(deleted
                ? cornerButton("↺", `Garder la photo ${index + 1}`, "btn-light border", () => { deletedIds.delete(image.id); renderPhotos(); })
                : cornerButton("×", `Retirer la photo ${index + 1}`, "btn-danger", () => { deletedIds.add(image.id); renderPhotos(); }));
            photosContainer.appendChild(item);
        });

        newPhotos.forEach((photo, index) => {
            const item = thumbnail(photo.url, `Nouvelle photo ${index + 1}`);
            item.querySelector("img").classList.add("border-primary", "border-2");
            item.appendChild(cornerButton("×", `Retirer la nouvelle photo ${index + 1}`, "btn-danger", () => {
                URL.revokeObjectURL(photo.url); // libère l'aperçu en mémoire
                newPhotos.splice(newPhotos.indexOf(photo), 1);
                renderPhotos();
            }));
            photosContainer.appendChild(item);
        });

        if (!data.images.length && !newPhotos.length) {
            photosContainer.innerHTML = '<p class="small text-muted mb-0">Aucune photo.</p>';
        }
        photoInput.disabled = newPhotos.length >= MAX_NEW_PHOTOS;
    }

    async function addPhotos(files) {
        const accepted = [...files].filter((file) => file.type.startsWith("image/"));
        const room = MAX_NEW_PHOTOS - newPhotos.length;
        if (accepted.length > room) {
            showMessage("warning", `Vous pouvez ajouter au maximum ${MAX_NEW_PHOTOS} photos à la fois.`);
        }

        photoInput.disabled = true;
        for (const file of accepted.slice(0, room)) {
            const blob = await window.resizePhoto(file); // scripts/resize-photo.js
            newPhotos.push({ blob, url: URL.createObjectURL(blob) });
        }
        photoInput.value = "";
        renderPhotos();
    }

    // ---- Ouverture et enregistrement ----

    // À chaque ouverture : on repart des valeurs enregistrées (une modification annulée est oubliée)
    function reset() {
        showMessage(null);
        form.classList.remove("was-validated");
        nameInput.value = data.name ?? "";
        categorySelect.value = data.categoryId ?? "";
        // { ...objet } : copie, pour ne pas modifier les valeurs d'origine en tapant
        fieldValues = { ...data.fieldValues };
        deletedIds = new Set();
        newPhotos.forEach((photo) => URL.revokeObjectURL(photo.url));
        newPhotos = [];
        photoInput.value = "";
        renderFields();
        renderPhotos();
    }

    async function save() {
        form.classList.add("was-validated");
        if (!form.checkValidity()) {
            return;
        }

        // FormData(form) : name, category_id et fields[…] (champs ayant un attribut name)
        const formData = new FormData(form);
        formData.append("organisation_id", data.organisationId);
        formData.append("code", data.code);
        deletedIds.forEach((id) => formData.append("delete_photos[]", id));
        newPhotos.forEach((photo, index) => formData.append("photos[]", photo.blob, `photo-${index + 1}.jpg`));

        saveButton.disabled = true;
        showMessage("info", "Enregistrement en cours…");
        let result;
        try {
            const response = await fetch("/api/article/configure-article.php", { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            saveButton.disabled = false;
            showMessage("danger", result.message || "Erreur lors de l'enregistrement.");
            return;
        }

        // Rechargement : la fiche (carrousel, chemin d'accès, champs…) affiche les nouvelles valeurs
        window.location.reload();
    }

    categorySelect.addEventListener("change", () => {
        rememberFieldValues();
        renderFields();
    });
    photoInput.addEventListener("change", () => addPhotos(photoInput.files));
    modal.addEventListener("show.bs.modal", reset);
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        save();
    });

    renderCategoryOptions();
})();
