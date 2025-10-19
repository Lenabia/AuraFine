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
     * Valide un statut précédent (tous les statuts sauf 'annulée')
     * 
     * @param string $status Statut précédent à valider
     * @return bool Valide ou non
     */
    public function isValidPreviousStatus(string $status): bool {
        $validPreviousStatuses = ['En attente', 'En preparation', 'Livraison en cours', 'Livrée'];
        return in_array($status, $validPreviousStatuses, true);
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
     * @param string|null $previousStatus Statut précédent (pour annulation)
     * @return bool Succès de la mise à jour
     */
    public function updateOrderStatus(int $id, string $status, ?string $previousStatus = null): bool {
        if (!$this->isValidStatus($status)) {
            return false;
        }
        $ok = $this->ordersModel->updateStatus($id, $status, $previousStatus);
        if ($ok && $status === 'Livrée') {
            // Sécuriser: recharger la commande depuis la DB et appliquer les points
            try {
                $order = $this->getOrderById($id);
                if ($order) {
                    $this->applyLoyaltyPoints($order);
                }
            } catch (\Throwable $e) {
                error_log('updateOrderStatus/applyLoyaltyPoints error: ' . $e->getMessage());
            }
        }
        return $ok;
    }

    /**
     * Annule une commande
     * 
     * @param int $id ID de la commande
     * @return bool Succès de l'annulation
     */
    public function cancelOrder(int $id): bool {
        // Récupérer le statut actuel avant annulation
        $order = $this->getOrderById($id);
        if (!$order) {
            error_log("cancelOrder: Commande introuvable: {$id}");
            return false;
        }
        
        $currentStatus = $order['status'];
        
        // Vérifier que la commande n'est pas déjà annulée
        if ($currentStatus === 'annulée') {
            error_log("cancelOrder: Commande déjà annulée: {$id}");
            return false;
        }
        
        // Vérifier que le statut actuel est valide pour l'annulation
        if (!$this->isValidPreviousStatus($currentStatus)) {
            error_log("cancelOrder: Statut actuel invalide pour annulation: {$currentStatus} (commande {$id})");
            return false;
        }
        
        return $this->updateOrderStatus($id, 'annulée', $currentStatus);
    }

    /**
     * Réactive une commande annulée
     * 
     * @param int $id ID de la commande
     * @return bool Succès de la réactivation
     */
    public function reactivateOrder(int $id): bool {
        // Récupérer la commande pour obtenir le statut précédent
        $order = $this->getOrderById($id);
        if (!$order) {
            error_log("reactivateOrder: Commande introuvable: {$id}");
            return false;
        }
        
        if ($order['status'] !== 'annulée') {
            error_log("reactivateOrder: Commande non annulée: {$id} (statut: {$order['status']})");
            return false;
        }
        
        if (!$order['previous_status']) {
            error_log("reactivateOrder: Aucun statut précédent trouvé: {$id}");
            return false;
        }
        
        // Valider le statut précédent
        $previousStatus = $order['previous_status'];
        if (!$this->isValidPreviousStatus($previousStatus)) {
            error_log("reactivateOrder: Statut précédent invalide: {$previousStatus} (commande {$id})");
            return false;
        }
        
        // Vérifier que le statut précédent est différent du statut actuel
        if ($previousStatus === $order['status']) {
            error_log("reactivateOrder: Statut précédent identique au statut actuel: {$previousStatus} (commande {$id})");
            return false;
        }
        
        // Restaurer le statut précédent et effacer previous_status
        $ok = $this->ordersModel->updateStatus($id, $previousStatus, null);
        
        // Si la commande est maintenant livrée, appliquer les points de fidélité
        if ($ok && $previousStatus === 'Livrée') {
            try {
                // Recharger la commande mise à jour pour appliquer les points
                $updatedOrder = $this->getOrderById($id);
                if ($updatedOrder) {
                    $this->applyLoyaltyPoints($updatedOrder);
                }
            } catch (\Throwable $e) {
                error_log('reactivateOrder/applyLoyaltyPoints error: ' . $e->getMessage());
            }
        }
        
        return $ok;
    }

    /**
     * Confirme une commande par le client
     * 
     * @param int $orderId ID de la commande
     * @param int $userId ID de l'utilisateur
     * @param string $comment Commentaire optionnel du client
     * @return bool Succès de la confirmation
     */
    public function confirmOrderByCustomer(int $orderId, int $userId, string $comment = ''): bool {
        $success = $this->ordersModel->confirmOrderByCustomer($orderId, $userId, $comment);
        
        if ($success) {
            // Appliquer les points de fidélité après confirmation client
            try {
                $order = $this->getOrderById($orderId);
                if ($order) {
                    $this->applyLoyaltyPoints($order);
                }
            } catch (\Throwable $e) {
                error_log('confirmOrderByCustomer/applyLoyaltyPoints error: ' . $e->getMessage());
            }
        }
        
        return $success;
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

    /**
     * Variante sans transaction interne (utilisée quand le contrôleur gère la transaction)
     */
    public function createOrderAndDetailsNoTx(array $orderData, array $cartItems): int|false {
        try {
            $orderId = $this->ordersModel->createOrder($orderData);
            if (!$orderId) {
                return false;
            }
            foreach ($cartItems as $item) {
                $detailData = [
                    'orders_id' => $orderId,
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'unit_price' => (float)($item['price'] ?? 0),
                    'item_label' => $item['name'] ?? ($item['item_label'] ?? ''),
                    'salads_id' => $item['product_type'] === 'salad' ? (int)$item['product_id'] : null,
                    'drinks_id' => $item['product_type'] === 'drink' ? (int)$item['product_id'] : null,
                    'menus_id' => $item['product_type'] === 'menu' ? (int)$item['product_id'] : null,
                    'fruits_veggies_id' => $item['product_type'] === 'fruit' ? (int)$item['product_id'] : null,
                    'desserts_id' => null,
                    'order_custom_salads_id' => null
                ];
                if (!$this->ordersDetailModel->createOrderDetail($detailData)) {
                    return false;
                }
            }
            return (int)$orderId;
        } catch (\Throwable $e) {
            error_log('createOrderAndDetailsNoTx error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Applique les points de fidélité (cashback) au prorata pour une commande livrée
     * - Client: 5% du montant total en FCFA (arrondi)
     * - Parrain: 2.5% du montant total en FCFA (arrondi) si présent
     * - Évite les doublons via vérification préalable dans loyalty_points_history
     *
     * @param array $order Tableau associatif: id, users_id, total_amount, status
     */
    public function applyLoyaltyPoints(array $order): void {
        // Conditions minimales
        $orderId = (int)($order['id'] ?? 0);
        $userId = (int)($order['users_id'] ?? 0);
        $status = (string)($order['status'] ?? '');
        $totalAmount = (float)($order['total_amount'] ?? 0);
        // Base d'éligibilité aux points: total des produits
        $eligibleAmount = max(0, $totalAmount);

        if ($orderId <= 0 || $userId <= 0) {
            return; // commande invitée ou invalide
        }
        if ($status !== 'Livrée') {
            return; // on ne crédite qu'une commande livrée
        }
        if ($eligibleAmount <= 0) {
            return; // rien à créditer
        }

        $pdo = $this->ordersModel->getConnection();

        // Calculs arrondis aux multiples de 5 FCFA
        $clientAmount = $this->roundToNearest5($eligibleAmount * 0.05);   // 5% sur produits
        $refAmount = $this->roundToNearest5($eligibleAmount * 0.025);     // 2.5% sur produits

        try {
            $pdo->beginTransaction();

            // Crédit Client si pas déjà crédité
            if ($clientAmount > 0 && !$this->historyExists($pdo, $userId, $orderId, 'order_5pct')) {
                $this->creditUserAndLog($pdo, $userId, $orderId, $clientAmount, 'order_5pct',
                    sprintf('5%% de %s FCFA = %s FCFA', number_format($eligibleAmount, 0, ',', ' '), number_format($clientAmount, 0, ',', ' '))
                );

                // Mettre à jour la session si l'utilisateur courant est concerné
                if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $userId) {
                    $_SESSION['user']['loyalty_points'] = (int)($_SESSION['user']['loyalty_points'] ?? 0) + $clientAmount;
                }
            }

            // Chercher le parrain
            $referrerId = $this->getReferrerId($pdo, $userId);
            if ($referrerId > 0 && $refAmount > 0 && !$this->historyExists($pdo, $referrerId, $orderId, 'referral_5pct')) {
                $this->creditUserAndLog($pdo, $referrerId, $orderId, $refAmount, 'referral_5pct',
                    sprintf('2.5%% de %s FCFA = %s FCFA (parrainage)', number_format($eligibleAmount, 0, ',', ' '), number_format($refAmount, 0, ',', ' '))
                );

                // Mettre à jour la session si l'utilisateur courant est le parrain
                if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $referrerId) {
                    $_SESSION['user']['loyalty_points'] = (int)($_SESSION['user']['loyalty_points'] ?? 0) + $refAmount;
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('applyLoyaltyPoints error: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie l'existence d'un enregistrement d'historique (anti-doublon)
     */
    private function historyExists(\PDO $pdo, int $userId, int $orderId, string $reason): bool {
        $sql = 'SELECT id FROM loyalty_points_history WHERE users_id = :uid AND orders_id = :oid AND reason = :reason LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':oid', $orderId, \PDO::PARAM_INT);
        $stmt->bindValue(':reason', $reason);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Retourne l'id du parrain si présent, sinon 0
     */
    private function getReferrerId(\PDO $pdo, int $userId): int {
        $sql = 'SELECT COALESCE(referred_by_users_id, 0) AS ref_id FROM users WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['ref_id'] ?? 0);
    }

    /**
     * Effectue l'UPDATE du solde utilisateur et insère l'historique
     */
    private function creditUserAndLog(\PDO $pdo, int $userId, int $orderId, int $amount, string $reason, string $note = ''): void {
        // Mettre à jour le solde
        $sqlUpdate = 'UPDATE users SET loyalty_points = COALESCE(loyalty_points, 0) + :amount WHERE id = :id';
        $stmt = $pdo->prepare($sqlUpdate);
        $stmt->bindValue(':amount', $amount, \PDO::PARAM_INT);
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        // Insérer l'historique
        $sqlInsert = 'INSERT INTO loyalty_points_history (users_id, orders_id, points, reason, note, created_at) VALUES (:uid, :oid, :points, :reason, :note, NOW())';
        $stmt2 = $pdo->prepare($sqlInsert);
        $stmt2->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt2->bindValue(':oid', $orderId, \PDO::PARAM_INT);
        $stmt2->bindValue(':points', $amount, \PDO::PARAM_INT);
        $stmt2->bindValue(':reason', $reason);
        $stmt2->bindValue(':note', $note);
        $stmt2->execute();
    }

    /**
     * Récupère toutes les commandes actives d'un utilisateur (en cours)
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Commandes actives avec détails
     */
    public function getActiveOrdersByUserId(int $userId): array {
        $orders = $this->ordersModel->getActiveOrdersByUserId($userId);
        
        // Enrichir chaque commande avec ses détails
        foreach ($orders as &$order) {
            $order['details'] = $this->getOrderDetails($order['id']);
        }
        
        return $orders;
    }

    /**
     * Récupère l'historique des commandes d'un utilisateur (terminées)
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Commandes passées avec détails
     */
    public function getPastOrdersByUserId(int $userId): array {
        $orders = $this->ordersModel->getPastOrdersByUserId($userId);
        
        // Récupérer les détails pour chaque commande
        foreach ($orders as &$order) {
            $order['details'] = $this->getOrderDetails($order['id']);
        }
        
        return $orders;
    }

    /**
     * Arrondit un montant au multiple de 5 FCFA le plus proche
     * 
     * @param float $amount Montant à arrondir
     * @return int Montant arrondi aux multiples de 5
     */
    private function roundToNearest5(float $amount): int {
        return (int)(round($amount / 5) * 5);
    }

    /**
     * Formate le numéro de commande selon le type d'utilisateur
     * 
     * @param int $orderId ID de la commande
     * @param int|null $userId ID de l'utilisateur (null = invité)
     * @return string Numéro formaté (AFI-XXX ou AFC-XXX)
     */
    public function formatOrderNumber(int $orderId, ?int $userId = null): string {
        return $userId === null ? "AFI-" . $orderId : "AFC-" . $orderId;
    }
}
