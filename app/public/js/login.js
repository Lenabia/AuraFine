/**
 * Login.js - Validation basique côté client pour l'interface de connexion
 *
 * @author Aura Fine Development Team
 * @version 2.0.0
 * @note La logique métier et la sécurité sont gérées côté serveur
 * @accessibility Support clavier et lecteurs d'écran
 * @ux Validation en temps réel pour améliorer l'expérience utilisateur
 */

// Configuration de validation côté client (basique)
const CLIENT_CONFIG = {
  PASSWORD_MIN_LENGTH: 8,
  DEBOUNCE_DELAY: 300,
};

/**
 * Module de gestion des erreurs et notifications côté client
 * Gère l'affichage des messages d'erreur et de succès
 */
const ErrorHandler = {
  /**
   * Affiche un message d'erreur ou de succès
   * @param {string} message - Le message à afficher
   * @param {string} type - Le type de message ('error', 'success', 'warning')
   */
  showMessage: (message, type = "error") => {
    // Supprimer les anciens messages
    ErrorHandler.clearMessages();

    // Créer le conteneur de message
    const messageDiv = document.createElement("div");
    messageDiv.className = `auth-message auth-message-${type}`;
    messageDiv.setAttribute("role", "alert");
    messageDiv.setAttribute("aria-live", "polite");

    // Contenu du message avec icône
    const icon =
      type === "error"
        ? "fas fa-exclamation-circle"
        : type === "success"
        ? "fas fa-check-circle"
        : "fas fa-info-circle";

    messageDiv.innerHTML = `
            <i class="${icon}" aria-hidden="true"></i>
            <span>${message}</span>
        `;

    // Insérer le message dans le formulaire
    const form = document.querySelector(".auth-form");
    if (form) {
      form.insertBefore(messageDiv, form.firstChild);

      // Auto-suppression après 5 secondes pour les succès
      if (type === "success") {
        setTimeout(() => {
          if (messageDiv.parentNode) {
            messageDiv.remove();
          }
        }, 5000);
      }
    }
  },

  /**
   * Supprime tous les messages affichés
   */
  clearMessages: () => {
    const messages = document.querySelectorAll(".auth-message");
    messages.forEach((message) => message.remove());
  },
};

/**
 * Module principal de gestion de la connexion côté client
 * Gère uniquement l'interface utilisateur et la validation basique
 */
