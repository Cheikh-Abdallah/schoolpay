<?php

declare(strict_types=1);

/**
 * Fonctions d'aide globales de SchoolPay (Namespace racine)
 */

use App\Helpers\FormatHelper;
use App\Helpers\FlashHelper;

if (!function_exists('format_money')) {
    function format_money(float|int|string|null $amount): string
    {
        return FormatHelper::money($amount);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd/m/Y'): string
    {
        return FormatHelper::date($date, $format);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $dateTime): string
    {
        return FormatHelper::dateTime($dateTime);
    }
}

if (!function_exists('format_phone')) {
    function format_phone(?string $phone): string
    {
        return FormatHelper::phone($phone);
    }
}

if (!function_exists('status_badge')) {
    function status_badge(string $status): string
    {
        return FormatHelper::statusBadge($status);
    }
}

if (!function_exists('flash_success')) {
    function flash_success(string $message): void
    {
        FlashHelper::success($message);
    }
}

if (!function_exists('flash_error')) {
    function flash_error(string $message): void
    {
        FlashHelper::error($message);
    }
}

if (!function_exists('flash_warning')) {
    function flash_warning(string $message): void
    {
        FlashHelper::warning($message);
    }
}

if (!function_exists('flash_info')) {
    function flash_info(string $message): void
    {
        FlashHelper::info($message);
    }
}
