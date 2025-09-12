<!-- ========================================
PAGE ACCÈS REFUSÉ - SÉCURITÉ
======================================== -->

<section class="access-denied">
    <header class="denied-header">
        <figure class="denied-icon">
            <i class="fas fa-lock"></i>
        </figure>
        <h1>Accès Refusé</h1>
        <p>Vous n'avez pas les permissions nécessaires pour accéder à cette page</p>
    </header>
    
    <section class="denied-content">
        <div class="denied-message">
            <h2>Accès Administrateur Requis</h2>
            <p>Cette section est réservée aux administrateurs du site.</p>
            <p>Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur.</p>
        </div>
        
        <nav class="denied-actions">
            <a href="index.php?action=home" class="btn-home">
                <i class="fas fa-home"></i>
                Retour à l'accueil
            </a>
            
            <a href="index.php?action=login" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </a>
        </nav>
    </section>
    
    <footer class="denied-footer">
        <p>Code d'erreur: 403 - Forbidden</p>
    </footer>
</section>
