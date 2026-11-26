<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Fonctions de formatage monétaire (FCFA/XOF), dates et affichage
 */
class FormatHelper
{
    /**
     * Formate un montant en Franc CFA (XOF) sans décimales inutiles
     * Ex: 15000 -> "15 000 FCFA"
     */
    public static function money(float|int|string|null $amount): string
    {
        $val = (float) ($amount ?? 0);
        return number_format($val, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Formate une date au format sénégalais/francophone (JJ/MM/AAAA)
     */
    public static function date(?string $date, string $format = 'd/m/Y'): string
    {
        if (empty($date)) {
            return '-';
        }
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : '-';
    }

    /**
     * Formate une date et heure (JJ/MM/AAAA à HH:mm)
     */
    public static function dateTime(?string $dateTime): string
    {
        if (empty($dateTime)) {
            return '-';
        }
        $ts = strtotime($dateTime);
        return $ts ? date('d/m/Y à H:i', $ts) : '-';
    }

    /**
     * Formate un numéro de téléphone pour une lisibilité optimale
     * Ex: 771234567 -> "77 123 45 67"
     */
    public static function phone(?string $phone): string
    {
        if (empty($phone)) {
            return '-';
        }
        $clean = preg_replace('/[^0-9\+]/', '', $phone);
        if (str_starts_with($clean, '+221')) {
            $num = substr($clean, 4);
            if (strlen($num) === 9) {
                return '+221 ' . substr($num, 0, 2) . ' ' . substr($num, 2, 3) . ' ' . substr($num, 5, 2) . ' ' . substr($num, 7, 2);
            }
        } elseif (strlen($clean) === 9) {
            return substr($clean, 0, 2) . ' ' . substr($clean, 2, 3) . ' ' . substr($clean, 5, 2) . ' ' . substr($clean, 7, 2);
        }
        return $phone;
    }

    /**
     * Génère un badge HTML stylisé selon le statut
     */
    public static function statusBadge(string $status): string
    {
        $badges = [
            'active' => ['label' => 'Actif', 'class' => 'badge-success'],
            'trial' => ['label' => 'Essai gratuit', 'class' => 'badge-warning'],
            'suspended' => ['label' => 'Suspendu', 'class' => 'badge-danger'],
            'closed' => ['label' => 'Clôturé', 'class' => 'badge-secondary'],
            'pending' => ['label' => 'En attente', 'class' => 'badge-warning'],
            'paid' => ['label' => 'Payé', 'class' => 'badge-success'],
            'partially_paid' => ['label' => 'Partiellement payé', 'class' => 'badge-info'],
            'issued' => ['label' => 'Émise', 'class' => 'badge-primary'],
            'draft' => ['label' => 'Brouillon', 'class' => 'badge-secondary'],
            'overdue' => ['label' => 'En retard', 'class' => 'badge-danger'],
            'cancelled' => ['label' => 'Annulé', 'class' => 'badge-secondary'],
            'delivered' => ['label' => 'Livré', 'class' => 'badge-success'],
            'undelivered' => ['label' => 'Non livré', 'class' => 'badge-danger'],
            'queued' => ['label' => 'En file d\'attente', 'class' => 'badge-info'],
            'failed' => ['label' => 'Échec', 'class' => 'badge-danger'],
        ];

        $data = $badges[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-secondary'];
        return sprintf('<span class="badge %s">%s</span>', $data['class'], htmlspecialchars($data['label']));
    }
}
