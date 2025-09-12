<?php
namespace app\Models;

/**
 * Modèle pour la gestion des menus (stub - structure de base)
 * 
 * @security Toutes les requêtes utilisent des requêtes préparées PDO
 * @performance Optimisé avec des requêtes spécifiques et indexées
 * @error-handling Gestion centralisée des erreurs avec logs détaillés
 */
class Menu extends Database {
    
    /**
     * Récupère tous les menus actifs
     * 
     * @return array Liste des menus avec leurs informations complètes
     * @security Filtre par is_available = 1 pour ne pas exposer les produits désactivés
     */
    public function getAll() {
        $sql = "SELECT m.id, m.name, m.bundle_price, m.is_available, m.created_at, m.updated_at,
                       s.name as salad_name, d.name as drink_name, des.name as dessert_name
                FROM menus m
                LEFT JOIN salads s ON m.salads_id = s.id
                LEFT JOIN drinks d ON m.drinks_id = d.id
                LEFT JOIN desserts des ON m.desserts_id = des.id
                WHERE m.is_available = 1 
                ORDER BY m.created_at DESC";
        
        return $this->findALl($sql);
    }
    
    /**
     * Récupère un menu par son ID
     * 
     * @param int $id L'ID du menu
     * @return array|false Les données du menu ou false si non trouvé
     * @security Validation de l'ID pour éviter les injections
     */
    public function findById($id) {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        
        $sql = "SELECT m.id, m.name, m.bundle_price, m.is_available, m.created_at, m.updated_at,
                       s.name as salad_name, d.name as drink_name, des.name as dessert_name
                FROM menus m
                LEFT JOIN salads s ON m.salads_id = s.id
                LEFT JOIN drinks d ON m.drinks_id = d.id
                LEFT JOIN desserts des ON m.desserts_id = des.id
                WHERE m.id = :id";
        
        return $this->findOne($sql, ['id' => (int)$id]);
    }
    
    /**
     * Compte le nombre total de menus actifs
     * 
     * @return int Le nombre de menus actifs
     * @performance Requête optimisée avec COUNT()
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM menus WHERE is_available = 1";
        $result = $this->findOne($sql);
        return (int)($result['total'] ?? 0);
    }
}
