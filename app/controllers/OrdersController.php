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
        error_log('DEBUG: checkAdmin() - Session user: ' . (isset($_SESSION['user']) ? 'OUI' : 'NON'));
        if (isset($_SESSION['user'])) {
            error_log('DEBUG: checkAdmin() - Role: ' . ($_SESSION['user']['role'] ?? 'NULL'));
        }
        
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            error_log('DEBUG: checkAdmin() - Redirection vers accesDenied');
            $this->redirectTo('accesDenied');
        }
        
        error_log('DEBUG: checkAdmin() - Admin vérifié avec succès');
    }

    /**
     * Affiche la liste des commandes avec métriques et filtres
     * 
     * @route GET /orders
     */
    public function listOrders(): void {
        error_log('DEBUG: listOrders() appelé - Session user: ' . (isset($_SESSION['user']) ? 'OUI' : 'NON'));
        if (isset($_SESSION['user'])) {
            error_log('DEBUG: Role utilisateur: ' . ($_SESSION['user']['role'] ?? 'NULL'));
        }
        
        $this->checkAdmin();

        // Récupérer les filtres depuis GET
        $filters = [
            'status' => trim($_GET['status'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'order_date'),
            'direction' => trim($_GET['direction'] ?? 'DESC')
        ];
        
        error_log('DEBUG: Filtres reçus: ' . json_encode($filters));

        // Récupérer les données via le service
        $metrics = $this->ordersService->getFormattedMetrics();
        $orders = $this->ordersService->getOrders($filters);
        $statusStats = $this->ordersService->getStatusStats();

        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();

        // Si c'est une requête AJAX, retourner seulement la table
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            // Extraire les données pour les rendre disponibles dans la vue
            extract([
                'orders' => $orders,
                'filters' => $filters,
                'ordersService' => $this->ordersService
            ]);
            // Inclure seulement le fichier partiel
            include "app/views/orders-table.phtml";
            return;
        }

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
            'csrfToken' => $csrfToken,
            'ordersService' => $this->ordersService
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
            'csrfToken' => $csrfToken,
            'ordersService' => $this->ordersService
        ]);
    }

    /**
     * Met à jour le statut d'une commande
     * 
     * @route POST /orders-update-status
     */
    public function updateOrderStatus(): void {
        $this->checkAdmin();

        $orderId = (int)($_POST['order_id'] ?? 0);
        
        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité';
            $this->redirectTo('admin-orders-details&id=' . $orderId);
        }
        $status = trim($_POST['status'] ?? '');

        if ($orderId <= 0 || empty($status)) {
            $_SESSION['error_message'] = 'Paramètres invalides';
            $this->redirectTo('admin-orders-details&id=' . $orderId);
        }

        $success = $this->ordersService->updateOrderStatus($orderId, $status);
        if ($success) {
            // Si la commande est désormais annulée, recréditer les points utilisés (idempotent via unique key)
            if ($status === 'annulée') {
                try {
                    $pdo = $this->ordersService->getOrdersModel()->getConnection();
                    $pdo->beginTransaction();
                    // Récupérer les dépenses de points pour cette commande
                    $stmt = $pdo->prepare('SELECT users_id, ABS(points) AS amount FROM loyalty_points_history WHERE orders_id = :oid AND reason = "redeem"');
                    $stmt->bindValue(':oid', $orderId, \PDO::PARAM_INT);
                    $stmt->execute();
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $uid = (int)($r['users_id'] ?? 0);
                        $amt = (int)($r['amount'] ?? 0);
                        if ($uid > 0 && $amt > 0) {
                            // Créditer (reason=adjust) — unique (users_id, orders_id, reason) protège des doublons si on switch/revient
                            $usersModel = new \app\Models\Users();
                            $usersModel->addLoyaltyPoints($uid, $amt, $orderId, 'adjust', 'Refund points après annulation');
                            if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $uid) {
                                $_SESSION['user']['loyalty_points'] = (int)($_SESSION['user']['loyalty_points'] ?? 0) + $amt;
                            }
                        }
                    }
                    $pdo->commit();
                } catch (\Throwable $e) {
                    if (isset($pdo)) $pdo->rollBack();
                    error_log('Refund loyalty on cancel error: ' . $e->getMessage());
                }
            }
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

        $this->redirectTo('admin-orders-details&id=' . $orderId);
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
     * Réactive une commande annulée
     * 
     * @route POST /orders-reactivate
     */
    public function reactivateOrder(): void {
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

        $success = $this->ordersService->reactivateOrder($orderId);
        
        if ($success) {
            $_SESSION['success_message'] = 'Commande réactivée avec succès';
        } else {
            $_SESSION['error_message'] = 'Erreur lors de la réactivation de la commande';
        }

        $this->redirectTo('admin-orders-details&id=' . $orderId);
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
            'csrfToken' => $csrfToken,
            'ordersService' => $this->ordersService
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
        $html .= '<h4>Commande #' . $this->ordersService->formatOrderNumber($order['id'], $order['users_id']) . '</h4>';
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

    /**
     * Affiche le ticket d'impression d'une commande
     * 
     * @route GET /admin-orders-ticket
     * @param int $id ID de la commande
     */
    public function printTicket(int $id): void {
        $this->checkAdmin();
        
        if ($id <= 0) {
            $_SESSION['error_message'] = 'ID de commande invalide';
            $this->redirectTo('admin-orders');
        }
        
        $order = $this->ordersService->getOrderById($id);
        if (!$order) {
            $_SESSION['error_message'] = 'Commande introuvable';
            $this->redirectTo('admin-orders');
        }
        
        $orderDetails = $this->ordersService->getOrderDetails($id);
        $productsSubtotal = array_sum(array_column($orderDetails, 'subtotal'));
        $finalTotal = $productsSubtotal - $order['loyalty_points_used'] + $order['delivery_fee'];
        
        $this->renderTicket('order-ticket.phtml', 'ticket-layout.phtml', [
            'order' => $order,
            'orderDetails' => $orderDetails,
            'productsSubtotal' => $productsSubtotal,
            'finalTotal' => $finalTotal,
            'companyName' => 'AuraFine',
            'companyAddress' => '123 Rue de l\'Exemple, Dakar',
            'companyPhone' => '+221 77 123 45 67',
            'ordersService' => $this->ordersService
        ]);
    }


}
