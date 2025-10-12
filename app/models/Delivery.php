<?php
namespace app\Models;

/**
 * Modèle Delivery: accès DB pour cities, delivery_zones, neighborhoods
 * - Aucune logique métier ici (validation dans le contrôleur)
 * - PDO préparé via Database
 */
class Delivery extends Database {

    /* =========================
       CITIES
       ========================= */

    public function getAllCities(): array {
        $sql = "SELECT id, name FROM cities ORDER BY name ASC";
        return $this->findAll($sql);
    }

    public function findCityById(int $id) {
        $sql = "SELECT id, name FROM cities WHERE id = :id";
        return $this->findOne($sql, ['id' => $id]);
    }

    public function findCityByName(string $name) {
        $sql = "SELECT id, name FROM cities WHERE name = :name";
        return $this->findOne($sql, ['name' => trim($name)]);
    }

    public function createCity(string $name) {
        $sql = "INSERT INTO cities (name) VALUES (:name)";
        return $this->execute($sql, ['name' => trim($name)]);
    }

    public function updateCity(int $id, string $name): bool {
        $sql = "UPDATE cities SET name = :name WHERE id = :id";
        return $this->execute($sql, ['id' => $id, 'name' => trim($name)]) !== false;
    }

    public function deleteCity(int $id): bool {
        // Suppression (bloquée côté contrôleur si dépendances existent)
        $sql = "DELETE FROM cities WHERE id = :id";
        return $this->execute($sql, ['id' => $id]) !== false;
    }

    /* =========================
       DELIVERY ZONES
       ========================= */

    public function getZonesByCity(int $cityId): array {
        $sql = "SELECT id, cities_id, code, fee, is_active FROM delivery_zones WHERE cities_id = :city ORDER BY code ASC";
        return $this->findAll($sql, ['city' => $cityId]);
    }

    public function findZoneById(int $id) {
        $sql = "SELECT id, cities_id, code, fee, is_active FROM delivery_zones WHERE id = :id";
        return $this->findOne($sql, ['id' => $id]);
    }

    public function findZoneByCityAndCode(int $cityId, string $code) {
        $sql = "SELECT id FROM delivery_zones WHERE cities_id = :city AND code = :code";
        return $this->findOne($sql, ['city' => $cityId, 'code' => $code]);
    }

    public function createZone(int $cityId, string $code, float $fee, int $isActive) {
        $sql = "INSERT INTO delivery_zones (cities_id, code, fee, is_active) VALUES (:city, :code, :fee, :is_active)";
        return $this->execute($sql, [
            'city' => $cityId,
            'code' => $code,
            'fee' => $fee,
            'is_active' => $isActive
        ]);
    }

    public function updateZone(int $id, string $code, float $fee, int $isActive): bool {
        $sql = "UPDATE delivery_zones SET code = :code, fee = :fee, is_active = :is_active WHERE id = :id";
        return $this->execute($sql, [
            'id' => $id,
            'code' => $code,
            'fee' => $fee,
            'is_active' => $isActive
        ]) !== false;
    }

    public function deleteZone(int $id): bool {
        $sql = "DELETE FROM delivery_zones WHERE id = :id";
        return $this->execute($sql, ['id' => $id]) !== false;
    }

    /* =========================
       NEIGHBORHOODS
       ========================= */

    public function getNeighborhoodsByCity(int $cityId, bool $includeInactive = false): array {
        $sql = "SELECT n.id, n.cities_id, n.delivery_zones_id, n.name, n.is_active, n.deleted_at,
                       dz.code AS zone_code
                FROM neighborhoods n
                JOIN delivery_zones dz ON dz.id = n.delivery_zones_id
                WHERE n.cities_id = :city";
        
        if (!$includeInactive) {
            $sql .= " AND n.is_active = 1";
        }
        
        $sql .= " ORDER BY n.name ASC";
        return $this->findAll($sql, ['city' => $cityId]);
    }

    public function findNeighborhoodById(int $id) {
        $sql = "SELECT id, cities_id, delivery_zones_id, name, is_active, deleted_at FROM neighborhoods WHERE id = :id";
        return $this->findOne($sql, ['id' => $id]);
    }

    public function findNeighborhoodByCityAndName(int $cityId, string $name) {
        $sql = "SELECT id FROM neighborhoods WHERE cities_id = :city AND name = :name";
        return $this->findOne($sql, ['city' => $cityId, 'name' => trim($name)]);
    }

    public function createNeighborhood(int $cityId, int $zoneId, string $name) {
        $sql = "INSERT INTO neighborhoods (cities_id, delivery_zones_id, name) VALUES (:city, :zone, :name)";
        return $this->execute($sql, [
            'city' => $cityId,
            'zone' => $zoneId,
            'name' => trim($name)
        ]);
    }

    public function updateNeighborhood(int $id, int $zoneId, string $name): bool {
        $sql = "UPDATE neighborhoods SET delivery_zones_id = :zone, name = :name WHERE id = :id";
        return $this->execute($sql, [
            'id' => $id,
            'zone' => $zoneId,
            'name' => trim($name)
        ]) !== false;
    }

    public function deleteNeighborhood(int $id): bool {
        // Vérifier s'il y a des références actives
        $references = $this->hasActiveReferences($id);
        if (!empty($references)) {
            return false; // Impossible de supprimer
        }
        
        $sql = "DELETE FROM neighborhoods WHERE id = :id";
        return $this->execute($sql, ['id' => $id]) !== false;
    }

    /**
     * Désactive un quartier au lieu de le supprimer
     */
    public function deactivateNeighborhood(int $id): bool {
        $sql = "UPDATE neighborhoods SET is_active = 0, deleted_at = NOW() WHERE id = :id";
        return $this->execute($sql, ['id' => $id]) !== false;
    }

    /**
     * Réactive un quartier désactivé
     */
    public function reactivateNeighborhood(int $id): bool {
        $sql = "UPDATE neighborhoods SET is_active = 1, deleted_at = NULL WHERE id = :id";
        return $this->execute($sql, ['id' => $id]) !== false;
    }

    /**
     * Vérifie s'il y a des références actives vers ce quartier
     */
    public function hasActiveReferences(int $neighborhoodId): array {
        $references = [];
        
        // Vérifier les commandes
        $sql = "SELECT COUNT(*) as count FROM orders WHERE neighborhoods_id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['id' => $neighborhoodId]);
        $ordersCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        if ($ordersCount > 0) {
            $references['orders'] = (int)$ordersCount;
        }
        
        // Vérifier les utilisateurs
        $sql = "SELECT COUNT(*) as count FROM users WHERE neighborhoods_id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['id' => $neighborhoodId]);
        $usersCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        if ($usersCount > 0) {
            $references['users'] = (int)$usersCount;
        }
        
        return $references;
    }

    /* =========================
       COUNTS (optionnel pour stats)
       ========================= */
    public function countCities(): int {
        $row = $this->findOne("SELECT COUNT(*) AS total FROM cities");
        return (int)($row['total'] ?? 0);
    }
}



