// Réduction d'une photo avant l'envoi (limite d'upload du serveur, données mobiles).
// Partagé par scripts/article-setup.js et scripts/article-edit.js : à charger AVANT eux.
// window.resizePhoto : fonction globale, utilisable depuis les autres fichiers.
window.resizePhoto = async (file, maxSize = 1600) => {
    try {
        // imageOrientation : les photos de téléphone prises en portrait restent droites
        const bitmap = await createImageBitmap(file, { imageOrientation: "from-image" });
        const ratio = Math.min(1, maxSize / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement("canvas");
        canvas.width = Math.round(bitmap.width * ratio);
        canvas.height = Math.round(bitmap.height * ratio);
        canvas.getContext("2d").drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();
        return await new Promise((resolve, reject) => canvas.toBlob((blob) => (blob ? resolve(blob) : reject()), "image/jpeg", 0.85));
    } catch {
        return file; // navigateur incapable de réduire : le serveur s'en charge
    }
};
