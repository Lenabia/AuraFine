<?php
namespace app\models;

/**
 * Modèle pour la gestion des réinitialisations de mot de passe
 * Gère les tokens de réinitialisation avec expiration
 */
class PasswordReset extends Database {
    
    /**
     * Crée un token de réinitialisation
     */
    public function createResetToken($email) {
        try {
            // Supprimer les anciens tokens pour cet email
            $this->deleteTokensByEmail($email);
            
            // Générer un token sécurisé
            $token = bin2hex(random_bytes(32));
            
            // Date d'expiration (15 minutes)
            $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60));
            
            $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':expires_at', $expiresAt);
            
            if ($stmt->execute()) {
                return $token;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur création token reset: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valide un token de réinitialisation
     */
    public function validateToken($token) {
        try {
            $sql = "SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur validation token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime un token après utilisation
     */
    public function deleteToken($token) {
        try {
            $sql = "DELETE FROM password_resets WHERE token = :token";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':token', $token);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur suppression token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime tous les tokens pour un email
     */
    public function deleteTokensByEmail($email) {
        try {
            $sql = "DELETE FROM password_resets WHERE email = :email";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':email', $email);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur suppression tokens par email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Nettoie les tokens expirés
     */
    public function cleanExpiredTokens() {
        try {
            $sql = "DELETE FROM password_resets WHERE expires_at < NOW()";
            $stmt = $this->getConnection()->prepare($sql);
            $result = $stmt->execute();
            
            $count = $stmt->rowCount();
            if ($count > 0) {
                error_log("Nettoyage: $count tokens expirés supprimés");
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Erreur nettoyage tokens: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie si un email a un token valide
     */
    public function hasValidToken($email) {
        try {
            $sql = "SELECT COUNT(*) FROM password_resets WHERE email = :email AND expires_at > NOW()";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur vérification token valide: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Méthode statique pour nettoyer les tokens expirés
     */
    public static function cleanupExpiredTokens() {
        $instance = new self();
        return $instance->cleanExpiredTokens();
    }
}


