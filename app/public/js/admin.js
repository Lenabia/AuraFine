/**
 * ========================================
 * JAVASCRIPT ADMIN - AURAFINE
 * ========================================
 * Gestion de l'interface d'administration
 * ======================================== */

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // GESTION DU MENU MOBILE
  // ========================================

  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("admin-sidebar");
  const overlay = document.getElementById("admin-overlay");
  const sidebarClose = document.getElementById("sidebar-close");

  if (menuToggle && sidebar && overlay) {
    // Ouvrir/fermer le menu
    menuToggle.addEventListener("click", function () {
      sidebar.classList.toggle("open");
      overlay.classList.toggle("open");
    });

    // Fermer le menu avec le bouton de fermeture
    if (sidebarClose) {
      sidebarClose.addEventListener("click", function () {
        sidebar.classList.remove("open");
        overlay.classList.remove("open");
      });
    }

    // Fermer le menu en cliquant sur l'overlay
    overlay.addEventListener("click", function () {
      sidebar.classList.remove("open");
      overlay.classList.remove("open");
    });

    // Fermer le menu en cliquant sur un lien
    const navItems = document.querySelectorAll(".nav-item");
    navItems.forEach((item) => {
      item.addEventListener("click", function () {
        sidebar.classList.remove("open");
        overlay.classList.remove("open");
      });
    });
  }

  // ========================================
  // GESTION DE LA DÉCONNEXION
  // ========================================
  // Note: La déconnexion est gérée par modal.js avec showLogoutModal()
  // L'event listener est géré par l'attribut onclick dans le HTML

  // ========================================
  // GESTION DES MÉTRIQUES (ANIMATIONS)
  // ========================================

  const metricCards = document.querySelectorAll(".metric-card");
  metricCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-2px)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
    });
  });

  // ========================================
  // GESTION DE L'ACTIVITÉ RÉCENTE
  // ========================================

  const activityItems = document.querySelectorAll(".activity-item");
  activityItems.forEach((item) => {
    item.addEventListener("mouseenter", function () {
      this.style.backgroundColor = "rgba(0, 0, 0, 0.05)";
    });

    item.addEventListener("mouseleave", function () {
      this.style.backgroundColor = "transparent";
    });
  });

  // ========================================
  // GESTION DES BREAKPOINTS RESPONSIVE
  // ========================================

  function handleResize() {
    if (window.innerWidth >= 1024) {
      // Desktop : fermer le menu mobile
      sidebar.classList.remove("open");
      overlay.classList.remove("open");
    }
  }

  window.addEventListener("resize", handleResize);

  // ========================================
  // GESTION DES TOOLTIPS (FUTUR)
  // ========================================

  // Préparation pour les tooltips sur les métriques
  const metricValues = document.querySelectorAll(".metric-value");
  metricValues.forEach((value) => {
    value.setAttribute("title", "Cliquez pour voir les détails");
    value.style.cursor = "pointer";
  });

  // ========================================
  // GESTION DES NOTIFICATIONS (FUTUR)
  // ========================================

  // Préparation pour les notifications en temps réel
  function showNotification(message, type = "info") {
    // TODO: Implémenter le système de notifications
    console.log(`[${type.toUpperCase()}] ${message}`);
  }

  // ========================================
  // GESTION DES RACCOURCIS CLAVIER
  // ========================================

  document.addEventListener("keydown", function (e) {
    // Échap : fermer le menu mobile
    if (e.key === "Escape") {
      sidebar.classList.remove("open");
      overlay.classList.remove("open");
    }

    // Ctrl + M : ouvrir/fermer le menu
    if (e.ctrlKey && e.key === "m") {
      e.preventDefault();
      menuToggle.click();
    }
  });

  // ========================================
  // INITIALISATION
  // ========================================

  console.log("Admin interface initialized successfully");
});
