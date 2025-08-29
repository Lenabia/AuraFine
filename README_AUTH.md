# 🔐 Système d'Authentification AuraFine - Approche Hybride

## 🎯 **Vue d'ensemble**

Ce système d'authentification combine le meilleur des deux approches :

- **Architecture MVC moderne** (votre approche originale)
- **Validation directe et simple** (inspirée de l'autre projet)

## 🏗️ **Architecture**

### **Structure des fichiers :**

```
app/
├── controllers/
│   └── UsersController.php      # Contrôleur principal avec validation intégrée
├── Models/
│   ├── Database.php            # Connexion à la base de données
│   └── User.php                # Modèle utilisateur
├── views/
│   ├── register.phtml          # Formulaire d'inscription
│   └── login.phtml             # Formulaire de connexion
├── middleware/
│   └── Middleware.php          # Middleware de base avec CSRF
└── public/css/
    └── auth.css                # Styles pour les messages d'erreur/succès
```

## 🚀 **Fonctionnalités**

### **✅ Inscription :**

- Validation des champs requis
- Validation du format email
- Validation de la force du mot de passe (8 caractères minimum)
- Vérification de l'unicité de l'email
- Validation des relations géographiques (ville, zone, quartier)
- Messages d'erreur contextuels
- Conservation des données saisies en cas d'erreur

### **✅ Connexion :**

- Validation des identifiants
- Création de session sécurisée
- Protection CSRF
- Messages d'erreur clairs

### **✅ Sécurité :**

- Hachage des mots de passe avec `password_hash()`
- Protection CSRF avec tokens
- Régénération d'ID de session
- Logs de sécurité
- Validation côté serveur

## 🔧 **Comment ça marche**

### **1. Validation directe dans le contrôleur :**

```php
private function validateRegistration($data) {
    $errors = [];

    // Vérification des champs requis
    $requiredFields = ['first_name', 'last_name', 'email', 'password', 'password_confirm', 'cities_id'];
    foreach ($requiredFields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[$field] = $this->getFieldLabel($field) . ' est obligatoire.';
        }
    }

    // Validation spécifique (regex, format, etc.)
    if (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s-]{2,}$/', trim($data['first_name']))) {
        $errors['first_name'] = 'Le prénom doit contenir au moins 2 lettres, sans caractères spéciaux.';
    }

    return $errors;
}
```

### **2. Gestion des erreurs côté serveur :**

```php
if (!empty($errors)) {
    // Stockage des erreurs et anciennes valeurs en session
    $_SESSION['validation_errors'] = $errors;
    $_SESSION['old_input'] = $_POST;

    // Redirection vers le formulaire
    header('Location: index.php?action=register');
    exit;
}
```

### **3. Affichage des erreurs dans les vues :**

```php
<!-- Erreur générale -->
<?php if (isset($errors['general'])): ?>
    <div class="auth-error" role="alert">
        <?= htmlspecialchars($errors['general']) ?>
    </div>
<?php endif; ?>

<!-- Erreur de champ spécifique -->
<div class="auth-error" aria-live="polite">
    <?= $errors['first_name'] ?? '' ?>
</div>

<!-- Conservation des valeurs -->
<input
    type="text"
    name="first_name"
    value="<?= htmlspecialchars($old['first_name'] ?? '') ?>"
>
```

## 📋 **Routes disponibles**

| Action     | URL                         | Méthode  | Description    |
| ---------- | --------------------------- | -------- | -------------- |
| `home`     | `index.php?action=home`     | GET      | Page d'accueil |
| `register` | `index.php?action=register` | GET/POST | Inscription    |
| `login`    | `index.php?action=login`    | GET/POST | Connexion      |
| `logout`   | `index.php?action=logout`   | GET      | Déconnexion    |

## 🎨 **Personnalisation**

### **Styles CSS :**

Le fichier `app/public/css/auth.css` contient les styles pour :

- Messages d'erreur (rouge)
- Messages de succès (vert)
- Animations d'apparition
- Design responsive

### **Validation :**

Modifiez les méthodes `validateRegistration()` et `validateLogin()` dans `UsersController.php` pour :

- Ajouter de nouvelles règles de validation
- Modifier les messages d'erreur
- Changer les critères de validation

## 🧪 **Tests**

### **Fichier de test :**

Exécutez `test_auth.php` pour vérifier que tout fonctionne :

```bash
php test_auth.php
```

### **Tests manuels :**

1. **Inscription avec champs vides** → Vérifier les messages d'erreur
2. **Inscription avec email existant** → Vérifier le message d'unicité
3. **Inscription réussie** → Vérifier la redirection vers login
4. **Connexion avec identifiants incorrects** → Vérifier les messages d'erreur
5. **Connexion réussie** → Vérifier la redirection vers l'accueil

## 🔒 **Sécurité**

### **Mesures implémentées :**

- ✅ Hachage des mots de passe
- ✅ Protection CSRF
- ✅ Validation côté serveur
- ✅ Échappement des données affichées
- ✅ Régénération d'ID de session
- ✅ Logs de sécurité

### **Bonnes pratiques :**

- Toujours utiliser `htmlspecialchars()` pour l'affichage
- Valider ET nettoyer toutes les données d'entrée
- Utiliser des requêtes préparées (déjà fait dans le modèle)
- Logger les événements de sécurité

## 🚀 **Déploiement**

1. **Vérifier la configuration** : `app/config/config.php`
2. **Tester la base de données** : Exécuter `test_auth.php`
3. **Vérifier les permissions** : Dossiers `app/` et `app/public/`
4. **Tester les formulaires** : Inscription et connexion

## 📝 **Maintenance**

### **Ajouter un nouveau champ :**

1. Modifier la méthode `validateRegistration()`
2. Ajouter le champ dans `sanitizeRegistrationData()`
3. Mettre à jour la vue `register.phtml`
4. Ajouter le label dans `getFieldLabel()`

### **Modifier la validation :**

Éditer directement les méthodes de validation dans `UsersController.php` :

- `validateRegistration()` pour l'inscription
- `validateLogin()` pour la connexion

---

**🎉 Votre système d'authentification est maintenant simple, sécurisé et maintenable !**
