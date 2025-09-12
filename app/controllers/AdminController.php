<?php
namespace app\controllers;

use app\Models\User;
use app\middleware\Middleware;



class AdminController extends Middleware {

    public function displayAdminHome() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: index.php?action=login');
            exit;
        }
        
        // Variables pour la page (à remplacer par des données BDD)
        $pageTitle = 'Dashboard';
        
        // Métriques principales (exemples - à remplacer par des requêtes BDD)
        $revenue = 125750; // Chiffre d'affaires en FCFA
        $revenue_trend = 'positive'; // positive ou negative
        $revenue_percentage = '+12%';
        $revenue_period = 'Aujourd\'hui';
        
        $most_popular_dish = 'Salade César';
        $popular_dish_orders = 23;
        
        $pending_orders = 8;
        
        // Activités récentes (exemples - à remplacer par des requêtes BDD)
        $recent_activities = null; // Si null, affiche les activités par défaut
        
        // Variables pour les activités par défaut
        $last_order_id = '1234';
        $last_order_items = 'Salade César + Jus d\'orange';
        $last_order_amount = 2500;
        $last_order_time = 'Il y a 5 min';
        
        $last_user_name = 'Fatou Diop';
        $last_user_zone = 'Zone 1';
        $last_user_time = 'Il y a 12 min';
        
        $delivered_order_id = '1233';
        $delivered_order_items = 'Salade Grecque + Smoothie';
        $delivered_order_amount = 3200;
        $delivered_order_time = 'Il y a 25 min';
        
        $new_product_name = 'Salade Thaï';
        $new_product_price = 2800;
        $new_product_time = 'Il y a 1h';
        
        $cancelled_order_id = '1232';
        $cancelled_order_items = 'Salade Niçoise';
        $cancelled_order_amount = 2300;
        $cancelled_order_time = 'Il y a 2h';
        
        $connected_user_name = 'Moussa Fall';
        $connected_user_status = 'Client fidèle';
        $connected_user_time = 'Il y a 3h';
        
        $this->render("admin-home.phtml", "admin-layout.phtml", [
            'pageTitle' => $pageTitle,
            'currentPage' => 'admin-home',
            'breadcrumbs' => [
                [
                    'title' => 'Dashboard',
                    'url' => 'index.php?action=admin-home',
                    'icon' => 'fas fa-tachometer-alt'
                ]
            ],
            'revenue' => $revenue,
            'revenue_trend' => $revenue_trend,
            'revenue_percentage' => $revenue_percentage,
            'revenue_period' => $revenue_period,
            'most_popular_dish' => $most_popular_dish,
            'popular_dish_orders' => $popular_dish_orders,
            'pending_orders' => $pending_orders,
            'recent_activities' => $recent_activities,
            'last_order_id' => $last_order_id,
            'last_order_items' => $last_order_items,
            'last_order_amount' => $last_order_amount,
            'last_order_time' => $last_order_time,
            'last_user_name' => $last_user_name,
            'last_user_zone' => $last_user_zone,
            'last_user_time' => $last_user_time,
            'delivered_order_id' => $delivered_order_id,
            'delivered_order_items' => $delivered_order_items,
            'delivered_order_amount' => $delivered_order_amount,
            'delivered_order_time' => $delivered_order_time,
            'new_product_name' => $new_product_name,
            'new_product_price' => $new_product_price,
            'new_product_time' => $new_product_time,
            'cancelled_order_id' => $cancelled_order_id,
            'cancelled_order_items' => $cancelled_order_items,
            'cancelled_order_amount' => $cancelled_order_amount,
            'cancelled_order_time' => $cancelled_order_time,
            'connected_user_name' => $connected_user_name,
            'connected_user_status' => $connected_user_status,
            'connected_user_time' => $connected_user_time
        ]);
    }
    
    /**
     * Affiche la page catalogue
     * 
     * @route GET /admin/catalog
     * @security Vérification du rôle admin
     */
    public function displayCatalogue() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->redirectTo('accesDenied');
        }
        
        $this->render("catalogue.phtml", "admin-layout.phtml", [
            'pageTitle' => 'Catalogue',
            'currentPage' => 'admin-catalog',
            'breadcrumbs' => [
                [
                    'title' => 'Dashboard',
                    'url' => 'index.php?action=admin-home',
                    'icon' => 'fas fa-tachometer-alt'
                ],
                [
                    'title' => 'Catalogue',
                    'url' => 'index.php?action=admin-catalog',
                    'icon' => 'fas fa-book'
                ]
            ]
        ]);
    }
}