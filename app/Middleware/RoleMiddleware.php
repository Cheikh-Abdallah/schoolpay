<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Authorization;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\FlashHelper;

/**
 * Intercepteur : Contrôle les permissions RBAC sur une route spécifique
 */
class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(Request $request): void
    {
        if (empty($this->allowedRoles)) {
            return;
        }

        if (!Authorization::hasRole($this->allowedRoles)) {
            if ($request->isAjax()) {
                Response::json(['error' => 'Action non autorisée pour votre profil.'], 403);
            }

            FlashHelper::error('Vous ne disposez pas des droits nécessaires pour accéder à cette fonctionnalité.');
            Response::redirect('/dashboard');
        }
    }
}
