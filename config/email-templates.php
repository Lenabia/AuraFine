<?php
/**
 * Configuration des templates email
 * Centralisé pour faciliter la maintenance
 */

return [
    'welcome' => [
        'subject' => 'Bienvenue sur AuraFine ! 🎉',
        'template' => '
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #2c5530; font-size: 28px;">Bienvenue sur AuraFine !</h1>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="font-size: 16px; color: #333; margin: 0;">Bonjour <strong>{{first_name}}</strong> !</p>
                <p style="font-size: 16px; color: #333; margin: 10px 0 0 0;">Votre compte AuraFine a été créé avec succès.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{profile_url}}" style="background: #2c5530; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Accéder à mon profil</a>
            </div>
            
            <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0; color: #2c5530; font-size: 14px;"><strong>💡 Astuce :</strong> Parrainez vos amis et gagnez des points de fidélité !</p>
            </div>
        '
    ],
    
    'password_reset' => [
        'subject' => 'Réinitialisation de votre mot de passe AuraFine',
        'template' => '
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #2c5530; font-size: 24px;">Réinitialisation de mot de passe</h1>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="font-size: 16px; color: #333; margin: 0;">Bonjour <strong>{{first_name}}</strong>,</p>
                <p style="font-size: 16px; color: #333; margin: 10px 0 0 0;">Vous avez demandé la réinitialisation de votre mot de passe AuraFine.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{reset_url}}" style="background: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Réinitialiser mon mot de passe</a>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
                <p style="margin: 0; color: #856404; font-size: 14px;"><strong>⏰ Important :</strong> Ce lien expire dans 15 minutes pour votre sécurité.</p>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;">Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            </div>
        '
    ],
    
    'password_reset_confirm' => [
        'subject' => 'Mot de passe modifié avec succès ✅',
        'template' => '
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #28a745; font-size: 24px;">Mot de passe modifié !</h1>
            </div>
            
            <div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
                <p style="font-size: 16px; color: #155724; margin: 0;">Bonjour <strong>{{first_name}}</strong>,</p>
                <p style="font-size: 16px; color: #155724; margin: 10px 0 0 0;">Votre mot de passe AuraFine a été modifié avec succès.</p>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;"><strong>🛡️ Sécurité :</strong> Si vous n\'avez pas effectué cette modification, contactez-nous immédiatement.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{login_url}}" style="background: #2c5530; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Se connecter</a>
            </div>
        '
    ],
    
    'order_confirmation' => [
        'subject' => 'Commande confirmée #{{order_number}} 🛒',
        'template' => '
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #2c5530; font-size: 24px;">Commande confirmée !</h1>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="font-size: 16px; color: #333; margin: 0;">Bonjour <strong>{{first_name}}</strong>,</p>
                <p style="font-size: 16px; color: #333; margin: 10px 0 0 0;">Votre commande <strong>#{{order_number}}</strong> a été confirmée avec succès.</p>
            </div>
            
            <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0; color: #2c5530; font-size: 16px;"><strong>💰 Total :</strong> {{total}} FCFA</p>
                <p style="margin: 5px 0 0 0; color: #2c5530; font-size: 16px;"><strong>🚚 Livraison :</strong> {{delivery_date}}</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{order_url}}" style="background: #2c5530; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Voir ma commande</a>
            </div>
        '
    ]
];


