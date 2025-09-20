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
    $controller->showLogin();
    break;

    //page d'inscription
    case 'register':
    $controller = new \app\controllers\UsersController();
    $controller->showRegister();
    break;

    //déconnexion
    case 'logout':
    $controller = new \app\controllers\UsersController();
    $controller->logout();
    break;


    //accueil admin 
    case 'admin-home':
    $controller = new \app\controllers\AdminController();
    $controller->displayAdminHome();
    break;

    //catalogue admin
    case 'admin-catalog':
    $controller = new \app\controllers\AdminController();
    $controller->displayCatalogue();
    break;

    //gestion des salades
    case 'admin-salads':
    $controller = new \app\controllers\SaladsController();
    $controller->index();
    break;

    case 'admin-salads-create':
    $controller = new \app\controllers\SaladsController();
    $controller->form();
    break;

    case 'admin-salads-edit':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\SaladsController();
    $controller->form($id);
    break;

    case 'admin-salads-store':
    $controller = new \app\controllers\SaladsController();
    $controller->store();
    break;

    case 'admin-salads-update':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\SaladsController();
    $controller->update($id);
    break;

    case 'admin-salads-delete':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\SaladsController();
    $controller->delete($id);
    break;


    //gestion des boissons
    case 'admin-drinks':
    $controller = new \app\controllers\DrinksController();
    $controller->index();
    break;

    case 'admin-drinks-create':
    $controller = new \app\controllers\DrinksController();
    $controller->form();
    break;

    case 'admin-drinks-edit':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\DrinksController();
    $controller->form($id);
    break;

    case 'admin-drinks-store':
    $controller = new \app\controllers\DrinksController();
    $controller->store();
    break;

    case 'admin-drinks-update':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\DrinksController();
    $controller->update($id);
    break;

    case 'admin-drinks-delete':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\DrinksController();
    $controller->delete($id);
    break;

    //gestion des menus
    case 'admin-menus':
    $controller = new \app\controllers\MenusController();
    $controller->index();
    break;

    case 'admin-menus-create':
    $controller = new \app\controllers\MenusController();
    $controller->form();
    break;

    case 'admin-menus-edit':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\MenusController();
    $controller->form($id);
    break;

    case 'admin-menus-store':
    $controller = new \app\controllers\MenusController();
    $controller->store();
    break;

    case 'admin-menus-update':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\MenusController();
    $controller->update($id);
    break;

    case 'admin-menus-delete':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\MenusController();
    $controller->delete($id);
    break;

    //gestion des fruits et légumes
    case 'admin-fruits-legumes':
    $controller = new \app\controllers\FruitsVeggiesController();
    $controller->index();
    break;

    case 'admin-fruits-legumes-create':
    $controller = new \app\controllers\FruitsVeggiesController();
    $controller->form();
    break;

    case 'admin-fruits-legumes-edit':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\FruitsVeggiesController();
    $controller->form($id);
    break;

    case 'admin-fruits-legumes-store':
    $controller = new \app\controllers\FruitsVeggiesController();
    $controller->store();
    break;

    case 'admin-fruits-legumes-update':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\FruitsVeggiesController();
    $controller->update($id);
    break;

    case 'admin-fruits-legumes-delete':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\FruitsVeggiesController();
    $controller->delete($id);
    break;

    //mes commandes
    case 'my-orders':
    $controller = new \app\controllers\UsersController();
    $controller->showMyOrders();
    break;

    //pages publiques des produits
    case 'salads':
    $controller = new \app\controllers\SaladsController();
    $controller->publicIndex();
    break;

    case 'drinks':
    $controller = new \app\controllers\DrinksController();
    $controller->publicIndex();
    break;

    case 'fruits-veggies':
    $controller = new \app\controllers\FruitsVeggiesController();
    $controller->publicIndex();
    break;

    case 'menus':
    $controller = new \app\controllers\MenusController();
    $controller->publicIndex();
    break;

    //accès refusé
    case 'accesDenied':
    include 'app/views/accesDenied.php';
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