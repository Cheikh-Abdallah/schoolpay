<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Helpers\FlashHelper;

/**
 * Intercepteur : Contrôle la validité de la période d'essai ou de l'abonnement SaaS
 */
class SubscriptionMiddleware
{
    public function handle(Request $request): void
    {
        $establishmentId = TenantContext::getTenantId();
        if (!$establishmentId) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        // 1. Vérification d'un abonnement payant actif
        $sub = Database::fetchOne(
            "SELECT s.*, p.name as plan_name, p.max_students, p.max_users, p.monthly_sms_quota
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.establishment_id = :est_id 
               AND s.status = 'active'
               AND (s.current_period_end IS NULL OR s.current_period_end >= :now)
             ORDER BY s.id DESC LIMIT 1",
            [':est_id' => $establishmentId, ':now' => $now]
        );

        if ($sub) {
            // Abonnement actif valide
            return;
        }

        // 2. Vérification de la période d'essai gratuit (72h)
        $trial = Database::fetchOne(
            "SELECT * FROM trials WHERE establishment_id = :est_id ORDER BY id DESC LIMIT 1",
            [':est_id' => $establishmentId]
        );

        if ($trial) {
            if ($trial['status'] === 'running' && $trial['ends_at'] > $now) {
                // Essai gratuit encore en cours
                return;
            }

            // Si l'essai vient d'expirer, mise à jour automatique en base de données
            if ($trial['status'] === 'running' && $trial['ends_at'] <= $now) {
                Database::query(
                    "UPDATE trials SET status = 'expired' WHERE id = :id",
                    [':id' => $trial['id']]
                );
                Database::query(
                    "UPDATE establishments SET status = 'pending' WHERE id = :id AND status = 'trial'",
                    [':id' => $establishmentId]
                );
            }
        }

        // 3. À ce stade, ni abonnement actif, ni période d'essai en cours
        // Les routes d'abonnement (/subscription/*) et de déconnexion (/logout) restent toujours autorisées
        $currentPath = $request->path();
        if (str_starts_with($currentPath, '/subscription') || $currentPath === '/logout') {
            return;
        }

        // Blocage des opérations de création/modification si l'accès est expiré
        if ($request->isPost()) {
            if ($request->isAjax()) {
                Response::json([
                    'error' => 'Votre période d\'essai ou abonnement a expiré. Veuillez souscrire une formule pour continuer.',
                    'redirect' => url('/subscription/upgrade')
                ], 403);
            }

            FlashHelper::warning('Votre période d\'essai a expiré. Veuillez choisir un forfait pour activer toutes les fonctionnalités.');
            Response::redirect('/subscription/upgrade');
        }
    }
}
