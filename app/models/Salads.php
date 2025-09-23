<?php
namespace app\Models;

/**
 * Modèle pour la gestion des salades
 * 
 * @security Toutes les requêtes utilisent des requêtes préparées PDO
 * @performance Optimisé avec des requêtes spécifiques et indexées
 * @error-handling Gestion centralisée des erreurs avec logs détaillés
 */
class Salads extends Database {
    
    /**
     * Récupère toutes les salades (admin)
     * 
     * @return array Liste de toutes les salades (disponibles + indisponibles)
     * @security Pour usage admin uniquement
     */
    public function getAll() {
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM salads 
                ORDER BY created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère seulement les salades disponibles (public)
     * 
     * @return array Liste des salades disponibles
     * @security Filtre par is_available = 1 pour l'affichage public
     */
    public function getAvailable() {
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM salads 
                WHERE is_available = 1 
                ORDER BY created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère une salade par son ID
     * 
     * @param int $id L'ID de la salade
     * @return array|false Les données de la salade ou false si non trouvée
     * @security Validation de l'ID pour éviter les injections
     */
    public function findById($id) {
        // Validation de l'ID (doit être un entier positif)
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM salads 
                WHERE id = :id";
        
        return $this->findOne($sql, ['id' => (int)$id]);
    }
    
    /**
     * Crée une nouvelle salade
     * 
     * @param array $data Les données de la salade (name, description, price, etc.)
     * @return int|false L'ID de la salade créée ou false en cas d'erreur
     * @security Nettoyage des données d'entrée (validation faite dans le contrôleur)
     */
    public function create($data) {
        // Nettoyage des données (validation faite dans le contrôleur)
        $cleanData = [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'image' => trim($data['image'] ?? ''),
            'stock_quantity' => (int)($data['stock_quantity'] ?? 0),
            'is_available' => isset($data['is_available']) && $data['is_available'] == '1' ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO salads (name, description, price, image, stock_quantity, is_available, created_at, updated_at) 
                VALUES (:name, :description, :price, :image, :stock_quantity, :is_available, :created_at, :updated_at)";
        
        return $this->execute($sql, $cleanData);
    }
    
    /**
     * Met à jour une salade existante
     * 
     * @param int $id L'ID de la salade à modifier
     * @param array $data Les nouvelles données
     * @return bool True si la mise à jour a réussi
     * @security Vérification de l'existence de la salade avant modification
     */
    public function update($id, $data) {
        // Vérifier que la salade existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Salade non trouvée");
        }
        
        // Nettoyage des données (validation faite dans le contrôleur)
        $cleanData = [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'image' => trim($data['image'] ?? ''),
            'stock_quantity' => (int)($data['stock_quantity'] ?? 0),
            'is_available' => isset($data['is_available']) && $data['is_available'] == '1' ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => (int)$id
        ];
        
        $sql = "UPDATE salads 
                SET name = :name, description = :description, price = :price, 
                    image = :image, stock_quantity = :stock_quantity, 
                    is_available = :is_available, updated_at = :updated_at 
                WHERE id = :id";
        
        return $this->execute($sql, $cleanData) !== false;
    }
    
    /**
     * Supprime définitivement une salade de la base de données
     * 
     * @param int $id L'ID de la salade à supprimer
     * @return bool True si la suppression a réussi
     * @security Suppression définitive - attention aux données liées
     */
    public function delete($id) {
        // Vérifier que la salade existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Salade non trouvée");
        }
        
        // Suppression définitive de la base de données
        $sql = "DELETE FROM salads WHERE id = :id";
        return $this->execute($sql, ['id' => (int)$id]) !== false;
    }
    
    /**
     * Bascule la disponibilité d'une salade (disponible ↔ indisponible)
     * 
     * @param int $id L'ID de la salade
     * @return bool True si la modification a réussi
     * @security Alternative au soft delete pour gérer la disponibilité
     */
    public function toggleAvailability($id) {
        // Vérifier que la salade existe
        $salad = $this->findById($id);
        if (!$salad) {
            throw new \InvalidArgumentException("Salade non trouvée");
        }
        
        // Bascule la disponibilité (1 ↔ 0)
        $newAvailability = $salad['is_available'] ? 0 : 1;
        
        $sql = "UPDATE salads 
                SET is_available = :is_available, updated_at = :updated_at 
                WHERE id = :id";
        
        return $this->execute($sql, [
            'id' => (int)$id,
            'is_available' => $newAvailability,
            'updated_at' => date('Y-m-d H:i:s')
        ]) !== false;
    }
    
    /**
     * Compte le nombre total de salades (admin)
     * 
     * @return int Le nombre total de salades
     * @performance Requête optimisée avec COUNT()
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM salads";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Compte le nombre de salades disponibles (public)
     * 
     * @return int Le nombre de salades disponibles
     * @performance Requête optimisée avec COUNT()
     */
    public function countAvailable() {
        $sql = "SELECT COUNT(*) as total FROM salads WHERE is_available = 1";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
}
