<?php

declare(strict_types=1);

/**
 * Layout Principal - Espace Établissement SchoolPay
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
    <title><?= e($title ?? 'Tableau de bord') ?> - <?= e(defined('APP_NAME') ? APP_NAME : 'SchoolPay') ?></title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= asset('css/schoolpay.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
</head>
<body>

<div class="app-container">
    <!-- Menu Latéral Établissement -->
    <?php component('sidebar-school'); ?>

    <!-- Conteneur Principal -->
    <div class="main-content">
        <!-- Barre Supérieure -->
        <?php component('topbar'); ?>

        <!-- Corps de la Page -->
        <main class="content-body">
            <!-- Messages Flash -->
            <?php component('toast'); ?>

            <!-- Contenu spécifique de la vue -->
            <?= $content ?>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
