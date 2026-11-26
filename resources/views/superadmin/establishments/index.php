<?php

declare(strict_types=1);

/**
 * Vue : Liste des établissements Super Admin
 * 
 * @var array $establishments
 * @var array $pagination
 * @var string $currentStatus
 * @var string $search
 */
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-main);">Gestion des Établissements</h1>
        <p style="color:var(--text-muted); font-size:0.88rem;">
            <?= e($pagination['total']) ?> établissement(s) inscrit(s) sur la plateforme SchoolPay
        </p>
    </div>
    <a href="<?= url('/superadmin/invitations') ?>" class="btn btn-primary">
        ✉️ Inviter une nouvelle école
    </a>
</div>

<!-- Filtres et Recherche -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding:16px;">
        <form action="<?= url('/superadmin/establishments') ?>" method="GET" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
                <input 
                    type="text" 
                    name="q" 
                    class="form-control" 
                    placeholder="Rechercher par nom, email, téléphone, ville..." 
                    value="<?= e($search) ?>"
                >
            </div>
            <div>
                <select name="status" class="form-control" style="width:180px;">
                    <option value="all" <?= $currentStatus === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                    <option value="trial" <?= $currentStatus === 'trial' ? 'selected' : '' ?>>En essai gratuit</option>
                    <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Actifs (Abonnés)</option>
                    <option value="suspended" <?= $currentStatus === 'suspended' ? 'selected' : '' ?>>Suspendus</option>
                    <option value="closed" <?= $currentStatus === 'closed' ? 'selected' : '' ?>>Clôturés</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">
                🔍 Filtrer
            </button>
            <?php if ($search || $currentStatus !== 'all'): ?>
                <a href="<?= url('/superadmin/establishments') ?>" class="btn btn-secondary" style="color:var(--danger);">
                    Réinitialiser
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table des Établissements -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Établissement</th>
                    <th>Contact</th>
                    <th>Ville</th>
                    <th>Élèves</th>
                    <th>Utilisateurs</th>
                    <th>Offre</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($establishments)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:32px; color:var(--text-muted);">
                            Aucun établissement ne correspond aux critères de recherche.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($establishments as $est): ?>
                        <tr>
                            <td>
                                <div style="font-weight:700; color:var(--primary);"><?= e($est['name']) ?></div>
                                <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;"><?= e($est['slug']) ?></div>
                            </td>
                            <td>
                                <div><?= e($est['email']) ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?= \App\Helpers\FormatHelper::phone($est['phone']) ?></div>
                            </td>
                            <td><?= e($est['city'] ?? '-') ?></td>
                            <td><strong><?= e($est['students_count']) ?></strong></td>
                            <td><?= e($est['users_count']) ?></td>
                            <td>
                                <?php if ($est['plan_name']): ?>
                                    <span class="badge badge-primary"><?= e($est['plan_name']) ?></span>
                                <?php elseif ($est['status'] === 'trial'): ?>
                                    <span class="badge badge-warning">Essai 72h</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td><?= \App\Helpers\FormatHelper::statusBadge($est['status']) ?></td>
                            <td>
                                <a href="<?= url('/superadmin/establishments/' . $est['id']) ?>" class="btn btn-secondary btn-sm">
                                    Voir Fiche
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
