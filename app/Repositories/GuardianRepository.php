<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class GuardianRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Pagination ────────────────────────────────────────────────────────────

    public function getPaginated(
        int $establishmentId,
        int $page = 1,
        int $perPage = 20,
        string $search = ''
    ): array {
        $offset = ($page - 1) * $perPage;
        $params = ['est_id' => $establishmentId];

        $where = 'g.establishment_id = :est_id';

        if ($search !== '') {
            $where .= ' AND (g.full_name LIKE :search
                         OR g.phone      LIKE :search
                         OR g.email      LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $total = (int) $this->db->fetchValue(
            "SELECT COUNT(*) FROM guardians g WHERE {$where}",
            $params
        );

        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        $rows = $this->db->fetchAll(
            "SELECT
                g.id,
                g.full_name,
                g.phone,
                g.email,
                g.created_at,
                COUNT(DISTINCT sg.student_id) AS student_count,
                COALESCE(financial.total_invoiced, 0) AS total_invoiced,
                COALESCE(financial.total_paid, 0) AS total_paid
             FROM guardians g
             LEFT JOIN student_guardians sg ON sg.guardian_id = g.id
             LEFT JOIN students s ON s.id = sg.student_id AND s.establishment_id = g.establishment_id
             LEFT JOIN (
                 SELECT sgf.guardian_id,
                        SUM(i.total_amount) AS total_invoiced,
                        SUM(i.amount_paid) AS total_paid
                 FROM student_guardians sgf
                 JOIN students sf ON sf.id = sgf.student_id
                 JOIN guardians gf ON gf.id = sgf.guardian_id AND gf.establishment_id = sf.establishment_id
                 JOIN invoices i ON i.student_id = sf.id
                               AND i.establishment_id = sf.establishment_id
                               AND i.status <> 'cancelled'
                 GROUP BY sgf.guardian_id
             ) financial ON financial.guardian_id = g.id
             WHERE {$where}
             GROUP BY g.id, g.full_name, g.phone, g.email, g.created_at,
                      financial.total_invoiced, financial.total_paid
             ORDER BY g.full_name
             LIMIT :limit OFFSET :offset",
            $params
        );

        return [
            'data'        => $rows,
            'total'       => $total,
            'per_page'    => $perPage,
            'current_page'=> $page,
            'last_page'   => (int) ceil($total / $perPage),
        ];
    }

    // ─── Find ──────────────────────────────────────────────────────────────────

    public function findById(int $id, int $establishmentId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM guardians
             WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            ['id' => $id, 'est_id' => $establishmentId]
        ) ?: null;
    }

    public function findWithDetails(int $id, int $establishmentId): ?array
    {
        $guardian = $this->findById($id, $establishmentId);
        if (!$guardian) {
            return null;
        }

        // Linked students
        $guardian['students'] = $this->db->fetchAll(
            "SELECT s.id, s.first_name, s.last_name, s.student_number, s.class_name, s.status,
                    sg.relationship AS student_relationship
             FROM students s
             JOIN student_guardians sg ON sg.student_id = s.id
             WHERE sg.guardian_id = :gid AND s.establishment_id = :est_id AND s.deleted_at IS NULL
             ORDER BY s.last_name, s.first_name",
            ['gid' => $id, 'est_id' => $establishmentId]
        );

        // Financial summary
        $financial = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(i.total_amount), 0) AS total_invoiced,
                COALESCE(SUM(i.balance_due), 0) AS total_balance_due,
                COALESCE(SUM(i.amount_paid), 0) AS total_paid,
                COUNT(DISTINCT i.id) AS invoice_count,
                COUNT(DISTINCT CASE WHEN i.balance_due > 0 THEN i.id END) AS unpaid_count
             FROM student_guardians sg
             JOIN students s ON s.id = sg.student_id AND s.establishment_id = :est_id
             JOIN invoices i ON i.student_id = s.id
                            AND i.establishment_id = s.establishment_id
                            AND i.status <> 'cancelled'
             WHERE sg.guardian_id = :gid",
            ['gid' => $id, 'est_id' => $establishmentId]
        );
        $guardian['financial'] = $financial;

        // Recent invoices
        $guardian['invoices'] = $this->db->fetchAll(
            "SELECT i.id, i.invoice_number, i.total_amount, i.balance_due, i.status,
                    i.due_date, i.created_at,
                    s.first_name AS student_first, s.last_name AS student_last
             FROM student_guardians sg
             JOIN students s ON s.id = sg.student_id AND s.establishment_id = :est_id
             JOIN invoices i ON i.student_id = s.id AND i.establishment_id = s.establishment_id
             WHERE sg.guardian_id = :gid AND i.status <> 'cancelled'
             ORDER BY i.created_at DESC
             LIMIT 10",
            ['gid' => $id, 'est_id' => $establishmentId]
        );

        return $guardian;
    }

    // ─── Mutations ─────────────────────────────────────────────────────────────

    public function create(int $establishmentId, array $data): int
    {
        $this->db->execute(
            "INSERT INTO guardians
                (establishment_id, first_name, last_name, phone, email, relationship,
                 address, profession, id_number, created_at, updated_at)
             VALUES
                (:est_id, :first_name, :last_name, :phone, :email, :relationship,
                 :address, :profession, :id_number, NOW(), NOW())",
            [
                'est_id'       => $establishmentId,
                'first_name'   => trim($data['first_name']),
                'last_name'    => strtoupper(trim($data['last_name'])),
                'phone'        => $data['phone'] ?? null,
                'email'        => $data['email'] ?? null,
                'relationship' => $data['relationship'] ?? 'parent',
                'address'      => $data['address'] ?? null,
                'profession'   => $data['profession'] ?? null,
                'id_number'    => $data['id_number'] ?? null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $establishmentId, array $data): bool
    {
        return $this->db->execute(
            "UPDATE guardians
             SET first_name   = :first_name,
                 last_name    = :last_name,
                 phone        = :phone,
                 email        = :email,
                 relationship = :relationship,
                 address      = :address,
                 profession   = :profession,
                 id_number    = :id_number,
                 updated_at   = NOW()
             WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            [
                'id'           => $id,
                'est_id'       => $establishmentId,
                'first_name'   => trim($data['first_name']),
                'last_name'    => strtoupper(trim($data['last_name'])),
                'phone'        => $data['phone'] ?? null,
                'email'        => $data['email'] ?? null,
                'relationship' => $data['relationship'] ?? 'parent',
                'address'      => $data['address'] ?? null,
                'profession'   => $data['profession'] ?? null,
                'id_number'    => $data['id_number'] ?? null,
            ]
        );
    }

    public function softDelete(int $id, int $establishmentId): bool
    {
        return $this->db->execute(
            "UPDATE guardians SET deleted_at = NOW()
             WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            ['id' => $id, 'est_id' => $establishmentId]
        );
    }

    // ─── Linking students ──────────────────────────────────────────────────────

    public function linkStudent(int $guardianId, int $studentId, int $establishmentId, bool $isPrimary = false): void
    {
        // Cross-tenant safety
        $guard  = $this->findById($guardianId, $establishmentId);
        $student = $this->db->fetchOne(
            "SELECT id FROM students WHERE id = :id AND establishment_id = :est_id AND deleted_at IS NULL",
            ['id' => $studentId, 'est_id' => $establishmentId]
        );

        if (!$guard || !$student) {
            throw new \RuntimeException('Guardian or student not found in this establishment.');
        }

        // Upsert
        $this->db->execute(
            "INSERT INTO student_guardians (student_id, guardian_id, relationship, is_primary, created_at)
             VALUES (:sid, :gid, :rel, :primary, NOW())
             ON DUPLICATE KEY UPDATE relationship = VALUES(relationship), is_primary = VALUES(is_primary)",
            [
                'sid'     => $studentId,
                'gid'     => $guardianId,
                'rel'     => 'parent',
                'primary' => $isPrimary ? 1 : 0,
            ]
        );
    }

    public function unlinkStudent(int $guardianId, int $studentId): void
    {
        $this->db->execute(
            "DELETE FROM student_guardians WHERE guardian_id = :gid AND student_id = :sid",
            ['gid' => $guardianId, 'sid' => $studentId]
        );
    }

    // ─── Search for autocomplete ───────────────────────────────────────────────

    public function search(int $establishmentId, string $term, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT id, first_name, last_name, phone
             FROM guardians
             WHERE establishment_id = :est_id
               AND deleted_at IS NULL
               AND (first_name LIKE :t OR last_name LIKE :t OR phone LIKE :t)
             ORDER BY last_name, first_name
             LIMIT :lim",
            ['est_id' => $establishmentId, 't' => '%' . $term . '%', 'lim' => $limit]
        );
    }
}
