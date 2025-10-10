"use strict";

// Variable globale pour éviter les doublons d'observers
let mainObserver = null;

// ========================================
// BURGER MENU (OPTIMISÉ)
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  const burgerBtn = document.getElementById("burger-btn");
  const burgerMenu = document.getElementById("burger-menu");
  const closeBurger = document.getElementById("close-burger");

  if (burgerBtn && burgerMenu && closeBurger) {
    burgerBtn.addEventListener("click", function () {
      burgerMenu.classList.add("open");
    });
    closeBurger.addEventListener("click", function () {
      burgerMenu.classList.remove("open");
    });
    // Fermer le menu si on clique en dehors
    burgerMenu.addEventListener("click", function (e) {
      if (e.target === burgerMenu) {
        burgerMenu.classList.remove("open");
      }
    });
  }
});

// ========================================
// COPIE DE LIEN (Parrainage)
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".referral-copy-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var targetSel = btn.getAttribute("data-copy-target");
      var input = targetSel ? document.querySelector(targetSel) : null;
      if (!input || !(input instanceof HTMLInputElement)) return;
      input.select();
      input.setSelectionRange(0, 99999);
      try {
        var ok = document.execCommand("copy");
        if (ok) {
          if (
            window["AuraFineUtils"] &&
            window["AuraFineUtils"].showNotification
          ) {
            window["AuraFineUtils"].showNotification("Lien copié !");
          }
        }
      } catch (e) {}
    });
  });
});

// ========================================
// ANIMATIONS AU SCROLL OPTIMISÉES
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  // Un seul observer pour toutes les animations au scroll
  if (!mainObserver) {
    const elements = document.querySelectorAll(".animate-on-scroll");
    mainObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("in-view");
            // Une fois animé, on peut arrêter d'observer pour économiser les ressources
            mainObserver.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.1, // Réduit pour déclencher plus tôt
        rootMargin: "0px 0px -50px 0px", // Déclenche 50px avant l'entrée
      }
    );

    elements.forEach((el) => mainObserver.observe(el));
  }
});

// ========================================
// ANIMATION MOT À MOT POUR LE SLOGAN (CSS PUR)
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  const tagline = document.querySelector(".site-tagline.animate-words");
  if (tagline) {
    let animationTimeout = null;

    const taglineObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            // Ajouter la classe pour déclencher l'animation CSS
            tagline.classList.add("in-view");
            // Arrêter d'observer une fois animé
            taglineObserver.unobserve(tagline);
          } else {
            // Si l'élément sort de l'écran, annuler l'animation
            tagline.classList.remove("in-view");
            if (animationTimeout) {
              clearTimeout(animationTimeout);
              animationTimeout = null;
            }
          }
        });
      },
      { threshold: 0.5 }
    );

    taglineObserver.observe(tagline);
  }
});

// ========================================
// SLIDER HORIZONTAL AUTOMATIQUE (CSS ANIMATION)
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  // Sélectionner le track du slider
  const sliderTrack = document.querySelector(".slider-track");

  if (sliderTrack) {
    // Configuration du slider
    const config = {
      pauseOnHover: true,
    };

    // Gestion du hover (pause/reprise de l'animation CSS)
    function handleSliderHover() {
      if (config.pauseOnHover && sliderTrack) {
        sliderTrack.addEventListener("mouseenter", function () {
          if (this instanceof HTMLElement) {
            this.style.animationPlayState = "paused";
          }
        });

        sliderTrack.addEventListener("mouseleave", function () {
          if (this instanceof HTMLElement) {
            this.style.animationPlayState = "running";
          }
        });
      }
    }

    // Gestion du redimensionnement optimisé (pas de recréation DOM)
    function handleResize() {
      let resizeTimer;
      window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
          // Pas de recréation DOM - l'animation CSS s'adapte automatiquement
          // Juste une vérification que l'animation fonctionne
          if (
            sliderTrack &&
            sliderTrack instanceof HTMLElement &&
            sliderTrack.style.animationPlayState === "paused"
          ) {
            sliderTrack.style.animationPlayState = "running";
          }
        }, 300); // Debounce de 300ms
      });
    }

    // Initialiser le slider (plus simple, pas de recréation DOM)
    setTimeout(() => {
      handleSliderHover();
      handleResize();
    }, 100);
  }
});

