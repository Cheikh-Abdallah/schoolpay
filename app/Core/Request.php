<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encapsulation sécurisée de la requête HTTP
 */
class Request
{
    private string $method;
    private string $uri;
    private array $query;
    private array $post;
    private array $files;
    private array $server;
    private array $headers;
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->headers = $this->extractHeaders();

        // Détection payload JSON pour requêtes AJAX/API
        $contentType = $this->header('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            if ($input) {
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    $this->jsonBody = $decoded;
                }
            }
        }
    }

    private function extractHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$headerName] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headerName = str_replace('_', '-', strtolower($key));
                $headers[$headerName] = (string) $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with', '')) === 'xmlhttprequest'
            || str_contains($this->header('accept', ''), 'application/json');
    }

    /**
     * Récupère le chemin d'URL sans query string et sans le préfixe de sous-dossier éventuel
     */
    public function path(): string
    {
        $url = parse_url($this->uri, PHP_URL_PATH) ?? '/';

        // Normalisation si l'application est dans un sous-dossier (ex: /schoolpay/public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);

        if ($baseDir !== '/' && $baseDir !== '\\' && str_starts_with($url, $baseDir)) {
            $url = substr($url, strlen($baseDir));
        }

        $url = '/' . trim($url, '/');
        return $url === '/' ? '/' : rtrim($url, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonBody !== null && array_key_exists($key, $this->jsonBody)) {
            return $this->jsonBody[$key];
        }

        if (array_key_exists($key, $this->post)) {
            return is_string($this->post[$key]) ? trim($this->post[$key]) : $this->post[$key];
        }

        if (array_key_exists($key, $this->query)) {
            return is_string($this->query[$key]) ? trim($this->query[$key]) : $this->query[$key];
        }

        return $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
    }

    public function all(): array
    {
        if ($this->jsonBody !== null) {
            return array_merge($this->query, $this->jsonBody);
        }
        return array_merge($this->query, $this->post);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);
        return $this->headers[$normalized] ?? $default;
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
