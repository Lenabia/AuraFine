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
            // Si la commande est désormais livrée, appliquer les points de fidélité
            if ($status === 'Livrée') {
                $order = $this->ordersService->getOrderById($orderId);
                if ($order) {
                    $this->ordersService->applyLoyaltyPoints($order);
                }
            }
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

    /**
     * Affiche les commandes de l'utilisateur connecté
     * 
     * @route GET /my-orders
     */
    public function showUserOrders(): void {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'user') {
            $this->redirectTo('login');
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            $this->redirectTo('login');
        }

        // Récupérer la commande active (en cours)
        $activeOrder = $this->ordersService->getActiveOrderByUserId($userId);
        
        // Récupérer l'historique des commandes passées
        $pastOrders = $this->ordersService->getPastOrdersByUserId($userId);

        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();

        $this->render('users_orders.phtml', 'layout.phtml', [
            'pageTitle' => 'Mes commandes',
            'activeOrder' => $activeOrder,
            'pastOrders' => $pastOrders,
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Vérifie le statut de la commande active (AJAX)
     * 
     * @route POST /check-order-status
     */
    public function checkOrderStatus(): void {
        // Vérifier que c'est une requête AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Requête invalide']);
        }

        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'user') {
            http_response_code(401);
            $this->json(['success' => false, 'message' => 'Non autorisé']);
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            $this->json(['success' => false, 'message' => 'Non autorisé']);
        }

        try {
            // Récupérer la commande active
            $activeOrder = $this->ordersService->getActiveOrderByUserId($userId);
            
            if ($activeOrder) {
                $this->json([
                    'success' => true,
                    'order' => [
                        'id' => $activeOrder['id'],
                        'status' => $activeOrder['status'],
                        'updated_at' => $activeOrder['updated_at']
                    ]
                ]);
            } else {
                $this->json([
                    'success' => true,
                    'order' => null
                ]);
            }
        } catch (\Exception $e) {
            error_log("Erreur checkOrderStatus: " . $e->getMessage());
            http_response_code(500);
            $this->json(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * Récupère les détails d'une commande (AJAX)
     * 
     * @route GET /order-details
     */
    public function getOrderDetails($orderId): void {
        // Vérifier que c'est une requête AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Requête invalide']);
        }

        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'user') {
            http_response_code(401);
            $this->json(['success' => false, 'message' => 'Non autorisé']);
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $orderId = (int)($orderId ?? 0);
        
        if ($userId <= 0 || $orderId <= 0) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Paramètres invalides']);
        }

        try {
            // Récupérer la commande avec vérification de propriétaire
            $order = $this->ordersService->getOrderById($orderId);
            
            if (!$order || (int)$order['users_id'] !== $userId) {
                http_response_code(404);
                $this->json(['success' => false, 'message' => 'Commande non trouvée']);
            }

            // Générer le HTML des détails
            $html = $this->generateOrderDetailsHtml($order);
            
            $this->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            error_log("Erreur getOrderDetails: " . $e->getMessage());
            http_response_code(500);
            $this->json(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * Génère le HTML des détails de commande
     */
    private function generateOrderDetailsHtml(array $order): string {
        $html = '<div class="order-details-content">';
        
        // Informations générales
        $html .= '<div class="order-info">';
        $html .= '<h4>Commande #' . (int)$order['id'] . '</h4>';
        $html .= '<p><strong>Date :</strong> ' . date('d/m/Y à H:i', strtotime($order['order_date'])) . '</p>';
        $html .= '<p><strong>Statut :</strong> ' . htmlspecialchars($order['status']) . '</p>';
        $html .= '<p><strong>Montant total :</strong> ' . number_format($order['total_amount'], 0, ',', ' ') . ' FCFA</p>';
        
        if (!empty($order['delivery_address'])) {
            $html .= '<p><strong>Adresse de livraison :</strong> ' . htmlspecialchars($order['delivery_address']) . '</p>';
        }
        
        if (!empty($order['delivery_comment'])) {
            $html .= '<p><strong>Commentaire :</strong> ' . htmlspecialchars($order['delivery_comment']) . '</p>';
        }
        
        $html .= '</div>';

        // Détails des articles
        if (!empty($order['details'])) {
            $html .= '<div class="order-items">';
            $html .= '<h4>Articles commandés</h4>';
            
            foreach ($order['details'] as $detail) {
                $html .= '<div class="item">';
                $html .= '<span class="item-name">' . htmlspecialchars($detail['item_label']) . '</span>';
                $html .= '<span class="item-quantity">x' . (int)$detail['quantity'] . '</span>';
                $html .= '<span class="item-price">' . number_format($detail['subtotal'], 0, ',', ' ') . ' FCFA</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        $html .= '</div>';
        
        return $html;
    }


}
