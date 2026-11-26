<?php

declare(strict_types=1);

/**
 * Définition des routes API et Webhooks de SchoolPay
 * 
 * @var \App\Core\Router $router
 */

// Webhooks de paiement Mobile Money (Wave, Orange Money)
$router->post('/api/webhooks/payment', [\App\Controllers\Api\WebhookPaymentController::class, 'handlePaymentWebhook']);

// Webhooks SMS
$router->post('/api/webhooks/sms', [\App\Controllers\Api\SmsSyncController::class, 'handleSmsWebhook']);

// Recherche instantanée AJAX (protégée par session)
$router->get('/api/search/students', [\App\Controllers\Api\LiveSearchController::class, 'students']);
$router->get('/api/search/guardians', [\App\Controllers\Api\LiveSearchController::class, 'guardians']);
