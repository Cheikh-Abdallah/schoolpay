<?php

declare(strict_types=1);

/**
 * Matrice des permissions de SchoolPay
 * Définition RBAC granulaire par rôle
 */
return [
    'establishment_roles' => [
        'owner' => [
            'description' => 'Fondateur / Propriétaire de l\'établissement',
            'permissions' => [
                'dashboard.view',
                'students.view', 'students.create', 'students.edit', 'students.delete',
                'guardians.view', 'guardians.create', 'guardians.edit', 'guardians.delete',
                'fee_types.view', 'fee_types.create', 'fee_types.edit', 'fee_types.delete',
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.cancel',
                'payments.view', 'payments.create', 'payments.cancel',
                'receipts.view', 'receipts.print', 'receipts.download',
                'unpaid.view', 'unpaid.remind',
                'sms.view', 'sms.send', 'sms.campaigns',
                'reports.view', 'reports.export',
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'settings.view', 'settings.edit',
                'subscription.view', 'subscription.upgrade', 'subscription.renew'
            ]
        ],
        'admin' => [
            'description' => 'Administrateur délégué de l\'école',
            'permissions' => [
                'dashboard.view',
                'students.view', 'students.create', 'students.edit', 'students.delete',
                'guardians.view', 'guardians.create', 'guardians.edit', 'guardians.delete',
                'fee_types.view', 'fee_types.create', 'fee_types.edit',
                'invoices.view', 'invoices.create', 'invoices.edit',
                'payments.view', 'payments.create',
                'receipts.view', 'receipts.print', 'receipts.download',
                'unpaid.view', 'unpaid.remind',
                'sms.view', 'sms.send', 'sms.campaigns',
                'reports.view', 'reports.export',
                'users.view',
                'settings.view',
                'subscription.view'
            ]
        ],
        'accountant' => [
            'description' => 'Comptable de l\'établissement',
            'permissions' => [
                'dashboard.view',
                'students.view',
                'guardians.view',
                'fee_types.view',
                'invoices.view', 'invoices.create', 'invoices.edit',
                'payments.view', 'payments.create',
                'receipts.view', 'receipts.print', 'receipts.download',
                'unpaid.view', 'unpaid.remind',
                'reports.view', 'reports.export',
                'subscription.view'
            ]
        ],
        'cashier' => [
            'description' => 'Caissier / Agent d\'encaissement',
            'permissions' => [
                'dashboard.view',
                'students.view',
                'invoices.view',
                'payments.view', 'payments.create',
                'receipts.view', 'receipts.print', 'receipts.download'
            ]
        ],
        'viewer' => [
            'description' => 'Consultation en lecture seule',
            'permissions' => [
                'dashboard.view',
                'students.view',
                'invoices.view',
                'payments.view',
                'receipts.view',
                'reports.view'
            ]
        ]
    ],

    'platform_roles' => [
        'super_admin' => [
            'description' => 'Super Administrateur HOME SCHOOL',
            'permissions' => [
                'platform.dashboard',
                'platform.establishments.view', 'platform.establishments.create', 'platform.establishments.edit', 'platform.establishments.toggle_status',
                'platform.invitations.create', 'platform.invitations.view', 'platform.invitations.revoke',
                'platform.plans.view', 'platform.plans.edit',
                'platform.subscriptions.view', 'platform.subscriptions.manage',
                'platform.sms.view', 'platform.sms.manage_credits', 'platform.sms.settings',
                'platform.users.manage',
                'platform.audit.view'
            ]
        ],
        'support' => [
            'description' => 'Agent Support Client HOME SCHOOL',
            'permissions' => [
                'platform.dashboard',
                'platform.establishments.view',
                'platform.invitations.view',
                'platform.plans.view',
                'platform.subscriptions.view',
                'platform.sms.view'
            ]
        ],
        'finance' => [
            'description' => 'Responsable Financier HOME SCHOOL',
            'permissions' => [
                'platform.dashboard',
                'platform.establishments.view',
                'platform.plans.view',
                'platform.subscriptions.view',
                'platform.subscriptions.manage',
                'platform.sms.view',
                'platform.sms.manage_credits'
            ]
        ]
    ]
];
