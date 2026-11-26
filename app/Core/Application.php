<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Amorçage central et cycle de vie de l'application SchoolPay
 */
class Application
{
    private Router $router;
    private Request $request;

    public function __construct()
    {
        $this->initializeErrorHandling();
        Autoloader::register();
        Session::start();

        $this->router = new Router();
        $this->request = new Request();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Configuration globale de la capture d'erreurs et exceptions
     */
    private function initializeErrorHandling(): void
    {
        error_reporting(E_ALL);

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            Logger::error("Erreur PHP [{$errno}] : {$errstr} dans {$errfile} à la ligne {$errline}");
            if (defined('APP_DEBUG') && APP_DEBUG) {
                // En mode debug, on laisse l'erreur s'afficher
                return false;
            }
            return true;
        });

        set_exception_handler(function (Throwable $e) {
            Logger::error("Exception non interceptée : " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo '<div style="background:#fee;border:2px solid #b42318;padding:20px;font-family:sans-serif;margin:20px;border-radius:8px;">';
                echo '<h2 style="color:#b42318;margin-top:0;">Une erreur est survenue (Mode Debug)</h2>';
                echo '<p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p><strong>Fichier :</strong> ' . htmlspecialchars($e->getFile()) . ' (ligne ' . $e->getLine() . ')</p>';
                echo '<pre style="background:#fff;padding:15px;overflow:auto;border-radius:4px;border:1px solid #ddd;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</div>';
                exit;
            }

            // En production : affichage sécurisé de la vue 500
            $errorView = dirname(__DIR__, 2) . '/resources/views/errors/500.php';
            if (is_file($errorView)) {
                http_response_code(500);
                require $errorView;
                exit;
            }

            Response::html('<h1>500 - Erreur Serveur</h1><p>Une erreur inattendue est survenue. Veuillez contacter le support.</p>', 500);
        });
    }

    /**
     * Lance le traitement de la requête
     */
    public function run(): void
    {
        // Chargement des définitions de routes
        $router = $this->router;
        require dirname(__DIR__, 2) . '/routes/web.php';
        require dirname(__DIR__, 2) . '/routes/api.php';

        // Distribution de la requête
        $this->router->dispatch($this->request);
    }
}
