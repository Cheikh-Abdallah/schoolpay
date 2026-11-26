<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\TenantContext;

$user = Auth::user();
$establishment = TenantContext::getTenant();
$initials = '';
if (!empty($user['full_name'])) {
    $parts = explode(' ', trim($user['full_name']));
    foreach ($parts as $p) {
        $initials .= mb_substr($p, 0, 1);
        if (mb_strlen($initials) >= 2) break;
    }
} else {
    $initials = 'SP';
}
?>
<header class="topbar">
    <div class="topbar-left">
        <button type="button" id="toggle-sidebar" class="btn btn-secondary btn-sm" style="display:none;" aria-label="Menu">
            ☰
        </button>

        <?php if ($establishment): ?>
            <div class="school-badge">
                <span>🏫</span>
                <span><?= e($establishment['name']) ?></span>
                <?php if ($establishment['status'] === 'trial'): ?>
                    <span class="badge badge-warning" style="margin-left:8px;">Essai 72h</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="school-badge" style="background:#060691;color:#ffffff;">
                <span>🛡️</span>
                <span>HOME SCHOOL - Administration Centrale</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="topbar-right">
        <div class="user-profile" id="user-menu-toggle" style="position:relative;">
            <div class="user-avatar">
                <?= strtoupper(e($initials)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= e($user['full_name'] ?? 'Utilisateur') ?></div>
                <div class="user-role"><?= ucfirst(e($user['role'] ?? 'Membre')) ?></div>
            </div>

            <!-- Menu déroulant -->
            <div id="user-dropdown" style="display:none;position:absolute;top:100%;right:0;margin-top:10px;background:#fff;border:1px solid var(--border-color);border-radius:var(--radius-md);box-shadow:var(--shadow-md);min-width:180px;z-index:200;">
                <a href="<?= url('/settings') ?>" style="display:block;padding:10px 16px;color:var(--text-main);font-size:0.88rem;border-bottom:1px solid var(--border-color);">
                    ⚙️ Mon Profil / Paramètres
                </a>
                <a href="<?= url('/logout') ?>" style="display:block;padding:10px 16px;color:var(--danger);font-size:0.88rem;">
                    🚪 Se déconnecter
                </a>
            </div>
        </div>
    </div>
</header>

<style>
#user-dropdown.show { display: block !important; }
@media (max-width: 992px) {
    #toggle-sidebar { display: inline-flex !important; }
}
</style>
