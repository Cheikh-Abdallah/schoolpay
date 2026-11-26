<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\CSRF;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\FlashHelper;

/**
 * Intercepteur : Validation automatique de la protection CSRF
 */
class CsrfMiddleware
{
    /**
     * Routes exemptées de protection CSRF (webhooks externes authentifiés par signature)
     */
    private array $except = [
        '/api/webhooks/payment',
        '/api/webhooks/sms'
    ];

    public function handle(Request $request): void
    {
        if (!$request->isPost()) {
            return;
        }

        $currentPath = $request->path();
        foreach ($this->except as $exemptPath) {
            if (str_starts_with($currentPath, $exemptPath)) {
                return;
            }
        }

        $token = $request->input('_csrf_token') 
            ?? $request->header('x-csrf-token') 
            ?? $request->header('x-xsrf-token');

        if (!CSRF::validate($token)) {
            Logger::security(sprintf(
                'Échec de validation CSRF sur la route %s (IP : %s)',
                $currentPath,
                $request->ip()
            ));

            if ($request->isAjax()) {
                Response::json(['error' => 'Session expirée ou jeton de sécurité invalide. Veuillez recharger la page.'], 419);
            }

            FlashHelper::error('Votre session a expiré ou le formulaire est invalide. Veuillez réessayer.');
            Response::redirect($currentPath);
        }
    }
}
