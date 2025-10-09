<?php
namespace app\controllers;

use app\Models\Panier;
use app\Models\Delivery;
use app\Models\Users;
use app\Models\Orders;
use app\Models\OrdersDetail;
use app\services\OrdersService;
use app\middleware\Middleware;

class PanierController extends Middleware {
    private Panier $cartModel;
    private Delivery $deliveryModel;
    private Users $userModel;
    private OrdersService $ordersService;

    public function __construct() {
        $this->cartModel = new Panier();
        $this->deliveryModel = new Delivery();
        $this->userModel = new Users();
        $this->ordersService = new OrdersService();
    }

    private function requireUserRole(): bool {
        return isset($_SESSION['user']) && (($_SESSION['user']['role'] ?? '') === 'user');
    }

    private function isGuest(): bool {
        return !$this->requireUserRole();
    }

    private function getGuestCart(): array {
        return $_SESSION['guest_cart'] ?? [];
    }

    private function setGuestCart(array $items): void {
        $_SESSION['guest_cart'] = $items;
    }

    private function computeTotals(array $items): array {
        $total = 0;
        $count = 0;
        foreach ($items as $it) {
            $qty = (int)($it['quantity'] ?? 0);
            $price = (float)($it['price'] ?? 0);
            $count += $qty;
            $total += $price * $qty;
        }
        return ['total' => $total, 'count' => $count];
    }

    private function fetchProduct(string $type, int $id): ?array {
        return $this->cartModel->getProductBasic($type, $id);
    }

    public function showCart(): void {
        $isGuest = $this->isGuest();
        if ($isGuest) {
            $items = $this->getGuestCart();
            $totals = $this->computeTotals($items);
            $total = $totals['total'];
            $totalQuantity = $totals['count'];
        } else {
        $userId = (int)$_SESSION['user']['id'];
        $items = $this->cartModel->getCartByUser($userId);
        $total = array_reduce($items, function($acc, $it){ return $acc + ($it['subtotal'] ?? 0); }, 0);
        $totalQuantity = array_reduce($items, function($acc, $it){ return $acc + ($it['quantity'] ?? 0); }, 0);
        }

        // Calculer les frais de livraison
        $deliveryFee = 0;
        $grandTotal = $total;
        
        // Initialiser les infos de livraison
        if ($isGuest) {
            $g = $_SESSION['guest_delivery'] ?? [];
            $userDeliveryInfo = [
                'address_line' => $g['address_line'] ?? '',
                'cities_id' => $g['cities_id'] ?? null,
                'delivery_zones_id' => $g['delivery_zones_id'] ?? null,
                'neighborhoods_id' => $g['neighborhoods_id'] ?? null,
                'first_name' => $g['first_name'] ?? '',
                'last_name' => $g['last_name'] ?? '',
                'phone' => $g['phone'] ?? ''
            ];
        } else {
            $userDeliveryInfo = [
                'address_line' => $_SESSION['user']['address_line'] ?? '',
                'cities_id' => $_SESSION['user']['cities_id'] ?? null,
                'delivery_zones_id' => $_SESSION['user']['delivery_zones_id'] ?? null,
                'neighborhoods_id' => $_SESSION['user']['neighborhoods_id'] ?? null
            ];
        }
        
        $sessionZoneId = $isGuest ? (int)($userDeliveryInfo['delivery_zones_id'] ?? 0) : (int)($_SESSION['user']['delivery_zones_id'] ?? 0);
        $sessionCityId = $isGuest ? (int)($userDeliveryInfo['cities_id'] ?? 0) : (int)($_SESSION['user']['cities_id'] ?? 0);
        if ($sessionZoneId) {
            $zone = $this->deliveryModel->findZoneById($sessionZoneId);
            // Vérifier que la zone existe, est active ET appartient à la ville de l'utilisateur
            if ($zone && $zone['is_active'] && (int)$zone['cities_id'] === $sessionCityId) {
                $deliveryFee = (float)$zone['fee'];
                $grandTotal = $total + $deliveryFee;
            }
        }

        // Récupérer les données géographiques pour les formulaires
        $cities = $this->userModel->getCities();
        $neighborhoods = [];
        
        $preCityId = $userDeliveryInfo['cities_id'] ?? null;
        if (!empty($preCityId)) {
            $neighborhoods = $this->deliveryModel->getNeighborhoodsByCity((int)$preCityId);
        }

        $this->render('panier.phtml', 'layout.phtml', [
            'cartItems' => $items,
            'cartTotal' => $total,
            'deliveryFee' => $deliveryFee,
            'grandTotal' => $grandTotal,
            'totalQuantity' => $totalQuantity,
            'cities' => $cities,
            'neighborhoods' => $neighborhoods,
            'userDeliveryInfo' => $userDeliveryInfo,
            'csrf_token' => $this->generateCSRFToken(),
            'isGuest' => $isGuest
        ]);
    }

