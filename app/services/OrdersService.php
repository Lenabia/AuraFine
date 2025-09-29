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
}
