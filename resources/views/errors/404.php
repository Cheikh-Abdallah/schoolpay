<?php

declare(strict_types=1);

$title = '404 - Page Non Trouvée';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page introuvable - SchoolPay</title>
    <link rel="stylesheet" href="<?= asset('css/schoolpay.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f4f6fa; }
        .error-card { max-width: 520px; background: white; padding: 48px; border-radius: 16px; text-align: center; box-shadow: var(--shadow-lg); }
        .error-code { font-size: 4.5rem; font-weight: 800; color: #060691; margin-bottom: 8px; line-height: 1; }
    </style>
</head>
<body>
<div class="error-card">
    <div class="error-code">404</div>
    <h1 style="font-size: 1.4rem; margin-bottom: 12px; color: var(--text-main);">Page Introuvable</h1>
    <p style="color: var(--text-muted); margin-bottom: 24px; font-size: 0.95rem;">
        La page ou le document que vous recherchez n'existe pas ou a été déplacé.
    </p>
    <a href="<?= url('/dashboard') ?>" class="btn btn-primary">Retour à l'accueil</a>
</div>
</body>
</html>
