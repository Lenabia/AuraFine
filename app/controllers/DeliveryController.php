<?php
namespace app\controllers;

use app\Models\Delivery;
use app\middleware\Middleware;

class DeliveryController extends Middleware {
    private Delivery $model;

    public function __construct() {
        $this->model = new Delivery();
    }

    private function checkAdmin() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->redirectTo('accesDenied');
        }
    }

    /* =========================
       CITIES
       ========================= */
    public function index() {
        $this->checkAdmin();
        $cities = $this->model->getAllCities();
        $csrfToken = $this->generateCSRFToken();
        $this->render('admin-deliveries.phtml', 'admin-layout.phtml', [
            'pageTitle' => 'Gestion des livraisons',
            'currentPage' => 'admin-delivery',
            'breadcrumbs' => [
                ['title' => 'Dashboard','url' => 'index.php?action=admin-home','icon' => 'fas fa-tachometer-alt'],
                ['title' => 'Livraison','url' => 'index.php?action=admin-deliveries','icon' => 'fas fa-truck']
            ],
            'cities' => $cities,
            'csrfToken' => $csrfToken
        ]);
    }

    public function formCity($id = null) {
        $this->checkAdmin();
        $city = null; $isEdit = false;
        if ($id) {
            $city = $this->model->findCityById((int)$id);
            if (!$city) { $_SESSION['error_message'] = 'Ville introuvable'; $this->redirectTo('admin-deliveries'); }
            $isEdit = true;
        }
        $csrfToken = $this->generateCSRFToken();
        $this->render('admin-deliveries-form-city.phtml', 'admin-layout.phtml', [
            'pageTitle' => $isEdit ? 'Modifier la ville' : 'Nouvelle ville',
            'currentPage' => 'admin-delivery',
            'breadcrumbs' => [
                ['title' => 'Dashboard','url' => 'index.php?action=admin-home','icon' => 'fas fa-tachometer-alt'],
                ['title' => 'Livraison','url' => 'index.php?action=admin-deliveries','icon' => 'fas fa-truck'],
                ['title' => $isEdit ? 'Modifier' : 'Créer','url' => $isEdit ? 'index.php?action=admin-deliveries-edit&id='.$id : 'index.php?action=admin-deliveries-create','icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus']
            ],
            'city' => $city,
            'isEdit' => $isEdit,
            'csrfToken' => $csrfToken
        ]);
    }

    public function storeCity() {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; $this->redirectTo('admin-deliveries'); }
        $name = trim($_POST['name'] ?? '');
        $errors = $this->validateCity($name);
        if ($errors) { $_SESSION['validation_errors'] = $errors; $_SESSION['old_input'] = $_POST; $this->redirectTo('admin-deliveries-create'); }
        $this->model->createCity($name);
        $_SESSION['success_message'] = 'Ville créée avec succès';
        $this->redirectTo('admin-deliveries');
    }

    public function updateCity($id) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; $this->redirectTo('admin-deliveries'); }
        $name = trim($_POST['name'] ?? '');
        $errors = $this->validateCity($name, (int)$id);
        if ($errors) { $_SESSION['validation_errors'] = $errors; $_SESSION['old_input'] = $_POST; header('Location: index.php?action=admin-deliveries-edit&id='.(int)$id); exit; }
        $this->model->updateCity((int)$id, $name);
        $_SESSION['success_message'] = 'Ville mise à jour';
        $this->redirectTo('admin-deliveries');
    }

    public function deleteCity($id) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; $this->redirectTo('admin-deliveries'); }
        // Vérifier dépendances (zones/quartiers) avant suppression
        $zones = $this->model->getZonesByCity((int)$id);
        $neigh = $this->model->getNeighborhoodsByCity((int)$id);
        if (!empty($zones) || !empty($neigh)) {
            $_SESSION['error_message'] = 'Impossible de supprimer: zones/quartiers existent';
            $this->redirectTo('admin-deliveries');
        }
        $this->model->deleteCity((int)$id);
        $_SESSION['success_message'] = 'Ville supprimée';
        $this->redirectTo('admin-deliveries');
    }

    /* =========================
       CITY SHOW (ZONES + NEIGHBORHOODS)
       ========================= */
    public function showCity($id) {
        $this->checkAdmin();
        $city = $this->model->findCityById((int)$id);
        if (!$city) { $_SESSION['error_message'] = 'Ville introuvable'; $this->redirectTo('admin-deliveries'); }
        $zones = $this->model->getZonesByCity((int)$id);
        $neighborhoods = $this->model->getNeighborhoodsByCity((int)$id);
        $csrfToken = $this->generateCSRFToken();
        $this->render('admin-deliveries-show.phtml', 'admin-layout.phtml', [
            'pageTitle' => 'Livraison - '.htmlspecialchars($city['name']),
            'currentPage' => 'admin-delivery',
            'breadcrumbs' => [
                ['title' => 'Dashboard','url' => 'index.php?action=admin-home','icon' => 'fas fa-tachometer-alt'],
                ['title' => 'Livraison','url' => 'index.php?action=admin-deliveries','icon' => 'fas fa-truck'],
                ['title' => htmlspecialchars($city['name']),'url' => 'index.php?action=admin-deliveries-show&id='.$id,'icon' => 'fas fa-city']
            ],
            'city' => $city,
            'zones' => $zones,
            'neighborhoods' => $neighborhoods,
            'csrfToken' => $csrfToken
        ]);
    }

    /* =========================
       ZONES
       ========================= */
    public function storeZone($cityId) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $fee = (string)($_POST['fee'] ?? '');
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;
        $errors = $this->validateZone((int)$cityId, $code, $fee);
        if ($errors) { $_SESSION['validation_errors'] = $errors; $_SESSION['old_input'] = $_POST; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $this->model->createZone((int)$cityId, $code, (float)$fee, $isActive);
        $_SESSION['success_message'] = 'Zone ajoutée';
        header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit;
    }

    public function updateZone($cityId, $zoneId) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $fee = (string)($_POST['fee'] ?? '');
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;
        $errors = $this->validateZone((int)$cityId, $code, $fee, (int)$zoneId);
        if ($errors) { $_SESSION['validation_errors'] = $errors; $_SESSION['old_input'] = $_POST; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $this->model->updateZone((int)$zoneId, $code, (float)$fee, $isActive);
        $_SESSION['success_message'] = 'Zone mise à jour';
        header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit;
    }

    public function deleteZone($cityId, $zoneId) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $this->model->deleteZone((int)$zoneId);
        $_SESSION['success_message'] = 'Zone supprimée';
        header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit;
    }

    /* =========================
       NEIGHBORHOODS
       ========================= */
    public function storeNeighborhood($cityId) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $name = trim($_POST['name'] ?? '');
        $zoneId = (int)($_POST['delivery_zones_id'] ?? 0);
        $errors = $this->validateNeighborhood((int)$cityId, $zoneId, $name);
        if ($errors) { $_SESSION['validation_errors'] = $errors; $_SESSION['old_input'] = $_POST; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $this->model->createNeighborhood((int)$cityId, $zoneId, $name);
        $_SESSION['success_message'] = 'Quartier ajouté';
        header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit;
    }

    public function updateNeighborhood($cityId, $neighborhoodId) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $name = trim($_POST['name'] ?? '');
        $zoneId = (int)($_POST['delivery_zones_id'] ?? 0);
        $errors = $this->validateNeighborhood((int)$cityId, $zoneId, $name, (int)$neighborhoodId);
        if ($errors) { $_SESSION['validation_errors'] = $errors; $_SESSION['old_input'] = $_POST; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $this->model->updateNeighborhood((int)$neighborhoodId, $zoneId, $name);
        $_SESSION['success_message'] = 'Quartier mis à jour';
        header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit;
    }

    public function deleteNeighborhood($cityId, $neighborhoodId) {
        $this->checkAdmin();
        if (!$this->checkCSRFToken()) { $_SESSION['error_message'] = 'Erreur de sécurité'; header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit; }
        $this->model->deleteNeighborhood((int)$neighborhoodId);
        $_SESSION['success_message'] = 'Quartier supprimé';
        header('Location: index.php?action=admin-deliveries-show&id='.(int)$cityId); exit;
    }

    /* =========================
       VALIDATION (contrôleur)
       ========================= */
    private function validateCity(string $name, int $ignoreId = 0): array {
        $errors = [];
        if ($name === '') { $errors['name'] = 'Le nom est obligatoire'; }
        if (mb_strlen($name) > 100) { $errors['name'] = 'Le nom est trop long'; }
        $existing = $this->model->findCityByName($name);
        if ($existing && (int)$existing['id'] !== $ignoreId) { $errors['name'] = 'Cette ville existe déjà'; }
        return $errors;
    }

    private function validateZone(int $cityId, string $code, string $fee, int $ignoreId = 0): array {
        $errors = [];
        if (!in_array($code, ['A','B','C'], true)) { $errors['code'] = 'Code invalide (A,B ou C)'; }
        if ($fee === '' || !is_numeric($fee) || (float)$fee <= 0) { $errors['fee'] = 'Le prix doit être un décimal positif'; }
        $existing = $this->model->findZoneByCityAndCode($cityId, $code);
        if ($existing && (int)$existing['id'] !== $ignoreId) { $errors['code'] = 'Ce code existe déjà pour cette ville'; }
        return $errors;
    }

    private function validateNeighborhood(int $cityId, int $zoneId, string $name, int $ignoreId = 0): array {
        $errors = [];
        if ($name === '') { $errors['name'] = 'Le nom est obligatoire'; }
        if (mb_strlen($name) > 120) { $errors['name'] = 'Nom trop long'; }
        // Vérifier unicité nom par ville
        $existing = $this->model->findNeighborhoodByCityAndName($cityId, $name);
        if ($existing && (int)$existing['id'] !== $ignoreId) { $errors['name'] = 'Ce quartier existe déjà'; }
        // Vérifier que la zone appartient à la ville
        $zone = $this->model->findZoneById($zoneId);
        if (!$zone || (int)$zone['cities_id'] !== $cityId) { $errors['delivery_zones_id'] = 'Zone invalide pour cette ville'; }
        return $errors;
    }
}



