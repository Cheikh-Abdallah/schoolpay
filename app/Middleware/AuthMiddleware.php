<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\FlashHelper;

/**
 * Intercepteur : Vérifie que l'utilisateur est authentifié
 */
class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            if ($request->isAjax()) {
                Response::json(['error' => 'Non authentifié. Veuillez vous connecter.'], 401);
            }

            FlashHelper::warning('Veuillez vous connecter pour accéder à cet espace.');
            Response::redirect('/login');
        }
    }
}
