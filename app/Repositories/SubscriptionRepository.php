<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Repository pour le suivi des abonnements SaaS souscrits par les écoles
 */
class SubscriptionRepository
{
    public function getPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::fetchValue("SELECT COUNT(*) FROM subscriptions");

        $sql = "SELECT s.*, e.name as establishment_name, e.slug as establishment_slug, e.email as establishment_email,
                       p.name as plan_name, p.code as plan_code
                FROM subscriptions s
                JOIN establishments e ON e.id = s.establishment_id
                JOIN plans p ON p.id = s.plan_id
                ORDER BY s.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $items = Database::fetchAll($sql);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage))
        ];
    }

    public function getRecentTransactions(int $limit = 10): array
    {
        $sql = "SELECT pt.*, e.name as establishment_name, p.name as plan_name
                FROM payment_transactions pt
                JOIN establishments e ON e.id = pt.establishment_id
                LEFT JOIN subscriptions s ON s.id = pt.subscription_id
                LEFT JOIN plans p ON p.id = s.plan_id
                ORDER BY pt.created_at DESC
                LIMIT {$limit}";

        return Database::fetchAll($sql);
    }
}
