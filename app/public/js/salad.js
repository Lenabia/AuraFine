// ========================================
// JAVASCRIPT POUR LA PAGE DES SALADES (PRÉPARÉ POUR PHP)
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // CONFIGURATION POUR INTÉGRATION PHP FUTURE
  // ========================================

  // PHP FUTURE: Ces données seront récupérées depuis la base de données
  // const saladsData = <?php echo json_encode($salads); ?>;
  // const categoriesData = <?php echo json_encode($categories); ?>;

  // Configuration pour les filtres côté serveur
  const serverSideFiltering = false; // PHP FUTURE: true pour filtrage côté serveur
  const serverSideSearch = false; // PHP FUTURE: true pour recherche côté serveur

  // ========================================
  // FILTRES ET RECHERCHE
  // ========================================

  const filterButtons = document.querySelectorAll(".salad-page .filter-btn");
  const searchInput = document.querySelector(".search-input");
  const saladCards = document.querySelectorAll(".salad-card");

  // Debounce function pour optimiser la recherche
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Fonction de filtrage optimisée
  function filterSalads() {
    const activeFilterBtn = document.querySelector(".filter-btn.active");
    if (!activeFilterBtn) return;

    const activeFilter = activeFilterBtn.getAttribute("data-filter");
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";

    // Utiliser requestAnimationFrame pour optimiser les performances
    requestAnimationFrame(() => {
      saladCards.forEach((card) => {
        const categories = card.getAttribute("data-category");
        if (!categories) return;

        const titleEl = card.querySelector("h3");
        const descEl = card.querySelector(".description");

        if (!titleEl || !descEl) return;

        const title = titleEl.textContent
          ? titleEl.textContent.toLowerCase()
          : "";
        const description = descEl.textContent
          ? descEl.textContent.toLowerCase()
          : "";

        const matchesFilter =
          activeFilter === "all" || categories.includes(activeFilter);
        const matchesSearch =
          title.includes(searchTerm) || description.includes(searchTerm);

        if (matchesFilter && matchesSearch) {
          card.style.display = "block";
          card.style.animation = "fadeInUp 0.5s ease forwards";
        } else {
          card.style.display = "none";
        }
      });
    });
  }

  // Événements pour les filtres
  filterButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Retirer la classe active de tous les boutons
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      // Ajouter la classe active au bouton cliqué
      this.classList.add("active");
      // Appliquer le filtre
      filterSalads();
    });
  });

  // Événement pour la recherche avec debounce
  if (searchInput) {
    const debouncedFilter = debounce(filterSalads, 300);
    searchInput.addEventListener("input", debouncedFilter);

    // PHP FUTURE: Gestion de la soumission du formulaire pour recherche côté serveur
    const searchForm = searchInput.closest("form");
    if (searchForm && serverSideSearch) {
      searchForm.addEventListener("submit", function (e) {
        // PHP FUTURE: Soumission vers le serveur pour filtrage côté serveur
        // this.submit();
      });
    }
  }

  // ========================================
  // CONTRÔLES DE QUANTITÉ (UTILISENT CARD.JS)
  // ========================================

  // Les contrôles de quantité sont maintenant gérés par card.js
  // Cette section est conservée pour la compatibilité mais les événements sont dans card.js

  // ========================================
  // Note: L'ajout au panier est géré par panier.js

  // ========================================
  // ANIMATIONS AU SCROLL (OPTIMISÉ)
  // ========================================

  // Vérifier si l'observer n'existe pas déjà (éviter les doublons)
  if (!window.saladObserver) {
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    };

    window.saladObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
        }
      });
    }, observerOptions);

    // Observer tous les éléments avec la classe animate-on-scroll
    document.querySelectorAll(".animate-on-scroll").forEach((el) => {
      window.saladObserver.observe(el);
    });
  }

  // ========================================
  // FONCTIONS POUR INTÉGRATION PHP FUTURE
  // ========================================

  // PHP FUTURE: Fonction pour charger les salades depuis le serveur
  function loadSaladsFromServer(filters = {}) {
    // PHP FUTURE: Appel AJAX vers le serveur
    /*
    fetch('/api/salads?' + new URLSearchParams(filters))
      .then(response => response.json())
      .then(data => {
        updateSaladsGrid(data.salads);
        updateResultsCount(data.total);
      })
      .catch(error => console.error('Erreur:', error));
    */
  }

  // PHP FUTURE: Fonction pour mettre à jour la grille des salades
  function updateSaladsGrid(salads) {
    const container = document.querySelector(".salad-container");
    if (!container) return;

    // PHP FUTURE: Vider et reconstruire la grille avec les nouvelles données
    // container.innerHTML = '';
    // salads.forEach(salad => {
    //   container.appendChild(createSaladCard(salad));
    // });
  }

  // PHP FUTURE: Fonction pour créer une carte de salade dynamiquement
  function createSaladCard(salad) {
    // PHP FUTURE: Créer l'élément HTML pour une salade
    const card = document.createElement("article");
    card.className = "salad-card";
    card.setAttribute("data-category", salad.categorie);
    card.setAttribute("data-salad-id", salad.id);

    // PHP FUTURE: Remplir le contenu de la carte
    // card.innerHTML = `...`;

    return card;
  }

  console.log("Page des salades initialisée avec succès !");

  // PHP FUTURE: Initialiser avec les données du serveur
  // loadSaladsFromServer();
});
