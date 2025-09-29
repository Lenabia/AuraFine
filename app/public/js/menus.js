/**
 * Gestion des filtres et interactions pour la page des menus
 *
 * @description Filtrage des menus par catégorie et gestion des boutons d'ajout au panier
 * @author AuraFine
 * @version 1.0.0
 */

document.addEventListener("DOMContentLoaded", function () {
  // Éléments DOM - Sélecteurs spécifiques aux menus
  const filterButtons = document.querySelectorAll(".menus-filters .filter-btn");
  const menuCards = document.querySelectorAll(".item-card");
  const filteredCount = document.getElementById("filtered-count");
  // Initialisation des filtres
  initFilters();

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

  // Note: L'ajout au panier est géré par panier.js
});
