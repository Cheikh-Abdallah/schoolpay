<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;

/**
 * Service central d'authentification, de contrôle anti-brute-force et de hachage
 */
class AuthenticationService
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes de blocage

    /**
     * Authentifie un utilisateur d'établissement
     */
    public function authenticateEstablishmentUser(string $email, string $password): ?array
    {
        $this->checkBruteForceLockout($email);

        $sql = "SELECT eu.*, e.status as establishment_status, e.name as establishment_name
                FROM establishment_users eu
                JOIN establishments e ON e.id = eu.establishment_id
                WHERE eu.email = :email 
                  AND eu.is_active = 1 
                  AND e.deleted_at IS NULL
                LIMIT 1";

        $user = Database::fetchOne($sql, [':email' => strtolower(trim($email))]);

        if (!$user) {
            $this->recordFailedAttempt($email);
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($email);
            return null;
        }

        // Réinitialise les tentatives infructueuses
        $this->clearFailedAttempts($email);

        // Si l'algorithme a évolué, mise à jour transparente du hachage
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            Database::query(
                "UPDATE establishment_users SET password_hash = :hash WHERE id = :id",
                [':hash' => $newHash, ':id' => $user['id']]
            );
        }

        $this->logAudit(
            'establishment_user',
            (int) $user['id'],
            'auth.login_success',
            (int) $user['establishment_id']
        );

        return $user;
    }

    /**
     * Authentifie un utilisateur de la plateforme HOME SCHOOL
     */
    public function authenticatePlatformUser(string $email, string $password): ?array
    {
        $this->checkBruteForceLockout($email);

        $sql = "SELECT * FROM platform_users WHERE email = :email AND is_active = 1 LIMIT 1";
        $user = Database::fetchOne($sql, [':email' => strtolower(trim($email))]);

        if (!$user) {
            $this->recordFailedAttempt($email);
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($email);
            return null;
        }

        $this->clearFailedAttempts($email);

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            Database::query(
                "UPDATE platform_users SET password_hash = :hash WHERE id = :id",
                [':hash' => $newHash, ':id' => $user['id']]
            );
        }

        $this->logAudit(
            'platform_user',
            (int) $user['id'],
            'auth.superadmin_login_success',
            null
        );

        return $user;
    }

    /**
     * Récupère l'établissement associé à un utilisateur
     */
    public function getEstablishmentForUser(int $userId): ?array
    {
        $sql = "SELECT e.* 
                FROM establishments e
                JOIN establishment_users eu ON eu.establishment_id = e.id
                WHERE eu.id = :user_id AND e.deleted_at IS NULL
                LIMIT 1";

        return Database::fetchOne($sql, [':user_id' => $userId]);
    }

    /**
     * Protection anti-brute force : vérifie si l'IP ou le compte est verrouillé
     */
    private function checkBruteForceLockout(string $email): void
    {
        $key = 'login_attempts_' . md5(strtolower(trim($email)));
        $attempts = Session::get($key, ['count' => 0, 'locked_until' => 0]);

        if ($attempts['locked_until'] > time()) {
            $remaining = ceil(($attempts['locked_until'] - time()) / 60);
            Logger::security(sprintf('Tentative de connexion bloquée par verrouillage pour %s', $email));
            throw new \RuntimeException("Trop de tentatives infructueuses. Veuillez réessayer dans {$remaining} minute(s).");
        }
    }

    private function recordFailedAttempt(string $email): void
    {
        $key = 'login_attempts_' . md5(strtolower(trim($email)));
        $attempts = Session::get($key, ['count' => 0, 'locked_until' => 0]);
        $attempts['count']++;

        if ($attempts['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $attempts['locked_until'] = time() + self::LOCKOUT_TIME;
            Logger::security(sprintf('Verrouillage déclenché (5 échecs consécutifs) pour %s', $email));
        }

        Session::set($key, $attempts);
    }

    private function clearFailedAttempts(string $email): void
    {
        $key = 'login_attempts_' . md5(strtolower(trim($email)));
        Session::remove($key);
    }

    /**
     * Journalise les connexions dans audit_logs
     */
    private function logAudit(string $actorType, int $actorId, string $action, ?int $establishmentId): void
    {
        try {
            Database::query(
                "INSERT INTO audit_logs (establishment_id, actor_type, actor_id, action, ip_address, user_agent, created_at)
                 VALUES (:est_id, :actor_type, :actor_id, :action, :ip, :ua, CURRENT_TIMESTAMP)",
                [
                    ':est_id' => $establishmentId,
                    ':actor_type' => $actorType,
                    ':actor_id' => $actorId,
                    ':action' => $action,
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    ':ua' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500)
                ]
            );
        } catch (\Throwable $e) {
            Logger::error('Erreur écriture audit log : ' . $e->getMessage());
        }
    }
}
