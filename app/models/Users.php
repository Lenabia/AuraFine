<?php
namespace app\Models;

class Users extends Database {
    // Propriétés correspondant exactement à la table users
    private $id;
    private $first_name;
    private $last_name;
    private $email;
    private $phone;
    private $password_hash;
    private $address_line;
    private $cities_id;
    private $delivery_zones_id;
    private $neighborhoods_id;
    private $loyalty_points;
    private $referral_code;
    private $referred_by_users_id;
    private $is_active;
    private $created_at;
    private $updated_at;

    // Hérite de Database: $this->bdd, findAll, findOne, execute, getConnection

    /**
     * Créer un nouvel utilisateur
     */
    public function create($data) {
        try {
            // Hashage sécurisé du mot de passe
            $data['password_hash'] = $this->hashPassword($data['password']);
            unset($data['password']); // Supprimer le mot de passe en clair

            // Génération automatique du code de parrainage
            if (empty($data['referral_code'])) {
                $data['referral_code'] = $this->generateReferralCode();
            }

            // Valeurs par défaut
            $data['loyalty_points'] = 0;
            $data['is_active'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Construction de la requête SQL
            $fields = array_keys($data);
            $placeholders = ':' . implode(', :', $fields);
            $fieldList = '`' . implode('`, `', $fields) . '`';

            $sql = "INSERT INTO users ($fieldList) VALUES ($placeholders)";
            
            $stmt = $this->getConnection()->prepare($sql);
            
            // Exécution avec les données
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->id = $this->getConnection()->lastInsertId();
                return $this->id;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail($email) {
        try {
            $sql = "SELECT * FROM users WHERE email = :email AND is_active = 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur recherche utilisateur par email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier les identifiants de connexion
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && $this->verifyPassword($password, $user['password_hash'])) {
            return $user;
        }
        
        return false;
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update($id, $data) {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $fields = array_keys($data);
            $setClause = '';
            
            foreach ($fields as $field) {
                $setClause .= "`$field` = :$field, ";
            }
            $setClause = rtrim($setClause, ', ');
            
            $sql = "UPDATE users SET $setClause WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($sql);
            
            // Binding des paramètres
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':id', $id);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un email existe déjà
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $params = [':email' => $email];
            
            if ($excludeId) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur vérification email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un code de parrainage existe
     */
    public function referralCodeExists($referralCode, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE referral_code = :referral_code";
            $params = [':referral_code' => $referralCode];
            
            if ($excludeId) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur vérification code parrainage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les données géographiques pour les formulaires
     */
    public function getCities() {
        try {
            $sql = "SELECT id, name FROM cities ORDER BY name";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur récupération villes: " . $e->getMessage());
            return [];
        }
    }

    public function getDeliveryZones($cityId = null) {
        try {
            $sql = "SELECT id, cities_id, code, fee FROM delivery_zones WHERE is_active = 1";
            $params = [];
            
            if ($cityId) {
                $sql .= " AND cities_id = :city_id";
                $params[':city_id'] = $cityId;
            }
            
            $sql .= " ORDER BY code";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur récupération zones livraison: " . $e->getMessage());
            return [];
        }
    }

    public function getNeighborhoods($cityId = null, $zoneId = null) {
        try {
            $sql = "SELECT id, cities_id, delivery_zones_id, name FROM neighborhoods";
            $params = [];
            $conditions = [];
            
            if ($cityId) {
                $conditions[] = "cities_id = :city_id";
                $params[':city_id'] = $cityId;
            }
            
            if ($zoneId) {
                $conditions[] = "delivery_zones_id = :zone_id";
                $params[':zone_id'] = $zoneId;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur récupération quartiers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Méthodes utilitaires
     */
    private function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    private function generateReferralCode($length = 8) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Vérifier que le code n'existe pas déjà
        while ($this->referralCodeExists($code)) {
            $code = $this->generateReferralCode($length);
        }
        
        return $code;
    }

    /**
     * Getters pour les propriétés
     */
    public function getId() { return $this->id; }
    public function getFirstName() { return $this->first_name; }
    public function getLastName() { return $this->last_name; }
    public function getEmail() { return $this->email; }
    public function getPhone() { return $this->phone; }
    public function getAddressLine() { return $this->address_line; }
    public function getCitiesId() { return $this->cities_id; }
    public function getDeliveryZonesId() { return $this->delivery_zones_id; }
    public function getNeighborhoodsId() { return $this->neighborhoods_id; }
    public function getLoyaltyPoints() { return $this->loyalty_points; }
    public function getReferralCode() { return $this->referral_code; }
    public function getReferredByUsersId() { return $this->referred_by_users_id; }
    public function getIsActive() { return $this->is_active; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }
}
