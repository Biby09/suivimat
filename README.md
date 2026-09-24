# SuiviMat

Application web de gestion et de suivi du matériel de prêt pour associations et petites entreprises.

## Fonctionnalités

- **Multi-organisation** : plusieurs associations/entreprises utilisent l'application en parallèle, chacune ne voit que son propre matériel.
- **Inventaire** : consultation des objets avec photo et statut actuel (disponible, en prêt, vendu, perdu).
- **Catégories** : regroupement des objets similaires (nom, prix par défaut, photo), avec champs personnalisés.
- **Identification** : chaque objet possède un code unique et un code QR imprimable.
  - Scanné par un membre connecté de l'organisation → page de gestion de l'objet.
  - Scanné par une autre personne → page « Vous avez trouvé cet objet ».
- **Prêts** : enregistrement du départ (avec ou sans délai de retour) et du retour, historique complet par objet.
- **Dégâts** : description et photos associées à un retour.
- **Emprunteurs** : ajout / suppression, liste des objets actuellement prêtés.
- **Contrat de prêt** : généré sur le serveur et téléchargeable en PDF.
- **Scanner** : bouton accessible en permanence en bas au centre de l'application.

L'application est conçue **mobile first**, tout en restant utilisable sur ordinateur.
