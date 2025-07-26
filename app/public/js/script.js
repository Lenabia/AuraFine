"use script";

console.log("burger");
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

// Animation au scroll pour les blocs .animate-on-scroll
// Utilise Intersection Observer pour ajouter la classe .in-view quand le bloc entre dans la fenêtre
// et la retire quand il sort, pour permettre l'animation dans les deux sens (haut/bas)

document.addEventListener("DOMContentLoaded", function () {
  const elements = document.querySelectorAll(".animate-on-scroll");
  const observer = new window.IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
        } else {
          entry.target.classList.remove("in-view");
        }
      });
    },
    { threshold: 0.25 }
  ); // 0.2 = 20% du bloc visible

  elements.forEach((el) => observer.observe(el));
});

// Animation mot à mot pour le slogan (site-tagline.animate-words)
document.addEventListener("DOMContentLoaded", function () {
  const tagline = document.querySelector(".site-tagline.animate-words");
  if (tagline) {
    const observer = new window.IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            tagline.classList.add("in-view");
          } else {
            tagline.classList.remove("in-view");
          }
        });
      },
      { threshold: 0.2 }
    ); // 0.3 = 30% du bloc visible
    observer.observe(tagline);
  }
});
