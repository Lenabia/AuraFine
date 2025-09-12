<?php
namespace app\Models;

/**
 * Modèle pour la gestion des fruits et légumes (stub - structure identique aux salades)
 * 
 * @security Toutes les requêtes utilisent des requêtes préparées PDO
 * @performance Optimisé avec des requêtes spécifiques et indexées
 * @error-handling Gestion centralisée des erreurs avec logs détaillés
 */
class FruitsVeggies extends Database {
    
    /**
     * Récupère tous les fruits et légumes (admin)
     * 
     * @return array Liste de tous les fruits et légumes (disponibles + indisponibles)
     * @security Pour usage admin uniquement
     */
    public function getAll() {
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM fruits_veggies 
                ORDER BY created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère seulement les fruits et légumes disponibles (public)
     * 
     * @return array Liste des fruits et légumes disponibles
     * @security Filtre par is_available = 1 pour l'affichage public
     */
    public function getAvailable() {
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM fruits_veggies 
                WHERE is_available = 1 
                ORDER BY created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère un fruit/légume par son ID
     * 
     * @param int $id L'ID du fruit/légume
     * @return array|false Les données du fruit/légume ou false si non trouvé
     * @security Validation de l'ID pour éviter les injections
     */
    public function findById($id) {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        
        $sql = "SELECT id, name, description, price, image, stock_quantity, is_available, 
                       created_at, updated_at 
                FROM fruits_veggies 
                WHERE id = :id";
        
        return $this->findOne($sql, ['id' => (int)$id]);
    }
    
    /**
     * Compte le nombre total de fruits et légumes (admin)
     * 
     * @return int Le nombre total de fruits et légumes
     * @performance Requête optimisée avec COUNT()
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM fruits_veggies";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Compte le nombre de fruits et légumes disponibles (public)
     * 
     * @return int Le nombre de fruits et légumes disponibles
     * @performance Requête optimisée avec COUNT()
     */
    public function countAvailable() {
        $sql = "SELECT COUNT(*) as total FROM fruits_veggies WHERE is_available = 1";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Crée un nouveau fruit/légume
     *
     * @param array $data Les données du fruit/légume (name, description, price, etc.)
     * @return int|false L'ID du fruit/légume créé ou false en cas d'erreur
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
        
        $sql = "INSERT INTO fruits_veggies (name, description, price, image, stock_quantity, is_available, created_at, updated_at)
                VALUES (:name, :description, :price, :image, :stock_quantity, :is_available, :created_at, :updated_at)";
        
        return $this->execute($sql, $cleanData);
    }
    
    /**
     * Met à jour un fruit/légume existant
     *
     * @param int $id L'ID du fruit/légume à modifier
     * @param array $data Les nouvelles données
     * @return bool True si la mise à jour a réussi
     * @security Vérification de l'existence du fruit/légume avant modification
     */
    public function update($id, $data) {
        // Vérifier que le fruit/légume existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Fruit/légume non trouvé");
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
        
        $sql = "UPDATE fruits_veggies
                SET name = :name, description = :description, price = :price,
                    image = :image, stock_quantity = :stock_quantity,
                    is_available = :is_available, updated_at = :updated_at
                WHERE id = :id";
        
        return $this->execute($sql, $cleanData) !== false;
    }
    
    /**
     * Supprime un fruit/légume
     *
     * @param int $id L'ID du fruit/légume à supprimer
     * @return bool True si la suppression a réussi
     * @security Vérification de l'existence du fruit/légume avant suppression
     */
    public function delete($id) {
        // Vérifier que le fruit/légume existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Fruit/légume non trouvé");
        }
        
        $sql = "DELETE FROM fruits_veggies WHERE id = :id";
        return $this->execute($sql, ['id' => (int)$id]) !== false;
    }
}
