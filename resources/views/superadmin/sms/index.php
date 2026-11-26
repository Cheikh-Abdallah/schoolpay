<?php

declare(strict_types=1);

/**
 * Vue : Gestion centrale des SMS SendText HOME SCHOOL
 * 
 * @var array|null $providerAccount
 * @var int|null $liveBalance
 * @var bool $isConfigured
 * @var array $establishmentCredits
 * @var array $recentTransactions
 */
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-main);">Passerelle SendText & Quotas SMS</h1>
        <p style="color:var(--text-muted); font-size:0.88rem;">
            Supervision du compte SMS central HOME SCHOOL et distribution des crédits aux établissements.
        </p>
    </div>
    <div>
        <a href="<?= url('/superadmin/sms/sync-balance') ?>" class="btn btn-secondary">
            🔄 Synchroniser le Solde SendText
        </a>
    </div>
</div>

<!-- Statut SendText & Solde en direct -->
<div style="display:grid; grid-template-columns: 1fr 2fr; gap:24px; margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Solde Global SendText</h2>
        </div>
        <div class="card-body" style="text-align:center; padding:32px 20px;">
            <div style="font-size:3rem; font-weight:800; color:var(--primary);">
                <?= $liveBalance !== null ? number_format($liveBalance, 0, ',', ' ') : '-' ?>
            </div>
            <div style="font-size:0.9rem; font-weight:600; color:var(--text-muted); margin-top:4px;">
                SMS Restants sur le Compte Central
            </div>

            <div style="margin-top:20px;">
                <?php if ($isConfigured): ?>
                    <span class="badge badge-success">✓ Passerelle Connectée</span>
                <?php else: ?>
                    <span class="badge badge-danger">⚠️ Identifiants manquants</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Formulaire de Configuration des clés d'API (chiffrées en AES-256-GCM) -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🔐 Configuration de l'API SendText Sénégal</h2>
        </div>
        <div class="card-body">
            <form action="<?= url('/superadmin/sms/settings') ?>" method="POST">
                <?= csrf_field() ?>

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">URL de base de l'API SendText *</label>
                        <input 
                            type="text" 
                            name="base_url" 
                            class="form-control" 
                            value="<?= e($providerAccount['base_url'] ?? 'https://api.sendtext.sn/v1') ?>" 
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expéditeur par défaut (Sender) *</label>
                        <input 
                            type="text" 
                            name="sender_name" 
                            class="form-control" 
                            value="<?= e($providerAccount['default_sender_name'] ?? 'SCHOOLPAY') ?>" 
                            maxlength="11" 
                            required
                        >
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">SNT-API-KEY *</label>
                        <input 
                            type="password" 
                            name="api_key" 
                            class="form-control" 
                            placeholder="Saisir la clé API" 
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label">SNT-API-SECRET *</label>
                        <input 
                            type="password" 
                            name="api_secret" 
                            class="form-control" 
                            placeholder="Saisir le secret API" 
                            required
                        >
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <p style="font-size:0.78rem; color:var(--text-muted); margin:0;">
                        🔒 Les secrets sont automatiquement chiffrés en base de données en AES-256-GCM.
                    </p>
                    <button type="submit" class="btn btn-primary">
                        💾 Enregistrer les Clés API
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dotation manuelle de crédits et Répartition par école -->
<div style="display:grid; grid-template-columns: 1fr 2fr; gap:24px; margin-bottom:24px;">
    <!-- Formulaire d'attribution manuelle -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🎁 Attribuer des Crédits SMS</h2>
        </div>
        <div class="card-body">
            <form action="<?= url('/superadmin/sms/grant-credits') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label">Établissement Bénéficiaire *</label>
                    <select name="establishment_id" class="form-control" required>
                        <option value="">Sélectionnez une école...</option>
                        <?php foreach ($establishmentCredits as $ec): ?>
                            <option value="<?= e($ec['id']) ?>">
                                <?= e($ec['name']) ?> (Solde: <?= e($ec['available_credits']) ?> SMS)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre de SMS à ajouter *</label>
                    <input type="number" name="quantity" class="form-control" placeholder="Ex: 50" min="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Motif de la dotation *</label>
                    <input type="text" name="description" class="form-control" placeholder="Ex: Rechargement pack 50 SMS" required>
                </div>

                <button type="submit" class="btn btn-accent" style="width:100%; font-weight:700;">
                    ➕ Créditer les SMS
                </button>
            </form>
        </div>
    </div>

    <!-- Répartition des soldes par établissement -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Portefeuilles SMS des Établissements</h2>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Établissement</th>
                        <th>Solde Disponible</th>
                        <th>Total Reçu</th>
                        <th>Total Consommé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($establishmentCredits)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">
                                Aucun établissement inscrit.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($establishmentCredits as $ec): ?>
                            <tr>
                                <td>
                                    <strong><?= e($ec['name']) ?></strong>
                                    <?= \App\Helpers\FormatHelper::statusBadge($ec['status']) ?>
                                </td>
                                <td>
                                    <strong style="color:var(--primary); font-size:1.05rem;">
                                        <?= number_format((int) $ec['available_credits'], 0, ',', ' ') ?> SMS
                                    </strong>
                                </td>
                                <td><?= number_format((int) $ec['lifetime_credited'], 0, ',', ' ') ?></td>
                                <td><?= number_format((int) $ec['lifetime_consumed'], 0, ',', ' ') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
