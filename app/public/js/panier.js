// panier.js: gestion AJAX du panier (ajout, +/−, suppression) et badge
(function () {
  // Déclaration TypeScript pour éviter les erreurs
  if (typeof window !== "undefined") {
    window["AuraFineUtils"] = window["AuraFineUtils"] || {};
  }
  var csrf = "";
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  if (csrfMeta) {
    csrf = csrfMeta.getAttribute("content") || "";
  }
  var cartCountEl = document.querySelector(".cart-count");

  function post(url, data) {
    var form = new URLSearchParams();
    Object.keys(data).forEach(function (k) {
      form.append(k, data[k]);
    });
    return fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: form.toString(),
    }).then(function (r) {
      return r.json();
    });
  }

  function updateBadge(count) {
    if (!cartCountEl) {
      // Essayer de retrouver l'élément au cas où il ne serait pas encore chargé
      cartCountEl = document.querySelector(".cart-count");
    }
    if (!cartCountEl) return;
    cartCountEl.textContent = String(count || 0);
  }

  // Déterminer type/id produit à partir d'un bouton
  function getProductPayload(btn) {
    if (btn.dataset.menuId)
      return { product_type: "menu", product_id: btn.dataset.menuId };
    if (btn.dataset.saladId)
      return { product_type: "salad", product_id: btn.dataset.saladId };
    if (btn.dataset.drinkId)
      return { product_type: "drink", product_id: btn.dataset.drinkId };
    if (btn.dataset.itemId)
      return { product_type: "fruit", product_id: btn.dataset.itemId };
    if (btn.dataset.type && btn.dataset.id)
      return { product_type: btn.dataset.type, product_id: btn.dataset.id };
    return null;
  }

  // Ajout au panier sur pages produits
  function bindAddToCart() {
    document
      .querySelectorAll(".btn-add-to-cart, .add-to-cart-btn")
      .forEach(function (btn) {
        btn.addEventListener("click", function (e) {
          e.preventDefault();
          var payload = getProductPayload(btn);
          if (!payload) return;
          payload.quantity = 1;
          payload.csrf_token = csrf;
          post("index.php?action=panier-add", payload)
            .then(function (res) {
              if (!res || !res.success) {
                if (res && res.error === "login_required") {
                  window.location.href = "index.php?action=login";
                }
                return;
              }
              updateBadge(res.count);
              // Afficher notification de succès
              if (
                window["AuraFineUtils"] &&
                window["AuraFineUtils"].showNotification
              ) {
                window["AuraFineUtils"].showNotification(
                  "Produit ajouté au panier !"
                );
              }
              // Pas besoin de mettre à jour les totaux pour l'ajout, on n'est pas sur la page panier
            })
            .catch(function () {});
        });
      });
  }

  // Interactions sur la page panier (pas de rendu, juste les boutons)
  function initCartPage() {
    var container = document.getElementById("panier-articles-container");
    if (!container) return;

    var itemsJson = container.getAttribute("data-items") || "[]";
    var items = [];
    try {
      items = JSON.parse(itemsJson);
    } catch (e) {
      items = [];
    }

    function render() {
      // Le rendu est géré par PHP, on ne fait que les interactions
      // Ne pas recalculer les totaux, ils sont déjà corrects côté PHP
      bindCartButtons();
    }

    function updateTotals(totalFromServer = null) {
      var total =
        totalFromServer !== null
          ? totalFromServer
          : items.reduce(function (acc, it) {
              return acc + Number(it.price || 0) * Number(it.quantity || 0);
            }, 0);
      var st = document.getElementById("sous-total");
      var tt = document.getElementById("total-commande");
      if (st) st.textContent = Math.round(total) + " FCFA";
      if (tt) tt.textContent = Math.round(total) + " FCFA";
    }

    function updateLocalDisplay() {
      // Mettre à jour les quantités et sous-totaux dans le DOM
      if (!container) return;
      items.forEach(function (item) {
        var row = container
          ? container.querySelector(
              '[data-type="' +
                item.product_type +
                '"][data-id="' +
                item.product_id +
                '"]'
            )
          : null;
        if (row) {
          var quantitySpan = row.querySelector(".quantity");
          var subtotalSpan = row.querySelector(".subtotal");
          if (quantitySpan) quantitySpan.textContent = item.quantity;
          if (subtotalSpan)
            subtotalSpan.textContent = Math.round(item.subtotal) + " FCFA";
        }
      });
    }

    function syncUpdate(product_type, product_id, quantity) {
      var payload = {
        csrf_token: csrf,
        product_type: product_type,
        product_id: product_id,
        quantity: quantity,
      };
      return post("index.php?action=panier-update", payload).then(function (
        res
      ) {
        if (!res || !res.success) {
          if (res && res.error === "login_required")
            window.location.href = "index.php?action=login";
          return;
        }
        items = res.items || [];
        updateBadge(res.count);
        updateTotals(res.total);
        updateLocalDisplay(); // Mettre à jour l'affichage des sous-totaux
        bindCartButtons();
      });
    }

    function syncRemove(product_type, product_id) {
      var payload = {
        csrf_token: csrf,
        product_type: product_type,
        product_id: product_id,
      };
      return post("index.php?action=panier-remove", payload).then(function (
        res
      ) {
        if (!res || !res.success) {
          if (res && res.error === "login_required")
            window.location.href = "index.php?action=login";
          return;
        }
        items = res.items || [];
        updateBadge(res.count);
        updateTotals(res.total);
        updateLocalDisplay(); // Mettre à jour l'affichage des sous-totaux
        bindCartButtons();
      });
    }

    function bindCartButtons() {
      if (!container) return;
      container.querySelectorAll(".panier-item").forEach(function (row) {
        var type = row.getAttribute("data-type");
        var id = row.getAttribute("data-id");
        var minus = row.querySelector(".qty-btn.minus");
        var plus = row.querySelector(".qty-btn.plus");
        var remove = row.querySelector(".remove-btn");
        minus &&
          minus.addEventListener("click", function () {
            var quantitySpan = row.querySelector(".quantity");
            if (!quantitySpan) return;
            var currentQty = Number(quantitySpan.textContent) || 0;
            var q = Math.max(0, currentQty - 1);
            if (q === 0) {
              // Supprimer l'élément du DOM immédiatement
              row.remove();
              return syncRemove(type, id);
            }
            syncUpdate(type, id, q);
          });
        plus &&
          plus.addEventListener("click", function () {
            var quantitySpan = row.querySelector(".quantity");
            if (!quantitySpan) return;
            var currentQty = Number(quantitySpan.textContent) || 0;
            var q = currentQty + 1;
            syncUpdate(type, id, q);
          });
        remove &&
          remove.addEventListener("click", function () {
            // Supprimer l'élément du DOM immédiatement
            row.remove();
            syncRemove(type, id);
          });
      });
    }

    render();
  }

  // Fonction pour initialiser le badge au chargement
  function initBadge() {
    // Initialiser le badge avec la quantité du panier au chargement
    var container = document.getElementById("panier-articles-container");
    if (container) {
      // Sur la page panier, utiliser les données déjà chargées
      var itemsJson = container.getAttribute("data-items") || "[]";
      try {
        var items = JSON.parse(itemsJson);
        var totalQty = items.reduce(function (acc, it) {
          return acc + (it.quantity || 0);
        }, 0);
        updateBadge(totalQty);
      } catch (e) {
        updateBadge(0);
      }
    } else {
      // Sur les autres pages, récupérer le count depuis le serveur via AJAX
      fetch("index.php?action=panier-count", {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data && data.success) {
            updateBadge(data.count);
          } else {
            updateBadge(0);
          }
        })
        .catch(function (error) {
          console.log("Erreur lors de la récupération du panier:", error);
          updateBadge(0);
        });
    }
  }

  // Init
  bindAddToCart();
  initCartPage();
  initBadge();
})();
