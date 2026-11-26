<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestionnaire des réponses HTTP sécurisées
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            // En-têtes de sécurité de niveau entreprise
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
        exit;
    }

    public static function json(array $data, int $status = 200): void
    {
        $response = new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
        $response->send();
    }

    public static function html(string $html, int $status = 200): void
    {
        $response = new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
        $response->send();
    }

    public static function redirect(string $url, int $status = 302): void
    {
        // Si l'URL est relative et ne commence pas par http, la préfixer avec APP_URL
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $base = rtrim(defined('APP_URL') ? APP_URL : '', '/');
            $url = $base . '/' . ltrim($url, '/');
        }

        $response = new self('', $status, ['Location' => $url]);
        $response->send();
    }

    public static function download(string $filePath, ?string $filename = null, string $contentType = 'application/octet-stream'): void
    {
        if (!is_file($filePath)) {
            http_response_code(404);
            die('Fichier introuvable.');
        }

        $filename = $filename ?? basename($filePath);
        $fileSize = filesize($filePath);

        if (!headers_sent()) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $fileSize);
        }

        readfile($filePath);
        exit;
    }
}
