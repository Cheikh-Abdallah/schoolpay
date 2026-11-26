<?php

declare(strict_types=1);

$title = 'Abonnement Requis';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès suspendu - SchoolPay</title>
    <link rel="stylesheet" href="<?= asset('css/schoolpay.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f4f6fa; }
        .notice-card { max-width: 580px; background: white; padding: 48px; border-radius: 16px; text-align: center; box-shadow: var(--shadow-lg); }
        .notice-icon { font-size: 3.5rem; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="notice-card">
    <div class="notice-icon">⏳</div>
    <h1 style="font-size: 1.5rem; margin-bottom: 12px; color: var(--primary);">Période d'essai ou Abonnement Terminé</h1>
    <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.95rem; line-height: 1.6;">
        Votre période d'essai gratuit de 72 heures ou votre forfait mensuel a expiré. 
        <strong>Toutes vos données (élèves, factures, reçus) sont rigoureusement conservées et protégées.</strong>
    </p>
    <p style="color: var(--text-muted); margin-bottom: 30px; font-size: 0.9rem;">
        Pour réactiver immédiatement la création de nouvelles factures, encaissements et SMS, veuillez choisir votre forfait SchoolPay.
    </p>
    <div style="display:flex; gap:12px; justify-content:center;">
        <a href="<?= url('/subscription/upgrade') ?>" class="btn btn-accent" style="font-weight:700;">
            ⭐ Choisir un Forfait (Dès 3 000 FCFA)
        </a>
        <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">
            Consulter mes données
        </a>
    </div>
</div>
</body>
</html>
