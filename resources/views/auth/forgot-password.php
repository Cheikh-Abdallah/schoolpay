<?php

declare(strict_types=1);

/**
 * Vue : Demande de réinitialisation de mot de passe
 */
?>

<div style="margin-bottom: 24px; text-align: center;">
    <h2 class="auth-title">Mot de passe oublié</h2>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 6px;">
        Indiquez votre adresse email professionnelle pour réinitialiser vos identifiants d'accès.
    </p>
</div>

<form action="<?= url('/forgot-password') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="email" class="form-label">Adresse Email Professionnelle</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="votre-email@etablissement.sn" 
            required 
            autofocus
        >
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
        📨 Envoyer les instructions
    </button>
</form>

<div style="margin-top: 24px; text-align: center;">
    <a href="<?= url('/login') ?>" style="font-size: 0.85rem; color: var(--text-muted);">
        ← Retour à l'écran de connexion
    </a>
</div>
