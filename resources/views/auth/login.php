<?php

declare(strict_types=1);

/**
 * Vue : Formulaire de connexion SchoolPay
 * 
 * @var bool $isSuperAdmin
 */
$actionUrl = $isSuperAdmin ? url('/superadmin/login') : url('/login');
?>

<div style="margin-bottom: 24px; text-align: center;">
    <h2 class="auth-title">
        <?= $isSuperAdmin ? 'Espace Plateforme HOME SCHOOL' : 'Connexion Établissement' ?>
    </h2>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">
        <?= $isSuperAdmin ? 'Accès sécurisé à l\'administration centrale SaaS' : 'Gérez vos paiements scolaires, reçus et SMS' ?>
    </p>
</div>

<form action="<?= $actionUrl ?>" method="POST">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="email" class="form-label">Adresse Email Professionnelle</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="direction@votre-ecole.sn" 
            value="<?= e(old('email')) ?>" 
            required 
            autofocus
        >
    </div>

    <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label for="password" class="form-label" style="margin-bottom: 0;">Mot de passe</label>
            <?php if (!$isSuperAdmin): ?>
                <a href="<?= url('/forgot-password') ?>" style="font-size: 0.8rem; color: var(--text-muted);">Mot de passe oublié ?</a>
            <?php endif; ?>
        </div>
        <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-control" 
            placeholder="••••••••••••" 
            required
        >
    </div>

    <button type="submit" class="btn <?= $isSuperAdmin ? 'btn-primary' : 'btn-primary' ?>" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
        <?= $isSuperAdmin ? '🛡️ Se connecter au Super Admin' : '🚀 Accéder à mon établissement' ?>
    </button>
</form>

<?php if (!$isSuperAdmin): ?>
    <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
        <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
            L'inscription à SchoolPay se fait <strong>exclusivement sur invitation</strong> de HOME SCHOOL.<br>
            Vous êtes un établissement pilote ? Vérifiez le lien reçu par email.
        </p>
    </div>
<?php else: ?>
    <div style="margin-top: 28px; text-align: center;">
        <a href="<?= url('/login') ?>" style="font-size: 0.85rem; color: var(--text-muted);">
            ← Retour à l'espace établissement
        </a>
    </div>
<?php endif; ?>
