// Configuration des objets à la chaîne (includes/article-setup.php), basée sur le scanner (scripts/scanner.js)
(() => {
    const root = document.getElementById("article-setup");
    if (!root) {
        return;
    }

    const organisationId = root.dataset.organisationId;
    const scannerElement = root.querySelector("[data-scanner]");
    const queueList = root.querySelector("[data-role='queue']");
    const doneCount = root.querySelector("[data-role='done-count']");
    const message = root.querySelector("[data-role='message']");
    const empty = root.querySelector("[data-role='empty']");
    const form = root.querySelector("[data-role='form']");
    const currentCode = root.querySelector("[data-role='current-code']");
    const alreadyConfigured = root.querySelector("[data-role='already-configured']");
    const nameInput = form.elements["name"];
    const categorySelect = form.elements["category_id"];
    const fieldsContainer = root.querySelector("[data-role='fields']");
    const skipButton = root.querySelector("[data-role='skip']");
    const saveButton = root.querySelector("[data-role='save']");
    const photosContainer = root.querySelector("[data-role='photos']");
    const photoInput = root.querySelector("[data-role='photo-input']");
    const MAX_PHOTOS = 10;       // nouvelles photos par enregistrement (limite de l'API)
    const PHOTO_MAX_SIZE = 1600; // plus grand côté, en pixels

    const queue = [];          // articles en attente ; le premier est celui en cours
    const pending = new Set(); // codes en cours de recherche
    let categories = [];
    let configuredCount = 0;
    let newPhotos = [];        // photos à envoyer pour l'objet en cours : { blob, url }

    // Écran tactile : pas de focus automatique, le clavier masquerait la caméra
    const hasKeyboard = window.matchMedia("(pointer: fine)").matches;

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

    async function loadCategories() {
        const data = await fetchJson(`/api/category/get-categories.php?organisation_id=${encodeURIComponent(organisationId)}`);
        if (!data.success) {
            showMessage("danger", data.message || "Impossible de charger les catégories.");
            return;
        }

        setCategories(data.categories);
    }

    // Remplit la liste des catégories en gardant la sélection et les champs déjà saisis
    function setCategories(list) {
        categories = list;
        const selected = categorySelect.value;
        const values = Object.fromEntries([...fieldsContainer.querySelectorAll("input")].map((input) => [input.name.match(/\d+/)[0], input.value]));

        categorySelect.length = 1; // garde l'option « Aucune catégorie »
        categories.forEach((category) => {
            const parent = categories.find((c) => c.id === category.parent_id);
            categorySelect.add(new Option(parent ? `${parent.name} › ${category.name}` : category.name, category.id));
        });
        categorySelect.value = selected;
        if (queue.length) {
            renderFields({ ...queue[0].fields, ...values });
        }
    }

    // Champs personnalisés de la catégorie choisie, pré-remplis avec les valeurs de l'objet
    function renderFields(values = {}) {
        fieldsContainer.innerHTML = "";
        const category = categories.find((c) => String(c.id) === categorySelect.value);

        category?.fields.forEach((field) => {
            const id = `article-setup-field-${field.id}`;
            const wrapper = document.createElement("div");
            wrapper.className = "mb-3";

            const label = document.createElement("label");
            label.className = "form-label";
            label.htmlFor = id;
            label.textContent = field.name;

            const input = document.createElement("input");
            input.type = "text";
            input.className = "form-control";
            input.id = id;
            input.name = `fields[${field.id}]`;
            input.maxLength = 200;
            input.value = values[field.id] ?? "";

            wrapper.append(label, input);
            fieldsContainer.appendChild(wrapper);
        });
    }

    // Réduit une photo avant l'envoi (limite d'upload du serveur, données mobiles)
    async function resizePhoto(file) {
        try {
            const bitmap = await createImageBitmap(file, { imageOrientation: "from-image" });
            const ratio = Math.min(1, PHOTO_MAX_SIZE / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement("canvas");
            canvas.width = Math.round(bitmap.width * ratio);
            canvas.height = Math.round(bitmap.height * ratio);
            canvas.getContext("2d").drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            bitmap.close();
            return await new Promise((resolve, reject) => canvas.toBlob((blob) => (blob ? resolve(blob) : reject()), "image/jpeg", 0.85));
        } catch {
            return file; // navigateur incapable de réduire : le serveur s'en charge
        }
    }

    function photoThumbnail(url, alt) {
        const wrapper = document.createElement("div");
        wrapper.className = "position-relative";

        const img = document.createElement("img");
        img.src = url;
        img.alt = alt;
        img.width = 72;
        img.height = 72;
        img.className = "rounded border object-fit-cover";
        wrapper.appendChild(img);
        return wrapper;
    }

    // Photos déjà enregistrées, puis nouvelles photos (retirables avant l'envoi)
    function renderPhotos() {
        photosContainer.innerHTML = "";
        (queue[0]?.images ?? []).forEach((image, index) => {
            photosContainer.appendChild(photoThumbnail(image.url, `Photo ${index + 1} enregistrée`));
        });

        newPhotos.forEach((photo, index) => {
            const thumbnail = photoThumbnail(photo.url, `Nouvelle photo ${index + 1}`);
            const remove = document.createElement("button");
            remove.type = "button";
            remove.className = "btn btn-sm btn-danger position-absolute top-0 end-0 py-0 px-1 lh-sm";
            remove.setAttribute("aria-label", `Retirer la nouvelle photo ${index + 1}`);
            remove.textContent = "×";
            remove.addEventListener("click", () => {
                URL.revokeObjectURL(photo.url);
                newPhotos.splice(newPhotos.indexOf(photo), 1);
                renderPhotos();
            });
            thumbnail.appendChild(remove);
            photosContainer.appendChild(thumbnail);
        });

        photoInput.disabled = newPhotos.length >= MAX_PHOTOS;
    }

    function clearNewPhotos() {
        newPhotos.forEach((photo) => URL.revokeObjectURL(photo.url));
        newPhotos = [];
        photoInput.value = "";
    }

    async function addPhotos(files) {
        const accepted = [...files].filter((file) => file.type.startsWith("image/"));
        const room = MAX_PHOTOS - newPhotos.length;
        if (accepted.length > room) {
            showMessage("warning", `Vous pouvez ajouter au maximum ${MAX_PHOTOS} photos à la fois.`);
        }

        photoInput.disabled = true;
        for (const file of accepted.slice(0, room)) {
            const blob = await resizePhoto(file);
            newPhotos.push({ blob, url: URL.createObjectURL(blob) });
        }
        photoInput.value = "";
        renderPhotos();
    }

    function renderQueue() {
        queueList.innerHTML = "";
        queue.forEach((article, index) => {
            const badge = document.createElement("span");
            badge.className = `badge font-monospace ${index === 0 ? "text-bg-primary" : "text-bg-light border"}`;
            badge.textContent = article.code;
            queueList.appendChild(badge);
        });
        doneCount.textContent = `${configuredCount} configuré${configuredCount > 1 ? "s" : ""}`;
    }

    // Affiche le formulaire de l'objet en tête de file
    function renderCurrent() {
        renderQueue();
        const article = queue[0];

        empty.hidden = Boolean(article);
        form.hidden = !article;
        if (!article) {
            clearNewPhotos();
            return;
        }

        form.classList.remove("was-validated");
        currentCode.textContent = article.code;
        alreadyConfigured.hidden = article.status === "Non attribué";
        nameInput.value = article.name ?? "";
        categorySelect.value = article.category_id ?? "";
        renderFields(article.fields);
        clearNewPhotos();
        renderPhotos();
        if (hasKeyboard) {
            nameInput.focus({ preventScroll: true });
        }
    }

    async function addCode(code) {
        if (queue.some((article) => article.code === code) || pending.has(code)) {
            showMessage("warning", `Le code ${code} est déjà dans la liste.`);
            return;
        }

        pending.add(code);
        const data = await fetchJson(`/api/article/get-article.php?organisation_id=${encodeURIComponent(organisationId)}&code=${encodeURIComponent(code)}`);
        pending.delete(code);

        if (!data.success) {
            showMessage("danger", data.message || `Le code ${code} est introuvable.`);
            return;
        }

        showMessage(null);
        queue.push(data.article);
        if (queue.length === 1) {
            renderCurrent();
            // Mobile : le formulaire est sous la caméra, on l'amène à l'écran
            form.scrollIntoView({ behavior: "smooth", block: "nearest" });
        } else {
            renderQueue();
        }
    }

    async function saveCurrent() {
        form.classList.add("was-validated");
        if (!form.checkValidity()) {
            return;
        }

        const article = queue[0];
        const formData = new FormData(form);
        formData.append("organisation_id", organisationId);
        formData.append("code", article.code);
        newPhotos.forEach((photo, index) => formData.append("photos[]", photo.blob, `photo-${index + 1}.jpg`));

        saveButton.disabled = true;
        const data = await fetchJson("/api/article/configure-article.php", { method: "POST", body: formData });
        saveButton.disabled = false;

        if (!data.success) {
            showMessage("danger", data.message || "Erreur lors de l'enregistrement.");
            return;
        }

        // L'objet en cours a pu changer pendant la requête (« Retirer ») : on retire celui qui a été enregistré
        const index = queue.indexOf(article);
        if (index !== -1) {
            queue.splice(index, 1);
        }
        configuredCount++;
        showMessage("success", `${formData.get("name")} (${article.code}) enregistré.`);
        root.dispatchEvent(new CustomEvent("article-configured", { bubbles: true, detail: { code: article.code } }));
        renderCurrent();
        scrollAfterChange();
    }

    // File vide : retour à la caméra pour scanner la suite ; sinon, objet suivant
    function scrollAfterChange() {
        const target = queue.length ? form : scannerElement;
        target.scrollIntoView({ behavior: "smooth", block: queue.length ? "nearest" : "start" });
    }

    scannerElement.addEventListener("code-scanned", (e) => addCode(e.detail.code));
    // Catégorie créée ailleurs sur la page (includes/category-setup.php)
    document.addEventListener("categories-changed", (e) => setCategories(e.detail.categories));
    categorySelect.addEventListener("change", () => {
        // Les valeurs déjà saisies sont conservées si on revient à la même catégorie
        renderFields(queue[0]?.fields);
    });
    photoInput.addEventListener("change", () => addPhotos(photoInput.files));
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        saveCurrent();
    });
    skipButton.addEventListener("click", () => {
        queue.shift();
        showMessage(null);
        renderCurrent();
        scrollAfterChange();
    });

    loadCategories().then(renderCurrent);
})();
