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
