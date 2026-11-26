<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Repository pour l'accès aux données des établissements scolaires
 */
class EstablishmentRepository
{
    /**
     * Récupère tous les établissements avec pagination et filtres
     */
    public function getPaginated(int $page = 1, int $perPage = 15, ?string $status = null, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["deleted_at IS NULL"];

        if ($status && $status !== 'all') {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        if ($search) {
            $where[] = "(name LIKE :search OR email LIKE :search OR phone LIKE :search OR city LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM establishments WHERE {$whereClause}";
        $total = (int) Database::fetchValue($countSql, $params);

        $sql = "SELECT e.*, 
                       (SELECT COUNT(*) FROM students s WHERE s.establishment_id = e.id AND s.status = 'active') as students_count,
                       (SELECT COUNT(*) FROM establishment_users eu WHERE eu.establishment_id = e.id AND eu.is_active = 1) as users_count,
                       t.ends_at as trial_ends_at,
                       t.status as trial_status,
                       sub.status as subscription_status,
                       p.name as plan_name
                FROM establishments e
                LEFT JOIN trials t ON t.establishment_id = e.id
                LEFT JOIN subscriptions sub ON sub.establishment_id = e.id AND sub.status = 'active'
                LEFT JOIN plans p ON p.id = sub.plan_id
                WHERE {$whereClause}
                ORDER BY e.created_at DESC
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
     * Récupère un établissement par son ID avec toutes ses métriques
     */
    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT e.*, 
                       s.receipt_prefix, s.invoice_prefix, s.sms_sender_name, s.sms_notifications_enabled,
                       t.started_at as trial_started_at, t.ends_at as trial_ends_at, t.status as trial_status,
                       sca.available_credits as sms_credits, sca.lifetime_consumed as sms_consumed,
                       sub.status as sub_status, sub.billing_cycle, sub.current_period_end,
                       p.name as plan_name, p.code as plan_code
                FROM establishments e
                LEFT JOIN establishment_settings s ON s.establishment_id = e.id
                LEFT JOIN trials t ON t.establishment_id = e.id
                LEFT JOIN sms_credit_accounts sca ON sca.establishment_id = e.id
                LEFT JOIN subscriptions sub ON sub.establishment_id = e.id AND sub.status = 'active'
                LEFT JOIN plans p ON p.id = sub.plan_id
                WHERE e.id = :id AND e.deleted_at IS NULL
                LIMIT 1";

        return Database::fetchOne($sql, [':id' => $id]);
    }

    /**
     * Métriques globales pour le tableau de bord Super Admin
     */
    public function getGlobalStats(): array
    {
        $stats = [];

        // Nombre total d'écoles
        $stats['total_establishments'] = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM establishments WHERE deleted_at IS NULL"
        );

        // Écoles actives
        $stats['active_establishments'] = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM establishments WHERE status = 'active' AND deleted_at IS NULL"
        );

        // Écoles en essai (trial)
        $stats['trial_establishments'] = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM establishments WHERE status = 'trial' AND deleted_at IS NULL"
        );

        // Écoles suspendues
        $stats['suspended_establishments'] = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM establishments WHERE status = 'suspended' AND deleted_at IS NULL"
        );

        // Nombre total d'élèves sur la plateforme
        $stats['total_students'] = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM students WHERE status = 'active'"
        );

        // Revenu Récurrent Mensuel (MRR) en FCFA
        $mrr = Database::fetchValue(
            "SELECT SUM(CASE 
                WHEN s.billing_cycle = 'monthly' THEN s.price_at_purchase 
                WHEN s.billing_cycle = 'annual' THEN s.price_at_purchase / 12 
                ELSE 0 END)
             FROM subscriptions s
             WHERE s.status = 'active'"
        );
        $stats['mrr'] = (float) ($mrr ?? 0);

        // Total facturé aux écoles par HOME SCHOOL (abonnements SaaS)
        $totalSaasRevenue = Database::fetchValue(
            "SELECT SUM(amount) FROM payment_transactions WHERE status = 'succeeded'"
        );
        $stats['total_saas_revenue'] = (float) ($totalSaasRevenue ?? 0);

        // Total des SMS consommés par toutes les écoles
        $stats['total_sms_consumed'] = (int) Database::fetchValue(
            "SELECT SUM(sms_count) FROM sms_messages WHERE status IN ('submitted', 'delivered')"
        );

        return $stats;
    }

    /**
     * Modifie le statut d'un établissement (ex: suspendre ou réactiver)
     */
    public function setStatus(int $id, string $status): void
    {
        Database::query(
            "UPDATE establishments SET status = :status WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
    }
}
