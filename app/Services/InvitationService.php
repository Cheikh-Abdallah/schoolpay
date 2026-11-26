<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

/**
 * Service de gestion des invitations d'onboarding délivrées par HOME SCHOOL
 */
class InvitationService
{
    private const DEFAULT_EXPIRY_DAYS = 7;

    /**
     * Crée une nouvelle invitation pour un établissement pilote
     */
    public function createInvitation(int $invitedByPlatformUserId, string $email, int $validDays = self::DEFAULT_EXPIRY_DAYS): array
    {
        $email = strtolower(trim($email));

        // Vérification si une invitation active existe déjà pour cet email
        $existing = Database::fetchOne(
            "SELECT * FROM establishment_invitations 
             WHERE invited_email = :email AND status = 'pending' AND expires_at > CURRENT_TIMESTAMP
             LIMIT 1",
            [':email' => $email]
        );

        if ($existing) {
            throw new RuntimeException("Une invitation en attente existe déjà pour l'adresse {$email}.");
        }

        // Génération d'un jeton aléatoire cryptographique de 32 octets (64 caractères hexadécimaux)
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$validDays} days"));

        Database::query(
            "INSERT INTO establishment_invitations 
             (establishment_id, invited_email, token_hash, invited_by, status, expires_at, created_at)
             VALUES (NULL, :email, :token_hash, :invited_by, 'pending', :expires_at, CURRENT_TIMESTAMP)",
            [
                ':email' => $email,
                ':token_hash' => $tokenHash,
                ':invited_by' => $invitedByPlatformUserId,
                ':expires_at' => $expiresAt
            ]
        );

        $invitationId = (int) Database::lastInsertId();

        $inviteUrl = url('/invitation/accept?token=' . $rawToken);

        Logger::info(sprintf('Invitation créée pour %s par admin ID %d (ID %d)', $email, $invitedByPlatformUserId, $invitationId));

        return [
            'id' => $invitationId,
            'email' => $email,
            'raw_token' => $rawToken,
            'invite_url' => $inviteUrl,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Vérifie la validité d'un jeton brut d'invitation
     */
    public function validateToken(string $rawToken): ?array
    {
        if (empty($rawToken) || strlen($rawToken) !== 64) {
            return null;
        }

        $tokenHash = hash('sha256', $rawToken);

        $sql = "SELECT i.*, pu.full_name as invited_by_name 
                FROM establishment_invitations i
                LEFT JOIN platform_users pu ON pu.id = i.invited_by
                WHERE i.token_hash = :hash
                LIMIT 1";

        $invitation = Database::fetchOne($sql, [':hash' => $tokenHash]);

        if (!$invitation) {
            return null;
        }

        // Vérification de la date d'expiration
        if ($invitation['status'] === 'pending' && strtotime($invitation['expires_at']) <= time()) {
            Database::query(
                "UPDATE establishment_invitations SET status = 'expired' WHERE id = :id",
                [':id' => $invitation['id']]
            );
            $invitation['status'] = 'expired';
        }

        if ($invitation['status'] !== 'pending') {
            return null;
        }

        return $invitation;
    }

    /**
     * Marque l'invitation comme acceptée et rattachée à l'établissement créé
     */
    public function acceptInvitation(int $invitationId, int $establishmentId): void
    {
        Database::query(
            "UPDATE establishment_invitations 
             SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP, establishment_id = :est_id
             WHERE id = :id AND status = 'pending'",
            [
                ':est_id' => $establishmentId,
                ':id' => $invitationId
            ]
        );
    }

    /**
     * Révoque une invitation
     */
    public function revokeInvitation(int $invitationId): void
    {
        Database::query(
            "UPDATE establishment_invitations SET status = 'revoked' WHERE id = :id AND status = 'pending'",
            [':id' => $invitationId]
        );
        Logger::info("Invitation ID {$invitationId} révoquée.");
    }

    /**
     * Liste toutes les invitations avec détails
     */
    public function getAllInvitations(): array
    {
        $sql = "SELECT i.*, pu.full_name as invited_by_name, e.name as establishment_name
                FROM establishment_invitations i
                LEFT JOIN platform_users pu ON pu.id = i.invited_by
                LEFT JOIN establishments e ON e.id = i.establishment_id
                ORDER BY i.created_at DESC";

        return Database::fetchAll($sql);
    }
}
