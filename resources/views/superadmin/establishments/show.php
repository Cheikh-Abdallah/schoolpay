<?php

declare(strict_types=1);

/**
 * Vue : Fiche détaillée établissement Super Admin
 * 
 * @var array $est
 * @var array $users
 * @var array $academicYears
 */
?>

<div style="margin-bottom: 20px;">
    <a href="<?= url('/superadmin/establishments') ?>" style="font-size:0.88rem; color:var(--text-muted);">
        ← Retour à la liste des établissements
    </a>
</div>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px; flex-wrap:wrap; gap:16px;">
    <div>
        <div style="display:flex; align-items:center; gap:12px;">
            <h1 style="font-size:1.6rem; font-weight:800; color:var(--primary); margin:0;">
                <?= e($est['name']) ?>
            </h1>
            <?= \App\Helpers\FormatHelper::statusBadge($est['status']) ?>
        </div>
        <p style="color:var(--text-muted); font-size:0.88rem; margin-top:4px;">
            Inscrit le <?= \App\Helpers\FormatHelper::dateTime($est['created_at']) ?> · Slug : <code><?= e($est['slug']) ?></code>
        </p>
    </div>

    <div>
        <form action="<?= url('/superadmin/establishments/' . $est['id'] . '/toggle-status') ?>" method="POST" style="display:inline;">
            <?= csrf_field() ?>
            <?php if ($est['status'] === 'suspended'): ?>
                <button type="submit" class="btn btn-primary" data-confirm="Réactiver cet établissement immédiatement ?">
                    ✅ Réactiver l'Établissement
                </button>
            <?php else: ?>
                <button type="submit" class="btn btn-danger" data-confirm="ATTENTION : Voulez-vous suspendre l'accès de cet établissement ?">
                    ⛔ Suspendre l'Établissement
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Informations Générales & État d'Abonnement -->
<div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px; margin-bottom: 24px;">
    <!-- Carte Coordonnées -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Coordonnées & Informations Légales</h2>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Email officiel</div>
                    <div style="font-weight:600; font-size:0.95rem;"><?= e($est['email']) ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Téléphone</div>
                    <div style="font-weight:600; font-size:0.95rem;"><?= \App\Helpers\FormatHelper::phone($est['phone']) ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Ville / Localité</div>
                    <div style="font-weight:600; font-size:0.95rem;"><?= e($est['city'] ?? '-') ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Adresse physique</div>
                    <div style="font-weight:600; font-size:0.95rem;"><?= e($est['address'] ?? '-') ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">NINEA</div>
                    <div style="font-weight:600; font-size:0.95rem;"><?= e($est['ninea'] ?? 'Non renseigné') ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">RCCM</div>
                    <div style="font-weight:600; font-size:0.95rem;"><?= e($est['rccm'] ?? 'Non renseigné') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte Abonnement SaaS & SMS -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Abonnement SaaS & Quotas</h2>
        </div>
        <div class="card-body">
            <div style="margin-bottom:16px;">
                <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Offre en cours</div>
                <div style="font-weight:700; font-size:1.1rem; color:var(--primary); margin-top:2px;">
                    <?= $est['plan_name'] ? e($est['plan_name']) : ($est['status'] === 'trial' ? 'Essai Gratuit Pilote (72h)' : 'Aucun forfait') ?>
                </div>
            </div>

            <?php if ($est['trial_status']): ?>
                <div style="margin-bottom:16px; padding:10px; background:#fffbeb; border-radius:6px; font-size:0.85rem;">
                    <strong>Fin de l'essai :</strong> <?= \App\Helpers\FormatHelper::dateTime($est['trial_ends_at']) ?><br>
                    <strong>Statut essai :</strong> <?= e($est['trial_status']) ?>
                </div>
            <?php endif; ?>

            <div style="border-top:1px solid var(--border-color); padding-top:12px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="font-size:0.88rem; color:var(--text-muted);">Crédits SMS disponibles :</span>
                    <strong style="color:var(--primary);"><?= e($est['sms_credits'] ?? 0) ?> SMS</strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="font-size:0.88rem; color:var(--text-muted);">SMS consommés à vie :</span>
                    <strong><?= e($est['sms_consumed'] ?? 0) ?> SMS</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Utilisateurs de l'école -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Utilisateurs Enregistrés dans cet Établissement</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Dernière Connexion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">
                            Aucun utilisateur rattaché.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= e($u['full_name']) ?></strong></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::phone($u['phone']) ?></td>
                            <td><span class="badge badge-secondary"><?= ucfirst(e($u['role'])) ?></span></td>
                            <td>
                                <?= $u['is_active'] ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-danger">Inactif</span>' ?>
                            </td>
                            <td><?= \App\Helpers\FormatHelper::dateTime($u['last_login_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
