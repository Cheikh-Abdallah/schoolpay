<?php /** @var array|null $old */ /** @var array $errors */ ?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="/students" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0">Ajouter un élève</h4>
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

<form method="POST" action="/students/create" novalidate>
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
                           value="<?= e($old['first_name'] ?? '') ?>" required autofocus>
                    <?php if (isset($errors['first_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                           value="<?= e($old['last_name'] ?? '') ?>" required>
                    <?php if (isset($errors['last_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="<?= e($old['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Genre</label>
                    <select name="gender" class="form-select">
                        <option value="">— Sélectionner —</option>
                        <option value="M" <?= ($old['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= ($old['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nationalité</label>
                    <input type="text" name="nationality" class="form-control"
                           value="<?= e($old['nationality'] ?? 'Sénégalaise') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= e($old['address'] ?? '') ?>" placeholder="Quartier, ville">
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
                           value="<?= e($old['class_name'] ?? '') ?>"
                           placeholder="Ex : 6ème A, Terminal S2…" required>
                    <?php if (isset($errors['class_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['class_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Actif</option>
                        <option value="inactive" <?= ($old['status'] ?? '')       === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes / Remarques</label>
                    <textarea name="notes" class="form-control" rows="2"><?= e($old['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="/students" class="btn btn-outline-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> Enregistrer
        </button>
    </div>
</form>

</div>
</div>
