// Filtres, recherche et tri d'une liste déjà affichée, sans recharger la page.
// Utilisé par « Prêts en cours » (dashboard/organisation/loans/) et « Clients » (dashboard/organisation/clients/).
//
// Structure attendue, à l'intérieur d'un élément [data-list-filter] :
// - [data-role='list'] contenant les éléments [data-item], chacun avec :
//     data-categories="late soon"  catégories de l'élément (pour les filtres), séparées par des espaces
//     data-search="…"              texte cherché, en minuscules
//     data-sort-xxx="…"            valeur de tri « xxx » (ex. data-sort-client) ; data-sort-order = ordre par défaut
// - [data-role='filters'] (optionnel) : boutons radio, valeur « all » ou une catégorie
// - [data-role='search'] (optionnel) : champ de recherche ; chaque mot tapé doit apparaître dans data-search
// - [data-role='sort'] (optionnel) : liste déroulante, valeur « xxx » ou « xxx:desc » (ordre décroissant)
// - [data-role='count'] (optionnel) : nombre d'éléments visibles, avec data-one="prêt" et data-many="prêts"
// - [data-role='no-results'] et [data-role='reset'] (optionnels) : message « aucun résultat » et bouton pour tout réafficher
document.querySelectorAll("[data-list-filter]").forEach((root) => {
    const list = root.querySelector("[data-role='list']");
    if (!list) {
        return;
    }

    const filters = root.querySelector("[data-role='filters']");
    const searchInput = root.querySelector("[data-role='search']");
    const sortSelect = root.querySelector("[data-role='sort']");
    const count = root.querySelector("[data-role='count']");
    const noResults = root.querySelector("[data-role='no-results']");
    const items = [...list.querySelectorAll("[data-item]")];

    // data-sort-client → dataset.sortClient : « client » → « sortClient »
    const sortKey = (name) => "sort" + name.charAt(0).toUpperCase() + name.slice(1);

    // Compare deux valeurs : nombres comme des nombres, textes par ordre alphabétique français (« É » avec les « E »)
    function compare(a, b) {
        if (a !== "" && b !== "" && !isNaN(a) && !isNaN(b)) {
            return Number(a) - Number(b);
        }
        return a.localeCompare(b, "fr");
    }

    function update() {
        const filter = filters?.querySelector("input:checked")?.value ?? "all";
        const words = (searchInput?.value ?? "").toLowerCase().split(/\s+/).filter(Boolean);

        let visible = 0;
        items.forEach((item) => {
            const categories = (item.dataset.categories ?? "").split(" ");
            const matchesFilter = filter === "all" || categories.includes(filter);
            const matchesSearch = words.every((word) => item.dataset.search.includes(word));
            item.hidden = !(matchesFilter && matchesSearch);
            // d-none en plus de hidden : un élément avec d-flex (display: flex !important) ignorerait hidden seul
            item.classList.toggle("d-none", item.hidden);
            if (!item.hidden) {
                visible++;
            }
        });

        if (sortSelect) {
            // « start:desc » → clé « start », ordre décroissant ; à égalité, ordre par défaut (data-sort-order)
            const [name, direction] = sortSelect.value.split(":");
            const key = sortKey(name);
            const sign = direction === "desc" ? -1 : 1;
            [...items]
                .sort((a, b) => sign * compare(a.dataset[key] ?? "", b.dataset[key] ?? "") || compare(a.dataset.sortOrder ?? "", b.dataset.sortOrder ?? ""))
                // append() déplace un élément déjà présent : les éléments sont replacés dans le nouvel ordre
                .forEach((item) => list.append(item));
        }

        if (count) {
            count.textContent = `${visible} ${visible > 1 ? count.dataset.many : count.dataset.one}`;
        }
        if (noResults) {
            noResults.hidden = visible > 0;
        }
    }

    filters?.addEventListener("change", update);
    searchInput?.addEventListener("input", update);
    sortSelect?.addEventListener("change", update);
    root.querySelector("[data-role='reset']")?.addEventListener("click", () => {
        if (searchInput) {
            searchInput.value = "";
        }
        const all = filters?.querySelector("input[value='all']");
        if (all) {
            all.checked = true;
        }
        update();
    });
});
