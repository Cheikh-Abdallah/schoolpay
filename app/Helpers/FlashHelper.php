<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Session;

/**
 * Gestionnaire des messages flash en session
 */
class FlashHelper
{
    private const FLASH_KEY = '_flash_messages';

    public static function add(string $type, string $message): void
    {
        $messages = Session::get(self::FLASH_KEY, []);
        $messages[] = [
            'type' => $type,
            'message' => $message
        ];
        Session::set(self::FLASH_KEY, $messages);
    }

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('danger', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    /**
     * Récupère tous les messages flash et les supprime de la session
     */
    public static function all(): array
    {
        $messages = Session::get(self::FLASH_KEY, []);
        Session::remove(self::FLASH_KEY);
        return $messages;
    }

    public static function has(): bool
    {
        $messages = Session::get(self::FLASH_KEY, []);
        return !empty($messages);
    }
}
