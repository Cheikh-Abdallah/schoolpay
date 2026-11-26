<?php
/** @var array $result */
/** @var array $classes */
/** @var string $search */
/** @var string $class */
/** @var string $status */
?>

<!-- En-tête + bouton -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Élèves</h4>
        <p class="text-muted small mb-0"><?= (int)$result['total'] ?> élève(s) au total</p>
    </div>
    <a href="/students/create" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Ajouter un élève
    </a>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="/students" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Recherche</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nom, prénom, matricule…"
                           value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Classe</label>
                <select name="class" class="form-select">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c) ?>" <?= $class === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <option value="active"      <?= $status === 'active'      ? 'selected' : '' ?>>Actif</option>
                    <option value="inactive"    <?= $status === 'inactive'    ? 'selected' : '' ?>>Inactif</option>
                    <option value="transferred" <?= $status === 'transferred' ? 'selected' : '' ?>>Transféré</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Filtrer</button>
                <?php if ($search || $class || $status): ?>
                <a href="/students" class="btn btn-outline-secondary">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Matricule</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Tuteur principal</th>
                    <th class="text-end">Dû</th>
                    <th class="text-center">Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($result['data'])): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                        Aucun élève trouvé.
                        <a href="/students/create" class="d-block mt-2">Ajouter le premier élève →</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($result['data'] as $s): ?>
                <tr>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace">
                            <?= e($s['student_number'] ?? '—') ?>
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($s['last_name']) ?> <?= e($s['first_name']) ?></div>
                        <?php if (!empty($s['date_of_birth'])): ?>
                        <small class="text-muted"><?= format_date($s['date_of_birth']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($s['class_name']) ?></td>
                    <td>
                        <?php if (!empty($s['primary_guardian_name'])): ?>
                            <div class="small"><?= e($s['primary_guardian_name']) ?></div>
                            <?php if (!empty($s['primary_guardian_phone'])): ?>
                            <small class="text-muted"><?= e($s['primary_guardian_phone']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if (($s['balance_due'] ?? 0) > 0): ?>
                            <span class="text-danger fw-semibold"><?= format_money((int)$s['balance_due']) ?></span>
                        <?php else: ?>
                            <span class="text-success"><i class="bi bi-check-circle"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php
                            $badges = [
                                'active'      => 'success',
                                'inactive'    => 'secondary',
                                'transferred' => 'warning',
                                'expelled'    => 'danger',
                            ];
                            $labels = [
                                'active'      => 'Actif',
                                'inactive'    => 'Inactif',
                                'transferred' => 'Transféré',
                                'expelled'    => 'Exclu',
                            ];
                            $st = $s['status'] ?? 'inactive';
                        ?>
                        <span class="badge bg-<?= $badges[$st] ?? 'secondary' ?>">
                            <?= $labels[$st] ?? $st ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="/students/<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="/students/<?= (int)$s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary ms-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($result['last_page'] > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Page <?= $result['current_page'] ?> / <?= $result['last_page'] ?>
            (<?= $result['total'] ?> résultats)
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $result['last_page']; $i++): ?>
                <li class="page-item <?= $i === $result['current_page'] ? 'active' : '' ?>">
                    <a class="page-link"
                       href="/students?page=<?= $i ?>&search=<?= urlencode($search) ?>&class=<?= urlencode($class) ?>&status=<?= urlencode($status) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
