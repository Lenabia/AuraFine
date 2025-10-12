// orders.js: gestion AJAX des filtres et tri pour la page admin-orders
(function () {
  "use strict";

  // Filtres AJAX et tri des colonnes
  document.addEventListener("DOMContentLoaded", function () {
    const statusTabs = document.querySelectorAll(".status-tab");
    const dateFromInput = document.getElementById("date_from");
    const dateToInput = document.getElementById("date_to");
    const sortableHeaders = document.querySelectorAll(".sortable");

    // Fonction pour charger les commandes via AJAX
    function loadOrders(filters = {}) {
      const params = new URLSearchParams();

      if (filters.status) params.set("status", filters.status);
      if (filters.date_from) params.set("date_from", filters.date_from);
      if (filters.date_to) params.set("date_to", filters.date_to);
      if (filters.sort) params.set("sort", filters.sort);
      if (filters.direction) params.set("direction", filters.direction);

      // Ajouter un indicateur de chargement
      const currentTable = document.querySelector(".orders-table-container");
      if (currentTable) {
        currentTable.style.opacity = "0.5";
        currentTable.style.transition = "opacity 0.8s ease-in-out";
      }

      fetch("index.php?action=admin-orders&" + params.toString(), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then((response) => response.text())
        .then((html) => {
          // Extraire seulement le contenu de la table
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, "text/html");
          const newTable = doc.querySelector(".orders-table-container");

          if (newTable && currentTable) {
            // Mise à jour avec transition
            currentTable.innerHTML = newTable.innerHTML;
            currentTable.style.opacity = "1";
          }

          // Mettre à jour le compteur
          const newCount = doc.querySelector(".orders-header p");
          const currentCount = document.querySelector(".orders-header p");
          if (newCount && currentCount) {
            currentCount.textContent = newCount.textContent;
          }
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des commandes:", error);
          // Restaurer l'opacité en cas d'erreur
          if (currentTable) {
            currentTable.style.opacity = "1";
          }
        });
    }

    // Filtres en temps réel - Onglets de statut
    statusTabs.forEach((tab) => {
      tab.addEventListener("click", function () {
        // Retirer la classe active de tous les onglets
        statusTabs.forEach((t) => t.classList.remove("active"));
        // Ajouter la classe active à l'onglet cliqué
        this.classList.add("active");

        // Charger les commandes avec le nouveau statut
        loadOrders({
          status: this.dataset.status,
          date_from: dateFromInput?.value || "",
          date_to: dateToInput?.value || "",
        });
      });
    });

    if (dateFromInput) {
      dateFromInput.addEventListener("change", function () {
        // Récupérer le statut actuellement sélectionné
        const activeTab = document.querySelector(".status-tab.active");
        loadOrders({
          status: activeTab?.dataset.status || "",
          date_from: this.value,
          date_to: dateToInput?.value || "",
        });
      });
    }

    if (dateToInput) {
      dateToInput.addEventListener("change", function () {
        // Récupérer le statut actuellement sélectionné
        const activeTab = document.querySelector(".status-tab.active");
        loadOrders({
          status: activeTab?.dataset.status || "",
          date_from: dateFromInput?.value || "",
          date_to: this.value,
        });
      });
    }

    // Tri des colonnes
    sortableHeaders.forEach((header) => {
      header.addEventListener("click", function () {
        const sortField = this.dataset.sort;
        const currentSort =
          this.getAttribute("data-current-sort") || "order_date";
        const currentDirection =
          this.getAttribute("data-current-direction") || "DESC";

        let newDirection = "ASC";
        if (sortField === currentSort && currentDirection === "ASC") {
          newDirection = "DESC";
        }

        // Récupérer le statut actuellement sélectionné
        const activeTab = document.querySelector(".status-tab.active");
        loadOrders({
          status: activeTab?.dataset.status || "",
          date_from: dateFromInput?.value || "",
          date_to: dateToInput?.value || "",
          sort: sortField,
          direction: newDirection,
        });
      });
    });
  });
})();
