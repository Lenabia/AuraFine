/**
 * Gestion des modales personnalisées
 * Remplace les alertes natives par des modales plus user-friendly
 */

// Variables globales pour les modales
let currentDeleteForm = null;

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

// Initialisation quand le DOM est chargé
document.addEventListener("DOMContentLoaded", function () {
  console.log("Modal.js chargé correctement");

  // Gestion des boutons de confirmation de suppression
  const confirmBtn = document.querySelector(".modal-btn-confirm");
  if (confirmBtn) {
    confirmBtn.addEventListener("click", confirmDelete);
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
