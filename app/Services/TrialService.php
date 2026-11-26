<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

/**
 * Service de gestion de la période d'essai gratuit (72 heures) et des 10 SMS offerts
 */
class TrialService
{
    private const DEFAULT_TRIAL_HOURS = 72;
    private const DEFAULT_INITIAL_SMS = 10;

    /**
     * Démarre la période d'essai gratuit de 72 heures pour un établissement
     */
    public function startTrial(int $establishmentId, int $hours = self::DEFAULT_TRIAL_HOURS, int $initialSms = self::DEFAULT_INITIAL_SMS): array
    {
        return Database::transaction(function () use ($establishmentId, $hours, $initialSms) {
            // Vérifie si l'établissement a déjà bénéficié d'un essai (règle stricte : 1 seul essai à vie)
            $existing = Database::fetchOne(
                "SELECT * FROM trials WHERE establishment_id = :id LIMIT 1",
                [':id' => $establishmentId]
            );

            if ($existing) {
                throw new RuntimeException("Cet établissement a déjà bénéficié d'une période d'essai gratuit.");
            }

            $startedAt = date('Y-m-d H:i:s');
            $endsAt = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

            // 1. Enregistrement de l'essai
            Database::query(
                "INSERT INTO trials (establishment_id, started_at, ends_at, status, initial_sms_credits, created_at)
                 VALUES (:est_id, :started, :ends, 'running', :sms, CURRENT_TIMESTAMP)",
                [
                    ':est_id' => $establishmentId,
                    ':started' => $startedAt,
                    ':ends' => $endsAt,
                    ':sms' => $initialSms
                ]
            );
            $trialId = (int) Database::lastInsertId();

            // 2. Passage du statut établissement à 'trial'
            Database::query(
                "UPDATE establishments SET status = 'trial' WHERE id = :id",
                [':id' => $establishmentId]
            );

            // 3. Initialisation du compte de crédits SMS avec les 10 SMS offerts
            Database::query(
                "INSERT INTO sms_credit_accounts (establishment_id, available_credits, lifetime_credited, lifetime_consumed, updated_at)
                 VALUES (:est_id, :credits_avail, :credits_life, 0, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE available_credits = available_credits + :credits_dup, lifetime_credited = lifetime_credited + :credits_dup2",
                [
                    ':est_id' => $establishmentId,
                    ':credits_avail' => $initialSms,
                    ':credits_life' => $initialSms,
                    ':credits_dup' => $initialSms,
                    ':credits_dup2' => $initialSms
                ]
            );

            // 4. Enregistrement de la transaction immuable de crédits SMS avec clé d'idempotence
            $idempotencyKey = "trial_grant_est_{$establishmentId}";
            Database::query(
                "INSERT INTO sms_credit_transactions 
                 (establishment_id, transaction_type, quantity, balance_after, source_type, source_id, description, idempotency_key, created_at)
                 VALUES (:est_id, 'trial_grant', :qty, :bal, 'trial', :trial_id, :desc, :idem, CURRENT_TIMESTAMP)",
                [
                    ':est_id' => $establishmentId,
                    ':qty' => $initialSms,
                    ':bal' => $initialSms,
                    ':trial_id' => $trialId,
                    ':desc' => "Dotation de bienvenue : {$initialSms} SMS offerts (Essai 72h)",
                    ':idem' => $idempotencyKey
                ]
            );

            Logger::info("Essai gratuit démarré pour l'école ID {$establishmentId} (Fin : {$endsAt}, {$initialSms} SMS)");

            return [
                'trial_id' => $trialId,
                'started_at' => $startedAt,
                'ends_at' => $endsAt,
                'sms_credits' => $initialSms
            ];
        });
    }

    /**
     * Récupère les informations détaillées de l'essai
     */
    public function getTrialDetails(int $establishmentId): ?array
    {
        $sql = "SELECT * FROM trials WHERE establishment_id = :id ORDER BY id DESC LIMIT 1";
        $trial = Database::fetchOne($sql, [':id' => $establishmentId]);

        if (!$trial) {
            return null;
        }

        $now = time();
        $endTime = strtotime($trial['ends_at']);
        $remainingSeconds = max(0, $endTime - $now);

        $trial['remaining_seconds'] = $remainingSeconds;
        $trial['remaining_hours'] = (int) floor($remainingSeconds / 3600);
        $trial['remaining_minutes'] = (int) floor(($remainingSeconds % 3600) / 60);
        $trial['is_active'] = ($trial['status'] === 'running' && $remainingSeconds > 0);

        return $trial;
    }

    /**
     * Vérifie si l'essai gratuit est actuellement actif
     */
    public function isTrialActive(int $establishmentId): bool
    {
        $trial = $this->getTrialDetails($establishmentId);
        return $trial !== null && $trial['is_active'];
    }
}
