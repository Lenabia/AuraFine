<?php
namespace app\Models;

/**
 * Modèle Orders: accès DB pour les commandes
 * - Aucune logique métier ici (validation dans le contrôleur)
 * - PDO préparé via Database
 */
class Orders extends Database {

    /**
     * Récupère les métriques brutes des commandes
     * 
     * @return array Données brutes de la DB
     */
    public function getMetrics(): array {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM orders WHERE status = 'En attente') AS pending_orders,
                    COALESCE(SUM(CASE WHEN status != 'annulée' THEN total_amount ELSE 0 END), 0) AS total_revenue,
                    COUNT(*) AS total_orders,
                    COALESCE(AVG(CASE WHEN status != 'annulée' THEN total_amount ELSE NULL END), 0) AS avg_basket
                FROM orders";

        return $this->findOne($sql);
    }

    /**
     * Récupère la liste des commandes
     * 
     * @param array $filters Filtres SQL
     * @return array Données brutes de la DB
     */
    public function getOrders(array $filters = []): array {
        $sql = "SELECT o.id, o.status, o.previous_status, o.order_date, o.total_amount, o.delivery_fee,
                       COALESCE(u.first_name, o.customer_first_name) AS first_name,
                       COALESCE(u.last_name, o.customer_last_name) AS last_name,
                       COALESCE(u.phone, o.customer_phone) AS phone,
                       COALESCE(u.email, o.customer_email) AS email,
                       c.name AS city_name, n.name AS neighborhood_name,
                       COALESCE(lph.points_used, 0) AS loyalty_points_used
                FROM orders o
                LEFT JOIN users u ON u.id = o.users_id
                LEFT JOIN cities c ON c.id = o.cities_id
                LEFT JOIN neighborhoods n ON n.id = o.neighborhoods_id
                LEFT JOIN (
                    SELECT orders_id, ABS(points) AS points_used 
                    FROM loyalty_points_history 
                    WHERE reason = 'redeem'
                ) lph ON lph.orders_id = o.id
                WHERE 1=1";

        $params = [];


        // Filtre par statut
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params['status'] = $filters['status'];
        }

        // Filtre par date (intervalle)
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.order_date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.order_date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        // Tri
        $sort = $filters['sort'] ?? 'order_date';
        $direction = $filters['direction'] ?? 'DESC';
        
        if (in_array($sort, ['order_date', 'total_amount', 'first_name', 'last_name'])) {
            $sql .= " ORDER BY o.{$sort} {$direction}";
        } else {
            $sql .= " ORDER BY o.order_date DESC";
        }

        // Limite
        $limit = (int)($filters['limit'] ?? 50);
        $sql .= " LIMIT {$limit}";

        return $this->findAll($sql, $params);
    }

    /**
     * Récupère les statistiques par statut
     * 
     * @return array Données brutes de la DB
     */
    public function getStatusStats(): array {
        $sql = "SELECT status, COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS revenue
                FROM orders 
                GROUP BY status
                ORDER BY count DESC";

        return $this->findAll($sql);
    }


    /**
     * Récupère une commande par ID avec toutes ses informations
     * 
     * @param int $id ID de la commande
     * @return array|null Commande avec infos utilisateur, ville, quartier
     */
    public function getOrderById(int $id): ?array {
        $sql = "SELECT o.id, o.users_id, o.status, o.previous_status, o.order_date, o.total_amount, o.delivery_address, o.delivery_comment, o.delivery_fee,
                       o.customer_first_name, o.customer_last_name, o.customer_phone, o.customer_email,
                       COALESCE(u.first_name, o.customer_first_name) AS first_name,
                       COALESCE(u.last_name, o.customer_last_name) AS last_name,
                       COALESCE(u.phone, o.customer_phone) AS phone,
                       COALESCE(u.email, o.customer_email) AS email,
                       c.name AS city_name, n.name AS neighborhood_name, dz.code AS zone_code,
                       COALESCE(lph.points_used, 0) AS loyalty_points_used
                FROM orders o
                LEFT JOIN users u ON u.id = o.users_id
                LEFT JOIN cities c ON c.id = o.cities_id
                LEFT JOIN neighborhoods n ON n.id = o.neighborhoods_id
                LEFT JOIN delivery_zones dz ON dz.id = o.delivery_zones_id
                LEFT JOIN (
                    SELECT orders_id, ABS(points) AS points_used 
                    FROM loyalty_points_history 
                    WHERE reason = 'redeem'
                ) lph ON lph.orders_id = o.id
                WHERE o.id = :id";

        $result = $this->findOne($sql, ['id' => $id]);
        return $result === false ? null : $result;
    }

    /**
     * Met à jour le statut d'une commande
     * 
     * @param int $id ID de la commande
     * @param string $status Nouveau statut
     * @param string|null $previousStatus Statut précédent (pour annulation)
     * @return bool Succès de la mise à jour
     */
    public function updateStatus(int $id, string $status, ?string $previousStatus = null): bool {
        if ($previousStatus !== null) {
            // Mise à jour avec sauvegarde du statut précédent
            $sql = "UPDATE orders SET status = :status, previous_status = :previous_status WHERE id = :id";
            return $this->execute($sql, ['id' => $id, 'status' => $status, 'previous_status' => $previousStatus]) !== false;
        } else {
            // Mise à jour simple ou effacement de previous_status
            $sql = "UPDATE orders SET status = :status, previous_status = NULL WHERE id = :id";
            return $this->execute($sql, ['id' => $id, 'status' => $status]) !== false;
        }
    }

    /**
     * Crée une nouvelle commande
     * 
     * @param array $orderData Données de la commande
     * @return int|false ID de la commande créée ou false en cas d'erreur
     */
    public function createOrder(array $orderData): int|false {
        $sql = "INSERT INTO orders (users_id, customer_first_name, customer_last_name, customer_phone, customer_email, status, order_date, total_amount, delivery_fee, 
                delivery_address, delivery_comment, cities_id, neighborhoods_id, delivery_zones_id) 
                VALUES (:users_id, :customer_first_name, :customer_last_name, :customer_phone, :customer_email, :status, :order_date, :total_amount, :delivery_fee, 
                :delivery_address, :delivery_comment, :cities_id, :neighborhoods_id, :delivery_zones_id)";
        
        return $this->execute($sql, $orderData);
    }

    /**
     * Récupère une commande active par utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array|null Commande active ou null
     */
    public function getActiveOrderByUserId(int $userId): ?array {
        $sql = "SELECT o.id, o.status, o.order_date, o.total_amount, o.delivery_fee,
                       o.customer_first_name, o.customer_last_name, o.customer_phone, o.customer_email,
                       o.delivery_address, o.delivery_comment,
                       c.name AS city_name, n.name AS neighborhood_name
                FROM orders o
                LEFT JOIN cities c ON c.id = o.cities_id
                LEFT JOIN neighborhoods n ON n.id = o.neighborhoods_id
                WHERE o.users_id = :user_id 
                AND o.status NOT IN ('Livrée', 'annulée')
                ORDER BY o.order_date DESC
                LIMIT 1";

        $result = $this->findOne($sql, ['user_id' => $userId]);
        return $result === false ? null : $result;
    }

    /**
     * Récupère l'historique des commandes d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Commandes passées
     */
    public function getPastOrdersByUserId(int $userId): array {
        $sql = "SELECT o.id, o.status, o.order_date, o.total_amount, o.delivery_fee,
                       o.customer_first_name, o.customer_last_name, o.customer_phone, o.customer_email,
                       o.delivery_address, o.delivery_comment,
                       c.name AS city_name, n.name AS neighborhood_name
                FROM orders o
                LEFT JOIN cities c ON c.id = o.cities_id
                LEFT JOIN neighborhoods n ON n.id = o.neighborhoods_id
                WHERE o.users_id = :user_id 
                AND o.status IN ('Livrée', 'annulée')
                ORDER BY o.order_date DESC";

        return $this->findAll($sql, ['user_id' => $userId]);
    }

}
