// ========================================
// SLIDER VANILLA JAVASCRIPT (OPTIMISÉ)
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // SLIDER HORIZONTAL AUTOMATIQUE (VANILLA)
  // ========================================

  // Sélectionner le track du slider
  const sliderTrack = document.querySelector(".slider-track");

  if (sliderTrack) {
    // Configuration du slider
    const config = {
      speed: 30, // pixels par seconde
      pauseOnHover: true,
      autoPlay: true,
    };

    // Variables d'état
    let isPlaying = true;
    let animationId;
    let startTime;
    let currentPosition = 0;

    // Fonction pour créer l'effet de défilement infini
    function createInfiniteSlider() {
      // Vérifier que les éléments existent
      const items = sliderTrack.querySelectorAll(".slider-item");
      if (items.length === 0) return;

      // Cloner les éléments pour l'effet infini
      const clonedItems = Array.from(items).map((item) => item.cloneNode(true));

      // Ajouter les éléments clonés à la fin
      clonedItems.forEach((item) => {
        sliderTrack.appendChild(item);
      });

      // Démarrer l'animation après un délai pour s'assurer que les éléments sont rendus
      setTimeout(() => {
        startSliderAnimation();
      }, 100);
    }

    // Fonction pour démarrer l'animation du slider (vanilla)
    function startSliderAnimation() {
      if (!isPlaying) return;

      const items = sliderTrack.querySelectorAll(".slider-item");
      if (items.length === 0) return;

      const firstItem = items[0];
      if (!firstItem) return;

      const itemWidth = firstItem.offsetWidth || 280;
      const gap = 32; // 2rem en pixels
      const totalWidth = (itemWidth + gap) * (items.length / 2);

      // Fonction d'animation vanilla
      function animate(currentTime) {
        if (!startTime) startTime = currentTime;

        const elapsed = currentTime - startTime;
        const progress = (elapsed / 1000) * config.speed; // Convertir en secondes

        // Calculer la nouvelle position
        currentPosition = -progress % totalWidth;

        // Appliquer la transformation
        sliderTrack.style.transform = `translateX(${currentPosition}px)`;

        // Continuer l'animation
        if (isPlaying) {
          animationId = requestAnimationFrame(animate);
        }
      }

      // Démarrer l'animation
      animationId = requestAnimationFrame(animate);
    }

    // Fonction pour arrêter l'animation
    function stopSliderAnimation() {
      isPlaying = false;
      if (animationId) {
        cancelAnimationFrame(animationId);
      }
    }

    // Fonction pour reprendre l'animation
    function resumeSliderAnimation() {
      isPlaying = true;
      startTime = null; // Reset du temps pour une transition fluide
      startSliderAnimation();
    }

    // Gestion du hover
    function handleSliderHover() {
      if (config.pauseOnHover) {
        sliderTrack.addEventListener("mouseenter", stopSliderAnimation);
        sliderTrack.addEventListener("mouseleave", resumeSliderAnimation);
      }
    }

    // Gestion du redimensionnement
    function handleResize() {
      let resizeTimer;
      window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
          // Arrêter l'animation actuelle
          stopSliderAnimation();

          // Recréer le slider
          sliderTrack.innerHTML = "";
          createInfiniteSlider();
        }, 250);
      });
    }

    // Initialiser le slider
    setTimeout(() => {
      createInfiniteSlider();
      handleSliderHover();
      handleResize();
    }, 200);
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
  // EFFETS HOVER SUR LES ÉLÉMENTS
  // ========================================

  // Effet hover sur les cartes du slider
  const sliderItems = document.querySelectorAll(".slider-item");
  sliderItems.forEach((item) => {
    item.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-10px) scale(1.02) translateZ(0)";
    });

    item.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0) scale(1) translateZ(0)";
    });
  });

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
            cartCount.style.animation = "pulse 0.5s ease-in-out";
            setTimeout(() => {
              if (cartCount) {
                cartCount.style.animation = "";
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
  // PERFORMANCE ET OPTIMISATIONS
  // ========================================

  // Utiliser requestIdleCallback pour les tâches non critiques
  if ("requestIdleCallback" in window) {
    requestIdleCallback(() => {
      // Précharger les images du slider
      const sliderImages = document.querySelectorAll(".slider-item img");
      sliderImages.forEach((img) => {
        if (img.src) {
          const preloadImg = new Image();
          preloadImg.src = img.src;
        }
      });
    });
  }

  console.log("Slider vanilla JavaScript initialisé avec succès !");
});
