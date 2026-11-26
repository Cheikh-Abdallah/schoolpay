<?php

declare(strict_types=1);

use App\Core\CSRF;
use App\Core\Session;

/**
 * Fonctions globales pour les vues Blade/PHP
 */

if (!function_exists('e')) {
    /**
     * Échappement HTML sécurisé contre les failles XSS
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Génère une URL absolue de l'application
     */
    function url(string $path = ''): string
    {
        $base = rtrim(defined('APP_URL') ? APP_URL : 'http://localhost/schoolpay/public', '/');
        $path = ltrim($path, '/');
        return $path === '' ? $base : "{$base}/{$path}";
    }
}

if (!function_exists('asset')) {
    /**
     * Génère l'URL d'un asset public (CSS, JS, images, icônes)
     */
    function asset(string $path): string
    {
        $base = rtrim(defined('APP_URL') ? APP_URL : 'http://localhost/schoolpay/public', '/');
        return $base . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Récupère le jeton CSRF actif
     */
    function csrf_token(): string
    {
        return CSRF::getToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Génère le champ input masqué CSRF
     */
    function csrf_field(): string
    {
        return CSRF::field();
    }
}

if (!function_exists('component')) {
    /**
     * Rend un composant d'interface réutilisable
     */
    function component(string $name, array $data = []): void
    {
        $path = dirname(__DIR__, 2) . '/resources/views/components/' . $name . '.php';
        if (is_file($path)) {
            extract($data);
            include $path;
        }
    }
}

if (!function_exists('old')) {
    /**
     * Récupère l'ancienne valeur d'un champ après une erreur de formulaire
     */
    function old(string $key, mixed $default = ''): mixed
    {
        $oldInputs = Session::get('_old_input', []);
        return $oldInputs[$key] ?? $default;
    }
}