// ========================================
// EFFETS HOVER SUR LES ÉLÉMENTS (OPTIMISÉ)
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  // Effet hover/touch sur les cartes du slider - Délégation d'événements
  const sliderContainer = document.querySelector(".slider-track");
  if (sliderContainer) {
    // Support desktop (hover)
    sliderContainer.addEventListener(
      "mouseenter",
      function (e) {
        const target = e.target;
        if (target && target.closest) {
          const sliderItem = target.closest(".slider-item");
          if (sliderItem) {
            sliderItem.classList.add("hover");
          }
        }
      },
      true
    );

    sliderContainer.addEventListener(
      "mouseleave",
      function (e) {
        const target = e.target;
        if (target && target.closest) {
          const sliderItem = target.closest(".slider-item");
          if (sliderItem) {
            sliderItem.classList.remove("hover");
          }
        }
      },
      true
    );

    // Support mobile (touch)
    let activeItem = null;

    sliderContainer.addEventListener(
      "touchstart",
      function (e) {
        const target = e.target;
        if (target && target.closest) {
          const sliderItem = target.closest(".slider-item");
          if (sliderItem) {
            activeItem = sliderItem;
            sliderItem.classList.add("hover");
          }
        }
      },
      { passive: true }
    );

    sliderContainer.addEventListener(
      "touchend",
      function (e) {
        if (activeItem) {
          setTimeout(() => {
            activeItem.classList.remove("hover");
            activeItem = null;
          }, 300); // Garder l'effet un peu plus longtemps sur mobile
        }
      },
      { passive: true }
    );
  }

  // ========================================
  // EFFET HOVER SUR LES IMAGES DES CARTES
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
});

// ========================================
// PRÉCHARGEMENT DES IMAGES CRITIQUES
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  // Précharger les images des sections about/services/menu (plus importantes que le slider)
  // Fallback pour les navigateurs qui ne supportent pas requestIdleCallback
  (
    window.requestIdleCallback ||
    function (cb) {
      setTimeout(cb, 200);
    }
  )(() => {
    const criticalImages = document.querySelectorAll(
      "#about img, #services img, #daily-menu img"
    );
    criticalImages.forEach((img) => {
      if (img instanceof HTMLImageElement && img.src) {
        const preloadImg = new Image();
        preloadImg.src = img.src;
      }
    });
  });
});

// ========================================
// GESTION DES ERREURS D'IMAGES
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  // Gestion des erreurs de chargement d'images
  const images = document.querySelectorAll("img");
  images.forEach((img) => {
    img.addEventListener("error", function () {
      this.src = "/app/public/images/wireframes/salad.webp"; // Image de fallback
      this.alt = "Image non disponible";
    });
  });
});

// ========================================
// ANIMATIONS CSS ADDITIONNELLES
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  // Note: Les animations CSS du panier sont dans panier.css
});

/* ========================================
   MENU PROFIL UTILISATEUR
   ======================================== */

