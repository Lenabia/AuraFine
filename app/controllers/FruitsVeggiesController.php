<?php
namespace app\controllers;

use app\models\FruitsVeggies;
use app\middleware\Middleware;

/**
 * Contrôleur pour la gestion des fruits et légumes (CRUD complet)
 * 
 * @security Vérification du rôle admin sur toutes les actions
 * @csrf Protection CSRF sur toutes les actions de modification
 * @error-handling Gestion centralisée des erreurs avec messages flash
 */
class FruitsVeggiesController extends Middleware {
    
    private $fruitVeggieModel;
    
    public function __construct() {
        $this->fruitVeggieModel = new FruitsVeggies();
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
     * Affiche la liste des fruits et légumes avec boutons d'action
     * 
     * @route GET /admin/fruits-legumes
     * @security Vérification du rôle admin
     */
    public function index() {
        $this->checkAdminAccess();
        
        try {
            // Récupérer tous les fruits et légumes actifs
            $fruitsVeggies = $this->fruitVeggieModel->getAll();
            $totalFruitsVeggies = $this->fruitVeggieModel->count();
            
            // Générer le token CSRF pour les formulaires
            $csrfToken = $this->generateCSRFToken();
            
            $this->render("display-fruits-veggies.phtml", "admin-layout.phtml", [
                'pageTitle' => 'Gestion des Fruits & Légumes',
                'currentPage' => 'admin-fruits-legumes',
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
                        'title' => 'Fruits & Légumes',
                        'url' => ''
                    ]
                ],
                'fruitsVeggies' => $fruitsVeggies,
                'totalFruitsVeggies' => $totalFruitsVeggies,
                'csrfToken' => $csrfToken
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur affichage fruits et légumes: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors du chargement des fruits et légumes';
            $this->redirectTo('admin-home');
        }
    }
    
    /**
     * Affiche le formulaire de création/modification
     * 
     * @route GET /admin/fruits-legumes/create ou /admin/fruits-legumes/edit/{id}
     * @security Vérification du rôle admin
     */
    public function form($id = null) {
        $this->checkAdminAccess();
        
        $isEdit = !is_null($id);
        $fruitVeggie = null;
        
        if ($isEdit) {
            try {
                $fruitVeggie = $this->fruitVeggieModel->findById($id);
                if (!$fruitVeggie) {
                    $_SESSION['error_message'] = 'Fruit/légume non trouvé';
                    $this->redirectTo('admin-fruits-legumes');
                }
            } catch (\Exception $e) {
                error_log("Erreur récupération fruit/légume: " . $e->getMessage());
                $_SESSION['error_message'] = 'Erreur lors du chargement du fruit/légume';
                $this->redirectTo('admin-fruits-legumes');
            }
        }
        
        // Générer le token CSRF
        $csrfToken = $this->generateCSRFToken();
        
        $this->render("form-fruits-veggies.phtml", "admin-layout.phtml", [
            'pageTitle' => $isEdit ? 'Modifier le Fruit/Légume' : 'Nouveau Fruit/Légume',
            'currentPage' => 'admin-fruits-legumes',
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
                    'title' => 'Fruits & Légumes',
                    'url' => 'index.php?action=admin-fruits-legumes'
                ],
                [
                    'title' => $isEdit ? 'Modifier' : 'Créer',
                    'url' => ''
                ]
            ],
            'isEdit' => $isEdit,
            'fruitVeggie' => $fruitVeggie,
            'csrfToken' => $csrfToken
        ]);
    }
    
    /**
     * Traite la création d'un nouveau fruit/légume
     * 
     * @route POST /admin/fruits-legumes/store
     * @security Vérification du rôle admin + CSRF
     */
    public function store() {
        $this->checkAdminAccess();
        
        // Vérification CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Token de sécurité invalide';
            $this->redirectTo('admin-fruits-legumes-create');
        }
        
        try {
            // Validation des données
            $validationErrors = $this->validateFruitVeggieData($_POST);
            
            if (!empty($validationErrors)) {
                $_SESSION['validation_errors'] = $validationErrors;
                $_SESSION['old_input'] = $_POST;
                $this->redirectTo('admin-fruits-legumes-create');
            }
            
            // Gestion de l'upload d'image
            $imagePath = $this->handleImageUpload();
            if ($imagePath === false) {
                // Erreur d'upload, rediriger vers le formulaire
                header('Location: index.php?action=admin-fruits-legumes-create');
                exit;
            }
            
            // Ajouter le chemin de l'image aux données
            $_POST['image'] = $imagePath;
            
            // Créer le fruit/légume
            $fruitVeggieId = $this->fruitVeggieModel->create($_POST);
            
            if ($fruitVeggieId) {
                $_SESSION['success_message'] = 'Fruit/légume créé avec succès !';
                $this->redirectTo('admin-fruits-legumes');
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la création du fruit/légume';
                $this->redirectTo('admin-fruits-legumes-create');
            }
            
        } catch (\Exception $e) {
            error_log("Erreur création fruit/légume: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la création du fruit/légume';
            $this->redirectTo('admin-fruits-legumes-create');
        }
    }
    
    /**
     * Traite la modification d'un fruit/légume existant
     * 
     * @route POST /admin/fruits-legumes/update/{id}
     * @security Vérification du rôle admin + CSRF
     */
    public function update($id) {
        $this->checkAdminAccess();
        
        // Vérification CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Token de sécurité invalide';
            $this->redirectTo('admin-fruits-legumes-edit', ['id' => $id]);
        }
        
        try {
            // Récupérer le fruit/légume existant pour préserver l'image
            $existingFruitVeggie = $this->fruitVeggieModel->findById($id);
            if (!$existingFruitVeggie) {
                $_SESSION['error_message'] = 'Fruit/légume non trouvé';
                $this->redirectTo('admin-fruits-legumes');
            }
            
            // Validation des données
            $validationErrors = $this->validateFruitVeggieData($_POST);
            
            if (!empty($validationErrors)) {
                $_SESSION['validation_errors'] = $validationErrors;
                $_SESSION['old_input'] = $_POST;
                header('Location: index.php?action=admin-fruits-legumes-edit&id=' . $id);
                exit;
            }
            
            // Gestion de l'upload d'image
            $imagePath = $this->handleImageUpload();
            if ($imagePath === false) {
                // Erreur d'upload, rediriger vers le formulaire
                header('Location: index.php?action=admin-fruits-legumes-edit&id=' . $id);
                exit;
            }
            
            // Si pas de nouvelle image, conserver l'ancienne
            if ($imagePath === null) {
                $_POST['image'] = $existingFruitVeggie['image'];
            } else {
                $_POST['image'] = $imagePath;
            }
            
            // Mettre à jour le fruit/légume
            $success = $this->fruitVeggieModel->update($id, $_POST);
            
            if ($success) {
                $_SESSION['success_message'] = 'Fruit/légume modifié avec succès !';
                $this->redirectTo('admin-fruits-legumes');
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la modification du fruit/légume';
                header('Location: index.php?action=admin-fruits-legumes-edit&id=' . $id);
                exit;
            }
            
        } catch (\Exception $e) {
            error_log("Erreur modification fruit/légume: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la modification du fruit/légume';
            header('Location: index.php?action=admin-fruits-legumes-edit&id=' . $id);
            exit;
        }
    }
    
    /**
     * Supprime un fruit/légume
     * 
     * @route POST /admin/fruits-legumes/delete/{id}
     * @security Vérification du rôle admin + CSRF
     */
    public function delete($id) {
        $this->checkAdminAccess();
        
        // Vérification CSRF
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Token de sécurité invalide';
            $this->redirectTo('admin-fruits-legumes');
        }
        
        try {
            $success = $this->fruitVeggieModel->delete($id);
            
            if ($success) {
                $_SESSION['success_message'] = 'Fruit/légume supprimé avec succès !';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la suppression du fruit/légume';
            }
            
        } catch (\Exception $e) {
            error_log("Erreur suppression fruit/légume: " . $e->getMessage());
            $_SESSION['error_message'] = 'Erreur lors de la suppression du fruit/légume';
        }
        
        $this->redirectTo('admin-fruits-legumes');
    }
    
    /**
     * Affiche la liste publique des fruits et légumes (disponibles uniquement)
     * 
     * @route GET /fruits-veggies
     * @public Page accessible à tous les utilisateurs
     */
    public function publicIndex() {
        try {
            // Récupérer seulement les fruits et légumes disponibles
            $fruitsVeggies = $this->fruitVeggieModel->getAvailable();
            $totalFruitsVeggies = $this->fruitVeggieModel->countAvailable();
            
            $this->render("fruits-veggies.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Fruits & Légumes Frais',
                'fruitsVeggies' => $fruitsVeggies,
                'totalFruitsVeggies' => $totalFruitsVeggies
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur affichage fruits et légumes publics: " . $e->getMessage());
            
            $this->render("fruits-veggies.phtml", "layout.phtml", [
                'pageTitle' => 'Nos Fruits & Légumes Frais',
                'fruitsVeggies' => [],
                'totalFruitsVeggies' => 0,
                'error' => 'Erreur lors du chargement des fruits et légumes'
            ]);
        }
    }
    
    /**
     * Valide les données du formulaire de fruit/légume
     * 
     * @param array $data Les données à valider
     * @return array Liste des erreurs de validation
     * @security Validation côté serveur pour la sécurité
     */
    private function validateFruitVeggieData($data) {
        $errors = [];
        
        // Nom obligatoire
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Le nom du fruit/légume est obligatoire';
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
     * Gère l'upload d'image pour les fruits et légumes
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
        $uploadDir = 'app/public/uploads/fruits-veggies/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'fruit_veggie_' . uniqid() . '.' . $extension;
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