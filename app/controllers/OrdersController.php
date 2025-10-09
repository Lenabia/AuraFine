<?php
namespace app\controllers;

use app\services\OrdersService;
use app\middleware\Middleware;

/**
 * Contrôleur Orders: gestion des commandes admin
 * - Vérification du rôle admin
 * - Délégation à OrdersService
 * - Gestion des sessions et redirections
 */
class OrdersController extends Middleware {
    private OrdersService $ordersService;

    public function __construct() {
        $this->ordersService = new OrdersService();
    }

    /**
     * Vérifie que l'utilisateur est admin
     */
    private function checkAdmin(): void {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->redirectTo('accesDenied');
        }
    }

    /**
     * Affiche la liste des commandes avec métriques et filtres
     * 
     * @route GET /orders
     */
    public function listOrders(): void {
        $this->checkAdmin();

        // Récupérer les filtres depuis GET
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'order_date'),
            'direction' => trim($_GET['direction'] ?? 'DESC')
        ];

        // Récupérer les données via le service
        $metrics = $this->ordersService->getFormattedMetrics();
        $orders = $this->ordersService->getOrders($filters);
        $statusStats = $this->ordersService->getStatusStats();

        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();

        $this->render('orders.phtml', 'admin-layout.phtml', [
            'pageTitle' => 'Gestion des commandes',
            'currentPage' => 'admin-orders',
            'breadcrumbs' => [
                [
                    'title' => 'Dashboard',
                    'url' => 'index.php?action=admin-home',
                    'icon' => 'fas fa-tachometer-alt'
                ],
                [
                    'title' => 'Commandes',
                    'url' => 'index.php?action=admin-orders',
                    'icon' => 'fas fa-shopping-bag'
                ]
            ],
            'metrics' => $metrics,
            'orders' => $orders,
            'statusStats' => $statusStats,
            'filters' => $filters,
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Affiche le détail d'une commande
     * 
     * @route GET /orders-details/:id
     * @param int $id ID de la commande
     */
    public function viewOrdersDetail(int $id): void {
        $this->checkAdmin();

        // Validation minimale de l'ID
        if ($id <= 0) {
            $_SESSION['error_message'] = 'ID de commande invalide';
            $this->redirectTo('admin-orders');
        }

        // Récupérer la commande via le service
        $order = $this->ordersService->getOrderById($id);
        
        if (!$order) {
            $_SESSION['error_message'] = 'Commande introuvable';
            $this->redirectTo('admin-orders');
        }

        // Récupérer les détails via le service
        $orderDetails = $this->ordersService->getOrderDetails($id);
        $customSalads = $this->ordersService->getCustomSaladsForOrder($id);

        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();

        $this->render('orders-detail.phtml', 'admin-layout.phtml', [
            'pageTitle' => 'Détail de la commande #' . $id,
            'currentPage' => 'admin-orders',
            'breadcrumbs' => [
                [
                    'title' => 'Dashboard',
                    'url' => 'index.php?action=admin-home',
                    'icon' => 'fas fa-tachometer-alt'
                ],
                [
                    'title' => 'Commandes',
                    'url' => 'index.php?action=admin-orders',
                    'icon' => 'fas fa-shopping-bag'
                ],
                [
                    'title' => 'Détail #' . $id,
                    'url' => 'index.php?action=admin-orders-details&id=' . $id,
                    'icon' => 'fas fa-eye'
                ]
            ],
            'order' => $order,
            'orderDetails' => $orderDetails,
            'customSalads' => $customSalads,
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Met à jour le statut d'une commande
     * 
     * @route POST /orders-update-status
     */
    public function updateOrderStatus(): void {
        $this->checkAdmin();

        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité';
            $this->redirectTo('admin-orders');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($orderId <= 0 || empty($status)) {
            $_SESSION['error_message'] = 'Paramètres invalides';
            $this->redirectTo('admin-orders');
        }

        $success = $this->ordersService->updateOrderStatus($orderId, $status);
        
        if ($success) {
            $_SESSION['success_message'] = 'Statut de la commande mis à jour';
        } else {
            $_SESSION['error_message'] = 'Statut invalide ou erreur lors de la mise à jour';
        }

        $this->redirectTo('admin-orders');
    }

    /**
     * Annule une commande
     * 
     * @route POST /orders-cancel
     */
    public function cancelOrder(): void {
        $this->checkAdmin();

        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité';
            $this->redirectTo('admin-orders');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);

        if ($orderId <= 0) {
            $_SESSION['error_message'] = 'ID de commande invalide';
            $this->redirectTo('admin-orders');
        }

        $success = $this->ordersService->cancelOrder($orderId);
        
        if ($success) {
            $_SESSION['success_message'] = 'Commande annulée';
        } else {
            $_SESSION['error_message'] = 'Erreur lors de l\'annulation';
        }

        $this->redirectTo('admin-orders');
    }


}
