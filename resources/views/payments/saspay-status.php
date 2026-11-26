<?php
/** @var array $attempt */
/** @var array|null $invoice */
/** @var string|null $receiptNumber */
/** @var string $statusUrl */
$statusMessages = [
    'succeeded' => ['Paiement confirmé', 'success', 'SasPay a confirmé le paiement et SchoolPay a mis à jour la facture.'],
    'pending' => ['Paiement en attente', 'warning', 'Le paiement n’est pas encore confirmé. Vous pouvez vérifier à nouveau dans quelques instants.'],
    'initiating' => ['Paiement en préparation', 'info', 'La session de paiement est en cours de création. Actualisez le statut dans un instant.'],
    'failed' => ['Paiement non abouti', 'danger', 'SasPay indique que le paiement a échoué ou que le lien a expiré. Vous pouvez générer un nouveau lien depuis la facture.'],
    'review' => ['Vérification requise', 'warning', 'Le paiement a été reçu, mais ses informations ne correspondent plus au solde de la facture. Contactez l’établissement pour régulariser la situation.'],
];
$display = $statusMessages[$attempt['status']] ?? $statusMessages['pending'];
?>

<div class="auth-title text-center"><?= e($display[0]) ?></div>
<div class="alert alert-<?= e($display[1]) ?> mt-4" role="status"><?= e($display[2]) ?></div>

<?php if (!empty($invoice)): ?>
    <p class="mb-1"><strong>Établissement :</strong> <?= e($invoice['establishment_name']) ?></p>
    <p class="mb-1"><strong>Facture :</strong> <?= e($invoice['invoice_number']) ?></p>
<?php endif; ?>
<p class="mb-1"><strong>Montant :</strong> <?= format_money($attempt['amount']) ?></p>

<?php if (!empty($receiptNumber)): ?>
    <p class="mb-3"><strong>Reçu :</strong> <?= e($receiptNumber) ?></p>
<?php endif; ?>

<?php if ($attempt['status'] === 'pending' || $attempt['status'] === 'initiating'): ?>
    <a class="btn btn-primary w-100 mt-3" href="<?= e($statusUrl) ?>">Actualiser le statut</a>
<?php else: ?>
    <a class="btn btn-outline-primary w-100 mt-3" href="<?= url('/') ?>">Retour à SchoolPay</a>
<?php endif; ?>
