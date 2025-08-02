<?php
namespace app\middleware;

class Middleware {

//Methode pour créer la session
  public function createdSession($data) {
        $_SESSION['connected'] = true;
        $_SESSION['user'] = $data;
        unset($_SESSION['user']['password']);//supprime le mdp de la session par securité
    }

    //Methode render pour afficher le header et footer 

    public function render($template, $layout, $data = []) {
        // Extraire les données pour les rendre disponibles dans la vue
        if (!empty($data)) {
            extract($data);
        }
        include_once "app/views/$layout";
    }
       
    

    // Méthode de redirection
    public function redirectTo($redirect) {
        header("Location: index.php?action=$redirect");
        exit;
    }
    
    // Vérifie que l'utilisateur est connecté
    protected function isAuthenticated() {
        if (!isset($_SESSION['connected']) || $_SESSION['connected'] === false) {
            $this->redirectTo('accessDenied');//Redirige vers une page de connexion, 
        }
    }

//Methode pour le token eviter la faille cscrf
     protected function checkCSRFToken() {
        if (!isset($_SESSION['token']) || $_POST['token'] !== $_SESSION['token']) {
            return false;
        } else {
            return true;
        }
    }
}