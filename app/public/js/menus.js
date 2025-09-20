/**
 * Gestion des filtres et interactions pour la page des menus
 *
 * @description Filtrage des menus par catégorie et gestion des boutons d'ajout au panier
 * @author AuraFine
 * @version 1.0.0
 */

document.addEventListener("DOMContentLoaded", function () {
  // Éléments DOM
  const filterButtons = document.querySelectorAll(".filter-btn");
  const menuCards = document.querySelectorAll(".menu-card");
  const filteredCount = document.getElementById("filtered-count");
  const addToCartButtons = document.querySelectorAll(".btn-add-to-cart");

  // Initialisation des filtres
  initFilters();

  // Initialisation des boutons d'ajout au panier
  initAddToCartButtons();

  /**
   * Initialise le système de filtrage des menus
   */
  function initFilters() {
    if (filterButtons.length === 0) return;

    filterButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const category = this.dataset.category;
        filterMenusByCategory(category);
        updateActiveFilterButton(this);
      });
    });
  }

  /**
   * Filtre les menus par catégorie
   * @param {string} category - ID de la catégorie ou 'all' pour toutes
   */
  function filterMenusByCategory(category) {
    let visibleCount = 0;

    menuCards.forEach((card) => {
      if (category === "all" || card.dataset.category === category) {
        card.style.display = "block";
        visibleCount++;
      } else {
        card.style.display = "none";
      }
    });

    // Mettre à jour le compteur
    updateFilteredCount(visibleCount);
  }

  /**
   * Met à jour le bouton de filtre actif
   * @param {HTMLElement} activeButton - Bouton à marquer comme actif
   */
  function updateActiveFilterButton(activeButton) {
    filterButtons.forEach((btn) => btn.classList.remove("active"));
    activeButton.classList.add("active");
  }

  /**
   * Met à jour le compteur de menus filtrés
   * @param {number} count - Nombre de menus visibles
   */
  function updateFilteredCount(count) {
    if (filteredCount) {
      filteredCount.textContent = count;
    }
  }

  /**
   * Initialise les boutons d'ajout au panier
   */
  function initAddToCartButtons() {
    if (addToCartButtons.length === 0) return;

    addToCartButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const menuId = this.dataset.menuId;
        handleAddToCart(this, menuId);
      });
    });
  }

  /**
   * Gère l'ajout d'un menu au panier
   * @param {HTMLElement} button - Bouton cliqué
   * @param {string} menuId - ID du menu à ajouter
   */
  function handleAddToCart(button, menuId) {
    // Animation de feedback
    showAddToCartFeedback(button);

    // TODO: Implémenter la logique d'ajout au panier
    console.log("Ajouter au panier menu ID:", menuId);

    // Réinitialiser le bouton après 2 secondes
    setTimeout(() => {
      resetAddToCartButton(button);
    }, 2000);
  }

  /**
   * Affiche le feedback d'ajout au panier
   * @param {HTMLElement} button - Bouton à modifier
   */
  function showAddToCartFeedback(button) {
    button.innerHTML = '<i class="fas fa-check"></i> Ajouté !';
    button.classList.add("added");
  }

  /**
   * Remet le bouton dans son état initial
   * @param {HTMLElement} button - Bouton à réinitialiser
   */
  function resetAddToCartButton(button) {
    button.innerHTML = '<i class="fas fa-plus"></i> Ajouter au panier';
    button.classList.remove("added");
  }
});
