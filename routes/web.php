<?php

declare(strict_types=1);

/**
 * Définition des routes Web de SchoolPay
 * 
 * @var \App\Core\Router $router
 */

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\SuperAdminMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\SubscriptionMiddleware;

// --- ROUTE RACINE ---
$router->get('/', function (Request $request) {
    if (Auth::check()) {
        if (Auth::isSuperAdmin()) {
            Response::redirect('/superadmin/dashboard');
        }
        Response::redirect('/dashboard');
    }
    Response::redirect('/login');
});

// --- ROUTES AUTHENTIFICATION ---
$router->get('/login', [\App\Controllers\Auth\AuthController::class, 'showLoginForm']);
$router->post('/login', [\App\Controllers\Auth\AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->any('/logout', [\App\Controllers\Auth\AuthController::class, 'logout']);

// SuperAdmin Login distinct
$router->get('/superadmin/login', [\App\Controllers\Auth\AuthController::class, 'showSuperAdminLoginForm']);
$router->post('/superadmin/login', [\App\Controllers\Auth\AuthController::class, 'superAdminLogin'], [CsrfMiddleware::class]);

// Acceptation d'invitation pilote (création école + admin)
$router->get('/invitation/accept', [\App\Controllers\Auth\OnboardingController::class, 'showAcceptForm']);
$router->post('/invitation/accept', [\App\Controllers\Auth\OnboardingController::class, 'acceptInvitation'], [CsrfMiddleware::class]);

// Réinitialisation mot de passe
$router->get('/forgot-password', [\App\Controllers\Auth\PasswordResetController::class, 'showRequestForm']);
$router->post('/forgot-password', [\App\Controllers\Auth\PasswordResetController::class, 'sendResetLink'], [CsrfMiddleware::class]);

// Retour du checkout SasPay : le statut est toujours vérifié côté serveur.
$router->get('/payment/saspay/return', [\App\Controllers\Establishment\SasPayCheckoutController::class, 'returnFromCheckout']);

// --- ROUTES SUPER ADMIN (HOME SCHOOL) ---
$router->group(['prefix' => '/superadmin', 'middleware' => [AuthMiddleware::class, SuperAdminMiddleware::class]], function (\App\Core\Router $router) {
    $router->get('/dashboard', [\App\Controllers\SuperAdmin\DashboardController::class, 'index']);
    
    // Établissements
    $router->get('/establishments', [\App\Controllers\SuperAdmin\EstablishmentController::class, 'index']);
    $router->get('/establishments/{id}', [\App\Controllers\SuperAdmin\EstablishmentController::class, 'show']);
    $router->post('/establishments/{id}/toggle-status', [\App\Controllers\SuperAdmin\EstablishmentController::class, 'toggleStatus'], [CsrfMiddleware::class]);
    
    // Invitations pilotes
    $router->get('/invitations', [\App\Controllers\SuperAdmin\InvitationController::class, 'index']);
    $router->post('/invitations/create', [\App\Controllers\SuperAdmin\InvitationController::class, 'create'], [CsrfMiddleware::class]);
    $router->post('/invitations/{id}/revoke', [\App\Controllers\SuperAdmin\InvitationController::class, 'revoke'], [CsrfMiddleware::class]);
    
    // Forfaits & Tarifs
    $router->get('/plans', [\App\Controllers\SuperAdmin\PlanController::class, 'index']);
    $router->post('/plans/{id}/update', [\App\Controllers\SuperAdmin\PlanController::class, 'update'], [CsrfMiddleware::class]);
    
    // Suivi Abonnements SaaS
    $router->get('/subscriptions', [\App\Controllers\SuperAdmin\SubscriptionController::class, 'index']);
    
    // SendText & Quotas centralisés
    $router->get('/sms', [\App\Controllers\SuperAdmin\SmsCentralController::class, 'index']);
    $router->post('/sms/settings', [\App\Controllers\SuperAdmin\SmsCentralController::class, 'saveSettings'], [CsrfMiddleware::class]);
    $router->post('/sms/grant-credits', [\App\Controllers\SuperAdmin\SmsCentralController::class, 'grantCredits'], [CsrfMiddleware::class]);
    $router->get('/sms/sync-balance', [\App\Controllers\SuperAdmin\SmsCentralController::class, 'syncBalance']);

    // Journaux d'audit
    $router->get('/audit', [\App\Controllers\SuperAdmin\AuditController::class, 'index']);
});

// --- ROUTES ESPACE ÉTABLISSEMENT ---
$router->group(['middleware' => [AuthMiddleware::class, TenantMiddleware::class, SubscriptionMiddleware::class]], function (\App\Core\Router $router) {
    // Tableau de bord principal
    $router->get('/dashboard', [\App\Controllers\Establishment\DashboardController::class, 'index']);

    // Gestion des élèves
    $router->get('/students', [\App\Controllers\Establishment\StudentController::class, 'index']);
    $router->get('/students/create', [\App\Controllers\Establishment\StudentController::class, 'create']);
    $router->post('/students/create', [\App\Controllers\Establishment\StudentController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/students/{id}', [\App\Controllers\Establishment\StudentController::class, 'show']);
    $router->get('/students/{id}/edit', [\App\Controllers\Establishment\StudentController::class, 'edit']);
    $router->post('/students/{id}/edit', [\App\Controllers\Establishment\StudentController::class, 'update'], [CsrfMiddleware::class]);
    $router->post('/students/{id}/status', [\App\Controllers\Establishment\StudentController::class, 'setStatus'], [CsrfMiddleware::class]);
    $router->post('/students/{id}/attach-guardian', [\App\Controllers\Establishment\StudentController::class, 'attachGuardian'], [CsrfMiddleware::class]);
    $router->post('/students/{id}/delete', [\App\Controllers\Establishment\StudentController::class, 'destroy'], [CsrfMiddleware::class]);

    // Parents & Tuteurs
    $router->get('/guardians', [\App\Controllers\Establishment\GuardianController::class, 'index']);
    $router->get('/guardians/create', [\App\Controllers\Establishment\GuardianController::class, 'create']);
    $router->post('/guardians/create', [\App\Controllers\Establishment\GuardianController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/guardians/search', [\App\Controllers\Establishment\GuardianController::class, 'search']);
    $router->get('/guardians/{id}', [\App\Controllers\Establishment\GuardianController::class, 'show']);
    $router->get('/guardians/{id}/edit', [\App\Controllers\Establishment\GuardianController::class, 'edit']);
    $router->post('/guardians/{id}/edit', [\App\Controllers\Establishment\GuardianController::class, 'update'], [CsrfMiddleware::class]);
    $router->post('/guardians/{id}/delete', [\App\Controllers\Establishment\GuardianController::class, 'destroy'], [CsrfMiddleware::class]);

    // Nomenclature des frais (Fee Types)
    $router->get('/fee-types', [\App\Controllers\Establishment\FeeTypeController::class, 'index']);
    $router->post('/fee-types/create', [\App\Controllers\Establishment\FeeTypeController::class, 'store'], [CsrfMiddleware::class]);
    $router->post('/fee-types/{id}/update', [\App\Controllers\Establishment\FeeTypeController::class, 'update'], [CsrfMiddleware::class]);
    $router->post('/fee-types/{id}/toggle', [\App\Controllers\Establishment\FeeTypeController::class, 'toggle'], [CsrfMiddleware::class]);
    $router->post('/fee-types/{id}/delete', [\App\Controllers\Establishment\FeeTypeController::class, 'destroy'], [CsrfMiddleware::class]);
    $router->post('/fee-types/reorder', [\App\Controllers\Establishment\FeeTypeController::class, 'reorder'], [CsrfMiddleware::class]);

    // Facturation scolaire
    $router->get('/invoices', [\App\Controllers\Establishment\InvoiceController::class, 'index']);
    $router->get('/invoices/create', [\App\Controllers\Establishment\InvoiceController::class, 'create']);
    $router->post('/invoices/create', [\App\Controllers\Establishment\InvoiceController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/invoices/{id}', [\App\Controllers\Establishment\InvoiceController::class, 'show']);
    $router->get('/invoices/{id}/print', [\App\Controllers\Establishment\InvoiceController::class, 'print']);
    $router->get('/invoices/{id}/pdf', [\App\Controllers\Establishment\InvoiceController::class, 'downloadPdf']);

    // Paiements & Encaissements
    $router->get('/payments', [\App\Controllers\Establishment\PaymentController::class, 'index']);
    $router->get('/payments/create', [\App\Controllers\Establishment\PaymentController::class, 'create']);
    $router->post('/payments/create', [\App\Controllers\Establishment\PaymentController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/payments/{id}', [\App\Controllers\Establishment\PaymentController::class, 'show']);
    $router->get('/invoices/{id}/saspay', [\App\Controllers\Establishment\SasPayCheckoutController::class, 'form']);
    $router->post('/invoices/{id}/saspay', [\App\Controllers\Establishment\SasPayCheckoutController::class, 'createCheckout'], [CsrfMiddleware::class]);

    // Reçus de paiement
    $router->get('/receipts', [\App\Controllers\Establishment\ReceiptController::class, 'index']);
    $router->get('/receipts/{id}', [\App\Controllers\Establishment\ReceiptController::class, 'show']);
    $router->get('/receipts/{id}/print', [\App\Controllers\Establishment\ReceiptController::class, 'print']);
    $router->get('/receipts/{id}/pdf', [\App\Controllers\Establishment\ReceiptController::class, 'downloadPdf']);

    // Impayés & Relances
    $router->get('/unpaid', [\App\Controllers\Establishment\UnpaidController::class, 'index']);
    $router->post('/unpaid/remind/{invoice_id}', [\App\Controllers\Establishment\UnpaidController::class, 'sendReminder'], [CsrfMiddleware::class]);

    // SMS & Campagnes SendText
    $router->get('/sms', [\App\Controllers\Establishment\SmsController::class, 'index']);
    $router->get('/sms/create', [\App\Controllers\Establishment\SmsController::class, 'create']);
    $router->post('/sms/send-single', [\App\Controllers\Establishment\SmsController::class, 'sendSingle'], [CsrfMiddleware::class]);
    $router->post('/sms/send-campaign', [\App\Controllers\Establishment\SmsController::class, 'sendCampaign'], [CsrfMiddleware::class]);

    // Rapports financiers
    $router->get('/reports', [\App\Controllers\Establishment\ReportController::class, 'index']);
    $router->get('/reports/daily', [\App\Controllers\Establishment\ReportController::class, 'daily']);
    $router->get('/reports/monthly', [\App\Controllers\Establishment\ReportController::class, 'monthly']);
    $router->get('/reports/export', [\App\Controllers\Establishment\ReportController::class, 'exportCsv']);

    // Équipe & Utilisateurs de l'école
    $router->get('/users', [\App\Controllers\Establishment\UserController::class, 'index']);
    $router->post('/users/create', [\App\Controllers\Establishment\UserController::class, 'store'], [CsrfMiddleware::class]);
    $router->post('/users/{id}/toggle', [\App\Controllers\Establishment\UserController::class, 'toggleActive'], [CsrfMiddleware::class]);

    // Paramètres de l'école
    $router->get('/settings', [\App\Controllers\Establishment\SettingsController::class, 'index']);
    $router->post('/settings/profile', [\App\Controllers\Establishment\SettingsController::class, 'updateProfile'], [CsrfMiddleware::class]);
    $router->post('/settings/billing', [\App\Controllers\Establishment\SettingsController::class, 'updateBilling'], [CsrfMiddleware::class]);
});

// La vérification d’un paiement déjà initié reste disponible même après expiration de l’abonnement.
$router->get('/payments/saspay/{reference}', [\App\Controllers\Establishment\SasPayCheckoutController::class, 'checkStatus'], [AuthMiddleware::class, TenantMiddleware::class]);

// --- ROUTES ABONNEMENT SAAS (Accessibles même si l'essai ou abonnement a expiré) ---
$router->group(['prefix' => '/subscription', 'middleware' => [AuthMiddleware::class, TenantMiddleware::class]], function (\App\Core\Router $router) {
    $router->get('/status', [\App\Controllers\Subscription\BillingController::class, 'index']);
    $router->get('/upgrade', [\App\Controllers\Subscription\BillingController::class, 'upgrade']);
    $router->post('/checkout', [\App\Controllers\Subscription\CheckoutController::class, 'initiateCheckout'], [CsrfMiddleware::class]);
    $router->get('/expired', [\App\Controllers\Subscription\BillingController::class, 'expiredNotice']);
    $router->get('/suspended', [\App\Controllers\Subscription\BillingController::class, 'suspendedNotice']);
});
