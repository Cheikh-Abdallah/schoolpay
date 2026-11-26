<?php

declare(strict_types=1);

namespace App\Controllers\Establishment;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Services\SasPayClient;
use DomainException;
use PDO;
use RuntimeException;

class SasPayCheckoutController extends Controller
{
    public function form(Request $request, string $invoiceId): void
    {
        $establishmentId = TenantContext::requireTenantId();
        $parsedInvoiceId = filter_var($invoiceId, FILTER_VALIDATE_INT);
        $invoice = $parsedInvoiceId !== false && $parsedInvoiceId > 0
            ? $this->loadInvoice($parsedInvoiceId, $establishmentId)
            : null;

        if ($invoice === null) {
            Response::html('Facture introuvable.', 404);
        }

        $this->render('payments.saspay-form', [
            'title' => 'Paiement Mobile Money',
            'invoice' => $invoice,
            'customerName' => $invoice['guardian_name'] ?: trim($invoice['student_first_name'] . ' ' . $invoice['student_last_name']),
            'customerEmail' => $invoice['guardian_email'] ?? '',
            'error' => null,
        ], 'main');
    }

    public function createCheckout(Request $request, string $invoiceId): void
    {
        $establishmentId = TenantContext::requireTenantId();
        $parsedInvoiceId = filter_var($invoiceId, FILTER_VALIDATE_INT);
        $invoice = $parsedInvoiceId !== false && $parsedInvoiceId > 0
            ? $this->loadInvoice($parsedInvoiceId, $establishmentId)
            : null;

        if ($invoice === null) {
            Response::html('Facture introuvable.', 404);
        }

        $nameInput = $request->input('customer_name', '');
        $emailInput = $request->input('customer_email', '');
        $customerName = is_string($nameInput) ? trim($nameInput) : '';
        $customerEmail = is_string($emailInput) ? trim($emailInput) : '';
        if (
            $customerName === ''
            || strlen($customerName) > 160
            || strlen($customerEmail) > 254
            || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)
        ) {
            $this->render('payments.saspay-form', [
                'title' => 'Paiement Mobile Money',
                'invoice' => $invoice,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
                'error' => 'Veuillez saisir un nom et une adresse e-mail valides pour le payeur.',
            ], 'main');
        }

        try {
            $reservation = $this->reserveCheckout($invoice, $establishmentId);
        } catch (DomainException $exception) {
            $this->render('payments.saspay-form', [
                'title' => 'Paiement Mobile Money',
                'invoice' => $invoice,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
                'error' => $exception->getMessage(),
            ], 'main');
        }

        if ($reservation['status'] === 'pending') {
            $this->renderPaymentLink($reservation['attempt'], $invoice);
        }

        $attempt = $reservation['attempt'];
        $token = $reservation['token'];
        try {
            $client = new SasPayClient();
            $session = $client->createCheckoutSession([
                'amount' => $this->formatAmount((string) $attempt['amount']),
                'currency' => $attempt['currency'],
                'description' => 'Facture ' . $invoice['invoice_number'] . ' - ' . $invoice['establishment_name'],
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
                'fee_charge_mode' => 'ADD_ON',
                'return_url' => rtrim(APP_URL, '/') . '/payment/saspay/return?token=' . rawurlencode($token),
                'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400),
                'metadata' => [
                    'schoolpay_reference' => $attempt['reference'],
                ],
            ]);

            $checkoutUrl = (string) ($session['checkout_url'] ?? '');
            $sessionId = (string) ($session['id'] ?? '');
            $urlParts = parse_url($checkoutUrl);
            $checkoutHost = strtolower((string) ($urlParts['host'] ?? ''));

            if (
                $sessionId === ''
                || !isset($urlParts['scheme'])
                || strtolower($urlParts['scheme']) !== 'https'
                || $checkoutHost !== 'pay.saspay.me'
            ) {
                throw new RuntimeException('SasPay a renvoyé une session de paiement invalide.');
            }
        } catch (RuntimeException $exception) {
            Database::query(
                "UPDATE saspay_invoice_checkouts SET status = 'failed' WHERE id = :id AND status = 'initiating'",
                ['id' => $attempt['id']]
            );
            Logger::error('Échec de création du checkout SasPay.', [
                'attempt_id' => (int) $attempt['id'],
                'reference' => $attempt['reference'],
                'reason' => $exception->getMessage(),
            ]);

            $this->render('payments.saspay-form', [
                'title' => 'Paiement Mobile Money',
                'invoice' => $invoice,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
                'error' => 'SasPay n’a pas pu créer le lien. Vérifiez la configuration ou réessayez.',
            ], 'main');
        }