// Gestion du menu déroulant profil
document.addEventListener("DOMContentLoaded", function () {
  // Toggle formulaire de livraison (édition adresse)
  const editBtn = document.getElementById("edit-delivery-btn");
  const formContainer = document.getElementById("delivery-form-container");
  const deliveryForm = document.getElementById("delivery-choice-form");
  const deliveryFlash = document.getElementById("delivery-flash");
  const summaryAddress = document.getElementById("summary-address");
  const summaryZone = document.getElementById("summary-zone");
  if (editBtn && formContainer) {
    editBtn.addEventListener("click", function () {
      const isVisible = formContainer.style.display === "block";
      formContainer.style.display = isVisible ? "none" : "block";
    });
  }
  if (deliveryForm) {
    deliveryForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(deliveryForm);
      fetch("index.php?action=panier-set-delivery", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => res.json())
        .then((json) => {
          if (!json || json.success !== true) {
            throw new Error(
              json && json.message ? json.message : "Erreur d'enregistrement"
            );
          }
          // Mettre à jour résumé
          const addressTextarea = document.getElementById("address_line");
          if (summaryAddress && addressTextarea) {
            summaryAddress.textContent = addressTextarea.value;
          }
          const deliveryFeeEl = document.getElementById("delivery-fee");
          const totalCommandeEl = document.getElementById("total-commande");
          if (deliveryFeeEl && typeof json.deliveryFee !== "undefined") {
            deliveryFeeEl.textContent =
              new Intl.NumberFormat("fr-FR").format(
                Math.round(json.deliveryFee)
              ) + " FCFA";
          }
          if (totalCommandeEl && typeof json.grandTotal !== "undefined") {
            totalCommandeEl.textContent =
              new Intl.NumberFormat("fr-FR").format(
                Math.round(json.grandTotal)
              ) + " FCFA";
          }
          // Feedback visuel + refermer le formulaire
          if (deliveryFlash) {
            deliveryFlash.style.color = "#0a7a0a";
            deliveryFlash.textContent = "Adresse enregistrée";
            deliveryFlash.style.display = "block";
            setTimeout(() => {
              deliveryFlash.style.display = "none";
            }, 2000);
          }
          if (formContainer) {
            formContainer.style.display = "none";
          }
        })
        .catch((err) => {
          if (deliveryFlash) {
            deliveryFlash.style.color = "#b00020";
            deliveryFlash.textContent =
              err && err.message
                ? err.message
                : "Erreur lors de l'enregistrement";
            deliveryFlash.style.display = "block";
            setTimeout(() => {
              deliveryFlash.style.display = "none";
              deliveryFlash.style.color = "#0a7a0a";
            }, 2500);
          }
        });
    });
  }
  const profileDropdown = document.querySelector(".user-profile-dropdown");

  if (profileDropdown) {
    const profileTrigger = profileDropdown.querySelector(".profile-trigger");
    const profileMenu = profileDropdown.querySelector(".profile-menu-content");

    // Gestion du clic sur mobile
    profileTrigger.addEventListener("click", function (e) {
      e.preventDefault();

      // Toggle du menu sur mobile
      if (window.innerWidth < 1024) {
        profileMenu.classList.toggle("show");

        // Fermer le menu si on clique ailleurs
        document.addEventListener("click", function closeMenu(e) {
          if (!profileDropdown.contains(e.target)) {
            profileMenu.classList.remove("show");
            document.removeEventListener("click", closeMenu);
          }
        });
      }
    });

    // Gestion du hover sur desktop
    if (window.innerWidth >= 1024) {
      profileDropdown.addEventListener("mouseenter", function () {
        profileMenu.style.opacity = "1";
        profileMenu.style.visibility = "visible";
        profileMenu.style.transform = "translateX(-50%) translateY(0)";
      });

      profileDropdown.addEventListener("mouseleave", function () {
        profileMenu.style.opacity = "0";
        profileMenu.style.visibility = "hidden";
        profileMenu.style.transform = "translateX(-50%) translateY(-10px)";
      });
    }

    // Gestion du redimensionnement de la fenêtre
    window.addEventListener("resize", function () {
      if (window.innerWidth < 1024) {
        profileMenu.style.opacity = "";
        profileMenu.style.visibility = "";
        profileMenu.style.transform = "";
      }
    });
  }
});

// Gestion des points fidélité (pour usage futur)
document.addEventListener("DOMContentLoaded", function () {
  const loyaltyPoints = document.getElementById("loyalty-points");

  if (loyaltyPoints) {
    const currentAmount = parseInt(loyaltyPoints.dataset.amount || "0") || 0;

    // Animation des points fidélité
    loyaltyPoints.addEventListener("click", function () {
      this.style.transform = "scale(1.1)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 200);
    });

    // Log pour debug (à retirer en production)
    console.log("Solde fidélité (FCFA):", currentAmount);
  }
});

// ========================================
// GESTION DES COMMANDES UTILISATEUR
// ========================================

// Variables globales pour le suivi des commandes
let orderUpdateInterval = null;
let isOrderPage = false;

// Vérifier si on est sur la page des commandes
document.addEventListener("DOMContentLoaded", function () {
  const orderPage = document.querySelector(".user-orders-page");
  if (orderPage) {
    isOrderPage = true;
    initOrderTracking();
  }
});

/**
 * Initialise le suivi des commandes en temps réel
 */
function initOrderTracking() {
  if (!isOrderPage) return;

  const activeOrderCard = document.getElementById("active-order-card");
  if (!activeOrderCard) return;

  // Démarrer le polling pour les mises à jour
  startOrderPolling();

  // Arrêter le polling quand l'utilisateur quitte la page
  window.addEventListener("beforeunload", stopOrderPolling);
}

/**
 * Démarre le polling pour vérifier les mises à jour de commande
 */
function startOrderPolling() {
  // Vérifier toutes les 30 secondes
  orderUpdateInterval = setInterval(checkOrderStatus, 30000);

  // Vérifier immédiatement au chargement
  setTimeout(checkOrderStatus, 2000);
}

/**
 * Arrête le polling
 */
function stopOrderPolling() {
  if (orderUpdateInterval) {
    clearInterval(orderUpdateInterval);
    orderUpdateInterval = null;
  }
}

/**
 * Vérifie le statut de la commande active
 */
