<?php
/**
 * Test final du système d'inscription
 */

// Charger la configuration
require('app/config/config.php');

// Démarrer la session
session_start();

echo "<h1>🎯 Test final du système d'inscription</h1>";

echo "<h2>📋 Instructions de test</h2>";
echo "<ol>";
echo "<li><strong>Cliquez sur le lien ci-dessous</strong> pour aller au formulaire d'inscription</li>";
echo "<li><strong>Soumettez le formulaire VIDE</strong> (sans remplir aucun champ)</li>";
echo "<li><strong>Vous devriez voir :</strong></li>";
echo "<ul>";
echo "<li>Message global : 'Veuillez remplir les champs suivants : ...'</li>";
echo "<li>Erreurs sous chaque champ obligatoire</li>";
echo "<li>Le formulaire ne doit PAS se soumettre</li>";
echo "</ul>";
echo "<li><strong>Si ça fonctionne</strong>, testez avec des données valides</li>";
echo "</ol>";

echo "<hr>";
echo "<h2>🔗 Test du formulaire</h2>";
echo "<a href='index.php?action=register' target='_blank'>📝 Ouvrir le formulaire d'inscription</a><br>";

echo "<hr>";
echo "<h2>🧪 Test de validation côté serveur</h2>";
echo "<p>Ce test simule la soumission d'un formulaire vide :</p>";

// Simuler des données POST vides
$_POST = [
    'csrf_token' => 'test_token',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'password' => '',
    'password_confirm' => '',
    'cities_id' => '',
    'phone' => ''
];

// Utiliser l'autoloader
spl_autoload_register(function($class) {
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    $controller = new \app\controllers\UsersController();
    
    // Utiliser la réflexion pour tester la validation
    $reflection = new ReflectionClass($controller);
    $validateMethod = $reflection->getMethod('validateRegistration');
    $validateMethod->setAccessible(true);
    
    $errors = $validateMethod->invoke($controller, $_POST);
    
    echo "<h3>✅ Erreurs de validation détectées :</h3>";
    echo "<pre>";
    print_r($errors);
    echo "</pre>";
    
    // Stocker en session pour le test
    $_SESSION['validation_errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    
    echo "<h3>📦 Données stockées en session</h3>";
    echo "<p>Maintenant, quand vous allez sur le formulaire, vous devriez voir ces erreurs !</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Erreur lors du test</h3>";
    echo $e->getMessage();
}

echo "<hr>";
echo "<h2>🧹 Nettoyer la session</h2>";
echo "<a href='test_final.php?clean=1'>🧹 Nettoyer la session</a><br>";

// Nettoyer la session si demandé
if (isset($_GET['clean'])) {
    unset($_SESSION['validation_errors']);
    unset($_SESSION['old_input']);
    echo "<p>✅ Session nettoyée !</p>";
}
?>
