<?php

declare(strict_types=1);

/**
 * SchoolPay - Plateforme SaaS de Gestion des Paiements Scolaires
 * Éditeur : HOME SCHOOL · « Votre tremplin vers l'excellence »
 * 
 * Point d'entrée unique HTTP
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// Si composer autoload existe, on le charge également pour les packages externes (ex: dompdf)
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

$app = new \App\Core\Application();
$app->run();

