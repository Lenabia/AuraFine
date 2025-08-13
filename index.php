<?php

session_start(); 
//gérer les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Africa/Dakar');
require('app/config/config.php');

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

    //page des salades
    case 'salad-page':
    $controller = new \app\controllers\UsersController();
    $controller->displaySaladPage();
    break;

    //page des boissons
    case 'boissons':
    $controller = new \app\controllers\UsersController();
    $controller->displayBoissonsPage();
    break;

    //page des fruits et légumes
    case 'fruits-legumes':
    $controller = new \app\controllers\UsersController();
    $controller->displayFruitsLegumesPage();
    break;

    //page du panier
    case 'panier':
    $controller = new \app\controllers\UsersController();
    $controller->displayPanierPage();
    break;

    //page de connexion
    case 'login':
    $controller = new \app\controllers\UsersController();
    $controller->showLogin();
    break;

    //traitement de la connexion
    case 'login-post':
    $controller = new \app\controllers\UsersController();
    $controller->login();
    break;

    //page d'inscription
    case 'register':
    $controller = new \app\controllers\UsersController();
    $controller->showRegister();
    break;

    //traitement de l'inscription
    case 'register-post':
    $controller = new \app\controllers\UsersController();
    $controller->register();
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