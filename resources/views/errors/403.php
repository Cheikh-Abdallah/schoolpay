<?php

declare(strict_types=1);

$title = '403 - Accès Refusé';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accès refusé - SchoolPay</title>
    <link rel="stylesheet" href="<?= asset('css/schoolpay.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f4f6fa; }
        .error-card { max-width: 520px; background: white; padding: 48px; border-radius: 16px; text-align: center; box-shadow: var(--shadow-lg); }
        .error-code { font-size: 4rem; font-weight: 800; color: #ef4444; margin-bottom: 8px; line-height: 1; }
    </style>
</head>
<body>
<div class="error-card">
    <div class="error-code">403</div>
    <h1 style="font-size: 1.4rem; margin-bottom: 12px; color: var(--text-main);">Accès Non Autorisé</h1>
    <p style="color: var(--text-muted); margin-bottom: 24px; font-size: 0.95rem;">
        Vous ne disposez pas des permissions requises pour accéder à cette ressource, ou vous tentez d'accéder à un établissement qui ne vous est pas attribué.
    </p>
    <a href="<?= url('/dashboard') ?>" class="btn btn-primary">Retour au tableau de bord</a>
</div>
</body>
</html>
