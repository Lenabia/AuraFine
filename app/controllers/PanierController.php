<?php
namespace app\controllers;

use app\models\Panier;
use app\models\Delivery;
use app\models\Users;
use app\models\Orders;
use app\models\OrdersDetail;
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

        // Récupérer les noms de ville et quartier pour l'affichage
        $userCityName = '';
        $userNeighborhoodName = '';
        
        // Pour les utilisateurs connectés, utiliser directement la session
        if (!$isGuest) {
            $citiesId = $_SESSION['user']['cities_id'] ?? null;
            $neighborhoodsId = $_SESSION['user']['neighborhoods_id'] ?? null;
        } else {
            $citiesId = $userDeliveryInfo['cities_id'] ?? null;
            $neighborhoodsId = $userDeliveryInfo['neighborhoods_id'] ?? null;
        }
        
        if (!empty($citiesId)) {
            $cityData = $this->deliveryModel->findCityById((int)$citiesId);
            $userCityName = $cityData['name'] ?? '';
        }
        
        if (!empty($neighborhoodsId)) {
            $neighborhood = $this->deliveryModel->findNeighborhoodById((int)$neighborhoodsId);
            $userNeighborhoodName = $neighborhood['name'] ?? '';
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
            'userCityName' => $userCityName,
            'userNeighborhoodName' => $userNeighborhoodName,
            'csrf_token' => $this->generateCSRFToken(),
            'isGuest' => $isGuest,
            'userLoyaltyPoints' => (int)($_SESSION['user']['loyalty_points'] ?? 0)
        ]);
    }

    public function precheckLoyalty(): void {
        // Requête AJAX GET
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Requête invalide']);
        }

        // Uniquement utilisateurs connectés
        if (!$this->requireUserRole()) {
            http_response_code(401);
            $this->json(['success' => false, 'message' => 'Non autorisé']);
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            $this->json(['success' => false, 'message' => 'Non autorisé']);
        }

        // Recalculer le panier serveur (source de vérité)
        $items = $this->cartModel->getCartByUser($userId);
        $cartTotal = array_reduce($items, function($acc, $it){ return $acc + ((float)($it['subtotal'] ?? 0)); }, 0.0);
        $currentPoints = (int)($_SESSION['user']['loyalty_points'] ?? 0);

        // Points utilisables plafonnés au sous-total produits
        $maxUsable = (int)min($currentPoints, (int)round($cartTotal));

        $this->json([
            'success' => true,
            'currentPoints' => (int)$currentPoints,
            'cartSubtotal' => (int)round($cartTotal),
            'maxUsable' => (int)$maxUsable
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
        $phoneSuffix = trim($_POST['phone_suffix'] ?? '');
        $phone = '+221' . $phoneSuffix;
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

        // Vérifier que l'invité a enregistré son adresse de livraison
        if ($isGuest && !($_SESSION['guest_delivery_confirmed'] ?? false)) {
            $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Veuillez enregistrer votre adresse de livraison']);
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

        // Gestion points fidélité (utilisateurs uniquement)
        $loyaltyUsed = 0;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if (!$isGuest) {
            $requested = (int)($_POST['loyalty_points_used'] ?? 0);
            $userPoints = (int)($_SESSION['user']['loyalty_points'] ?? 0);
            $loyaltyUsed = max(0, min($requested, (int)round($total), $userPoints));
        }

        // Préparer les données de la commande
        if ($isGuest) {
            $firstName = trim($_POST['first_name'] ?? ($_SESSION['guest_delivery']['first_name'] ?? ''));
            $lastName = trim($_POST['last_name'] ?? ($_SESSION['guest_delivery']['last_name'] ?? ''));
            $phone = trim($_POST['phone'] ?? ($_SESSION['guest_delivery']['phone'] ?? ''));
            $email = trim($_POST['email'] ?? ($_SESSION['guest_delivery']['email'] ?? ''));
            $addressLinePost = trim($_POST['address_line'] ?? '');
            $addressLineSess = trim($_SESSION['guest_delivery']['address_line'] ?? '');
            $addressLine = $addressLinePost !== '' ? $addressLinePost : $addressLineSess;

            // Validation minimale serveur pour données invitées
            $nameRegex = "/^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,50}$/u";
            $phoneRegex = "/^\+221[0-9]{9}$/";
            $emailRegex = "/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/";
            // Email optionnel pour les invités - pas de validation si vide
            if ($firstName && !preg_match($nameRegex, $firstName)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Prénom invalide']);
            }
            if ($lastName && !preg_match($nameRegex, $lastName)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Nom invalide']);
            }
            if ($phone && !preg_match($phoneRegex, $phone)) {
                $this->json(['success' => false, 'error_code' => 'INVALID_DATA', 'message' => 'Le numéro doit commencer par +221 suivi de 9 chiffres (ex: +221771234567)']);
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
                'total_amount' => $total + $deliveryInfo['data']['delivery_fee'] - $loyaltyUsed,
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
                // On fige l'adresse utilisée dans la commande, tout en autorisant l'édition
                'customer_first_name' => null,
                'customer_last_name' => null,
                'customer_phone' => null,
                'customer_email' => null,
                'status' => 'En attente',
                'order_date' => date('Y-m-d H:i:s'),
                'total_amount' => $total + $deliveryInfo['data']['delivery_fee'] - $loyaltyUsed,
                'delivery_fee' => $deliveryInfo['data']['delivery_fee'],
                'delivery_address' => $deliveryInfo['data']['delivery_address'] ?? $deliveryInfo['data']['address_line'],
                'delivery_comment' => $deliveryInfo['data']['delivery_comment'],
                'cities_id' => $deliveryInfo['data']['cities_id'],
                'neighborhoods_id' => $deliveryInfo['data']['neighborhoods_id'],
                'delivery_zones_id' => $deliveryInfo['data']['delivery_zones_id']
            ];
            // Mettre à jour le profil utilisateur et la session si l'adresse change via POST
            if (!empty($_POST)) {
                $updateData = [
                    'address_line' => $deliveryInfo['data']['address_line'],
                    'cities_id' => $deliveryInfo['data']['cities_id'],
                    'neighborhoods_id' => $deliveryInfo['data']['neighborhoods_id'],
                    'delivery_zones_id' => $deliveryInfo['data']['delivery_zones_id']
                ];
                $this->userModel->update($userId, $updateData);
                $_SESSION['user']['address_line'] = $updateData['address_line'];
                $_SESSION['user']['cities_id'] = $updateData['cities_id'];
                $_SESSION['user']['neighborhoods_id'] = $updateData['neighborhoods_id'];
                $_SESSION['user']['delivery_zones_id'] = $updateData['delivery_zones_id'];
            }
        }

        // Créer la commande et déduire les points dans LA MÊME transaction
        $pdo = $this->ordersService->getOrdersModel()->getConnection();
        try {
            $pdo->beginTransaction();

            // Créer commande + détails sans transaction interne
            $orderId = $this->ordersService->createOrderAndDetailsNoTx($orderData, $cartItems);
            if (!$orderId) {
                throw new \Exception('Échec création commande');
            }

            // Déduction des points si applicable
            if ($loyaltyUsed > 0 && !$isGuest) {
                // Verrouiller l'utilisateur et déduire
                $usersModel = new \app\Models\Users();
                $ok = $usersModel->deductLoyaltyPoints($userId, $loyaltyUsed, (int)$orderId, $_POST['idempotency_key'] ?? null);
                if (!$ok) {
                    throw new \Exception('Échec déduction points');
                }
                // Mettre à jour la session
                $_SESSION['user']['loyalty_points'] = (int)max(0, ((int)($_SESSION['user']['loyalty_points'] ?? 0)) - $loyaltyUsed);
            }

            // Vider le panier
            if ($isGuest) {
                $this->setGuestCart([]);
            } else {
                $this->cartModel->clearCart($userId);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('createOrder (with loyalty) error: ' . $e->getMessage());
            $this->json(['success' => false, 'error_code' => 'ORDER_CREATION_FAILED', 'message' => 'Erreur lors de la création de la commande']);
        }

        // Générer le numéro de commande formaté
        $orderNumber = $this->ordersService->formatOrderNumber($orderId, $isGuest ? null : $userId);
        
        $this->json(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber, 'message' => 'Commande validée avec succès', 'loyalty_used' => (int)$loyaltyUsed, 'new_loyalty_balance' => (int)($_SESSION['user']['loyalty_points'] ?? 0)]);
    }

    /**
     * Valide les informations de livraison
     */
    private function validateDeliveryInfo(array $postData): array {
        // Fallback pour utilisateur connecté: si POST vide, reprendre la session
        $isGuest = $this->isGuest();
        $sessionUser = $_SESSION['user'] ?? [];

        $addressLine = trim($postData['address_line'] ?? ($isGuest ? '' : ($sessionUser['address_line'] ?? '')));
        $deliveryComment = trim($postData['delivery_comment'] ?? '');
        $citiesId = (int)($postData['cities_id'] ?? ($isGuest ? 0 : (int)($sessionUser['cities_id'] ?? 0)));
        $neighborhoodsId = (int)($postData['neighborhoods_id'] ?? ($isGuest ? 0 : (int)($sessionUser['neighborhoods_id'] ?? 0)));

        // Pour les invités, si les données de livraison ne sont pas dans POST, 
        // essayer de les récupérer depuis la session invité
        if ($isGuest && $citiesId <= 0) {
            $guestDelivery = $_SESSION['guest_delivery'] ?? [];
            $citiesId = (int)($guestDelivery['cities_id'] ?? 0);
            $neighborhoodsId = (int)($guestDelivery['neighborhoods_id'] ?? 0);
            $addressLine = trim($guestDelivery['address_line'] ?? '');
        }

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


