<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Repository pour la gestion des élèves avec isolation Multi-Tenant stricte
 */
class StudentRepository
{
    /**
     * Liste paginée des élèves d'un établissement avec recherche et filtres
     */
    public function getPaginated(int $establishmentId, int $page = 1, int $perPage = 15, ?string $className = null, ?string $status = null, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [':est_id' => $establishmentId];
        $where = ["s.establishment_id = :est_id"];

        if ($className && $className !== 'all') {
            $where[] = "s.class_name = :class_name";
            $params[':class_name'] = $className;
        }

        if ($status && $status !== 'all') {
            $where[] = "s.status = :status";
            $params[':status'] = $status;
        }

        if ($search) {
            $where[] = "(s.first_name LIKE :search OR s.last_name LIKE :search OR s.student_number LIKE :search OR s.phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);

        $total = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM students s WHERE {$whereClause}",
            $params
        );

        $sql = "SELECT s.*, 
                       (SELECT GROUP_CONCAT(g.full_name SEPARATOR ', ') 
                        FROM student_guardians sg 
                        JOIN guardians g ON g.id = sg.guardian_id 
                        WHERE sg.student_id = s.id) as guardians_names,
                       COALESCE((SELECT SUM(inv.total_amount) FROM invoices inv WHERE inv.student_id = s.id AND inv.status != 'cancelled'), 0) as total_invoiced,
                       COALESCE((SELECT SUM(inv.amount_paid) FROM invoices inv WHERE inv.student_id = s.id AND inv.status != 'cancelled'), 0) as total_paid,
                       COALESCE((SELECT SUM(inv.balance_due) FROM invoices inv WHERE inv.student_id = s.id AND inv.status != 'cancelled'), 0) as total_balance_due
                FROM students s
                WHERE {$whereClause}
                ORDER BY s.last_name ASC, s.first_name ASC
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

    /**
     * Récupère la liste distincte des classes enregistrées pour cet établissement
     */
    public function getDistinctClasses(int $establishmentId): array
    {
        $sql = "SELECT DISTINCT class_name 
                FROM students 
                WHERE establishment_id = :est_id AND class_name IS NOT NULL AND class_name != ''
                ORDER BY class_name ASC";

        $rows = Database::fetchAll($sql, [':est_id' => $establishmentId]);
        return array_column($rows, 'class_name');
    }

    /**
     * Récupère un élève par son ID en vérifiant scrupuleusement l'établissement
     */
    public function findById(int $establishmentId, int $studentId): ?array
    {
        $sql = "SELECT s.* 
                FROM students s 
                WHERE s.id = :id AND s.establishment_id = :est_id 
                LIMIT 1";

        return Database::fetchOne($sql, [':id' => $studentId, ':est_id' => $establishmentId]);
    }

    /**
     * Récupère un élève avec l'ensemble de ses tuteurs et son historique financier
     */
    public function findWithDetails(int $establishmentId, int $studentId): ?array
    {
        $student = $this->findById($establishmentId, $studentId);
        if (!$student) {
            return null;
        }

        // Tuteurs rattachés
        $guardiansSql = "SELECT g.*, sg.relationship_label, sg.is_primary
                         FROM student_guardians sg
                         JOIN guardians g ON g.id = sg.guardian_id
                         WHERE sg.student_id = :student_id AND g.establishment_id = :est_id";
        $student['guardians'] = Database::fetchAll($guardiansSql, [
            ':student_id' => $studentId,
            ':est_id' => $establishmentId
        ]);

        // Factures scolaires de l'élève
        $invoicesSql = "SELECT inv.*, ay.label as academic_year_label
                        FROM invoices inv
                        LEFT JOIN academic_years ay ON ay.id = inv.academic_year_id
                        WHERE inv.student_id = :student_id AND inv.establishment_id = :est_id
                        ORDER BY inv.issue_date DESC";
        $student['invoices'] = Database::fetchAll($invoicesSql, [
            ':student_id' => $studentId,
            ':est_id' => $establishmentId
        ]);

        // Paiements effectués pour cet élève
        $paymentsSql = "SELECT p.*, r.receipt_number, eu.full_name as received_by_name
                        FROM payments p
                        LEFT JOIN receipts r ON r.payment_id = p.id
                        LEFT JOIN establishment_users eu ON eu.id = p.received_by
                        WHERE p.student_id = :student_id AND p.establishment_id = :est_id
                        ORDER BY p.paid_at DESC";
        $student['payments'] = Database::fetchAll($paymentsSql, [
            ':student_id' => $studentId,
            ':est_id' => $establishmentId
        ]);

        return $student;
    }

    /**
     * Génère un matricule unique pour un nouvel élève de l'établissement
     */
    public function generateStudentNumber(int $establishmentId): string
    {
        $year = date('Y');
        $count = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM students WHERE establishment_id = :est_id",
            [':est_id' => $establishmentId]
        );

        $nextNumber = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        $candidate = "ELV-{$year}-{$nextNumber}";

        // Vérification de collision éventuelle
        $exists = Database::fetchValue(
            "SELECT id FROM students WHERE establishment_id = :est_id AND student_number = :num",
            [':est_id' => $establishmentId, ':num' => $candidate]
        );

        if ($exists) {
            $candidate .= '-' . mt_rand(10, 99);
        }

        return $candidate;
    }

