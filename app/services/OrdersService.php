<?php
namespace app\services;

use app\Models\Orders;
use app\Models\OrdersDetail;

/**
 * Service Orders: logique métier des commandes
 * - Validation des statuts
 * - Formatage des données
 * - Export CSV
 * - Règles métier
 */
class OrdersService {
    private Orders $ordersModel;
    private OrdersDetail $ordersDetailModel;

    public function __construct() {
        $this->ordersModel = new Orders();
        $this->ordersDetailModel = new OrdersDetail();
    }

    /**
     * Valide un statut de commande
     * 
     * @param string $status Statut à valider
     * @return bool Valide ou non
     */
    public function isValidStatus(string $status): bool {
        $validStatuses = ['En attente', 'En preparation', 'Livraison en cours', 'Livrée', 'annulée'];
        return in_array($status, $validStatuses, true);
    }

    /**
     * Récupère les métriques formatées
     * 
     * @return array Métriques formatées
     */
    public function getFormattedMetrics(): array {
        $rawMetrics = $this->ordersModel->getMetrics();
        
        return [
            'pending_orders' => (int)($rawMetrics['pending_orders'] ?? 0),
            'total_revenue' => (float)($rawMetrics['total_revenue'] ?? 0),
            'total_orders' => (int)($rawMetrics['total_orders'] ?? 0),
            'avg_basket' => (float)($rawMetrics['avg_basket'] ?? 0)
        ];
    }

    /**
     * Met à jour le statut d'une commande avec validation
     * 
     * @param int $id ID de la commande
     * @param string $status Nouveau statut
     * @return bool Succès de la mise à jour
     */
    public function updateOrderStatus(int $id, string $status): bool {
        if (!$this->isValidStatus($status)) {
            return false;
        }

        return $this->ordersModel->updateStatus($id, $status);
    }

    /**
     * Annule une commande
     * 
     * @param int $id ID de la commande
     * @return bool Succès de l'annulation
     */
    public function cancelOrder(int $id): bool {
        return $this->updateOrderStatus($id, 'annulée');
    }


    /**
     * Récupère les commandes avec filtres
     * 
     * @param array $filters Filtres
     * @return array Commandes
     */
    public function getOrders(array $filters = []): array {
        return $this->ordersModel->getOrders($filters);
    }

    /**
     * Récupère une commande par ID
     * 
     * @param int $id ID de la commande
     * @return array|null Commande
     */
    public function getOrderById(int $id): ?array {
        return $this->ordersModel->getOrderById($id);
    }

    /**
     * Récupère les statistiques par statut
     * 
     * @return array Statistiques
     */
    public function getStatusStats(): array {
        return $this->ordersModel->getStatusStats();
    }

    /**
     * Récupère les détails d'une commande
     * 
     * @param int $orderId ID de la commande
     * @return array Détails
     */
    public function getOrderDetails(int $orderId): array {
        return $this->ordersDetailModel->getDetailsWithProducts($orderId);
    }

    /**
     * Récupère les salades personnalisées d'une commande
     * 
     * @param int $orderId ID de la commande
     * @return array Salades personnalisées
     */
    public function getCustomSaladsForOrder(int $orderId): array {
        return $this->ordersDetailModel->getCustomSaladsForOrder($orderId);
    }

    /**
     * Récupère le modèle Orders (pour les transactions)
     * 
     * @return Orders Modèle Orders
     */
    public function getOrdersModel(): Orders {
        return $this->ordersModel;
    }

    /**
     * Récupère le modèle OrdersDetail (pour les transactions)
     * 
     * @return OrdersDetail Modèle OrdersDetail
     */
    public function getOrdersDetailModel(): OrdersDetail {
        return $this->ordersDetailModel;
    }

    /**
     * Crée une commande avec transaction (rollback en cas d'échec)
     * 
     * @param array $orderData Données de la commande
     * @param array $cartItems Articles du panier
     * @return int|false ID de la commande créée ou false en cas d'erreur
     */
    public function createOrderWithTransaction(array $orderData, array $cartItems): int|false {
        try {
            // Démarrer la transaction
            $this->ordersModel->getConnection()->beginTransaction();

            // Créer la commande
            $orderId = $this->ordersModel->createOrder($orderData);
            if (!$orderId) {
                throw new \Exception('Échec de création de la commande');
            }

            // Créer les détails de commande
            foreach ($cartItems as $item) {
                $detailData = [
                    'orders_id' => $orderId,
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'unit_price' => (float)($item['price'] ?? 0),
                    'item_label' => $item['name'] ?? '',
                    'salads_id' => $item['product_type'] === 'salad' ? (int)$item['product_id'] : null,
                    'drinks_id' => $item['product_type'] === 'drink' ? (int)$item['product_id'] : null,
                    'menus_id' => $item['product_type'] === 'menu' ? (int)$item['product_id'] : null,
                    'fruits_veggies_id' => $item['product_type'] === 'fruit' ? (int)$item['product_id'] : null,
                    'desserts_id' => null,
                    'order_custom_salads_id' => null
                ];

                if (!$this->ordersDetailModel->createOrderDetail($detailData)) {
                    throw new \Exception('Échec de création des détails de commande');
                }
            }

            // Valider la transaction
            $this->ordersModel->getConnection()->commit();
            return $orderId;

        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            $this->ordersModel->getConnection()->rollBack();
            error_log('Erreur création commande: ' . $e->getMessage());
            return false;
        }
    }
}
