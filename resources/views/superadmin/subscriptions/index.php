<?php

declare(strict_types=1);

/**
 * Vue : Abonnements et encaissements SaaS HOME SCHOOL
 * 
 * @var array $subscriptions
 * @var array $pagination
 * @var array $recentTransactions
 */
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-main);">Abonnements & Encaissements SaaS</h1>
        <p style="color:var(--text-muted); font-size:0.88rem;">
            Suivi des souscriptions actives et historique des transactions de la plateforme HOME SCHOOL.
        </p>
    </div>
</div>

<!-- Abonnements souscrits -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2 class="card-title">Abonnements des Établissements</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Établissement</th>
                    <th>Forfait</th>
                    <th>Cycle</th>
                    <th>Prix Payé</th>
                    <th>Début Période</th>
                    <th>Échéance</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted);">
                            Aucun abonnement payant actif pour le moment (Établissements en phase d'essai pilote).
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscriptions as $s): ?>
                        <tr>
                            <td>
                                <strong><?= e($s['establishment_name']) ?></strong>
                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($s['establishment_email']) ?></div>
                            </td>
                            <td><span class="badge badge-primary"><?= e($s['plan_name']) ?></span></td>
                            <td><?= $s['billing_cycle'] === 'annual' ? 'Annuel' : 'Mensuel' ?></td>
                            <td><strong><?= format_money($s['price_at_purchase']) ?></strong></td>
                            <td><?= \App\Helpers\FormatHelper::date($s['current_period_start']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::date($s['current_period_end']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::statusBadge($s['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Dernières Transactions Mobile Money SaaS -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Dernières Transactions SaaS (Wave / Orange Money)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Établissement</th>
                    <th>Opérateur</th>
                    <th>Montant</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted);">
                            Aucune transaction de paiement enregistrée pour le moment.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td><code><?= e($tx['reference']) ?></code></td>
                            <td><?= e($tx['establishment_name']) ?></td>
                            <td><strong><?= strtoupper(e($tx['provider'])) ?></strong></td>
                            <td><strong><?= format_money($tx['amount']) ?></strong></td>
                            <td><?= \App\Helpers\FormatHelper::dateTime($tx['created_at']) ?></td>
                            <td><?= \App\Helpers\FormatHelper::statusBadge($tx['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
