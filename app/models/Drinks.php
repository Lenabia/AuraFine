<?php
namespace app\Models;

/**
 * Modèle pour la gestion des boissons (stub - structure identique aux salades)
 * 
 * @security Toutes les requêtes utilisent des requêtes préparées PDO
 * @performance Optimisé avec des requêtes spécifiques et indexées
 * @error-handling Gestion centralisée des erreurs avec logs détaillés
 */
class Drinks extends Database {
    
    /**
     * Récupère toutes les boissons (admin)
     * 
     * @return array Liste de toutes les boissons (disponibles + indisponibles)
     * @security Pour usage admin uniquement
     */
    public function getAll() {
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM drinks 
                ORDER BY created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère seulement les boissons disponibles (public)
     * 
     * @return array Liste des boissons disponibles
     * @security Filtre par is_available = 1 pour l'affichage public
     */
    public function getAvailable() {
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM drinks 
                WHERE is_available = 1 
                ORDER BY created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère une boisson par son ID
     * 
     * @param int $id L'ID de la boisson
     * @return array|false Les données de la boisson ou false si non trouvée
     * @security Validation de l'ID pour éviter les injections
     */
    public function findById($id) {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM drinks 
                WHERE id = :id";
        
        return $this->findOne($sql, ['id' => (int)$id]);
    }
    
    /**
     * Compte le nombre total de boissons (admin)
     * 
     * @return int Le nombre total de boissons
     * @performance Requête optimisée avec COUNT()
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM drinks";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Compte le nombre de boissons disponibles (public)
     * 
     * @return int Le nombre de boissons disponibles
     * @performance Requête optimisée avec COUNT()
     */
    public function countAvailable() {
        $sql = "SELECT COUNT(*) as total FROM drinks WHERE is_available = 1";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Crée une nouvelle boisson
     *
     * @param array $data Les données de la boisson (name, description, price, etc.)
     * @return int|false L'ID de la boisson créée ou false en cas d'erreur
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
        
        $sql = "INSERT INTO drinks (name, description, price, image, stock_quantity, is_available, created_at, updated_at)
                VALUES (:name, :description, :price, :image, :stock_quantity, :is_available, :created_at, :updated_at)";
        
        return $this->execute($sql, $cleanData);
    }
    
    /**
     * Met à jour une boisson existante
     *
     * @param int $id L'ID de la boisson à modifier
     * @param array $data Les nouvelles données
     * @return bool True si la mise à jour a réussi
     * @security Vérification de l'existence de la boisson avant modification
     */
    public function update($id, $data) {
        // Vérifier que la boisson existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Boisson non trouvée");
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
        
        $sql = "UPDATE drinks
                SET name = :name, description = :description, price = :price,
                    image = :image, stock_quantity = :stock_quantity,
                    is_available = :is_available, updated_at = :updated_at
                WHERE id = :id";
        
        return $this->execute($sql, $cleanData) !== false;
    }
    
    /**
     * Supprime une boisson
     *
     * @param int $id L'ID de la boisson à supprimer
     * @return bool True si la suppression a réussi
     * @security Vérification de l'existence de la boisson avant suppression
     */
    public function delete($id) {
        // Vérifier que la boisson existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Boisson non trouvée");
        }
        
        $sql = "DELETE FROM drinks WHERE id = :id";
        return $this->execute($sql, ['id' => (int)$id]) !== false;
    }
}
