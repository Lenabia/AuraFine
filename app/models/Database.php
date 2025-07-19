<?php
namespace Models;

class Database {
  protected $bdd;

  public function __construct(){
    try{
            $this->bdd = new \PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
                 \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,    // retourne un tableau indexé par le nom de la colonne
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION           // lance PDOExeptions
            ]);
            
            $this->bdd->exec("SET CHARACTER SET utf8mb4");
                  
        } catch(\PDOException $e) {
            header( 'Location: index.php?action=error');              // mettre la page d'erreur 404
            exit;
           //die("Erreur de connexion : " . $e->getMessage());
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

  //ajouter les requêtes préparées génériques

  public function findALl(string $req, array $params = []){
    try{
      $query = $this->bdd->prepare($req);
      $query->execute($params);
      return $query->fetchAll();
    }catch(\PDOException $e){
      header('Location: index.php?action=error'); //la page 404
      exit;
    }
    
  }

  protected function findOne(string $req, array $params = []) {
        try {
            $query = $this->bdd->prepare($req);
            $query->execute($params);
            return $query->fetch();
        }catch(\PDOException $e) {
            header('Location: index.php?action=error');//la page 404 à creer 
            exit;
        }
    }


    protected function execute($sql, $data = []) {
        try {
            $query = $this->bdd->prepare($sql);
            $query->execute($data);
            return $this->bdd->lastInsertId();//native phph elle permet de récupérer l'identifiant de la dernière ligne insérée dans une table, généralement dans une colonne de type clé primaire auto-incrémentée.
            //return $query;//retourne l'objet pdoStatement
            
        }catch(\PDOException $e) {
            header('Location: index.php?action=error');//la page 404 à creer 
            exit;
        }
    }
  
}