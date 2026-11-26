<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protection contre les attaques CSRF (Cross-Site Request Forgery)
 */
class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Génère ou récupère le token CSRF actif
     */
    public static function getToken(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_KEY);

        if (!$token || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Valide le jeton fourni par rapport à la session
     */
    public static function validate(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $sessionToken = Session::get(self::SESSION_KEY);
        if (!$sessionToken || !is_string($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Génère le champ HTML masqué pour les formulaires
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    /**
     * Régénère un nouveau jeton
     */
    public static function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);
        return $token;
    }
}