    public function addProduct(): void {
        // AJAX only
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error_code' => 'INVALID_CSRF', 'message' => 'Erreur de sécurité, veuillez réessayer']);
        }
        $productId = (int)($_POST['product_id'] ?? 0);
        $productType = trim($_POST['product_type'] ?? '');
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if ($this->isGuest()) {
            $items = $this->getGuestCart();
            $found = false;
            foreach ($items as &$it) {
                if ($it['product_type'] === $productType && (int)$it['product_id'] === $productId) {
                    $it['quantity'] = (int)$it['quantity'] + $quantity;
                    $found = true;
                    break;
                }
            }
            unset($it);
            if (!$found) {
                $prod = $this->fetchProduct($productType, $productId);
                if (!$prod) {
                    $this->json(['success' => false, 'error_code' => 'OPERATION_FAILED', 'message' => 'Opération échouée']);
                }
                $items[] = [
                    'product_type' => $productType,
                    'product_id' => $productId,
                    'name' => $prod['name'],
                    'price' => $prod['price'],
                    'image' => $prod['image'],
                    'quantity' => $quantity,
                    'subtotal' => $prod['price'] * $quantity
                ];
            }
            // Recalcul des sous-totaux
            foreach ($items as &$it2) {
                $it2['subtotal'] = (float)($it2['price'] ?? 0) * (int)($it2['quantity'] ?? 0);
            }
            unset($it2);
            $this->setGuestCart($items);
            $stats = $this->computeTotals($items);
            $this->json(['success' => true, 'count' => $stats['count'], 'total' => $stats['total']]);
        } else {
            $userId = (int)$_SESSION['user']['id'];
        $ok = $this->cartModel->addItem($userId, $productType, $productId, $quantity);
        if (!$ok) {
                $this->json(['success' => false, 'error_code' => 'OPERATION_FAILED', 'message' => 'Opération échouée']);
        }
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'count' => $stats['count'], 'total' => $stats['total']]);
        }
    }

    public function updateQuantity(): void {
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error_code' => 'INVALID_CSRF', 'message' => 'Erreur de sécurité, veuillez réessayer']);
        }
        $productId = (int)($_POST['product_id'] ?? 0);
        $productType = trim($_POST['product_type'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        if ($this->isGuest()) {
            $items = $this->getGuestCart();
            foreach ($items as $idx => $it) {
                if ($it['product_type'] === $productType && (int)$it['product_id'] === $productId) {
                    if ($quantity <= 0) {
                        unset($items[$idx]);
                    } else {
                        $items[$idx]['quantity'] = $quantity;
                        $items[$idx]['subtotal'] = (float)($items[$idx]['price'] ?? 0) * $quantity;
                    }
                    break;
                }
            }
            // Réindexer
            $items = array_values($items);
            $this->setGuestCart($items);
            $stats = $this->computeTotals($items);
            $this->json(['success' => true, 'items' => $items, 'total' => $stats['total'], 'count' => $stats['count']]);
        } else {
            $userId = (int)$_SESSION['user']['id'];
        $ok = $this->cartModel->updateItem($userId, $productType, $productId, $quantity);
        if (!$ok) {
                $this->json(['success' => false, 'error_code' => 'OPERATION_FAILED', 'message' => 'Opération échouée']);
        }
        $items = $this->cartModel->getCartByUser($userId);
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'items' => $items, 'total' => $stats['total'], 'count' => $stats['count']]);
        }
    }

    public function removeProduct(): void {
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error_code' => 'INVALID_CSRF', 'message' => 'Erreur de sécurité, veuillez réessayer']);
        }
        $productId = (int)($_POST['product_id'] ?? 0);
        $productType = trim($_POST['product_type'] ?? '');
        if ($this->isGuest()) {
            $items = $this->getGuestCart();
            foreach ($items as $idx => $it) {
                if ($it['product_type'] === $productType && (int)$it['product_id'] === $productId) {
                    unset($items[$idx]);
                    break;
                }
            }
            $items = array_values($items);
            $this->setGuestCart($items);
            $stats = $this->computeTotals($items);
            $this->json(['success' => true, 'items' => $items, 'total' => $stats['total'], 'count' => $stats['count']]);
        } else {
            $userId = (int)$_SESSION['user']['id'];
        $ok = $this->cartModel->removeItem($userId, $productType, $productId);
        if (!$ok) {
                $this->json(['success' => false, 'error_code' => 'OPERATION_FAILED', 'message' => 'Opération échouée']);
        }
        $items = $this->cartModel->getCartByUser($userId);
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'items' => $items, 'total' => $stats['total'], 'count' => $stats['count']]);
        }
    }

    public function getCartCount(): void {
        // AJAX only
        if ($this->isGuest()) {
            $items = $this->getGuestCart();
            $stats = $this->computeTotals($items);
            $this->json(['success' => true, 'count' => $stats['count']]);
        } else {
        $userId = (int)$_SESSION['user']['id'];
        $stats = $this->getCartStats($userId);
        $this->json(['success' => true, 'count' => $stats['count']]);
    }
        }
        

    public function getNeighborhoods(): void {
        // Public GET: inscription et panier (invité ou connecté)
        $cityId = (int)($_GET['city_id'] ?? 0);
        $neighborhoodId = (int)($_GET['neighborhood_id'] ?? 0);
        
        if ($cityId <= 0) {
            $this->json(['success' => false, 'error_code' => 'INVALID_CITY', 'message' => 'Ville invalide']);
        }
        
        // Si on demande les frais d'un quartier spécifique
        if ($neighborhoodId > 0) {
            $neighborhood = $this->deliveryModel->findNeighborhoodById($neighborhoodId);
            if (!$neighborhood || (int)$neighborhood['cities_id'] !== $cityId) {
                $this->json(['success' => false, 'error_code' => 'INVALID_NEIGHBORHOOD', 'message' => 'Quartier invalide']);
            }
            
            $zone = $this->deliveryModel->findZoneById((int)$neighborhood['delivery_zones_id']);
            if (!$zone || !$zone['is_active']) {
                $this->json(['success' => false, 'error_code' => 'INVALID_ZONE', 'message' => 'Zone de livraison invalide']);
            }
            
            $this->json(['success' => true, 'delivery_fee' => (float)$zone['fee']]);
        }
        
        // Sinon, retourner tous les quartiers de la ville
        $neighborhoods = $this->deliveryModel->getNeighborhoodsByCity($cityId);
        $this->json(['success' => true, 'neighborhoods' => $neighborhoods]);
    }

    public function setDeliveryChoice(): void {
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error_code' => 'INVALID_CSRF', 'message' => 'Erreur de sécurité, veuillez réessayer']);
        }

        $citiesId = (int)($_POST['cities_id'] ?? 0);
        $neighborhoodsId = (int)($_POST['neighborhoods_id'] ?? 0);
        $addressLine = trim($_POST['address_line'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validation
        if ($citiesId <= 0 || $neighborhoodsId <= 0) {
            $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Veuillez sélectionner une ville et un quartier']);
        }

        // Récupérer le quartier et sa zone
        $neighborhood = $this->deliveryModel->findNeighborhoodById($neighborhoodsId);
        if (!$neighborhood || (int)$neighborhood['cities_id'] !== $citiesId) {
            $this->json(['success' => false, 'error_code' => 'INVALID_NEIGHBORHOOD', 'message' => 'Quartier invalide pour cette ville']);
        }

        $deliveryZonesId = (int)$neighborhood['delivery_zones_id'];
        $zone = $this->deliveryModel->findZoneById($deliveryZonesId);
        if (!$zone || !$zone['is_active']) {
            $this->json(['success' => false, 'error_code' => 'INVALID_ZONE', 'message' => 'Zone de livraison invalide']);
        }

        if ($this->isGuest()) {
            // Stocker côté session invité
            $_SESSION['guest_delivery'] = [
                'cities_id' => $citiesId,
                'delivery_zones_id' => $deliveryZonesId,
                'neighborhoods_id' => $neighborhoodsId,
                'address_line' => $addressLine,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email
            ];
        } else {
            // Mettre à jour l'utilisateur connecté
            $userId = (int)$_SESSION['user']['id'];
        $updateData = [
            'cities_id' => $citiesId,
            'delivery_zones_id' => $deliveryZonesId,
                'neighborhoods_id' => $neighborhoodsId,
                'address_line' => $addressLine
            ];
        $success = $this->userModel->update($userId, $updateData);
        if (!$success) {
                $this->json(['success' => false, 'error_code' => 'UPDATE_FAILED', 'message' => 'Échec de la mise à jour']);
        }
            // Session user
        $_SESSION['user']['cities_id'] = $citiesId;
        $_SESSION['user']['delivery_zones_id'] = $deliveryZonesId;
        $_SESSION['user']['neighborhoods_id'] = $neighborhoodsId;
        $_SESSION['user']['address_line'] = $addressLine;
        }

        // Calculer les nouveaux frais
        $deliveryFee = (float)$zone['fee'];
        if ($this->isGuest()) {
            $guest = $this->getGuestCart();
            $stats = $this->computeTotals($guest);
            $grandTotal = $stats['total'] + $deliveryFee;
            $cartTotal = $stats['total'];
            // Marquer la saisie invitée comme confirmée
            $_SESSION['guest_delivery_confirmed'] = true;
        } else {
            $userId = (int)$_SESSION['user']['id'];
        $cartStats = $this->getCartStats($userId);
        $grandTotal = $cartStats['total'] + $deliveryFee;
            $cartTotal = $cartStats['total'];
        }

        $this->json([
            'success' => true, 
            'deliveryFee' => $deliveryFee,
            'grandTotal' => $grandTotal,
            'cartTotal' => $cartTotal
        ]);
    }

    /**
     * Crée une commande depuis le panier
     * 
     * @route POST /create-order
     */
    public function createOrder(): void {
        // Vérifier le token CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error_code' => 'INVALID_CSRF', 'message' => 'Erreur de sécurité, veuillez réessayer']);
        }
        
        $isGuest = $this->isGuest();
        if ($isGuest) {
            $cartItems = $this->getGuestCart();
        } else {
            $userId = (int)$_SESSION['user']['id'];
            $cartItems = $this->cartModel->getCartByUser($userId);
        }

        if (empty($cartItems)) {
            $this->json(['success' => false, 'error_code' => 'EMPTY_CART', 'message' => 'Votre panier est vide']);
        }

        // Validation des données de livraison
        $deliveryInfo = $this->validateDeliveryInfo($_POST);
        if (!$deliveryInfo['valid']) {
            $this->json(['success' => false, 'error_code' => $deliveryInfo['error_code'], 'message' => $deliveryInfo['message']]);
        }

        // Calculer le total
        $total = array_reduce($cartItems, function($acc, $item) {
            return $acc + ($item['subtotal'] ?? 0);
        }, 0);

        // Préparer les données de la commande
        if ($isGuest) {
            $firstName = trim($_POST['first_name'] ?? ($_SESSION['guest_delivery']['first_name'] ?? ''));
            $lastName = trim($_POST['last_name'] ?? ($_SESSION['guest_delivery']['last_name'] ?? ''));
            $phone = trim($_POST['phone'] ?? ($_SESSION['guest_delivery']['phone'] ?? ''));
            $email = trim($_POST['email'] ?? ($_SESSION['guest_delivery']['email'] ?? ''));
            $addressLinePost = trim($_POST['address_line'] ?? '');
            $addressLineSess = trim($_SESSION['guest_delivery']['address_line'] ?? '');
            $addressLine = $addressLinePost !== '' ? $addressLinePost : $addressLineSess;
            // Exiger que l'adresse ait été enregistrée côté invité
            if (!($_SESSION['guest_delivery_confirmed'] ?? false)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Veuillez enregistrer votre adresse de livraison']);
            }

            // Validation minimale serveur pour données invitées
            $nameRegex = "/^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,50}$/u";
            $phoneRegex = "/^[0-9 +().-]{7,20}$/";
            $emailRegex = "/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/";
            if ($email === '') {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Email requis']);
            }
            if ($firstName && !preg_match($nameRegex, $firstName)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Prénom invalide']);
            }
            if ($lastName && !preg_match($nameRegex, $lastName)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Nom invalide']);
            }
            if ($phone && !preg_match($phoneRegex, $phone)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Téléphone invalide']);
            }
            if ($email && !preg_match($emailRegex, $email)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Email invalide']);
            }
            if ($addressLine === '' || mb_strlen($addressLine) < 5) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Adresse complète requise']);
            }
            $orderData = [
                'users_id' => null,
                'customer_first_name' => $firstName,
                'customer_last_name' => $lastName,
                'customer_phone' => $phone,
                'customer_email' => $email ?: null,
                'status' => 'En attente',
                'order_date' => date('Y-m-d H:i:s'),
                'total_amount' => $total,
                'delivery_fee' => $deliveryInfo['data']['delivery_fee'],
                'delivery_address' => $deliveryInfo['data']['delivery_address'] ?? $addressLine ?? $deliveryInfo['data']['address_line'],
                'delivery_comment' => $deliveryInfo['data']['delivery_comment'],
                'cities_id' => $deliveryInfo['data']['cities_id'],
                'neighborhoods_id' => $deliveryInfo['data']['neighborhoods_id'],
                'delivery_zones_id' => $deliveryInfo['data']['delivery_zones_id']
            ];
        } else {
            $userId = (int)$_SESSION['user']['id'];
            $orderData = [
                'users_id' => $userId,
                'customer_first_name' => null,
                'customer_last_name' => null,
                'customer_phone' => null,
                'customer_email' => null,
                'status' => 'En attente',
                'order_date' => date('Y-m-d H:i:s'),
                'total_amount' => $total,
                'delivery_fee' => $deliveryInfo['data']['delivery_fee'],
                'delivery_address' => $deliveryInfo['data']['delivery_address'] ?? $deliveryInfo['data']['address_line'],
                'delivery_comment' => $deliveryInfo['data']['delivery_comment'],
                'cities_id' => $deliveryInfo['data']['cities_id'],
                'neighborhoods_id' => $deliveryInfo['data']['neighborhoods_id'],
                'delivery_zones_id' => $deliveryInfo['data']['delivery_zones_id']
            ];
        }

        // Créer la commande avec transaction via le service
        $orderId = $this->ordersService->createOrderWithTransaction($orderData, $cartItems);
        
        if (!$orderId) {
            $this->json(['success' => false, 'error_code' => 'ORDER_CREATION_FAILED', 'message' => 'Erreur lors de la création de la commande']);
        }

        // Vider le panier
        if ($isGuest) {
            $this->setGuestCart([]);
        } else {
            $this->cartModel->clearCart((int)$_SESSION['user']['id']);
        }

        $this->json(['success' => true, 'order_id' => $orderId, 'message' => 'Commande validée avec succès']);
    }

    /**
     * Valide les informations de livraison
     */
    private function validateDeliveryInfo(array $postData): array {
        $addressLine = trim($postData['address_line'] ?? '');
        $deliveryComment = trim($postData['delivery_comment'] ?? '');
        $citiesId = (int)($postData['cities_id'] ?? 0);
        $neighborhoodsId = (int)($postData['neighborhoods_id'] ?? 0);

        // Validation des champs obligatoires
        if ($citiesId <= 0) {
            return ['valid' => false, 'error_code' => 'INVALID_CITY', 'message' => 'Veuillez sélectionner une ville'];
        }

        if ($neighborhoodsId <= 0) {
            return ['valid' => false, 'error_code' => 'INVALID_NEIGHBORHOOD', 'message' => 'Veuillez sélectionner un quartier'];
        }

        // Récupérer le quartier et sa zone
        $neighborhood = $this->deliveryModel->findNeighborhoodById($neighborhoodsId);
        if (!$neighborhood || (int)$neighborhood['cities_id'] !== $citiesId) {
            return ['valid' => false, 'error_code' => 'INVALID_NEIGHBORHOOD', 'message' => 'Quartier invalide pour cette ville'];
        }

        $deliveryZonesId = (int)$neighborhood['delivery_zones_id'];
        $zone = $this->deliveryModel->findZoneById($deliveryZonesId);
        if (!$zone || !$zone['is_active']) {
            return ['valid' => false, 'error_code' => 'INVALID_ZONE', 'message' => 'Zone de livraison invalide'];
        }

        return [
            'valid' => true,
            'data' => [
                'delivery_fee' => (float)$zone['fee'],
                'address_line' => $addressLine,
                'delivery_comment' => $deliveryComment,
                'cities_id' => $citiesId,
                'neighborhoods_id' => $neighborhoodsId,
                'delivery_zones_id' => $deliveryZonesId
            ]
        ];
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


