<?php
namespace app\controllers;

use app\models\Drinks;
use app\middleware\Middleware;

/**
 * Contrôleur pour la gestion des boissons (CRUD complet)
 * 
 * @security Vérification du rôle admin sur toutes les actions
 * @csrf Protection CSRF sur toutes les actions de modification
 * @error-handling Gestion centralisée des erreurs avec messages flash
 */
class DrinksController extends Middleware {
    
    private $drinkModel;
    
    public function __construct() {
        $this->drinkModel = new Drinks();
    }
    
    /**
     * Vérifie que l'utilisateur est admin, sinon redirige
     * 
     * @security Protection contre l'accès non autorisé
     */
    protected function checkAdminAccess(): void {
        // Utiliser la méthode centralisée du Middleware
        parent::checkAdminAccess();
    }
    
    /**
     * Affiche la liste des boissons avec boutons d'action
     * 
     * @route GET /admin/boissons
     * @security Vérification du rôle admin
     */
    public function index() {
        $this->checkAdminAccess();
        
        try {
            // Récupérer toutes les boissons actives
            $drinks = $this->drinkModel->getAll();
            $totalDrinks = $this->drinkModel->count();
            
            // Générer le token CSRF pour les formulaires
            $csrfToken = $this->generateCSRFToken();
            
            $this->render("display-drinks.phtml", "admin-layout.phtml", [
                'pageTitle' => 'Gestion des Boissons',
                'currentPage' => 'admin-drinks',
                'breadcrumbs' => [
                    [
                        'title' => 'Dashboard',
                        'url' => 'index.php?action=admin-home'
                    ],
                    [
                        'title' => 'Catalogue',
                        'url' => 'index.php?action=admin-catalog'
                    ],
                    [
                        'title' => 'Boissons',
                        'url' => ''
                    ]
                ],
                'drinks' => $drinks,
                'totalDrinks' => $totalDrinks,
                'csrfToken' => $csrfToken
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur affichage boissons: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors du chargement des boissons';
            $this->redirectTo('admin-home');
        }
    }
    
    /**
     * Affiche le formulaire de création/modification
     * 
     * @route GET /admin/boissons/create ou /admin/boissons/edit/{id}
     * @security Vérification du rôle admin
     */
    public function form($id = null) {
        $this->checkAdminAccess();
        
        $isEdit = !is_null($id);
        $drink = null;
        
        if ($isEdit) {
            try {
                $drink = $this->drinkModel->findById($id);
                if (!$drink) {
                    $_SESSION['error_message'] = 'Boisson non trouvée';
                    $this->redirectTo('admin-drinks');
                }
            } catch (\Exception $e) {
                error_log("Erreur récupération boisson: " . $e->getMessage());
                $_SESSION['error_message'] = 'Erreur lors du chargement de la boisson';
                $this->redirectTo('admin-drinks');
            }
        }
        
        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();
        
        $this->render("form-drinks.phtml", "admin-layout.phtml", [
            'pageTitle' => $isEdit ? 'Modifier la Boisson' : 'Nouvelle Boisson',
            'currentPage' => 'admin-drinks',
            'breadcrumbs' => [
                [
                    'title' => 'Dashboard',
                    'url' => 'index.php?action=admin-home'
                ],
                [
                    'title' => 'Catalogue',
                    'url' => 'index.php?action=admin-catalog'
                ],
                [
                    'title' => 'Boissons',
                    'url' => 'index.php?action=admin-drinks'
                ],
                [
                    'title' => $isEdit ? 'Modifier' : 'Créer',
                    'url' => ''
                ]
            ],
            'isEdit' => $isEdit,
            'drink' => $drink,
            'csrfToken' => $csrfToken
        ]);
    }
    
    /**
     * Traite la création d'une nouvelle boisson
     * 
     * @route POST /admin/boissons/store
     * @security Vérification du rôle admin + CSRF
     */
    public function store() {
        $this->checkAdminAccess();
        
        // Vérification CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Token de sécurité invalide';
            $this->redirectTo('admin-drinks-create');
        }
        
        try {
            // Validation des données
            $validationErrors = $this->validateDrinkData($_POST);
            
            if (!empty($validationErrors)) {
                $_SESSION['validation_errors'] = $validationErrors;
                $_SESSION['old_input'] = $_POST;
                $this->redirectTo('admin-drinks-create');
            }
            
            // Gestion de l'upload d'image
            $imagePath = $this->handleImageUpload();
            if ($imagePath === false) {
                // Erreur d'upload, rediriger vers le formulaire
                header('Location: index.php?action=admin-drinks-create');
                exit;
            }
            
            // Ajouter le chemin de l'image aux données
            $_POST['image'] = $imagePath;
            
            // Créer la boisson
            $drinkId = $this->drinkModel->create($_POST);
            
            if ($drinkId) {
                $_SESSION['success_message'] = 'Boisson créée avec succès !';
                $this->redirectTo('admin-drinks');
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la création de la boisson';
                $this->redirectTo('admin-drinks-create');
            }
            
        } catch (\Exception $e) {
            error_log("Erreur création boisson: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la création de la boisson';
            $this->redirectTo('admin-drinks-create');
        }
    }
    
    /**
     * Traite la modification d'une boisson existante
     * 
     * @route POST /admin/boissons/update/{id}
     * @security Vérification du rôle admin + CSRF
     */
    public function update($id) {
        $this->checkAdminAccess();
        
        // Vérification CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Token de sécurité invalide';
            $this->redirectTo('admin-drinks-edit', ['id' => $id]);
        }
        
        try {
            // Récupérer la boisson existante pour préserver l'image
            $existingDrink = $this->drinkModel->findById($id);
            if (!$existingDrink) {
                $_SESSION['error_message'] = 'Boisson non trouvée';
                $this->redirectTo('admin-drinks');
            }
            
            // Validation des données
            $validationErrors = $this->validateDrinkData($_POST);
            
            if (!empty($validationErrors)) {
                $_SESSION['validation_errors'] = $validationErrors;
                $_SESSION['old_input'] = $_POST;
                header('Location: index.php?action=admin-drinks-edit&id=' . $id);
                exit;
            }
            
            // Gestion de l'upload d'image
            $imagePath = $this->handleImageUpload();
            if ($imagePath === false) {
                // Erreur d'upload, rediriger vers le formulaire
                header('Location: index.php?action=admin-drinks-edit&id=' . $id);
                exit;
            }
            
            // Si pas de nouvelle image, conserver l'ancienne
            if ($imagePath === null) {
                $_POST['image'] = $existingDrink['image'];
            } else {
                $_POST['image'] = $imagePath;
            }
            
            // Mettre à jour la boisson
            $success = $this->drinkModel->update($id, $_POST);
            
            if ($success) {
                $_SESSION['success_message'] = 'Boisson modifiée avec succès !';
                $this->redirectTo('admin-drinks');
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la modification de la boisson';
                header('Location: index.php?action=admin-drinks-edit&id=' . $id);
                exit;
            }
            
        } catch (\Exception $e) {
            error_log("Erreur modification boisson: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la modification de la boisson';
            header('Location: index.php?action=admin-drinks-edit&id=' . $id);
            exit;
        }
    }
    
    /**
     * Supprime une boisson
     * 
     * @route POST /admin/boissons/delete/{id}
     * @security Vérification du rôle admin + CSRF
     */
    public function delete($id) {
        $this->checkAdminAccess();
        
        // Vérification CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Token de sécurité invalide';
            $this->redirectTo('admin-drinks');
        }
        
        try {
            $success = $this->drinkModel->delete($id);
            
            if ($success) {
                $_SESSION['success_message'] = 'Boisson supprimée avec succès !';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la suppression de la boisson';
            }
            
        } catch (\Exception $e) {
            error_log("Erreur suppression boisson: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la suppression de la boisson';
        }
        
        $this->redirectTo('admin-drinks');
    }
    
    /**
     * Affiche la liste publique des boissons (disponibles uniquement)
     * 
     * @route GET /drinks
     * @public Page accessible à tous les utilisateurs
     */
    public function publicIndex() {
        try {
            // Récupérer seulement les boissons disponibles
            $drinks = $this->drinkModel->getAvailable();
            $totalDrinks = $this->drinkModel->countAvailable();
            
            $this->render("drinks.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Boissons Rafraîchissantes',
                'drinks' => $drinks,
                'totalDrinks' => $totalDrinks
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur affichage boissons publiques: " . $e->getMessage());
            
            $this->render("drinks.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Boissons Rafraîchissantes',
                'drinks' => [],
                'totalDrinks' => 0,
                'error' => 'Erreur lors du chargement des boissons'
            ]);
        }
    }
    
    /**
     * Valide les données du formulaire de boisson
     * 
     * @param array $data Les données à valider
     * @return array Liste des erreurs de validation
     * @security Validation côté serveur pour la sécurité
     */
    private function validateDrinkData($data) {
        $errors = [];
        
        // Nom obligatoire
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Le nom de la boisson est obligatoire';
        }
        
        // Prix obligatoire et positif
        if (empty($data['price']) || !is_numeric($data['price']) || (float)$data['price'] <= 0) {
            $errors['price'] = 'Le prix doit être un nombre positif';
        }
        
        // Stock obligatoire et entier positif
        if (!isset($data['stock_quantity']) || !is_numeric($data['stock_quantity']) || (int)$data['stock_quantity'] < 0) {
            $errors['stock_quantity'] = 'La quantité en stock doit être un nombre entier positif ou zéro';
        }
        
        return $errors;
    }
    
    /**
     * Gère l'upload d'image pour les boissons
     * 
     * @return string|null|false Chemin de l'image, null si pas d'upload, false si erreur
     * @security Validation du type et de la taille du fichier
     */
    private function handleImageUpload() {
        // Vérifier si un fichier a été uploadé
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // Pas d'image à uploader
        }
        
        // Vérifier les erreurs d'upload
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'Erreur lors de l\'upload de l\'image';
            return false;
        }
        
        // Vérifier la taille (max 2MB)
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $_SESSION['error_message'] = 'L\'image ne doit pas dépasser 2MB';
            return false;
        }
        
        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error_message'] = 'Format d\'image non supporté. Utilisez JPG, PNG ou WEBP';
            return false;
        }
        
        // Créer le dossier d'upload s'il n'existe pas
        $uploadDir = 'app/public/uploads/drinks/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'drink_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Déplacer le fichier
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
            return $filepath;
        } else {
            $_SESSION['error_message'] = 'Erreur lors de la sauvegarde de l\'image';
            return false;
        }
    }
}