<?php

return [
    'modules' => [
        'Core Modules' => [
            'dashboard' => ['label' => 'Dashboard', 'actions' => ['view']],
            'clients' => ['label' => 'Clients', 'actions' => ['view', 'create', 'edit', 'delete']],
            'invoices' => ['label' => 'Accounts Receivable - Invoices', 'actions' => ['view', 'create', 'edit', 'delete', 'approve']],
            'payments' => ['label' => 'Accounts Receivable - Collections', 'actions' => ['view', 'create', 'edit', 'delete']],
            'ar_reports' => ['label' => 'Accounts Receivable - Reports', 'actions' => ['view']],
        ],
        'Finance & Operations' => [
            'vendors' => ['label' => 'Vendors', 'actions' => ['view', 'create', 'edit', 'delete']],
            'payables' => ['label' => 'Accounts Payable (AP)', 'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'release']],
            'accounting' => ['label' => 'General Accounting', 'actions' => ['view', 'create', 'edit', 'delete', 'approve']],
            'banking' => ['label' => 'Banking', 'actions' => ['view', 'create', 'edit', 'delete']],
            'fx' => ['label' => 'Multi-Currency & FX', 'actions' => ['view', 'create', 'edit', 'delete']],
            'tax' => ['label' => 'Tax Management', 'actions' => ['view', 'create', 'edit', 'delete']],
            'reports' => ['label' => 'Reports', 'actions' => ['view', 'create', 'edit', 'delete']],
        ],
        'Administration' => [
            'user_access_control' => ['label' => 'User Access Control', 'actions' => ['view', 'create', 'edit', 'delete']],
            'finance_settings' => ['label' => 'Finance Settings', 'actions' => ['view', 'create', 'edit', 'delete']],
            'system_updates' => ['label' => 'System Updates', 'actions' => ['view', 'edit']],
        ],
    ],

    'roles' => [
        'admin' => [
            'name' => 'Administrator',
            'description' => 'Full ERP access',
            'color' => '#f15b45',
            'locked' => true,
            'permissions' => ['*'],
        ],
        'accounting' => [
            'name' => 'Accounting Staff',
            'description' => 'AR, AP, general ledger, tax, banking, and reports',
            'color' => '#2f5597',
            'permissions' => [
                'dashboard.*', 'clients.*', 'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
                'payments.*', 'ar_reports.*', 'vendors.*', 'payables.view', 'payables.create', 'payables.edit', 'payables.delete',
                'accounting.view', 'accounting.create', 'accounting.edit', 'accounting.delete',
                'banking.*', 'fx.*', 'tax.*', 'reports.*', 'finance_settings.view',
            ],
        ],
        'finance' => [
            'name' => 'Finance Staff',
            'description' => 'Cash, collections, AP payment controls, FX, and reports',
            'color' => '#00a99d',
            'permissions' => [
                'dashboard.view', 'clients.view', 'invoices.view', 'payments.*', 'vendors.view',
                'payables.view', 'payables.edit', 'payables.delete', 'payables.approve', 'payables.release',
                'banking.*', 'fx.*', 'ar_reports.view', 'reports.view',
            ],
        ],
        'accounts_receivable' => [
            'name' => 'Accounts Receivable (AR)',
            'description' => 'Customer accounts, invoices, collections, and AR reports',
            'color' => '#6f42c1',
            'permissions' => ['dashboard.view', 'clients.*', 'invoices.*', 'payments.*', 'ar_reports.view'],
        ],
        'accounts_payable' => [
            'name' => 'Accounts Payable (AP)',
            'description' => 'Vendor accounts, bills, payments, and AP reports',
            'color' => '#d97706',
            'permissions' => [
                'dashboard.view', 'vendors.*', 'payables.view', 'payables.create',
                'payables.edit', 'payables.delete',
            ],
        ],
    ],

    'role_aliases' => [
        'administrator' => 'admin',
        'ap' => 'accounts_payable',
        'payables' => 'accounts_payable',
        'ar' => 'accounts_receivable',
        'receivables' => 'accounts_receivable',
    ],
];
