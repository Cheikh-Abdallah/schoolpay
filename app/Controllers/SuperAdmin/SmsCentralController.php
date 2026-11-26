<?php

declare(strict_types=1);

namespace App\Controllers\SuperAdmin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Helpers\CryptoHelper;
use App\Helpers\FlashHelper;
use App\Services\SendTextService;

/**
 * Contrôleur de gestion centralisée des SMS SendText et des quotas par école
 */
class SmsCentralController extends Controller
{
    private SendTextService $sendTextService;

    public function __construct()
    {
        $this->sendTextService = new SendTextService();
    }

    public function index(Request $request): void
    {
        // Compte SendText central
        $providerAccount = Database::fetchOne(
            "SELECT * FROM sms_provider_accounts WHERE provider_code = 'sendtext' LIMIT 1"
        );

        $liveBalance = $this->sendTextService->getBalance();

        // Répartition des crédits par établissement
        $sql = "SELECT e.id, e.name, e.slug, e.status,
                       COALESCE(sca.available_credits, 0) as available_credits,
                       COALESCE(sca.lifetime_credited, 0) as lifetime_credited,
                       COALESCE(sca.lifetime_consumed, 0) as lifetime_consumed
                FROM establishments e
                LEFT JOIN sms_credit_accounts sca ON sca.establishment_id = e.id
                WHERE e.deleted_at IS NULL
                ORDER BY sca.available_credits DESC";

        $establishmentCredits = Database::fetchAll($sql);

        // Dernières transactions de crédits
        $recentTransactions = Database::fetchAll(
            "SELECT sct.*, e.name as establishment_name
             FROM sms_credit_transactions sct
             JOIN establishments e ON e.id = sct.establishment_id
             ORDER BY sct.created_at DESC
             LIMIT 15"
        );

        $this->render('superadmin.sms.index', [
            'title' => 'Gestion Centrale SMS SendText',
            'providerAccount' => $providerAccount,
            'liveBalance' => $liveBalance,
            'isConfigured' => $this->sendTextService->isConfigured(),
            'establishmentCredits' => $establishmentCredits,
            'recentTransactions' => $recentTransactions
        ], 'superadmin');
    }

    public function saveSettings(Request $request): void
    {
        $data = $this->validate($request, [
            'base_url' => 'required',
            'api_key' => 'required',
            'api_secret' => 'required',
            'sender_name' => 'required|max:11'
        ]);

        $encryptedKey = CryptoHelper::encrypt($data['api_key']);
        $encryptedSecret = CryptoHelper::encrypt($data['api_secret']);

        Database::query(
            "INSERT INTO sms_provider_accounts 
             (provider_code, base_url, api_key_encrypted, api_secret_encrypted, default_sender_name, is_active, created_at)
             VALUES ('sendtext', :base_url, :api_key, :api_secret, :sender, 1, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE 
                base_url = VALUES(base_url),
                api_key_encrypted = VALUES(api_key_encrypted),
                api_secret_encrypted = VALUES(api_secret_encrypted),
                default_sender_name = VALUES(default_sender_name),
                is_active = 1",
            [
                ':base_url' => $data['base_url'],
                ':api_key' => $encryptedKey,
                ':api_secret' => $encryptedSecret,
                ':sender' => strtoupper(trim($data['sender_name']))
            ]
        );

        Logger::info('Paramètres SendText mis à jour par Super Admin avec chiffrement AES-256-GCM.');
        FlashHelper::success('Les identifiants SendText ont été chiffrés et enregistrés avec succès.');
        $this->redirect('/superadmin/sms');
    }

    public function grantCredits(Request $request): void
    {
        $data = $this->validate($request, [
            'establishment_id' => 'required|numeric',
            'quantity' => 'required|numeric|positive',
            'description' => 'required|max:255'
        ]);

        $establishmentId = (int) $data['establishment_id'];
        $qty = (int) $data['quantity'];
        $desc = trim($data['description']);

        Database::transaction(function () use ($establishmentId, $qty, $desc) {
            Database::query(
                "INSERT INTO sms_credit_accounts (establishment_id, available_credits, lifetime_credited, lifetime_consumed, updated_at)
                 VALUES (:est_id, :qty, :qty, 0, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE available_credits = available_credits + :qty2, lifetime_credited = lifetime_credited + :qty3",
                [':est_id' => $establishmentId, ':qty' => $qty, ':qty2' => $qty, ':qty3' => $qty]
            );

            $acc = Database::fetchOne("SELECT available_credits FROM sms_credit_accounts WHERE establishment_id = :id", [':id' => $establishmentId]);
            $newBalance = (int) $acc['available_credits'];

            $idempotency = 'admin_grant_' . $establishmentId . '_' . time();

            Database::query(
                "INSERT INTO sms_credit_transactions 
                 (establishment_id, transaction_type, quantity, balance_after, source_type, description, idempotency_key, created_at)
                 VALUES (:est_id, 'adjustment', :qty, :bal, 'admin', :desc, :idem, CURRENT_TIMESTAMP)",
                [
                    ':est_id' => $establishmentId,
                    ':qty' => $qty,
                    ':bal' => $newBalance,
                    ':desc' => $desc,
                    ':idem' => $idempotency
                ]
            );
        });

        FlashHelper::success("Dotation de {$qty} SMS créditée à l'établissement.");
        $this->redirect('/superadmin/sms');
    }

    public function syncBalance(Request $request): void
    {
        $balance = $this->sendTextService->getBalance();

        if ($balance !== null) {
            FlashHelper::success("Solde SendText synchronisé : {$balance} SMS disponibles.");
        } else {
            FlashHelper::error("Impossible de joindre l'API SendText. Vérifiez vos identifiants et votre connexion.");
        }

        $this->redirect('/superadmin/sms');
    }
}
