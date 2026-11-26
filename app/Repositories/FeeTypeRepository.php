<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class FeeTypeRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Read ──────────────────────────────────────────────────────────────────

    public function getAllByEstablishment(int $establishmentId, bool $activeOnly = false): array
    {
        $where = 'establishment_id = :est_id AND deleted_at IS NULL';
        if ($activeOnly) {
            $where .= ' AND is_active = 1';
        }

        return $this->db->fetchAll(
            "SELECT * FROM fee_types
             WHERE {$where}
             ORDER BY sort_order ASC, name ASC",
            ['est_id' => $establishmentId]
        );
    }

    public function findById(int $id, int $establishmentId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM fee_types
             WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            ['id' => $id, 'est_id' => $establishmentId]
        ) ?: null;
    }

    // ─── Mutations ─────────────────────────────────────────────────────────────

    public function create(int $establishmentId, array $data): int
    {
        // Determine sort_order
        $maxOrder = (int) $this->db->fetchValue(
            "SELECT COALESCE(MAX(sort_order), 0) FROM fee_types WHERE establishment_id = :est_id",
            ['est_id' => $establishmentId]
        );

        $this->db->execute(
            "INSERT INTO fee_types
                (establishment_id, name, description, default_amount, is_mandatory,
                 is_active, applies_to_class, payment_frequency, sort_order, created_at, updated_at)
             VALUES
                (:est_id, :name, :description, :default_amount, :is_mandatory,
                 1, :applies_to_class, :payment_frequency, :sort_order, NOW(), NOW())",
            [
                'est_id'            => $establishmentId,
                'name'              => trim($data['name']),
                'description'       => $data['description'] ?? null,
                'default_amount'    => (int) ($data['default_amount'] ?? 0),
                'is_mandatory'      => isset($data['is_mandatory']) ? 1 : 0,
                'applies_to_class'  => $data['applies_to_class'] ?? null,
                'payment_frequency' => $data['payment_frequency'] ?? 'annual',
                'sort_order'        => $maxOrder + 1,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $establishmentId, array $data): bool
    {
        return $this->db->execute(
            "UPDATE fee_types
             SET name              = :name,
                 description       = :description,
                 default_amount    = :default_amount,
                 is_mandatory      = :is_mandatory,
                 applies_to_class  = :applies_to_class,
                 payment_frequency = :payment_frequency,
                 updated_at        = NOW()
             WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            [
                'id'                => $id,
                'est_id'            => $establishmentId,
                'name'              => trim($data['name']),
                'description'       => $data['description'] ?? null,
                'default_amount'    => (int) ($data['default_amount'] ?? 0),
                'is_mandatory'      => isset($data['is_mandatory']) ? 1 : 0,
                'applies_to_class'  => $data['applies_to_class'] ?? null,
                'payment_frequency' => $data['payment_frequency'] ?? 'annual',
            ]
        );
    }

    public function toggleActive(int $id, int $establishmentId): bool
    {
        return $this->db->execute(
            "UPDATE fee_types
             SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW()
             WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            ['id' => $id, 'est_id' => $establishmentId]
        );
    }

    public function softDelete(int $id, int $establishmentId): bool
    {
        // Prevent delete if already used in invoices
        $used = (int) $this->db->fetchValue(
            "SELECT COUNT(*) FROM invoice_items ii
             JOIN invoices i ON i.id = ii.invoice_id
             WHERE ii.fee_type_id = :id AND i.establishment_id = :est_id",
            ['id' => $id, 'est_id' => $establishmentId]
        );

        if ($used > 0) {
            throw new \RuntimeException('Ce type de frais est utilisé dans des factures existantes et ne peut pas être supprimé.');
        }

        return $this->db->execute(
            "UPDATE fee_types SET deleted_at = NOW()
             WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            ['id' => $id, 'est_id' => $establishmentId]
        );
    }

    public function updateOrder(int $establishmentId, array $orderedIds): void
    {
        foreach ($orderedIds as $sort => $id) {
            $this->db->execute(
                "UPDATE fee_types SET sort_order = :sort
                 WHERE id = :id AND establishment_id = :est_id",
                ['sort' => (int) $sort, 'id' => (int) $id, 'est_id' => $establishmentId]
            );
        }
    }

    // ─── Stats ─────────────────────────────────────────────────────────────────

    public function getUsageStats(int $establishmentId): array
    {
        return $this->db->fetchAll(
            "SELECT ft.id, ft.name, ft.default_amount, ft.is_active,
                    COUNT(DISTINCT ii.invoice_id) AS usage_count,
                    COALESCE(SUM(ii.amount), 0)   AS total_amount
             FROM fee_types ft
             LEFT JOIN invoice_items ii ON ii.fee_type_id = ft.id
             LEFT JOIN invoices i ON i.id = ii.invoice_id AND i.deleted_at IS NULL
             WHERE ft.establishment_id = :est_id AND ft.deleted_at IS NULL
             GROUP BY ft.id
             ORDER BY ft.sort_order ASC",
            ['est_id' => $establishmentId]
        );
    }
}
