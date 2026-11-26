<?php

declare(strict_types=1);

namespace App\Controllers\Establishment;

use App\Core\Controller;
use App\Core\Request;
use App\Core\TenantContext;
use App\Core\Auth;
use App\Core\Database;

class DashboardController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();

        // ── KPI: Élèves ────────────────────────────────────────────────────
        $studentStats = $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active'   THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive
             FROM students
             WHERE establishment_id = :est_id AND deleted_at IS NULL",
            ['est_id' => $establishmentId]
        );

        // ── KPI: Facturation (année courante) ──────────────────────────────
        $financeStats = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(i.total_amount), 0)  AS total_invoiced,
                COALESCE(SUM(i.amount_paid), 0)   AS total_paid,
                COALESCE(SUM(i.balance_due), 0)   AS total_due,
                COUNT(*)                           AS invoice_count,
                COUNT(CASE WHEN i.balance_due > 0 AND i.due_date < CURDATE() THEN 1 END) AS overdue_count
             FROM invoices i
             JOIN academic_years ay ON ay.id = i.academic_year_id
             WHERE i.establishment_id = :est_id
               AND ay.is_current = 1",
            ['est_id' => $establishmentId]
        );

        // ── KPI: Paiements du mois ─────────────────────────────────────────
        $monthlyPayments = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(p.amount), 0) AS amount,
                COUNT(*) AS count
             FROM payments p
             WHERE p.establishment_id = :est_id
               AND p.status = 'confirmed'
               AND MONTH(p.paid_at) = MONTH(CURDATE())
               AND YEAR(p.paid_at)  = YEAR(CURDATE())",
            ['est_id' => $establishmentId]
        );

        // ── KPI: SMS ───────────────────────────────────────────────────────
        $smsBalance = $this->db->fetchOne(
            "SELECT credits_available, credits_total
             FROM sms_credit_accounts
             WHERE establishment_id = :est_id",
            ['est_id' => $establishmentId]
        );

        // ── Essai gratuit ──────────────────────────────────────────────────
        $trial = $this->db->fetchOne(
            "SELECT t.*, TIMESTAMPDIFF(HOUR, NOW(), t.trial_ends_at) AS hours_left
             FROM trials t
             WHERE t.establishment_id = :est_id AND t.status = 'active'
             LIMIT 1",
            ['est_id' => $establishmentId]
        );

        // ── Abonnement actif ───────────────────────────────────────────────
        $subscription = $this->db->fetchOne(
            "SELECT s.*, p.name AS plan_name, p.max_students, p.sms_per_month
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.establishment_id = :est_id AND s.status = 'active'
             LIMIT 1",
            ['est_id' => $establishmentId]
        );

        // ── Paiements récents ──────────────────────────────────────────────
        $recentPayments = $this->db->fetchAll(
            "SELECT p.id, p.amount, p.payment_method, p.paid_at AS created_at,
                    p.payment_reference AS reference_number,
                    GROUP_CONCAT(DISTINCT i.invoice_number ORDER BY i.invoice_number SEPARATOR ', ') AS invoice_number,
                    CONCAT(s.first_name, ' ', s.last_name) AS student_name
             FROM payments p
             LEFT JOIN payment_allocations pa ON pa.payment_id = p.id
             LEFT JOIN invoices i ON i.id = pa.invoice_id
             LEFT JOIN students s ON s.id = p.student_id
             WHERE p.establishment_id = :est_id AND p.status = 'confirmed'
             GROUP BY p.id, p.amount, p.payment_method, p.paid_at, p.payment_reference, s.first_name, s.last_name
             ORDER BY p.paid_at DESC
             LIMIT 8",
            ['est_id' => $establishmentId]
        );

        // ── Impayés urgents (top 5) ────────────────────────────────────────
        $urgentUnpaid = $this->db->fetchAll(
            "SELECT i.id, i.invoice_number, i.balance_due, i.due_date,
                    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                    g.full_name AS guardian_name,
                    g.phone AS guardian_phone,
                    DATEDIFF(CURDATE(), i.due_date) AS days_overdue
             FROM invoices i
             LEFT JOIN students s ON s.id = i.student_id
             LEFT JOIN student_guardians sg ON sg.student_id = i.student_id AND sg.is_primary = 1
             LEFT JOIN guardians g ON g.id = sg.guardian_id AND g.establishment_id = i.establishment_id
             WHERE i.establishment_id = :est_id
               AND i.balance_due > 0
               AND i.due_date < CURDATE()
             ORDER BY days_overdue DESC
             LIMIT 5",
            ['est_id' => $establishmentId]
        );

        // ── Établissement info ─────────────────────────────────────────────
        $establishment = $this->db->fetchOne(
            "SELECT name, logo_path FROM establishments WHERE id = :id",
            ['id' => $establishmentId]
        );

        $this->render('establishment.dashboard', [
            'title'          => 'Tableau de bord',
            'establishment'  => $establishment,
            'studentStats'   => $studentStats,
            'financeStats'   => $financeStats,
            'monthlyPayments'=> $monthlyPayments,
            'smsBalance'     => $smsBalance,
            'trial'          => $trial,
            'subscription'   => $subscription,
            'recentPayments' => $recentPayments,
            'urgentUnpaid'   => $urgentUnpaid,
        ], 'main');
    }
}
