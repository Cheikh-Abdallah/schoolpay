<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use RuntimeException;

class SasPayClient
{
    private string $apiKey;
    private string $baseUrl;
    private ?Closure $transport;

    /**
     * Optional transport and credentials allow isolated client tests.
     */
    public function __construct(
        ?callable $transport = null,
        ?string $apiKey = null,
        ?string $environment = null
    )
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $saspay = $config['saspay'];

        $environment = $environment ?? $saspay['environment'];
        $this->apiKey = $apiKey ?? (string) $saspay['api_key'];
        $this->baseUrl = rtrim((string) $saspay['base_url'], '/');
        $this->transport = $transport !== null ? Closure::fromCallable($transport) : null;

        if ($this->apiKey === '') {
            throw new RuntimeException('La clé API SasPay n’est pas configurée.');
        }

        $expectedPrefix = $environment === 'live' ? 'sk_live_' : 'sk_test_';
        if (!str_starts_with($this->apiKey, $expectedPrefix)) {
            throw new RuntimeException('La clé API SasPay ne correspond pas à l’environnement configuré.');
        }

        if ($environment === 'live' && !str_starts_with(APP_URL, 'https://')) {
            throw new RuntimeException('APP_URL doit utiliser HTTPS pour les paiements SasPay en production.');
        }
    }

    public function createCheckoutSession(array $payload): array
    {
        return $this->request('POST', '/checkout-sessions/', $payload);
    }

    public function getCheckoutStatus(string $sessionId): array
    {
        return $this->request('GET', '/checkout-sessions/' . rawurlencode($sessionId) . '/status/');
    }

    public function verifyPayment(string $paymentId): array
    {
        return $this->request('GET', '/payments/' . rawurlencode($paymentId) . '/verify/');
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];
        $body = null;

        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        if ($this->transport !== null) {
            $response = ($this->transport)($method, $this->baseUrl . $path, $headers, $body);
            $statusCode = (int) ($response['status'] ?? 0);
            $responseBody = (string) ($response['body'] ?? '');
        } else {
            if (!function_exists('curl_init')) {
                throw new RuntimeException('L’extension PHP cURL est requise pour SasPay.');
            }

            $handle = curl_init($this->baseUrl . $path);
            if ($handle === false) {
                throw new RuntimeException('Impossible d’initialiser la connexion SasPay.');
            }

            curl_setopt_array($handle, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            if ($body !== null) {
                curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
            }

            $result = curl_exec($handle);
            if ($result === false) {
                $message = curl_error($handle);
                curl_close($handle);
                throw new RuntimeException('Connexion SasPay impossible : ' . $message);
            }

            $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);
            $responseBody = $result;
        }

        $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Réponse SasPay invalide.');
        }

        if ($statusCode < 200 || $statusCode >= 300 || ($decoded['success'] ?? true) !== true) {
            $message = $decoded['error']['message'] ?? $decoded['message'] ?? 'La requête SasPay a échoué.';
            throw new RuntimeException((string) $message);
        }

        $data = $decoded['data'] ?? null;
        if (!is_array($data)) {
            throw new RuntimeException('La réponse SasPay ne contient pas de données exploitables.');
        }

        return $data;
    }
}
