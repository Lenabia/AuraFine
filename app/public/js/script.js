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
    const currentPoints = parseInt(loyaltyPoints.dataset.points) || 0;

    // Animation des points fidélité
    loyaltyPoints.addEventListener("click", function () {
      this.style.transform = "scale(1.1)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 200);
    });

    // Log pour debug (à retirer en production)
    console.log("Points fidélité actuels:", currentPoints);
  }
});

console.log("Script principal initialisé avec succès !");
