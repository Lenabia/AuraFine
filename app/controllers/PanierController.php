<?php
namespace app\controllers;

use app\Models\Panier;
use app\middleware\Middleware;

class PanierController extends Middleware {
    private Panier $cartModel;

    public function __construct() {
        $this->cartModel = new Panier();
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

        $this->render('panier.phtml', 'layout.phtml', [
            'cartItems' => $items,
            'cartTotal' => $total,
            'totalQuantity' => $totalQuantity,
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


