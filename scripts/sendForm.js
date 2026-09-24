async function sendForm(form, url, options = {}) {
    const msg = options.messageContainer
        ? document.querySelector(options.messageContainer)
        : null;

    const formData = new FormData(form);
    const loadingMsg = options.loadingMessage || "Envoi en cours...";
    const successMsg = options.successMessage || "Succès";
    const errorMsg = options.errorMessage || "Erreur";
    const noReset = options.noReset || false;

    if (msg) {
        msg.innerHTML = `<div class="alert alert-info">${loadingMsg}</div>`;
    }

    try {
        const response = await fetch(url, {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (msg) {
                msg.innerHTML = `<div class="alert alert-success">${successMsg}</div>`;

                if (!noReset) {
                    form.reset();
                }
            }

            // Redirection si une URL est fournie
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            if (msg) {
                const displayMsg = data.message ? data.message : errorMsg;
                msg.innerHTML = `<div class="alert alert-danger">${displayMsg}</div>`;
            }
        }

        return data;

    } catch (error) {
        if (msg) {
            msg.innerHTML = `<div class="alert alert-danger">${errorMsg}</div>`;
        }

        return { success: false, error };
    }
}