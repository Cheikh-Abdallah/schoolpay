<?php

declare(strict_types=1);

/**
 * Vue : Formulaire d'acceptation d'invitation et création d'école
 * 
 * @var array $invitation
 * @var string $token
 */
?>

<div style="margin-bottom: 24px; text-align: center;">
    <div class="badge badge-warning" style="margin-bottom: 12px; font-size: 0.85rem; padding: 6px 14px;">
        ✨ Programme Établissement Pilote · 72h Offertes
    </div>
    <h2 class="auth-title" style="margin-top: 0;">Configurez votre Établissement</h2>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 6px; line-height: 1.4;">
        Bienvenue ! Vous avez été convié par <strong>HOME SCHOOL</strong>. Complétez ce formulaire pour initialiser votre espace de gestion sécurisé.
    </p>
</div>

<form action="<?= url('/invitation/accept') ?>" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin-bottom: 20px;">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <span>🏫</span> 1. Coordonnées de l'Établissement
        </h3>

        <div class="form-group">
            <label for="school_name" class="form-label">Nom de l'école ou institut *</label>
            <input 
                type="text" 
                id="school_name" 
                name="school_name" 
                class="form-control" 
                placeholder="Ex : Groupe Scolaire Excellence" 
                value="<?= e(old('school_name')) ?>" 
                required
            >
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
                <label for="phone" class="form-label">Téléphone officiel *</label>
                <input 
                    type="text" 
                    id="phone" 
                    name="phone" 
                    class="form-control" 
                    placeholder="77 123 45 67" 
                    value="<?= e(old('phone')) ?>" 
                    required
                >
            </div>
            <div class="form-group">
                <label for="city" class="form-label">Ville *</label>
                <input 
                    type="text" 
                    id="city" 
                    name="city" 
                    class="form-control" 
                    placeholder="Dakar, Thiès..." 
                    value="<?= e(old('city', 'Dakar')) ?>" 
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="address" class="form-label">Adresse physique *</label>
            <input 
                type="text" 
                id="address" 
                name="address" 
                class="form-control" 
                placeholder="Ex : Liberté 6, Villa n° 45" 
                value="<?= e(old('address')) ?>" 
                required
            >
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="ninea" class="form-label">NINEA (Optionnel)</label>
                <input 
                    type="text" 
                    id="ninea" 
                    name="ninea" 
                    class="form-control" 
                    placeholder="Numéro NINEA" 
                    value="<?= e(old('ninea')) ?>"
                >
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="rccm" class="form-label">RCCM (Optionnel)</label>
                <input 
                    type="text" 
                    id="rccm" 
                    name="rccm" 
                    class="form-control" 
                    placeholder="Numéro RCCM" 
                    value="<?= e(old('rccm')) ?>"
                >
            </div>
        </div>
    </div>

    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px;">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <span>👤</span> 2. Compte Administrateur Principal (Directeur / Fondateur)
        </h3>

        <div class="form-group">
            <label for="admin_name" class="form-label">Nom et Prénom du Responsable *</label>
            <input 
                type="text" 
                id="admin_name" 
                name="admin_name" 
                class="form-control" 
                placeholder="Ex : Moussa Diop" 
                value="<?= e(old('admin_name')) ?>" 
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label">Email de connexion (associé à l'invitation)</label>
            <input 
                type="email" 
                class="form-control" 
                value="<?= e($invitation['invited_email']) ?>" 
                disabled 
                style="background:#e2e8f0; color:#475569; font-weight:600;"
            >
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="password" class="form-label">Mot de passe *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Min. 8 caractères" 
                    required
                >
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="password_confirmation" class="form-label">Confirmez *</label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    class="form-control" 
                    placeholder="Répétez" 
                    required
                >
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem; font-weight: 700;">
        🚀 Créer mon école et démarrer l'essai (72h gratuites)
    </button>
</form>

<div style="margin-top: 20px; text-align: center;">
    <p style="font-size: 0.8rem; color: var(--text-muted);">
        En validant ce formulaire, vous acceptez les conditions d'utilisation de SCHOOLPAY éditées par HOME SCHOOL.
    </p>
</div>
