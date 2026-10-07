// Paramètres du modal de téléchargement des codes QR (includes/modals/download-qr-modal.php)
(() => {
    const form = document.getElementById("download-qr-form");
    const labelsSettings = document.getElementById("labels-settings");
    const sheetFormat = document.getElementById("sheet-format");
    const sheetWidth = document.getElementById("sheet-width");
    const sheetHeight = document.getElementById("sheet-height");
    const customWidth = document.getElementById("custom-size-width");
    const customHeight = document.getElementById("custom-size-height");
    const columns = document.getElementById("label-columns");
    const rows = document.getElementById("label-rows");
    const objectCount = document.getElementById("object-count");
    const objectCountHelp = document.getElementById("object-count-help");
    const MAX_OBJECTS = 1000;

    const isLabelsExport = () => form.elements["export_type"].value === "labels";
    const labelsPerSheet = () => Math.max(1, parseInt(columns.value) || 1) * Math.max(1, parseInt(rows.value) || 1);

    // Ajuste le nombre d'objets pour qu'il corresponde à un nombre entier de feuilles
    function updateObjectCount() {
        const count = parseInt(objectCount.value) || 0;

        if (!isLabelsExport()) {
            objectCount.min = 1;
            objectCount.step = 1;
            objectCount.max = MAX_OBJECTS;
            objectCountHelp.textContent = "Un code unique sera généré pour chaque objet.";
            return;
        }

        const perSheet = labelsPerSheet();
        const maxSheets = Math.floor(MAX_OBJECTS / perSheet);
        const sheets = Math.min(maxSheets, Math.max(1, Math.ceil(count / perSheet)));

        objectCount.min = perSheet;
        objectCount.step = perSheet;
        objectCount.max = maxSheets * perSheet;
        objectCount.value = sheets * perSheet;
        objectCountHelp.textContent = `Par lots de ${perSheet} étiquettes (${columns.value} × ${rows.value}) : ${sheets} feuille${sheets > 1 ? "s" : ""} à imprimer.`;
    }

    function updateSheetFormat() {
        const option = sheetFormat.selectedOptions[0];
        const isCustom = option.value === "custom";

        customWidth.hidden = !isCustom;
        customHeight.hidden = !isCustom;
        if (!isCustom) {
            sheetWidth.value = option.dataset.width;
            sheetHeight.value = option.dataset.height;
        }
    }

    function updateExportType() {
        const labels = isLabelsExport();

        labelsSettings.hidden = !labels;
        // Les champs masqués ne sont ni validés ni envoyés
        labelsSettings.querySelectorAll("input, select").forEach((field) => field.disabled = !labels);
        updateObjectCount();
    }

    form.querySelectorAll("input[name='export_type']").forEach((radio) => radio.addEventListener("change", updateExportType));
    sheetFormat.addEventListener("change", updateSheetFormat);
    columns.addEventListener("change", updateObjectCount);
    rows.addEventListener("change", updateObjectCount);
    objectCount.addEventListener("change", updateObjectCount);

    // Génération asynchrone : erreurs affichées dans le modal, PDF ouvert dans un nouvel onglet
    const modal = document.getElementById("download-qr-modal");
    const message = document.getElementById("download-qr-message");
    const submitButton = document.getElementById("download-qr-submit");
    let pdfUrl = null;

    function showMessage(type, text, linkUrl = null) {
        message.innerHTML = "";
        const alert = document.createElement("div");
        alert.className = `alert alert-${type} mb-0`;
        alert.textContent = text;
        if (linkUrl) {
            const link = document.createElement("a");
            link.href = linkUrl;
            link.target = "_blank";
            link.rel = "noopener";
            link.className = "alert-link ms-1";
            link.textContent = "Ouvrir le PDF";
            alert.appendChild(link);
        }
        message.appendChild(alert);
    }

    async function getErrorMessage(response) {
        try {
            const data = await response.json();
            return data.error || "Erreur lors de la génération des codes.";
        } catch {
            // Réponse non JSON : typiquement la page de connexion après expiration de la session
            return "Votre session a peut-être expiré. Rechargez la page et réessayez.";
        }
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        // L'onglet est ouvert tout de suite, pendant le clic : ouvert après l'attente, il serait bloqué comme pop-up
        const pdfTab = window.open("", "_blank");
        if (pdfTab) {
            pdfTab.document.title = "Génération des codes QR…";
            pdfTab.document.body.textContent = "Génération des codes QR en cours…";
        }

        showMessage("info", "Génération en cours…");
        submitButton.disabled = true;

        try {
            const response = await fetch(form.action, { method: "POST", body: new FormData(form) });
            const contentType = response.headers.get("Content-Type") || "";

            if (!response.ok || !contentType.includes("application/pdf")) {
                pdfTab?.close();
                showMessage("danger", await getErrorMessage(response));
                return;
            }

            if (pdfUrl) {
                URL.revokeObjectURL(pdfUrl);
            }
            pdfUrl = URL.createObjectURL(await response.blob());
            if (pdfTab) {
                pdfTab.location.href = pdfUrl;
            }

            // La page peut prendre le relais (ex. marche à suivre) en appelant preventDefault()
            const event = new CustomEvent("qr-codes-generated", { bubbles: true, cancelable: true, detail: { pdfUrl } });
            if (!form.dispatchEvent(event)) {
                message.innerHTML = "";
                bootstrap.Modal.getOrCreateInstance(modal).hide();
            } else {
                showMessage("success", "Les codes ont été générés.", pdfUrl);
            }
        } catch {
            pdfTab?.close();
            showMessage("danger", "Impossible de contacter le serveur. Vérifiez votre connexion et réessayez.");
        } finally {
            submitButton.disabled = false;
        }
    });

    updateSheetFormat();
    updateExportType();
})();
