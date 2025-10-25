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

  // Messages d'erreur centralisés
  function getErrorMessage(errorCode) {
    var errorMessages = {
      EMPTY_CART: "Votre panier est vide",
      LOGIN_REQUIRED: "Vous devez être connecté pour effectuer cette action",
      INVALID_CSRF: "Erreur de sécurité, veuillez réessayer",
      ORDER_CREATION_FAILED: "Erreur lors de la création de la commande",
      INVALID_CITY: "Veuillez sélectionner une ville",
      INVALID_ZONE: "Zone de livraison invalide",
      INVALID_NEIGHBORHOOD: "Quartier invalide",
      OPERATION_FAILED: "Opération échouée",
      INVALID_DATA: "Données invalides",
      UPDATE_FAILED: "Échec de la mise à jour",
    };
    return errorMessages[errorCode] || null;
  }

  // Validation du formulaire de livraison
  // Fonction pour combiner le préfixe +221 avec le suffixe
  function combinePhoneNumber() {
    var phoneInputs = document.querySelectorAll('input[name="phone_suffix"]');
    phoneInputs.forEach(function (input) {
      var phoneFullInput = input.parentNode.querySelector(
        'input[name="phone"]'
      );
      if (phoneFullInput) {
        var suffix = input.value.trim();
        phoneFullInput.value = "+221" + suffix;
      }
    });
  }

  function validateDeliveryForm() {
    var isValid = true;

    // Combiner les numéros de téléphone avant validation
    combinePhoneNumber();

    // Réinitialiser les messages d'erreur
    clearErrorMessages();

    // Valider la ville
    var citySelect = document.getElementById("cities_id");
    if (!citySelect || !citySelect.value) {
      showFieldError("cities_id", "Veuillez sélectionner une ville");
      isValid = false;
    }

    // Valider le quartier
    var neighborhoodSelect = document.getElementById("neighborhoods_id");
    if (!neighborhoodSelect || !neighborhoodSelect.value) {
      showFieldError("neighborhoods_id", "Veuillez sélectionner un quartier");
      isValid = false;
    }

    // Validation invités si les champs existent
    var fn = document.getElementById("first_name");
    var ln = document.getElementById("last_name");
    var ph = document.getElementById("phone");
    var phSuffix = document.querySelector('input[name="phone_suffix"]');
    var em = document.getElementById("email");
    var addr = document.getElementById("address_line");

    var nameRegex = /^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,50}$/;
    var phoneRegex = /^\+221[0-9]{9}$/;
    var phoneSuffixRegex = /^[0-9]{9}$/;
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (fn && !nameRegex.test(fn.value.trim())) {
      showFieldError("first_name", "Prénom invalide");
      isValid = false;
    }
    if (ln && !nameRegex.test(ln.value.trim())) {
      showFieldError("last_name", "Nom invalide");
      isValid = false;
    }
    if (phSuffix) {
      if (!phoneSuffixRegex.test(phSuffix.value.trim())) {
        showFieldError(
          "phone",
          "Le numéro doit contenir exactement 9 chiffres (ex: 771234567)"
        );
        isValid = false;
      }
    } else if (ph && !phoneRegex.test(ph.value.trim())) {
      showFieldError(
        "phone",
        "Le numéro doit commencer par +221 suivi de 9 chiffres (ex: +221771234567)"
      );
      isValid = false;
    }
    if (em) {
      var ev = (em.value || "").trim();
      if (ev && !emailRegex.test(ev)) {
        showFieldError("email", "Email invalide");
        isValid = false;
      }
    }

    if (addr) {
      var av = (addr.value || "").trim();
      if (!av || av.length < 5) {
        showFieldError("address_line", "Adresse complète requise");
        isValid = false;
      }
    }

    return isValid;
  }

  // Afficher un message d'erreur pour un champ
  function showFieldError(fieldId, message) {
    var errorEl = document.getElementById(fieldId + "-error");
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.style.display = "block";
    }
  }

  // Effacer tous les messages d'erreur
  function clearErrorMessages() {
    var errorElements = document.querySelectorAll(".error-message");
    errorElements.forEach(function (el) {
      el.style.display = "none";
      el.textContent = "";
    });
  }

  // Vider tous les champs du formulaire de livraison
  function clearDeliveryForm() {
    // Utiliser les fonctions existantes
    clearErrorMessages();
    clearNeighborhoods();

    // Vider les autres champs
    var fieldsToClear = [
      "first_name",
      "last_name",
      "phone",
      "phone_suffix",
      "email",
      "address_line",
      "delivery_comment",
    ];
    fieldsToClear.forEach(function (fieldName) {
      var field = document.querySelector('[name="' + fieldName + '"]');
      if (field) field.value = "";
    });

    // Réinitialiser la ville
    var citySelect = document.getElementById("cities_id");
    if (citySelect) citySelect.selectedIndex = 0;
  }

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

  // Fonction pour basculer l'affichage du formulaire de livraison
  window["toggleDeliveryForm"] = function () {
    var form = document.getElementById("delivery-form");
    var display = document.querySelector(".delivery-info-display");
    if (form && display) {
      if (form.style) {
        // @ts-ignore
        form.style.display = form.style.display === "none" ? "block" : "none";
      }
      if (display.style) {
        // @ts-ignore
        display.style.display =
          display.style.display === "none" ? "block" : "none";
      }
    }
  };

  // Gestion du formulaire de livraison
  function initDeliveryForm() {
    var form = document.getElementById("delivery-choice-form");
    if (!form) return;

    // Gestion du changement de ville
    var citySelect = document.getElementById("cities_id");
    if (citySelect && citySelect.tagName === "SELECT") {
      citySelect.addEventListener("change", function () {
        // @ts-ignore
        var cityId = this.value;
        if (cityId) {
          loadNeighborhoods(cityId);
        } else {
          clearNeighborhoods();
        }
      });
    }

    // Gestion du changement de quartier
    var neighborhoodSelect = document.getElementById("neighborhoods_id");
    if (neighborhoodSelect && neighborhoodSelect.tagName === "SELECT") {
      neighborhoodSelect.addEventListener("change", function () {
        // @ts-ignore
        var neighborhoodId = this.value;
        if (neighborhoodId) {
          // Récupérer les frais de livraison via AJAX
          updateDeliveryFeeFromNeighborhood(neighborhoodId);
        }
      });
    }

    // Gestion de la soumission du formulaire
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      saveDeliveryChoice();
    });
  }

  // Charger les quartiers pour une ville
  function loadNeighborhoods(cityId) {
    var neighborhoodSelect = document.getElementById("neighborhoods_id");
    if (!neighborhoodSelect || !neighborhoodSelect.innerHTML) return;

    // Afficher un indicateur de chargement
    neighborhoodSelect.innerHTML = '<option value="">Chargement...</option>';

    fetch("index.php?action=neighborhoods&city_id=" + cityId, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data && data.success && data.neighborhoods && neighborhoodSelect) {
          // Vider et remplir les quartiers
          neighborhoodSelect.innerHTML =
            '<option value="">Sélectionnez un quartier</option>';
          data.neighborhoods.forEach(function (neighborhood) {
            var option = document.createElement("option");
            option.value = neighborhood.id;
            option.textContent = neighborhood.name;
            // @ts-ignore
            neighborhoodSelect.appendChild(option);
          });

          // Pré-sélectionner le quartier de l'utilisateur s'il en a un
          var citySelectEl = document.getElementById("cities_id");
          var userNeighborhoodId = citySelectEl
            ? citySelectEl.getAttribute("data-user-neighborhood-id")
            : null;
          if (
            userNeighborhoodId &&
            neighborhoodSelect &&
            neighborhoodSelect.tagName === "SELECT"
          ) {
            // @ts-ignore
            neighborhoodSelect.value = userNeighborhoodId;
            // Déclencher le changement pour mettre à jour les frais
            var event = new Event("change");
            if (neighborhoodSelect) {
              neighborhoodSelect.dispatchEvent(event);
            }
          }
        } else if (neighborhoodSelect) {
          neighborhoodSelect.innerHTML =
            '<option value="">Aucun quartier disponible</option>';
        }
      })
      .catch(function (error) {
        console.error("Erreur lors du chargement des quartiers:", error);
        if (neighborhoodSelect) {
          neighborhoodSelect.innerHTML =
            '<option value="">Erreur de chargement</option>';
        }
      });
  }

  // Mettre à jour les frais de livraison depuis le quartier
  function updateDeliveryFeeFromNeighborhood(neighborhoodId) {
    // Récupérer les frais via AJAX
    fetch("index.php?action=neighborhoods&neighborhood_id=" + neighborhoodId, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data && data.success && data.delivery_fee !== undefined) {
          updateDeliveryFee(parseFloat(data.delivery_fee));
        }
      })
      .catch(function (error) {
        console.error("Erreur lors de la récupération des frais:", error);
      });
  }

  // Vider les quartiers
  function clearNeighborhoods() {
    var neighborhoodSelect = document.getElementById("neighborhoods_id");
    if (neighborhoodSelect) {
      neighborhoodSelect.innerHTML =
        '<option value="">Sélectionnez un quartier</option>';
    }
    // Réinitialiser les frais
    var deliveryFeeEl = document.getElementById("delivery-fee");
    if (deliveryFeeEl) {
      deliveryFeeEl.textContent = "À définir";
    }
  }

  // Mettre à jour l'affichage des frais de livraison
  function updateDeliveryFee(fee) {
    var deliveryFeeEl = document.getElementById("delivery-fee");
    if (deliveryFeeEl) {
      deliveryFeeEl.textContent = Math.round(fee) + " FCFA";
    }
    // Recalcul instantané du total = sous-total + frais
    var st = document.getElementById("sous-total");
    var tt = document.getElementById("total-commande");
    if (st && tt) {
      var stVal = 0;
      try {
        stVal =
          parseInt((st.textContent || "0").replace(/[^0-9]/g, ""), 10) || 0;
      } catch (e) {
        stVal = 0;
      }
      var newTotal = stVal + Math.round(Number(fee || 0));
      tt.textContent = newTotal + " FCFA";
    }
  }

  // Sauvegarder le choix de livraison
  function saveDeliveryChoice() {
    var form = document.getElementById("delivery-choice-form");
    if (!form || form.tagName !== "FORM") return;

    // Valider les champs avant d'envoyer (affiche les erreurs sous les inputs)
    if (!validateDeliveryForm()) {
      if (window["AuraFineUtils"] && window["AuraFineUtils"].showNotification) {
        window["AuraFineUtils"].showNotification(
          "Veuillez corriger les champs en erreur",
          "error"
        );
      }
      return;
    }

    // @ts-ignore
    var formData = new FormData(form);
    // Le token CSRF est déjà dans le formulaire, pas besoin de l'ajouter

    fetch("index.php?action=panier-set-delivery", {
      method: "POST",
      body: formData,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.success) {
          // Marquer l'adresse comme enregistrée (mode invité)
          window["guestDeliverySaved"] = true;
          var orderButton = document.querySelector(".btn-commander");
          if (orderButton) {
            orderButton.disabled = false;
          }
          // Mettre à jour l'affichage des frais
          var deliveryFeeEl = document.getElementById("delivery-fee");
          var totalEl = document.getElementById("total-commande");

          if (deliveryFeeEl) {
            deliveryFeeEl.textContent = Math.round(data.deliveryFee) + " FCFA";
          }
          if (totalEl) {
            totalEl.textContent = Math.round(data.grandTotal) + " FCFA";
          }

          // Afficher notification de succès
          if (
            window["AuraFineUtils"] &&
            window["AuraFineUtils"].showNotification
          ) {
            window["AuraFineUtils"].showNotification(
              "Adresse de livraison enregistrée !"
            );
          }

          // Masquer le formulaire et afficher les infos
          window["toggleDeliveryForm"]();
        } else {
          console.error("Erreur:", data.error);
          if (
            window["AuraFineUtils"] &&
            window["AuraFineUtils"].showNotification
          ) {
            window["AuraFineUtils"].showNotification(
              "Erreur lors de l'enregistrement",
              "error"
            );
          }
        }
      })
      .catch(function (error) {
        console.error("Erreur:", error);
        if (
          window["AuraFineUtils"] &&
          window["AuraFineUtils"].showNotification
        ) {
          window["AuraFineUtils"].showNotification(
            "Erreur de connexion",
            "error"
          );
        }
      });
  }

  // Gestion de la commande
  function initOrderButton() {
    var orderButton = document.querySelector(".btn-commander");
    if (!orderButton) return;

    // Si mode invité (présence des champs) et non encore enregistré, désactiver
    var isGuestMode = !!document.getElementById("first_name");
    if (isGuestMode && !window["guestDeliverySaved"]) {
      orderButton.disabled = true;
    }

    orderButton.addEventListener("click", function (e) {
      e.preventDefault();
      createOrder();
    });
  }

  function createOrder() {
    // Valider les champs obligatoires avant l'envoi
    if (!validateDeliveryForm()) {
      return;
    }

    // Générer une idempotency key simple (client) pour anti double-soumission
    var idem = Date.now().toString(36) + Math.random().toString(36).slice(2);
    var idemInput = document.getElementById("idempotency_key");
    if (idemInput) idemInput.value = idem;

    // Collecter les données du formulaire de livraison
    var formData = new FormData();
    formData.append("csrf_token", csrf);

    // Récupérer les frais de livraison
    var deliveryFeeEl = document.getElementById("delivery-fee");
    var deliveryFee = 0;
    if (deliveryFeeEl) {
      var feeText = deliveryFeeEl.textContent.replace(/[^\d]/g, "");
      deliveryFee = parseInt(feeText) || 0;
    }
    formData.append("delivery_fee", deliveryFee.toString());

    // Combiner les numéros de téléphone avant envoi
    combinePhoneNumber();

    // Récupérer les informations de livraison
    var addressLine = document.getElementById("address_line");
    if (addressLine) formData.append("address_line", addressLine.value);

    var deliveryComment = document.getElementById("delivery_comment");
    if (deliveryComment)
      formData.append("delivery_comment", deliveryComment.value);

    var citiesId = document.getElementById("cities_id");
    if (citiesId) formData.append("cities_id", citiesId.value);

    var neighborhoodsId = document.getElementById("neighborhoods_id");
    if (neighborhoodsId)
      formData.append("neighborhoods_id", neighborhoodsId.value);

    // Champs invités (si présents)
    var firstName = document.getElementById("first_name");
    if (firstName) formData.append("first_name", firstName.value);
    var lastName = document.getElementById("last_name");
    if (lastName) formData.append("last_name", lastName.value);
    var phone = document.getElementById("phone");
    var phoneFull = document.querySelector('input[name="phone"]');
    if (phoneFull) {
      formData.append("phone", phoneFull.value);
    } else if (phone) {
      formData.append("phone", phone.value);
    }
    var email = document.getElementById("email");
    if (email && email.value) formData.append("email", email.value);

    // Points de fidélité utilisés
    var loyaltyUsedEl = document.getElementById("loyalty_points_used");
    var loyaltyUsedVal = loyaltyUsedEl
      ? parseInt(loyaltyUsedEl.value || "0", 10) || 0
      : 0;
    formData.append("loyalty_points_used", String(loyaltyUsedVal));
    formData.append("idempotency_key", idem);

    // Désactiver le bouton pendant la requête
    var orderButton = document.querySelector(".btn-commander");
    if (orderButton) {
      orderButton.disabled = true;
      orderButton.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    }

    // Envoyer la requête
    fetch("index.php?action=create-order", {
      method: "POST",
      body: formData,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.success) {
          // Afficher le message de succès avec le numéro de commande
          if (
            window["AuraFineUtils"] &&
            window["AuraFineUtils"].showNotification
          ) {
            var orderNumber = data.order_number || "#" + data.order_id;
            window["AuraFineUtils"].showNotification(
              "Commande validée ! Votre numéro de commande est : " +
                orderNumber +
                ". Gardez-le pour la livraison.",
              "success"
            );
          }

          // Vider le panier visuellement
          var articlesContainer = document.getElementById(
            "panier-articles-container"
          );
          if (articlesContainer) {
            articlesContainer.innerHTML = `
            <div id="panier-vide" class="panier-vide">
              <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <h3>Votre panier est vide</h3>
                <p>Ajoutez des articles pour commencer vos achats</p>
                <a href="index.php?action=home" class="btn-continuer">Continuer mes achats</a>
              </div>
            </div>
          `;
          }

          // Mettre à jour les totaux
          var sousTotalEl = document.getElementById("sous-total");
          if (sousTotalEl) sousTotalEl.textContent = "0 FCFA";

          var totalCommandeEl = document.getElementById("total-commande");
          if (totalCommandeEl) totalCommandeEl.textContent = "0 FCFA";

          // Mettre à jour le badge du panier
          updateBadge(0);

          // Nettoyer les champs du formulaire de livraison
          clearDeliveryForm();

          // Mettre à jour l'affichage du solde de fidélité
          if (data.new_loyalty_balance !== undefined) {
            updateLoyaltyDisplay(data.new_loyalty_balance);
          }

          // Masquer le bouton commander
          if (orderButton) {
            orderButton.style.display = "none";
          }
        } else {
          // Afficher l'erreur - utiliser le message du serveur ou le code d'erreur
          var errorMessage =
            data.message ||
            getErrorMessage(data.error_code) ||
            "Erreur lors de la création de la commande";

          if (
            window["AuraFineUtils"] &&
            window["AuraFineUtils"].showNotification
          ) {
            window["AuraFineUtils"].showNotification(errorMessage, "error");
          }
        }
      })
      .catch(function (error) {
        console.error("Erreur:", error);
        if (
          window["AuraFineUtils"] &&
          window["AuraFineUtils"].showNotification
        ) {
          window["AuraFineUtils"].showNotification(
            "Erreur de connexion",
            "error"
          );
        }
      })
      .finally(function () {
        // Réactiver le bouton
        if (orderButton) {
          orderButton.disabled = false;
          orderButton.innerHTML =
            '<i class="fas fa-credit-card"></i> Commander maintenant';
        }
      });
  }

  // Validation en temps réel des champs de livraison
  function initDeliveryValidation() {
    var citySelect = document.getElementById("cities_id");
    var neighborhoodSelect = document.getElementById("neighborhoods_id");

    if (citySelect) {
      citySelect.addEventListener("change", function () {
        clearErrorMessages();
      });
    }

    if (neighborhoodSelect) {
      neighborhoodSelect.addEventListener("change", function () {
        clearErrorMessages();
      });
    }
  }

  // Fonction pour mettre à jour l'affichage du solde de fidélité
  function updateLoyaltyDisplay(newAmount) {
    var loyaltyElement = document.getElementById("loyalty-points");
    if (loyaltyElement) {
      var numberElement = loyaltyElement.querySelector(".loyalty-number");
      if (numberElement) {
        numberElement.textContent = newAmount.toLocaleString() + " FCFA";
      }
      loyaltyElement.setAttribute("data-amount", newAmount);
    }
  }

  // Init
  bindAddToCart();
  initCartPage();
  initBadge();
  initDeliveryForm();
  initOrderButton();
  initDeliveryValidation();

  // Pré-check points fidélité côté serveur et recalcul UX
  (function initLoyaltyUI() {
    var input = document.getElementById("loyalty-points-input");
    var usedHidden = document.getElementById("loyalty_points_used");
    if (!input || !usedHidden) return;

    function applyMax(maxUsable) {
      input.max = String(maxUsable);
      // Borne la valeur actuelle
      var v = parseInt(input.value || "0", 10) || 0;
      if (v > maxUsable) {
        input.value = String(maxUsable);
        v = maxUsable;
      }
      usedHidden.value = String(v);
      updateDiscountDisplay(v);
    }

    function updateDiscountDisplay(v) {
      var stEl = document.getElementById("sous-total");
      var feeEl = document.getElementById("delivery-fee");
      var totalEl = document.getElementById("total-commande");
      var discountLine = document.querySelector(".loyalty-discount");
      if (!stEl || !feeEl || !totalEl || !discountLine) return;
      var st =
        parseInt((stEl.textContent || "0").replace(/[^0-9]/g, ""), 10) || 0;
      var fee =
        parseInt((feeEl.textContent || "0").replace(/[^0-9]/g, ""), 10) || 0;
      var maxAllowed = Math.min(v, st);
      var newTotal = Math.max(0, st + fee - maxAllowed);
      var discEl = document.getElementById("loyalty-discount");
      if (discEl)
        discEl.textContent = "-" + maxAllowed.toLocaleString() + " FCFA";
      discountLine.style.display = maxAllowed > 0 ? "flex" : "none";
      totalEl.textContent = newTotal.toLocaleString() + " FCFA";
    }

    function precheck() {
      fetch("index.php?action=precheck-loyalty", {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (!data || !data.success) return;
          applyMax(parseInt(data.maxUsable || 0, 10) || 0);
        })
        .catch(function () {});
    }

    input.addEventListener("input", function () {
      var v = parseInt(input.value || "0", 10) || 0;
      // Borne localement au max courant
      var max = parseInt(input.max || "0", 10) || 0;
      if (v > max) {
        v = max;
        input.value = String(v);
      }
      usedHidden.value = String(v);
      updateDiscountDisplay(v);
    });

    // Pré-check au chargement
    precheck();
  })();
})();