        Database::query(
            "UPDATE saspay_invoice_checkouts
             SET checkout_session_id = :session_id, checkout_url = :checkout_url, status = 'pending'
             WHERE id = :id AND status = 'initiating'",
            [
                'session_id' => $sessionId,
                'checkout_url' => $checkoutUrl,
                'id' => $attempt['id'],
            ]
        );

        $attempt['checkout_url'] = $checkoutUrl;
        $attempt['status_url'] = '/payments/saspay/' . rawurlencode($attempt['reference']);
        $attempt['token'] = $token;
        $this->renderPaymentLink($attempt, $invoice);
    }

    public function returnFromCheckout(Request $request): void
    {
        $tokenInput = $request->query('token', '');
        $token = is_string($tokenInput) ? $tokenInput : '';
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            Response::html('Lien de vérification invalide.', 404);
        }

        $attempt = $this->findByToken($token);
        if ($attempt === null) {
            Response::html('Lien de vérification introuvable.', 404);
        }

        $this->refreshAndRender($attempt, $token);
    }

    public function checkStatus(Request $request, string $reference): void
    {
        $establishmentId = TenantContext::requireTenantId();
        $attempt = Database::fetchOne(
            "SELECT * FROM saspay_invoice_checkouts
             WHERE reference = :reference AND establishment_id = :establishment_id
             LIMIT 1",
            ['reference' => $reference, 'establishment_id' => $establishmentId]
        );

        if ($attempt === null) {
            Response::html('Paiement introuvable.', 404);
        }

        $this->refreshAndRender($attempt, null);
    }

    private function loadInvoice(int $invoiceId, int $establishmentId): ?array
    {
        $invoice = Database::fetchOne(
            "SELECT i.id, i.student_id, i.invoice_number, i.balance_due, i.status,
                    s.first_name AS student_first_name, s.last_name AS student_last_name,
                    e.name AS establishment_name, e.currency
             FROM invoices i
             JOIN students s ON s.id = i.student_id
             JOIN establishments e ON e.id = i.establishment_id
             WHERE i.id = :invoice_id AND i.establishment_id = :establishment_id
             LIMIT 1",
            ['invoice_id' => $invoiceId, 'establishment_id' => $establishmentId]
        );

        if ($invoice === null) {
            return null;
        }

        $guardian = Database::fetchOne(
            "SELECT g.full_name, g.email
             FROM student_guardians sg
             JOIN guardians g ON g.id = sg.guardian_id
             WHERE sg.student_id = :student_id AND g.establishment_id = :establishment_id
             ORDER BY sg.is_primary DESC, sg.guardian_id ASC
             LIMIT 1",
            ['student_id' => $invoice['student_id'], 'establishment_id' => $establishmentId]
        );

        $invoice['guardian_name'] = $guardian['full_name'] ?? '';
        $invoice['guardian_email'] = $guardian['email'] ?? '';

        return $invoice;
    }

    private function reserveCheckout(array $invoice, int $establishmentId): array
    {
        $token = bin2hex(random_bytes(32));
        $reference = 'SP-' . strtoupper(bin2hex(random_bytes(12)));

        return Database::transaction(function (PDO $pdo) use ($invoice, $establishmentId, $token, $reference): array {
            $currentInvoice = Database::fetchOne(
                "SELECT id, student_id, invoice_number, balance_due, status
                 FROM invoices
                 WHERE id = :invoice_id AND establishment_id = :establishment_id
                 FOR UPDATE",
                ['invoice_id' => $invoice['id'], 'establishment_id' => $establishmentId]
            );

            if (
                $currentInvoice === null
                || !in_array($currentInvoice['status'], ['issued', 'partially_paid', 'overdue'], true)
                || (float) $currentInvoice['balance_due'] <= 0
            ) {
                throw new DomainException('Cette facture n’a plus de solde payable.');
            }

            $existing = Database::fetchOne(
                "SELECT id, reference, amount, currency, checkout_url, status, created_at
                 FROM saspay_invoice_checkouts
                 WHERE invoice_id = :invoice_id AND establishment_id = :establishment_id
                   AND status IN ('initiating', 'pending')
                 ORDER BY id DESC LIMIT 1",
                ['invoice_id' => $invoice['id'], 'establishment_id' => $establishmentId]
            );

            if ($existing !== null) {
                if ($existing['status'] === 'pending' && !empty($existing['checkout_url'])) {
                    if (
                        !$this->amountsMatch((string) $existing['amount'], (string) $currentInvoice['balance_due'])
                        || strtoupper((string) $existing['currency']) !== strtoupper((string) $invoice['currency'])
                    ) {
                        throw new DomainException('Un lien SasPay précédent est toujours actif avec un autre montant. Vérifiez son statut avant d’en créer un nouveau.');
                    }

                    $existing['status_url'] = '/payments/saspay/' . rawurlencode($existing['reference']);
                    return ['status' => 'pending', 'attempt' => $existing];
                }

                $isStale = strtotime((string) $existing['created_at']) < time() - 600;
                if ($existing['status'] !== 'initiating' || !$isStale) {
                    throw new DomainException('Un lien de paiement est déjà en cours de création. Actualisez la page dans un instant.');
                }

                Database::query(
                    "UPDATE saspay_invoice_checkouts SET status = 'failed' WHERE id = :id AND status = 'initiating'",
                    ['id' => $existing['id']]
                );
            }

            Database::query(
                "INSERT INTO saspay_invoice_checkouts
                    (establishment_id, invoice_id, token_hash, reference, amount, currency, status, created_at, updated_at)
                 VALUES
                    (:establishment_id, :invoice_id, :token_hash, :reference, :amount, :currency, 'initiating', NOW(), NOW())",
                [
                    'establishment_id' => $establishmentId,
                    'invoice_id' => $currentInvoice['id'],
                    'token_hash' => hash('sha256', $token),
                    'reference' => $reference,
                    'amount' => $currentInvoice['balance_due'],
                    'currency' => $invoice['currency'],
                ]
            );

            $attempt = [
                'id' => (int) Database::lastInsertId(),
                'reference' => $reference,
                'amount' => $currentInvoice['balance_due'],
                'currency' => $invoice['currency'],
            ];

            return ['status' => 'initiating', 'attempt' => $attempt, 'token' => $token];
        });
    }

    private function findByToken(string $token): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM saspay_invoice_checkouts WHERE token_hash = :token_hash LIMIT 1",
            ['token_hash' => hash('sha256', $token)]
        );
    }

    private function refreshAndRender(array $attempt, ?string $token): void
    {
        if ($attempt['status'] === 'pending' && !empty($attempt['checkout_session_id'])) {
            $checkoutStatus = null;
            $verifiedPayment = null;
            $transactionId = '';
            try {
                $client = new SasPayClient();
                $checkoutStatus = $client->getCheckoutStatus((string) $attempt['checkout_session_id']);
                $transactionStatus = strtoupper((string) ($checkoutStatus['transaction_status'] ?? ''));
                $transactionId = (string) ($checkoutStatus['transaction_id'] ?? '');

                if ($transactionStatus === 'SUCCESS' && $transactionId !== '') {
                    $verifiedPayment = $client->verifyPayment($transactionId);
                }
            } catch (RuntimeException $exception) {
                Logger::warning('Vérification du checkout SasPay temporairement impossible.', [
                    'attempt_id' => (int) $attempt['id'],
                    'reference' => $attempt['reference'],
                    'reason' => $exception->getMessage(),
                ]);
            }

            if ($verifiedPayment !== null) {
                $this->settleIfVerified($attempt, $verifiedPayment, $transactionId);
                $attempt = $this->reloadAttempt((int) $attempt['id']);
            } elseif (
                $checkoutStatus !== null
                && (
                    strtoupper((string) ($checkoutStatus['transaction_status'] ?? '')) === 'FAILED'
                    || in_array(strtoupper((string) ($checkoutStatus['status'] ?? '')), ['EXPIRED', 'CANCELLED'], true)
                )
            ) {
                Database::query(
                    "UPDATE saspay_invoice_checkouts SET status = 'failed' WHERE id = :id AND status = 'pending'",
                    ['id' => $attempt['id']]
                );
                $attempt = $this->reloadAttempt((int) $attempt['id']);
            }
        }

        $invoice = Database::fetchOne(
            "SELECT i.invoice_number, e.name AS establishment_name
             FROM invoices i
             JOIN establishments e ON e.id = i.establishment_id
             WHERE i.id = :invoice_id AND i.establishment_id = :establishment_id",
            ['invoice_id' => $attempt['invoice_id'], 'establishment_id' => $attempt['establishment_id']]
        );

        $receiptNumber = null;
        if (!empty($attempt['payment_id'])) {
            $receiptNumber = Database::fetchValue(
                'SELECT receipt_number FROM receipts WHERE payment_id = :payment_id LIMIT 1',
                ['payment_id' => $attempt['payment_id']]
            );
        }

        $this->render('payments.saspay-status', [
            'title' => 'Statut du paiement',
            'attempt' => $attempt,
            'invoice' => $invoice,
            'receiptNumber' => $receiptNumber,
            'statusUrl' => $token !== null
                ? '/payment/saspay/return?token=' . rawurlencode($token)
                : '/payments/saspay/' . rawurlencode($attempt['reference']),
        ], 'auth');
    }

    private function settleIfVerified(array $attempt, array $payment, string $transactionId): void
    {
        $validStatus = strtoupper((string) ($payment['status'] ?? '')) === 'SUCCESS';
        $validDirection = strtoupper((string) ($payment['flow_direction'] ?? '')) === 'INBOUND';
        $validCurrency = strtoupper((string) ($payment['currency'] ?? '')) === strtoupper((string) $attempt['currency']);
        $requestedAmount = $payment['requested_amount'] ?? null;
        $netAmount = $payment['net_amount'] ?? null;
        $validAmount = (is_string($requestedAmount) || is_int($requestedAmount) || is_float($requestedAmount))
            && (is_string($netAmount) || is_int($netAmount) || is_float($netAmount));

        if (
            !$validStatus
            || !$validDirection
            || !$validCurrency
            || !$validAmount
            || (string) ($payment['id'] ?? '') !== $transactionId
            || !$this->amountsMatch((string) $requestedAmount, (string) $attempt['amount'])
            || !$this->amountsMatch((string) $netAmount, (string) $attempt['amount'])
        ) {
            Database::query(
                "UPDATE saspay_invoice_checkouts
                 SET status = 'review', provider_transaction_id = :transaction_id
                 WHERE id = :id AND status = 'pending'",
                ['transaction_id' => $transactionId, 'id' => $attempt['id']]
            );
            return;
        }

        Database::transaction(function (PDO $pdo) use ($attempt, $transactionId): void {
            $lockedAttempt = Database::fetchOne(
                'SELECT * FROM saspay_invoice_checkouts WHERE id = :id FOR UPDATE',
                ['id' => $attempt['id']]
            );
            if ($lockedAttempt === null || $lockedAttempt['status'] !== 'pending') {
                return;
            }

            $invoice = Database::fetchOne(
                "SELECT id, student_id, total_amount, amount_paid, balance_due, status
                 FROM invoices
                 WHERE id = :invoice_id AND establishment_id = :establishment_id
                 FOR UPDATE",
                [
                    'invoice_id' => $lockedAttempt['invoice_id'],
                    'establishment_id' => $lockedAttempt['establishment_id'],
                ]
            );

            if (
                $invoice === null
                || !in_array($invoice['status'], ['issued', 'partially_paid', 'overdue'], true)
                || $this->amountInMinorUnits((string) $invoice['balance_due'])
                    !== $this->amountInMinorUnits((string) $lockedAttempt['amount'])
            ) {
                Database::query(
                    "UPDATE saspay_invoice_checkouts SET status = 'review' WHERE id = :id",
                    ['id' => $lockedAttempt['id']]
                );
                return;
            }

            $paymentReference = 'SASPAY-' . strtoupper(substr($lockedAttempt['reference'], -20));
            Database::query(
                "INSERT INTO payments
                    (establishment_id, student_id, payment_reference, amount, currency, payment_method,
                     external_transaction_id, paid_at, received_by, status, notes, created_at)
                 VALUES
                    (:establishment_id, :student_id, :payment_reference, :amount, :currency, 'saspay',
                     :transaction_id, NOW(), NULL, 'confirmed', :notes, NOW())",
                [
                    'establishment_id' => $lockedAttempt['establishment_id'],
                    'student_id' => $invoice['student_id'],
                    'payment_reference' => $paymentReference,
                    'amount' => $lockedAttempt['amount'],
                    'currency' => $lockedAttempt['currency'],
                    'transaction_id' => $transactionId,
                    'notes' => 'Paiement Mobile Money confirmé par SasPay.',
                ]
            );
            $paymentId = (int) Database::lastInsertId();

            Database::query(
                "INSERT INTO payment_allocations (payment_id, invoice_id, allocated_amount, created_at)
                 VALUES (:payment_id, :invoice_id, :amount, NOW())",
                [
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoice['id'],
                    'amount' => $lockedAttempt['amount'],
                ]
            );

            $newStatus = $this->amountInMinorUnits((string) $invoice['balance_due'])
                <= $this->amountInMinorUnits((string) $lockedAttempt['amount'])
                ? 'paid'
                : 'partially_paid';
            Database::query(
                "UPDATE invoices
                 SET amount_paid = amount_paid + :amount_paid,
                     balance_due = balance_due - :balance_reduction,
                     status = :status,
                     updated_at = NOW()
                 WHERE id = :invoice_id AND establishment_id = :establishment_id",
                [
                    'amount_paid' => $lockedAttempt['amount'],
                    'balance_reduction' => $lockedAttempt['amount'],
                    'status' => $newStatus,
                    'invoice_id' => $invoice['id'],
                    'establishment_id' => $lockedAttempt['establishment_id'],
                ]
            );

            $prefix = Database::fetchValue(
                'SELECT receipt_prefix FROM establishment_settings WHERE establishment_id = :establishment_id LIMIT 1',
                ['establishment_id' => $lockedAttempt['establishment_id']]
            );
            $receiptNumber = strtoupper((string) ($prefix ?: 'REC'))
                . '-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));
            Database::query(
                "INSERT INTO receipts (establishment_id, payment_id, receipt_number, issued_at)
                 VALUES (:establishment_id, :payment_id, :receipt_number, NOW())",
                [
                    'establishment_id' => $lockedAttempt['establishment_id'],
                    'payment_id' => $paymentId,
                    'receipt_number' => $receiptNumber,
                ]
            );

            Database::query(
                "UPDATE saspay_invoice_checkouts
                 SET status = 'succeeded', provider_transaction_id = :transaction_id, payment_id = :payment_id
                 WHERE id = :id AND status = 'pending'",
                [
                    'transaction_id' => $transactionId,
                    'payment_id' => $paymentId,
                    'id' => $lockedAttempt['id'],
                ]
            );
        });
    }

    private function amountInMinorUnits(string $amount): int
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new RuntimeException('Montant de paiement invalide.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function amountsMatch(string $left, string $right): bool
    {
        if (
            !preg_match('/^\d+(?:\.\d{1,2})?$/', $left)
            || !preg_match('/^\d+(?:\.\d{1,2})?$/', $right)
        ) {
            return false;
        }

        return $this->amountInMinorUnits($left) === $this->amountInMinorUnits($right);
    }

    private function formatAmount(string $amount): string
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new RuntimeException('Montant de paiement invalide.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return $whole . '.' . str_pad($fraction, 2, '0');
    }

    private function reloadAttempt(int $attemptId): array
    {
        $attempt = Database::fetchOne(
            'SELECT * FROM saspay_invoice_checkouts WHERE id = :id LIMIT 1',
            ['id' => $attemptId]
        );

        if ($attempt === null) {
            throw new RuntimeException('Tentative de paiement introuvable.');
        }

        return $attempt;
    }

    private function renderPaymentLink(array $attempt, array $invoice): void
    {
        $this->render('payments.saspay-link', [
            'title' => 'Lien de paiement Mobile Money',
            'attempt' => $attempt,
            'invoice' => $invoice,
        ], 'main');
    }
}
