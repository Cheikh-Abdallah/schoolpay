<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader PSR-4 natif haute performance
 * Permet l'exécution sans dépendance obligatoire à 'composer dump-autoload'
 */
class Autoloader
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(function (string $class) {
            $prefix = 'App\\';
            $baseDir = dirname(__DIR__) . '/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (is_file($file)) {
                require_once $file;
            }
        });

        // Chargement automatique des fichiers helpers
        $helpers = [
            dirname(__DIR__) . '/Helpers/ViewHelper.php',
            dirname(__DIR__) . '/Helpers/FormatHelper.php',
            dirname(__DIR__) . '/Helpers/FlashHelper.php',
            dirname(__DIR__) . '/Helpers/CryptoHelper.php',
            dirname(__DIR__) . '/Helpers/functions.php',
        ];

        foreach ($helpers as $helper) {
            if (is_file($helper)) {
                require_once $helper;
            }
        }

        self::$registered = true;
    }
}
