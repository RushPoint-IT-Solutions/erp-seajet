<?php

return [
    'name' => 'SeaJet ERP',
    'currency' => 'PHP',
    'palette' => [
        'primary' => '#fe790d',
        'secondary' => '#2c3e50',
        'background' => '#f4f6f9',
    ],
    'modules' => [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'erp.dashboard'],
        'accounting' => ['label' => 'Accounting', 'icon' => 'bi-journal-text', 'route' => 'erp.modules.show'],
        'receivables' => ['label' => 'Accounts Receivable', 'icon' => 'bi-receipt', 'route' => 'erp.modules.show'],
        'billing' => ['label' => 'Billing Invoice', 'icon' => 'bi-file-earmark-text', 'route' => 'erp.modules.show'],
        'collections' => ['label' => 'Collections', 'icon' => 'bi-cash-coin', 'route' => 'erp.modules.show'],
        'payables' => ['label' => 'Accounts Payable', 'icon' => 'bi-credit-card-2-front', 'route' => 'erp.modules.show'],
        'banking' => ['label' => 'Banking', 'icon' => 'bi-bank', 'route' => 'erp.modules.show'],
        'fx' => ['label' => 'Multi-Currency & FX', 'icon' => 'bi-currency-exchange', 'route' => 'erp.fx.index'],
        'reports' => ['label' => 'Financial Reports', 'icon' => 'bi-bar-chart-line', 'route' => 'erp.modules.show'],
        'tax' => ['label' => 'Tax Management', 'icon' => 'bi-percent', 'route' => 'erp.modules.show'],
        'users' => ['label' => 'Users & Access', 'icon' => 'bi-people', 'route' => 'erp.modules.show'],
    ],
    'sidebar' => [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-3x3-gap-fill', 'route' => 'erp.dashboard', 'patterns' => ['/']],
        ['label' => 'Clients', 'icon' => 'bi-people-fill', 'route' => 'erp.customers.index', 'patterns' => ['erp/customers*']],
        ['label' => 'Invoices', 'icon' => 'bi-file-earmark-pdf-fill', 'route' => 'erp.invoices.index', 'patterns' => ['erp/invoices*', 'erp/billings*', 'erp/invoice/preview']],
        ['label' => 'Payments', 'icon' => 'bi-credit-card-fill', 'route' => 'erp.payments.create', 'patterns' => ['erp/payments*']],
        ['label' => 'Vendors', 'icon' => 'bi-building-fill', 'route' => 'erp.suppliers.index', 'patterns' => ['erp/suppliers*']],
        ['label' => 'Accounts Payable (AP)', 'icon' => 'bi-file-earmark-check-fill', 'route' => 'erp.expenses.index', 'patterns' => ['erp/expenses', 'erp/payables*']],
        ['label' => 'Reports', 'icon' => 'bi-graph-up-arrow', 'route' => 'erp.reports.index', 'patterns' => ['erp/reports*', 'erp/statements*', 'erp/tax*', 'erp/accounting*']],
        ['label' => 'User Access Control', 'icon' => 'bi-person-lock', 'route' => 'erp.users.index', 'patterns' => ['erp/users*', 'erp/action/approval-matrix']],
        ['label' => 'Finance Settings', 'icon' => 'bi-gear-fill', 'route' => 'erp.settings.index', 'patterns' => ['erp/settings']],
    ],
];
