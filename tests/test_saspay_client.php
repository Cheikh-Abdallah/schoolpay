<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Services\SasPayClient;

$requests = [];
$transport = static function (string $method, string $url, array $headers, ?string $body) use (&$requests): array {
    $requests[] = compact('method', 'url', 'headers', 'body');

    if ($method === 'POST') {
        return [
            'status' => 201,
            'body' => json_encode([
                'success' => true,
                'data' => [
                    'id' => 'session-123',
                    'checkout_url' => 'https://pay.saspay.me/checkout/example',
                    'status' => 'PENDING',
                ],
            ], JSON_THROW_ON_ERROR),
        ];
    }

    if (str_contains($url, '/checkout-sessions/')) {
        return [
            'status' => 200,
            'body' => json_encode([
                'success' => true,
                'data' => [
                    'id' => 'session-123',
                    'status' => 'PAID',
                    'transaction_status' => 'SUCCESS',
                    'transaction_id' => 'transaction-123',
                ],
            ], JSON_THROW_ON_ERROR),
        ];
    }

    return [
        'status' => 200,
        'body' => json_encode([
            'success' => true,
            'data' => [
                'id' => 'transaction-123',
                'status' => 'SUCCESS',
                'flow_direction' => 'INBOUND',
                'requested_amount' => '2500.00',
                'net_amount' => '2500.00',
                'currency' => 'XOF',
            ],
        ], JSON_THROW_ON_ERROR),
    ];
};

$client = new SasPayClient($transport, 'sk_test_unit_test', 'sandbox');
$session = $client->createCheckoutSession([
    'amount' => '2500.00',
    'currency' => 'XOF',
    'fee_charge_mode' => 'ADD_ON',
]);
$status = $client->getCheckoutStatus('session-123');
$payment = $client->verifyPayment('transaction-123');

if (($session['id'] ?? null) !== 'session-123'
    || ($status['transaction_id'] ?? null) !== 'transaction-123'
    || ($payment['net_amount'] ?? null) !== '2500.00'
) {
    throw new RuntimeException('Unexpected SasPay response data.');
}

if (
    count($requests) !== 3
    || $requests[0]['method'] !== 'POST'
    || !str_ends_with($requests[0]['url'], '/checkout-sessions/')
    || !in_array('Authorization: Bearer sk_test_unit_test', $requests[0]['headers'], true)
    || json_decode((string) $requests[0]['body'], true)['fee_charge_mode'] !== 'ADD_ON'
    || !str_ends_with($requests[1]['url'], '/checkout-sessions/session-123/status/')
    || !str_ends_with($requests[2]['url'], '/payments/transaction-123/verify/')
) {
    throw new RuntimeException('SasPay request construction is incorrect.');
}

$failedClient = new SasPayClient(
    static fn (): array => [
        'status' => 401,
        'body' => '{"success":false,"error":{"message":"Unauthorized"}}',
    ],
    'sk_test_unit_test',
    'sandbox'
);

try {
    $failedClient->getCheckoutStatus('invalid');
    throw new RuntimeException('Expected SasPay errors to be surfaced.');
} catch (RuntimeException $exception) {
    if ($exception->getMessage() === 'Expected SasPay errors to be surfaced.') {
        throw $exception;
    }
}

echo "SasPay client tests passed." . PHP_EOL;
