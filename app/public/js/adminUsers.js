// Gestion AJAX de la recherche, du tri et de la pagination pour Admin > Utilisateurs
(function () {
  var form = document.querySelector(".filters-form");
  var wrapper = document.getElementById("users-table-wrapper");
  if (!wrapper) return; // Ne rien faire si on n'est pas sur la page

  function buildUrl(params) {
    var usp = new URLSearchParams(params);
    usp.set("action", "admin-users");
    usp.set("partial", "table");
    return "index.php?" + usp.toString();
  }

  function currentParams() {
    var p = new URLSearchParams(window.location.search);
    return {
      search:
        form && form.querySelector('input[name="search"]')
          ? form.querySelector('input[name="search"]').value
          : p.get("search") || "",
      sort: p.get("sort") || "orders_count",
      direction: p.get("direction") || "DESC",
      page: p.get("page") || "1",
    };
  }

  function loadTable(params, push) {
    var url = buildUrl(params);
    fetch(url, { headers: { "X-Requested-With": "fetch" } })
      .then(function (r) {
        return r.text();
      })
      .then(function (html) {
        wrapper.innerHTML = html;
        if (push)
          history.pushState(
            null,
            "",
            "index.php?action=admin-users&search=" +
              encodeURIComponent(params.search) +
              "&sort=" +
              encodeURIComponent(params.sort) +
              "&direction=" +
              encodeURIComponent(params.direction) +
              "&page=" +
              encodeURIComponent(params.page)
          );
        bindLinks();
      });
  }

  function bindLinks() {
    // tri
    wrapper.querySelectorAll("a.sort-link").forEach(function (a) {
      a.addEventListener("click", function (e) {
        e.preventDefault();
        var p = currentParams();
        var url = new URL(a.href, window.location.origin);
        p.sort = url.searchParams.get("sort") || p.sort;
        p.direction = url.searchParams.get("direction") || p.direction;
        p.page = url.searchParams.get("page") || "1";
        loadTable(p, true);
      });
    });
    // pagination
    wrapper.querySelectorAll(".pagination a.btn-back").forEach(function (a) {
      a.addEventListener("click", function (e) {
        e.preventDefault();
        var p = currentParams();
        var url = new URL(a.href, window.location.origin);
        p.page = url.searchParams.get("page") || "1";
        loadTable(p, true);
      });
    });
  }

  if (form) {
    // Bouton "Filtrer" = réinitialiser et afficher tout
    var btn = form.querySelector(".btn-save");
    if (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        var input = form.querySelector('input[name="search"]');
        if (input) input.value = "";
        var p = {
          search: "",
          sort: "orders_count",
          direction: "DESC",
          page: "1",
        };
        loadTable(p, true);
      });
    }

    // debounce recherche
    var t;
    var input = form.querySelector('input[name="search"]');
    if (input) {
      input.addEventListener("input", function () {
        clearTimeout(t);
        t = setTimeout(function () {
          var p = currentParams();
          p.page = "1";
          loadTable(p, true);
        }, 400);
      });
    }
  }

  // init
  bindLinks();
})();
