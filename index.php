<?php

// Charger la configuration AVANT de démarrer la session
require('app/config/config.php');

// Démarrer la session APRÈS la configuration
session_start();

//gérer les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Africa/Dakar');

//chargement des class
spl_autoload_register(function($class) {
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

if(array_key_exists('action', $_GET)):
  switch($_GET['action']) {

    //page d'accueil
    case 'home':
    $controller = new \app\controllers\UsersController();
    $controller->displayHome();
    break;

    //page des salades (à adapter plus tard)
    // case 'salad-page':
    // $controller = new \app\controllers\UsersController();
    // $controller->displaySaladPage();
    // break;

    //page des boissons (à adapter plus tard)
    // case 'boissons':
    // $controller = new \app\controllers\UsersController();
    // $controller->displayBoissonsPage();
    // break;

    //page des fruits et légumes (à adapter plus tard)
    // case 'fruits-legumes':
    // $controller = new \app\controllers\UsersController();
    // $controller->displayFruitsLegumesPage();
    // break;

    //page du panier (à adapter plus tard)
    // case 'panier':
    // $controller = new \app\controllers\UsersController();
    // $controller->displayPanierPage();
    // break;

    //page de connexion
    case 'login':
    $controller = new \app\controllers\UsersController();
    
    // Si c'est une soumission POST, traiter la connexion
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->login();
    } else {
        // Sinon afficher le formulaire
        $controller->showLogin();
    }
    break;

    //page d'inscription
    case 'register':
    $controller = new \app\controllers\UsersController();
    
    // Si c'est une soumission POST, traiter l'inscription
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->register();
    } else {
        // Sinon afficher le formulaire
        $controller->showRegister();
    }
    break;

    //déconnexion
    case 'logout':
    $controller = new \app\controllers\UsersController();
    $controller->logout();
    break;

//si pas de route on redirige vers l'accueil
    default:
    header('Location: index.php?action=home');
    exit;
    break;

  }

//si absence de clé action par défaut 
else:
  header('Location: index.php?action=home');
  exit;
endif;