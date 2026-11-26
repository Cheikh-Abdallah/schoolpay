<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Helpers\CryptoHelper;
use RuntimeException;

/**
 * Client officiel d'intégration de l'API SendText Sénégal
 * https://api.sendtext.sn/v1
 * Authentification via SNT-API-KEY et SNT-API-SECRET
 */
class SendTextService
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $defaultSenderName;

    public function __construct()
    {
        $this->loadCredentials();
    }

    /**
     * Charge les identifiants chiffrés depuis la base ou les variables d'environnement
     */
    private function loadCredentials(): void
    {
        $account = Database::fetchOne(
            "SELECT * FROM sms_provider_accounts WHERE provider_code = 'sendtext' AND is_active = 1 LIMIT 1"
        );

        if ($account && !empty($account['api_key_encrypted']) && !empty($account['api_secret_encrypted'])) {
            $this->baseUrl = rtrim($account['base_url'], '/');
            $this->apiKey = CryptoHelper::decrypt($account['api_key_encrypted']) ?? '';
            $this->apiSecret = CryptoHelper::decrypt($account['api_secret_encrypted']) ?? '';
            $this->defaultSenderName = $account['default_sender_name'] ?? 'SCHOOLPAY';
        } else {
            // Fallback sur le fichier .env
            $this->baseUrl = rtrim(getenv('SENDTEXT_BASE_URL') ?: 'https://api.sendtext.sn/v1', '/');
            $this->apiKey = getenv('SENDTEXT_API_KEY') ?: '';
            $this->apiSecret = getenv('SENDTEXT_API_SECRET') ?: '';
            $this->defaultSenderName = getenv('SENDTEXT_SENDER_NAME') ?: 'SCHOOLPAY';
        }
    }

    /**
     * Vérifie si le service SendText est configuré avec des clés API valides
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Interroge le solde réel de SMS disponible chez SendText (GET /balance)
     */
    public function getBalance(): ?int
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $response = $this->request('GET', '/balance');

        if ($response && isset($response['balance'])) {
            $balance = (int) $response['balance'];

            // Mise à jour en base de données du dernier solde relevé
            Database::query(
                "UPDATE sms_provider_accounts 
                 SET last_balance = :balance, balance_checked_at = CURRENT_TIMESTAMP
                 WHERE provider_code = 'sendtext'",
                [':balance' => $balance]
            );

            return $balance;
        }

        return null;
    }

    /**
     * Envoie un SMS unitaire (POST /sms)
     */
    public function sendSingle(string $recipientPhone, string $message, ?string $senderName = null): array
    {
        $sender = $senderName ?: $this->defaultSenderName;

        // Nettoyage du numéro de téléphone sénégalais (ex: 221771234567)
        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
        if (strlen($cleanPhone) === 9) {
            $cleanPhone = '221' . $cleanPhone;
        }

        $payload = [
            'recipient' => $cleanPhone,
            'message' => $message,
            'sender_name' => $sender
        ];

        $response = $this->request('POST', '/sms', $payload);

        return [
            'success' => ($response['status'] ?? '') === 'success' || isset($response['message_id']),
            'message_id' => $response['message_id'] ?? null,
            'sms_count' => (int) ($response['sms_count'] ?? 1),
            'raw_response' => $response
        ];
    }

    /**
     * Envoie des SMS groupés (POST /bulk_sms)
     */
    public function sendBulk(array $recipients, string $message, ?string $senderName = null): array
    {
        $sender = $senderName ?: $this->defaultSenderName;

        $cleanPhones = [];
        foreach ($recipients as $phone) {
            $c = preg_replace('/[^0-9]/', '', (string) $phone);
            if (strlen($c) === 9) {
                $c = '221' . $c;
            }
            if (!empty($c)) {
                $cleanPhones[] = $c;
            }
        }

        $payload = [
            'recipients' => $cleanPhones,
            'message' => $message,
            'sender_name' => $sender
        ];

        $response = $this->request('POST', '/bulk_sms', $payload);

        return [
            'success' => ($response['status'] ?? '') === 'success' || isset($response['campaign_id']),
            'campaign_id' => $response['campaign_id'] ?? null,
            'total_sent' => (int) ($response['total_sent'] ?? count($cleanPhones)),
            'total_sms_consumed' => (int) ($response['total_sms_consumed'] ?? count($cleanPhones)),
            'raw_response' => $response
        ];
    }

    /**
     * Exécute une requête HTTP sécurisée vers l'API SendText
     */
    private function request(string $method, string $endpoint, array $data = []): ?array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Accept: application/json',
            'SNT-API-KEY: ' . $this->apiKey,
            'SNT-API-SECRET: ' . $this->apiSecret
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            Logger::error("Erreur cURL SendText ({$endpoint}) : {$error}");
            return null;
        }

        $decoded = json_decode($result, true);
        if ($httpCode >= 400) {
            Logger::error("Erreur HTTP {$httpCode} SendText ({$endpoint}) : " . $result);
        }

        return is_array($decoded) ? $decoded : null;
    }
}
