<?php
namespace app\Models;

/**
 * Modèle pour la gestion des menus
 * 
 * @security Toutes les requêtes utilisent des requêtes préparées PDO
 * @performance Optimisé avec des requêtes spécifiques et indexées
 * @error-handling Gestion centralisée des erreurs avec logs détaillés
 */
class Menus extends Database {
    
    /**
     * Récupère tous les menus (admin)
     * 
     * @return array Liste de tous les menus (disponibles + indisponibles)
     * @security Pour usage admin uniquement
     */
    public function getAll() {
        $sql = "SELECT m.id, m.name, m.description, m.price, m.image, m.is_available, 
                       m.categories_id, m.created_at, m.updated_at, c.name as category_name
                FROM menus m
                LEFT JOIN categories c ON m.categories_id = c.id
                ORDER BY m.created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère seulement les menus disponibles (public)
     * 
     * @return array Liste des menus disponibles
     * @security Filtre par is_available = 1 pour l'affichage public
     */
    public function getAvailable() {
        $sql = "SELECT m.id, m.name, m.description, m.price, m.image, m.is_available, 
                       m.categories_id, m.created_at, m.updated_at, c.name as category_name, c.id as category_id
                FROM menus m
                LEFT JOIN categories c ON m.categories_id = c.id
                WHERE m.is_available = 1 
                ORDER BY m.created_at DESC";
        
        return $this->findAll($sql);
    }
    
    /**
     * Récupère les menus par catégorie (public)
     * 
     * @param int $categoryId L'ID de la catégorie
     * @return array Liste des menus de la catégorie
     * @security Filtre par catégorie et disponibilité
     */
    public function getByCategory($categoryId) {
        // Validation de l'ID (doit être un entier positif)
        if (!is_numeric($categoryId) || $categoryId <= 0) {
            return [];
        }
        
        $sql = "SELECT m.id, m.name, m.description, m.price, m.image, m.is_available, 
                       m.created_at, m.updated_at, c.name as category_name, c.id as category_id
                FROM menus m
                LEFT JOIN categories c ON m.categories_id = c.id
                WHERE m.categories_id = :category_id AND m.is_available = 1 
                ORDER BY m.created_at DESC";
        
        return $this->findAll($sql, ['category_id' => (int)$categoryId]);
    }
    
    /**
     * Récupère un menu par son ID
     * 
     * @param int $id L'ID du menu
     * @return array|false Les données du menu ou false si non trouvé
     * @security Validation de l'ID pour éviter les injections
     */
    public function findById($id) {
        // Validation de l'ID (doit être un entier positif)
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        
        $sql = "SELECT m.id, m.name, m.description, m.price, m.image, m.is_available, 
                       m.categories_id, m.created_at, m.updated_at, c.name as category_name, c.id as category_id
                FROM menus m
                LEFT JOIN categories c ON m.categories_id = c.id
                WHERE m.id = :id";
        
        return $this->findOne($sql, ['id' => (int)$id]);
    }
    
    /**
     * Récupère toutes les catégories
     * 
     * @return array Liste des catégories
     * @security Requête simple sans paramètres
     */
    public function getCategories() {
        $sql = "SELECT id, name FROM categories ORDER BY name ASC";
        return $this->findAll($sql);
    }
    
    /**
     * Crée un nouveau menu
     * 
     * @param array $data Les données du menu (name, description, price, etc.)
     * @return int|false L'ID du menu créé ou false en cas d'erreur
     * @security Nettoyage des données d'entrée (validation faite dans le contrôleur)
     */
    public function create($data) {
        // Nettoyage des données (validation faite dans le contrôleur)
        $cleanData = [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'image' => trim($data['image'] ?? ''),
            'categories_id' => (int)($data['categories_id'] ?? 1),
            'is_available' => isset($data['is_available']) && $data['is_available'] == '1' ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO menus (name, description, price, image, categories_id, is_available, created_at, updated_at) 
                VALUES (:name, :description, :price, :image, :categories_id, :is_available, :created_at, :updated_at)";
        
        return $this->execute($sql, $cleanData);
    }
    
    /**
     * Met à jour un menu existant
     * 
     * @param int $id L'ID du menu à modifier
     * @param array $data Les nouvelles données
     * @return bool True si la mise à jour a réussi
     * @security Vérification de l'existence du menu avant modification
     */
    public function update($id, $data) {
        // Vérifier que le menu existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Menu non trouvé");
        }
        
        // Nettoyage des données (validation faite dans le contrôleur)
        $cleanData = [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'image' => trim($data['image'] ?? ''),
            'categories_id' => (int)($data['categories_id'] ?? 1),
            'is_available' => isset($data['is_available']) && $data['is_available'] == '1' ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => (int)$id
        ];
        
        $sql = "UPDATE menus 
                SET name = :name, description = :description, price = :price, 
                    image = :image, categories_id = :categories_id, 
                    is_available = :is_available, updated_at = :updated_at 
                WHERE id = :id";
        
        return $this->execute($sql, $cleanData) !== false;
    }
    
    /**
     * Supprime un menu (soft delete - désactive au lieu de supprimer)
     * 
     * @param int $id L'ID du menu à supprimer
     * @return bool True si la suppression a réussi
     * @security Soft delete pour préserver l'intégrité des commandes existantes
     */
    public function delete($id) {
        // Vérifier que le menu existe
        if (!$this->findById($id)) {
            throw new \InvalidArgumentException("Menu non trouvé");
        }
        
        // Soft delete : marquer comme non disponible au lieu de supprimer
        $sql = "UPDATE menus 
                SET is_available = 0, updated_at = :updated_at 
                WHERE id = :id";
        
        return $this->execute($sql, [
            'id' => (int)$id,
            'updated_at' => date('Y-m-d H:i:s')
        ]) !== false;
    }
    
    /**
     * Compte le nombre total de menus (admin)
     * 
     * @return int Le nombre total de menus
     * @performance Requête optimisée avec COUNT()
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM menus";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Compte le nombre de menus disponibles (public)
     * 
     * @return int Le nombre de menus disponibles
     * @performance Requête optimisée avec COUNT()
     */
    public function countAvailable() {
        $sql = "SELECT COUNT(*) as total FROM menus WHERE is_available = 1";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Compte le nombre de menus par catégorie
     * 
     * @param int $categoryId L'ID de la catégorie
     * @return int Le nombre de menus dans la catégorie
     * @performance Requête optimisée avec COUNT()
     */
    public function countByCategory($categoryId) {
        if (!is_numeric($categoryId) || $categoryId <= 0) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as total FROM menus WHERE categories_id = :category_id AND is_available = 1";
        $result = $this->findOne($sql, ['category_id' => (int)$categoryId]);
        return (int)($result['total'] ?? 0);
    }
}
