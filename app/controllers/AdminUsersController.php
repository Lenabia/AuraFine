<?php
namespace app\controllers;

use app\models\AdminUsers;
use app\middleware\Middleware;

/**
 * Contrôleur Admin - Utilisateurs
 */
class AdminUsersController extends Middleware {
    private AdminUsers $usersModel;

    public function __construct() {
        $this->usersModel = new AdminUsers();
    }

    private function checkAdmin(): void {
        // Utiliser la méthode centralisée du Middleware
        $this->checkAdminAccess();
    }

    public function listUsers(): void {
        error_log("DEBUG: AdminUsersController::listUsers() appelé");
        $this->checkAdmin();

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'orders_count'),
            'direction' => trim($_GET['direction'] ?? 'DESC'),
            'page' => (int)($_GET['page'] ?? 1),
            'limit' => 20
        ];

        $metrics = $this->usersModel->getMetrics();
        $users = $this->usersModel->getUsers($filters);

        $csrfToken = $this->generateCSRFToken();

        // Rendu partiel du tableau pour les requêtes AJAX (partial=table)
        // Permet d'actualiser la liste sans recharger toute la page
        if (isset($_GET['partial']) && $_GET['partial'] === 'table') {
            // Expose les variables nécessaires au fragment
            $pageTitle = 'Gestion des utilisateurs';
            $currentPage = 'admin-users';
            $breadcrumbs = [];
            // Inclure directement le fragment et terminer la requête
            require 'app/views/users-table.phtml';
            exit;
        }

        $this->render('users.phtml', 'admin-layout.phtml', [
            'pageTitle' => 'Gestion des utilisateurs',
            'currentPage' => 'admin-users',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => 'index.php?action=admin-home', 'icon' => 'fas fa-tachometer-alt'],
                ['title' => 'Utilisateurs', 'url' => 'index.php?action=admin-users', 'icon' => 'fas fa-users']
            ],
            'metrics' => $metrics,
            'users' => $users,
            'filters' => $filters,
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Action POST: Ajout de points de fidélité à un utilisateur
     * - Protégée par checkAdmin et CSRF
     * - Valide user_id et points
     */
    public function addLoyaltyPoints(): void {
        $this->checkAdmin();

        // Méthode et CSRF
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = "Requête invalide ou token CSRF invalide.";
            $this->redirectTo('admin-users');
        }

        // Validation des entrées
        $userId = (int)($_POST['user_id'] ?? 0);
        $points = (int)($_POST['points'] ?? 0);

        if ($userId <= 0 || $points <= 0) {
            $_SESSION['error_message'] = "Paramètres invalides (utilisateur/points).";
            $this->redirectTo('admin-users');
        }

        // Appel modèle
        $ok = $this->usersModel->addLoyaltyPoints($userId, $points);
        if ($ok) {
            $_SESSION['success_message'] = "Points ajoutés avec succès.";
        } else {
            $_SESSION['error_message'] = "Échec de l'ajout de points.";
        }

        // Redirection
        $this->redirectTo('admin-users');
    }
}