const LoginManager = {
  /**
   * Initialise le gestionnaire de connexion
   */
  init: () => {
    LoginManager.setupPasswordToggle();
    LoginManager.setupFormValidation();
    LoginManager.setupAccessibility();

    console.log("LoginManager initialisé avec succès");
  },

  /**
   * Configure l'affichage/masquage du mot de passe
   */
  setupPasswordToggle: () => {
    const togglePasswordBtns = document.querySelectorAll(".auth-toggle-pass");

    togglePasswordBtns.forEach((btn) => {
      const passwordInput = btn.parentElement.querySelector(
        'input[type="password"], input[type="text"]'
      );

      if (passwordInput) {
        btn.addEventListener("click", function (e) {
          e.preventDefault();

          const type =
            passwordInput.getAttribute("type") === "password"
              ? "text"
              : "password";
          passwordInput.setAttribute("type", type);

          // Mise à jour de l'icône et de l'état
          const icon = this.querySelector("i");
          const isVisible = type === "text";

          if (icon) {
            icon.className = isVisible ? "fas fa-eye-slash" : "fas fa-eye";
          }

          // Mise à jour des attributs d'accessibilité
          this.setAttribute("aria-pressed", isVisible.toString());
          this.setAttribute(
            "aria-label",
            isVisible ? "Masquer le mot de passe" : "Afficher le mot de passe"
          );
        });
      }
    });
  },

  /**
   * Configure la validation basique du formulaire
   */
  setupFormValidation: () => {
    const form = document.querySelector(".auth-form");
    if (!form) return;

    const identifierInput = form.querySelector("#auth-identifier");
    const passwordInput = form.querySelector("#auth-password");

    if (
      identifierInput &&
      passwordInput &&
      identifierInput instanceof HTMLInputElement &&
      passwordInput instanceof HTMLInputElement
    ) {
      // Validation basique en temps réel
      identifierInput.addEventListener("blur", () => {
        LoginManager.validateIdentifier(identifierInput.value);
      });

      passwordInput.addEventListener("blur", () => {
        LoginManager.validatePassword(passwordInput.value);
      });

      // Validation à la soumission
      form.addEventListener("submit", (e) => {
        if (!LoginManager.validateForm()) {
          e.preventDefault();
          ErrorHandler.showMessage(
            "Veuillez corriger les erreurs avant de soumettre",
            "warning"
          );
        }
      });
    }
  },

  /**
   * Valide l'identifiant (validation basique côté client)
   * @param {string} identifier - L'identifiant à valider
   * @returns {boolean} True si valide
   */
  validateIdentifier: (identifier) => {
    ErrorHandler.clearMessages();

    if (!identifier.trim()) {
      ErrorHandler.showMessage("Veuillez saisir votre email ou téléphone");
      return false;
    }

    // Validation basique - la vraie validation se fait côté serveur
    if (identifier.includes("@")) {
      // Format email basique
      if (identifier.length < 5 || !identifier.includes(".")) {
        ErrorHandler.showMessage("Format d'email invalide");
        return false;
      }
    } else {
      // Format téléphone basique
      if (identifier.length < 10) {
        ErrorHandler.showMessage("Numéro de téléphone trop court");
        return false;
      }
    }

    return true;
  },

  /**
   * Valide le mot de passe (validation basique côté client)
   * @param {string} password - Le mot de passe à valider
   * @returns {boolean} True si valide
   */
  validatePassword: (password) => {
    ErrorHandler.clearMessages();

    if (!password) {
      ErrorHandler.showMessage("Le mot de passe est requis");
      return false;
    }

    // Validation basique - la vraie validation se fait côté serveur
    if (password.length < CLIENT_CONFIG.PASSWORD_MIN_LENGTH) {
      ErrorHandler.showMessage(
        `Le mot de passe doit contenir au moins ${CLIENT_CONFIG.PASSWORD_MIN_LENGTH} caractères`
      );
      return false;
    }

    return true;
  },

  /**
   * Valide l'ensemble du formulaire
   * @returns {boolean} True si le formulaire est valide
   */
  validateForm: () => {
    const identifierInput = document.querySelector("#auth-identifier");
    const passwordInput = document.querySelector("#auth-password");

    if (
      !identifierInput ||
      !passwordInput ||
      !(identifierInput instanceof HTMLInputElement) ||
      !(passwordInput instanceof HTMLInputElement)
    ) {
      return false;
    }

    const identifierValid = LoginManager.validateIdentifier(
      identifierInput.value
    );
    const passwordValid = LoginManager.validatePassword(passwordInput.value);

    return identifierValid && passwordValid;
  },

  /**
   * Configure les fonctionnalités d'accessibilité
   */
  setupAccessibility: () => {
    const togglePasswordBtn = document.querySelector(".auth-toggle-pass");

    if (togglePasswordBtn) {
      // Support clavier complet
      togglePasswordBtn.addEventListener("keydown", function (event) {
        if (
          event instanceof KeyboardEvent &&
          (event.key === "Enter" || event.key === " ")
        ) {
          event.preventDefault();
          this.click();
        }
      });

      // Attributs ARIA pour l'accessibilité
      togglePasswordBtn.setAttribute("role", "button");
      togglePasswordBtn.setAttribute("tabindex", "0");
      togglePasswordBtn.setAttribute("aria-label", "Afficher le mot de passe");
    }

    // Améliorer la navigation au clavier
    const form = document.querySelector(".auth-form");
    if (form) {
      const inputs = form.querySelectorAll("input, button, a");
      inputs.forEach((input, index) => {
        input.setAttribute("tabindex", String(index + 1));
      });
    }
  },
};

// Initialisation sécurisée quand le DOM est prêt
document.addEventListener("DOMContentLoaded", function () {
  try {
    LoginManager.init();
  } catch (error) {
    console.error("Erreur lors de l'initialisation de LoginManager:", error);
    // Fallback basique en cas d'erreur
    const form = document.querySelector(".auth-form");
    if (form) {
      form.addEventListener("submit", (e) => {
        // Validation basique de fallback
        const requiredFields = form.querySelectorAll("[required]");
        let isValid = true;

        requiredFields.forEach((field) => {
          if (field instanceof HTMLInputElement && !field.value.trim()) {
            isValid = false;
            field.classList.add("error");
          } else if (field instanceof HTMLInputElement) {
            field.classList.remove("error");
          }
        });

        if (!isValid) {
          e.preventDefault();
          alert("Veuillez remplir tous les champs obligatoires");
        }
      });
    }
  }
});
