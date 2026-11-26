<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Service central d'authentification et gestion d'état de session utilisateur
 */
class Auth
{
    public const TYPE_PLATFORM = 'platform';
    public const TYPE_ESTABLISHMENT = 'establishment';

    public static function check(): bool
    {
        return Session::has('auth_user_id') && Session::has('auth_user_type');
    }

    public static function id(): ?int
    {
        $id = Session::get('auth_user_id');
        return $id !== null ? (int) $id : null;
    }

    public static function type(): ?string
    {
        return Session::get('auth_user_type');
    }

    public static function role(): ?string
    {
        return Session::get('auth_user_role');
    }

    public static function user(): ?array
    {
        return Session::get('auth_user');
    }

    public static function establishmentId(): ?int
    {
        return TenantContext::getTenantId();
    }

    public static function isPlatformUser(): bool
    {
        return self::check() && self::type() === self::TYPE_PLATFORM;
    }

    public static function isSuperAdmin(): bool
    {
        return self::isPlatformUser() && self::role() === 'super_admin';
    }

    public static function isEstablishmentUser(): bool
    {
        return self::check() && self::type() === self::TYPE_ESTABLISHMENT;
    }

    /**
     * Connecte un utilisateur d'établissement
     */
    public static function loginEstablishmentUser(array $user, array $establishment): void
    {
        Session::regenerate();
        Session::set('auth_user_id', (int) $user['id']);
        Session::set('auth_user_type', self::TYPE_ESTABLISHMENT);
        Session::set('auth_user_role', $user['role']);
        Session::set('auth_user', $user);
        Session::set('establishment_id', (int) $establishment['id']);
        Session::set('establishment', $establishment);

        TenantContext::setTenant($establishment);

        // Mise à jour de la date de dernière connexion
        Database::query(
            "UPDATE establishment_users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id",
            [':id' => $user['id']]
        );

        Logger::info(sprintf('Connexion réussie utilisateur école ID %d (Établissement %s)', $user['id'], $establishment['name']));
    }

    /**
     * Connecte un utilisateur plateforme (Super Admin / Support / Finance)
     */
    public static function loginPlatformUser(array $user): void
    {
        Session::regenerate();
        Session::set('auth_user_id', (int) $user['id']);
        Session::set('auth_user_type', self::TYPE_PLATFORM);
        Session::set('auth_user_role', $user['role']);
        Session::set('auth_user', $user);
        Session::remove('establishment_id');
        Session::remove('establishment');

        TenantContext::clear();

        Database::query(
            "UPDATE platform_users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id",
            [':id' => $user['id']]
        );

        Logger::info(sprintf('Connexion réussie utilisateur plateforme ID %d (Rôle %s)', $user['id'], $user['role']));
    }

    /**
     * Déconnecte l'utilisateur et détruit la session
     */
    public static function logout(): void
    {
        $userId = self::id();
        $type = self::type();

        if ($userId) {
            Logger::info(sprintf('Déconnexion utilisateur %s ID %d', $type, $userId));
        }

        TenantContext::clear();
        Session::destroy();
    }
}
