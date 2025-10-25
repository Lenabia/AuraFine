<?php
namespace app\controllers;

use app\models\Menus;
use app\middleware\Middleware;

/**
 * Contrôleur pour la gestion des menus (CRUD complet)
 * 
 * @security Vérification du rôle admin sur toutes les actions
 * @csrf Protection CSRF sur toutes les actions de modification
 * @error-handling Gestion centralisée des erreurs avec messages flash
 */
class MenusController extends Middleware {
    
    private $menuModel;
    
    public function __construct() {
        $this->menuModel = new Menus();
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
     * Affiche la liste des menus avec boutons d'action
     * 
     * @route GET /admin/menus
     * @security Vérification du rôle admin
     */
    public function index() {
        $this->checkAdminAccess();
        
        try {
            // Récupérer tous les menus actifs
            $menus = $this->menuModel->getAll();
            $totalMenus = $this->menuModel->count();
            
            // Générer le token CSRF pour les formulaires
            $csrfToken = $this->generateCSRFToken();
            
            $this->render("display-menus.phtml", "admin-layout.phtml", [
                'pageTitle' => 'Gestion des Menus',
                'currentPage' => 'admin-menus',
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
                        'title' => 'Menus',
                        'url' => 'index.php?action=admin-menus',
                        'icon' => 'fas fa-utensils'
                    ]
                ],
                'menus' => $menus,
                'totalMenus' => $totalMenus,
                'csrfToken' => $csrfToken
            ]);
            
        } catch (\Exception $e) {
            // Log de l'erreur pour le debugging
            error_log("Erreur affichage menus: " . $e->getMessage());
            
            // Message d'erreur pour l'utilisateur
            $_SESSION['error_message'] = 'Erreur lors du chargement des menus';
            $this->redirectTo('admin-home');
        }
    }
    
    /**
     * Affiche le formulaire de création/édition de menu
     * 
     * @route GET /admin/menus/create ou /admin/menus/{id}/edit
     * @param int|null $id L'ID du menu à modifier (null pour création)
     * @security Vérification du rôle admin
     */
    public function form($id = null) {
        $this->checkAdminAccess();
        
        $menu = null;
        $isEdit = false;
        
        // Si un ID est fourni, récupérer le menu pour édition
        if ($id) {
            try {
                $menu = $this->menuModel->findById($id);
                if (!$menu) {
                    $_SESSION['error_message'] = 'Menu non trouvé';
                    $this->redirectTo('admin-menus');
                }
                $isEdit = true;
            } catch (\Exception $e) {
                error_log("Erreur récupération menu: " . $e->getMessage());
                $_SESSION['error_message'] = 'Erreur lors du chargement du menu';
                $this->redirectTo('admin-menus');
            }
        }
        
        // Récupérer les catégories pour le formulaire
        try {
            $categories = $this->menuModel->getCategories();
        } catch (\Exception $e) {
            error_log("Erreur récupération catégories: " . $e->getMessage());
            $categories = [];
        }
        
        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();
        
        $this->render("form-menus.phtml", "admin-layout.phtml", [
            'pageTitle' => $isEdit ? 'Modifier le Menu' : 'Nouveau Menu',
            'currentPage' => 'admin-menus',
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
                    'title' => 'Menus',
                    'url' => 'index.php?action=admin-menus',
                    'icon' => 'fas fa-utensils'
                ],
                [
                    'title' => $isEdit ? 'Modifier' : 'Nouveau',
                    'url' => $isEdit ? 'index.php?action=admin-menus-edit&id=' . $id : 'index.php?action=admin-menus-create',
                    'icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus'
                ]
            ],
            'menu' => $menu,
            'categories' => $categories,
            'isEdit' => $isEdit,
            'csrfToken' => $csrfToken
        ]);
    }
    
    /**
     * Traite la création d'un nouveau menu
     * 
     * @route POST /admin/menus/store
     * @security Vérification CSRF et rôle admin
     */
    public function store() {
        $this->checkAdminAccess();
        
        // Vérifier le token CSRF
        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité. Veuillez réessayer.';
            $this->redirectTo('admin-menus');
        }
        
        try {
            // Validation des données
            $errors = $this->validateMenuData($_POST);
            if (!empty($errors)) {
                $_SESSION['validation_errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->redirectTo('admin-menus-create');
            }
            
            // Gérer l'upload d'image si un fichier est fourni
            $imageUrl = $this->handleImageUpload();
            if ($imageUrl) {
                $_POST['image'] = $imageUrl;
            }
            
            // Créer le menu
            $menuId = $this->menuModel->create($_POST);
            
            if ($menuId) {
                $_SESSION['success_message'] = 'Menu créé avec succès';
                $this->redirectTo('admin-menus');
            } else {
                throw new \Exception('Erreur lors de la création');
            }
            
        } catch (\InvalidArgumentException $e) {
            // Erreur de validation métier
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['old_input'] = $_POST;
            header('Location: index.php?action=admin-menus-create');
            exit;
            
        } catch (\Exception $e) {
            // Erreur technique
            error_log("Erreur création menu: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la création du menu';
            header('Location: index.php?action=admin-menus-create');
            exit;
        }
    }
    
    /**
     * Traite la mise à jour d'un menu existant
     * 
     * @route POST /admin/menus/{id}/update
     * @param int $id L'ID du menu à modifier
     * @security Vérification CSRF et rôle admin
     */
    public function update($id) {
        $this->checkAdminAccess();
        
        // Vérifier le token CSRF
        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité. Veuillez réessayer.';
            $this->redirectTo('admin-menus');
        }
        
        try {
            // Récupérer le menu existant pour préserver l'image
            $existingMenu = $this->menuModel->findById($id);
            if (!$existingMenu) {
                throw new \InvalidArgumentException('Menu non trouvé');
            }
            
            // Gérer l'upload d'image si un fichier est fourni
            $imageUrl = $this->handleImageUpload();
            if ($imageUrl) {
                $_POST['image'] = $imageUrl;
            } else {
                // Préserver l'image existante si aucun nouveau fichier
                $_POST['image'] = $existingMenu['image'];
            }
            
            // Validation des données
            $errors = $this->validateMenuData($_POST);
            if (!empty($errors)) {
                $_SESSION['validation_errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                header('Location: index.php?action=admin-menus-edit&id=' . $id);
                exit;
            }
            
            // Mettre à jour le menu
            $success = $this->menuModel->update($id, $_POST);
            
            if ($success) {
                $_SESSION['success_message'] = 'Menu modifié avec succès';
                $this->redirectTo('admin-menus');
            } else {
                throw new \Exception('Erreur lors de la mise à jour');
            }
            
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['old_input'] = $_POST;
            header('Location: index.php?action=admin-menus-edit&id=' . $id);
            exit;
            
        } catch (\Exception $e) {
            error_log("Erreur modification menu: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la modification du menu';
            header('Location: index.php?action=admin-menus-edit&id=' . $id);
            exit;
        }
    }
    
    /**
     * Supprime un menu (soft delete)
     * 
     * @route POST /admin/menus/{id}/delete
     * @param int $id L'ID du menu à supprimer
     * @security Vérification CSRF et rôle admin
     */
    public function delete($id) {
        $this->checkAdminAccess();
        
        // Vérifier le token CSRF
        if (!$this->checkCSRFToken()) {
            $_SESSION['error_message'] = 'Erreur de sécurité. Veuillez réessayer.';
            $this->redirectTo('admin-menus');
        }
        
        try {
            $success = $this->menuModel->delete($id);
            
            if ($success) {
                $_SESSION['success_message'] = 'Menu supprimé avec succès';
            } else {
                throw new \Exception('Erreur lors de la suppression');
            }
            
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error_message'] = $e->getMessage();
        } catch (\Exception $e) {
            error_log("Erreur suppression menu: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la suppression du menu';
        }
        
        $this->redirectTo('admin-menus');
    }
    
    /**
     * Valide les données d'un menu
     * 
     * @param array $data Les données à valider
     * @return array Liste des erreurs de validation
     * @security Validation côté serveur pour la sécurité
     */
    private function validateMenuData($data) {
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
        
        // Catégorie obligatoire
        if (empty($data['categories_id']) || !is_numeric($data['categories_id'])) {
            $errors['categories_id'] = 'La catégorie est obligatoire';
        }
        
        // Description optionnelle mais limitée
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'La description ne peut pas dépasser 1000 caractères';
        }
        
        return $errors;
    }
    
    /**
     * Affiche la liste publique des menus (disponibles uniquement)
     * 
     * @route GET /menus
     * @public Page accessible à tous les utilisateurs
     */
    public function publicIndex() {
        try {
            // Récupérer seulement les menus disponibles
            $menus = $this->menuModel->getAvailable();
            $totalMenus = count($menus);
            
            // Récupérer les catégories pour les filtres
            $categories = $this->menuModel->getCategories();
            
            $this->render("menus.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Menus Spéciaux',
                'menus' => $menus,
                'categories' => $categories,
                'totalMenus' => $totalMenus
            ]);
            
        } catch (\Exception $e) {
            // Log de l'erreur pour le debugging
            error_log("Erreur affichage menus publics: " . $e->getMessage());
            
            // Afficher la page même en cas d'erreur avec un message
            $this->render("menus.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Menus Spéciaux',
                'menus' => [],
                'categories' => [],
                'totalMenus' => 0,
                'error' => 'Erreur lors du chargement des menus'
            ]);
        }
    }
    
    /**
     * Gère l'upload d'image pour les menus
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
        $fileName = 'menu_' . uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = 'app/public/uploads/menus/' . $fileName;
        
        // Créer le dossier s'il n'existe pas
        $uploadDir = dirname($uploadPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return 'app/public/uploads/menus/' . $fileName;
        } else {
            throw new \Exception('Erreur lors de l\'upload de l\'image');
        }
    }
}
