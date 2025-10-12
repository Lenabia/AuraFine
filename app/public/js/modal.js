/**
 * Gestion des modales personnalisées
 * Remplace les alertes natives par des modales plus user-friendly
 */

// Variables globales pour les modales
let currentDeleteForm = null;
let currentCancelOrderId = null;
let currentReactivateOrderId = null;

/**
 * Affiche la modale de suppression
 * @param {string} itemName - Nom de l'élément à supprimer
 * @param {string} itemType - Type de l'élément (ex: "ce menu", "cette salade")
 * @param {string} formId - ID du formulaire de suppression
 */
function showDeleteModal(itemName, itemType, formId) {
  console.log("showDeleteModal appelée avec:", itemName, itemType, formId);

  const modal = document.getElementById("deleteModal");
  const message = modal.querySelector(".modal-message");
  const confirmBtn = modal.querySelector(".modal-btn-confirm");

  if (!modal || !message || !confirmBtn) {
    console.error("Éléments de la modale de suppression non trouvés");
    return;
  }

  // Mettre à jour le message
  message.textContent = `Êtes-vous sûr de vouloir supprimer ${itemType} "${itemName}" ? Cette action est irréversible.`;

  // Stocker l'ID du formulaire pour la confirmation
  currentDeleteForm = formId;

  // Afficher la modale
  modal.classList.add("active");
  document.body.style.overflow = "hidden";

  // Focus sur le bouton d'annulation pour l'accessibilité
  const cancelBtn = modal.querySelector(".modal-btn-cancel");
  if (cancelBtn) {
    cancelBtn.focus();
  }
}

/**
 * Affiche la modale de déconnexion
 */
function showLogoutModal() {
  console.log("showLogoutModal appelée");

  const modal = document.getElementById("logoutModal");

  if (!modal) {
    console.error("Modale de déconnexion non trouvée");
    return;
  }

  // Afficher la modale
  modal.classList.add("active");
  document.body.style.overflow = "hidden";

  // Focus sur le bouton d'annulation pour l'accessibilité
  const cancelBtn = modal.querySelector(".modal-btn-cancel");
  if (cancelBtn) {
    cancelBtn.focus();
  }
}

/**
 * Affiche la modale d'annulation de commande
 * @param {number} orderId - ID de la commande à annuler
 */
function showCancelOrderModal(orderId) {
  console.log("showCancelOrderModal appelée avec orderId:", orderId);

  const modal = document.getElementById("cancelOrderModal");

  if (!modal) {
    console.error("Modale d'annulation de commande non trouvée");
    return;
  }

  // Stocker l'ID de la commande
  currentCancelOrderId = orderId;

  // Afficher la modale
  modal.classList.add("active");
  document.body.style.overflow = "hidden";

  // Focus sur le bouton d'annulation pour l'accessibilité
  const cancelBtn = modal.querySelector(".modal-btn-cancel");
  if (cancelBtn) {
    cancelBtn.focus();
  }
}

/**
 * Affiche la modale de réactivation de commande
 * @param {number} orderId - ID de la commande à réactiver
 */
function showReactivateOrderModal(orderId) {
  console.log("showReactivateOrderModal appelée avec orderId:", orderId);

  const modal = document.getElementById("reactivateOrderModal");

  if (!modal) {
    console.error("Modale de réactivation de commande non trouvée");
    return;
  }

  // Stocker l'ID de la commande
  currentReactivateOrderId = orderId;

  // Afficher la modale
  modal.classList.add("active");
  document.body.style.overflow = "hidden";

  // Focus sur le bouton d'annulation pour l'accessibilité
  const cancelBtn = modal.querySelector(".modal-btn-cancel");
  if (cancelBtn) {
    cancelBtn.focus();
  }
}

/**
 * Ferme toutes les modales
 */
function closeModal() {
  console.log("closeModal appelée");

  const modals = document.querySelectorAll(".modal-overlay");
  modals.forEach((modal) => {
    modal.classList.remove("active");
  });

  document.body.style.overflow = "auto";
  currentDeleteForm = null;
  currentCancelOrderId = null;
  currentReactivateOrderId = null;
}

/**
 * Confirme la suppression et soumet le formulaire
 */
function confirmDelete() {
  console.log("confirmDelete appelée, formulaire:", currentDeleteForm);

  if (currentDeleteForm) {
    const form = document.getElementById(currentDeleteForm);
    if (form) {
      form.submit();
    } else {
      console.error("Formulaire de suppression non trouvé:", currentDeleteForm);
    }
  }

  closeModal();
}

/**
 * Confirme l'annulation de commande et soumet le formulaire
 */
function confirmCancelOrder() {
  console.log("confirmCancelOrder appelée, orderId:", currentCancelOrderId);

  if (currentCancelOrderId) {
    const form = document.getElementById("cancelOrderForm");
    if (form) {
      form.submit();
    } else {
      console.error("Formulaire d'annulation de commande non trouvé");
    }
  }

  closeModal();
}

/**
 * Confirme la réactivation de commande et soumet le formulaire
 */
function confirmReactivateOrder() {
  console.log(
    "confirmReactivateOrder appelée, orderId:",
    currentReactivateOrderId
  );

  if (currentReactivateOrderId) {
    const form = document.getElementById("reactivateOrderForm");
    if (form) {
      form.submit();
    } else {
      console.error("Formulaire de réactivation de commande non trouvé");
    }
  }

  closeModal();
}

// Initialisation quand le DOM est chargé
document.addEventListener("DOMContentLoaded", function () {
  console.log("Modal.js chargé correctement");

  // Gestion des boutons de confirmation de suppression
  const confirmBtn = document.querySelector("#deleteModal .modal-btn-confirm");
  if (confirmBtn) {
    confirmBtn.addEventListener("click", confirmDelete);
  }

  // Gestion des boutons de confirmation d'annulation de commande
  const cancelOrderBtn = document.querySelector(
    "#cancelOrderModal .modal-btn-confirm"
  );
  if (cancelOrderBtn) {
    cancelOrderBtn.addEventListener("click", confirmCancelOrder);
  }

  // Gestion des boutons de confirmation de réactivation de commande
  const reactivateOrderBtn = document.querySelector(
    "#reactivateOrderModal .modal-btn-confirm"
  );
  if (reactivateOrderBtn) {
    reactivateOrderBtn.addEventListener("click", confirmReactivateOrder);
  }

  // Gestion de la fermeture avec Escape
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeModal();
    }
  });

  // Gestion du clic sur l'overlay pour fermer
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal-overlay")) {
      closeModal();
    }
  });

  // Ouvrir la modale de connexion si invité et clic sur panier dans le header
  try {
    var isGuest = document.body.getAttribute("data-is-guest") === "1";
    var cartLink = document.querySelector("header .cart-icon");
    if (isGuest && cartLink) {
      cartLink.addEventListener("click", function (evt) {
        // Si on veut juste afficher une modale au lieu d'aller direct panier
        evt.preventDefault();
        var modal = document.getElementById("loginPromptModal");
        if (modal) {
          modal.classList.add("active");
          document.body.style.overflow = "hidden";
        } else {
          // Fallback vers panier si la modale n'existe pas
          window.location.href = "index.php?action=panier";
        }
      });
    }
  } catch (err) {
    console.warn("Init modal invité panier: ", err);
  }
});
