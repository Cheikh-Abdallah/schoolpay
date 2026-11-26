<?php
/** @var array $student */
/** @var array $allGuardians */
$st = $student;
$financial = $st['financial'] ?? [];
$guardians  = $st['guardians']  ?? [];
$invoices   = $st['invoices']   ?? [];
$statusLabels = ['active'=>'Actif','inactive'=>'Inactif','transferred'=>'Transféré','expelled'=>'Exclu'];
$statusColors = ['active'=>'success','inactive'=>'secondary','transferred'=>'warning','expelled'=>'danger'];
$curStatus = $st['status'] ?? 'inactive';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="/students" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h4 class="fw-bold mb-0">
                <?= e($st['last_name']) ?> <?= e($st['first_name']) ?>
                <span class="badge bg-<?= $statusColors[$curStatus] ?? 'secondary' ?> ms-2 fs-6">
                    <?= $statusLabels[$curStatus] ?? $curStatus ?>
                </span>
            </h4>
            <small class="text-muted font-monospace"><?= e($st['student_number'] ?? '—') ?></small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="/students/<?= (int)$st['id'] ?>/edit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Modifier
        </a>

        <!-- Changer statut -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                Statut
            </button>
            <ul class="dropdown-menu">
                <?php foreach ($statusLabels as $val => $label): ?>
                <?php if ($val !== $curStatus): ?>
                <li>
                    <form method="POST" action="/students/<?= (int)$st['id'] ?>/status">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="<?= $val ?>">
                        <button type="submit" class="dropdown-item"><?= $label ?></button>
                    </form>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Colonne gauche -->
    <div class="col-lg-4">

        <!-- Infos personnelles -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small fw-semibold mb-3">Informations</h6>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Classe</dt>
                    <dd class="col-7 fw-semibold"><?= e($st['class_name']) ?></dd>

                    <dt class="col-5 text-muted">Genre</dt>
                    <dd class="col-7"><?= $st['gender'] === 'M' ? 'Masculin' : ($st['gender'] === 'F' ? 'Féminin' : '—') ?></dd>

                    <dt class="col-5 text-muted">Naissance</dt>
                    <dd class="col-7"><?= $st['date_of_birth'] ? format_date($st['date_of_birth']) : '—' ?></dd>

                    <dt class="col-5 text-muted">Nationalité</dt>
                    <dd class="col-7"><?= e($st['nationality'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted">Adresse</dt>
                    <dd class="col-7"><?= e($st['address'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted">Inscrit le</dt>
                    <dd class="col-7"><?= format_date($st['created_at']) ?></dd>
                </dl>
                <?php if (!empty($st['notes'])): ?>
                <hr class="my-2">
                <p class="text-muted small mb-0"><?= nl2br(e($st['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Résumé financier -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small fw-semibold mb-3">Situation financière</h6>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <p class="text-muted small mb-0">Facturé</p>
                        <p class="fw-bold mb-0"><?= format_money((int)($financial['total_invoiced'] ?? 0)) ?></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-0">Payé</p>
                        <p class="fw-bold text-success mb-0"><?= format_money((int)($financial['total_paid'] ?? 0)) ?></p>
                    </div>
                    <div class="col-12">
                        <hr class="my-1">
                        <p class="text-muted small mb-0">Reste dû</p>
                        <p class="fw-bold <?= ($financial['total_balance_due'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?> mb-0 fs-5">
                            <?= format_money((int)($financial['total_balance_due'] ?? 0)) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tuteurs rattachés -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold small"><i class="bi bi-people me-1"></i>Tuteurs</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#attachGuardianModal">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($guardians)): ?>
                <li class="list-group-item text-muted small text-center py-3">Aucun tuteur rattaché</li>
                <?php else: ?>
                <?php foreach ($guardians as $g): ?>
                <li class="list-group-item px-3 py-2">
                    <div class="fw-semibold small"><?= e($g['full_name']) ?></div>
                    <small class="text-muted"><?= e($g['phone'] ?? '') ?></small>
                    <?php if ($g['is_primary']): ?>
                    <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.65rem">Principal</span>
                    <?php endif; ?>
                    <div><a href="/guardians/<?= (int)$g['id'] ?>" class="text-decoration-none" style="font-size:.75rem">Voir fiche →</a></div>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Colonne droite : Factures -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-receipt me-2"></i>Historique des factures
                    <span class="badge bg-secondary ms-1"><?= count($invoices) ?></span>
                </h6>
                <a href="/invoices/create?student_id=<?= (int)$st['id'] ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus me-1"></i> Nouvelle facture
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Facture</th>
                            <th>Montant</th>
                            <th>Payé</th>
                            <th>Dû</th>
                            <th>Échéance</th>
                            <th class="text-center">Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25"></i>
                                Aucune facture pour cet élève.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                        <?php
                            $invStatus = $inv['status'] ?? 'pending';
                            $invColors = ['paid'=>'success','partially_paid'=>'warning','issued'=>'secondary','draft'=>'dark','overdue'=>'danger','cancelled'=>'dark'];
                            $invLabels = ['paid'=>'Payé','partially_paid'=>'Partiel','issued'=>'En attente','draft'=>'Brouillon','overdue'=>'En retard','cancelled'=>'Annulé'];
                        ?>
                        <tr>
                            <td>
                                <a href="/invoices/<?= (int)$inv['id'] ?>" class="fw-semibold text-decoration-none">
                                    <?= e($inv['invoice_number']) ?>
                                </a>
                            </td>
                            <td><?= format_money((int)$inv['total_amount']) ?></td>
                            <td class="text-success"><?= format_money((int)($inv['amount_paid'] ?? 0)) ?></td>
                            <td class="<?= ($inv['balance_due'] ?? 0) > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                <?= format_money((int)($inv['balance_due'] ?? 0)) ?>
                            </td>
                            <td class="text-muted small"><?= $inv['due_date'] ? format_date($inv['due_date']) : '—' ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $invColors[$invStatus] ?? 'secondary' ?>">
                                    <?= $invLabels[$invStatus] ?? $invStatus ?>
                                </span>
                            </td>
                            <td>
                                <a href="/invoices/<?= (int)$inv['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ((float) ($inv['balance_due'] ?? 0) > 0 && !in_array($invStatus, ['draft', 'cancelled', 'paid'], true)): ?>
                                <a href="/invoices/<?= (int)$inv['id'] ?>/saspay" class="btn btn-sm btn-outline-success" title="Créer un lien de paiement Mobile Money">
                                    <i class="bi bi-phone"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal : rattacher un tuteur -->
<div class="modal fade" id="attachGuardianModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/students/<?= (int)$st['id'] ?>/attach-guardian">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Rattacher un tuteur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tuteur</label>
                        <select name="guardian_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php
                            $linkedIds = array_column($guardians, 'id');
                            foreach ($allGuardians as $ag):
                                if (!in_array($ag['id'], $linkedIds)):
                            ?>
                            <option value="<?= (int)$ag['id'] ?>">
                                <?= e($ag['full_name']) ?>
                                <?= !empty($ag['phone']) ? ' — ' . e($ag['phone']) : '' ?>
                            </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="isPrimary" value="1">
                        <label class="form-check-label" for="isPrimary">Tuteur principal</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rattacher</button>
                </div>
            </form>
        </div>
    </div>
</div>
