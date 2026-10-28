// Inventaire d'une organisation (dashboard/organisation/index.php) : filtre par catégorie, recherche et pages de résultats
(() => {
    const root = document.getElementById("organisation-articles");
    if (!root) {
        return;
    }

    const organisationId = root.dataset.organisationId;
    const searchForm = root.querySelector("[data-role='search-form']");
    const searchInput = searchForm.elements["q"];
    const suggestionList = root.querySelector("[data-role='suggestions']");
    const categoryList = root.querySelector("[data-role='categories']");
    const currentCategoryShort = root.querySelector("[data-role='current-category-short']");
    const offcanvasElement = document.getElementById("organisation-categories");
    const resultsSection = root.querySelector("[aria-labelledby='organisation-results-title']");
    const resultsTitle = root.querySelector("[data-role='results-title']");
    const resultsCount = root.querySelector("[data-role='results-count']");
    const message = root.querySelector("[data-role='message']");
    const articleList = root.querySelector("[data-role='articles']");
    const pagination = root.querySelector("[data-role='pagination']");
    const noResults = root.querySelector("[data-role='no-results']");
    const empty = root.querySelector("[data-role='empty']");

    // Filtre « catégorie » : "all", "none" (sans catégorie) ou l'ID d'une catégorie
    const ALL = "all";
    const NONE = "none";
    const SUGGESTION_DELAY = 200; // ms sans frappe avant de demander des propositions

    let categories = [];
    let counts = {};      // nombre d'objets par catégorie (« 0 » = sans catégorie), fourni par l'API
    let selected = ALL;
    let query = "";       // recherche appliquée (validée avec Entrée), différente de la saisie en cours
    let page = 1;
    let articlesRequest = 0;  // seule la réponse de la dernière requête est affichée

    let suggestionTimer = null;
    let suggestionController = null;
    let suggestionOptions = [];  // [{ element, choose }]
    let activeSuggestion = -1;

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
        } catch (error) {
            if (error.name === "AbortError") {
                throw error;
            }
            return { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }
    }

    function categoryById(id) {
        return categories.find((c) => c.id === id);
    }

    // Catégorie et ses parentes, de la racine à la catégorie : [Parent, Enfant]
    function categoryPath(id) {
        const path = [];
        let category = categoryById(id);
        while (category && !path.includes(category)) {
            path.unshift(category);
            category = categoryById(category.parent_id);
        }
        return path;
    }

    function categoryPathLabel(id) {
        return categoryPath(id).map((c) => c.name).join(" › ");
    }

    // ID de la catégorie et de toutes ses sous-catégories
    function withDescendants(id) {
        const ids = [id];
        for (let i = 0; i < ids.length; i++) {
            categories.filter((c) => c.parent_id === ids[i] && !ids.includes(c.id)).forEach((c) => ids.push(c.id));
        }
        return ids;
    }

    function selectedLabel() {
        if (selected === ALL) {
            return "Tous les objets";
        }
        if (selected === NONE) {
            return "Sans catégorie";
        }
        return categoryPathLabel(selected);
    }

    function countIn(filter) {
        if (filter === ALL) {
            return Object.values(counts).reduce((sum, count) => sum + count, 0);
        }
        if (filter === NONE) {
            return counts[0] ?? 0;
        }
        return withDescendants(filter).reduce((sum, id) => sum + (counts[id] ?? 0), 0);
    }

    // ---- Catégories (aside) ----

    function categoryLink(filter, label, depth = 0) {
        const item = document.createElement("li");
        const link = document.createElement("a");
        link.href = "#";
        link.className = "d-flex justify-content-between align-items-center gap-2 rounded px-2 py-1 text-decoration-none";
        // Indentation des sous-catégories (px-2 de Bootstrap est en !important)
        link.style.setProperty("padding-left", `${0.5 + depth}rem`, "important");
        link.dataset.filter = filter;

        const name = document.createElement("span");
        name.className = "text-truncate";
        name.textContent = label;

        const count = document.createElement("span");
        count.className = "badge rounded-pill";
        count.textContent = countIn(filter);

        link.append(name, count);
        link.addEventListener("click", (e) => {
            e.preventDefault();
            selectCategory(filter);
            bootstrap.Offcanvas.getInstance(offcanvasElement)?.hide();
        });

        item.appendChild(link);
        return item;
    }

    // Arbre des catégories : les sous-catégories sont indentées sous leur parente
    function renderCategories() {
        categoryList.innerHTML = "";
        categoryList.appendChild(categoryLink(ALL, "Tous les objets"));

        const addChildren = (parentId, depth) => {
            categories.filter((c) => c.parent_id === parentId).forEach((category) => {
                categoryList.appendChild(categoryLink(category.id, category.name, depth));
                addChildren(category.id, depth + 1);
            });
        };
        addChildren(null, 0);

        if (countIn(NONE) > 0) {
            categoryList.appendChild(categoryLink(NONE, "Sans catégorie"));
        }

        highlightSelected();
    }

    function highlightSelected() {
        categoryList.querySelectorAll("a").forEach((link) => {
            const active = link.dataset.filter === String(selected);
            link.classList.toggle("bg-primary", active);
            link.classList.toggle("text-white", active);
            link.classList.toggle("text-body", !active);
            link.querySelector(".badge").className = `badge rounded-pill ${active ? "text-bg-light" : "text-bg-secondary"}`;
            if (active) {
                link.setAttribute("aria-current", "page");
            } else {
                link.removeAttribute("aria-current");
            }
        });
        currentCategoryShort.textContent = selectedLabel();
    }

    // ---- Objets ----

    function statusClass(status) {
        return { "Disponible": "text-bg-success", "Prêté": "text-bg-warning" }[status] ?? "text-bg-secondary";
    }

    function articleCard(article) {
        const col = document.createElement("div");
        col.className = "col";

        const card = document.createElement("article");
        card.className = "card h-100";

        const picture = document.createElement("div");
        picture.className = "ratio ratio-4x3 bg-light rounded-top overflow-hidden";
        if (article.cover_url) {
            const img = document.createElement("img");
            img.src = article.cover_url;
            img.alt = "";
            img.loading = "lazy";
            img.className = "object-fit-cover";
            picture.appendChild(img);
        } else {
            picture.innerHTML = '<div class="d-flex align-items-center justify-content-center text-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/></svg></div>';
        }

        const body = document.createElement("div");
        body.className = "card-body p-2 p-sm-3 d-flex flex-column";

        const name = document.createElement("h3");
        name.className = "fs-6 fw-semibold mb-1 text-break";
        name.textContent = article.name;

        const category = document.createElement("p");
        category.className = "small text-muted mb-2";
        category.textContent = categoryPathLabel(article.category_id) || "Sans catégorie";

        const footer = document.createElement("div");
        footer.className = "d-flex flex-wrap gap-1 mt-auto";
        const code = document.createElement("span");
        code.className = "badge text-bg-light border font-monospace";
        code.textContent = article.code;
        const status = document.createElement("span");
        status.className = `badge ${statusClass(article.status)}`;
        status.textContent = article.status;
        footer.append(code, status);

        body.append(name, category, footer);
        card.append(picture, body);
        col.appendChild(card);
        return col;
    }

    // Numéros affichés : première, dernière, et deux pages autour de la page courante ;
    // « … » remplace les trous de plusieurs pages (un trou d'une seule page affiche son numéro)
    function pageNumbers(current, last) {
        const numbers = [...new Set([1, current - 2, current - 1, current, current + 1, current + 2, last])]
            .filter((n) => n >= 1 && n <= last)
            .sort((a, b) => a - b);
        return numbers.flatMap((n, i) => {
            const gap = i > 0 ? n - numbers[i - 1] : 1;
            return gap === 1 ? [n] : gap === 2 ? [n - 1, n] : ["…", n];
        });
    }

    function pageItem(label, target, { active = false, disabled = false, ariaLabel = null } = {}) {
        const item = document.createElement("li");
        item.className = `page-item${active ? " active" : ""}${disabled ? " disabled" : ""}`;

        if (disabled || active || target === null) {
            const span = document.createElement("span");
            span.className = "page-link";
            span.textContent = label;
            if (active) {
                item.setAttribute("aria-current", "page");
            }
            item.appendChild(span);
            return item;
        }

        const link = document.createElement("a");
        link.className = "page-link";
        link.href = `?${urlParams(target)}`;
        link.textContent = label;
        if (ariaLabel) {
            link.setAttribute("aria-label", ariaLabel);
        }
        link.addEventListener("click", (e) => {
            e.preventDefault();
            page = target;
            loadArticles({ scroll: true });
        });
        item.appendChild(link);
        return item;
    }

    function renderPagination(current, last) {
        const list = pagination.querySelector(".pagination");
        list.innerHTML = "";
        pagination.hidden = last <= 1;
        if (last <= 1) {
            return;
        }

        list.appendChild(pageItem("‹", current - 1, { disabled: current === 1, ariaLabel: "Page précédente" }));
        pageNumbers(current, last).forEach((n) => {
            list.appendChild(n === "…" ? pageItem("…", null, { disabled: true }) : pageItem(String(n), n, { active: n === current, ariaLabel: `Page ${n}` }));
        });
        list.appendChild(pageItem("›", current + 1, { disabled: current === last, ariaLabel: "Page suivante" }));
    }

    async function loadArticles({ scroll = false } = {}) {
        const requestId = ++articlesRequest;
        const params = new URLSearchParams({ organisation_id: organisationId, page });
        if (selected !== ALL) {
            params.set("categorie", selected);
        }
        if (query) {
            params.set("q", query);
        }

        articleList.setAttribute("aria-busy", "true");
        articleList.classList.add("opacity-50");
        const data = await fetchJson(`/api/article/get-articles.php?${params}`);
        if (requestId !== articlesRequest) {
            return; // une requête plus récente a été lancée entre-temps
        }
        articleList.setAttribute("aria-busy", "false");
        articleList.classList.remove("opacity-50");

        if (!data.success) {
            showMessage("danger", data.message || "Impossible de charger les objets.");
            return;
        }
        showMessage(null);

        counts = data.counts;
        page = data.page;
        renderCategories();

        articleList.innerHTML = "";
        data.articles.forEach((article) => articleList.appendChild(articleCard(article)));
        renderPagination(data.page, data.pages);

        const hasArticles = countIn(ALL) > 0;
        resultsTitle.textContent = query ? `${selectedLabel()} · « ${query} »` : selectedLabel();
        const first = (data.page - 1) * data.page_size + 1;
        resultsCount.textContent = data.pages > 1
            ? `${first}–${first + data.articles.length - 1} sur ${data.total} objets`
            : `${data.total} objet${data.total > 1 ? "s" : ""}`;
        resultsCount.hidden = !hasArticles;
        empty.hidden = hasArticles;
        noResults.hidden = !hasArticles || data.total > 0;

        updateUrl();
        if (scroll) {
            resultsSection.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    async function loadCategories() {
        const data = await fetchJson(`/api/category/get-categories.php?organisation_id=${encodeURIComponent(organisationId)}`);
        if (!data.success) {
            showMessage("danger", data.message || "Impossible de charger les catégories.");
            return false;
        }
        categories = data.categories;
        // Catégorie sélectionnée supprimée entre-temps : retour à tous les objets
        if (selected !== ALL && selected !== NONE && !categoryById(selected)) {
            selected = ALL;
        }
        return true;
    }

    async function reload() {
        if (await loadCategories()) {
            await loadArticles();
        }
    }

    function selectCategory(filter) {
        selected = filter;
        page = 1;
        highlightSelected();
        loadArticles();
    }

    function applySearch(text) {
        hideSuggestions();
        searchInput.value = text;
        query = text.trim();
        page = 1;
        loadArticles();
    }

    // ---- URL : un rechargement ou un lien partagé retombe sur la même vue ----

    function urlParams(targetPage = page) {
        const params = new URLSearchParams(window.location.search);
        selected === ALL ? params.delete("categorie") : params.set("categorie", selected);
        query ? params.set("q", query) : params.delete("q");
        targetPage > 1 ? params.set("page", targetPage) : params.delete("page");
        return params;
    }

    function updateUrl() {
        history.replaceState(null, "", `${window.location.pathname}?${urlParams()}`);
    }

    function readUrl() {
        const params = new URLSearchParams(window.location.search);
        const filter = params.get("categorie");
        if (filter === NONE) {
            selected = NONE;
        } else if (filter && categoryById(Number(filter))) {
            selected = Number(filter);
        }
        query = (params.get("q") ?? "").trim();
        searchInput.value = query;
        page = Math.max(1, parseInt(params.get("page")) || 1);
    }

    // ---- Propositions pendant la saisie ----

    function hideSuggestions() {
        clearTimeout(suggestionTimer);
        suggestionController?.abort();
        suggestionList.classList.remove("show");
        suggestionList.innerHTML = "";
        suggestionOptions = [];
        activeSuggestion = -1;
        searchInput.setAttribute("aria-expanded", "false");
        searchInput.removeAttribute("aria-activedescendant");
    }

    function setActiveSuggestion(index) {
        suggestionOptions.forEach((option, i) => {
            option.element.classList.toggle("active", i === index);
            option.element.setAttribute("aria-selected", i === index ? "true" : "false");
        });
        activeSuggestion = index;
        if (index >= 0) {
            searchInput.setAttribute("aria-activedescendant", suggestionOptions[index].element.id);
            suggestionOptions[index].element.scrollIntoView({ block: "nearest" });
        } else {
            searchInput.removeAttribute("aria-activedescendant");
        }
    }

    function addSuggestionHeader(text) {
        const header = document.createElement("li");
        header.setAttribute("role", "presentation");
        header.innerHTML = `<h6 class="dropdown-header"></h6>`;
        header.firstChild.textContent = text;
        suggestionList.appendChild(header);
    }

    function addSuggestion(label, detail, choose) {
        const item = document.createElement("li");
        item.id = `organisation-suggestion-${suggestionOptions.length}`;
        item.className = "dropdown-item d-flex justify-content-between align-items-baseline gap-3";
        item.setAttribute("role", "option");
        item.setAttribute("aria-selected", "false");
        item.style.cursor = "pointer";

        const text = document.createElement("span");
        text.className = "text-truncate";
        text.textContent = label;
        item.appendChild(text);

        if (detail) {
            const small = document.createElement("small");
            small.className = "text-muted text-truncate";
            small.textContent = detail;
            item.appendChild(small);
        }

        // mousedown : le champ ne perd pas le focus (sinon la liste se fermerait avant le clic)
        item.addEventListener("mousedown", (e) => e.preventDefault());
        item.addEventListener("click", choose);
        suggestionList.appendChild(item);
        suggestionOptions.push({ element: item, choose });
    }

    function renderSuggestions(text, data) {
        suggestionList.innerHTML = "";
        suggestionOptions = [];
        activeSuggestion = -1;

        const matchingCategories = data.categories.map(categoryById).filter(Boolean);
        if (matchingCategories.length) {
            addSuggestionHeader("Catégories");
            matchingCategories.forEach((category) => {
                addSuggestion(categoryPathLabel(category.id), `${countIn(category.id)} objet${countIn(category.id) > 1 ? "s" : ""}`, () => {
                    // Choisir une catégorie l'ouvre dans l'aside, sans recherche
                    searchInput.value = "";
                    query = "";
                    hideSuggestions();
                    selectCategory(category.id);
                });
            });
        }

        if (data.articles.length) {
            addSuggestionHeader("Objets");
            data.articles.forEach((article) => {
                addSuggestion(article.name, `${article.code}${article.category_id ? ` · ${categoryPathLabel(article.category_id)}` : ""}`, () => {
                    // Recherche par code : seul cet objet est affiché, quelle que soit la catégorie
                    selected = ALL;
                    applySearch(article.code);
                });
            });
        }

        const divider = document.createElement("li");
        divider.setAttribute("role", "presentation");
        divider.innerHTML = '<hr class="dropdown-divider">';
        if (suggestionOptions.length) {
            suggestionList.appendChild(divider);
        }
        addSuggestion(`Rechercher « ${text} »`, data.total ? `${data.total} objet${data.total > 1 ? "s" : ""}` : "aucun objet", () => applySearch(text));

        suggestionList.classList.add("show");
        searchInput.setAttribute("aria-expanded", "true");
    }

    async function loadSuggestions() {
        const text = searchInput.value.trim();
        suggestionController?.abort();
        if (!text) {
            hideSuggestions();
            return;
        }

        suggestionController = new AbortController();
        const params = new URLSearchParams({ organisation_id: organisationId, q: text });
        let data;
        try {
            data = await fetchJson(`/api/article/search-suggestions.php?${params}`, { signal: suggestionController.signal });
        } catch {
            return; // requête annulée par une frappe plus récente
        }

        // La saisie a pu changer ou le champ perdre le focus pendant la requête
        if (data.success && searchInput.value.trim() === text && document.activeElement === searchInput) {
            renderSuggestions(text, data);
        }
    }

    searchInput.addEventListener("input", () => {
        clearTimeout(suggestionTimer);
        suggestionTimer = setTimeout(loadSuggestions, SUGGESTION_DELAY);
    });

    searchInput.addEventListener("keydown", (e) => {
        const open = suggestionList.classList.contains("show");
        if (e.key === "ArrowDown" && open) {
            e.preventDefault();
            setActiveSuggestion((activeSuggestion + 1) % suggestionOptions.length);
        } else if (e.key === "ArrowUp" && open) {
            e.preventDefault();
            setActiveSuggestion(activeSuggestion <= 0 ? suggestionOptions.length - 1 : activeSuggestion - 1);
        } else if (e.key === "Enter" && open && activeSuggestion >= 0) {
            e.preventDefault();
            suggestionOptions[activeSuggestion].choose();
        } else if (e.key === "Escape" && open) {
            e.preventDefault();
            hideSuggestions();
        }
    });

    searchInput.addEventListener("blur", hideSuggestions);

    // Croix du champ de recherche (Chrome, Safari) : on revient à la liste sans recherche
    searchInput.addEventListener("search", () => {
        if (!searchInput.value && query) {
            applySearch("");
        }
    });

    // Entrée ou bouton « Rechercher » : seul moment où le filtre est appliqué
    searchForm.addEventListener("submit", (e) => {
        e.preventDefault();
        applySearch(searchInput.value);
    });

    root.querySelector("[data-role='reset']").addEventListener("click", () => {
        searchInput.value = "";
        query = "";
        selectCategory(ALL);
    });

    // Objet enregistré depuis le modal « Enregistrer des objets », catégories modifiées : on recharge
    document.addEventListener("article-configured", reload);
    document.addEventListener("categories-changed", reload);

    (async () => {
        if (await loadCategories()) {
            readUrl();
            await loadArticles();
        }
    })();
})();
