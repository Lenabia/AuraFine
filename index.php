<?php

// Charger l'autoloader Composer AVANT tout
require_once 'vendor/autoload.php';

// Charger le fichier .env si il existe
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Charger la configuration AVANT de démarrer la session
require('app/config/config.php');

// Démarrer la session APRÈS la configuration
session_start();

// Gestion du mode public
if (PUBLIC_MODE) {
    $blockedRoutes = [
        'login', 'register', 'forgot-password', 'reset-password',
        'users-profile', 'users-profile-update', 'users-profile-delete',
        'referral', 'my-orders', 'loginWithGoogle', 'google-callback'
    ];
    
    if (isset($_GET['action']) && in_array($_GET['action'], $blockedRoutes)) {
        http_response_code(404);
        include 'app/views/error404.phtml';
        exit;
    }
}


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


$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestUri === '/google-callback') {
    $controller = new \app\controllers\UsersController();
    $controller->googleCallback();
    exit;
}

// Route de nettoyage des tokens expirés
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'tokens') {
    $cleaned = \app\models\PasswordReset::cleanupExpiredTokens();
    echo "Tokens nettoyés: " . ($cleaned ? "SUCCESS" : "FAILED");
    exit;
}

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

    //page de mot de passe oublié
    case 'forgot-password':
    $controller = new \app\controllers\UsersController();
    $controller->showForgotPassword();
    break;

    //page de réinitialisation de mot de passe
    case 'reset-password':
    $controller = new \app\controllers\UsersController();
    $controller->showResetPassword();
    break;

    // parrainage (profil)
    case 'referral':
    $controller = new \app\controllers\UsersController();
    $controller->referral();
    break;

    // profil utilisateur
    case 'users-profile':
    $controller = new \app\controllers\UsersController();
    $controller->showProfile();
    break;

    // mise à jour profil utilisateur
    case 'users-profile-update':
    $controller = new \app\controllers\UsersController();
    $controller->updateProfile();
    break;

    // suppression compte utilisateur
    case 'users-profile-delete':
    $controller = new \app\controllers\UsersController();
    $controller->deleteAccount();
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

    // admin utilisateurs
    case 'admin-users':
    $controller = new \app\controllers\AdminUsersController();
    $controller->listUsers();
    break;

    case 'admin-users-add-points':
    $controller = new \app\controllers\AdminUsersController();
    $controller->addLoyaltyPoints();
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

    // gestion des livraisons
    case 'admin-delivery': // alias vers la liste
    case 'admin-deliveries':
    $controller = new \app\controllers\DeliveryController();
    $controller->index();
    break;

    case 'admin-deliveries-create':
    $controller = new \app\controllers\DeliveryController();
    $controller->formCity();
    break;

    case 'admin-deliveries-edit':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\DeliveryController();
    $controller->formCity((int)$id);
    break;

    case 'admin-deliveries-store':
    $controller = new \app\controllers\DeliveryController();
    $controller->storeCity();
    break;

    case 'admin-deliveries-update':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\DeliveryController();
    $controller->updateCity((int)$id);
    break;

    case 'admin-deliveries-delete':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\DeliveryController();
    $controller->deleteCity((int)$id);
    break;

    case 'admin-deliveries-show':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\DeliveryController();
    $controller->showCity((int)$id);
    break;

    // zones
    case 'admin-delivery-zones-store':
    $city = (int)($_GET['city'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->storeZone($city);
    break;

    case 'admin-delivery-zones-update':
    $city = (int)($_GET['city'] ?? 0);
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->updateZone($city, $id);
    break;

    case 'admin-delivery-zones-delete':
    $city = (int)($_GET['city'] ?? 0);
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->deleteZone($city, $id);
    break;

    // neighborhoods
    case 'admin-neighborhoods-store':
    $city = (int)($_GET['city'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->storeNeighborhood($city);
    break;

    case 'admin-neighborhoods-update':
    $city = (int)($_GET['city'] ?? 0);
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->updateNeighborhood($city, $id);
    break;

    case 'admin-neighborhoods-delete':
    $city = (int)($_GET['city'] ?? 0);
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->deleteNeighborhood($city, $id);
    break;

    case 'admin-neighborhoods-deactivate':
    $city = (int)($_GET['city'] ?? 0);
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->deactivateNeighborhood($city, $id);
    break;

    case 'admin-neighborhoods-reactivate':
    $city = (int)($_GET['city'] ?? 0);
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\DeliveryController();
    $controller->reactivateNeighborhood($city, $id);
    break;

    //gestion des commandes
    case 'admin-orders':
    error_log('DEBUG: Route admin-orders détectée');
    $controller = new \app\controllers\OrdersController();
    $controller->listOrders();
    break;

    case 'admin-orders-details':
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\OrdersController();
    $controller->viewOrdersDetail($id);
    break;

    case 'admin-orders-update-status':
    $controller = new \app\controllers\OrdersController();
    $controller->updateOrderStatus();
    break;

    case 'admin-orders-cancel':
    $controller = new \app\controllers\OrdersController();
    $controller->cancelOrder();
    break;

    case 'admin-orders-reactivate':
    $controller = new \app\controllers\OrdersController();
    $controller->reactivateOrder();
    break;

    case 'admin-orders-ticket':
    $id = (int)($_GET['id'] ?? 0);
    $controller = new \app\controllers\OrdersController();
    $controller->printTicket($id);
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
    $controller = new \app\controllers\OrdersController();
    $controller->showUserOrders();
    break;

    //vérification statut commande (AJAX)
    case 'check-order-status':
    $controller = new \app\controllers\OrdersController();
    $controller->checkOrderStatus();
    break;

    //détails commande (AJAX)
    case 'order-details':
    $id = $_GET['id'] ?? null;
    $controller = new \app\controllers\OrdersController();
    $controller->getOrderDetails($id);
    break;

    //confirmation commande par client (AJAX)
    case 'confirm-order':
    $controller = new \app\controllers\OrdersController();
    $controller->confirmOrder();
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
    // panier (site public)
    case 'panier':
    $controller = new \app\controllers\PanierController();
    $controller->showCart();
    break;

    case 'panier-add':
    $controller = new \app\controllers\PanierController();
    $controller->addProduct();
    break;

    case 'panier-update':
    $controller = new \app\controllers\PanierController();
    $controller->updateQuantity();
    break;

    case 'panier-remove':
    $controller = new \app\controllers\PanierController();
    $controller->removeProduct();
    break;

    case 'panier-count':
    $controller = new \app\controllers\PanierController();
    $controller->getCartCount();
    break;

    case 'panier-set-delivery':
    $controller = new \app\controllers\PanierController();
    $controller->setDeliveryChoice();
    break;

    case 'precheck-loyalty':
    $controller = new \app\controllers\PanierController();
    $controller->precheckLoyalty();
    break;


    case 'register-neighborhoods':
    $controller = new \app\controllers\UsersController();
    $controller->getNeighborhoodsByCity();
    break;

    case 'loginWithGoogle':
    $controller = new \app\controllers\UsersController();
    $controller->loginWithGoogle();
    break;

    case 'google-callback':
    $controller = new \app\controllers\UsersController();
    $controller->googleCallback();
    break;

    case 'neighborhoods':
    $controller = new \app\controllers\PanierController();
    $controller->getNeighborhoods();
    break;

    case 'create-order':
    $controller = new \app\controllers\PanierController();
    $controller->createOrder();
    break;

    //accès refusé
    case 'accesDenied':
    include 'app/views/accesDenied.php';
    break;

    //page d'erreur 404
    case '404':
    http_response_code(404);
    include 'app/views/error404.phtml';
    break;

    //si pas de route on affiche la page 404
    default:
    // Log seulement en développement
    if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
        error_log('Action non reconnue: ' . ($action ?? 'NULL'));
    }
    http_response_code(404);
    include 'app/views/error404.phtml';
    exit;
    break;

  }

//si absence de clé action par défaut 
else:
  // Rediriger vers la page d'accueil par défaut
  $controller = new \app\controllers\UsersController();
  $controller->displayHome();
  exit;
endif;