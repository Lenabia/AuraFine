<?php
// Debug de la session et de la connexion
session_start();

echo "<h2>🔍 DEBUG SESSION ET CONNEXION</h2>";

echo "<h3>📊 État de la session :</h3>";
echo "<p>Session ID : " . session_id() . "</p>";
echo "<p>Session status : " . session_status() . "</p>";
echo "<p>Session data :</p>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>👤 Vérification utilisateur :</h3>";
if (isset($_SESSION['user'])) {
    echo "<p style='color: green;'>✅ Session utilisateur trouvée !</p>";
    echo "<p>ID : " . ($_SESSION['user']['id'] ?? 'N/A') . "</p>";
    echo "<p>Prénom : " . ($_SESSION['user']['first_name'] ?? 'N/A') . "</p>";
    echo "<p>Rôle : " . ($_SESSION['user']['role'] ?? 'N/A') . "</p>";
    echo "<p>Points fidélité : " . ($_SESSION['user']['loyalty_points'] ?? 'N/A') . "</p>";
    
    // Test de la condition du layout
    if ($_SESSION['user']['role'] === 'user') {
        echo "<p style='color: green;'>✅ Condition 'role === user' est VRAIE</p>";
    } else {
        echo "<p style='color: red;'>❌ Condition 'role === user' est FAUSSE</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Aucune session utilisateur trouvée</p>";
}

echo "<h3>🔐 Test de connexion :</h3>";
echo "<p>Essayez de vous connecter avec ce formulaire :</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h4>Données POST reçues :</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Test de connexion simple
    if (isset($_POST['email']) && isset($_POST['password'])) {
        echo "<p>Email : " . htmlspecialchars($_POST['email']) . "</p>";
        echo "<p>Mot de passe : " . str_repeat('*', strlen($_POST['password'])) . "</p>";
        
        // Rediriger vers le contrôleur de connexion
        header('Location: index.php?action=login');
        exit;
    }
}
?>

<form method="POST" action="">
    <h4>Test de connexion :</h4>
    <p>
        <label>Email : <input type="email" name="email" required></label>
    </p>
    <p>
        <label>Mot de passe : <input type="password" name="password" required></label>
    </p>
    <p>
        <button type="submit">Tester la connexion</button>
    </p>
</form>

<hr>
<h3>🔗 Liens de test :</h3>
<p><a href="index.php?action=home">Accueil</a></p>
<p><a href="index.php?action=login">Page de connexion</a></p>
<p><a href="index.php?action=register">Page d'inscription</a></p>
<p><a href="debug_session.php">Rafraîchir cette page</a></p>
