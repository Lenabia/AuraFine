/**
 * Gestion des quartiers dans l'interface admin
 * - Vérification des références avant suppression
 * - Modales de suppression et d'édition
 * - Gestion des quartiers désactivés
 */

// Variables globales pour les quartiers (déclarées dans modal.js)

/**
 * Vérifie les références d'un quartier avant suppression
 * @param {number} neighborhoodId - ID du quartier
 * @param {string} neighborhoodName - Nom du quartier
 * @param {number} cityId - ID de la ville
 */
function checkNeighborhoodReferences(neighborhoodId, neighborhoodName, cityId) {
  currentNeighborhoodId = neighborhoodId;
  currentCityId = cityId;

  // Récupérer le token CSRF
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.getAttribute("content") : "";

  if (!csrfToken) {
    console.error("Token CSRF non trouvé");
    // Fallback: procéder à la suppression normale
    showDeleteModal(
      neighborhoodName,
      "ce quartier",
      `delete-neighborhood-${neighborhoodId}`
    );
    return;
  }

  // Faire une requête AJAX pour vérifier les références
  fetch(
    `index.php?action=admin-neighborhoods-delete&city=${cityId}&id=${neighborhoodId}`,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: `csrf_token=${csrfToken}`,
    }
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.has_references) {
        showNeighborhoodDeleteModal(neighborhoodName, data.references);
      } else {
        // Pas de références, procéder à la suppression normale
        showDeleteModal(
          neighborhoodName,
          "ce quartier",
          `delete-neighborhood-${neighborhoodId}`
        );
      }
    })
    .catch((error) => {
      console.error("Erreur:", error);
      // En cas d'erreur, procéder à la suppression normale
      showDeleteModal(
        neighborhoodName,
        "ce quartier",
        `delete-neighborhood-${neighborhoodId}`
      );
    });
}

/**
 * Affiche la modale de suppression avec références
 * @param {string} neighborhoodName - Nom du quartier
 * @param {object} references - Références trouvées
 */
function showNeighborhoodDeleteModal(neighborhoodName, references) {
  const modal = document.getElementById("neighborhoodDeleteModal");
  const nameEl = document.getElementById("neighborhoodName");
  const listEl = document.getElementById("referencesList");

  if (!modal || !nameEl || !listEl) {
    console.error("Éléments de la modale de suppression non trouvés");
    return;
  }

  nameEl.textContent = neighborhoodName;
  listEl.innerHTML = "";

  if (references.orders) {
    const li = document.createElement("li");
    li.innerHTML = `<strong>${references.orders}</strong> commande(s)`;
    listEl.appendChild(li);
  }

  if (references.users) {
    const li = document.createElement("li");
    li.innerHTML = `<strong>${references.users}</strong> utilisateur(s)`;
    listEl.appendChild(li);
  }

  modal.classList.add("active");
  document.body.style.overflow = "hidden";
}

/**
 * Ferme la modale de suppression de quartier
 */
function closeNeighborhoodModal() {
  const modal = document.getElementById("neighborhoodDeleteModal");
  if (modal) {
    modal.classList.remove("active");
    document.body.style.overflow = "";
  }
  currentNeighborhoodId = null;
  currentCityId = null;
}

/**
 * Désactive un quartier
 */
function deactivateNeighborhood() {
  if (currentNeighborhoodId && currentCityId) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = `index.php?action=admin-neighborhoods-deactivate&city=${currentCityId}&id=${currentNeighborhoodId}`;

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute("content") : "";

    if (csrfToken) {
      const csrfInput = document.createElement("input");
      csrfInput.type = "hidden";
      csrfInput.name = "csrf_token";
      csrfInput.value = csrfToken;
      form.appendChild(csrfInput);
    }

    document.body.appendChild(form);
    form.submit();
  }
}

/**
 * Met un quartier en mode édition
 */
function editNeighborhood() {
  // Trouver le formulaire d'édition correspondant
  const editForm = document.querySelector(
    `form[id^="edit-neighborhood-${currentNeighborhoodId}"]`
  );
  if (editForm) {
    // Focuser sur le champ de nom
    const nameInput = editForm.querySelector('input[name="name"]');
    if (nameInput) {
      nameInput.focus();
      nameInput.select();
    }
  }
  closeNeighborhoodModal();
}

// Attendre que le DOM soit chargé et que modal.js soit disponible
document.addEventListener("DOMContentLoaded", function () {
  // Vérifier que les fonctions de modal.js sont disponibles
  if (typeof showDeleteModal === "undefined") {
    console.error("modal.js n'est pas chargé correctement");
    return;
  }

  console.log("Admin-delivery.js chargé correctement");

  // Rendre les fonctions globales
  window.checkNeighborhoodReferences = checkNeighborhoodReferences;
  window.showNeighborhoodDeleteModal = showNeighborhoodDeleteModal;
  window.closeNeighborhoodModal = closeNeighborhoodModal;
  window.deactivateNeighborhood = deactivateNeighborhood;
  window.editNeighborhood = editNeighborhood;
});
