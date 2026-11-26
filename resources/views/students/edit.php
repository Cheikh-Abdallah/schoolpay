<?php
/** @var array $student */
/** @var array $errors */
$st = $student;
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="/students/<?= (int)$st['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0">Modifier l'élève</h4>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $field => $msg): ?>
        <li><?= e(is_array($msg) ? implode(', ', $msg) : $msg) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="/students/<?= (int)$st['id'] ?>/edit" novalidate>
    <?= csrf_field() ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-person-badge me-2 text-primary"></i>Informations personnelles
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                           value="<?= e($st['first_name'] ?? '') ?>" required autofocus>
                    <?php if (isset($errors['first_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                           value="<?= e($st['last_name'] ?? '') ?>" required>
                    <?php if (isset($errors['last_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="<?= e($st['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Genre</label>
                    <select name="gender" class="form-select">
                        <option value="">— Sélectionner —</option>
                        <option value="M" <?= ($st['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= ($st['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nationalité</label>
                    <input type="text" name="nationality" class="form-control"
                           value="<?= e($st['nationality'] ?? 'Sénégalaise') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= e($st['address'] ?? '') ?>" placeholder="Quartier, ville">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-mortarboard me-2 text-primary"></i>Informations scolaires
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Classe <span class="text-danger">*</span></label>
                    <input type="text" name="class_name" class="form-control <?= isset($errors['class_name']) ? 'is-invalid' : '' ?>"
                           value="<?= e($st['class_name'] ?? '') ?>"
                           placeholder="Ex : 6ème A, Terminal S2…" required>
                    <?php if (isset($errors['class_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['class_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="active"      <?= ($st['status'] ?? '') === 'active'      ? 'selected' : '' ?>>Actif</option>
                        <option value="inactive"    <?= ($st['status'] ?? '') === 'inactive'    ? 'selected' : '' ?>>Inactif</option>
                        <option value="transferred" <?= ($st['status'] ?? '') === 'transferred' ? 'selected' : '' ?>>Transféré</option>
                        <option value="expelled"    <?= ($st['status'] ?? '') === 'expelled'    ? 'selected' : '' ?>>Exclu</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes / Remarques</label>
                    <textarea name="notes" class="form-control" rows="2"><?= e($st['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <!-- Suppression / Archivage -->
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
            <i class="bi bi-trash me-1"></i> Archiver cet élève
        </button>

        <div class="d-flex gap-2">
            <a href="/students/<?= (int)$st['id'] ?>" class="btn btn-outline-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i> Enregistrer les modifications
            </button>
        </div>
    </div>
</form>

<!-- Modal confirmation suppression -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/students/<?= (int)$st['id'] ?>/delete">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Archiver l'élève</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir archiver l'élève <strong><?= e($st['first_name'] . ' ' . $st['last_name']) ?></strong> ?</p>
                    <p class="text-muted small mb-0">L'élève n'apparaîtra plus dans les listes actives mais son historique de paiement sera conservé.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Confirmer l'archivage</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
</div>
