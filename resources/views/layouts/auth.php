<?php

declare(strict_types=1);

/**
 * Layout Authentification & Onboarding - SchoolPay
 * 
 * @var string $content
 * @var string|null $title
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#060691">
    <title><?= e($title ?? 'Connexion') ?> - SchoolPay</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= asset('css/schoolpay.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #060691 0%, #030349 100%);
            padding: 24px 16px;
        }

        .auth-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .auth-slogan {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 6px;
            letter-spacing: 0.02em;
        }

        .auth-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 16px;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <a href="<?= url('/') ?>" class="auth-logo">
            <span>SchoolPay</span>
            <span class="brand-dot" style="width:12px;height:12px;"></span>
        </a>
        <div class="auth-slogan">HOME SCHOOL · « Votre tremplin vers l'excellence »</div>
    </div>

    <!-- Messages Flash -->
    <?php component('toast'); ?>

    <!-- Contenu du Formulaire -->
    <?= $content ?>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