async function checkOrderStatus() {
  if (!isOrderPage) return;

  try {
    const response = await fetch("index.php?action=check-order-status", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        csrf_token: document.getElementById("csrf-token")?.value || "",
      }),
    });

    if (!response.ok) {
      throw new Error("Erreur réseau");
    }

    const data = await response.json();

    if (data.success && data.order) {
      updateOrderDisplay(data.order);
    } else if (data.success && !data.order) {
      // Plus de commande active, recharger la page
      location.reload();
    }
  } catch (error) {
    console.error("Erreur lors de la vérification du statut:", error);
  }
}

/**
 * Met à jour l'affichage de la commande
 */
function updateOrderDisplay(order) {
  const activeOrderCard = document.getElementById("active-order-card");
  if (!activeOrderCard) return;

  // Mettre à jour le statut actuel
  const currentStatusElement = activeOrderCard.querySelector(
    ".current-status strong"
  );
  if (currentStatusElement) {
    currentStatusElement.textContent = order.status;
  }

  // Mettre à jour la dernière mise à jour
  const lastUpdateElement = activeOrderCard.querySelector(".last-update");
  if (lastUpdateElement) {
    const updateDate = new Date(order.updated_at);
    lastUpdateElement.textContent = `Dernière mise à jour : ${updateDate.toLocaleDateString(
      "fr-FR"
    )} à ${updateDate.toLocaleTimeString("fr-FR", {
      hour: "2-digit",
      minute: "2-digit",
    })}`;
  }

  // Mettre à jour le workflow visuel
  updateWorkflowSteps(order.status);
}

/**
 * Met à jour les étapes du workflow visuel
 */
function updateWorkflowSteps(currentStatus) {
  const steps = document.querySelectorAll(".workflow-step");
  if (!steps.length) return;

  // Réinitialiser toutes les étapes
  steps.forEach((step) => {
    step.classList.remove("active", "completed");
  });

  // Définir les statuts et leurs positions
  const statusMap = {
    "En attente": 0,
    "En preparation": 1,
    "Livraison en cours": 2,
    Livrée: 3,
  };

  const currentStepIndex = statusMap[currentStatus];
  if (currentStepIndex === undefined) return;

  // Marquer les étapes précédentes comme terminées
  for (let i = 0; i < currentStepIndex; i++) {
    if (steps[i]) {
      steps[i].classList.add("completed");
    }
  }

  // Marquer l'étape actuelle comme active
  if (steps[currentStepIndex]) {
    steps[currentStepIndex].classList.add("active");
  }

  // Si la commande est livrée, marquer toutes les étapes comme terminées
  if (currentStatus === "Livrée") {
    steps.forEach((step) => {
      step.classList.remove("active");
      step.classList.add("completed");
    });
  }
}

/**
 * Affiche les détails d'une commande dans une modal
 */
function showOrderDetails(orderId) {
  if (!orderId) return;

  // Afficher la modal
  const modal = document.getElementById("orderDetailsModal");
  if (modal) {
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  // Charger les détails de la commande
  loadOrderDetails(orderId);
}

/**
 * Charge les détails d'une commande via AJAX
 */
async function loadOrderDetails(orderId) {
  const contentElement = document.getElementById("orderDetailsContent");
  if (!contentElement) return;

  // Afficher un indicateur de chargement
  contentElement.innerHTML =
    '<div class="loading-spinner">Chargement des détails...</div>';

  try {
    const response = await fetch(
      `index.php?action=order-details&id=${orderId}`,
      {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      }
    );

    if (!response.ok) {
      throw new Error("Erreur réseau");
    }

    const data = await response.json();

    if (data.success) {
      contentElement.innerHTML = data.html;
    } else {
      contentElement.innerHTML =
        '<div class="error-message">Erreur lors du chargement des détails</div>';
    }
  } catch (error) {
    console.error("Erreur lors du chargement des détails:", error);
    contentElement.innerHTML =
      '<div class="error-message">Erreur lors du chargement des détails</div>';
  }
}

/**
 * Ferme la modal des détails de commande
 */
function closeOrderDetailsModal() {
  const modal = document.getElementById("orderDetailsModal");
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Fermer la modal en cliquant à l'extérieur
document.addEventListener("click", function (event) {
  const modal = document.getElementById("orderDetailsModal");
  if (modal && event.target === modal) {
    closeOrderDetailsModal();
  }
});

// Fermer la modal avec la touche Échap
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeOrderDetailsModal();
  }
});

console.log("Script principal initialisé avec succès !");
