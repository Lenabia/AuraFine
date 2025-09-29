<?php
namespace app\Models;

/**
 * Modèle Panier: accès DB (PDO préparé) pour la persistance du panier
 * Uniquement SQL: addItem, updateItem, removeItem, getCartByUser
 */
class Panier extends Database {

    /**
     * Ajoute un article au panier (ou incrémente s'il existe)
     */
    public function addItem(int $userId, string $productType, int $productId, int $quantity): bool {
        if ($userId <= 0 || $productId <= 0 || $quantity <= 0) return false;
        try {
            // Upsert simple: tente update, sinon insert
            $sqlUpdate = "UPDATE cart_items SET quantity = quantity + :qty WHERE users_id = :uid AND product_type = :ptype AND product_id = :pid";
            $stmt = $this->getConnection()->prepare($sqlUpdate);
            $stmt->bindValue(':qty', $quantity, \PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':ptype', $productType);
            $stmt->bindValue(':pid', $productId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) return true;

            $sqlInsert = "INSERT INTO cart_items (users_id, product_type, product_id, quantity, created_at, updated_at) VALUES (:uid, :ptype, :pid, :qty, NOW(), NOW())";
            $stmt2 = $this->getConnection()->prepare($sqlInsert);
            $stmt2->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt2->bindValue(':ptype', $productType);
            $stmt2->bindValue(':pid', $productId, \PDO::PARAM_INT);
            $stmt2->bindValue(':qty', $quantity, \PDO::PARAM_INT);
            return $stmt2->execute();
        } catch (\PDOException $e) {
            error_log('Panier::addItem SQL error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour la quantité (remplace par la valeur)
     */
    public function updateItem(int $userId, string $productType, int $productId, int $quantity): bool {
        if ($userId <= 0 || $productId <= 0 || $quantity < 0) return false;
        if ($quantity === 0) return $this->removeItem($userId, $productType, $productId);
        try {
            $sql = "UPDATE cart_items SET quantity = :qty, updated_at = NOW() WHERE users_id = :uid AND product_type = :ptype AND product_id = :pid";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':qty', $quantity, \PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':ptype', $productType);
            $stmt->bindValue(':pid', $productId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() >= 0; // true même si même valeur
        } catch (\PDOException $e) {
            error_log('Panier::updateItem SQL error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un article du panier
     */
    public function removeItem(int $userId, string $productType, int $productId): bool {
        if ($userId <= 0 || $productId <= 0) return false;
        try {
            $sql = "DELETE FROM cart_items WHERE users_id = :uid AND product_type = :ptype AND product_id = :pid";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':ptype', $productType);
            $stmt->bindValue(':pid', $productId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log('Panier::removeItem SQL error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne les items du panier pour un user, avec infos produit de base
     * NB: enrichissement minimal (nom, prix) via LEFT JOIN selon type
     */
    public function getCartByUser(int $userId): array {
        if ($userId <= 0) return [];

        // Récupération brute des items
        try {
            $sql = "SELECT id, users_id, product_type, product_id, quantity FROM cart_items WHERE users_id = :uid";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log('Panier::getCartByUser SQL error: ' . $e->getMessage());
            return [];
        }

        // Enrichissement par type (minimal, 1 requête par type présent)
        $byType = [
            'menu' => [],
            'salad' => [],
            'drink' => [],
            'fruit' => []
        ];
        foreach ($items as $it) {
            $byType[$it['product_type']][] = (int)$it['product_id'];
        }

        $map = [];
        if (!empty($byType['menu'])) {
            $in = implode(',', array_fill(0, count($byType['menu']), '?'));
            $q = $this->getConnection()->prepare("SELECT id, name, price, image FROM menus WHERE id IN ($in)");
            $q->execute($byType['menu']);
            foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $r) { $map['menu'][$r['id']] = $r; }
        }
        if (!empty($byType['salad'])) {
            $in = implode(',', array_fill(0, count($byType['salad']), '?'));
            $q = $this->getConnection()->prepare("SELECT id, name, price, image FROM salads WHERE id IN ($in)");
            $q->execute($byType['salad']);
            foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $r) { $map['salad'][$r['id']] = $r; }
        }
        if (!empty($byType['drink'])) {
            $in = implode(',', array_fill(0, count($byType['drink']), '?'));
            $q = $this->getConnection()->prepare("SELECT id, name, price, image FROM drinks WHERE id IN ($in)");
            $q->execute($byType['drink']);
            foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $r) { $map['drink'][$r['id']] = $r; }
        }
        if (!empty($byType['fruit'])) {
            $in = implode(',', array_fill(0, count($byType['fruit']), '?'));
            // Aligné avec le schéma: table fruits_veggies (name, price, image)
            $q = $this->getConnection()->prepare("SELECT id, name, price, image FROM fruits_veggies WHERE id IN ($in)");
            $q->execute($byType['fruit']);
            foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $r) { $map['fruit'][$r['id']] = $r; }
        }

        // Fusion
        $output = [];
        foreach ($items as $it) {
            $prod = $map[$it['product_type']][$it['product_id']] ?? null;
            if (!$prod) continue;
            $output[] = [
                'product_type' => $it['product_type'],
                'product_id' => (int)$it['product_id'],
                'name' => $prod['name'] ?? '',
                'price' => (float)($prod['price'] ?? 0),
                'image' => $prod['image'] ?? null,
                'quantity' => (int)$it['quantity'],
                'subtotal' => (float)($prod['price'] ?? 0) * (int)$it['quantity']
            ];
        }
        return $output;
    }
}


