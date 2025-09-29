<?php
namespace app\controllers;

use app\Models\Panier;
use app\Models\Delivery;
use app\Models\Users;
use app\middleware\Middleware;

class PanierController extends Middleware {
    private Panier $cartModel;
    private Delivery $deliveryModel;
    private Users $userModel;

    public function __construct() {
        $this->cartModel = new Panier();
        $this->deliveryModel = new Delivery();
        $this->userModel = new Users();
    }

    private function requireUserRole(): bool {
        return isset($_SESSION['user']) && (($_SESSION['user']['role'] ?? '') === 'user');
    }

    public function showCart(): void {
        if (!$this->requireUserRole()) {
            // Rendre la page avec un flag pour afficher la modale de connexion
            $this->render('panier.phtml', 'layout.phtml', [
                'loginRequired' => true,
                'cartItems' => [],
                'cartTotal' => 0,
                'csrf_token' => $this->generateCSRFToken()
            ]);
            return;
        }

        $userId = (int)$_SESSION['user']['id'];
        $items = $this->cartModel->getCartByUser($userId);
        $total = array_reduce($items, function($acc, $it){ return $acc + ($it['subtotal'] ?? 0); }, 0);
        $totalQuantity = array_reduce($items, function($acc, $it){ return $acc + ($it['quantity'] ?? 0); }, 0);

        // Calculer les frais de livraison
        $deliveryFee = 0;
        $grandTotal = $total;
        $userDeliveryInfo = null;
        
        if (isset($_SESSION['user']['delivery_zones_id']) && $_SESSION['user']['delivery_zones_id']) {
            $zone = $this->deliveryModel->findZoneById((int)$_SESSION['user']['delivery_zones_id']);
            if ($zone && $zone['is_active']) {
                $deliveryFee = (float)$zone['fee'];
                $grandTotal = $total + $deliveryFee;
            }
        }

        // Récupérer les données géographiques pour les formulaires
        $cities = $this->userModel->getCities();
        $deliveryZones = [];
        $neighborhoods = [];
        
        if (isset($_SESSION['user']['cities_id'])) {
            $deliveryZones = $this->userModel->getDeliveryZones((int)$_SESSION['user']['cities_id']);
            $neighborhoods = $this->userModel->getNeighborhoods((int)$_SESSION['user']['cities_id']);
        }

        $this->render('panier.phtml', 'layout.phtml', [
            'cartItems' => $items,
            'cartTotal' => $total,
            'deliveryFee' => $deliveryFee,
            'grandTotal' => $grandTotal,
            'totalQuantity' => $totalQuantity,
            'cities' => $cities,
            'deliveryZones' => $deliveryZones,
            'neighborhoods' => $neighborhoods,
            'userDeliveryInfo' => [
                'address_line' => $_SESSION['user']['address_line'] ?? '',
                'cities_id' => $_SESSION['user']['cities_id'] ?? null,
                'delivery_zones_id' => $_SESSION['user']['delivery_zones_id'] ?? null,
                'neighborhoods_id' => $_SESSION['user']['neighborhoods_id'] ?? null,
                'delivery_comment' => $_SESSION['user']['delivery_comment'] ?? ''
            ],
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }

    public function addProduct(): void {
        // AJAX only
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error' => 'invalid_csrf']);
        }
        if (!$this->requireUserRole()) {
            $this->json(['success' => false, 'error' => 'login_required']);
        }
        $userId = (int)$_SESSION['user']['id'];
        $productId = (int)($_POST['product_id'] ?? 0);
        $productType = trim($_POST['product_type'] ?? '');
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $ok = $this->cartModel->addItem($userId, $productType, $productId, $quantity);
        if (!$ok) {
            $this->json(['success' => false, 'error' => 'failed']);
        }
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'count' => $stats['count'], 'total' => $stats['total']]);
    }

    public function updateQuantity(): void {
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error' => 'invalid_csrf']);
        }
        if (!$this->requireUserRole()) {
            $this->json(['success' => false, 'error' => 'login_required']);
        }
        $userId = (int)$_SESSION['user']['id'];
        $productId = (int)($_POST['product_id'] ?? 0);
        $productType = trim($_POST['product_type'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $ok = $this->cartModel->updateItem($userId, $productType, $productId, $quantity);
        if (!$ok) {
            $this->json(['success' => false, 'error' => 'failed']);
        }
        $items = $this->cartModel->getCartByUser($userId);
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'items' => $items, 'total' => $stats['total'], 'count' => $stats['count']]);
    }

    public function removeProduct(): void {
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error' => 'invalid_csrf']);
        }
        if (!$this->requireUserRole()) {
            $this->json(['success' => false, 'error' => 'login_required']);
        }
        $userId = (int)$_SESSION['user']['id'];
        $productId = (int)($_POST['product_id'] ?? 0);
        $productType = trim($_POST['product_type'] ?? '');
        $ok = $this->cartModel->removeItem($userId, $productType, $productId);
        if (!$ok) {
            $this->json(['success' => false, 'error' => 'failed']);
        }
        $items = $this->cartModel->getCartByUser($userId);
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'items' => $items, 'total' => $stats['total'], 'count' => $stats['count']]);
    }

    public function getCartCount(): void {
        // AJAX only
        if (!$this->requireUserRole()) {
            $this->json(['success' => false, 'error' => 'login_required']);
        }
        $userId = (int)$_SESSION['user']['id'];
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'count' => $stats['count']]);
    }

    public function getDeliveryZones(): void {
        // AJAX only
        if (!$this->requireUserRole()) {
            $this->json(['success' => false, 'error' => 'login_required']);
        }
        
        $cityId = (int)($_GET['city_id'] ?? 0);
        if ($cityId <= 0) {
            $this->json(['success' => false, 'error' => 'invalid_city']);
        }
        
        $zones = $this->userModel->getDeliveryZones($cityId);
        $this->json(['success' => true, 'zones' => $zones]);
    }

    public function getNeighborhoods(): void {
        // AJAX only
        if (!$this->requireUserRole()) {
            $this->json(['success' => false, 'error' => 'login_required']);
        }
        
        $cityId = (int)($_GET['city_id'] ?? 0);
        $zoneId = (int)($_GET['zone_id'] ?? 0);
        if ($cityId <= 0) {
            $this->json(['success' => false, 'error' => 'invalid_city']);
        }
        
        $neighborhoods = $this->userModel->getNeighborhoods($cityId);
        $this->json(['success' => true, 'neighborhoods' => $neighborhoods]);
    }

    public function setDeliveryChoice(): void {
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error' => 'invalid_csrf']);
        }
        if (!$this->requireUserRole()) {
            $this->json(['success' => false, 'error' => 'login_required']);
        }

        $userId = (int)$_SESSION['user']['id'];
        $citiesId = (int)($_POST['cities_id'] ?? 0);
        $deliveryZonesId = (int)($_POST['delivery_zones_id'] ?? 0);
        $neighborhoodsId = (int)($_POST['neighborhoods_id'] ?? 0);
        $addressLine = trim($_POST['address_line'] ?? '');
        $deliveryComment = trim($_POST['delivery_comment'] ?? '');

        // Validation
        if ($citiesId <= 0 || $deliveryZonesId <= 0) {
            $this->json(['success' => false, 'error' => 'invalid_data']);
        }

        // Vérifier que la zone appartient à la ville
        $zone = $this->deliveryModel->findZoneById($deliveryZonesId);
        if (!$zone || (int)$zone['cities_id'] !== $citiesId || !$zone['is_active']) {
            $this->json(['success' => false, 'error' => 'invalid_zone']);
        }

        // Mettre à jour l'utilisateur
        $updateData = [
            'cities_id' => $citiesId,
            'delivery_zones_id' => $deliveryZonesId,
            'address_line' => $addressLine,
            'delivery_comment' => $deliveryComment
        ];
        
        if ($neighborhoodsId > 0) {
            $neighborhood = $this->deliveryModel->findNeighborhoodById($neighborhoodsId);
            if ($neighborhood && (int)$neighborhood['cities_id'] === $citiesId) {
                $updateData['neighborhoods_id'] = $neighborhoodsId;
            }
        }

        $success = $this->userModel->update($userId, $updateData);
        if (!$success) {
            $this->json(['success' => false, 'error' => 'update_failed']);
        }

        // Mettre à jour la session
        $_SESSION['user']['cities_id'] = $citiesId;
        $_SESSION['user']['delivery_zones_id'] = $deliveryZonesId;
        $_SESSION['user']['neighborhoods_id'] = $neighborhoodsId;
        $_SESSION['user']['address_line'] = $addressLine;
        $_SESSION['user']['delivery_comment'] = $deliveryComment;

        // Calculer les nouveaux frais
        $deliveryFee = (float)$zone['fee'];
        $cartStats = $this->getCartStats($userId);
        $grandTotal = $cartStats['total'] + $deliveryFee;

        $this->json([
            'success' => true, 
            'deliveryFee' => $deliveryFee,
            'grandTotal' => $grandTotal,
            'cartTotal' => $cartStats['total']
        ]);
    }

    private function getCartStats(int $userId): array {
        $items = $this->cartModel->getCartByUser($userId);
        $count = 0;
        $total = 0;
        foreach ($items as $it) { 
            $count += (int)($it['quantity'] ?? 0);
            $total += (float)($it['subtotal'] ?? 0);
        }
        return ['count' => $count, 'total' => $total];
    }
}


