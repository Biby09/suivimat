// Liste et création des catégories (includes/category-setup.php)
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

    // « Parent › Enfant » pour les sous-catégories
    function fullName(category, categories) {
        const parent = categories.find((c) => c.id === category.parent_id);
        return parent ? `${parent.name} › ${category.name}` : category.name;
    }

    function render(categories) {
        list.innerHTML = "";
        empty.hidden = categories.length > 0;
        list.hidden = categories.length === 0;

        const selectedParent = parentSelect.value;
        parentSelect.length = 1; // garde l'option « Aucune »

        categories.forEach((category) => {
            const item = document.createElement("li");
            item.className = "list-group-item";

            const title = document.createElement("div");
            title.className = "fw-bold";
            title.textContent = fullName(category, categories);
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
        render(data.categories);
        return data.categories;
    }

    function addField() {
        if (fieldsContainer.children.length >= MAX_FIELDS) {
            return;
        }

        const index = fieldsContainer.children.length + 1;
        const row = document.createElement("div");
        row.className = "input-group";

        const input = document.createElement("input");
        input.type = "text";
        input.className = "form-control";
        input.name = "fields[]";
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
            addFieldButton.hidden = false;
        });

        row.append(input, remove);
        fieldsContainer.appendChild(row);
        addFieldButton.hidden = fieldsContainer.children.length >= MAX_FIELDS;
        input.focus();
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

        const categories = await loadCategories();
        if (categories) {
            document.dispatchEvent(new CustomEvent("categories-changed", { detail: { categories } }));
        }
    });

    addFieldButton.addEventListener("click", addField);

    loadCategories();
})();
