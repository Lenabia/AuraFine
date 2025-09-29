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
                    COALESCE(SUM(total_amount), 0) AS total_revenue,
                    COUNT(*) AS total_orders,
                    COALESCE(AVG(total_amount), 0) AS avg_basket
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
        $sql = "SELECT o.id, o.status, o.order_date, o.total_amount, o.delivery_fee,
                       u.first_name, u.last_name, u.phone,
                       c.name AS city_name, n.name AS neighborhood_name
                FROM orders o
                LEFT JOIN users u ON u.id = o.users_id
                LEFT JOIN cities c ON c.id = o.cities_id
                LEFT JOIN neighborhoods n ON n.id = o.neighborhoods_id
                WHERE 1=1";

        $params = [];

        // Filtre par recherche (nom/prénom)
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

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
        $sql = "SELECT o.id, o.status, o.order_date, o.total_amount, o.delivery_address, o.delivery_fee,
                       u.first_name, u.last_name, u.email, u.phone,
                       c.name AS city_name, n.name AS neighborhood_name, dz.code AS zone_code
                FROM orders o
                LEFT JOIN users u ON u.id = o.users_id
                LEFT JOIN cities c ON c.id = o.cities_id
                LEFT JOIN neighborhoods n ON n.id = o.neighborhoods_id
                LEFT JOIN delivery_zones dz ON dz.id = o.delivery_zones_id
                WHERE o.id = :id";

        $result = $this->findOne($sql, ['id' => $id]);
        return $result === false ? null : $result;
    }

    /**
     * Met à jour le statut d'une commande
     * 
     * @param int $id ID de la commande
     * @param string $status Nouveau statut
     * @return bool Succès de la mise à jour
     */
    public function updateStatus(int $id, string $status): bool {
        $sql = "UPDATE orders SET status = :status WHERE id = :id";
        return $this->execute($sql, ['id' => $id, 'status' => $status]) !== false;
    }

}
