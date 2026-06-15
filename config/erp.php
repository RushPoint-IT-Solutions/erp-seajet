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
        [
            'label' => 'Reports',
            'icon' => 'bi-graph-up-arrow',
            'items' => [
                ['label' => 'Report Library', 'icon' => 'bi-folder2-open', 'route' => 'erp.reports.index', 'patterns' => ['erp/reports']],
                ['label' => 'Trial Balance', 'icon' => 'bi-columns-gap', 'route' => 'erp.reports.trial-balance', 'patterns' => ['erp/reports/trial-balance']],
                ['label' => 'Workbook Reports', 'icon' => 'bi-folder-check', 'route' => 'erp.reports.workbook', 'patterns' => ['erp/reports/workbook*']],
                ['label' => 'Financial Statements', 'icon' => 'bi-file-earmark-spreadsheet', 'route' => 'erp.reports.financial-statements', 'patterns' => ['erp/reports/financial-statements']],
                ['label' => 'Client SOA', 'icon' => 'bi-file-earmark-text', 'route' => 'erp.reports.client-soa', 'patterns' => ['erp/reports/client-statement-of-account']],
                ['label' => 'AR Aging', 'icon' => 'bi-bar-chart-steps', 'route' => 'erp.customer-billing.aging', 'patterns' => ['erp/customer-billing/aging']],
            ],
        ],
        ['label' => 'User Access Control', 'icon' => 'bi-person-lock', 'route' => 'erp.users.index', 'patterns' => ['erp/users*', 'erp/action/approval-matrix']],
        ['label' => 'Finance Settings', 'icon' => 'bi-gear-fill', 'route' => 'erp.settings.index', 'patterns' => ['erp/settings']],
    ],
];
