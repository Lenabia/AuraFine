<?php
namespace app\middleware;

class Middleware {

    public function __construct() {
        $this->enforceSessionActivity();
    }

/**
 * Nettoie la session utilisateur en supprimant les données sensibles
 * 
 * @param array $data Les données utilisateur
 * @security Supprime immédiatement le mot de passe de la session
 */
public function createdSession($data) {
    $_SESSION['connected'] = true;
    $_SESSION['user'] = $data;
    
    // CRITIQUE : Supprimer le mot de passe de la session pour la sécurité
    // Même hashé, un mot de passe ne doit jamais être stocké en session
    unset($_SESSION['user']['password']);
    
    // Ajouter un timestamp de création pour la gestion de l'expiration
    $_SESSION['session_created'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regen'] = time();
}

    /**
     * Méthode render pour afficher le header et footer
     * 
     * @param string $template Le template principal à inclure
     * @param string $layout Le layout à utiliser
     * @param array $data Les données à passer à la vue
     */
    public function render($template, $layout, $data = []) {
        // Extraire les données pour les rendre disponibles dans la vue
        if (!empty($data)) {
            extract($data);
        }
        // Définir le chemin du template pour l'inclusion dans le layout
        $template = "app/views/" . $template;
        
        // Inclure le layout qui inclura ensuite le template
        include "app/views/$layout";
    }

    /**
     * Méthode spécialisée pour les tickets (avec capture de contenu)
     * 
     * @param string $template Le template principal à inclure
     * @param string $layout Le layout à utiliser
     * @param array $data Les données à passer à la vue
     */
    public function renderTicket($template, $layout, $data = []) {
        // Extraire les données pour les rendre disponibles dans la vue
        if (!empty($data)) {
            extract($data);
        }
        
        // Capturer le contenu du template dans $content
        ob_start();
        include "app/views/" . $template;
        $content = ob_get_clean();
        
        // Inclure le layout qui affichera $content
        include "app/views/$layout";
    }

    /**
     * Réponse JSON standardisée et terminaison
     */
    protected function json(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
       
    

    // Méthode de redirection
    public function redirectTo($redirect) {
        header("Location: index.php?action=$redirect");
        exit;
    }
    
    /**
     * Vérifie si l'utilisateur est authentifié et si sa session n'a pas expiré
     * 
     * @return bool True si l'utilisateur est authentifié et sa session valide
     * 
     * @security Vérifie l'expiration de session pour prévenir la fixation de session
     */
    protected function isAuthenticated() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['connected']) || $_SESSION['connected'] === false) {
            return false;
        }
        
        // Vérifier que la session existe physiquement sur le serveur
        if (session_status() === PHP_SESSION_ACTIVE && session_id()) {
            $sessionFile = session_save_path() . '/sess_' . session_id();
            if (!file_exists($sessionFile)) {
                // Fichier de session supprimé - nettoyer et rediriger
                $this->destroySession();
                return false;
            }
        }
        
        // Vérifier l'expiration de session (basé sur config SESSION_LIFETIME)
        $sessionLifetime = defined('SESSION_LIFETIME') ? (int)SESSION_LIFETIME : (8 * 3600);
        if (isset($_SESSION['session_created']) && 
            (time() - $_SESSION['session_created']) > $sessionLifetime) {
            
            // Session expirée - nettoyer et rediriger
            $this->destroySession();
            return false;
        }
        
        return true;
    }

    /**
     * Applique l'expiration par inactivité et régénération périodique d'ID
     */
    protected function enforceSessionActivity(): void {
        if (!isset($_SESSION['connected']) || $_SESSION['connected'] !== true) {
            return;
        }
        
        // Vérifier que la session existe physiquement sur le serveur
        if (session_status() === PHP_SESSION_ACTIVE && session_id()) {
            $sessionFile = session_save_path() . '/sess_' . session_id();
            if (!file_exists($sessionFile)) {
                // Fichier de session supprimé - nettoyer et rediriger
                $this->destroySession();
                header('Location: index.php?action=login');
                exit;
            }
        }
        
        $now = time();
        $lifetime = defined('SESSION_LIFETIME') ? (int)SESSION_LIFETIME : (8 * 3600);
        $lastActivity = (int)($_SESSION['last_activity'] ?? $_SESSION['session_created'] ?? $now);
        if (($now - $lastActivity) > $lifetime) {
            // Expiration par inactivité
            $this->destroySession();
            header('Location: index.php?action=login');
            exit;
        }
        // Sliding expiration
        $_SESSION['last_activity'] = $now;
        // Régénération périodique d'ID (toutes les 5 minutes)
        $lastRegen = (int)($_SESSION['last_regen'] ?? 0);
        if ($now - $lastRegen >= 300) {
            session_regenerate_id(true);
            $_SESSION['last_regen'] = $now;
        }
    }

    /**
     * Détruit proprement la session utilisateur
     * 
     * @security Nettoie complètement la session pour éviter la réutilisation
     */
    protected function destroySession() {
        // Vider le tableau de session
        $_SESSION = array();
        
        // Détruire le cookie de session si il existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Détruire la session
        session_destroy();
    }

    /**
     * Vérifie si la requête est une requête AJAX
     * 
     * @return bool True si c'est une requête AJAX, False sinon
     * 
     * @security Détecte les requêtes AJAX pour adapter la réponse
     */
    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Vérifie la validité du token CSRF pour prévenir les attaques Cross-Site Request Forgery
     * 
     * @return bool True si le token est valide, False sinon
     * 
     * @security Cette méthode utilise hash_equals() pour une comparaison sécurisée
     *           qui évite les attaques par timing attack
     */
    protected function checkCSRFToken() {
        // Vérifier que le token existe dans la session ET dans la requête POST
        if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token'])) {
        return false;
    }
    
    // Utiliser hash_equals() pour une comparaison sécurisée (timing attack safe)
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Génère un token CSRF cryptographiquement sécurisé
 * 
 * @return string Le token CSRF généré
 * 
 * @security Utilise random_bytes(32) pour générer 256 bits d'entropie
 *           Suffisant pour résister aux attaques par force brute
 */
protected function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        // Générer 32 bytes (256 bits) d'entropie cryptographique
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF spécifique
 * 
 * @param string $token Le token à vérifier
 * @return bool True si le token est valide, False sinon
 * 
 * @security Utilise hash_equals() pour éviter les attaques par timing
 */
protected function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie l'accès administrateur (mode normal ou mode public avec clé)
 * 
 * @return void Redirige vers accesDenied si l'accès est refusé
 * 
 * @security Gère l'accès admin en mode public et mode normal
 */
protected function checkAdminAccess(): void {
    // En mode public, vérifier la clé d'accès admin
    if (PUBLIC_MODE && !ADMIN_MODE) {
        $this->redirectTo('accesDenied');
        return;
    }
    
    // En mode normal, vérifier la session utilisateur admin
    if (!PUBLIC_MODE && (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin')) {
        $this->redirectTo('accesDenied');
        return;
    }
}
}