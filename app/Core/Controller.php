<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\FlashHelper;

/**
 * Contrôleur de base de l'application SchoolPay
 */
abstract class Controller
{
    /**
     * Rend une vue encapsulée dans un layout parent
     */
    protected function render(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        $baseViewsDir = dirname(__DIR__, 2) . '/resources/views/';
        $viewFile = $baseViewsDir . str_replace('.', '/', $viewPath) . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("Fichier de vue introuvable : {$viewFile}");
        }

        // Extraction des variables pour la vue
        extract($data);

        // Capture du contenu de la vue
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Si aucun layout demandé, affichage direct
        if ($layout === '') {
            echo $content;
            exit;
        }

        $layoutFile = $baseViewsDir . 'layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Fichier de layout introuvable : {$layoutFile}");
        }

        // Rendu du layout contenant la variable $content
        require $layoutFile;
        exit;
    }

    /**
     * Émet une réponse JSON
     */
    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    /**
     * Redirige vers une URL avec optionnellement un message flash
     */
    protected function redirect(string $url, ?string $flashSuccess = null, ?string $flashError = null): void
    {
        if ($flashSuccess) {
            FlashHelper::success($flashSuccess);
        }
        if ($flashError) {
            FlashHelper::error($flashError);
        }

        Response::redirect($url);
    }

    /**
     * Valide les données selon les règles indiquées
     */
    protected function validate(Request $request, array $rules): array
    {
        $data = $request->all();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            if ($request->isAjax()) {
                Response::json([
                    'error' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            Session::set('_old_input', $data);
            Session::set('_validation_errors', $validator->errors());

            $firstError = null;
            foreach ($validator->errors() as $fieldErrors) {
                $firstError = $fieldErrors[0] ?? null;
                if ($firstError) break;
            }

            FlashHelper::error($firstError ?? 'Veuillez corriger les erreurs dans le formulaire.');
            Response::redirect($request->path());
        }

        Session::remove('_old_input');
        Session::remove('_validation_errors');

        return $data;
    }
}
