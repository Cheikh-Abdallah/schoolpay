<?php

declare(strict_types=1);

/**
 * Vue : Journaux d'audit et cybersécurité HOME SCHOOL
 * 
 * @var array $logs
 * @var array $pagination
 * @var string $currentActor
 * @var string $search
 */
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-main);">Journaux d'Audit & Sécurité</h1>
        <p style="color:var(--text-muted); font-size:0.88rem;">
            Traçabilité immuable des connexions, opérations sensibles et actions d'administration.
        </p>
    </div>
</div>

<!-- Filtres de recherche -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding:16px;">
        <form action="<?= url('/superadmin/audit') ?>" method="GET" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
                <input 
                    type="text" 
                    name="q" 
                    class="form-control" 
                    placeholder="Rechercher par action, adresse IP, nom d'école..." 
                    value="<?= e($search) ?>"
                >
            </div>
            <div>
                <select name="actor_type" class="form-control" style="width:200px;">
                    <option value="all" <?= $currentActor === 'all' ? 'selected' : '' ?>>Tous les types d'acteurs</option>
                    <option value="platform_user" <?= $currentActor === 'platform_user' ? 'selected' : '' ?>>Super Administrateur</option>
                    <option value="establishment_user" <?= $currentActor === 'establishment_user' ? 'selected' : '' ?>>Utilisateur École</option>
                    <option value="system" <?= $currentActor === 'system' ? 'selected' : '' ?>>Système / Automatismes</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">
                🔍 Filtrer
            </button>
            <?php if ($search || $currentActor !== 'all'): ?>
                <a href="<?= url('/superadmin/audit') ?>" class="btn btn-secondary" style="color:var(--danger);">
                    Réinitialiser
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table des logs -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Horodatage</th>
                    <th>Type Acteur</th>
                    <th>Établissement</th>
                    <th>Action Exécutée</th>
                    <th>Adresse IP</th>
                    <th>Agent Utilisateur</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted);">
                            Aucun événement d'audit enregistré pour le moment.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= \App\Helpers\FormatHelper::dateTime($log['created_at']) ?></td>
                            <td>
                                <span class="badge <?= $log['actor_type'] === 'platform_user' ? 'badge-primary' : 'badge-secondary' ?>">
                                    <?= e($log['actor_type']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['establishment_name']): ?>
                                    <strong><?= e($log['establishment_name']) ?></strong>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">HOME SCHOOL Central</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-weight:600; color:var(--primary);"><?= e($log['action']) ?></code>
                            </td>
                            <td><code><?= e($log['ip_address'] ?? '-') ?></code></td>
                            <td style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.75rem; color:var(--text-muted);" title="<?= e($log['user_agent'] ?? '') ?>">
                                <?= e($log['user_agent'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
