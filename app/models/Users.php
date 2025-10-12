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

            // Génération automatique du code de parrainage (toujours généré côté serveur)
            // Ignorer toute valeur fournie depuis l'extérieur pour éviter les collisions/modifications
            $data['referral_code'] = $this->generateReferralCode();

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

            // Empêcher toute modification du code de parrainage par mise à jour
            if (array_key_exists('referral_code', $data)) {
                unset($data['referral_code']);
            }
            
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
     * Trouve un utilisateur par son code de parrainage
     * 
     * @param string $code Code de parrainage (ex: A1B2C3D4)
     * @return array|null Utilisateur ou null si introuvable
     */
    public function findByReferralCode(string $code): ?array {
        $normalized = strtolower(trim($code));
        if ($normalized === '') {
            return null;
        }
        try {
            $sql = "SELECT id, first_name, last_name, email, referral_code FROM users WHERE referral_code = :code LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':code', $normalized);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Surveillance des tentatives de parrainage
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $result = $row ? 'SUCCESS' : 'NOT_FOUND';
            error_log("Tentative parrainage: code={$normalized} result={$result} IP={$ip} User-Agent={$userAgent}");
            
            return $row ?: null;
        } catch (\PDOException $e) {
            error_log("Erreur findByReferralCode: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les filleuls d'un utilisateur
     * 
     * @param int $userId ID du parrain
     * @return array Liste des filleuls (first_name, email, created_at)
     */
    public function getReferralsByUserId(int $userId): array {
        if ($userId <= 0) return [];
        try {
            $sql = "SELECT first_name, email, created_at FROM users WHERE referred_by_users_id = :uid ORDER BY created_at DESC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("Erreur getReferralsByUserId: " . $e->getMessage());
            return [];
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

    private function generateReferralCode(): string {
        // 8 caractères hex (32 bits d'entropie), minuscules
        return bin2hex(random_bytes(4));
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

    /**
     * Retourne le solde de points de fidélité d'un utilisateur
     */
    public function getLoyaltyPointsByUserId(int $userId): int {
        $stmt = $this->getConnection()->prepare('SELECT COALESCE(loyalty_points,0) FROM users WHERE id = :id');
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Déduit des points (avec verrouillage pessimiste) et logue l'opération (reason=redeem)
     */
    public function deductLoyaltyPoints(int $userId, int $amount, int $orderId, ?string $idempotencyKey = null): bool {
        if ($userId <= 0 || $amount <= 0) return false;
        $pdo = $this->getConnection();
        // NB: la transaction englobante est gérée par l'appelant (contrôleur)
        try {
            // Verrouiller la ligne utilisateur
            $q = $pdo->prepare('SELECT loyalty_points FROM users WHERE id = :id FOR UPDATE');
            $q->bindValue(':id', $userId, \PDO::PARAM_INT);
            $q->execute();
            $current = (int)$q->fetchColumn();
            if ($current < $amount) {
                return false;
            }
            // Déduction
            $u = $pdo->prepare('UPDATE users SET loyalty_points = loyalty_points - :amt WHERE id = :id');
            $u->bindValue(':amt', $amount, \PDO::PARAM_INT);
            $u->bindValue(':id', $userId, \PDO::PARAM_INT);
            $u->execute();
            // Log historique (reason=redeem)
            $h = $pdo->prepare('INSERT INTO loyalty_points_history (users_id, orders_id, points, reason, idempotency_key, note, created_at) VALUES (:uid, :oid, :pts, "redeem", :ikey, :note, NOW())');
            $h->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $h->bindValue(':oid', $orderId, \PDO::PARAM_INT);
            $h->bindValue(':pts', -$amount, \PDO::PARAM_INT);
            $h->bindValue(':ikey', $idempotencyKey);
            $h->bindValue(':note', 'Utilisation de points sur commande #' . $orderId);
            $h->execute();
            return true;
        } catch (\PDOException $e) {
            error_log('Users::deductLoyaltyPoints error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute des points (refund/adjust) et logue l'opération
     */
    public function addLoyaltyPoints(int $userId, int $amount, int $orderId, string $reason = 'adjust', string $note = ''): bool {
        if ($userId <= 0 || $amount <= 0) return false;
        $pdo = $this->getConnection();
        try {
            // L'appelant doit gérer la transaction si besoin
            $u = $pdo->prepare('UPDATE users SET loyalty_points = COALESCE(loyalty_points,0) + :amt WHERE id = :id');
            $u->bindValue(':amt', $amount, \PDO::PARAM_INT);
            $u->bindValue(':id', $userId, \PDO::PARAM_INT);
            $u->execute();
            $h = $pdo->prepare('INSERT INTO loyalty_points_history (users_id, orders_id, points, reason, note, created_at) VALUES (:uid, :oid, :pts, :reason, :note, NOW())');
            $h->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $h->bindValue(':oid', $orderId, \PDO::PARAM_INT);
            $h->bindValue(':pts', $amount, \PDO::PARAM_INT);
            $h->bindValue(':reason', $reason);
            $h->bindValue(':note', $note);
            $h->execute();
            return true;
        } catch (\PDOException $e) {
            error_log('Users::addLoyaltyPoints error: ' . $e->getMessage());
            return false;
        }
    }
}
