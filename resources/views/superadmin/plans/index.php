<?php

declare(strict_types=1);

/**
 * Vue : Gestion des forfaits et tarifs SaaS HOME SCHOOL
 * 
 * @var array $plans
 */
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-main);">Gestion des Forfaits & Tarifs SaaS</h1>
        <p style="color:var(--text-muted); font-size:0.88rem;">
            Configurez les prix mensuels, les quotas d'élèves et les dotations SMS de chaque offre SchoolPay.
        </p>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:24px;">
    <?php foreach ($plans as $plan): ?>
        <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div class="card-header" style="background:#f8fafc;">
                    <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                        <h2 class="card-title" style="color:var(--primary); font-size:1.2rem;"><?= e($plan['name']) ?></h2>
                        <span class="badge <?= $plan['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $plan['is_active'] ? 'Actif' : 'Inactif' ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
                        <?= e($plan['description'] ?? 'Formule d\'abonnement SchoolPay.') ?>
                    </p>

                    <form action="<?= url('/superadmin/plans/' . $plan['id'] . '/update') ?>" method="POST">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label class="form-label">Prix Mensuel (FCFA) *</label>
                            <input 
                                type="number" 
                                name="monthly_price" 
                                class="form-control" 
                                value="<?= e($plan['monthly_price']) ?>" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prix Annuel (Optionnel)</label>
                            <input 
                                type="number" 
                                name="annual_price" 
                                class="form-control" 
                                value="<?= e($plan['annual_price'] ?? '') ?>" 
                                placeholder="Non défini"
                            >
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div class="form-group">
                                <label class="form-label">Élèves Max</label>
                                <input 
                                    type="number" 
                                    name="max_students" 
                                    class="form-control" 
                                    value="<?= e($plan['max_students'] ?? '') ?>" 
                                    placeholder="Illimité"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label">Comptes Max</label>
                                <input 
                                    type="number" 
                                    name="max_users" 
                                    class="form-control" 
                                    value="<?= e($plan['max_users'] ?? '') ?>" 
                                    placeholder="Illimité"
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Quota SMS Inclus / Mois *</label>
                            <input 
                                type="number" 
                                name="monthly_sms_quota" 
                                class="form-control" 
                                value="<?= e($plan['monthly_sms_quota']) ?>" 
                                required
                            >
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.88rem; cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" <?= $plan['is_active'] ? 'checked' : '' ?>>
                                <span>Activer ce forfait pour les nouvelles souscriptions</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            💾 Enregistrer les Modifications
                        </button>
                    </form>
                </div>
            </div>

            <div style="padding:12px 24px; background:#f8fafc; border-top:1px solid var(--border-color); font-size:0.8rem; color:var(--text-muted); text-align:center;">
                <?= e($plan['active_subscribers']) ?> établissement(s) abonnés
            </div>
        </div>
    <?php endforeach; ?>
</div>
