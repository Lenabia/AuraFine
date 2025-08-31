<?php
// Test simple de la base de données
require('app/config/config.php');

echo "<h2>Test de la base de données</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie !</p>";
    
    // Test de la table users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table 'users' existe</p>";
        
        // Compter les utilisateurs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 Nombre d'utilisateurs dans la base : $count</p>";
        
        // Voir la structure de la table
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        echo "<h3>Structure de la table users :</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} - {$column['Null']} - {$column['Key']}</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>❌ Table 'users' n'existe pas</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion : " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test de la session</h2>";
session_start();
echo "<p>Session ID : " . session_id() . "</p>";
echo "<p>Session status : " . session_status() . "</p>";
echo "<p>Session data : " . print_r($_SESSION, true) . "</p>";
?>
