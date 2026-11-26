<?php
/** @var array $attempt */
/** @var array $invoice */
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/students/<?= (int) $invoice['student_id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-0">Lien de paiement prêt</h4>
        <small class="text-muted">Facture <?= e($invoice['invoice_number']) ?></small>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <p>Transmettez ce lien au parent. Il pourra sélectionner son moyen de paiement sur la page sécurisée SasPay.</p>
        <label for="checkout_url" class="form-label">Lien Mobile Money</label>
        <div class="input-group mb-3">
            <input id="checkout_url" class="form-control" type="url" value="<?= e($attempt['checkout_url']) ?>" readonly>
            <a class="btn btn-outline-primary" href="<?= e($attempt['checkout_url']) ?>" target="_blank" rel="noopener noreferrer">
                Ouvrir
            </a>
        </div>
        <p class="mb-1"><strong>Montant :</strong> <?= format_money($attempt['amount']) ?></p>
        <p class="text-muted small mb-4">
            Le statut de la facture ne changera qu’après vérification du paiement auprès de SasPay.
        </p>
        <a class="btn btn-primary w-100" href="<?= e($attempt['status_url']) ?>">
            Vérifier le statut du paiement
        </a>
    </div>
</div>
