<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Moteur de routage HTTP avec support des paramètres dynamiques et middlewares
 */
class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function any(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('*', $path, $handler, $middlewares);
    }

    /**
     * Regroupe des routes avec un préfixe commun et des middlewares communs
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): self
    {
        $prefix = '';
        $groupMiddlewares = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $groupMw = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddlewares = array_merge($groupMiddlewares, $groupMw);
            }
        }

        $fullPath = rtrim($prefix, '/') . '/' . ltrim($path, '/');
        $fullPath = $fullPath === '' ? '/' : $fullPath;

        $allMiddlewares = array_merge($groupMiddlewares, $middlewares);

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => $allMiddlewares,
            'regex' => $this->compileRegex($fullPath)
        ];

        return $this;
    }

    private function compileRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Résout la requête HTTP courante et exécute le handler
     */
    public function dispatch(Request $request): void
    {
        $requestMethod = $request->method();
        $requestPath = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $requestMethod) {
                continue;
            }

            if (preg_match($route['regex'], $requestPath, $matches)) {
                // Extraction des paramètres dynamiques de l'URL
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Exécution des middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $mwInstance = is_string($middleware) ? new $middleware() : $middleware;
                    if (method_exists($mwInstance, 'handle')) {
                        $mwInstance->handle($request);
                    }
                }

                // Exécution du handler
                $handler = $route['handler'];

                if (is_callable($handler)) {
                    $handler($request, ...array_values($params));
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $method] = $handler;
                    if (!class_exists($controllerClass)) {
                        throw new \RuntimeException("Contrôleur introuvable : {$controllerClass}");
                    }

                    $controller = new $controllerClass();
                    if (!method_exists($controller, $method)) {
                        throw new \RuntimeException("Méthode {$method} introuvable dans {$controllerClass}");
                    }

                    $controller->$method($request, ...array_values($params));
                    return;
                }
            }
        }

        // Aucune route trouvée -> 404
        $this->handleNotFound($request);
    }

    private function handleNotFound(Request $request): void
    {
        if ($request->isAjax()) {
            Response::json(['error' => 'Endpoint introuvable.'], 404);
        }

        $errorView = dirname(__DIR__, 2) . '/resources/views/errors/404.php';
        if (is_file($errorView)) {
            http_response_code(404);
            require $errorView;
            exit;
        }

        Response::html('<h1>404 - Page non trouvée</h1><p>La page demandée n\'existe pas.</p>', 404);
    }
}
