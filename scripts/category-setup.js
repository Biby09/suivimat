// Liste, création, modification et suppression des catégories (includes/category-setup.php)
(() => {
    const root = document.getElementById("category-setup");
    if (!root) {
        return;
    }

    const organisationId = root.dataset.organisationId;
    const list = root.querySelector("[data-role='list']");
    const empty = root.querySelector("[data-role='empty']");
    const form = root.querySelector("[data-role='form']");
    const parentSelect = form.elements["parent_id"];
    const fieldsContainer = root.querySelector("[data-role='fields']");
    const addFieldButton = root.querySelector("[data-role='add-field']");
    const message = root.querySelector("[data-role='message']");
    const submitButton = root.querySelector("[data-role='submit']");
    const MAX_FIELDS = 20;

    const modalElement = document.getElementById("edit-category-modal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    // Liste affichée dans un modal (includes/modals/category-setup-modal.php) : Bootstrap n'empile pas les modals,
    // le modal de modification est sorti du modal parent, qui est fermé pendant la modification puis rouvert
    const parentModalElement = root.closest(".modal");
    if (parentModalElement) {
        document.body.appendChild(modalElement);
        modalElement.addEventListener("hidden.bs.modal", () => {
            delete parentModalElement.dataset.editing;
            bootstrap.Modal.getOrCreateInstance(parentModalElement).show();
        });
    }
    const editForm = modalElement.querySelector("[data-role='edit-form']");
    const editParentSelect = editForm.elements["parent_id"];
    const editFieldsContainer = modalElement.querySelector("[data-role='edit-fields']");
    const editAddFieldButton = modalElement.querySelector("[data-role='edit-add-field']");
    const editMessage = modalElement.querySelector("[data-role='edit-message']");
    const editSubmitButton = modalElement.querySelector("[data-role='edit-submit']");
    const deleteButton = modalElement.querySelector("[data-role='delete']");
    const deleteConfirm = modalElement.querySelector("[data-role='delete-confirm']");
    const deleteConfirmButton = modalElement.querySelector("[data-role='delete-confirm-button']");

    let categories = [];
    let editedCategory = null;

    function showMessage(type, text, container = message) {
        container.innerHTML = "";
        if (!text) {
            return;
        }
        const alert = document.createElement("div");
        alert.className = `alert alert-${type} py-2`;
        alert.textContent = text;
        container.appendChild(alert);
    }

    async function fetchJson(url, options = {}) {
        try {
            const response = await fetch(url, options);
            return await response.json();
        } catch {
            return { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }
    }

    // « Parent › Enfant » pour les sous-catégories
    function fullName(category, categories) {
        const parent = categories.find((c) => c.id === category.parent_id);
        return parent ? `${parent.name} › ${category.name}` : category.name;
    }

    // IDs des sous-catégories (directes ou non) : elles ne peuvent pas devenir parentes de la catégorie
    function descendantIds(categoryId) {
        const ids = [];
        const toVisit = [categoryId];
        while (toVisit.length) {
            const id = toVisit.pop();
            categories.filter((c) => c.parent_id === id && !ids.includes(c.id)).forEach((c) => {
                ids.push(c.id);
                toVisit.push(c.id);
            });
        }
        return ids;
    }

    function render() {
        list.innerHTML = "";
        empty.hidden = categories.length > 0;
        list.hidden = categories.length === 0;

        const selectedParent = parentSelect.value;
        parentSelect.length = 1; // garde l'option « Aucune »

        categories.forEach((category) => {
            const item = document.createElement("li");
            item.className = "list-group-item";

            // Nom cliquable : le crayon apparaît au survol
            const title = document.createElement("button");
            title.type = "button";
            title.className = "category-setup-edit btn btn-link p-0 fw-bold text-reset text-decoration-none text-start";
            title.setAttribute("aria-label", `Modifier la catégorie ${fullName(category, categories)}`);
            title.textContent = fullName(category, categories);
            title.insertAdjacentHTML("beforeend", '<svg class="category-setup-edit-icon ms-2" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>');
            title.addEventListener("click", () => openEditModal(category));
            item.appendChild(title);

            if (category.fields.length) {
                const fields = document.createElement("div");
                fields.className = "d-flex flex-wrap gap-1 mt-1";
                category.fields.forEach((field) => {
                    const badge = document.createElement("span");
                    badge.className = "badge text-bg-light border";
                    badge.textContent = field.name;
                    fields.appendChild(badge);
                });
                item.appendChild(fields);
            }

            list.appendChild(item);
            parentSelect.add(new Option(fullName(category, categories), category.id));
        });

        parentSelect.value = selectedParent;
    }

    async function loadCategories() {
        const data = await fetchJson(`/api/category/get-categories.php?organisation_id=${encodeURIComponent(organisationId)}`);
        if (!data.success) {
            showMessage("danger", data.message || "Impossible de charger les catégories.");
            return null;
        }
        categories = data.categories;
        render();
        return categories;
    }

    // Ajoute une ligne de champ personnalisé dans container (formulaire de création ou modal)
    function addField(container, addButton, inputName, value = "", focus = true) {
        if (container.children.length >= MAX_FIELDS) {
            return;
        }

        const index = container.children.length + 1;
        const row = document.createElement("div");
        row.className = "input-group";

        const input = document.createElement("input");
        input.type = "text";
        input.className = "form-control";
        input.name = inputName;
        input.value = value;
        input.maxLength = 45;
        input.placeholder = `Nom du champ ${index}`;
        input.setAttribute("aria-label", `Nom du champ ${index}`);

        const remove = document.createElement("button");
        remove.type = "button";
        remove.className = "btn btn-outline-danger";
        remove.setAttribute("aria-label", "Supprimer ce champ");
        remove.textContent = "×";
        remove.addEventListener("click", () => {
            row.remove();
            addButton.hidden = false;
        });

        row.append(input, remove);
        container.appendChild(row);
        addButton.hidden = container.children.length >= MAX_FIELDS;
        if (focus) {
            input.focus();
        }
    }

    function openEditModal(category) {
        editedCategory = category;
        editForm.reset();
        editForm.classList.remove("was-validated");
        showMessage("", "", editMessage);
        deleteConfirm.hidden = true;
        deleteButton.hidden = false;

        editForm.elements["name"].value = category.name;

        // Parentes possibles : toutes sauf la catégorie elle-même et ses sous-catégories
        const excluded = [category.id, ...descendantIds(category.id)];
        editParentSelect.length = 1; // garde l'option « Aucune »
        categories.filter((c) => !excluded.includes(c.id)).forEach((c) => {
            editParentSelect.add(new Option(fullName(c, categories), c.id));
        });
        editParentSelect.value = category.parent_id ?? "";

        editFieldsContainer.innerHTML = "";
        editAddFieldButton.hidden = false;
        category.fields.forEach((field) => addField(editFieldsContainer, editAddFieldButton, `fields[${field.id}]`, field.name, false));

        if (parentModalElement) {
            // data-editing : le modal parent se ferme seulement le temps de la modification
            parentModalElement.dataset.editing = "1";
            parentModalElement.addEventListener("hidden.bs.modal", () => modal.show(), { once: true });
            bootstrap.Modal.getOrCreateInstance(parentModalElement).hide();
        } else {
            modal.show();
        }
    }

    async function refreshAfterChange() {
        const updated = await loadCategories();
        if (updated) {
            document.dispatchEvent(new CustomEvent("categories-changed", { detail: { categories: updated } }));
        }
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
        const data = await fetchJson("/api/category/create-category.php", { method: "POST", body: formData });
        submitButton.disabled = false;

        if (!data.success) {
            showMessage("danger", data.message || "Erreur lors de la création de la catégorie.");
            return;
        }

        showMessage("success", `Catégorie « ${formData.get("name")} » créée.`);
        form.reset();
        form.classList.remove("was-validated");
        fieldsContainer.innerHTML = "";
        addFieldButton.hidden = false;

        await refreshAfterChange();
    });

    editForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        editForm.classList.add("was-validated");
        if (!editForm.checkValidity()) {
            return;
        }

        const formData = new FormData(editForm);
        formData.append("organisation_id", organisationId);
        formData.append("category_id", editedCategory.id);

        editSubmitButton.disabled = true;
        const data = await fetchJson("/api/category/update-category.php", { method: "POST", body: formData });
        editSubmitButton.disabled = false;

        if (!data.success) {
            showMessage("danger", data.message || "Erreur lors de la modification de la catégorie.", editMessage);
            return;
        }

        modal.hide();
        showMessage("success", `Catégorie « ${formData.get("name")} » modifiée.`);
        await refreshAfterChange();
    });

    // Suppression en deux temps : « Supprimer » affiche la confirmation dans le modal
    deleteButton.addEventListener("click", () => {
        showMessage("", "", editMessage);
        modalElement.querySelector("[data-role='delete-name']").textContent = editedCategory.name;
        deleteConfirm.hidden = false;
        deleteButton.hidden = true;
        deleteConfirmButton.focus();
    });

    modalElement.querySelector("[data-role='delete-cancel']").addEventListener("click", () => {
        deleteConfirm.hidden = true;
        deleteButton.hidden = false;
    });

    deleteConfirmButton.addEventListener("click", async () => {
        const formData = new FormData();
        formData.append("organisation_id", organisationId);
        formData.append("category_id", editedCategory.id);

        deleteConfirmButton.disabled = true;
        const data = await fetchJson("/api/category/delete-category.php", { method: "POST", body: formData });
        deleteConfirmButton.disabled = false;

        if (!data.success) {
            deleteConfirm.hidden = true;
            deleteButton.hidden = false;
            showMessage("danger", data.message || "Erreur lors de la suppression de la catégorie.", editMessage);
            return;
        }

        modal.hide();
        showMessage("success", `Catégorie « ${editedCategory.name} » supprimée.`);
        await refreshAfterChange();
    });

    addFieldButton.addEventListener("click", () => addField(fieldsContainer, addFieldButton, "fields[]"));
    editAddFieldButton.addEventListener("click", () => addField(editFieldsContainer, editAddFieldButton, "new_fields[]"));

    loadCategories();
})();
