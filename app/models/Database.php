<?php
namespace app\Models;

class Database {
  /**
   * Connexion PDO partagée entre toutes les instances de modèles
   */
  protected static $sharedBdd = null;
  protected $bdd;

  public function __construct(){
    try{
            // Réutiliser la connexion partagée si elle existe
            if (self::$sharedBdd instanceof \PDO) {
                $this->bdd = self::$sharedBdd;
                return;
            }

            // Créer et mémoriser la connexion partagée
            self::$sharedBdd = new \PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
                 \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,    // retourne un tableau indexé par le nom de la colonne
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION           // lance PDOExeptions
            ]);
            self::$sharedBdd->exec("SET CHARACTER SET utf8mb4");

            $this->bdd = self::$sharedBdd;
        } catch(\PDOException $e) {
            // Log l'erreur de base de données
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            
            // Rediriger vers la page d'erreur 404
            http_response_code(500);
            header('Location: index.php?action=404');
            exit;
        }
  }

  // public function testConnection() {
  //       try {
  //           $this->bdd->query('SELECT 1');
  //           return "Connexion à la base de données réussie !";
  //       } catch(\PDOException $e) {
  //           return "Erreur de connexion : " . $e->getMessage();
  //       }
  //   }

  /**
   * Exécute une requête SELECT et retourne tous les résultats
   * 
   * @param string $req La requête SQL préparée
   * @param array $params Les paramètres de la requête
   * @return array|false Les résultats ou false en cas d'erreur
   * 
   * @security Utilise des requêtes préparées pour prévenir l'injection SQL
   * @error-handling Log les erreurs techniques et redirige l'utilisateur
   * @performance Optimisé avec PDO::FETCH_ASSOC pour la mémoire
   */
  public function findAll(string $req, array $params = []){
    try{
      // Préparer la requête pour éviter l'injection SQL
      $query = $this->bdd->prepare($req);
      
      // Exécuter avec les paramètres
      $query->execute($params);
      
      // Retourner les résultats en mode tableau associatif
      return $query->fetchAll(\PDO::FETCH_ASSOC);
      
    }catch(\PDOException $e){
      // LOG DE SÉCURITÉ - Enregistrer l'erreur pour les développeurs
      error_log("Database SELECT error: " . $e->getMessage() . 
                " | Query: " . $req . 
                " | Params: " . json_encode($params) . 
                " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
      
      // Redirection générique pour l'utilisateur (pas d'infos techniques)
      header('Location: index.php?action=error');
      exit;
    }
  }

      /**
     * Exécute une requête SELECT et retourne un seul résultat
     * 
     * @param string $req La requête SQL préparée
     * @param array $params Les paramètres de la requête
     * @return array|false Le résultat ou false en cas d'erreur
     * 
     * @security Requêtes préparées pour la sécurité
     * @performance PDO::FETCH_ASSOC pour optimiser la mémoire
     */
    protected function findOne(string $req, array $params = []) {
        try {
            $query = $this->bdd->prepare($req);
            $query->execute($params);
            return $query->fetch(\PDO::FETCH_ASSOC);
            
        } catch(\PDOException $e) {
            // Log détaillé pour le debugging
            error_log("Database SELECT ONE error: " . $e->getMessage() . 
                      " | Query: " . $req . 
                      " | Params: " . json_encode($params));
            
            header('Location: index.php?action=error');
            exit;
        }
    }


    /**
     * Exécute une requête INSERT/UPDATE/DELETE et retourne l'ID de la dernière insertion
     * 
     * @param string $sql La requête SQL à exécuter
     * @param array $data Les données à insérer/mettre à jour
     * @return int|false L'ID de la dernière insertion ou false en cas d'erreur
     * 
     * @security Requêtes préparées pour éviter l'injection SQL
     * @note Les transactions sont gérées par les services, pas ici
     */
    protected function execute($sql, $data = []) {
        try {
            $query = $this->bdd->prepare($sql);
            $query->execute($data);
            
            // Récupérer l'ID de la dernière insertion
            return $this->bdd->lastInsertId();
            
        } catch(\PDOException $e) {
            // Log détaillé pour le debugging
            error_log("Database EXECUTE error: " . $e->getMessage() . 
                      " | SQL: " . $sql . 
                      " | Data: " . json_encode($data));
            
            // Relancer l'exception pour que le service puisse gérer
            throw $e;
        }
    }
    
    /**
     * Vérifie la connexion à la base de données
     * 
     * @return bool True si la connexion est active
     * 
     * @health-check Méthode de diagnostic pour vérifier l'état de la DB
     */
    public function testConnection() {
        try {
            $this->bdd->query('SELECT 1');
            return true;
        } catch(\PDOException $e) {
            error_log("Database connection test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère la connexion PDO pour les opérations personnalisées
     * 
     * @return \PDO La connexion PDO
     * 
     * @security Cette méthode permet l'accès contrôlé à la connexion
     */
    public function getConnection() {
        return $this->bdd;
    }
    
    /**
     * Ferme proprement la connexion à la base de données
     * 
     * @security Fermeture propre pour éviter les fuites de connexions
     */
    public function __destruct() {
        // Ne pas fermer la connexion partagée ici pour éviter de casser d'autres modèles
        $this->bdd = null;
    }
}