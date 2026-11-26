<?php

declare(strict_types=1);

use App\Core\TenantContext;
use App\Core\Request;

$establishment = TenantContext::getTenant();
$request = new Request();
$currentPath = $request->path();

function is_active(string $prefix, string $currentPath): string {
    if ($prefix === '/dashboard' && $currentPath === '/dashboard') {
        return 'active';
    }
    return ($prefix !== '/dashboard' && str_starts_with($currentPath, $prefix)) ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= url('/dashboard') ?>" class="sidebar-brand">
            <span>SchoolPay</span>
            <span class="brand-dot"></span>
        </a>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="<?= url('/dashboard') ?>" class="nav-link <?= is_active('/dashboard', $currentPath) ?>">
                <span class="icon">📊</span>
                <span>Tableau de bord</span>
            </a>
        </li>

        <li class="nav-section-title">Scolarité</li>
        <li class="nav-item">
            <a href="<?= url('/students') ?>" class="nav-link <?= is_active('/students', $currentPath) ?>">
                <span class="icon">👨‍🎓</span>
                <span>Élèves</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/guardians') ?>" class="nav-link <?= is_active('/guardians', $currentPath) ?>">
                <span class="icon">👨‍👩‍👧</span>
                <span>Parents & Tuteurs</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/fee-types') ?>" class="nav-link <?= is_active('/fee-types', $currentPath) ?>">
                <span class="icon">🏷️</span>
                <span>Grille des frais</span>
            </a>
        </li>

        <li class="nav-section-title">Finances & Reçus</li>
        <li class="nav-item">
            <a href="<?= url('/invoices') ?>" class="nav-link <?= is_active('/invoices', $currentPath) ?>">
                <span class="icon">📄</span>
                <span>Factures scolaires</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/payments') ?>" class="nav-link <?= is_active('/payments', $currentPath) ?>">
                <span class="icon">💳</span>
                <span>Encaissements</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/receipts') ?>" class="nav-link <?= is_active('/receipts', $currentPath) ?>">
                <span class="icon">🧾</span>
                <span>Reçus officiels</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/unpaid') ?>" class="nav-link <?= is_active('/unpaid', $currentPath) ?>">
                <span class="icon">⚠️</span>
                <span>Impayés & Retards</span>
            </a>
        </li>

        <li class="nav-section-title">Communications</li>
        <li class="nav-item">
            <a href="<?= url('/sms') ?>" class="nav-link <?= is_active('/sms', $currentPath) ?>">
                <span class="icon">💬</span>
                <span>SMS SendText</span>
            </a>
        </li>

        <li class="nav-section-title">Administration</li>
        <li class="nav-item">
            <a href="<?= url('/reports') ?>" class="nav-link <?= is_active('/reports', $currentPath) ?>">
                <span class="icon">📈</span>
                <span>Rapports financiers</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/users') ?>" class="nav-link <?= is_active('/users', $currentPath) ?>">
                <span class="icon">👥</span>
                <span>Équipe & Rôles</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/settings') ?>" class="nav-link <?= is_active('/settings', $currentPath) ?>">
                <span class="icon">⚙️</span>
                <span>Paramètres école</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/subscription/status') ?>" class="nav-link <?= is_active('/subscription', $currentPath) ?>">
                <span class="icon">⭐</span>
                <span>Mon Forfait SaaS</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:8px;">
            Établissement actif :
        </div>
        <div style="font-weight:600;font-size:0.85rem;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= e($establishment['name'] ?? 'Mon École') ?>
        </div>
    </div>
</aside>
