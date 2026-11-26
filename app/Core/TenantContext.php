<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Contexte d'isolation Multi-Tenant centralisé
 * Garantit que chaque opération est rattachée sans équivoque à l'établissement courant
 */
class TenantContext
{
    private static ?int $currentEstablishmentId = null;
    private static ?array $currentEstablishment = null;

    /**
     * Initialise le tenant actif en mémoire
     */
    public static function setTenant(array $establishment): void
    {
        self::$currentEstablishment = $establishment;
        self::$currentEstablishmentId = (int) $establishment['id'];
    }

    /**
     * Définit uniquement l'ID du tenant
     */
    public static function setTenantId(int $id): void
    {
        self::$currentEstablishmentId = $id;
    }

    /**
     * Retourne l'ID du tenant actif
     */
    public static function getTenantId(): ?int
    {
        if (self::$currentEstablishmentId !== null) {
            return self::$currentEstablishmentId;
        }

        // Récupération depuis la session serveur authentifiée
        $sessionEstId = Session::get('establishment_id');
        if ($sessionEstId !== null) {
            self::$currentEstablishmentId = (int) $sessionEstId;
            return self::$currentEstablishmentId;
        }

        return null;
    }

    /**
     * Retourne l'ID de l'établissement ou lève une exception stricte
     */
    public static function requireTenantId(): int
    {
        $id = self::getTenantId();
        if ($id === null || $id <= 0) {
            Logger::security('Tentative d\'opération métier sans contexte tenant valide');
            throw new RuntimeException('Aucun établissement sélectionné dans la session.');
        }
        return $id;
    }

    /**
     * Récupère les données complètes de l'établissement courant
     */
    public static function getTenant(): ?array
    {
        if (self::$currentEstablishment !== null) {
            return self::$currentEstablishment;
        }

        $id = self::getTenantId();
        if ($id !== null) {
            $establishment = Database::fetchOne(
                "SELECT * FROM establishments WHERE id = :id AND deleted_at IS NULL LIMIT 1",
                [':id' => $id]
            );
            if ($establishment) {
                self::$currentEstablishment = $establishment;
                return self::$currentEstablishment;
            }
        }

        return null;
    }

    /**
     * Réinitialise le contexte du tenant
     */
    public static function clear(): void
    {
        self::$currentEstablishmentId = null;
        self::$currentEstablishment = null;
    }
}
