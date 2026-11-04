// Réponse aux invitations du tableau de bord (dashboard/index.php) → api/invitation/respond-invitation.php
// Acceptée : ouverture de l'inventaire de l'organisation rejointe. Refusée : l'invitation disparaît de la liste.
document.querySelectorAll("[data-invitation-id]").forEach((button) => {
    button.addEventListener("click", async () => {
        const card = button.closest("[data-role='invitation']");
        const message = document.querySelector("[data-role='invitation-message']");
        const buttons = card.querySelectorAll("button");
        buttons.forEach((b) => (b.disabled = true));

        const formData = new FormData();
        formData.append("invitation_id", button.dataset.invitationId);
        formData.append("action", button.dataset.action); // « accept » ou « reject »

        let result;
        try {
            const response = await fetch("/api/invitation/respond-invitation.php", { method: "POST", body: formData });
            result = await response.json();
        } catch {
            result = { success: false, message: "Impossible de contacter le serveur. Votre session a peut-être expiré." };
        }

        if (!result.success) {
            buttons.forEach((b) => (b.disabled = false));
            message.innerHTML = "";
            const alert = document.createElement("div");
            alert.className = "alert alert-danger py-2 text-start";
            alert.textContent = result.message || "Erreur lors de la réponse à l'invitation.";
            message.appendChild(alert);
            return;
        }

        if (result.redirect) {
            window.location.href = result.redirect;
        } else {
            card.remove();
        }
    });
});
