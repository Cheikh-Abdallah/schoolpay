<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestionnaire de session sécurisé
 * Protection contre le vol de session, fixation et détournement
 */
class Session
{
    private static bool $started = false;

    /**
     * Démarre la session de manière sécurisée
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (headers_sent()) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            self::$started = true;
            return;
        }

        $lifetime = (int) (getenv('SESSION_LIFETIME') ?: 7200);
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        // Directives de sécurité du cookie de session
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_strict_mode', '1');

        session_start();
        self::$started = true;

        // Contrôle d'intégrité de la session (empreinte User-Agent)
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        if (!isset($_SESSION['_user_agent'])) {
            $_SESSION['_user_agent'] = hash('sha256', $userAgent);
        } elseif ($_SESSION['_user_agent'] !== hash('sha256', $userAgent)) {
            // Empreinte incohérente : suspicion de détournement
            Logger::security('Tentative potentielle de vol de session détectée');
            self::destroy();
            session_start();
            $_SESSION['_user_agent'] = hash('sha256', $userAgent);
        }
    }

    /**
     * Régénère l'ID de session (à appeler obligatoirement après la connexion)
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            self::$started = false;
        }
    }
}
