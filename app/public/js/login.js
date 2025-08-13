/**
 * Login.js - Gestion de l'interface utilisateur de la page de connexion
 * Fonctionnalités : Affichage/masquage du mot de passe
 */

document.addEventListener("DOMContentLoaded", function () {
  // Gestion de l'affichage/masquage du mot de passe
  const togglePasswordBtn = document.querySelector(".auth-toggle-pass");
  const passwordInput = document.querySelector("#auth-password");

  if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener("click", function () {
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);

      // Mise à jour de l'icône et de l'état
      const icon = this.querySelector("i");
      const isVisible = type === "text";

      if (icon) {
        icon.className = isVisible ? "fas fa-eye-slash" : "fas fa-eye";
      }

      this.setAttribute("aria-pressed", isVisible.toString());
    });

    // Gestion du clavier pour l'accessibilité
    togglePasswordBtn.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        this.click();
      }
    });
  }
});
