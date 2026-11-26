<?php

declare(strict_types=1);

/**
 * Vue : Gestion des invitations pilotes HOME SCHOOL
 * 
 * @var array $invitations
 */
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-main);">Invitations des Établissements Pilotes</h1>
        <p style="color:var(--text-muted); font-size:0.88rem;">
            L'inscription sur SchoolPay est privée. Seules les écoles invitées peuvent créer leur espace.
        </p>
    </div>
</div>

<!-- Formulaire de Génération d'Invitation -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2 class="card-title">✉️ Émettre une Nouvelle Invitation</h2>
    </div>
    <div class="card-body">
        <form action="<?= url('/superadmin/invitations/create') ?>" method="POST" style="display:grid; grid-template-columns: 2fr 1fr auto; gap:16px; align-items:flex-end;">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom:0;">
                <label for="email" class="form-label">Adresse Email de la Direction / École *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="direction@nouvelle-ecole.sn" 
                    required
                >
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="valid_days" class="form-label">Durée de validité du lien</label>
                <select id="valid_days" name="valid_days" class="form-control">
                    <option value="3">3 jours</option>
                    <option value="7" selected>7 jours (Standard)</option>
                    <option value="15">15 jours</option>
                    <option value="30">30 jours</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="height:42px;">
                🚀 Générer le Lien Pilote
            </button>
        </form>
    </div>
</div>

<!-- Liste des Invitations -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Historique des Invitations Émises</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Email Destinataire</th>
                    <th>Établissement Créé</th>
                    <th>Émis par</th>
                    <th>Date d'émission</th>
                    <th>Expiration</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invitations)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted);">
                            Aucune invitation émise pour le moment.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invitations as $inv): ?>
                        <tr>
                            <td><strong><?= e($inv['invited_email']) ?></strong></td>
                            <td>
                                <?php if ($inv['establishment_name']): ?>
                                    <span style="color:var(--primary); font-weight:600;"><?= e($inv['establishment_name']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($inv['invited_by_name'] ?? 'Super Admin') ?></td>
                            <td><?= \App\Helpers\FormatHelper::date($inv['created_at']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::date($inv['expires_at']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::statusBadge($inv['status']) ?></td>
                            <td>
                                <?php if ($inv['status'] === 'pending'): ?>
                                    <form action="<?= url('/superadmin/invitations/' . $inv['id'] . '/revoke') ?>" method="POST" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Révoquer cette invitation ? Le lien ne sera plus utilisable.">
                                            Révoquer
                                        </button>
                                    </form>
                                <?php elseif ($inv['status'] === 'accepted'): ?>
                                    <span style="font-size:0.8rem; color:var(--success); font-weight:600;">✓ Compte Activé</span>
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
