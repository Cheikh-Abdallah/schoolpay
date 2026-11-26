<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestionnaire de journalisation interne sécurisé
 * Écrit dans storage/logs/ avec rotation quotidienne
 */
class Logger
{
    private static ?string $logDir = null;

    private static function getLogDir(): string
    {
        if (self::$logDir === null) {
            self::$logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $logFile = self::getLogDir() . '/schoolpay-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        $formatted = sprintf("[%s] [%s] [%s] %s%s%s", $timestamp, strtoupper($level), $ip, $message, $contextString, PHP_EOL);

        file_put_contents($logFile, $formatted, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::log('SECURITY', $message, $context);
    }
}
