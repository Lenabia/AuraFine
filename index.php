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