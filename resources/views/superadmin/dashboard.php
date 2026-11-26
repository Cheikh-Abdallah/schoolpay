<?php

declare(strict_types=1);

/**
 * Vue : Tableau de bord Super Admin HOME SCHOOL
 * 
 * @var array $stats
 * @var array $recentEstablishments
 * @var array $recentInvitations
 * @var array $plans
 * @var int|null $sendTextBalance
 * @var bool $isSendTextConfigured
 */
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size:1.6rem; font-weight:800; color:var(--primary); margin-bottom:4px;">
            Super Administration HOME SCHOOL
        </h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">
            Supervision globale de la plateforme SaaS SCHOOLPAY · Sénégal & Afrique de l'Ouest
        </p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?= url('/superadmin/invitations') ?>" class="btn btn-primary">
            ✉️ Inviter un Établissement
        </a>
        <a href="<?= url('/superadmin/sms') ?>" class="btn btn-secondary">
            💬 Passerelle SendText
        </a>
    </div>
</div>

<!-- Cartes Statistiques KPI -->
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Établissements Inscrits</div>
            <div class="stat-value"><?= e($stats['total_establishments']) ?></div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">
                <span class="badge badge-warning"><?= e($stats['trial_establishments']) ?> en essai</span>
                <span class="badge badge-success"><?= e($stats['active_establishments']) ?> actifs</span>
            </div>
        </div>
        <div class="stat-icon primary">🏫</div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Total Élèves Gérés</div>
            <div class="stat-value"><?= e($stats['total_students']) ?></div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">
                Toutes écoles confondues
            </div>
        </div>
        <div class="stat-icon info">👨‍🎓</div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Revenu Récurrent (MRR)</div>
            <div class="stat-value" style="color:var(--primary);"><?= format_money($stats['mrr']) ?></div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">
                Abonnements mensuels actifs
            </div>
        </div>
        <div class="stat-icon success">💳</div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">SMS SendText Central</div>
            <div class="stat-value">
                <?= $sendTextBalance !== null ? number_format($sendTextBalance, 0, ',', ' ') : '-' ?>
            </div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">
                <?= e($stats['total_sms_consumed']) ?> consommés au total
            </div>
        </div>
        <div class="stat-icon warning">💬</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px;">
    <!-- Établissements Récents -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Établissements Récents</h2>
            <a href="<?= url('/superadmin/establishments') ?>" class="btn btn-secondary btn-sm">Voir tout</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Établissement</th>
                        <th>Ville</th>
                        <th>Élèves</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentEstablishments)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:24px; color:var(--text-muted);">
                                Aucun établissement inscrit pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentEstablishments as $est): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= e($est['name']) ?></div>
                                    <div style="font-size:0.78rem; color:var(--text-muted);"><?= e($est['email']) ?></div>
                                </td>
                                <td><?= e($est['city'] ?? '-') ?></td>
                                <td><strong><?= e($est['students_count']) ?></strong></td>
                                <td><?= \App\Helpers\FormatHelper::statusBadge($est['status']) ?></td>
                                <td>
                                    <a href="<?= url('/superadmin/establishments/' . $est['id']) ?>" class="btn btn-secondary btn-sm">
                                        Détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Forfaits et Tarifs SaaS Actifs -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Grille des Forfaits</h2>
            <a href="<?= url('/superadmin/plans') ?>" class="btn btn-secondary btn-sm">Modifier</a>
        </div>
        <div class="card-body" style="padding:16px;">
            <?php foreach ($plans as $plan): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid var(--border-color);">
                    <div>
                        <div style="font-weight:700; color:var(--primary);"><?= e($plan['name']) ?></div>
                        <div style="font-size:0.78rem; color:var(--text-muted);">
                            Max <?= $plan['max_students'] ? e($plan['max_students']) . ' élèves' : 'Illimité' ?> · <?= e($plan['monthly_sms_quota']) ?> SMS
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700; color:var(--text-main);">
                            <?= $plan['monthly_price'] > 0 ? format_money($plan['monthly_price']) : 'Sur devis' ?>
                        </div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">/ mois</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Invitations Pilotes Récentes -->
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h2 class="card-title">Dernières Invitations Établissements Pilotes</h2>
        <a href="<?= url('/superadmin/invitations') ?>" class="btn btn-primary btn-sm">Nouvelle Invitation</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Email Invité</th>
                    <th>Invité par</th>
                    <th>Date d'envoi</th>
                    <th>Expiration</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInvitations)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">
                            Aucune invitation générée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentInvitations as $inv): ?>
                        <tr>
                            <td><strong><?= e($inv['invited_email']) ?></strong></td>
                            <td><?= e($inv['invited_by_name'] ?? 'Super Admin') ?></td>
                            <td><?= \App\Helpers\FormatHelper::date($inv['created_at']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::date($inv['expires_at']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::statusBadge($inv['status']) ?></td>
                            <td>
                                <?php if ($inv['status'] === 'pending'): ?>
                                    <form action="<?= url('/superadmin/invitations/' . $inv['id'] . '/revoke') ?>" method="POST" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Révoquer cette invitation ?">
                                            Révoquer
                                        </button>
                                    </form>
                                <?php elseif ($inv['status'] === 'accepted'): ?>
                                    <span style="font-size:0.8rem; color:var(--success); font-weight:600;">✓ École créée</span>
                                <?php else: ?>
                                    <span style="font-size:0.8rem; color:var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
