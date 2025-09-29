// ========================================
// FONCTIONS UTILITAIRES - CARD.JS
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // FONCTIONS UTILITAIRES
  // ========================================

  // Fonction d'affichage des notifications
  function showNotification(message, type = "success") {
    // Créer l'élément de notification
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    // Styles de base
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type === "success" ? "#4CAF50" : "#f44336"};
      color: white;
      padding: 12px 20px;
      border-radius: 4px;
      z-index: 10000;
      font-size: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      transform: translateX(100%);
      transition: transform 0.3s ease;
    `;

    // Ajouter au DOM
    document.body.appendChild(notification);

    // Animation d'entrée
    setTimeout(() => {
      notification.style.transform = "translateX(0)";
    }, 100);

    // Supprimer après 3 secondes
    setTimeout(() => {
      notification.style.transform = "translateX(100%)";
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 3000);
  }

  // ========================================
  // EXPOSITION DES FONCTIONS (pour utilisation externe)
  // ========================================

  // Rendre les fonctions disponibles globalement si nécessaire
  window.AuraFineUtils = {
    showNotification: showNotification,
  };

  console.log("Fonctions utilitaires initialisées avec succès !");
});
