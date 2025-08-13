# Page Panier - Documentation

## Vue d'ensemble

La page panier (`panier.phtml`) est maintenant prête avec une structure HTML propre et des styles CSS complets. Elle est conçue pour recevoir du contenu dynamique via JavaScript.

## Structure HTML

- **Hero Section** : Titre et description de la page
- **Section Principale** :
  - Conteneur pour les articles du panier (`#panier-articles-container`)
  - Message panier vide (`#panier-vide`)
  - Résumé de commande avec totaux
- **Section Recommandations** : Grille pour les suggestions de produits

## CSS

- Styles complets et responsifs (mobile-first)
- Utilise les couleurs et mixins existants du projet
- Design moderne et cohérent avec le reste du site

## JavaScript

**Toute la logique du panier est maintenant dans `card.js`** :

- ✅ Ajout d'articles au panier
- ✅ Gestion des quantités
- ✅ Stockage localStorage
- ✅ Compteur du panier
- ✅ **NOUVEAU** : Affichage de la page panier
- ✅ **NOUVEAU** : Modification des quantités dans le panier
- ✅ **NOUVEAU** : Suppression d'articles du panier
- ✅ **NOUVEAU** : Calcul automatique des totaux

## Fonctionnalités du Panier

1. **Affichage dynamique** : Les articles sont récupérés du localStorage
2. **Gestion des quantités** : Boutons +/- pour modifier les quantités
3. **Suppression** : Bouton poubelle pour retirer des articles
4. **Totaux automatiques** : Calcul en temps réel du sous-total et total
5. **Panier vide** : Message approprié quand le panier est vide
6. **Synchronisation** : Le compteur du header se met à jour automatiquement

## Utilisation Future

1. **Images dynamiques** : Récupérer les vraies images des produits
2. **Descriptions** : Ajouter les vraies descriptions des articles
3. **Catégories** : Afficher les vraies catégories et calories
4. **Commande** : Intégration avec un système de paiement
5. **Recommandations** : Système de suggestions basé sur l'historique

## Routes

- **URL** : `index.php?action=panier`
- **Contrôleur** : `UsersController::displayPanierPage()`
- **Vue** : `panier.phtml`
- **Layout** : `layout.phtml`
- **JavaScript** : `card.js` (gestion complète du panier)

## Navigation

- L'icône panier dans le header est maintenant un lien cliquable
- Boutons "Continuer mes achats" redirigent vers l'accueil
- Bouton "Commander" affiche un message temporaire

## Responsive

- Design mobile-first avec breakpoints `min-width`
- Adaptation automatique pour tablette et desktop
- Grille flexible pour les articles et recommandations

## Architecture Technique

- **Un seul fichier JS** : `card.js` gère tout le système de panier
- **localStorage** : Persistance des données du panier
- **Événements** : Gestion des clics et modifications
- **DOM dynamique** : Création et mise à jour des éléments HTML
- **Synchronisation** : Mise à jour automatique de tous les composants
