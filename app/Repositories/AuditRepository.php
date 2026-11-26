<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Repository pour les journaux d'audit et cybersécurité
 */
class AuditRepository
{
    public function getPaginated(int $page = 1, int $perPage = 25, ?string $actorType = null, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["1=1"];

        if ($actorType && $actorType !== 'all') {
            $where[] = "a.actor_type = :actor_type";
            $params[':actor_type'] = $actorType;
        }

        if ($search) {
            $where[] = "(a.action LIKE :search OR a.ip_address LIKE :search OR e.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);

        $total = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM audit_logs a LEFT JOIN establishments e ON e.id = a.establishment_id WHERE {$whereClause}",
            $params
        );

        $sql = "SELECT a.*, e.name as establishment_name
                FROM audit_logs a
                LEFT JOIN establishments e ON e.id = a.establishment_id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $items = Database::fetchAll($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage))
        ];
    }
}
