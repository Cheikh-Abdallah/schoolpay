<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Helpers\FlashHelper;

/**
 * Intercepteur : Vérifie l'intégrité et le statut de l'établissement du tenant actif
 */
class TenantMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check() || !Auth::isEstablishmentUser()) {
            if ($request->isAjax()) {
                Response::json(['error' => 'Accès établissement requis.'], 403);
            }
            Response::redirect('/login');
        }

        $establishmentId = TenantContext::getTenantId();
        if (!$establishmentId) {
            Auth::logout();
            FlashHelper::error('Aucun établissement associé à ce compte.');
            Response::redirect('/login');
        }

        // Vérification directe du statut dans la base de données
        $est = Database::fetchOne(
            "SELECT id, uuid, name, slug, email, phone, status, currency FROM establishments WHERE id = :id AND deleted_at IS NULL LIMIT 1",
            [':id' => $establishmentId]
        );

        if (!$est) {
            Auth::logout();
            FlashHelper::error('Établissement introuvable ou supprimé.');
            Response::redirect('/login');
        }

        if ($est['status'] === 'suspended') {
            FlashHelper::error('Votre établissement a été temporairement suspendu par HOME SCHOOL. Veuillez contacter le support.');
            Response::redirect('/subscription/suspended');
        }

        if ($est['status'] === 'closed') {
            Auth::logout();
            FlashHelper::error('Ce compte établissement est définitivement clôturé.');
            Response::redirect('/login');
        }

        // Contexte mis à jour
        TenantContext::setTenant($est);
    }
}
