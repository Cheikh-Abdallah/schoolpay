<?php
/** @var array $invoice */
/** @var string $customerName */
/** @var string $customerEmail */
/** @var string|null $error */
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/students/<?= (int) $invoice['student_id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-0">Paiement Mobile Money</h4>
        <small class="text-muted">Facture <?= e($invoice['invoice_number']) ?></small>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="text-muted">Montant restant à payer</span>
            <strong class="fs-4"><?= format_money($invoice['balance_due']) ?></strong>
        </div>

        <p class="text-muted small">
            SasPay hébergera la page de paiement. Le parent pourra y choisir son opérateur Mobile Money.
            Les frais de transaction sont ajoutés au montant payé par le parent. Après confirmation par SasPay,
            SchoolPay enregistrera le paiement et générera un reçu.
        </p>

        <form method="POST" action="/invoices/<?= (int) $invoice['id'] ?>/saspay">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="customer_name" class="form-label">Nom du payeur</label>
                <input
                    id="customer_name"
                    name="customer_name"
                    type="text"
                    class="form-control"
                    maxlength="160"
                    value="<?= e($customerName) ?>"
                    required
                >
            </div>
            <div class="mb-4">
                <label for="customer_email" class="form-label">E-mail du payeur</label>
                <input
                    id="customer_email"
                    name="customer_email"
                    type="email"
                    class="form-control"
                    maxlength="254"
                    value="<?= e($customerEmail) ?>"
                    required
                >
                <div class="form-text">SasPay exige une adresse e-mail pour ouvrir le checkout.</div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-link-45deg me-1"></i> Générer le lien SasPay
            </button>
        </form>
    </div>
</div>
