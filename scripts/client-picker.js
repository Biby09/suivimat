// Comportement du choix de client (includes/client-picker.php) : « + Nouveau client » affiche le prénom et le nom
// et les rend obligatoires. Partagé par scripts/article-loan.js et scripts/article-sale.js : à charger AVANT eux.
// Utilisation : const picker = window.clientPicker(form); picker.select("12") ; picker.select("") ; picker.select("new")
window.clientPicker = (form) => {
    const select = form.elements["client_id"];
    const newClient = form.querySelector("[data-role='new-client']");
    const firstname = form.elements["client_firstname"];
    const lastname = form.elements["client_lastname"];

    // focus : donne le curseur au prénom (seulement quand l'utilisateur choisit lui-même « + Nouveau client »)
    function update(focus = false) {
        const isNew = select.value === "new";
        newClient.hidden = !isNew;
        firstname.required = isNew;
        lastname.required = isNew;
        if (isNew && focus) {
            firstname.focus();
        }
    }

    select.addEventListener("change", () => update(true));

    return {
        // Choisit un client par son ID ; "" = aucun ; sans clients enregistrés, « + Nouveau client » d'office
        select(clientId) {
            select.value = clientId || (select.options.length > 2 ? "" : "new");
            update();
        },
    };
};
