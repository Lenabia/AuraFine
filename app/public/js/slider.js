// ========================================
// SLIDER VANILLA JAVASCRIPT (OPTIMISÉ - CSS FIRST)
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // SLIDER HORIZONTAL AUTOMATIQUE (CSS ANIMATION)
  // ========================================

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

  // ========================================
  // ANIMATIONS AU SCROLL OPTIMISÉES
  // ========================================

  // Observer pour les animations au scroll avec throttling
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  let animationFrameId;
  const scrollObserver = new IntersectionObserver((entries) => {
    // Utiliser requestAnimationFrame pour optimiser les performances
    if (animationFrameId) {
      cancelAnimationFrame(animationFrameId);
    }

    animationFrameId = requestAnimationFrame(() => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
        }
      });
    });
  }, observerOptions);

  // Observer tous les éléments avec la classe animate-on-scroll
  const animateElements = document.querySelectorAll(".animate-on-scroll");
  animateElements.forEach((el) => {
    scrollObserver.observe(el);
  });

  // ========================================
  // ANIMATION MOT À MOT POUR LE SLOGAN
  // ========================================

  const tagline = document.querySelector(".site-tagline.animate-words");
  if (tagline) {
    const words = tagline.querySelectorAll("span");

    const taglineObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            // Animer chaque mot avec un délai progressif
            words.forEach((word, index) => {
              setTimeout(() => {
                word.classList.add("in-view");
              }, index * 150); // Réduit à 150ms pour plus de fluidité
            });
          }
        });
      },
      { threshold: 0.5 }
    );

    taglineObserver.observe(tagline);
  }

  // ========================================
  // EFFETS HOVER SUR LES ÉLÉMENTS (OPTIMISÉ)
  // ========================================

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
  // GESTION DU PANIER (BASIQUE)
  // ========================================

  const cartIcon = document.querySelector(".cart-icon");
  const cartCount = document.querySelector(".cart-count");

  if (cartIcon && cartCount) {
    // Récupérer le nombre d'articles dans le panier depuis localStorage
    function updateCartCount() {
      try {
        const cartData = localStorage.getItem("aurafineCart");
        const cart = cartData ? JSON.parse(cartData) : [];
        const totalItems = cart.reduce(
          (total, item) => total + (item.quantity || 0),
          0
        );

        if (cartCount) {
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
        }
      } catch (error) {
        console.error("Erreur lors de la lecture du panier:", error);
      }
    }

    // Mettre à jour le compteur au chargement
    updateCartCount();

    // Écouter les changements du localStorage (pour les autres pages)
    window.addEventListener("storage", function (e) {
      if (e.key === "aurafineCart") {
        updateCartCount();
      }
    });
  }

  // ========================================
  // ANIMATIONS CSS ADDITIONNELLES
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
  // PRÉCHARGEMENT DES IMAGES CRITIQUES
  // ========================================

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
