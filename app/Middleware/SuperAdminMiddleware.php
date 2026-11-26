<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\FlashHelper;

/**
 * Intercepteur : Vérifie que l'utilisateur appartient à la plateforme HOME SCHOOL
 */
class SuperAdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check() || !Auth::isPlatformUser()) {
            if ($request->isAjax()) {
                Response::json(['error' => 'Accès réservé à l\'administration HOME SCHOOL.'], 403);
            }

            FlashHelper::error('Accès refusé. Espace réservé à l\'équipe HOME SCHOOL.');
            Response::redirect('/superadmin/login');
        }
    }
}
