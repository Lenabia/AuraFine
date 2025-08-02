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

  const filterButtons = document.querySelectorAll(".filter-btn");
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
  // CONTRÔLES DE QUANTITÉ
  // ========================================

  const quantityControls = document.querySelectorAll(".quantity-controls");

  quantityControls.forEach((control) => {
    const minusBtn = control.querySelector(".minus");
    const plusBtn = control.querySelector(".plus");
    const quantitySpan = control.querySelector(".quantity");

    if (minusBtn && plusBtn && quantitySpan) {
      minusBtn.addEventListener("click", function () {
        let currentQty = parseInt(quantitySpan.textContent || "1");
        if (currentQty > 1) {
          quantitySpan.textContent = (currentQty - 1).toString();
          updateAddButton(control);
        }
      });

      plusBtn.addEventListener("click", function () {
        let currentQty = parseInt(quantitySpan.textContent || "1");
        quantitySpan.textContent = (currentQty + 1).toString();
        updateAddButton(control);
      });
    }
  });

  // ========================================
  // AJOUT AU PANIER
  // ========================================

  const addToCartButtons = document.querySelectorAll(".add-to-cart-btn");

  addToCartButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const saladId = this.getAttribute("data-salad-id");
      const card = this.closest(".salad-card");

      if (!card) return;

      const titleEl = card.querySelector("h3");
      const priceEl = card.querySelector(".price");
      const quantityEl = card.querySelector(".quantity");

      if (!titleEl || !priceEl || !quantityEl) return;

      const title = titleEl.textContent || "";
      const price = priceEl.textContent || "";
      const quantity = quantityEl.textContent || "1";

      addToCart(saladId, title, price, quantity);

      // Animation de confirmation
      this.innerHTML = '<i class="fas fa-check"></i> Ajouté !';
      this.style.background = "#4CAF50";

      setTimeout(() => {
        this.innerHTML = '<i class="fas fa-shopping-cart"></i> Ajouter';
        this.style.background = "";
      }, 2000);
    });
  });

  // Fonction d'ajout au panier (à connecter avec votre système de panier)
  function addToCart(id, title, price, quantity) {
    const item = {
      id: id,
      title: title,
      price: parseFloat(price.replace("€", "")) || 0,
      quantity: parseInt(quantity) || 1,
    };

    // Récupérer le panier existant ou en créer un nouveau
    let cart = [];
    try {
      const cartData = localStorage.getItem("aurafineCart");
      cart = cartData ? JSON.parse(cartData) : [];
    } catch (error) {
      console.error("Erreur lors de la lecture du panier:", error);
      cart = [];
    }

    // Vérifier si l'article existe déjà dans le panier
    const existingItemIndex = cart.findIndex((item) => item.id === id);

    if (existingItemIndex !== -1) {
      // Mettre à jour la quantité
      cart[existingItemIndex].quantity += parseInt(quantity) || 1;
    } else {
      // Ajouter le nouvel article
      cart.push(item);
    }

    // Sauvegarder le panier
    try {
      localStorage.setItem("aurafineCart", JSON.stringify(cart));
      showNotification(`${title} ajouté au panier !`);
      console.log("Panier mis à jour:", cart);
    } catch (error) {
      console.error("Erreur lors de la sauvegarde du panier:", error);
    }
  }

  // ========================================
  // NOTIFICATIONS
  // ========================================

  function showNotification(message) {
    // Créer la notification
    const notification = document.createElement("div");
    notification.className = "notification";
    notification.textContent = message;
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #4CAF50;
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 1000;
      transform: translateX(100%);
      transition: transform 0.3s ease;
    `;

    document.body.appendChild(notification);

    // Animer l'entrée
    setTimeout(() => {
      notification.style.transform = "translateX(0)";
    }, 100);

    // Supprimer après 3 secondes
    setTimeout(() => {
      notification.style.transform = "translateX(100%)";
      setTimeout(() => {
        if (document.body.contains(notification)) {
          document.body.removeChild(notification);
        }
      }, 300);
    }, 3000);
  }

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
  // EFFET HOVER SUR LES IMAGES
  // ========================================

  const cardImages = document.querySelectorAll(".card-image img");

  cardImages.forEach((img) => {
    img.addEventListener("mouseenter", function () {
      this.style.transform = "scale(1.05)";
    });

    img.addEventListener("mouseleave", function () {
      this.style.transform = "scale(1)";
    });
  });

  // ========================================
  // FONCTION UTILITAIRE POUR METTRE À JOUR LE BOUTON D'AJOUT
  // ========================================

  function updateAddButton(control) {
    const card = control.closest(".salad-card");
    if (!card) return;

    const addButton = card.querySelector(".add-to-cart-btn");
    const quantityEl = control.querySelector(".quantity");

    if (!addButton || !quantityEl) return;

    const quantity = quantityEl.textContent || "1";

    // Mettre à jour le texte du bouton si nécessaire
    if (parseInt(quantity) > 1) {
      addButton.innerHTML = `<i class="fas fa-shopping-cart"></i> Ajouter (${quantity})`;
    } else {
      addButton.innerHTML = '<i class="fas fa-shopping-cart"></i> Ajouter';
    }
  }

  // ========================================
  // GESTION DES ERREURS
  // ========================================

  // Gestion des erreurs de chargement d'images
  const images = document.querySelectorAll("img");
  images.forEach((img) => {
    img.addEventListener("error", function () {
      this.src = "/app/public/images/wireframes/salad.webp"; // Image de fallback
      this.alt = "Image non disponible";
    });
  });

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

  // PHP FUTURE: Fonction pour ajouter au panier via serveur
  function addToCartServer(saladId, quantity) {
    // PHP FUTURE: Appel AJAX vers le serveur
    /*
    fetch('/api/cart/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        salad_id: saladId,
        quantity: quantity
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showNotification('Salade ajoutée au panier');
        updateCartCount(data.cart_count);
      }
    })
    .catch(error => console.error('Erreur:', error));
    */
  }

  console.log("Page des salades initialisée avec succès !");

  // PHP FUTURE: Initialiser avec les données du serveur
  // loadSaladsFromServer();
});
