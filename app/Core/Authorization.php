<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Moteur d'autorisation RBAC (Role-Based Access Control)
 */
class Authorization
{
    private static ?array $permissionsConfig = null;

    private static function getConfig(): array
    {
        if (self::$permissionsConfig === null) {
            self::$permissionsConfig = require dirname(__DIR__, 2) . '/config/permissions.php';
        }
        return self::$permissionsConfig;
    }

    /**
     * Vérifie si l'utilisateur possède l'un des rôles indiqués
     */
    public static function hasRole(string|array $roles): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $userRole = Auth::role();
        if (!$userRole) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($userRole, $roles, true);
    }

    /**
     * Vérifie si l'utilisateur possède une permission donnée
     */
    public static function can(string $permission): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $config = self::getConfig();
        $type = Auth::type();
        $role = Auth::role();

        if (!$role) {
            return false;
        }

        // Cas des utilisateurs de la plateforme
        if ($type === Auth::TYPE_PLATFORM) {
            if ($role === 'super_admin') {
                return true; // Le super administrateur a tous les droits
            }
            $rolePerms = $config['platform_roles'][$role]['permissions'] ?? [];
            return in_array($permission, $rolePerms, true);
        }

        // Cas des utilisateurs d'établissement
        if ($type === Auth::TYPE_ESTABLISHMENT) {
            if ($role === 'owner') {
                return true; // Le fondateur de l'école a tous les droits au sein de son école
            }
            $rolePerms = $config['establishment_roles'][$role]['permissions'] ?? [];
            return in_array($permission, $rolePerms, true);
        }

        return false;
    }

    /**
     * Exige une permission sous peine de bloquer la requête en 403
     */
    public static function authorize(string $permission): void
    {
        if (!self::can($permission)) {
            Logger::security(sprintf(
                'Accès refusé : utilisateur ID %s (rôle %s) a tenté d\'exécuter l\'action %s',
                Auth::id() ?? 'anonyme',
                Auth::role() ?? 'aucun',
                $permission
            ));

            Response::json(['error' => 'Action non autorisée pour votre profil.'], 403);
        }
    }
}
