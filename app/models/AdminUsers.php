<?php
namespace app\Models;

/**
 * Modèle AdminUsers: requêtes SQL pour la section admin Utilisateurs
 * (séparé du modèle public/base des utilisateurs)
 */
class AdminUsers extends Database {

    /**
     * KPIs: total utilisateurs, utilisateurs actifs (>=2 commandes), total affiliés
     */
    public function getMetrics(): array {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM users) AS total_users,
                    (SELECT COUNT(*) FROM (
                        SELECT o.users_id FROM orders o GROUP BY o.users_id HAVING COUNT(*) >= 2
                    ) t) AS active_users,
                    (SELECT COUNT(*) FROM users WHERE referred_by_users_id IS NOT NULL) AS total_affiliates";

        return $this->findOne($sql) ?: ['total_users' => 0, 'active_users' => 0, 'total_affiliates' => 0];
    }

    /**
     * Liste des utilisateurs avec agrégats et filtres/tri/pagination (max 20)
     */
    public function getUsers(array $filters = []): array {
        $search = trim($filters['search'] ?? '');
        $sort = $filters['sort'] ?? 'orders_count';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(20, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Whitelist des colonnes triables pour éviter toute injection
        $allowedSort = ['orders_count', 'affiliates_count', 'loyalty_points'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'orders_count';
        }
        $direction = $direction === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT 
                    u.id,
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    u.email,
                    u.phone,
                    COALESCE(u.loyalty_points, 0) AS loyalty_points,
                    COALESCE(aff.cnt, 0) AS affiliates_count,
                    COALESCE(ord.cnt, 0) AS orders_count
                FROM users u
                LEFT JOIN (
                    SELECT referred_by_users_id AS ref_id, COUNT(*) AS cnt
                    FROM users
                    WHERE referred_by_users_id IS NOT NULL
                    GROUP BY referred_by_users_id
                ) aff ON aff.ref_id = u.id
                LEFT JOIN (
                    SELECT users_id, COUNT(*) AS cnt
                    FROM orders
                    GROUP BY users_id
                ) ord ON ord.users_id = u.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q)";
            $params['q'] = "%{$search}%";
        }

        // Injection sûre des entiers (déjà castés et bornés)
        $sql .= " ORDER BY {$sort} {$direction} LIMIT {$limit} OFFSET {$offset}";

        return $this->findAll($sql, $params);
    }

    /**
     * Incrémente les points de fidélité d'un utilisateur (admin)
     * Retourne true si l'UPDATE a modifié au moins une ligne
     */
    public function addLoyaltyPoints(int $userId, int $points): bool {
        // Validation minimale côté modèle
        if ($userId <= 0 || $points <= 0) {
            return false;
        }

        $sql = "UPDATE users SET loyalty_points = COALESCE(loyalty_points, 0) + :points WHERE id = :id";
        try {
            // Préparer et exécuter pour pouvoir lire rowCount()
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':points', $points, \PDO::PARAM_INT);
            $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            // Succès si au moins une ligne a été mise à jour
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log('AdminUsers::addLoyaltyPoints error: ' . $e->getMessage());
            return false;
        }
    }
}


