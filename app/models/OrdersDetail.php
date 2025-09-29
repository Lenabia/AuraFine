<?php
namespace app\Models;

/**
 * Modèle OrdersDetail: accès DB pour les détails des commandes
 * - Aucune logique métier ici (validation dans le contrôleur)
 * - PDO préparé via Database
 */
class OrdersDetail extends Database {

    /**
     * Récupère tous les détails d'une commande
     * 
     * @param int $orderId ID de la commande
     * @return array Liste des articles commandés
     */
    public function getDetailsByOrderId(int $orderId): array {
        $sql = "SELECT od.id, od.quantity, od.unit_price, od.item_label,
                       od.salads_id, od.drinks_id, od.desserts_id, od.fruits_veggies_id, od.menus_id, od.order_custom_salads_id,
                       (od.quantity * od.unit_price) AS subtotal
                FROM order_details od
                WHERE od.orders_id = :order_id
                ORDER BY od.id ASC";

        return $this->findAll($sql, ['order_id' => $orderId]);
    }

    /**
     * Récupère les détails d'une commande avec informations des produits
     * 
     * @param int $orderId ID de la commande
     * @return array Détails avec infos produits
     */
    public function getDetailsWithProducts(int $orderId): array {
        $sql = "SELECT od.id, od.quantity, od.unit_price, od.item_label,
                       od.salads_id, od.drinks_id, od.desserts_id, od.fruits_veggies_id, od.menus_id, od.order_custom_salads_id,
                       (od.quantity * od.unit_price) AS subtotal,
                       s.name AS salad_name, s.image AS salad_image,
                       d.name AS drink_name, d.image AS drink_image,
                       de.name AS dessert_name, de.image AS dessert_image,
                       fv.name AS fruit_veggie_name, fv.image AS fruit_veggie_image,
                       m.name AS menu_name, m.image AS menu_image,
                       ocs.name AS custom_salad_name, ocs.composition_json
                FROM order_details od
                LEFT JOIN salads s ON s.id = od.salads_id
                LEFT JOIN drinks d ON d.id = od.drinks_id
                LEFT JOIN desserts de ON de.id = od.desserts_id
                LEFT JOIN fruits_veggies fv ON fv.id = od.fruits_veggies_id
                LEFT JOIN menus m ON m.id = od.menus_id
                LEFT JOIN order_custom_salads ocs ON ocs.id = od.order_custom_salads_id
                WHERE od.orders_id = :order_id
                ORDER BY od.id ASC";

        return $this->findAll($sql, ['order_id' => $orderId]);
    }


    /**
     * Récupère les détails d'une commande personnalisée
     * 
     * @param int $customSaladId ID de la salade personnalisée
     * @return array|null Détails de la salade personnalisée
     */
    public function getCustomSaladDetails(int $customSaladId): ?array {
        $sql = "SELECT id, orders_id, name, unit_price, note, composition_json, created_at
                FROM order_custom_salads 
                WHERE id = :id";

        return $this->findOne($sql, ['id' => $customSaladId]);
    }

    /**
     * Récupère toutes les salades personnalisées d'une commande
     * 
     * @param int $orderId ID de la commande
     * @return array Salades personnalisées indexées par ID
     */
    public function getCustomSaladsForOrder(int $orderId): array {
        $sql = "SELECT ocs.id, ocs.orders_id, ocs.name, ocs.unit_price, ocs.note, ocs.composition_json
                FROM order_custom_salads ocs
                INNER JOIN order_details od ON od.order_custom_salads_id = ocs.id
                WHERE od.orders_id = :order_id";

        $customSalads = $this->findAll($sql, ['order_id' => $orderId]);
        
        $result = [];
        foreach ($customSalads as $salad) {
            $result[$salad['id']] = $salad;
        }
        
        return $result;
    }
}
