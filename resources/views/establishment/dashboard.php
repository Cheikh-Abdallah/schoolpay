<?php
/** @var array $studentStats */
/** @var array $financeStats */
/** @var array $monthlyPayments */
/** @var array|null $smsBalance */
/** @var array|null $trial */
/** @var array|null $subscription */
/** @var array $recentPayments */
/** @var array $urgentUnpaid */
/** @var array $establishment */
?>

<!-- Alerte essai gratuit -->
<?php if ($trial && $trial['hours_left'] !== null): ?>
<div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-clock-history me-2 fs-5"></i>
    <div>
        <strong>Période d'essai</strong> —
        <?php if ($trial['hours_left'] > 0): ?>
            Il vous reste <strong><?= (int)$trial['hours_left'] ?> heure(s)</strong> d'essai gratuit.
        <?php else: ?>
            Votre essai gratuit est <strong>expiré</strong>.
        <?php endif; ?>
        <a href="/subscription/upgrade" class="alert-link ms-2">Passer à un forfait →</a>
    </div>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-4 mb-4">

    <!-- Élèves -->
    <div class="col-6 col-md-3">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Élèves actifs</p>
                        <h3 class="fw-bold mb-0"><?= (int)($studentStats['active'] ?? 0) ?></h3>
                        <small class="text-muted">/ <?= (int)($studentStats['total'] ?? 0) ?> total</small>
                    </div>
                    <div class="stat-icon bg-primary-soft text-primary rounded-circle">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <a href="/students" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Total facturé -->
    <div class="col-6 col-md-3">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Total facturé</p>
                        <h3 class="fw-bold mb-0"><?= format_money((int)($financeStats['total_invoiced'] ?? 0)) ?></h3>
                        <small class="text-muted"><?= (int)($financeStats['invoice_count'] ?? 0) ?> factures</small>
                    </div>
                    <div class="stat-icon bg-info-soft text-info rounded-circle">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
                <a href="/invoices" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Total encaissé -->
    <div class="col-6 col-md-3">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Encaissé</p>
                        <h3 class="fw-bold mb-0 text-success"><?= format_money((int)($financeStats['total_paid'] ?? 0)) ?></h3>
                        <small class="text-muted">Ce mois : <?= format_money((int)($monthlyPayments['amount'] ?? 0)) ?></small>
                    </div>
                    <div class="stat-icon bg-success-soft text-success rounded-circle">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <a href="/payments" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Impayés -->
    <div class="col-6 col-md-3">
        <div class="card stat-card border-0 shadow-sm h-100 <?= ($financeStats['total_due'] ?? 0) > 0 ? 'border-danger border-start border-3' : '' ?>">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Impayés</p>
                        <h3 class="fw-bold mb-0 text-danger"><?= format_money((int)($financeStats['total_due'] ?? 0)) ?></h3>
                        <small class="text-<?= ($financeStats['overdue_count'] ?? 0) > 0 ? 'danger' : 'muted' ?>">
                            <?= (int)($financeStats['overdue_count'] ?? 0) ?> en retard
                        </small>
                    </div>
                    <div class="stat-icon bg-danger-soft text-danger rounded-circle">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
                <a href="/unpaid" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<!-- Ligne SMS + abonnement -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small fw-semibold mb-3">
                    <i class="bi bi-chat-dots me-1"></i> Crédits SMS
                </h6>
                <?php if ($smsBalance): ?>
                    <?php
                        $pct = $smsBalance['credits_total'] > 0
                            ? min(100, round($smsBalance['credits_available'] * 100 / $smsBalance['credits_total']))
                            : 0;
                        $color = $pct > 40 ? 'success' : ($pct > 15 ? 'warning' : 'danger');
                    ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold fs-5"><?= (int)$smsBalance['credits_available'] ?></span>
                        <span class="text-muted small">/ <?= (int)$smsBalance['credits_total'] ?></span>
                    </div>
                    <div class="progress" style="height:8px">
                        <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <p class="text-muted small mt-2 mb-0">crédits disponibles</p>
                <?php else: ?>
                    <p class="text-muted small">Aucun compte SMS configuré.</p>
                <?php endif; ?>
                <a href="/sms" class="btn btn-sm btn-outline-primary mt-3">Gérer les SMS</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small fw-semibold mb-3">
                    <i class="bi bi-box-seam me-1"></i> Forfait SaaS
                </h6>
                <?php if ($subscription): ?>
                    <span class="badge bg-success fs-6 mb-2"><?= e($subscription['plan_name']) ?></span>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-people me-1"></i> Jusqu'à <?= (int)$subscription['max_students'] ?> élèves
                    </p>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-chat me-1"></i> <?= (int)$subscription['sms_per_month'] ?> SMS/mois
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar me-1"></i> Expire le <?= format_date($subscription['ends_at']) ?>
                    </p>
                <?php elseif ($trial): ?>
                    <span class="badge bg-warning text-dark fs-6 mb-2">Essai gratuit</span>
                    <p class="text-muted small">72h d'essai + 10 SMS offerts</p>
                    <a href="/subscription/upgrade" class="btn btn-sm btn-primary">Choisir un forfait</a>
                <?php else: ?>
                    <p class="text-danger small">Aucun abonnement actif.</p>
                    <a href="/subscription/upgrade" class="btn btn-sm btn-danger">Renouveler</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small fw-semibold mb-3">
                    <i class="bi bi-lightning-charge me-1"></i> Actions rapides
                </h6>
                <div class="d-grid gap-2">
                    <a href="/students/create" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-plus me-1"></i> Nouvel élève
                    </a>
                    <a href="/invoices/create" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-plus me-1"></i> Nouvelle facture
                    </a>
                    <a href="/payments/create" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-cash me-1"></i> Enregistrer paiement
                    </a>
                    <a href="/sms/create" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-send me-1"></i> Envoyer SMS
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Paiements récents + Impayés urgents -->
<div class="row g-4">

    <!-- Paiements récents -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Paiements récents</h6>
                <a href="/payments" class="btn btn-sm btn-outline-secondary">Voir tout</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Élève</th>
                            <th>Facture</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun paiement enregistré</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentPayments as $p): ?>
                        <tr>
                            <td><?= e($p['student_name'] ?? '—') ?></td>
                            <td><a href="/invoices/<?= (int)$p['id'] ?>"><?= e($p['invoice_number']) ?></a></td>
                            <td class="fw-semibold text-success"><?= format_money((int)$p['amount']) ?></td>
                            <td><span class="badge bg-secondary"><?= $p['payment_method'] === 'saspay' ? 'SasPay Mobile Money' : e($p['payment_method']) ?></span></td>
                            <td class="text-muted small"><?= format_date($p['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Impayés urgents -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-danger"><i class="bi bi-alarm me-2"></i>Impayés en retard</h6>
                <a href="/unpaid" class="btn btn-sm btn-outline-danger">Voir tout</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($urgentUnpaid)): ?>
                <div class="list-group-item text-center text-muted py-4">
                    <i class="bi bi-check-circle text-success fs-4"></i>
                    <p class="mb-0 mt-1">Aucun impayé en retard</p>
                </div>
                <?php else: ?>
                <?php foreach ($urgentUnpaid as $u): ?>
                <div class="list-group-item px-3 py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-0 fw-semibold small"><?= e($u['student_name']) ?></p>
                            <p class="mb-0 text-muted" style="font-size:.78rem"><?= e($u['guardian_name'] ?? '') ?> <?= e($u['guardian_phone'] ?? '') ?></p>
                        </div>
                        <div class="text-end">
                            <span class="text-danger fw-bold small"><?= format_money((int)$u['balance_due']) ?></span><br>
                            <span class="badge bg-danger-subtle text-danger" style="font-size:.7rem">
                                +<?= (int)$u['days_overdue'] ?>j
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