    /**
     * Enregistre un nouvel élève
     */
    public function create(int $establishmentId, array $data): int
    {
        $studentNumber = !empty($data['student_number']) ? trim($data['student_number']) : $this->generateStudentNumber($establishmentId);

        $sql = "INSERT INTO students 
                (establishment_id, student_number, first_name, last_name, date_of_birth, gender, class_name, email, phone, address, status, enrolled_at, created_at)
                VALUES 
                (:est_id, :student_number, :first_name, :last_name, :dob, :gender, :class_name, :email, :phone, :address, 'active', :enrolled_at, CURRENT_TIMESTAMP)";

        Database::query($sql, [
            ':est_id' => $establishmentId,
            ':student_number' => $studentNumber,
            ':first_name' => trim($data['first_name']),
            ':last_name' => trim($data['last_name']),
            ':dob' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            ':gender' => $data['gender'] ?? 'unspecified',
            ':class_name' => !empty($data['class_name']) ? trim($data['class_name']) : null,
            ':email' => !empty($data['email']) ? strtolower(trim($data['email'])) : null,
            ':phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            ':address' => !empty($data['address']) ? trim($data['address']) : null,
            ':enrolled_at' => !empty($data['enrolled_at']) ? $data['enrolled_at'] : date('Y-m-d')
        ]);

        return (int) Database::lastInsertId();
    }

    /**
     * Met à jour les informations d'un élève
     */
    public function update(int $establishmentId, int $studentId, array $data): void
    {
        $sql = "UPDATE students 
                SET first_name = :first_name,
                    last_name = :last_name,
                    date_of_birth = :dob,
                    gender = :gender,
                    class_name = :class_name,
                    email = :email,
                    phone = :phone,
                    address = :address,
                    status = :status,
                    student_number = :student_number
                WHERE id = :id AND establishment_id = :est_id";

        Database::query($sql, [
            ':id' => $studentId,
            ':est_id' => $establishmentId,
            ':first_name' => trim($data['first_name']),
            ':last_name' => trim($data['last_name']),
            ':dob' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            ':gender' => $data['gender'] ?? 'unspecified',
            ':class_name' => !empty($data['class_name']) ? trim($data['class_name']) : null,
            ':email' => !empty($data['email']) ? strtolower(trim($data['email'])) : null,
            ':phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            ':address' => !empty($data['address']) ? trim($data['address']) : null,
            ':status' => $data['status'] ?? 'active',
            ':student_number' => trim($data['student_number'])
        ]);
    }

    /**
     * Associe un parent/tuteur à un élève en validant l'étanchéité multi-tenant
     */
    public function attachGuardian(int $establishmentId, int $studentId, int $guardianId, string $relationship = 'Parent', bool $isPrimary = false): void
    {
        // Validation que l'élève ET le tuteur appartiennent au même tenant
        $studentOk = Database::fetchValue("SELECT id FROM students WHERE id = :id AND establishment_id = :est_id", [':id' => $studentId, ':est_id' => $establishmentId]);
        $guardianOk = Database::fetchValue("SELECT id FROM guardians WHERE id = :id AND establishment_id = :est_id", [':id' => $guardianId, ':est_id' => $establishmentId]);

        if (!$studentOk || !$guardianOk) {
            throw new \RuntimeException('Violation de sécurité multi-tenant : tentative d\'association inter-établissements.');
        }

        $sql = "INSERT INTO student_guardians (student_id, guardian_id, relationship_label, is_primary)
                VALUES (:student_id, :guardian_id, :rel, :primary)
                ON DUPLICATE KEY UPDATE relationship_label = VALUES(relationship_label), is_primary = VALUES(is_primary)";

        Database::query($sql, [
            ':student_id' => $studentId,
            ':guardian_id' => $guardianId,
            ':rel' => $relationship,
            ':primary' => $isPrimary ? 1 : 0
        ]);
    }

    /**
     * Supprime / désactive un élève
     */
    public function setStatus(int $establishmentId, int $studentId, string $status): void
    {
        Database::query(
            "UPDATE students SET status = :status WHERE id = :id AND establishment_id = :est_id",
            [':status' => $status, ':id' => $studentId, ':est_id' => $establishmentId]
        );
    }
}
