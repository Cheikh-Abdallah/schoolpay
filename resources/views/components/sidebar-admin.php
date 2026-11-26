<?php

declare(strict_types=1);

use App\Core\Request;

$request = new Request();
$currentPath = $request->path();

function is_admin_active(string $path, string $currentPath): string {
    return ($currentPath === $path || str_starts_with($currentPath, $path . '/')) ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="sidebar-header" style="background:#04046d;border-bottom-color:#030357;">
        <a href="<?= url('/superadmin/dashboard') ?>" class="sidebar-brand" style="color:#ffffff;">
            <span>HOME SCHOOL</span>
            <span class="brand-dot"></span>
        </a>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-section-title">Plateforme SaaS</li>
        <li class="nav-item">
            <a href="<?= url('/superadmin/dashboard') ?>" class="nav-link <?= is_admin_active('/superadmin/dashboard', $currentPath) ?>">
                <span class="icon">📊</span>
                <span>Vue Globale</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/superadmin/establishments') ?>" class="nav-link <?= is_admin_active('/superadmin/establishments', $currentPath) ?>">
                <span class="icon">🏫</span>
                <span>Établissements</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/superadmin/invitations') ?>" class="nav-link <?= is_admin_active('/superadmin/invitations', $currentPath) ?>">
                <span class="icon">✉️</span>
                <span>Invitations Pilotes</span>
            </a>
        </li>

        <li class="nav-section-title">Monétisation & Forfaits</li>
        <li class="nav-item">
            <a href="<?= url('/superadmin/plans') ?>" class="nav-link <?= is_admin_active('/superadmin/plans', $currentPath) ?>">
                <span class="icon">🏷️</span>
                <span>Grille des Tarifs</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/superadmin/subscriptions') ?>" class="nav-link <?= is_admin_active('/superadmin/subscriptions', $currentPath) ?>">
                <span class="icon">💳</span>
                <span>Abonnements & MRR</span>
            </a>
        </li>

        <li class="nav-section-title">Infrastructure & Sécurité</li>
        <li class="nav-item">
            <a href="<?= url('/superadmin/sms') ?>" class="nav-link <?= is_admin_active('/superadmin/sms', $currentPath) ?>">
                <span class="icon">💬</span>
                <span>SendText & Quotas</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/superadmin/audit') ?>" class="nav-link <?= is_admin_active('/superadmin/audit', $currentPath) ?>">
                <span class="icon">🛡️</span>
                <span>Journaux d'Audit</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer" style="background:#f1f5f9;">
        <a href="<?= url('/logout') ?>" class="btn btn-secondary btn-sm" style="width:100%;">
            🚪 Déconnexion
        </a>
    </div>
</aside>
