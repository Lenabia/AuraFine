<?php
namespace app\controllers;

use app\models\Salads;
use app\middleware\Middleware;

/**
 * Contrôleur pour la gestion des salades (CRUD complet)
 * 
 * @security Vérification du rôle admin sur toutes les actions
 * @csrf Protection CSRF sur toutes les actions de modification
 * @error-handling Gestion centralisée des erreurs avec messages flash
 */
class SaladsController extends Middleware {
    
    private $saladModel;
    
    public function __construct() {
        $this->saladModel = new Salads();
    }
    
    /**
     * Vérifie que l'utilisateur est admin, sinon redirige
     * 
     * @security Protection contre l'accès non autorisé
     */
    private function checkAdminAccess() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->redirectTo('accesDenied');
        }
    }
    
    /**
     * Affiche la liste des salades avec boutons d'action
     * 
     * @route GET /admin/salades
     * @security Vérification du rôle admin
     */
    public function index() {
        $this->checkAdminAccess();
        
        try {
            // Récupérer toutes les salades actives
            $salads = $this->saladModel->getAll();
            $totalSalads = $this->saladModel->count();
            
            // Générer le token CSRF pour les formulaires
            $csrfToken = $this->generateCSRFToken();
            
            $this->render("display-salads.phtml", "admin-layout.phtml", [
                'pageTitle' => 'Gestion des Salades',
                'currentPage' => 'admin-salads',
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
                    ],
                    [
                        'title' => 'Salades',
                        'url' => 'index.php?action=admin-salads',
                        'icon' => 'fas fa-leaf'
                    ]
                ],
                'salads' => $salads,
                'totalSalads' => $totalSalads,
                'csrfToken' => $csrfToken
            ]);
            
        } catch (\Exception $e) {
            // Log de l'erreur pour le debugging
            error_log("Erreur affichage salades: " . $e->getMessage());
            
            // Message d'erreur pour l'utilisateur
            $_SESSION['error_message'] = 'Erreur lors du chargement des salades';
            $this->redirectTo('admin-home');
        }
    }
    
    /**
     * Affiche le formulaire de création/édition de salade
     * 
     * @route GET /admin/salades/create ou /admin/salades/{id}/edit
     * @param int|null $id L'ID de la salade à modifier (null pour création)
     * @security Vérification du rôle admin
     */
    public function form($id = null) {
        $this->checkAdminAccess();
        
        $salad = null;
        $isEdit = false;
        
        // Si un ID est fourni, récupérer la salade pour édition
        if ($id) {
            try {
                $salad = $this->saladModel->findById($id);
                if (!$salad) {
                    $_SESSION['error_message'] = 'Salade non trouvée';
                    $this->redirectTo('admin-salads');
                }
                $isEdit = true;
            } catch (\Exception $e) {
                error_log("Erreur récupération salade: " . $e->getMessage());
                $_SESSION['error_message'] = 'Erreur lors du chargement de la salade';
                $this->redirectTo('admin-salads');
            }
        }
        
        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();
        
        $this->render("form-salads.phtml", "admin-layout.phtml", [
            'pageTitle' => $isEdit ? 'Modifier la Salade' : 'Nouvelle Salade',
            'currentPage' => 'admin-salads',
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
                ],
                [
                    'title' => 'Salades',
                    'url' => 'index.php?action=admin-salads',
                    'icon' => 'fas fa-leaf'
                ],
                [
                    'title' => $isEdit ? 'Modifier' : 'Nouvelle',
                    'url' => $isEdit ? 'index.php?action=admin-salads-edit&id=' . $id : 'index.php?action=admin-salads-create',
                    'icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus'
                ]
            ],
            'salad' => $salad,
            'isEdit' => $isEdit,
            'csrfToken' => $csrfToken
        ]);
    }
    
    /**
     * Traite la création d'une nouvelle salade
     * 
     * @route POST /admin/salades/store
     * @security Vérification CSRF et rôle admin
     */
    public function store() {
        $this->checkAdminAccess();
        
        // Vérifier le token CSRF
        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité. Veuillez réessayer.';
            $this->redirectTo('admin-salads');
        }
        
        try {
            // Validation des données
            $errors = $this->validateSaladData($_POST);
            if (!empty($errors)) {
                $_SESSION['validation_errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->redirectTo('admin-salads-create');
            }
            
            // Gérer l'upload d'image si un fichier est fourni
            $imageUrl = $this->handleImageUpload();
            if ($imageUrl) {
                $_POST['image'] = $imageUrl;
            }
            
            // Créer la salade
            $saladId = $this->saladModel->create($_POST);
            
            if ($saladId) {
                $_SESSION['success_message'] = 'Salade créée avec succès';
                $this->redirectTo('admin-salads');
            } else {
                throw new \Exception('Erreur lors de la création');
            }
            
        } catch (\InvalidArgumentException $e) {
            // Erreur de validation métier
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['old_input'] = $_POST;
            header('Location: index.php?action=admin-salads-create');
            exit;
            
        } catch (\Exception $e) {
            // Erreur technique
            error_log("Erreur création salade: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la création de la salade';
            header('Location: index.php?action=admin-salads-create');
            exit;
        }
    }
    
    /**
     * Traite la mise à jour d'une salade existante
     * 
     * @route POST /admin/salades/{id}/update
     * @param int $id L'ID de la salade à modifier
     * @security Vérification CSRF et rôle admin
     */
    public function update($id) {
        $this->checkAdminAccess();
        
        // Vérifier le token CSRF
        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité. Veuillez réessayer.';
            $this->redirectTo('admin-salads');
        }
        
        try {
            // Récupérer la salade existante pour préserver l'image
            $existingSalad = $this->saladModel->findById($id);
            if (!$existingSalad) {
                throw new \InvalidArgumentException('Salade non trouvée');
            }
            
            // Gérer l'upload d'image si un fichier est fourni
            $imageUrl = $this->handleImageUpload();
            if ($imageUrl) {
                $_POST['image'] = $imageUrl;
            } else {
                // Préserver l'image existante si aucun nouveau fichier
                $_POST['image'] = $existingSalad['image'];
            }
            
            // Validation des données
            $errors = $this->validateSaladData($_POST);
            if (!empty($errors)) {
                $_SESSION['validation_errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                header('Location: index.php?action=admin-salads-edit&id=' . $id);
                exit;
            }
            
            // Mettre à jour la salade
            $success = $this->saladModel->update($id, $_POST);
            
            if ($success) {
                $_SESSION['success_message'] = 'Salade modifiée avec succès';
                $this->redirectTo('admin-salads');
            } else {
                throw new \Exception('Erreur lors de la mise à jour');
            }
            
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['old_input'] = $_POST;
            header('Location: index.php?action=admin-salads-edit&id=' . $id);
            exit;
            
        } catch (\Exception $e) {
            error_log("Erreur modification salade: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la modification de la salade';
            header('Location: index.php?action=admin-salads-edit&id=' . $id);
            exit;
        }
    }
    
    /**
     * Supprime une salade (soft delete)
     * 
     * @route POST /admin/salades/{id}/delete
     * @param int $id L'ID de la salade à supprimer
     * @security Vérification CSRF et rôle admin
     */
    public function delete($id) {
        $this->checkAdminAccess();
        
        // Vérifier le token CSRF
        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité. Veuillez réessayer.';
            $this->redirectTo('admin-salads');
        }
        
        try {
            $success = $this->saladModel->delete($id);
            
            if ($success) {
                $_SESSION['success_message'] = 'Salade supprimée avec succès';
            } else {
                throw new \Exception('Erreur lors de la suppression');
            }
            
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error_message'] = $e->getMessage();
        } catch (\Exception $e) {
            error_log("Erreur suppression salade: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la suppression de la salade';
        }
        
        $this->redirectTo('admin-salads');
    }
    
    /**
     * Valide les données d'une salade
     * 
     * @param array $data Les données à valider
     * @return array Liste des erreurs de validation
     * @security Validation côté serveur pour la sécurité
     */
    private function validateSaladData($data) {
        $errors = [];
        
        // Nom obligatoire et longueur
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Le nom est obligatoire';
        } elseif (strlen(trim($data['name'])) > 100) {
            $errors['name'] = 'Le nom ne peut pas dépasser 100 caractères';
        }
        
        // Prix obligatoire et positif
        if (empty($data['price']) || !is_numeric($data['price'])) {
            $errors['price'] = 'Le prix est obligatoire et doit être un nombre';
        } elseif ((float)$data['price'] < 0) {
            $errors['price'] = 'Le prix doit être positif';
        }
        
        // Description optionnelle mais limitée
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'La description ne peut pas dépasser 1000 caractères';
        }
        
        // Stock quantity doit être un entier positif
        if (!empty($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || (int)$data['stock_quantity'] < 0)) {
            $errors['stock_quantity'] = 'La quantité en stock doit être un nombre entier positif';
        }
        
        return $errors;
    }
    
    /**
     * Affiche la liste publique des salades (disponibles uniquement)
     * 
     * @route GET /salad
     * @public Page accessible à tous les utilisateurs
     */
    public function publicIndex() {
        try {
            // Récupérer seulement les salades disponibles
            $salads = $this->saladModel->getAvailable();
            $totalSalads = count($salads);
            
            $this->render("salads.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Salades Fraîches',
                'salads' => $salads,
                'totalSalads' => $totalSalads
            ]);
            
        } catch (\Exception $e) {
            // Log de l'erreur pour le debugging
            error_log("Erreur affichage salades publiques: " . $e->getMessage());
            
            // Afficher la page même en cas d'erreur avec un message
            $this->render("salads.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Salades Fraîches',
                'salads' => [],
                'totalSalads' => 0,
                'error' => 'Erreur lors du chargement des salades'
            ]);
        }
    }
    
    /**
     * Gère l'upload d'image pour les salades
     * 
     * @return string|null L'URL de l'image uploadée ou null si aucun fichier
     * @security Validation du type et de la taille du fichier
     */
    private function handleImageUpload() {
        // Vérifier qu'un fichier a été uploadé
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null; // Aucun fichier uploadé, pas d'erreur
        }
        
        $file = $_FILES['image'];
        
        // Validation du type de fichier
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé. Formats acceptés : JPG, PNG, WEBP');
        }
        
        // Validation de la taille (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            throw new \InvalidArgumentException('Fichier trop volumineux. Taille maximale : 2MB');
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'salad_' . uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = 'app/public/uploads/salads/' . $fileName;
        
        // Créer le dossier s'il n'existe pas
        $uploadDir = dirname($uploadPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return 'app/public/uploads/salads/' . $fileName;
        } else {
            throw new \Exception('Erreur lors de l\'upload de l\'image');
        }
    }
}
