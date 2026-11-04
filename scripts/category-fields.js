// Champs personnalisés d'une catégorie, y compris ceux hérités de ses catégories parentes.
// Partagé par scripts/article-setup.js et scripts/article-edit.js : à charger AVANT eux.
// categories : liste de l'API (api/category/get-categories.php) : { id, name, parent_id, fields: [{ id, name, default }] }
// Renvoie les champs de la racine à la catégorie (« Tentes » puis « Igloo ») ; un champ hérité porte
// inheritedFrom = nom de la catégorie parente qui le définit.
window.categoryFields = (categoryId, categories) => {
    const path = [];
    let category = categories.find((c) => c.id === categoryId);
    // Garde-fou : une boucle dans les parentes (A parente de B, B parente de A) ne bloque pas la page
    while (category && !path.includes(category) && path.length < 20) {
        path.unshift(category); // ajoute au début : la racine finit en premier
        category = categories.find((c) => c.id === category.parent_id);
    }

    // flatMap : une liste de champs par catégorie, mises bout à bout en une seule liste
    return path.flatMap((c) => c.fields.map((field) => ({
        ...field,
        inheritedFrom: c.id === categoryId ? null : c.name,
    })));
};
