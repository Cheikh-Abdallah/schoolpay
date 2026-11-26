<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Repository pour la gestion des forfaits et tarifs SaaS SchoolPay
 */
class PlanRepository
{
    /**
     * Récupère tous les forfaits ordonnés
     */
    public function getAll(): array
    {
        $sql = "SELECT p.*,
                       (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = p.id AND s.status = 'active') as active_subscribers
                FROM plans p
                ORDER BY p.sort_order ASC";

        return Database::fetchAll($sql);
    }

    public function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM plans WHERE id = :id LIMIT 1", [':id' => $id]);
    }

    public function findByCode(string $code): ?array
    {
        return Database::fetchOne("SELECT * FROM plans WHERE code = :code LIMIT 1", [':code' => $code]);
    }

    /**
     * Met à jour les tarifs et quotas d'un forfait
     */
    public function update(int $id, array $data): void
    {
        $sql = "UPDATE plans 
                SET monthly_price = :monthly_price,
                    annual_price = :annual_price,
                    max_students = :max_students,
                    max_users = :max_users,
                    monthly_sms_quota = :monthly_sms_quota,
                    is_active = :is_active
                WHERE id = :id";

        Database::query($sql, [
            ':id' => $id,
            ':monthly_price' => (float) $data['monthly_price'],
            ':annual_price' => !empty($data['annual_price']) ? (float) $data['annual_price'] : null,
            ':max_students' => !empty($data['max_students']) ? (int) $data['max_students'] : null,
            ':max_users' => !empty($data['max_users']) ? (int) $data['max_users'] : null,
            ':monthly_sms_quota' => (int) ($data['monthly_sms_quota'] ?? 0),
            ':is_active' => isset($data['is_active']) ? 1 : 0
        ]);
    }
}
