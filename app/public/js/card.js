// ========================================
// GESTION DU PANIER - CARD.JS
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // VARIABLES GLOBALES PANIER
  // ========================================
  const cartIcon = document.querySelector(".cart-icon");
  const cartCount = document.querySelector(".cart-count");

  // ========================================
  // FONCTIONS PRINCIPALES DU PANIER
  // ========================================

  // Fonction d'ajout au panier (réutilisable sur toutes les pages)
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
      updateCartCount();
      console.log("Panier mis à jour:", cart);
    } catch (error) {
      console.error("Erreur lors de la sauvegarde du panier:", error);
    }
  }

  // Mettre à jour le compteur du panier
  function updateCartCount() {
    if (!cartCount) return;

    try {
      const cartData = localStorage.getItem("aurafineCart");
      const cart = cartData ? JSON.parse(cartData) : [];
      const totalItems = cart.reduce(
        (total, item) => total + (item.quantity || 0),
        0
      );

      cartCount.textContent = totalItems;

      // Animation si le panier n'est pas vide
      if (totalItems > 0) {
        cartCount.classList.add("pulse");
        setTimeout(() => {
          if (cartCount) {
            cartCount.classList.remove("pulse");
          }
        }, 500);
      }
    } catch (error) {
      console.error("Erreur lors de la lecture du panier:", error);
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
  // GESTION DES QUANTITÉS
  // ========================================

  // Initialiser les contrôles de quantité sur la page
  function initQuantityControls() {
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
  }

  // Mettre à jour le bouton d'ajout selon la quantité
  function updateAddButton(control) {
    const card = control.closest(".salad-card, .produit-card");
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
  // GESTION DES BOUTONS D'AJOUT AU PANIER
  // ========================================

  // Initialiser les boutons d'ajout au panier
  function initAddToCartButtons() {
    const addToCartButtons = document.querySelectorAll(".add-to-cart-btn");

    addToCartButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const saladId = this.getAttribute("data-salad-id");
        const card = this.closest(".salad-card, .produit-card");

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
  }

  // ========================================
  // INITIALISATION
  // ========================================

  // Initialiser le panier au chargement de la page
  if (cartIcon && cartCount) {
    updateCartCount();

    // Écouter les changements du localStorage (pour les autres pages)
    window.addEventListener("storage", function (e) {
      if (e.key === "aurafineCart") {
        updateCartCount();
      }
    });
  }

  // Initialiser les contrôles de quantité
  initQuantityControls();

  // Initialiser les boutons d'ajout au panier
  initAddToCartButtons();

  // ========================================
  // ANIMATIONS CSS POUR LE PANIER
  // ========================================

  // Ajouter l'animation pulse pour le panier
  const style = document.createElement("style");
  style.textContent = `
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); }
    }
  `;
  document.head.appendChild(style);

  // ========================================
  // AFFICHAGE DE LA PAGE PANIER
  // ========================================

  // Fonction pour afficher les articles du panier
  function displayCartItems() {
    const articlesContainer = document.getElementById(
      "panier-articles-container"
    );
    const panierVide = document.getElementById("panier-vide");
    const sousTotal = document.getElementById("sous-total");
    const totalCommande = document.getElementById("total-commande");

    if (!articlesContainer || !panierVide) return;

    try {
      const cartData = localStorage.getItem("aurafineCart");
      const cart = cartData ? JSON.parse(cartData) : [];

      if (cart.length === 0) {
        // Afficher le panier vide
        articlesContainer.style.display = "none";
        panierVide.style.display = "block";
        if (sousTotal) sousTotal.textContent = "0,00 €";
        if (totalCommande) totalCommande.textContent = "0,00 €";
        return;
      }

      // Cacher le message panier vide
      panierVide.style.display = "none";
      articlesContainer.style.display = "block";

      // Vider le conteneur
      articlesContainer.innerHTML = "";

      let total = 0;

      // Créer chaque article du panier
      cart.forEach((item) => {
        const articleElement = createCartItemElement(item);
        articlesContainer.appendChild(articleElement);
        total += item.price * item.quantity;
      });

      // Mettre à jour les totaux
      if (sousTotal) sousTotal.textContent = total.toFixed(2) + " €";
      if (totalCommande) totalCommande.textContent = total.toFixed(2) + " €";
    } catch (error) {
      console.error("Erreur lors de l'affichage du panier:", error);
    }
  }

  // Créer un élément article pour le panier
  function createCartItemElement(item) {
    const articleDiv = document.createElement("div");
    articleDiv.className = "panier-article";
    articleDiv.innerHTML = `
      <div class="article-image">
        <img src="/app/public/images/salade1.webp" alt="${item.title}">
      </div>
      <div class="article-details">
        <h3>${item.title}</h3>
        <p class="article-description">Description de l'article</p>
        <div class="article-meta">
          <span class="article-category">Catégorie</span>
          <span class="article-calories">Calories</span>
        </div>
      </div>
      <div class="article-quantity">
        <div class="quantity-controls">
          <button class="qty-btn minus" data-id="${
            item.id
          }" aria-label="Diminuer la quantité">-</button>
          <span class="quantity">${item.quantity}</span>
          <button class="qty-btn plus" data-id="${
            item.id
          }" aria-label="Augmenter la quantité">+</button>
        </div>
      </div>
      <div class="article-price">
        <span class="price-unit">${item.price.toFixed(2)} €</span>
        <span class="price-total">${(item.price * item.quantity).toFixed(
          2
        )} €</span>
      </div>
      <button class="remove-btn" data-id="${
        item.id
      }" aria-label="Supprimer l'article">
        <i class="fas fa-trash"></i>
      </button>
    `;

    // Ajouter les événements pour les boutons
    const minusBtn = articleDiv.querySelector(".minus");
    const plusBtn = articleDiv.querySelector(".plus");
    const removeBtn = articleDiv.querySelector(".remove-btn");

    minusBtn.addEventListener("click", () =>
      updateCartItemQuantity(item.id, -1)
    );
    plusBtn.addEventListener("click", () => updateCartItemQuantity(item.id, 1));
    removeBtn.addEventListener("click", () => removeCartItem(item.id));

    return articleDiv;
  }

  // Mettre à jour la quantité d'un article
  function updateCartItemQuantity(itemId, change) {
    try {
      const cartData = localStorage.getItem("aurafineCart");
      let cart = cartData ? JSON.parse(cartData) : [];

      const itemIndex = cart.findIndex((item) => item.id === itemId);
      if (itemIndex === -1) return;

      const newQuantity = cart[itemIndex].quantity + change;
      if (newQuantity <= 0) {
        removeCartItem(itemId);
        return;
      }

      cart[itemIndex].quantity = newQuantity;
      localStorage.setItem("aurafineCart", JSON.stringify(cart));

      // Mettre à jour l'affichage
      displayCartItems();
      updateCartCount();
    } catch (error) {
      console.error("Erreur lors de la mise à jour de la quantité:", error);
    }
  }

  // Supprimer un article du panier
  function removeCartItem(itemId) {
    try {
      const cartData = localStorage.getItem("aurafineCart");
      let cart = cartData ? JSON.parse(cartData) : [];

      cart = cart.filter((item) => item.id !== itemId);
      localStorage.setItem("aurafineCart", JSON.stringify(cart));

      // Mettre à jour l'affichage
      displayCartItems();
      updateCartCount();

      showNotification("Article supprimé du panier");
    } catch (error) {
      console.error("Erreur lors de la suppression:", error);
    }
  }

  // Initialiser la page panier si on est dessus
  if (window.location.href.includes("action=panier")) {
    displayCartItems();
  }

  // ========================================
  // EXPOSITION DES FONCTIONS (pour utilisation externe)
  // ========================================

  // Rendre les fonctions disponibles globalement si nécessaire
  window.AuraFineCart = {
    addToCart: addToCart,
    updateCartCount: updateCartCount,
    showNotification: showNotification,
    initQuantityControls: initQuantityControls,
    initAddToCartButtons: initAddToCartButtons,
    displayCartItems: displayCartItems,
    updateCartItemQuantity: updateCartItemQuantity,
    removeCartItem: removeCartItem,
  };

  console.log("Système de panier initialisé avec succès !");
});
