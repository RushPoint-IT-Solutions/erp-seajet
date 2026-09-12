<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\Accounting\PeriodLockService;
use App\Services\Accounting\PostingService;
use App\Services\Audit\ActivityLogger;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Security\RbacService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    private $customers = [
        [
            'customer_id' => 'CUST-001',
            'name' => 'North Harbor Trading',
            'tin' => '000-345-111-000',
            'vat_classification' => '12%',
            'contact_person' => 'Angela Reyes',
            'email' => 'billing@northharbor.test',
            'phone' => '+63 917 100 2040',
            'address' => 'North Harbor Center, Manila',
            'terms' => '30 Days',
            'currency' => 'PHP',
            'class' => 'MANILA',
            'ar_account' => 'A/Trade-Billing Invoice',
            'credit_limit' => '500,000.00',
            'credit_status' => 'Within Limit',
        ],
        [
            'customer_id' => 'CUST-002',
            'name' => 'Blue Anchor Logistics',
            'tin' => '000-888-221-000',
            'vat_classification' => 'zero-rated',
            'contact_person' => 'Mark Lim',
            'email' => 'billing@blueanchor.test',
            'phone' => '+63 922 881 1130',
            'address' => 'Pier 7, Cebu City',
            'terms' => '60 Days',
            'currency' => 'USD',
            'class' => 'CEBU',
            'ar_account' => 'A/R Trade-SOA A',
            'credit_limit' => '250,000.00',
            'credit_status' => 'Watchlist',
        ],
        [
            'customer_id' => 'CUST-003',
            'name' => 'Island Prime Foods',
            'tin' => '000-744-902-000',
            'vat_classification' => 'exempt',
            'contact_person' => 'Carlo Dizon',
            'email' => 'ap@islandprime.test',
            'phone' => '+63 945 221 0199',
            'address' => 'San Fernando, Pampanga',
            'terms' => '90 Days',
            'currency' => 'PHP',
            'class' => 'PAMPANGA',
            'ar_account' => 'A/R Trade-Debit Note',
            'credit_limit' => '150,000.00',
            'credit_status' => 'Within Limit',
        ],
    ];

    private $suppliers = [
        [
            'supplier_id' => 'SUP-001',
            'name' => 'Metro Industrial Supply',
            'tin' => '000-901-881-000',
            'vat_classification' => '12%',
            'contact_person' => 'Dennis Co',
            'email' => 'ap@metroindustrial.test',
            'phone' => '+63 917 455 9081',
            'address' => 'Binondo, Manila',
            'terms' => '30 Days',
            'currency' => 'PHP',
            'class' => 'MANILA',
            'ap_account' => 'Accounts Payable Trade',
            'payment_method' => 'Cheque',
            'withholding_tax' => '5% EWT',
        ],
        [
            'supplier_id' => 'SUP-002',
            'name' => 'Pacific Fuel Services',
            'tin' => '000-510-294-000',
            'vat_classification' => '12%',
            'contact_person' => 'Liza Tan',
            'email' => 'billing@pacificfuel.test',
            'phone' => '+63 932 448 2098',
            'address' => 'Mandaue City, Cebu',
            'terms' => '60 Days',
            'currency' => 'PHP',
            'class' => 'CEBU',
            'ap_account' => 'Accounts Payable Trade',
            'payment_method' => 'Bank Transfer',
            'withholding_tax' => '2% EWT',
        ],
        [
            'supplier_id' => 'SUP-003',
            'name' => 'Harbor Office Center',
            'tin' => '000-772-130-000',
            'vat_classification' => 'exempt',
            'contact_person' => 'Ruth Garcia',
            'email' => 'sales@harboroffice.test',
            'phone' => '+63 921 770 3310',
            'address' => 'San Fernando, Pampanga',
            'terms' => '30 Days',
            'currency' => 'PHP',
            'class' => 'PAMPANGA',
            'ap_account' => 'Operating Expense Payable',
            'payment_method' => 'Online Transfer',
            'withholding_tax' => '0%',
        ],
    ];

    private $users = [
        ['user_id' => 'USR-001', 'name' => 'Maria Santos', 'email' => 'maria.santos@seajet.test', 'role' => 'Admin', 'department' => 'Accounting', 'branch' => 'Main', 'status' => 'Active', 'last_login' => '2026-05-24 09:15'],
        ['user_id' => 'USR-002', 'name' => 'John Cruz', 'email' => 'john.cruz@seajet.test', 'role' => 'Finance', 'department' => 'Finance', 'branch' => 'Main', 'status' => 'Active', 'last_login' => '2026-05-24 08:55'],
        ['user_id' => 'USR-003', 'name' => 'Ana Reyes', 'email' => 'ana.reyes@seajet.test', 'role' => 'Viewer', 'department' => 'Management', 'branch' => 'All', 'status' => 'Active', 'last_login' => '2026-05-23 17:20'],
        ['user_id' => 'USR-004', 'name' => 'Leo Garcia', 'email' => 'leo.garcia@seajet.test', 'role' => 'Payables', 'department' => 'Accounting', 'branch' => 'Cebu', 'status' => 'Pending Invite', 'last_login' => '-'],
    ];

    public function show($module)
    {
        if ($module === 'purchasing') {
            return redirect()->route('erp.expenses.index');
        }

        $modules = $this->modules();
        abort_unless(isset($modules[$module]), 404);

        return view('erp.modules.show', [
            'moduleKey' => $module,
            'module' => $modules[$module],
        ]);
    }

    public function invoicePreview()
    {
        return view('erp.print.invoice', [
            'positions' => $this->invoicePrintPositions(),
            'settings' => $this->invoicePrintSettings(),
            'fieldStyles' => $this->invoicePrintFieldStyles(),
            'customFields' => $this->invoicePrintCustomFields(),
            'hiddenFields' => $this->invoicePrintHiddenFields(),
            'print' => $this->billingPrintPreviewData(),
        ]);
    }

    public function notifications()
    {
        return view('erp.utility.notifications');
    }

    public function action($action)
    {
        $details = $this->actionDetails($action);

        return view('erp.utility.action', [
            'action' => ucwords(str_replace('-', ' ', $action)),
            'slug' => $action,
            'details' => $details,
        ]);
    }

    public function export($type)
    {
        return view('erp.utility.export', [
            'type' => strtoupper(str_replace('-', ' ', $type)),
        ]);
    }

    public function approvalMatrix()
    {
        $approvalLevels = [
            ['Billing Invoice', 'Create / revise invoice', 'Sales', 'Accounting', 'Finance Manager', '50,000.00+', 'Requires Shipment Job ID and customer credit check'],
            ['Customer Credit', 'Credit limit override', 'Accounting', 'Finance Manager', 'Admin', 'Any override', '90-day terms and overdue accounts require approval'],
            ['Vendor Bill', 'Record supplier bill', 'Accounting', 'Accounting', 'Finance Manager', '25,000.00+', 'Requires supplier, client tag, WHT, and Shipment Job ID'],
            ['AP Payment', 'Bank, cheque, online transfer', 'Accounting', 'Finance Manager', 'Admin', '10,000.00+', 'Approved payment required before check voucher release'],
            ['Bank Reconciliation', 'Finalize reconciliation', 'Finance', 'Finance Manager', 'Admin', 'All accounts', 'Outstanding checks/deposits must be reviewed'],
            ['FX Revaluation', 'Post realized/unrealized FX', 'Accounting', 'Finance Manager', 'Admin', 'USD entries', 'Posts to GL after rate review'],
            ['Tax Filing', 'VAT/EWT/CWT summaries', 'Accounting', 'Finance Manager', 'Admin', 'All filings', 'Summary lists and audit trail required'],
            ['User Access', 'Role and permission changes', 'Admin', 'Admin', 'Owner', 'All changes', 'Login and activity logs retained'],
        ];

        $rolePermissions = collect($this->rolePermissionRoles())->map(function ($role) {
            $enabled = collect($role['enabled']);
            $modules = $role['locked']
                ? 'Full ERP access'
                : $enabled->map(function ($permission) { return explode('.', $permission, 2)[0]; })->unique()->map(function ($module) {
                    return ucwords(str_replace('_', ' ', $module));
                })->implode(', ');
            $approvals = $role['locked'] ? 'Approve all' : $enabled->filter(function ($permission) {
                return substr($permission, -8) === '.approve' || substr($permission, -8) === '.release';
            })->map(function ($permission) {
                return ucwords(str_replace(['.', '_'], ' ', $permission));
            })->implode(', ');

            return [$role['label'], $modules ?: 'No module access', $approvals ?: 'No approval', $role['note']];
        })->all();

        $pendingApprovals = [
            ['AP Payment', 'PV-2026-00312', 'Metro Industrial Supply', 'JOB-SEA-2026-0423', '127,900.00', 'Finance Manager'],
            ['Credit Override', 'CUST-002', 'Blue Anchor Logistics', 'SOAA-2026-00211', '250,000.00', 'Admin'],
            ['FX Revaluation', 'FX-2026-00008', 'Metrobank USD Collections', 'BANK-FX-0007', '250.00', 'Finance Manager'],
        ];

        return view('erp.admin.approval-matrix', compact('approvalLevels', 'rolePermissions', 'pendingApprovals'));
    }

    private function actionDetails($slug)
    {
        $defaults = [
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'subtitle' => 'Operational workspace with records, controls, audit trail, and next-step actions.',
            'primary' => ['label' => 'Dashboard', 'route' => route('erp.dashboard'), 'icon' => 'bi-house'],
            'secondary' => ['label' => 'Export', 'route' => route('erp.export', $slug), 'icon' => 'bi-file-earmark-excel'],
            'cards' => [
                ['Open Items', '12'],
                ['For Review', '4'],
                ['Posted Today', '8'],
                ['Audit Status', 'Enabled'],
            ],
            'steps' => ['Create or select record', 'Review required fields', 'Submit for approval or posting', 'Print, export, or audit'],
            'columns' => ['Reference', 'Module', 'Owner', 'Status', 'Last Update'],
            'rows' => [
                ['REC-2026-0001', 'ERP', 'Accounting', 'Draft', '2026-05-24'],
                ['REC-2026-0002', 'ERP', 'Finance', 'For Review', '2026-05-24'],
                ['REC-2026-0003', 'ERP', 'Admin', 'Posted', '2026-05-23'],
            ],
        ];

        $actions = [
            'invite-user' => [
                'title' => 'Invite User',
                'subtitle' => 'Send user invitations and assign role, department, branch, and access scope.',
                'primary' => ['label' => 'Users & Access', 'route' => route('erp.modules.show', 'users'), 'icon' => 'bi-people'],
                'cards' => [['Pending Invites', '3'], ['Active Users', '18'], ['Departments', '6'], ['Login Logs', 'Enabled']],
                'steps' => ['Enter user email and full name', 'Select role and department', 'Assign company and branch access', 'Send invitation and record audit log'],
                'columns' => ['Invite No.', 'User', 'Role', 'Department', 'Status'],
                'rows' => [
                    ['INV-2026-001', 'new.accounting@seajet.test', 'Accounting', 'Accounting', 'Pending'],
                    ['INV-2026-002', 'warehouse@seajet.test', 'Viewer', 'Operations', 'Pending'],
                    ['INV-2026-003', 'finance.lead@seajet.test', 'Finance', 'Finance', 'Accepted'],
                ],
            ],
            'create-role' => [
                'title' => 'Create Role',
                'subtitle' => 'Define role permissions for modules, approval limits, and report access.',
                'primary' => ['label' => 'Approval Matrix', 'route' => route('erp.approval-matrix'), 'icon' => 'bi-check2-square'],
                'cards' => [['Roles', '7'], ['Permission Groups', '12'], ['Approval Roles', '4'], ['Restricted Roles', '2']],
                'steps' => ['Name the role', 'Select module permissions', 'Assign approval limits', 'Save and audit role changes'],
                'columns' => ['Role', 'Access Scope', 'Approval Rights', 'Users', 'Status'],
                'rows' => [
                    ['Admin', 'Full ERP', 'All approvals', '2', 'Active'],
                    ['Accounting', 'AR/AP/GL/Tax', 'Prepare only', '6', 'Active'],
                    ['Finance', 'Banking/Reports/AP Payment', 'Level 1', '3', 'Active'],
                ],
            ],
            'audit-logs' => [
                'title' => 'Audit Logs',
                'subtitle' => 'Activity log for create, update, approval, posting, print, export, and login events.',
                'primary' => ['label' => 'Tax Audit Trail', 'route' => route('erp.tax.audit-trail'), 'icon' => 'bi-clock-history'],
                'cards' => [['Events Today', '42'], ['Approvals', '9'], ['Exports', '6'], ['Failed Logins', '0']],
                'steps' => ['Filter by date, user, module, or action', 'Review before and after values', 'Export audit results', 'Attach findings to period close'],
                'columns' => ['Date / Time', 'User', 'Action', 'Reference', 'Module'],
                'rows' => [
                    ['2026-05-24 14:10', 'Maria Santos', 'Posted VAT computation', 'BI-2026-01042', 'Tax'],
                    ['2026-05-24 13:45', 'Ana Reyes', 'Approved AP payment', 'PV-2026-00312', 'Payables'],
                    ['2026-05-24 11:20', 'John Cruz', 'Updated bank reconciliation', 'BPI-PHP-001', 'Banking'],
                ],
            ],
            'create-pr' => [
                'title' => 'Create Vendor Bill',
                'subtitle' => 'Record supplier charges and route them into Accounts Payable.',
                'primary' => ['label' => 'Accounts Payable', 'route' => route('erp.expenses.index'), 'icon' => 'bi-receipt-cutoff'],
                'cards' => [['Draft Bills', '5'], ['For Approval', '2'], ['For Payment', '8'], ['Rejected', '1']],
                'steps' => ['Select vendor and branch', 'Add supplier service or charge details', 'Attach supporting documents', 'Submit for AP approval'],
                'columns' => ['Bill No.', 'Department', 'Supplier', 'Amount', 'Status'],
                'rows' => [
                    ['BILL-2026-0042', 'Operations', 'Pacific Fuel Services', '76,880.00', 'For Approval'],
                    ['BILL-2026-0041', 'Admin', 'Harbor Office Center', '18,000.00', 'Draft'],
                    ['BILL-2026-0040', 'Accounting', 'Metro Industrial Supply', '127,900.00', 'Approved'],
                ],
            ],
            'create-po' => [
                'title' => 'Review Vendor Bill Support',
                'subtitle' => 'Check supplier terms, attachments, tax, and shipment tagging before AP approval.',
                'primary' => ['label' => 'Vendor Bill Entry', 'route' => route('erp.payables.vendor-bills.create'), 'icon' => 'bi-receipt'],
                'cards' => [['Open Bills', '11'], ['For Review', '4'], ['Approved', '19'], ['Overdue', '1']],
                'steps' => ['Select vendor bill', 'Confirm supplier terms and currency', 'Review tax and shipment details', 'Approve for payment'],
                'columns' => ['Bill No.', 'Supplier', 'Branch', 'Amount', 'Status'],
                'rows' => [
                    ['BILL-2026-00491', 'Metro Industrial Supply', 'Main', '127,900.00', 'Approved'],
                    ['BILL-2026-00490', 'Pacific Fuel Services', 'Cebu', '76,880.00', 'For Review'],
                    ['BILL-2026-00489', 'Harbor Office Center', 'Pampanga', '18,000.00', 'Closed'],
                ],
            ],
            'receive-goods-services' => [
                'title' => 'Review Service Completion',
                'subtitle' => 'Record completion details and supporting files before supplier bill approval.',
                'primary' => ['label' => 'Vendor Bill Entry', 'route' => route('erp.payables.vendor-bills.create'), 'icon' => 'bi-receipt'],
                'cards' => [['For Review', '4'], ['Reviewed Today', '6'], ['Partial', '2'], ['Matched', '9']],
                'steps' => ['Select vendor bill', 'Confirm quantity or service completion', 'Attach delivery or service proof', 'Match to supplier bill'],
                'columns' => ['Review No.', 'Bill No.', 'Supplier', 'Shipment Job ID', 'Status'],
                'rows' => [
                    ['REV-2026-0028', 'BILL-2026-00490', 'Pacific Fuel Services', 'JOB-SEA-2026-0398', 'Partial'],
                    ['REV-2026-0027', 'BILL-2026-00491', 'Metro Industrial Supply', 'JOB-SEA-2026-0423', 'Matched'],
                    ['REV-2026-0026', 'BILL-2026-00489', 'Harbor Office Center', 'JOB-SEA-2026-0365', 'Closed'],
                ],
            ],
            'supplier-performance' => [
                'title' => 'Supplier Performance',
                'subtitle' => 'Review supplier delivery, quality, payment terms, and AP aging performance.',
                'primary' => ['label' => 'Supplier Masterlist', 'route' => route('erp.suppliers.index'), 'icon' => 'bi-building'],
                'cards' => [['Active Suppliers', '23'], ['On-time Rate', '94%'], ['Open Bills', '7'], ['Preferred', '8']],
                'steps' => ['Review delivery status', 'Check bill accuracy and terms', 'Monitor aging and payment history', 'Update supplier rating'],
                'columns' => ['Supplier', 'Terms', 'On-time', 'Open Bills', 'Rating'],
                'rows' => [
                    ['Metro Industrial Supply', '30 Days', '97%', '2', 'Preferred'],
                    ['Pacific Fuel Services', '60 Days', '92%', '3', 'Active'],
                    ['Harbor Office Center', '30 Days', '89%', '1', 'Watchlist'],
                ],
            ],
            'deposit-collection' => [
                'title' => 'Deposit Collection',
                'subtitle' => 'Prepare collected customer payments for bank deposit and reconciliation.',
                'primary' => ['label' => 'Record Bank Transaction', 'route' => route('erp.banking.transactions.create'), 'icon' => 'bi-bank'],
                'cards' => [['Undeposited', '55,800.00'], ['Deposited Today', '43,200.00'], ['Checks', '4'], ['Bank Transfer', '6']],
                'steps' => ['Select receipts for deposit', 'Choose bank account', 'Record deposit reference', 'Match in bank reconciliation'],
                'columns' => ['Receipt No.', 'Customer', 'Method', 'Amount', 'Status'],
                'rows' => [
                    ['OR-2026-00618', 'North Harbor Trading', 'Bank Transfer', '43,200.00', 'Deposited'],
                    ['AR-2026-00109', 'Island Prime Foods', 'Check', '12,800.00', 'For Deposit'],
                    ['COLA-2026-00031', 'Blue Anchor Logistics', 'USD Transfer', '1,840.00', 'Matched'],
                ],
            ],
            'fund-transfer' => [
                'title' => 'Fund Transfer',
                'subtitle' => 'Transfer funds between cash or bank accounts with FX tracking when currency changes.',
                'primary' => ['label' => 'Cashbook', 'route' => route('erp.banking.cashbook'), 'icon' => 'bi-journal-text'],
                'cards' => [['Pending Transfers', '2'], ['Completed Today', '3'], ['FX Transfers', '1'], ['Bank Accounts', '3']],
                'steps' => ['Select source and destination account', 'Enter transfer amount and currency', 'Add bank reference', 'Post to cashbook and reconcile'],
                'columns' => ['Transfer No.', 'From', 'To', 'Amount', 'Status'],
                'rows' => [
                    ['FT-2026-0011', 'BPI Operating - PHP', 'BDO Payroll - PHP', '200,000.00', 'Released'],
                    ['FT-2026-0010', 'Metrobank USD Collections', 'BPI Operating - PHP', '5,000.00 USD', 'FX Posted'],
                    ['FT-2026-0009', 'Cash on Hand', 'BPI Operating - PHP', '25,000.00', 'Matched'],
                ],
            ],
            'check-monitoring' => [
                'title' => 'Check Monitoring',
                'subtitle' => 'Track released, post-dated, cleared, and bounced checks.',
                'primary' => ['label' => 'Print Checks', 'route' => route('erp.payables.check-print'), 'icon' => 'bi-printer'],
                'cards' => [['Released', '8'], ['Post-dated', '5'], ['Cleared', '22'], ['Bounced', '0']],
                'steps' => ['Record check details', 'Monitor release date', 'Update clearing status', 'Reconcile bank movement'],
                'columns' => ['Check No.', 'Supplier', 'Bank', 'Amount', 'Status'],
                'rows' => [
                    ['CHK-10291', 'Metro Industrial Supply', 'BPI Operating', '127,900.00', 'Released'],
                    ['CHK-10290', 'Harbor Office Center', 'BPI Operating', '18,000.00', 'Cleared'],
                    ['CHK-10289', 'Pacific Fuel Services', 'Metrobank USD', '1,325.00 USD', 'Post-dated'],
                ],
            ],
            'view-record' => [
                'title' => 'Record Details',
                'subtitle' => 'Read-only record view with workflow status, audit trail, and related documents.',
                'primary' => ['label' => 'Dashboard', 'route' => route('erp.dashboard'), 'icon' => 'bi-house'],
                'cards' => [['Status', 'Active'], ['Attachments', '2'], ['Audit Events', '5'], ['Approvals', '1']],
                'steps' => ['Review header details', 'Inspect related ledger and documents', 'Check audit trail', 'Print or export record'],
                'columns' => ['Field', 'Value', 'Source', 'Owner', 'Status'],
                'rows' => [
                    ['Reference', 'REC-2026-0001', 'Module table', 'Accounting', 'Current'],
                    ['Shipment Job ID', 'JOB-SEA-2026-0423', 'Document', 'Operations', 'Traceable'],
                    ['Posting Status', 'Posted', 'GL', 'Accounting', 'Locked'],
                ],
            ],
            'edit-record' => [
                'title' => 'Edit Record',
                'subtitle' => 'Controlled edit screen for draft records with approval and audit protection.',
                'primary' => ['label' => 'Audit Logs', 'route' => route('erp.action', 'audit-logs'), 'icon' => 'bi-clock-history'],
                'cards' => [['Editable Fields', '8'], ['Requires Approval', 'Yes'], ['Draft Mode', 'Enabled'], ['Audit Log', 'Required']],
                'steps' => ['Validate record is not locked', 'Update allowed fields', 'Submit for approval if required', 'Write activity log'],
                'columns' => ['Field', 'Current Value', 'New Value', 'Approval', 'Status'],
                'rows' => [
                    ['Due Date', '2026-06-24', '2026-07-24', 'Finance', 'Pending'],
                    ['Payment Terms', '30 Days', '60 Days', 'Finance', 'Pending'],
                    ['Remarks', 'Original note', 'Updated note', 'Not required', 'Draft'],
                ],
            ],
            'journal-entry' => [
                'title' => 'Journal Entry Quick Action',
                'subtitle' => 'Quick access to shipment-linked journal entry creation and GL posting controls.',
                'primary' => ['label' => 'Journal Entries', 'route' => route('erp.accounting.journals'), 'icon' => 'bi-journal-plus'],
                'cards' => [['Draft Journals', '4'], ['For Posting', '3'], ['Posted Today', '11'], ['Posting Lock', 'Open']],
                'steps' => ['Enter Journal No. and date', 'Attach Shipment Job ID', 'Balance debit and credit lines', 'Post to GL or save as draft'],
                'columns' => ['Journal No.', 'Shipment Job ID', 'Reference', 'Amount', 'Status'],
                'rows' => [
                    ['JV-2026-00218', 'JOB-SEA-2026-0423', 'BI-2026-01043', '98,500.00', 'Draft'],
                    ['JV-2026-00217', 'JOB-SEA-2026-0422', 'BI-2026-01042', '87,946.43', 'Posted'],
                    ['JV-2026-00216', 'JOB-SEA-2026-0398', 'SOAA-2026-00211', '106,720.00', 'Posted'],
                ],
            ],
            'new-journal-entry' => [
                'title' => 'New Journal Entry',
                'subtitle' => 'Create a manual journal entry with required Shipment Job ID traceability.',
                'primary' => ['label' => 'Journal Entries', 'route' => route('erp.accounting.journals'), 'icon' => 'bi-journal-plus'],
                'cards' => [['Draft Journals', '4'], ['Balanced', '3'], ['Unbalanced', '1'], ['Audit Trail', 'Enabled']],
                'steps' => ['Enter reference and Shipment Job ID', 'Select debit account', 'Select credit account', 'Verify balance before posting'],
                'columns' => ['Journal No.', 'Debit', 'Credit', 'Amount', 'Status'],
                'rows' => [
                    ['JV-2026-00219', 'Accounts Receivable', 'Service Revenue', '45,000.00', 'Draft'],
                    ['JV-2026-00218', 'Bank - Operating', 'Accounts Receivable', '43,200.00', 'Posted'],
                    ['JV-2026-00217', 'AP Trade', 'Bank - Operating', '127,900.00', 'For Approval'],
                ],
            ],
            'bank-transfer' => [
                'title' => 'Bank Transfer',
                'subtitle' => 'Quick bank transfer workspace for fund movement between cash and bank accounts.',
                'primary' => ['label' => 'Record Bank Transaction', 'route' => route('erp.banking.transactions.create'), 'icon' => 'bi-arrow-left-right'],
                'cards' => [['Draft Transfers', '2'], ['Released Today', '3'], ['For Reconciliation', '2'], ['FX Transfers', '1']],
                'steps' => ['Select source bank account', 'Select receiving bank account', 'Enter reference and amount', 'Post to cashbook'],
                'columns' => ['Transfer No.', 'Source', 'Destination', 'Amount', 'Status'],
                'rows' => [
                    ['BT-2026-00021', 'BPI Operating - PHP', 'BDO Payroll - PHP', '200,000.00', 'Released'],
                    ['BT-2026-00020', 'Metrobank USD Collections', 'BPI Operating - PHP', '5,000.00 USD', 'FX Posted'],
                    ['BT-2026-00019', 'Cash on Hand', 'BPI Operating - PHP', '25,000.00', 'Matched'],
                ],
            ],
            'approve-ap-payment' => [
                'title' => 'Approve AP Payment',
                'subtitle' => 'Review supplier payment details before voucher release, check printing, or bank transfer.',
                'primary' => ['label' => 'Payment Approvals', 'route' => route('erp.payables.approvals'), 'icon' => 'bi-check2-square'],
                'cards' => [['For Approval', '3'], ['Approved', '8'], ['Returned', '1'], ['Released', '6']],
                'steps' => ['Review supplier and bill reference', 'Check Shipment Job ID and WHT', 'Confirm bank or cheque details', 'Approve or return with notes'],
                'columns' => ['Payment No.', 'Supplier', 'Shipment Job ID', 'Amount', 'Status'],
                'rows' => [
                    ['PV-2026-00312', 'Metro Industrial Supply', 'JOB-SEA-2026-0423', '127,900.00', 'For Approval'],
                    ['PV-2026-00311', 'Pacific Fuel Services', 'JOB-SEA-2026-0398', '1,325.00 USD', 'Approved'],
                    ['PV-2026-00310', 'Harbor Office Center', 'JOB-SEA-2026-0365', '18,000.00', 'Returned'],
                ],
            ],
            'record-supplier-bill' => [
                'title' => 'Record Supplier Bill',
                'subtitle' => 'Create a shipment-linked supplier bill with client tagging, VAT, WHT, and currency.',
                'primary' => ['label' => 'Vendor Bill Entry', 'route' => route('erp.payables.vendor-bills.create'), 'icon' => 'bi-receipt'],
                'cards' => [['Draft Bills', '5'], ['For Approval', '3'], ['USD Bills', '1'], ['WHT Required', '4']],
                'steps' => ['Select supplier', 'Tag client and Shipment Job ID', 'Compute VAT and withholding tax', 'Submit bill for approval'],
                'columns' => ['Bill No.', 'Supplier', 'Shipment Job ID', 'Currency', 'Status'],
                'rows' => [
                    ['BILL-4902', 'Metro Industrial Supply', 'JOB-SEA-2026-0423', 'PHP', 'For Approval'],
                    ['BILL-7710', 'Pacific Fuel Services', 'JOB-SEA-2026-0398', 'USD', 'For Payment'],
                    ['BILL-2420', 'Harbor Office Center', 'JOB-SEA-2026-0365', 'PHP', 'Approved'],
                ],
            ],
            'email-official-receipt' => [
                'title' => 'Email Collection Receipt',
                'subtitle' => 'Prepare a supplementary collection receipt email with PDF attachment and delivery audit trail.',
                'primary' => ['label' => 'Collection Receipt', 'route' => route('erp.official-receipt.preview'), 'icon' => 'bi-receipt'],
                'cards' => [['Queued Emails', '2'], ['Sent Today', '9'], ['PDF Attached', 'Yes'], ['Audit Trail', 'Enabled']],
                'steps' => ['Review customer email', 'Attach Collection Receipt PDF', 'Add email message', 'Send and log delivery status'],
                'columns' => ['Receipt No.', 'Customer', 'Email', 'Amount', 'Status'],
                'rows' => [
                    ['OR-2026-00618', 'North Harbor Trading', 'billing@northharbor.test', '43,200.00', 'Ready'],
                    ['OR-2026-00617', 'Blue Anchor Logistics', 'billing@blueanchor.test', '1,840.00 USD', 'Sent'],
                    ['OR-2026-00616', 'Island Prime Foods', 'ap@islandprime.test', '12,800.00', 'Queued'],
                ],
            ],
            'email-soa' => [
                'title' => 'Email Statement of Account',
                'subtitle' => 'Prepare SOA email with outstanding invoice list and aging summary.',
                'primary' => ['label' => 'Statement Preview', 'route' => route('erp.soa.preview'), 'icon' => 'bi-envelope'],
                'cards' => [['SOA Ready', '6'], ['Sent Today', '4'], ['Outstanding', '238,700.00'], ['Aging Attached', 'Yes']],
                'steps' => ['Review SOA date range', 'Confirm outstanding balances', 'Attach SOA PDF', 'Send and log activity'],
                'columns' => ['SOA No.', 'Customer', 'Date Range', 'Balance', 'Status'],
                'rows' => [
                    ['SOA-2026-0052', 'North Harbor Trading', 'May 2026', '98,500.00', 'Ready'],
                    ['SOA-2026-0051', 'Island Prime Foods', 'May 2026', '48,900.00', 'Sent'],
                    ['SOA-2026-0050', 'Blue Anchor Logistics', 'May 2026', '1,840.00 USD', 'Queued'],
                ],
            ],
            'email-soa-a' => [
                'title' => 'Email SOA A',
                'subtitle' => 'Prepare non-pre-printed SOA A email for customer collection follow-up.',
                'primary' => ['label' => 'SOA A Preview', 'route' => route('erp.soa-a.preview'), 'icon' => 'bi-envelope'],
                'cards' => [['SOA A Ready', '3'], ['USD Documents', '2'], ['Sent Today', '2'], ['No Pre-print', 'Yes']],
                'steps' => ['Review SOA A details', 'Confirm USD/PHP currency', 'Attach PDF', 'Send and update customer ledger'],
                'columns' => ['SOA A No.', 'Customer', 'Shipment Job ID', 'Currency', 'Status'],
                'rows' => [
                    ['SOAA-2026-00211', 'Blue Anchor Logistics', 'JOB-SEA-2026-0398', 'USD', 'Ready'],
                    ['SOAA-2026-00210', 'North Harbor Trading', 'JOB-SEA-2026-0422', 'PHP', 'Sent'],
                    ['SOAA-2026-00209', 'Island Prime Foods', 'JOB-SEA-2026-0365', 'PHP', 'Queued'],
                ],
            ],
        ];

        return array_replace_recursive($defaults, isset($actions[$slug]) ? $actions[$slug] : []);
    }

    public function customers()
    {
        $totalCreditLimit = 0.0;
        $watchlistCount = 0;
        $vatableCount = 0;
        $arAccounts = [
            'AR_TRADE_BI' => 'A/Trade-Billing Invoice',
            'AR_REIMBURSABLE' => 'A/R Reimbursable Charges',
            'AR_TRADE_SOAA' => 'A/R Trade-SOA A',
            'AR_TRADE_DN' => 'A/R Trade-Debit Note',
        ];

        $customers = DB::table('customers')
            ->where('company_id', $this->defaultCompanyId())
            ->orderBy('name')
            ->get()
            ->map(function ($customer) use ($arAccounts) {
                return [
                    'id' => $customer->id,
                    'customer_id' => $customer->customer_id ?: $customer->code,
                    'name' => $customer->name,
                    'tin' => $customer->tin,
                    'vat_classification' => $customer->tax_classification ?: '12% VAT',
                    'contact_person' => $customer->contact_person ?: '-',
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'terms' => $customer->payment_terms,
                    'credit_limit' => $customer->currency.' '.number_format((float) $customer->credit_limit, 2),
                    'credit_limit_value' => (float) $customer->credit_limit,
                    'credit_status' => $customer->credit_status ?: 'Within Limit',
                    'currency' => $customer->currency,
                    'class' => $customer->default_class_code,
                    'ar_account' => isset($arAccounts[$customer->default_ar_account_code]) ? $arAccounts[$customer->default_ar_account_code] : $customer->default_ar_account_code,
                ];
            });

        foreach ($customers as $customer) {
            $totalCreditLimit += $customer['credit_limit_value'];

            if (strtolower($customer['credit_status']) === 'watchlist') {
                $watchlistCount++;
            }

            if ($customer['vat_classification'] === '12%') {
                $vatableCount++;
            }
        }

        return view('erp.customers.index', [
            'customers' => $customers,
            'stats' => [
                ['label' => 'Active Clients', 'value' => $customers->count(), 'icon' => 'bi-people-fill', 'tone' => 'primary'],
                ['label' => 'Credit Limit', 'value' => 'PHP '.number_format($totalCreditLimit, 2), 'icon' => 'bi-bank', 'tone' => 'success'],
                ['label' => 'Watchlist', 'value' => $watchlistCount, 'icon' => 'bi-exclamation-triangle', 'tone' => 'warning'],
                ['label' => 'VATable Clients', 'value' => $vatableCount, 'icon' => 'bi-percent', 'tone' => 'danger'],
            ],
            'workflows' => [
                ['label' => 'Create client', 'route' => route('erp.customers.create'), 'icon' => 'bi-person-plus'],
                ['label' => 'Create invoice', 'route' => route('erp.invoices.create'), 'icon' => 'bi-file-earmark-plus'],
                ['label' => 'Receive payment', 'route' => route('erp.payments.create'), 'icon' => 'bi-credit-card'],
                ['label' => 'AR aging', 'route' => route('erp.customer-billing.aging'), 'icon' => 'bi-bar-chart-line'],
            ],
        ]);
    }

    public function customerCreate()
    {
        $nextCustomerNumber = ((int) DB::table('customers')->where('company_id', $this->defaultCompanyId())->max('id')) + 1;

        return view('erp.customers.create', [
            'nextCustomerCode' => 'CUST-'.str_pad($nextCustomerNumber, 3, '0', STR_PAD_LEFT),
        ]);
    }

    public function customerShow($customer)
    {
        $record = $this->customerRecord($customer);

        $invoices = $this->customerInvoiceHistory($record->id);
        $summary = $this->customerInvoiceSummary($invoices);

        return view('erp.customers.show', [
            'customer' => $record,
            'invoices' => $invoices,
            'summary' => $summary,
        ]);
    }

    public function customerEdit($customer)
    {
        $record = $this->customerRecord($customer);

        return view('erp.customers.create', [
            'nextCustomerCode' => $record->customer_id ?: $record->code,
            'customer' => $record,
            'isEdit' => true,
        ]);
    }

    public function customerUpdate($customer)
    {
        $record = $this->customerRecord($customer);
        $data = request()->validate([
            'customer_id' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'tax_classification' => 'required|string|max:255',
            'payment_terms' => 'required|string|max:50',
            'currency' => 'required|string|max:3',
            'default_class_code' => 'required|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string|max:1000',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_status' => 'required|string|max:50',
            'default_ar_account_code' => 'required|string|max:50',
        ]);

        $companyId = $this->defaultCompanyId();
        $code = strtoupper(trim($data['customer_id']));
        $exists = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('id', '<>', $record->id)
            ->where(function ($query) use ($code) {
                $query->where('code', $code)
                    ->orWhere('customer_id', $code);
            })
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['customer_id' => 'This customer ID already exists.']);
        }

        DB::table('customers')->where('id', $record->id)->update([
            'code' => $code,
            'customer_id' => $code,
            'name' => trim($data['name']),
            'tin' => $data['tin'] ?? null,
            'tax_classification' => $data['tax_classification'],
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'credit_status' => $data['credit_status'],
            'payment_terms' => $data['payment_terms'],
            'credit_term_days' => $this->paymentTermDays($data['payment_terms']),
            'currency' => $data['currency'],
            'default_class_code' => $data['default_class_code'],
            'default_ar_account_code' => $data['default_ar_account_code'],
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('erp.customers.show', $record->id)
            ->with('success', 'Customer '.$code.' was updated.');
    }

    private function customerRecord($customer)
    {
        if (!Schema::hasTable('customers')) {
            abort(404);
        }

        $record = DB::table('customers')
            ->where('company_id', $this->defaultCompanyId())
            ->where(function ($query) use ($customer) {
                $query->where('id', $customer)
                    ->orWhere('code', $customer)
                    ->orWhere('customer_id', $customer);
            })
            ->first();

        if (!$record) {
            abort(404);
        }

        return $record;
    }

    private function customerInvoiceHistory($customerId)
    {
        if (!Schema::hasTable('sales_documents')) {
            return collect();
        }

        return DB::table('sales_documents')
            ->where('company_id', $this->defaultCompanyId())
            ->where('customer_id', $customerId)
            ->whereIn('document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->orderBy('document_date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($invoice) {
                $balance = $this->salesDocumentBalance($invoice);
                $status = strtolower((string) ($invoice->status ?: 'draft'));
                $isFinal = in_array($status, ['cancelled', 'voided'], true);
                $isActive = !in_array($status, ['paid', 'cancelled', 'voided'], true);

                return (object) [
                    'id' => $invoice->id,
                    'document_no' => $invoice->document_no,
                    'soa_no' => $invoice->soa_no,
                    'document_date' => $invoice->document_date,
                    'due_date' => $invoice->due_date,
                    'job_shipment_id' => $invoice->job_shipment_id ?: $invoice->shipment_reference,
                    'currency' => $invoice->currency ?: 'PHP',
                    'grand_total' => (float) $invoice->grand_total,
                    'paid_total' => (float) $invoice->paid_total,
                    'balance' => $balance,
                    'status' => $status,
                    'vat_classification' => $invoice->vat_classification,
                    'class_code' => $invoice->class_code,
                    'counts_as_billed' => !$isFinal,
                    'active_unpaid' => $isActive && $balance > 0,
                ];
            });
    }

    private function customerInvoiceSummary($invoices)
    {
        $summary = [
            'invoice_count' => 0,
            'open_count' => 0,
            'billed' => 0,
            'paid' => 0,
            'open_balance' => 0,
        ];

        foreach ($invoices as $invoice) {
            if (!$invoice->counts_as_billed) {
                continue;
            }

            $summary['invoice_count']++;
            $summary['billed'] += $invoice->grand_total;
            $summary['paid'] += $invoice->paid_total;
            if ($invoice->active_unpaid) {
                $summary['open_count']++;
                $summary['open_balance'] += $invoice->balance;
            }
        }

        return $summary;
    }

    public function customerStore()
    {
        $data = request()->validate([
            'customer_id' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'tax_classification' => 'required|string|max:255',
            'payment_terms' => 'required|string|max:50',
            'currency' => 'required|string|max:3',
            'default_class_code' => 'required|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string|max:1000',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_status' => 'required|string|max:50',
            'default_ar_account_code' => 'required|string|max:50',
        ]);

        $companyId = $this->defaultCompanyId();
        $code = strtoupper(trim($data['customer_id']));

        $exists = DB::table('customers')
            ->where('company_id', $companyId)
            ->where(function ($query) use ($code) {
                $query->where('code', $code)
                    ->orWhere('customer_id', $code);
            })
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['customer_id' => 'This customer ID already exists.']);
        }

        DB::table('customers')->insert([
            'company_id' => $companyId,
            'code' => $code,
            'customer_id' => $code,
            'name' => $data['name'],
            'tin' => isset($data['tin']) ? $data['tin'] : null,
            'tax_classification' => $data['tax_classification'],
            'email' => isset($data['email']) ? $data['email'] : null,
            'phone' => isset($data['phone']) ? $data['phone'] : null,
            'billing_address' => isset($data['billing_address']) ? $data['billing_address'] : null,
            'contact_person' => isset($data['contact_person']) ? $data['contact_person'] : null,
            'credit_limit' => isset($data['credit_limit']) ? $data['credit_limit'] : 0,
            'credit_status' => $data['credit_status'],
            'payment_terms' => $data['payment_terms'],
            'credit_term_days' => $this->paymentTermDays($data['payment_terms']),
            'currency' => $data['currency'],
            'default_class_code' => $data['default_class_code'],
            'default_ar_account_code' => $data['default_ar_account_code'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('erp.customers.index')
            ->with('success', 'Customer '.$code.' was saved to the database.');
    }

    private function defaultCompanyId()
    {
        $userCompanyId = auth()->check() ? auth()->user()->company_id : null;
        if ($userCompanyId && DB::table('companies')->where('id', $userCompanyId)->where('is_active', true)->exists()) {
            return $userCompanyId;
        }

        $companyId = DB::table('companies')->orderBy('id')->value('id');

        if ($companyId) {
            return $companyId;
        }

        return DB::table('companies')->insertGetId([
            'code' => 'SJ',
            'name' => 'SeaJet Holdings Inc.',
            'tin' => '000-123-456-000',
            'address' => 'Harbor Business Center, Manila, Philippines',
            'base_currency' => 'PHP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentTermDays($paymentTerms)
    {
        if (preg_match('/\d+/', $paymentTerms, $matches)) {
            return (int) $matches[0];
        }

        return 0;
    }

    public function suppliers()
    {
        $suppliers = $this->vendorRows();

        return view('erp.suppliers.index', [
            'suppliers' => $suppliers,
            'stats' => $this->vendorDashboardStats($suppliers),
            'recentBills' => $this->vendorRecentBills(),
            'agingBuckets' => $this->vendorAgingBuckets(),
        ]);
    }

    public function supplierCreate()
    {
        return view('erp.suppliers.create', [
            'nextSupplierCode' => $this->nextDocumentNumber('vendor', 'VND-', 4, false),
            'expenseAccounts' => $this->expenseAccountOptions(),
        ]);
    }

    public function supplierStore()
    {
        $data = request()->validate([
            'supplier_id' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'tax_classification' => 'required|string|max:255',
            'payment_terms' => 'required|string|max:50',
            'currency' => 'required|string|max:3',
            'default_class_code' => 'required|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'zip_code' => ['nullable', 'regex:/^\d{4}$/'],
            'default_ap_account' => 'nullable|string|max:255',
            'default_expense_account_id' => 'nullable|integer',
            'preferred_payment_method' => 'nullable|string|max:255',
            'withholding_tax' => 'nullable|string|max:50',
        ]);

        $companyId = $this->defaultCompanyId();
        $requestedCode = strtoupper(trim((string) ($data['supplier_id'] ?? '')));
        $code = $requestedCode !== ''
            ? $requestedCode
            : $this->nextDocumentNumber('vendor', 'VND-', 4);
        $exists = DB::table('suppliers')
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->withErrors(['supplier_id' => 'This vendor ID already exists.']);
        }

        DB::table('suppliers')->insert([
            'company_id' => $companyId,
            'code' => $code,
            'name' => $data['name'],
            'tin' => isset($data['tin']) ? $data['tin'] : null,
            'tax_classification' => $data['tax_classification'],
            'contact_person' => isset($data['contact_person']) ? $data['contact_person'] : null,
            'email' => isset($data['email']) ? $data['email'] : null,
            'phone' => isset($data['phone']) ? $data['phone'] : null,
            'address' => isset($data['address']) ? $data['address'] : null,
            'zip_code' => $data['zip_code'] ?? null,
            'payment_terms' => $data['payment_terms'],
            'payment_term_days' => $this->paymentTermDays($data['payment_terms']),
            'currency' => $data['currency'],
            'preferred_payment_method' => isset($data['preferred_payment_method']) ? $data['preferred_payment_method'] : null,
            'default_class_code' => $data['default_class_code'],
            'default_ap_account' => isset($data['default_ap_account']) ? $data['default_ap_account'] : null,
            'default_expense_account_id' => $data['default_expense_account_id'] ?? null,
            'withholding_tax' => isset($data['withholding_tax']) ? $data['withholding_tax'] : '0%',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->syncDocumentNumberSeries('vendor', $code);

        return redirect()->route('erp.suppliers.index')->with('success', 'Vendor '.$code.' was saved to the database.');
    }

    public function supplierEdit($supplier)
    {
        $record = DB::table('suppliers')
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $supplier)
            ->first();
        abort_unless($record, 404);

        return view('erp.suppliers.create', [
            'nextSupplierCode' => $record->code,
            'expenseAccounts' => $this->expenseAccountOptions(),
            'supplier' => $record,
            'isEdit' => true,
        ]);
    }

    public function supplierUpdate($supplier)
    {
        $data = request()->validate([
            'supplier_id' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'tax_classification' => 'required|string|max:255',
            'payment_terms' => 'required|string|max:50',
            'currency' => 'required|string|max:3',
            'default_class_code' => 'required|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'zip_code' => ['nullable', 'regex:/^\d{4}$/'],
            'default_ap_account' => 'nullable|string|max:255',
            'default_expense_account_id' => 'nullable|integer',
            'preferred_payment_method' => 'nullable|string|max:255',
            'withholding_tax' => 'nullable|string|max:50',
        ]);

        $companyId = $this->defaultCompanyId();
        $record = DB::table('suppliers')->where('company_id', $companyId)->where('id', $supplier)->first();
        abort_unless($record, 404);

        $code = strtoupper(trim($data['supplier_id']));
        $duplicate = DB::table('suppliers')
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->where('id', '<>', $record->id)
            ->exists();
        if ($duplicate) {
            return redirect()->back()->withInput()->withErrors(['supplier_id' => 'This vendor ID already exists.']);
        }

        DB::table('suppliers')->where('id', $record->id)->update([
            'code' => $code,
            'name' => $data['name'],
            'tin' => $data['tin'] ?? null,
            'tax_classification' => $data['tax_classification'],
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
            'payment_terms' => $data['payment_terms'],
            'payment_term_days' => $this->paymentTermDays($data['payment_terms']),
            'currency' => $data['currency'],
            'preferred_payment_method' => $data['preferred_payment_method'] ?? null,
            'default_class_code' => $data['default_class_code'],
            'default_ap_account' => $data['default_ap_account'] ?? null,
            'default_expense_account_id' => $data['default_expense_account_id'] ?? null,
            'withholding_tax' => $data['withholding_tax'] ?? '0%',
            'updated_at' => now(),
        ]);

        return redirect()->route('erp.suppliers.index')->with('success', 'Vendor '.$code.' was updated.');
    }

    private function vendorRows()
    {
        if (!Schema::hasTable('suppliers')) {
            return $this->suppliers;
        }

        return DB::table('suppliers')
            ->where('company_id', $this->defaultCompanyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($supplier) {
                $openAp = $this->supplierOpenPayable($supplier->id);

                return [
                    'id' => $supplier->id,
                    'supplier_id' => $supplier->code,
                    'name' => $supplier->name,
                    'tin' => $supplier->tin,
                    'vat_classification' => $supplier->tax_classification ?: '12% VAT',
                    'contact_person' => $supplier->contact_person ?: '-',
                    'email' => $supplier->email,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                    'terms' => $supplier->payment_terms,
                    'payment_method' => $supplier->preferred_payment_method ?: '-',
                    'withholding_tax' => $supplier->withholding_tax ?: '0%',
                    'currency' => $supplier->currency ?: 'PHP',
                    'class' => $supplier->default_class_code ?: 'MANILA',
                    'ap_account' => $supplier->default_ap_account ?: 'Accounts Payable Trade',
                    'expense_account_id' => $supplier->default_expense_account_id ?? null,
                    'expense_account' => $this->accountLabelById($supplier->default_expense_account_id ?? null),
                    'open_bills' => $this->supplierOpenBillCount($supplier->id),
                    'open_ap' => $openAp,
                    'open_ap_display' => ($supplier->currency ?: 'PHP').' '.number_format($openAp, 2),
                ];
            })
            ->all();
    }

    private function vendorDashboardStats(array $suppliers)
    {
        $activeCount = count($suppliers);
        $openBills = array_sum(array_column($suppliers, 'open_bills'));
        $openAp = array_sum(array_column($suppliers, 'open_ap'));
        $withholdingCount = count(array_filter($suppliers, function ($supplier) {
            return ($supplier['withholding_tax'] ?? '0%') !== '0%';
        }));

        return [
            ['label' => 'Active Vendors', 'value' => $activeCount, 'icon' => 'bi-building-fill', 'tone' => 'primary'],
            ['label' => 'Open AP', 'value' => 'PHP '.number_format($openAp, 2), 'icon' => 'bi-credit-card-2-front', 'tone' => 'danger'],
            ['label' => 'Open Bills', 'value' => $openBills, 'icon' => 'bi-receipt', 'tone' => 'warning'],
            ['label' => 'Withholding Setup', 'value' => $withholdingCount, 'icon' => 'bi-percent', 'tone' => 'success'],
        ];
    }

    private function expenseAccountOptions()
    {
        if (!Schema::hasTable('accounts')) {
            return collect();
        }

        $query = DB::table('accounts')
            ->leftJoin('account_categories', 'account_categories.id', '=', 'accounts.account_category_id')
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.atc_code',
                'accounts.tax_code as withholding_rate',
                'account_categories.name as category'
            )
            ->where('accounts.is_active', true)
            ->where('accounts.company_id', $this->defaultCompanyId())
            ->whereIn('accounts.type', $this->apItemAccountTypes());

        if (Schema::hasColumn('accounts', 'show_in_ap')) {
            $query->where('accounts.show_in_ap', true);
        }

        return $query
            ->orderBy('accounts.code')
            ->get()
            ->map(function ($account) {
                $label = trim(($account->code ? $account->code.' - ' : '').$account->name);

                return (object) [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'label' => $label,
                    'category' => $account->category,
                    'atc_code' => $account->atc_code,
                    'withholding_rate' => $this->withholdingRatePercent($account->withholding_rate),
                ];
            });
    }

    private function apItemAccountTypes()
    {
        return ['Expense', 'Other Expense', 'Other Current Asset', 'Fixed Asset', 'Other Asset'];
    }

    private function glAccountOptions()
    {
        if (!Schema::hasTable('accounts')) {
            return collect();
        }

        return DB::table('accounts')
            ->leftJoin('account_categories', 'account_categories.id', '=', 'accounts.account_category_id')
            ->select('accounts.id', 'accounts.code', 'accounts.name', 'account_categories.name as category')
            ->where('accounts.is_active', true)
            ->where('accounts.company_id', $this->defaultCompanyId())
            ->orderBy('accounts.code')
            ->get()
            ->map(function ($account) {
                return (object) [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'label' => trim(($account->code ? $account->code.' - ' : '').$account->name),
                    'category' => $account->category,
                ];
            });
    }

    private function accountLabelById($accountId)
    {
        if (!$accountId || !Schema::hasTable('accounts')) {
            return '';
        }

        $account = DB::table('accounts')->where('company_id', $this->defaultCompanyId())->where('id', $accountId)->first();
        if (!$account) {
            return '';
        }

        return trim(($account->code ? $account->code.' - ' : '').$account->name);
    }

    private function expenseAccountIdFromLabel($label)
    {
        $label = trim((string) $label);
        if ($label === '' || !Schema::hasTable('accounts')) {
            return null;
        }

        $code = trim(strtok($label, '-'));
        $query = DB::table('accounts')->where('company_id', $this->defaultCompanyId());
        if ($code !== '') {
            $query->where('code', $code);
        }

        $id = $query->value('id');
        if ($id) {
            return $id;
        }

        $name = trim(preg_replace('/^[^-]+-\s*/', '', $label));

        return DB::table('accounts')
            ->where('company_id', $this->defaultCompanyId())
            ->whereIn('name', array_values(array_unique([$name, $label])))
            ->value('id');
    }

    private function vendorRecentBills()
    {
        if (!Schema::hasTable('purchase_documents')) {
            return [];
        }

        return DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->select(
                'purchase_documents.document_no',
                'suppliers.name as supplier_name',
                'purchase_documents.shipment_job_id',
                'purchase_documents.currency',
                'purchase_documents.grand_total',
                'purchase_documents.paid_total',
                'purchase_documents.status',
                'purchase_documents.document_date'
            )
            ->where('purchase_documents.company_id', $this->defaultCompanyId())
            ->orderBy('purchase_documents.document_date', 'desc')
            ->orderBy('purchase_documents.id', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($bill) {
                return [
                    'bill_no' => $bill->document_no,
                    'supplier' => $bill->supplier_name ?: '-',
                    'shipment_job_id' => $bill->shipment_job_id ?: '-',
                    'currency' => $bill->currency ?: 'PHP',
                    'amount' => number_format((float) $bill->grand_total, 2),
                    'balance' => number_format(max(0, (float) $bill->grand_total - (float) $bill->paid_total), 2),
                    'status' => ucwords(str_replace('_', ' ', $bill->status)),
                    'date' => $bill->document_date,
                ];
            })
            ->all();
    }

    private function vendorAgingBuckets()
    {
        $buckets = ['Current' => 0.0, '30 Days' => 0.0, '60 Days' => 0.0, '90+ Days' => 0.0];

        if (Schema::hasTable('purchase_documents')) {
            DB::table('purchase_documents')
                ->select('due_date', 'grand_total', 'paid_total', 'status')
                ->whereNotIn('status', ['paid', 'cancelled', 'voided'])
                ->get()
                ->each(function ($bill) use (&$buckets) {
                    $balance = max(0, (float) $bill->grand_total - (float) $bill->paid_total);
                    $daysPastDue = $bill->due_date ? now()->diffInDays(\Carbon\Carbon::parse($bill->due_date), false) * -1 : 0;

                    if ($daysPastDue <= 0) {
                        $buckets['Current'] += $balance;
                    } elseif ($daysPastDue <= 30) {
                        $buckets['30 Days'] += $balance;
                    } elseif ($daysPastDue <= 60) {
                        $buckets['60 Days'] += $balance;
                    } else {
                        $buckets['90+ Days'] += $balance;
                    }
                });
        }

        return collect($buckets)->map(function ($amount, $label) {
            return ['label' => $label, 'value' => 'PHP '.number_format($amount, 2)];
        })->values()->all();
    }

    private function supplierOpenPayable($supplierId)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return 0.0;
        }

        return (float) DB::table('purchase_documents')
            ->where('supplier_id', $supplierId)
            ->whereNotIn('status', ['paid', 'cancelled', 'voided'])
            ->select(DB::raw('COALESCE(SUM(grand_total - paid_total), 0) as balance'))
            ->value('balance');
    }

    private function supplierOpenBillCount($supplierId)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return 0;
        }

        return (int) DB::table('purchase_documents')
            ->where('supplier_id', $supplierId)
            ->whereNotIn('status', ['paid', 'cancelled', 'voided'])
            ->count();
    }

    public function usersIndex()
    {
        return view('erp.users.index', [
            'users' => $this->userRows(),
            'roles' => $this->rolePermissionRoles(),
            'permissionGroups' => $this->rolePermissionGroups(),
        ]);
    }

    public function roleStore()
    {
        $data = request()->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);
        $companyId = $this->defaultCompanyId();
        $slug = str_replace('-', '_', Str::slug($data['name']));
        if ($slug === '' || in_array($slug, ['admin', 'administrator'], true)) {
            return back()->withErrors(['name' => 'Please choose a different role name.']);
        }
        $exists = DB::table('roles')->where('slug', $slug)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id')->orWhere('company_id', $companyId);
            })->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['name' => 'A role with this name already exists.']);
        }

        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $companyId,
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? 'Custom company role',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        app(ActivityLogger::class)->log('created', 'role', $roleId, [], ['name' => $data['name'], 'slug' => $slug], $companyId);

        return redirect()->route('erp.users.index')->with('status', 'Role created. Assign its permissions below.');
    }

    public function rolePermissionsUpdate($role)
    {
        $companyId = $this->defaultCompanyId();
        $recordQuery = Role::where('id', $role);
        $companyId === null ? $recordQuery->whereNull('company_id') : $recordQuery->where('company_id', $companyId);
        $record = $recordQuery->first();
        abort_unless($record, 404);
        abort_if($record->slug === 'admin', 422, 'Administrator permissions cannot be restricted.');

        $submitted = (array) request('permissions', []);
        $allowedSlugs = [];
        foreach (app(RbacService::class)->moduleDefinitions() as $modules) {
            foreach ($modules as $module => $definition) {
                $moduleValues = (array) ($submitted[$module] ?? []);
                $hasWrite = false;
                foreach ($definition['actions'] as $action) {
                    if ($action !== 'view' && (string) ($moduleValues[$action] ?? '0') === '1') {
                        $hasWrite = true;
                    }
                    if ((string) ($moduleValues[$action] ?? '0') === '1') {
                        $allowedSlugs[] = $module.'.'.$action;
                    }
                }
                if ($hasWrite) {
                    $allowedSlugs[] = $module.'.view';
                }
            }
        }
        $allowedSlugs = array_values(array_unique($allowedSlugs));
        $permissionIds = Permission::whereIn('slug', $allowedSlugs)->pluck('id')->all();
        $before = $record->permissions()->pluck('permissions.slug')->all();
        $record->permissions()->sync($permissionIds);
        app(ActivityLogger::class)->log('permissions_updated', 'role', $record->id, ['permissions' => $before], [
            'permissions' => $allowedSlugs,
        ], $companyId);

        return redirect()->route('erp.users.index')->with('status', $record->name.' permissions saved.');
    }

    public function userCreate()
    {
        return view('erp.users.create', ['roles' => $this->rolePermissionRoles()]);
    }

    public function userEdit($user)
    {
        $record = DB::table('users')->where('company_id', $this->defaultCompanyId())->where('id', $user)->first();
        abort_unless($record, 404);

        return view('erp.users.edit', [
            'userId' => $user,
            'record' => $record,
            'roles' => $this->rolePermissionRoles(),
        ]);
    }

    public function userDelete($user)
    {
        $record = DB::table('users')->where('company_id', $this->defaultCompanyId())->where('id', $user)->first();
        abort_unless($record, 404);

        return view('erp.users.delete', ['userId' => $user, 'record' => $record]);
    }

    public function userDeleteReview()
    {
        $data = request()->validate([
            'user_id' => 'required|integer',
            'name' => 'nullable|string|max:255',
            'delete_action' => 'required|string|max:100',
            'reason' => 'required|string|max:1000',
        ]);
        abort_if((int) $data['user_id'] === (int) auth()->id(), 422, 'You cannot deactivate your own account.');
        $record = DB::table('users')->where('company_id', $this->defaultCompanyId())->where('id', $data['user_id'])->first();
        abort_unless($record, 404);
        DB::table('users')->where('id', $record->id)->update(['is_active' => false, 'updated_at' => now()]);
        app(ActivityLogger::class)->log('deactivated', 'user', $record->id, ['is_active' => $record->is_active], [
            'is_active' => false,
            'reason' => $data['reason'],
        ], $record->company_id);

        return view('erp.users.delete-review', [
            'delete' => $data,
        ]);
    }

    public function userReview()
    {
        $data = request()->validate([
            'user_id' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:100',
            'status' => 'required|in:Active,Inactive,Locked,Pending Invite',
            'access_scope' => 'nullable|string|max:2000',
            'password' => 'nullable|string|min:12|confirmed',
        ]);
        $companyId = $this->defaultCompanyId();
        $roleSlug = app(RbacService::class)->normalizeRole($data['role']);
        $roleExists = Schema::hasTable('roles') && DB::table('roles')
            ->where('slug', $roleSlug)
            ->where('is_active', true)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id')->orWhere('company_id', $companyId);
            })->exists();
        if (!$roleExists && !array_key_exists($roleSlug, config('rbac.roles', []))) {
            return back()->withInput()->withErrors(['role' => 'The selected role is not active for this company.']);
        }
        $userId = ctype_digit((string) ($data['user_id'] ?? '')) ? (int) $data['user_id'] : null;
        $existing = $userId ? DB::table('users')->where('company_id', $companyId)->where('id', $userId)->first() : null;
        if (!$existing && empty($data['password'])) {
            return back()->withInput()->withErrors(['password' => 'A password of at least 12 characters is required for a new user.']);
        }
        $duplicate = DB::table('users')->where('email', strtolower($data['email']))
            ->when($existing, function ($query) use ($existing) { $query->where('id', '<>', $existing->id); })
            ->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors(['email' => 'That email address is already assigned to another user.']);
        }

        $branchId = null;
        if (!empty($data['branch']) && strtolower($data['branch']) !== 'all') {
            $branchId = DB::table('branches')->where('company_id', $companyId)
                ->where(function ($query) use ($data) {
                    $query->where('code', $data['branch'])->orWhere('name', $data['branch']);
                })->value('id');
        }
        $payload = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'role' => $roleSlug,
            'department' => $data['department'] ?? null,
            'is_active' => $data['status'] === 'Active',
            'updated_at' => now(),
        ];
        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
            $payload['must_change_password'] = false;
        }

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($payload);
            $savedId = $existing->id;
            $action = 'updated';
        } else {
            $payload['created_at'] = now();
            $savedId = DB::table('users')->insertGetId($payload);
            $action = 'created';
        }
        app(ActivityLogger::class)->log($action, 'user', $savedId, $existing ? (array) $existing : [], [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'is_active' => $payload['is_active'],
        ], $companyId);

        return view('erp.users.review', [
            'user' => array_merge(request()->only(['name', 'email', 'role', 'department', 'branch', 'status', 'access_scope']), [
                'user_id' => $savedId,
                'result' => ucfirst($action),
            ]),
        ]);
    }

    public function userStatus()
    {
        return view('erp.users.status', ['users' => $this->userRows()]);
    }

    public function userStatusReview()
    {
        $data = request()->validate([
            'user_id' => 'required|integer',
            'name' => 'nullable|string|max:255',
            'current_status' => 'nullable|string|max:100',
            'new_status' => 'required|in:Active,Inactive,Locked,Pending Invite',
            'reason' => 'required|string|max:1000',
        ]);
        if ((int) $data['user_id'] === (int) auth()->id() && $data['new_status'] !== 'Active') {
            abort(422, 'You cannot deactivate your own account.');
        }
        $record = DB::table('users')->where('company_id', $this->defaultCompanyId())->where('id', $data['user_id'])->first();
        abort_unless($record, 404);
        $isActive = $data['new_status'] === 'Active';
        DB::table('users')->where('id', $record->id)->update(['is_active' => $isActive, 'updated_at' => now()]);
        app(ActivityLogger::class)->log('status_changed', 'user', $record->id, ['is_active' => $record->is_active], [
            'is_active' => $isActive,
            'status' => $data['new_status'],
            'reason' => $data['reason'],
        ], $record->company_id);

        return view('erp.users.status-review', [
            'status' => $data,
        ]);
    }

    private function userRows()
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        $companyId = $this->defaultCompanyId();
        $roleNames = Schema::hasTable('roles')
            ? DB::table('roles')->where('company_id', $companyId)->pluck('name', 'slug')
            : collect();

        return DB::table('users')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
            ->where('users.company_id', $companyId)
            ->select('users.*', 'branches.name as branch_name')
            ->orderBy('users.name')
            ->get()
            ->map(function ($user) use ($roleNames) {
                $roleSlug = app(RbacService::class)->normalizeRole($user->role ?: 'viewer');
                return [
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $roleNames->get($roleSlug, config('rbac.roles.'.$roleSlug.'.name', ucwords(str_replace('_', ' ', $roleSlug)))),
                    'department' => $user->department ?: '-',
                    'branch' => $user->branch_name ?: 'All',
                    'status' => $user->is_active ? 'Active' : 'Inactive',
                ];
            })->all();
    }

    public function vendorBillCreate()
    {
        $bill = request('bill') && Schema::hasTable('purchase_documents')
            ? $this->apBillByNumber(request('bill'))
            : null;

        return view('erp.payables.vendor-bill-create', [
            'suppliers' => $this->vendorRows(),
            'customers' => $this->customerSelectOptions(),
            'expenseAccounts' => $this->expenseAccountOptions(),
            'bill' => $bill,
            'lines' => $bill ? $this->apBillLines($bill['id']) : collect(),
            'attachments' => $bill ? $this->documentAttachments('purchase_document', $bill['id']) : collect(),
            'nextBillNo' => $this->nextDocumentNumber('vendor_bill', 'BILL-', 5, false),
        ]);
    }

    public function expensesIndex()
    {
        $expenses = $this->apRegisterRows();
        $phpPayables = $this->openApTotalByCurrency('PHP');
        $usdPayables = $this->openApTotalByCurrency('USD');
        $forApproval = Schema::hasTable('purchase_documents')
            ? DB::table('purchase_documents')->where('approval_status', 'For Approval')->count()
            : 0;

        $apStats = [
            ['label' => 'PHP Payables', 'value' => 'PHP '.number_format($phpPayables, 2), 'icon' => 'bi-cash-stack', 'tone' => 'primary'],
            ['label' => 'USD Payables', 'value' => 'USD '.number_format($usdPayables, 2), 'icon' => 'bi-currency-dollar', 'tone' => 'success'],
            ['label' => 'For Approval', 'value' => $forApproval, 'icon' => 'bi-check2-square', 'tone' => 'warning'],
            ['label' => 'WHT Computation', 'value' => 'Enabled', 'icon' => 'bi-percent', 'tone' => 'danger'],
        ];

        $apFeatures = [
            ['label' => 'Vendor bill entry', 'icon' => 'bi-receipt'],
            ['label' => 'Shipment-linked expenses', 'icon' => 'bi-tags'],
            ['label' => 'Client tagging', 'icon' => 'bi-person-lines-fill'],
            ['label' => 'Check vouchers', 'icon' => 'bi-printer'],
            ['label' => 'Payment approvals', 'icon' => 'bi-check2-square'],
            ['label' => 'Bank / cheque / online transfer', 'icon' => 'bi-bank'],
            ['label' => 'Check printing', 'icon' => 'bi-file-earmark-check'],
            ['label' => 'Withholding tax computation', 'icon' => 'bi-percent'],
            ['label' => 'AP aging reports', 'icon' => 'bi-clock-history'],
            ['label' => 'Multi-currency: USD & PHP', 'icon' => 'bi-currency-exchange'],
        ];

        $apWorkflows = [
            ['label' => 'Vendor bill entry', 'route' => route('erp.payables.vendor-bills.create'), 'icon' => 'bi-plus-lg'],
            ['label' => 'Payment approvals', 'route' => route('erp.payables.approvals'), 'icon' => 'bi-check2-square'],
            ['label' => 'Payment processing', 'route' => route('erp.payables.payments.create'), 'icon' => 'bi-credit-card'],
            ['label' => 'Print checks', 'route' => route('erp.payables.check-print'), 'icon' => 'bi-file-earmark-check'],
            ['label' => 'AP aging report', 'route' => route('erp.payables.aging'), 'icon' => 'bi-clock-history'],
        ];

        return view('erp.payables.expenses-index', compact('expenses', 'apStats', 'apFeatures', 'apWorkflows'));
    }

    public function vendorBillReview()
    {
        $data = request()->validate([
            'transaction_type' => 'required|string|max:50',
            'ap_account' => 'nullable|string|max:255',
            'bill_received' => 'nullable|string|max:10',
            'supplier_id' => 'required|string|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_address' => 'nullable|string',
            'client_customer_id' => 'nullable|string|max:100',
            'shipment_job_id' => 'nullable|string|max:100',
            'bill_no' => 'nullable|string|max:100',
            'original_bill_no' => 'nullable|string|max:100',
            'bill_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'terms' => 'nullable|string|max:100',
            'currency' => 'required|string|max:3',
            'exchange_rate' => 'nullable',
            'gross_amount' => 'nullable',
            'php_amount' => 'nullable',
            'withholding_tax_rate' => 'nullable',
            'withholding_tax_amount' => 'nullable',
            'net_amount' => 'nullable',
            'memo' => 'nullable|string',
            'expense_account' => 'nullable|array',
            'expense_tax' => 'nullable|array',
            'expense_atc_code' => 'nullable|array',
            'expense_withholding_rate' => 'nullable|array',
            'expense_amount' => 'nullable|array',
            'expense_memo' => 'nullable|array',
            'expense_customer_job' => 'nullable|array',
            'expense_billable' => 'nullable|array',
            'expense_class' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        if (request('save_mode') === 'post') {
            $this->authorizeAccountingPosting('payables', 'approve');
        }

        $shouldPost = request('save_mode') === 'post';
        $bill = DB::transaction(function () use ($data, $shouldPost) {
            $bill = $this->saveApBill($data);
            if ($shouldPost) {
                app(PostingService::class)->postPurchaseDocument($bill['id']);
                $bill = $this->apBillById($bill['id']);
            }

            return $bill;
        });

        if (request('save_mode') === 'close') {
            return redirect()
                ->route('erp.payables.vendor-bills.view', $bill['bill_no'])
                ->with('status', 'Bill saved.');
        }

        if (request('save_mode') === 'new') {
            return redirect()
                ->route('erp.payables.vendor-bills.create')
                ->with('status', 'Bill saved. You can encode another bill.');
        }

        return view('erp.payables.vendor-bill-review', [
            'bill' => $bill,
            'lines' => $this->apBillLines($bill['id']),
            'attachments' => $this->documentAttachments('purchase_document', $bill['id']),
        ]);
    }

    public function vendorBillView($bill)
    {
        $record = is_numeric($bill) ? $this->apBillById($bill) : $this->apBillByNumber($bill);
        abort_unless(!empty($record), 404);

        return view('erp.payables.vendor-bill-review', [
            'bill' => $record,
            'lines' => $this->apBillLines($record['id']),
            'attachments' => $this->documentAttachments('purchase_document', $record['id']),
        ]);
    }

    public function deleteVendorBill($bill)
    {
        $this->authorizeAccountingPosting('payables', 'delete');
        $companyId = $this->defaultCompanyId();

        $deleted = DB::transaction(function () use ($bill, $companyId) {
            $query = DB::table('purchase_documents')
                ->where('company_id', $companyId)
                ->lockForUpdate();
            $record = is_numeric($bill)
                ? $query->where('id', $bill)->first()
                : $query->where('document_no', $bill)->first();
            abort_unless($record, 404);

            $hasJournal = Schema::hasTable('journal_entries') && DB::table('journal_entries')
                ->where('company_id', $companyId)
                ->where('source_type', 'purchase_document')
                ->where('source_id', $record->id)
                ->exists();
            $isPosted = in_array(strtolower((string) $record->status), ['posted', 'approved', 'paid'], true)
                || ($record->posting_status ?? 'unposted') !== 'unposted'
                || !empty($record->journal_entry_id)
                || $hasJournal;
            if ($isPosted) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill' => 'A posted vendor bill cannot be deleted because it has accounting entries.',
                ]);
            }

            if ((float) $record->paid_total > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill' => 'A paid or partially paid vendor bill cannot be deleted.',
                ]);
            }

            $hasPaymentApplication = Schema::hasTable('payment_applications') && DB::table('payment_applications')
                ->where('purchase_document_id', $record->id)
                ->exists();
            $hasPayment = Schema::hasTable('payments') && DB::table('payments')
                ->where('company_id', $companyId)
                ->where('bill_no', $record->document_no)
                ->exists();
            if ($hasPaymentApplication || $hasPayment) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill' => 'Delete or void the linked payment before deleting this vendor bill.',
                ]);
            }

            $attachments = Schema::hasTable('document_attachments')
                ? DB::table('document_attachments')
                    ->where('document_type', 'purchase_document')
                    ->where('document_id', $record->id)
                    ->get()
                : collect();

            app(ActivityLogger::class)->log('deleted', 'purchase_document', $record->id, [
                'document_no' => $record->document_no,
                'status' => $record->status,
                'grand_total' => $record->grand_total,
            ], [], $companyId);

            foreach (['vendor_bill_lines', 'purchase_document_lines'] as $lineTable) {
                if (Schema::hasTable($lineTable)) {
                    DB::table($lineTable)->where('purchase_document_id', $record->id)->delete();
                }
            }
            if (Schema::hasTable('document_attachments')) {
                DB::table('document_attachments')
                    ->where('document_type', 'purchase_document')
                    ->where('document_id', $record->id)
                    ->delete();
            }
            DB::table('purchase_documents')->where('id', $record->id)->delete();

            return ['record' => $record, 'attachments' => $attachments];
        });

        foreach ($deleted['attachments'] as $attachment) {
            try {
                Storage::disk($attachment->disk ?: 'local')->delete($attachment->path);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->route('erp.expenses.index')
            ->with('status', 'Vendor bill '.$deleted['record']->document_no.' deleted.');
    }

    public function uploadVendorBillAttachments($bill)
    {
        $record = is_numeric($bill) ? $this->apBillById($bill) : $this->apBillByNumber($bill);
        abort_unless(!empty($record), 404);

        request()->validate([
            'attachments.*' => 'required|file|max:10240',
        ]);

        $this->storeDocumentAttachments('purchase_document', $record['id'], request()->file('attachments', []));

        return redirect()
            ->route('erp.payables.vendor-bills.view', $record['bill_no'])
            ->with('status', 'Attachment uploaded.');
    }

    public function vendorBillPrint($bill)
    {
        $record = $this->apBillPrintData($bill);
        abort_unless(!empty($record['bill']), 404);

        return view('erp.payables.vendor-bill-print', $record);
    }

    public function vendorBill2307Print($bill)
    {
        $record = $this->apBill2307PrintData($bill);
        abort_unless(!empty($record['form']), 404);

        $record['printLayout'] = $this->vendorBill2307PrintLayout();
        $record['printLayoutDefaults'] = $this->defaultVendorBill2307PrintLayout();
        $record['canSaveLayout'] = $this->canSaveCheckPrintLayout();

        return view('erp.payables.vendor-bill-2307-print', $record);
    }

    public function saveVendorBill2307Layout()
    {
        abort_unless($this->canSaveCheckPrintLayout(), 403, 'Only an Administrator may save the BIR 2307 print layout.');

        $defaults = $this->defaultVendorBill2307PrintLayout();
        $submitted = request()->input('layout', []);
        request()->validate(['layout' => 'present|array']);
        abort_unless(empty(array_diff(array_keys($submitted), array_keys($defaults))), 422, 'The print layout contains an unknown field.');

        $rules = ['layout' => 'present|array'];
        foreach (array_keys($submitted) as $field) {
            $rules['layout.'.$field] = 'required|array';
            $rules['layout.'.$field.'.top'] = 'required|numeric|between:-50,1298';
            $rules['layout.'.$field.'.left'] = 'required|numeric|between:-100,916';
            $rules['layout.'.$field.'.width'] = 'required|numeric|between:4,816';
            $rules['layout.'.$field.'.font_size'] = 'required|numeric|between:6,24';
        }

        $validated = request()->validate($rules);
        $layout = $this->sanitizeVendorBill2307PrintLayout($validated['layout']);
        abort_unless(Schema::hasTable('print_layouts'), 503, 'Print layout storage is not available. Run the database migrations first.');

        DB::table('print_layouts')->updateOrInsert(
            [
                'company_id' => $this->defaultCompanyId(),
                'document_type' => 'bir_2307',
                'layout_name' => 'Default',
            ],
            [
                'paper_size' => 'letter',
                'paper_width' => 816,
                'paper_height' => 1056,
                'template_url' => asset('images/bir_2307_bg.png'),
                'print_template' => true,
                'positions' => json_encode($layout),
                'field_styles' => json_encode([]),
                'custom_fields' => json_encode([]),
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'BIR 2307 print layout saved.',
            'layout' => $layout,
        ]);
    }

    public function apPaymentCreate()
    {
        $selectedBill = request('bill') ? $this->apBillByNumber(request('bill')) : null;
        $selectedPaymentMethod = in_array(request('method'), ['Cheque', 'Bank', 'Online Transfer'], true)
            ? request('method')
            : 'Bank';

        return view('erp.payables.payment-create', [
            'suppliers' => $this->vendorRows(),
            'openBills' => $this->openApBills(),
            'selectedBill' => $selectedBill,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'nextPaymentNo' => $this->nextDocumentNumber('payment_voucher', 'PV-', 5, false),
            'bankAccounts' => $this->bankAccounts(),
        ]);
    }

    public function apPaymentReview()
    {
        request()->merge([
            'amount' => str_replace(',', '', (string) request('amount')),
        ]);

        $data = request()->validate([
            'payment_no' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'bill_no' => 'required|string|max:100',
            'shipment_job_id' => 'nullable|string|max:100',
            'supplier_id' => 'required|string|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'payee_name' => 'required|string|max:255',
            'payment_method' => 'required|in:Cheque,Bank Transfer,Online Transfer,Bank',
            'bank_account' => 'required|string|max:255',
            'check_no' => 'required_if:payment_method,Cheque|nullable|string|max:100',
            'currency' => 'required|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'amount' => 'required|numeric|min:0.01',
            'attachments.*' => 'file|max:10240',
        ]);

        $companyId = $this->defaultCompanyId();
        $bill = $this->apBillRecordByNumber($data['bill_no']);
        if (!$bill) {
            throw \Illuminate\Validation\ValidationException::withMessages(['bill_no' => 'The selected supplier bill was not found for this company.']);
        }

        $amount = $this->moneyToFloat($data['amount']);
        if (abs($amount - round($amount, 2)) > 0.00001) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => 'Payment amounts may have no more than two decimal places.',
            ]);
        }
        $balance = max(0, (float) $bill->grand_total - (float) $bill->paid_total);
        if ($amount > $balance + 0.0049) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => 'Payment cannot exceed the outstanding bill balance of '.number_format($balance, 2).'.',
            ]);
        }

        $openPaymentExists = DB::table('payments')
            ->where('company_id', $companyId)
            ->where('bill_no', $bill->document_no)
            ->whereNotIn('status', ['released', 'cancelled', 'voided'])
            ->exists();
        if ($openPaymentExists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'bill_no' => 'This bill already has an unreleased payment. Approve, release, or void it before preparing another.',
            ]);
        }

        $bank = DB::table('bank_accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($data) {
                $query->where('account_no', $data['bank_account'])
                    ->orWhere('account_name', $data['bank_account']);
            })
            ->first();
        if (!$bank) {
            throw \Illuminate\Validation\ValidationException::withMessages(['bank_account' => 'Select an active company bank account.']);
        }
        if (strtoupper($bank->currency) !== strtoupper($data['currency'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'currency' => 'Payment currency must match the selected bank account currency ('.$bank->currency.').',
            ]);
        }
        if (strtoupper($bill->currency ?: 'PHP') !== strtoupper($data['currency'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'currency' => 'Payment currency must match the supplier bill currency ('.$bill->currency.').',
            ]);
        }

        $data['bank_account_id'] = $bank->id;
        $data['check_no'] = isset($data['check_no']) ? trim($data['check_no']) : null;
        $data['approval_status'] = 'For Approval';
        $payment = $this->saveApPayment($data);

        return view('erp.payables.payment-review', [
            'payment' => $payment,
            'attachments' => $this->documentAttachments('payment', $payment['id']),
        ]);
    }

    public function paymentApprovals()
    {
        $approvals = $this->apPaymentRows();

        return view('erp.payables.approvals', compact('approvals'));
    }

    public function approveApPayment($payment)
    {
        $this->authorizeAccountingPosting('payables', 'view');
        $companyId = $this->defaultCompanyId();

        DB::transaction(function () use ($payment, $companyId) {
            $record = DB::table('payments')->where('company_id', $companyId)
                ->where('id', $payment)->lockForUpdate()->first();
            abort_unless($record, 404);

            if (!in_array(strtolower((string) $record->approval_status), ['for approval', 'pending'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'approval_status' => 'Only a payment awaiting approval can be approved.',
                ]);
            }
            if ($record->payment_date > date('Y-m-d')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payment_date' => 'A post-dated payment cannot be released before '.date('M d, Y', strtotime($record->payment_date)).'.',
                ]);
            }

            DB::table('payments')->where('id', $record->id)->update([
                'status' => 'approved',
                'approval_status' => 'Approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

            app(ActivityLogger::class)->log('approved', 'payment', $record->id, [
                'approval_status' => $record->approval_status,
            ], [
                'approval_status' => 'Approved',
                'approved_by' => auth()->id(),
            ], $companyId);
        });

        return redirect()->route('erp.payables.approvals')->with('status', 'Payment approved. It has not been posted or released yet.');
    }

    public function releaseApPayment($payment)
    {
        $this->authorizeAccountingPosting('payables', 'release');
        $companyId = $this->defaultCompanyId();

        DB::transaction(function () use ($payment, $companyId) {
            $record = DB::table('payments')->where('company_id', $companyId)
                ->where('id', $payment)->lockForUpdate()->first();
            abort_unless($record, 404);

            if (strtolower((string) $record->approval_status) !== 'approved') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'approval_status' => 'The payment must be independently approved before release.',
                ]);
            }
            if ($record->created_by && (int) $record->created_by === (int) auth()->id()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'approval_status' => 'Maker-checker control: the preparer cannot release this payment.',
                ]);
            }

            $bill = $this->apBillRecordByNumber($record->bill_no);
            abort_unless($bill, 422, 'The supplier bill linked to this payment no longer exists.');
            $balance = max(0, (float) $bill->grand_total - (float) $bill->paid_total);
            if ((float) $record->amount > $balance + 0.0049) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Release blocked because the payment exceeds the current bill balance of '.number_format($balance, 2).'.',
                ]);
            }

            $issuance = null;
            if (strcasecmp((string) $record->payment_method, 'Cheque') === 0) {
                $issuance = $this->activeCheckIssuance($record->id, true);
                if (!$issuance || (int) $issuance->print_count < 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'check' => 'The approved check must be printed and recorded before release.',
                    ]);
                }
            }

            DB::table('payments')->where('id', $record->id)->update([
                'status' => 'released',
                'approval_status' => 'Released',
                'released_by' => auth()->id(),
                'released_at' => now(),
                'updated_at' => now(),
            ]);

            if ($issuance) {
                DB::table('check_issuances')->where('id', $issuance->id)->update([
                    'status' => 'released',
                    'released_by' => auth()->id(),
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            app(PostingService::class)->postSupplierPayment($record->id);
            $this->applyReleasedPaymentToBill($record, $bill);

            app(ActivityLogger::class)->log('released', 'payment', $record->id, [
                'approval_status' => $record->approval_status,
            ], [
                'approval_status' => 'Released',
                'released_by' => auth()->id(),
            ], $companyId);
        });

        return redirect()->route('erp.payables.approvals')->with('status', 'Payment released and posted to the ledger.');
    }

    public function voidApPayment($payment)
    {
        $this->authorizeAccountingPosting('payables', 'delete');
        $data = request()->validate(['reason' => 'required|string|min:5|max:1000']);
        $companyId = $this->defaultCompanyId();

        DB::transaction(function () use ($payment, $companyId, $data) {
            $record = DB::table('payments')->where('company_id', $companyId)
                ->where('id', $payment)->lockForUpdate()->first();
            abort_unless($record, 404);
            if (in_array(strtolower((string) $record->status), ['voided', 'cancelled'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['reason' => 'This payment is already voided.']);
            }

            $wasReleased = strtolower((string) $record->approval_status) === 'released';
            $activeCheck = $this->activeCheckIssuance($record->id);
            if ($activeCheck && $activeCheck->status === 'cleared') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reason' => 'A cleared check cannot be voided through the payment screen. Process a documented bank correction instead.',
                ]);
            }
            if ($wasReleased && $record->posting_status === 'posted') {
                app(PostingService::class)->reverseSource('payment', $record->id, $data['reason']);
                $this->reverseReleasedPaymentFromBill($record);
            }

            DB::table('payments')->where('id', $record->id)->update([
                'status' => 'voided',
                'approval_status' => 'Voided',
                'posting_status' => $wasReleased ? 'reversed' : $record->posting_status,
                'voided_by' => auth()->id(),
                'voided_at' => now(),
                'void_reason' => $data['reason'],
                'updated_at' => now(),
            ]);

            $issuance = $activeCheck;
            if ($issuance) {
                DB::table('check_issuances')->where('id', $issuance->id)->update([
                    'status' => 'voided',
                    'voided_by' => auth()->id(),
                    'voided_at' => now(),
                    'void_reason' => $data['reason'],
                    'updated_at' => now(),
                ]);
            }

            app(ActivityLogger::class)->log('voided', 'payment', $record->id, [
                'approval_status' => $record->approval_status,
            ], [
                'approval_status' => 'Voided',
                'reason' => $data['reason'],
            ], $companyId);
        });

        return redirect()->route('erp.payables.approvals')->with('status', 'Payment and associated check were voided.');
    }

    public function replaceCheck($check)
    {
        $this->authorizeAccountingPosting('payables', 'release');
        $data = request()->validate([
            'new_check_no' => 'required|string|max:100',
            'reason' => 'required|string|min:5|max:1000',
        ]);
        $companyId = $this->defaultCompanyId();

        DB::transaction(function () use ($check, $data, $companyId) {
            $issuance = DB::table('check_issuances')->where('company_id', $companyId)
                ->where('id', $check)->lockForUpdate()->first();
            abort_unless($issuance, 404);
            $payment = DB::table('payments')->where('company_id', $companyId)
                ->where('id', $issuance->payment_id)->lockForUpdate()->first();
            abort_unless($payment, 404);

            if ($payment->posting_status === 'posted' || strtolower((string) $payment->approval_status) === 'released') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'new_check_no' => 'A released check cannot be replaced in place. Void the payment and prepare a new replacement payment.',
                ]);
            }
            if (in_array($issuance->status, ['replaced', 'cleared'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['new_check_no' => 'This check can no longer be replaced.']);
            }

            DB::table('check_issuances')->where('id', $issuance->id)->update([
                'status' => 'voided',
                'voided_by' => auth()->id(),
                'voided_at' => now(),
                'void_reason' => $data['reason'],
                'updated_at' => now(),
            ]);
            DB::table('payments')->where('id', $payment->id)->update([
                'check_no' => trim($data['new_check_no']),
                'updated_at' => now(),
            ]);
            $replacement = $this->createCheckIssuanceForPayment($payment->id, $issuance->id);

            app(ActivityLogger::class)->log('replaced', 'check_issuance', $issuance->id, [
                'check_no' => $issuance->check_no,
            ], [
                'check_no' => $replacement->check_no,
                'replacement_id' => $replacement->id,
                'reason' => $data['reason'],
            ], $companyId);
        });

        return redirect()->route('erp.payables.check-register')->with('status', 'Spoiled check replaced with the new check number.');
    }

    public function clearCheck($check)
    {
        $this->authorizeAccountingPosting('banking', 'edit');
        $data = request()->validate([
            'cleared_at' => 'required|date|before_or_equal:today',
            'clearing_reference' => 'required|string|max:255',
        ]);
        $companyId = $this->defaultCompanyId();
        $issuance = DB::table('check_issuances')->where('company_id', $companyId)->where('id', $check)->first();
        abort_unless($issuance, 404);
        if ($issuance->status !== 'released') {
            throw \Illuminate\Validation\ValidationException::withMessages(['cleared_at' => 'Only a released check may be marked cleared.']);
        }
        if ($data['cleared_at'] < $issuance->check_date) {
            throw \Illuminate\Validation\ValidationException::withMessages(['cleared_at' => 'Clearing date cannot be earlier than the check date.']);
        }

        DB::table('check_issuances')->where('id', $issuance->id)->update([
            'status' => 'cleared',
            'cleared_at' => $data['cleared_at'].' 00:00:00',
            'cleared_by' => auth()->id(),
            'clearing_reference' => $data['clearing_reference'],
            'updated_at' => now(),
        ]);
        app(ActivityLogger::class)->log('cleared', 'check_issuance', $issuance->id, ['status' => 'released'], [
            'status' => 'cleared',
            'clearing_reference' => $data['clearing_reference'],
        ], $companyId);

        return redirect()->route('erp.payables.check-register')->with('status', 'Check marked as cleared.');
    }

    public function checkRegister()
    {
        $checks = DB::table('check_issuances')
            ->leftJoin('payments', 'payments.id', '=', 'check_issuances.payment_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'check_issuances.bank_account_id')
            ->select(
                'check_issuances.*', 'payments.payment_no', 'payments.voucher_no',
                'payments.approval_status', 'bank_accounts.bank_name', 'bank_accounts.account_name'
            )
            ->where('check_issuances.company_id', $this->defaultCompanyId())
            ->orderBy('check_issuances.check_date', 'desc')
            ->orderBy('check_issuances.id', 'desc')
            ->get();

        return view('erp.payables.check-register', compact('checks'));
    }

    public function checkVoucherPrintLayout()
    {
        return view('erp.payables.check-voucher-layout', [
            'layout' => $this->checkVoucherPrintLayoutPayload(),
            'sample' => $this->paymentPrintData(request('payment')),
            'fields' => $this->checkVoucherPrintFields(),
        ]);
    }

    public function saveCheckVoucherPrintLayout()
    {
        if (request()->hasFile('template_file') && request()->file('template_file')->isValid()) {
            $path = request()->file('template_file')->store('erp/templates', 'public');
            request()->merge([
                'template_url' => asset('storage/'.$path),
                'print_template' => '1',
            ]);
        }

        $layout = $this->checkVoucherPrintLayoutPayload(true);

        if (Schema::hasTable('print_layouts')) {
            DB::table('print_layouts')->updateOrInsert(
                [
                    'company_id' => null,
                    'document_type' => 'check_voucher',
                    'layout_name' => 'Default',
                ],
                [
                    'paper_size' => $layout['settings']['paper_size'],
                    'paper_width' => $layout['settings']['paper_width'],
                    'paper_height' => $layout['settings']['paper_height'],
                    'template_url' => $layout['settings']['template_url'],
                    'print_template' => $layout['settings']['print_template'],
                    'positions' => json_encode($layout['positions']),
                    'field_styles' => json_encode($layout['field_styles']),
                    'custom_fields' => json_encode($layout['custom_fields']),
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        Storage::put('erp/check-voucher-print-layout.json', json_encode($layout, JSON_PRETTY_PRINT));

        return redirect()
            ->route('erp.payables.check-voucher.layout')
            ->with('status', 'Check voucher print layout saved.');
    }

    public function checkVoucherPreview($payment = null)
    {
        if ($payment) {
            $record = $this->resolveLegacyPaymentBankAccount($this->paymentRecordForCompany($payment));
            abort_unless($record, 404);
            $errors = $this->checkVoucherPreparationErrors($record);
            if (!empty($errors)) {
                return redirect()->route('erp.payables.approvals')->withErrors($errors);
            }
        }

        return view('erp.payables.check-voucher', [
            'voucher' => $this->paymentPrintData($payment),
            'layout' => $this->checkVoucherPrintLayoutPayload(),
            'fields' => $this->checkVoucherPrintFields(),
            'voucherPrinted' => $payment ? $this->hasRecordedCheckVoucherPrint($payment) : false,
        ]);
    }

    public function recordCheckVoucherPrint($payment)
    {
        $record = $this->resolveLegacyPaymentBankAccount($this->paymentRecordForCompany($payment));
        abort_unless($record, 404);
        $errors = $this->checkVoucherPreparationErrors($record);
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        $voucher = $this->paymentPrintData($payment);
        $layout = $this->checkVoucherPrintLayoutPayload();
        $payloadHash = hash('sha256', json_encode([$voucher, $layout]));

        $eventId = DB::table('check_voucher_print_events')->insertGetId([
            'company_id' => $record->company_id,
            'payment_id' => $record->id,
            'payload_snapshot' => json_encode($voucher),
            'layout_snapshot' => json_encode($layout),
            'payload_hash' => $payloadHash,
            'printed_by' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);

        app(ActivityLogger::class)->log('print_requested', 'check_voucher', $record->id, [], [
            'payment_no' => $record->payment_no,
            'voucher_no' => $record->voucher_no,
            'event_id' => $eventId,
            'payload_hash' => $payloadHash,
        ], $record->company_id);

        return response()->json([
            'status' => 'Check voucher print request recorded.',
            'event' => ['id' => $eventId],
        ]);
    }

    public function checkVoucherForBill($bill)
    {
        $billNo = is_numeric($bill) ? DB::table('purchase_documents')->where('id', $bill)->value('document_no') : $bill;
        $paymentId = $this->latestPaymentIdForBill($billNo);

        if (!$paymentId) {
            return redirect()
                ->route('erp.payables.payments.create', ['bill' => $billNo, 'method' => 'Cheque'])
                ->with('status', 'Create or release a payment first before printing the check voucher.');
        }

        return redirect()->route('erp.payables.check-voucher', $paymentId);
    }

    public function checkPrint($payment = null)
    {
        if (!$payment) {
            return redirect()->route('erp.payables.approvals')->withErrors([
                'check' => 'Select a specific approved cheque payment before opening the check printer.',
            ]);
        }

        $paymentRecord = $this->paymentRecordForCompany($payment);
        abort_unless($paymentRecord, 404);
        $paymentRecord = $this->resolveLegacyPaymentBankAccount($paymentRecord);
        $errors = $this->checkPrintEligibilityErrors($paymentRecord);
        if (!empty($errors)) {
            return redirect()->route('erp.payables.approvals')->withErrors(['check' => reset($errors)]);
        }

        $issuance = $this->activeCheckIssuance($paymentRecord->id);
        if (!$issuance) {
            $issuance = $this->createCheckIssuanceForPayment($paymentRecord->id);
        }
        if (trim((string) $issuance->amount_words) === '' || trim((string) $issuance->payee_name) === '') {
            $payee = trim((string) ($paymentRecord->payee_snapshot ?: $paymentRecord->supplier_name));
            $amountWords = $this->amountToWords($paymentRecord->amount, $paymentRecord->currency);
            DB::table('check_issuances')->where('id', $issuance->id)->update([
                'payee_name' => $payee,
                'amount_words' => $amountWords,
                'updated_at' => now(),
            ]);
            DB::table('payments')->where('id', $paymentRecord->id)->update([
                'payee_snapshot' => $payee,
                'amount_words_snapshot' => $amountWords,
                'updated_at' => now(),
            ]);
            $issuance = DB::table('check_issuances')->where('id', $issuance->id)->first();
        }
        if (!in_array($issuance->status, ['assigned', 'printed', 'released'], true)) {
            return redirect()->route('erp.payables.check-register')->withErrors([
                'check' => 'A '.strtolower($issuance->status).' check cannot be printed or reprinted.',
            ]);
        }
        $profile = $this->bankCheckLayoutProfile($paymentRecord->bank_account_id, request('profile'));

        return view('erp.payables.check-print', [
            'voucher' => $this->paymentPrintData($payment),
            'checkLayout' => $this->bankCheckPrintLayout($paymentRecord->bank_account_id, request('profile')),
            'checkLayoutDefaults' => $this->defaultBankCheckPrintLayout(),
            'checkProfile' => $profile,
            'checkProfiles' => $this->bankCheckLayoutProfiles($paymentRecord->bank_account_id),
            'checkIssuance' => $issuance,
            'canSaveLayout' => $this->canSaveCheckPrintLayout(),
            'canPrintCheck' => $this->canOperateChecks(),
        ]);
    }

    public function updateCheckPayee($payment)
    {
        abort_unless($this->canOperateChecks(), 403, 'Only Finance or an Administrator may edit a check payee.');
        $data = request()->validate([
            'payee_name' => 'required|string|max:255',
        ]);
        $companyId = $this->defaultCompanyId();
        $payee = trim($data['payee_name']);

        DB::transaction(function () use ($payment, $companyId, $payee) {
            $record = DB::table('payments')
                ->where('company_id', $companyId)
                ->where('id', $payment)
                ->lockForUpdate()
                ->first();
            abort_unless($record, 404);

            $issuance = $this->activeCheckIssuance($record->id, true);
            abort_unless($issuance, 422, 'No active check issuance exists for this payment.');
            if (strtolower((string) $record->approval_status) !== 'approved') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payee_name' => 'The payment must be approved before editing the check payee.',
                ]);
            }
            if (!in_array($issuance->status, ['assigned', 'printed'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payee_name' => 'The payee cannot be changed after the check is released or closed.',
                ]);
            }

            DB::table('payments')->where('id', $record->id)->update([
                'payee_snapshot' => $payee,
                'updated_at' => now(),
            ]);
            DB::table('check_issuances')->where('id', $issuance->id)->update([
                'payee_name' => $payee,
                'updated_at' => now(),
            ]);

            app(ActivityLogger::class)->log('payee_updated', 'check_issuance', $issuance->id, [
                'payee_name' => $issuance->payee_name,
            ], [
                'payee_name' => $payee,
                'payment_id' => $record->id,
            ], $companyId);
        });

        return redirect()
            ->route('erp.payables.check-print', $payment)
            ->with('status', 'Check payee updated.');
    }

    public function saveCheckPrintLayout()
    {
        abort_unless($this->canSaveCheckPrintLayout(), 403, 'Only an Administrator may save the check print layout.');

        $defaults = $this->defaultBankCheckPrintLayout();
        $rules = [
            'layout' => 'required|array',
            'layout.font_sizes' => 'required|array',
            'bank_account_id' => 'required|integer',
            'profile_name' => 'nullable|string|max:100',
            'printer_name' => 'nullable|string|max:255',
            'paper_width_mm' => 'required|numeric|between:150,260',
            'paper_height_mm' => 'required|numeric|between:50,150',
            'feed_orientation' => 'required|in:top,left,right',
            'offset_x_mm' => 'required|numeric|between:-50,50',
            'offset_y_mm' => 'required|numeric|between:-50,50',
        ];

        foreach (array_keys($defaults['font_sizes']) as $field) {
            $rules['layout.'.$field] = 'required|array';
            $rules['layout.'.$field.'.top'] = 'required|numeric|between:-20,120';
            $rules['layout.'.$field.'.left'] = 'required|numeric|between:-50,260';
            $rules['layout.font_sizes.'.$field] = 'required|numeric|between:8,24';
        }

        $validated = request()->validate($rules);
        $layout = $this->sanitizeBankCheckPrintLayout($validated['layout']);
        $companyId = $this->defaultCompanyId();
        $bank = DB::table('bank_accounts')->where('company_id', $companyId)
            ->where('id', $validated['bank_account_id'])->first();
        abort_unless($bank, 422, 'The selected bank account is invalid for this company.');

        $profileName = trim((string) ($validated['profile_name'] ?? '')) ?: 'Default';
        $profileId = DB::transaction(function () use ($validated, $layout, $companyId, $profileName) {
            $current = DB::table('check_layout_profiles')
                ->where('company_id', $companyId)
                ->where('bank_account_id', $validated['bank_account_id'])
                ->where('profile_name', $profileName)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            $version = ((int) DB::table('check_layout_profiles')
                ->where('company_id', $companyId)
                ->where('bank_account_id', $validated['bank_account_id'])
                ->where('profile_name', $profileName)
                ->max('version')) + 1;

            DB::table('check_layout_profiles')
                ->where('company_id', $companyId)
                ->where('bank_account_id', $validated['bank_account_id'])
                ->where('profile_name', $profileName)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $id = DB::table('check_layout_profiles')->insertGetId([
                'company_id' => $companyId,
                'bank_account_id' => $validated['bank_account_id'],
                'profile_name' => $profileName,
                'printer_name' => $validated['printer_name'] ?? null,
                'paper_width_mm' => $validated['paper_width_mm'],
                'paper_height_mm' => $validated['paper_height_mm'],
                'feed_orientation' => $validated['feed_orientation'],
                'offset_x_mm' => $validated['offset_x_mm'],
                'offset_y_mm' => $validated['offset_y_mm'],
                'positions' => json_encode(array_diff_key($layout, ['font_sizes' => true])),
                'field_styles' => json_encode(['font_sizes' => $layout['font_sizes']]),
                'version' => $version,
                'is_active' => true,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            app(ActivityLogger::class)->log('versioned', 'check_layout_profile', $id, $current ? (array) $current : [], [
                'profile_name' => $profileName,
                'version' => $version,
                'bank_account_id' => $validated['bank_account_id'],
            ], $companyId);

            return $id;
        });

        return response()->json([
            'status' => 'Layout saved as a new bank/printer profile version.',
            'layout' => $layout,
            'profile' => $this->checkLayoutProfileArray(DB::table('check_layout_profiles')->where('id', $profileId)->first()),
        ]);
    }

    public function recordCheckPrint($payment)
    {
        abort_unless($this->canOperateChecks(), 403, 'Only Finance or an Administrator may print checks.');
        $data = request()->validate(['reason' => 'nullable|string|max:1000']);
        $companyId = $this->defaultCompanyId();

        $event = DB::transaction(function () use ($payment, $data, $companyId) {
            $record = DB::table('payments')->where('company_id', $companyId)
                ->where('id', $payment)->lockForUpdate()->first();
            abort_unless($record, 404);
            $errors = $this->checkPrintEligibilityErrors($record);
            if (!empty($errors)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['check' => reset($errors)]);
            }

            $issuance = $this->activeCheckIssuance($record->id, true);
            abort_unless($issuance, 422, 'No active check issuance exists for this payment.');
            if (!in_array($issuance->status, ['assigned', 'printed', 'released'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'check' => 'A '.strtolower($issuance->status).' check cannot be printed or reprinted.',
                ]);
            }
            $isReprint = (int) $issuance->print_count > 0;
            $reason = trim((string) ($data['reason'] ?? ''));
            if ($isReprint && strlen($reason) < 5) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reason' => 'A reason of at least five characters is required for every reprint.',
                ]);
            }

            $profile = $this->bankCheckLayoutProfile($record->bank_account_id, request('profile_id'));
            $layout = $this->bankCheckPrintLayout($record->bank_account_id, request('profile_id'));
            $payload = [
                'payment_id' => $record->id,
                'payment_no' => $record->payment_no,
                'voucher_no' => $record->voucher_no,
                'check_no' => $issuance->check_no,
                'check_date' => $issuance->check_date,
                'payee' => $issuance->payee_name,
                'currency' => $issuance->currency,
                'amount' => (float) $issuance->amount,
                'amount_words' => $issuance->amount_words,
            ];
            $layoutSnapshot = ['profile' => $profile, 'layout' => $layout];
            $hash = hash('sha256', json_encode([$payload, $layoutSnapshot]));

            $eventId = DB::table('check_print_events')->insertGetId([
                'company_id' => $companyId,
                'check_issuance_id' => $issuance->id,
                'payment_id' => $record->id,
                'layout_profile_id' => $profile['id'],
                'event_type' => $isReprint ? 'reprint_requested' : 'original_print_requested',
                'reason' => $reason ?: null,
                'layout_snapshot' => json_encode($layoutSnapshot),
                'payload_snapshot' => json_encode($payload),
                'payload_hash' => $hash,
                'printed_by' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'created_at' => now(),
            ]);

            DB::table('check_issuances')->where('id', $issuance->id)->update([
                'status' => in_array($issuance->status, ['released', 'cleared'], true) ? $issuance->status : 'printed',
                'print_count' => (int) $issuance->print_count + 1,
                'first_printed_at' => $issuance->first_printed_at ?: now(),
                'first_printed_by' => $issuance->first_printed_by ?: auth()->id(),
                'last_printed_at' => now(),
                'last_printed_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            app(ActivityLogger::class)->log($isReprint ? 'reprint_requested' : 'print_requested', 'check_issuance', $issuance->id, [], [
                'check_no' => $issuance->check_no,
                'event_id' => $eventId,
                'reason' => $reason ?: null,
                'payload_hash' => $hash,
            ], $companyId);

            return ['id' => $eventId, 'is_reprint' => $isReprint, 'print_count' => (int) $issuance->print_count + 1];
        });

        return response()->json(['status' => 'Print request recorded.', 'event' => $event]);
    }

    public function apAging()
    {
        $rows = $this->apAgingRows();

        return view('erp.payables.aging', compact('rows'));
    }

    private function apRegisterRows()
    {
        if (!Schema::hasTable('purchase_documents')) {
            return [];
        }

        return DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->select(
                'purchase_documents.id',
                'purchase_documents.document_no',
                'purchase_documents.shipment_job_id',
                'purchase_documents.client_customer_id',
                'purchase_documents.currency',
                'purchase_documents.grand_total',
                'purchase_documents.paid_total',
                'purchase_documents.status',
                'purchase_documents.approval_status',
                'purchase_documents.document_date',
                'suppliers.name as supplier_name'
            )
            ->orderBy('purchase_documents.document_date', 'desc')
            ->orderBy('purchase_documents.id', 'desc')
            ->get()
            ->map(function ($bill) {
                $balance = max(0, (float) $bill->grand_total - (float) $bill->paid_total);
                return [
                    $bill->document_no,
                    $bill->supplier_name ?: '-',
                    $bill->shipment_job_id ?: '-',
                    $bill->client_customer_id ?: '-',
                    $bill->currency ?: 'PHP',
                    number_format($balance ?: (float) $bill->grand_total, 2),
                    $bill->approval_status ?: ucwords(str_replace('_', ' ', $bill->status)),
                    $bill->document_date,
                    'id' => $bill->id,
                ];
            })
            ->all();
    }

    private function openApTotalByCurrency($currency)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return 0.0;
        }

        return (float) DB::table('purchase_documents')
            ->where('currency', $currency)
            ->whereNotIn('status', ['paid', 'cancelled', 'voided'])
            ->select(DB::raw('COALESCE(SUM(grand_total - paid_total), 0) as balance'))
            ->value('balance');
    }

    private function apBillTaxSplit(array $data, $grossAmount)
    {
        $amounts = (array) ($data['expense_amount'] ?? []);
        $taxes = (array) ($data['expense_tax'] ?? []);

        $vatable = 0.0;
        $zeroRated = 0.0;
        $vatExempt = 0.0;
        $inputVat = 0.0;
        $hasLines = false;

        foreach ($amounts as $index => $amount) {
            $lineAmount = $this->moneyToFloat($amount);
            if ($lineAmount <= 0) {
                continue;
            }
            $hasLines = true;
            $taxCategory = strtoupper(trim((string) ($taxes[$index] ?? '')));

            if (in_array($taxCategory, ['VAT12', '12%'], true)) {
                $vatable += $lineAmount;
                $inputVat += $lineAmount * 0.12;
            } elseif ($taxCategory === 'ZERO') {
                $zeroRated += $lineAmount;
            } elseif ($taxCategory === 'EXEMPT') {
                $vatExempt += $lineAmount;
            } else {
                $vatable += $lineAmount;
            }
        }

        if (!$hasLines) {
            $vatable = $grossAmount;
        }

        return [
            'vatable_amount' => round($vatable, 4),
            'zero_rated_amount' => round($zeroRated, 4),
            'vat_exempt_amount' => round($vatExempt, 4),
            'input_vat' => round($inputVat, 4),
        ];
    }

    private function apBillLineWithholdingTotals(array $data, $grossAmount, array $taxSplit)
    {
        $accounts = (array) ($data['expense_account'] ?? []);
        $taxes = (array) ($data['expense_tax'] ?? []);
        $amounts = (array) ($data['expense_amount'] ?? []);
        $rates = (array) ($data['expense_withholding_rate'] ?? []);
        $withholdingAmount = 0.0;
        $withholdingBase = 0.0;
        $hasLineWithholding = false;

        foreach ($amounts as $index => $amount) {
            $lineAmount = $this->moneyToFloat($amount);
            if ($lineAmount <= 0) {
                continue;
            }

            $profile = $this->expenseAccountWithholdingProfile($accounts[$index] ?? null);
            $rate = trim((string) ($rates[$index] ?? '')) !== ''
                ? $this->moneyToFloat($rates[$index])
                : $profile['rate'];

            if ($rate <= 0) {
                continue;
            }

            $hasLineWithholding = true;
            $base = $this->vendorBillLineWithholdingBase($lineAmount, $taxes[$index] ?? null);
            $withholdingBase += $base;
            $withholdingAmount += $base * ($rate / 100);
        }

        if ($hasLineWithholding) {
            return [
                'rate' => $withholdingBase > 0 ? ($withholdingAmount / $withholdingBase) * 100 : 0,
                'amount' => $withholdingAmount,
            ];
        }

        $rate = $this->moneyToFloat($data['withholding_tax_rate'] ?? 0);
        $amount = $this->moneyToFloat($data['withholding_tax_amount'] ?? 0);
        if ($amount <= 0 && $rate > 0) {
            // BIR EWT is computed on the income payment net of VAT, not the VAT-inclusive gross.
            $base = max(0, $grossAmount - $taxSplit['input_vat']);
            $amount = $base * ($rate / 100);
        }

        return ['rate' => $rate, 'amount' => $amount];
    }

    private function vendorBillLineWithholdingBase($amount, $taxCategory)
    {
        return $amount;
    }

    private function expenseAccountWithholdingProfile($accountLabel)
    {
        $accountId = $this->expenseAccountIdFromLabel($accountLabel);
        $account = $accountId
            ? DB::table('accounts')->where('id', $accountId)->first(['name', 'atc_code', 'tax_code'])
            : null;

        return [
            'name' => $account ? $account->name : trim((string) $accountLabel),
            'atc_code' => $account ? strtoupper(trim((string) $account->atc_code)) : '',
            'rate' => $account ? $this->withholdingRatePercent($account->tax_code) : 0,
        ];
    }

    private function withholdingRatePercent($rate)
    {
        if ($rate === null || trim((string) $rate) === '') {
            return 0.0;
        }

        $numericRate = $this->moneyToFloat($rate);

        return abs($numericRate) <= 1 ? $numericRate * 100 : $numericRate;
    }

    private function saveApBill(array $data)
    {
        return DB::transaction(function () use ($data) {
            $companyId = $this->defaultCompanyId();
            $supplierId = $this->supplierIdForAp($companyId, $data);
            $originalDocumentNo = trim((string) ($data['original_bill_no'] ?? ''));
            $documentNo = $originalDocumentNo !== ''
                ? $originalDocumentNo
                : $this->nextDocumentNumber('vendor_bill', 'BILL-', 5);

            $lineTotal = 0.0;
            foreach ((array) ($data['expense_amount'] ?? []) as $amount) {
                $lineTotal += $this->moneyToFloat($amount);
            }

            $taxSplit = $this->apBillTaxSplit($data, $lineTotal);
            $grossAmount = $lineTotal > 0
                ? $lineTotal + $taxSplit['input_vat']
                : $this->moneyToFloat($data['gross_amount'] ?? 0);
            $withholding = $this->apBillLineWithholdingTotals($data, $grossAmount, $taxSplit);
            $whtRate = $withholding['rate'];
            $whtAmount = $withholding['amount'];
            $netAmount = max(0, $grossAmount - $whtAmount);
            $usdToPhpRate = $this->moneyToFloat($data['exchange_rate'] ?? 1) ?: 1;
            $exchangeRate = strtoupper($data['currency']) === 'USD' ? $usdToPhpRate : 1;
            $phpAmount = strtoupper($data['currency']) === 'USD' ? $grossAmount * $usdToPhpRate : $grossAmount;

            $existingQuery = DB::table('purchase_documents')->where('company_id', $companyId);
            if ($originalDocumentNo !== '') {
                $existingQuery->where('document_no', $originalDocumentNo);
            } else {
                $existingQuery->where('document_no', $documentNo);
            }
            $existingBill = $existingQuery->first();

            $payload = $this->tablePayload('purchase_documents', [
                'company_id' => $companyId,
                'branch_id' => DB::table('branches')->where('company_id', $companyId)->orderBy('id')->value('id'),
                'supplier_id' => $supplierId,
                'document_type' => 'vendor_bill',
                'transaction_type' => $data['transaction_type'],
                'ap_account' => $data['ap_account'] ?? null,
                'bill_received' => isset($data['bill_received']),
                'supplier_address' => $data['supplier_address'] ?? null,
                'client_customer_id' => $data['client_customer_id'] ?? null,
                'shipment_job_id' => $data['shipment_job_id'] ?? null,
                'document_no' => $documentNo,
                'reference_no' => $data['reference_no'] ?? null,
                'document_date' => $data['bill_date'],
                'due_date' => $data['due_date'] ?? null,
                'terms' => $data['terms'] ?? null,
                'currency' => strtoupper($data['currency']),
                'exchange_rate' => $exchangeRate,
                'usd_to_php_rate' => $usdToPhpRate,
                'gross_amount' => $grossAmount,
                'php_amount' => $phpAmount,
                'subtotal' => $lineTotal > 0 ? $lineTotal : max(0, $grossAmount - $taxSplit['input_vat']),
                'vatable_amount' => $taxSplit['vatable_amount'],
                'zero_rated_amount' => $taxSplit['zero_rated_amount'],
                'vat_exempt_amount' => $taxSplit['vat_exempt_amount'],
                'tax_total' => $taxSplit['input_vat'],
                'withholding_tax_rate' => $whtRate,
                'withholding_tax_amount' => $whtAmount,
                'grand_total' => $netAmount,
                'net_amount' => $netAmount,
                'paid_total' => $existingBill ? (float) $existingBill->paid_total : 0,
                'status' => $existingBill ? $existingBill->status : 'for_payment',
                'approval_status' => $existingBill ? ($existingBill->approval_status ?: 'For Approval') : 'For Approval',
                'remarks' => $data['memo'] ?? null,
                'memo' => $data['memo'] ?? null,
                'updated_at' => now(),
            ]);

            $duplicateId = DB::table('purchase_documents')
                ->where('company_id', $companyId)
                ->where('document_no', $documentNo)
                ->when($existingBill, function ($query) use ($existingBill) {
                    $query->where('id', '<>', $existingBill->id);
                })
                ->value('id');

            if ($duplicateId) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill_no' => 'Bill No. already exists. Please use a different document number.',
                ]);
            }

            if ($existingBill) {
                DB::table('purchase_documents')->where('id', $existingBill->id)->update($payload);
                $billId = $existingBill->id;
            } else {
                $payload['created_at'] = now();
                $billId = DB::table('purchase_documents')->insertGetId($payload);
            }

            $this->replaceApBillLines($billId, $data);
            $this->storeDocumentAttachments('purchase_document', $billId, request()->file('attachments', []));

            return $this->apBillById($billId);
        });
    }

    private function replaceApBillLines($billId, array $data)
    {
        $table = Schema::hasTable('vendor_bill_lines') ? 'vendor_bill_lines' : 'purchase_document_lines';
        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->where('purchase_document_id', $billId)->delete();

        $accounts = (array) ($data['expense_account'] ?? []);
        $taxes = (array) ($data['expense_tax'] ?? []);
        $atcCodes = (array) ($data['expense_atc_code'] ?? []);
        $withholdingRates = (array) ($data['expense_withholding_rate'] ?? []);
        $amounts = (array) ($data['expense_amount'] ?? []);
        $memos = (array) ($data['expense_memo'] ?? []);
        $jobs = (array) ($data['expense_customer_job'] ?? []);
        $billable = (array) ($data['expense_billable'] ?? []);
        $classes = (array) ($data['expense_class'] ?? []);

        foreach ($accounts as $index => $account) {
            $amount = $this->moneyToFloat($amounts[$index] ?? 0);
            if (trim((string) $account) === '' && $amount <= 0) {
                continue;
            }

            $profile = $this->expenseAccountWithholdingProfile($account);
            $atcCode = strtoupper(trim((string) ($atcCodes[$index] ?? $profile['atc_code'])));
            $withholdingRate = trim((string) ($withholdingRates[$index] ?? '')) !== ''
                ? $this->moneyToFloat($withholdingRates[$index])
                : $profile['rate'];
            $withholdingBase = $this->vendorBillLineWithholdingBase($amount, $taxes[$index] ?? null);
            $withholdingAmount = $withholdingBase * ($withholdingRate / 100);

            DB::table($table)->insert($this->tablePayload($table, [
                'purchase_document_id' => $billId,
                'account_id' => $this->expenseAccountIdFromLabel($account),
                'account_name' => $account,
                'tax_category' => $taxes[$index] ?? null,
                'atc_code' => $atcCode ?: null,
                'withholding_tax_rate' => $withholdingRate,
                'withholding_tax_amount' => $withholdingAmount,
                'amount' => $amount,
                'description' => $memos[$index] ?? ($account ?: 'Vendor bill expense'),
                'quantity' => 1,
                'unit_cost' => $amount,
                'line_total' => $amount,
                'memo' => $memos[$index] ?? null,
                'customer_job_id' => $jobs[$index] ?? null,
                'is_billable' => isset($billable[$index]) && $billable[$index] === 'Yes',
                'class_code' => $classes[$index] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function supplierIdForAp($companyId, array $data)
    {
        $supplierCode = trim((string) ($data['supplier_id'] ?? ''));
        $supplierName = trim((string) ($data['supplier_name'] ?? ''));

        $supplier = DB::table('suppliers')
            ->where('company_id', $companyId)
            ->where(function ($query) use ($supplierCode, $supplierName) {
                $query->where('code', $supplierCode);
                if ($supplierName !== '') {
                    $query->orWhere('name', $supplierName);
                }
            })
            ->first();

        if ($supplier) {
            return $supplier->id;
        }

        $code = $supplierCode ?: $this->nextDocumentNumber('vendor', 'VND-', 4);
        $supplierId = DB::table('suppliers')->insertGetId($this->tablePayload('suppliers', [
            'company_id' => $companyId,
            'code' => $code,
            'name' => $supplierName ?: $code,
            'address' => $data['supplier_address'] ?? null,
            'payment_terms' => $data['terms'] ?? 'COD',
            'payment_term_days' => $this->paymentTermDays($data['terms'] ?? 'COD'),
            'currency' => strtoupper($data['currency'] ?? 'PHP'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $this->syncDocumentNumberSeries('vendor', $code);

        return $supplierId;
    }

    private function openApBills()
    {
        if (!Schema::hasTable('purchase_documents')) {
            return [];
        }

        return DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->select(
                'purchase_documents.id',
                'purchase_documents.document_no',
                'purchase_documents.shipment_job_id',
                'purchase_documents.currency',
                'purchase_documents.grand_total',
                'purchase_documents.paid_total',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name'
            )
            ->where('purchase_documents.company_id', $this->defaultCompanyId())
            ->whereNotIn('purchase_documents.status', ['paid', 'cancelled', 'voided'])
            ->orderBy('purchase_documents.document_date', 'desc')
            ->get()
            ->map(function ($bill) {
                $balance = max(0, (float) $bill->grand_total - (float) $bill->paid_total);

                return [
                    'id' => $bill->id,
                    'bill_no' => $bill->document_no,
                    'supplier_id' => $bill->supplier_code,
                    'supplier_name' => $bill->supplier_name,
                    'shipment_job_id' => $bill->shipment_job_id,
                    'currency' => $bill->currency,
                    'balance' => $balance,
                    'balance_display' => ($bill->currency ?: 'PHP').' '.number_format($balance, 2),
                ];
            })
            ->all();
    }

    private function saveApPayment(array $data)
    {
        return DB::transaction(function () use ($data) {
            $companyId = $this->defaultCompanyId();
            $bill = $this->apBillRecordByNumber($data['bill_no']);
            $supplierId = $bill ? $bill->supplier_id : $this->supplierIdForAp($companyId, $data);
            $amount = $this->moneyToFloat($data['amount']);
            $currency = strtoupper($data['currency']);
            $exchangeRate = $currency === 'PHP' ? 1 : max(0.000001, $this->moneyToFloat($data['exchange_rate'] ?? 1));
            $paymentNo = trim((string) ($data['payment_no'] ?? ''));
            if ($paymentNo === '' || preg_match('/^PV-\d+$/i', $paymentNo)) {
                $paymentNo = $this->nextDocumentNumber('payment_voucher', 'PV-', 5);
            } elseif (DB::table('payments')->where('company_id', $companyId)->where('payment_no', $paymentNo)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payment_no' => 'Payment No. already exists. Use a different custom payment number.',
                ]);
            }
            $voucherNo = $this->nextDocumentNumber('check_voucher', 'CV-', 5);
            $bankAccountId = null;
            if (!empty($data['bank_account_id'])) {
                $bankAccountId = (int) $data['bank_account_id'];
            } elseif (!empty($data['bank_account']) && Schema::hasTable('bank_accounts')) {
                $bankAccountId = DB::table('bank_accounts')
                    ->where('company_id', $companyId)
                    ->where(function ($query) use ($data) {
                        $query->where('account_no', $data['bank_account'])
                            ->orWhere('account_name', $data['bank_account']);
                    })
                    ->value('id');
            }

            $paymentId = DB::table('payments')->insertGetId($this->tablePayload('payments', [
                'company_id' => $companyId,
                'supplier_id' => $supplierId,
                'shipment_job_id' => $data['shipment_job_id'] ?? ($bill->shipment_job_id ?? null),
                'bill_no' => $data['bill_no'],
                'payment_no' => $paymentNo,
                'voucher_no' => $voucherNo,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'bank_account_id' => $bankAccountId,
                'bank_account' => $data['bank_account'] ?? null,
                'check_no' => $data['check_no'] ?? null,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'amount' => $amount,
                'status' => 'for_approval',
                'approval_status' => 'For Approval',
                'created_by' => auth()->id(),
                'submitted_at' => now(),
                'payee_snapshot' => trim($data['payee_name']),
                'amount_words_snapshot' => $this->amountToWords($amount, $currency),
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            if ($bill && Schema::hasTable('payment_applications')) {
                DB::table('payment_applications')->insert($this->tablePayload('payment_applications', [
                    'payment_id' => $paymentId,
                    'purchase_document_id' => $bill->id,
                    'amount_applied' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            if (strcasecmp($data['payment_method'], 'Cheque') === 0) {
                $this->createCheckIssuanceForPayment($paymentId);
            }

            $this->storeDocumentAttachments('payment', $paymentId, request()->file('attachments', []));

            app(ActivityLogger::class)->log('submitted', 'payment', $paymentId, [], [
                'payment_no' => $paymentNo,
                'bill_no' => $data['bill_no'],
                'amount' => $amount,
                'status' => 'For Approval',
            ], $companyId);

            return $this->paymentArrayById($paymentId);
        });
    }

    private function createCheckIssuanceForPayment($paymentId, $replacementFor = null)
    {
        $payment = DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->select('payments.*', 'suppliers.name as supplier_name')
            ->where('payments.company_id', $this->defaultCompanyId())
            ->where('payments.id', $paymentId)
            ->first();

        if (!$payment || strcasecmp((string) $payment->payment_method, 'Cheque') !== 0) {
            return null;
        }
        if (empty($payment->bank_account_id) || trim((string) $payment->check_no) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'check_no' => 'A cheque requires both a bank account and check number.',
            ]);
        }

        $existing = DB::table('check_issuances')->where('payment_id', $payment->id)
            ->whereNotIn('status', ['voided', 'replaced'])->orderBy('id', 'desc')->first();
        if ($existing && !$replacementFor) {
            return $existing;
        }

        $bankKey = 'ID:'.(int) $payment->bank_account_id;
        $duplicate = DB::table('check_issuances')
            ->where('company_id', $payment->company_id)
            ->where('bank_account_key', $bankKey)
            ->where('check_no', trim($payment->check_no))
            ->exists();
        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'check_no' => 'Check number '.trim($payment->check_no).' is already assigned to this bank account.',
            ]);
        }

        $payee = trim((string) ($payment->payee_snapshot ?: $payment->supplier_name));
        $amountWords = $payment->amount_words_snapshot ?: $this->amountToWords($payment->amount, $payment->currency);
        DB::table('payments')->where('id', $payment->id)->update([
            'payee_snapshot' => $payee,
            'amount_words_snapshot' => $amountWords,
            'updated_at' => now(),
        ]);

        $issuanceId = DB::table('check_issuances')->insertGetId([
            'company_id' => $payment->company_id,
            'payment_id' => $payment->id,
            'bank_account_id' => $payment->bank_account_id,
            'bank_account_key' => $bankKey,
            'check_no' => trim($payment->check_no),
            'check_date' => $payment->payment_date,
            'payee_name' => $payee,
            'currency' => strtoupper($payment->currency ?: 'PHP'),
            'amount' => $payment->amount,
            'amount_words' => $amountWords,
            'status' => 'assigned',
            'print_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($replacementFor) {
            DB::table('check_issuances')->where('id', $replacementFor)->update([
                'status' => 'replaced',
                'replaced_by_check_issuance_id' => $issuanceId,
                'updated_at' => now(),
            ]);
        }

        return DB::table('check_issuances')->where('id', $issuanceId)->first();
    }

    private function activeCheckIssuance($paymentId, $lock = false)
    {
        if (!Schema::hasTable('check_issuances')) {
            return null;
        }

        $query = DB::table('check_issuances')
            ->where('company_id', $this->defaultCompanyId())
            ->where('payment_id', $paymentId)
            ->whereNotIn('status', ['voided', 'replaced'])
            ->orderBy('id', 'desc');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function applyReleasedPaymentToBill($payment, $bill = null)
    {
        $bill = $bill ?: $this->apBillRecordByNumber($payment->bill_no);
        if (!$bill) {
            return;
        }

        $paidTotal = min((float) $bill->grand_total, (float) $bill->paid_total + (float) $payment->amount);
        DB::table('purchase_documents')->where('company_id', $payment->company_id)->where('id', $bill->id)->update([
            'paid_total' => $paidTotal,
            'status' => $paidTotal >= (float) $bill->grand_total ? 'paid' : 'partially_paid',
            'approval_status' => 'Released',
            'updated_at' => now(),
        ]);
    }

    private function reverseReleasedPaymentFromBill($payment)
    {
        $bill = $this->apBillRecordByNumber($payment->bill_no);
        if (!$bill) {
            return;
        }

        $paidTotal = max(0, (float) $bill->paid_total - (float) $payment->amount);
        DB::table('purchase_documents')->where('company_id', $payment->company_id)->where('id', $bill->id)->update([
            'paid_total' => $paidTotal,
            'status' => $paidTotal <= 0 ? 'for_payment' : 'partially_paid',
            'updated_at' => now(),
        ]);
    }

    private function apPaymentRows()
    {
        if (!Schema::hasTable('payments')) {
            return [];
        }

        $rbac = app(RbacService::class);
        $canApprove = $rbac->allows(auth()->user(), 'payables', 'view');
        $canControlChecks = $rbac->allows(auth()->user(), 'payables', 'release');

        return DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->select('payments.*', 'suppliers.name as supplier_name')
            ->where('payments.company_id', $this->defaultCompanyId())
            ->orderBy('payments.payment_date', 'desc')
            ->orderBy('payments.id', 'desc')
            ->get()
            ->map(function ($payment) use ($canApprove, $canControlChecks) {
                $issuance = $this->activeCheckIssuance($payment->id);
                $status = $payment->approval_status ?: ucwords(str_replace('_', ' ', $payment->status));
                $isCheque = strcasecmp((string) $payment->payment_method, 'Cheque') === 0;

                return [
                    'id' => $payment->id,
                    'payment_no' => $payment->payment_no,
                    'check_voucher' => $payment->voucher_no ?: '-',
                    'supplier' => $payment->payee_snapshot ?: ($payment->supplier_name ?: '-'),
                    'shipment_job_id' => $payment->shipment_job_id ?: '-',
                    'check_no' => $issuance && trim((string) $issuance->check_no) !== ''
                        ? $issuance->check_no
                        : ($payment->check_no ?: '-'),
                    'currency' => $payment->currency ?: 'PHP',
                    'amount' => number_format((float) $payment->amount, 2),
                    'status' => $status,
                    'payment_method' => $payment->payment_method,
                    'check_status' => $issuance ? $issuance->status : null,
                    'print_count' => $issuance ? (int) $issuance->print_count : 0,
                    'voucher_printed' => $this->hasRecordedCheckVoucherPrint($payment->id),
                    'can_approve' => $canApprove
                        && in_array(strtolower((string) $status), ['for approval', 'pending'], true),
                    'can_release' => $canControlChecks
                        && strtolower((string) $status) === 'approved'
                        && (!$payment->created_by || (int) $payment->created_by !== (int) auth()->id())
                        && (!$isCheque || ($issuance && (int) $issuance->print_count > 0)),
                    'can_print' => $canControlChecks && $isCheque
                        && in_array(strtolower((string) $status), ['approved', 'released'], true),
                    'can_void' => $canControlChecks
                        && !in_array(strtolower((string) $status), ['voided', 'cancelled'], true),
                ];
            })
            ->all();
    }

    private function apAgingRows()
    {
        if (!Schema::hasTable('purchase_documents')) {
            return [];
        }

        return DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->select(
                'purchase_documents.*',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name'
            )
            ->where('purchase_documents.company_id', $this->defaultCompanyId())
            ->whereNotIn('purchase_documents.status', ['paid', 'cancelled', 'voided'])
            ->orderBy('purchase_documents.due_date')
            ->get()
            ->map(function ($bill) {
                $balance = max(0, (float) $bill->grand_total - (float) $bill->paid_total);
                $daysPastDue = $bill->due_date ? now()->diffInDays(\Carbon\Carbon::parse($bill->due_date), false) * -1 : 0;
                $buckets = [0, 0, 0, 0];
                if ($daysPastDue <= 0) {
                    $buckets[0] = $balance;
                } elseif ($daysPastDue <= 30) {
                    $buckets[1] = $balance;
                } elseif ($daysPastDue <= 60) {
                    $buckets[2] = $balance;
                } else {
                    $buckets[3] = $balance;
                }

                return array_merge([
                    $bill->supplier_code ?: '-',
                    $bill->supplier_name ?: '-',
                    $bill->shipment_job_id ?: '-',
                    $bill->document_no,
                    $bill->currency ?: 'PHP',
                ], $buckets);
            })
            ->all();
    }

    private function paymentPrintData($paymentId = null)
    {
        if (!Schema::hasTable('payments')) {
            return $this->emptyPaymentPrintData();
        }

        $query = DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'payments.bank_account_id')
            ->select(
                'payments.*',
                'suppliers.name as supplier_name',
                'bank_accounts.bank_name as payment_bank_name',
                'bank_accounts.account_no as payment_bank_account_no'
            )
            ->where('payments.company_id', $this->defaultCompanyId());

        $payment = $paymentId
            ? $query->where('payments.id', $paymentId)->first()
            : $query->orderBy('payments.id', 'desc')->first();

        if (!$payment) {
            return $this->emptyPaymentPrintData();
        }

        $bill = $this->apBillRecordByNumber($payment->bill_no);
        $gross = $bill ? (float) $bill->gross_amount : (float) $payment->amount;
        $wht = $bill ? (float) $bill->withholding_tax_amount : 0;
        $net = (float) $payment->amount;

        $documentDate = !empty($payment->payment_date)
            ? date('m/d/Y', strtotime($payment->payment_date))
            : now()->format('m/d/Y');
        $bankAccountNo = $payment->payment_bank_account_no ?: '-';
        if ($bankAccountNo === '-' && !empty($payment->bank_account) && preg_match('/(?:CA#|SA#|Acct#|Account#)\s*([A-Za-z0-9\-]+)/i', $payment->bank_account, $matches)) {
            $bankAccountNo = $matches[1];
        }
        $bankName = $payment->payment_bank_name ?: 'Bank';
        $currency = strtoupper($payment->currency ?: 'PHP');
        $apTradeTitle = 'A/P Trade ('.$bankName.') - '.$currency;
        $cashInBankTitle = 'Cash in Bank - '.$bankName.($bankAccountNo !== '-' ? ' ('.$bankAccountNo.')' : '');
        $clientAccountNo = $bill ? trim((string) ($bill->client_customer_id ?? '')) : '';
        $vendorBillMemo = $bill ? trim((string) ($bill->memo ?: $bill->remarks)) : '';

        return [
            'id' => $payment->id,
            'voucher_no' => $payment->voucher_no ?: 'CV-'.$payment->id,
            'document_no' => $payment->voucher_no ?: 'CV-'.$payment->id,
            'document_date' => $documentDate,
            'payment_no' => $payment->payment_no,
            'company' => DB::table('companies')->where('id', $payment->company_id)->value('name') ?: 'SeaJet Holdings Inc.',
            'supplier' => $payment->payee_snapshot ?: ($payment->supplier_name ?: '-'),
            'vendor' => $payment->supplier_name ?: '-',
            'shipment_job_id' => $payment->shipment_job_id ?: '-',
            'payment_method' => $payment->payment_method,
            'bank_account' => $payment->bank_account ?: '-',
            'bank_name' => $payment->payment_bank_name ?: '-',
            'account_name' => $payment->supplier_name ?: '-',
            'account_no' => $clientAccountNo !== '' ? $clientAccountNo : '-',
            'check_no' => $payment->check_no ?: '-',
            'bill_no' => $payment->bill_no,
            'description' => $vendorBillMemo,
            'currency' => $currency,
            'gross' => $gross,
            'withholding' => $wht,
            'net' => $net,
            'debit_account_title' => $apTradeTitle,
            'credit_account_title' => $cashInBankTitle,
            'amount_words' => $payment->amount_words_snapshot ?: $this->amountToWords($net, $payment->currency ?: 'PHP'),
        ];
    }

    private function paymentRecordForCompany($paymentId)
    {
        return DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->select('payments.*', 'suppliers.name as supplier_name')
            ->where('payments.company_id', $this->defaultCompanyId())
            ->where('payments.id', $paymentId)
            ->first();
    }

    private function resolveLegacyPaymentBankAccount($payment)
    {
        if (!$payment || !empty($payment->bank_account_id) || !Schema::hasTable('bank_accounts')) {
            return $payment;
        }

        $label = strtoupper(trim((string) $payment->bank_account));
        $currency = strtoupper((string) ($payment->currency ?: 'PHP'));
        $banks = DB::table('bank_accounts')->where('company_id', $payment->company_id)
            ->where('currency', $currency)->where('is_active', true)->get();
        $match = $banks->first(function ($bank) use ($label) {
            return $label === strtoupper((string) $bank->account_no)
                || $label === strtoupper((string) $bank->account_name)
                || strpos($label, strtoupper((string) $bank->account_no)) !== false
                || (strpos($label, strtoupper((string) $bank->bank_name)) !== false
                    && strpos($label, 'USD') !== false);
        });

        if ($match) {
            DB::table('payments')->where('id', $payment->id)->update([
                'bank_account_id' => $match->id,
                'updated_at' => now(),
            ]);
            $payment->bank_account_id = $match->id;
        }

        return $payment;
    }

    private function checkPrintEligibilityErrors($payment)
    {
        $errors = [];
        if (!$payment) {
            return ['payment' => 'Payment not found.'];
        }
        if (strcasecmp((string) $payment->payment_method, 'Cheque') !== 0) {
            $errors['payment_method'] = 'Only payments using the Cheque method can be printed on check stock.';
        }
        if (!in_array(strtolower((string) $payment->approval_status), ['approved', 'released'], true)) {
            $errors['approval_status'] = 'The payment must be approved before check printing.';
        }
        if (empty($payment->bank_account_id)) {
            $errors['bank_account'] = 'A valid company bank account is required before check printing.';
        }
        if (trim((string) $payment->check_no) === '') {
            $errors['check_no'] = 'A check number is required before printing.';
        }
        if ((float) $payment->amount <= 0) {
            $errors['amount'] = 'The check amount must be greater than zero.';
        }
        if (trim((string) ($payment->payee_snapshot ?: $payment->supplier_name)) === '') {
            $errors['payee'] = 'A payee is required before printing.';
        }
        if (in_array(strtolower((string) $payment->status), ['voided', 'cancelled'], true)) {
            $errors['status'] = 'A voided or cancelled payment cannot be printed.';
        }
        $issuance = $this->activeCheckIssuance($payment->id);
        if ((!$issuance || (int) $issuance->print_count === 0) && !$this->hasRecordedCheckVoucherPrint($payment->id)) {
            $errors['check_voucher'] = 'Print the Check Voucher before printing the Check.';
        }

        return $errors;
    }

    private function checkVoucherPreparationErrors($payment)
    {
        $errors = [];
        $hasValidBank = $payment && !empty($payment->bank_account_id)
            && DB::table('bank_accounts')
                ->where('company_id', $payment->company_id)
                ->where('id', $payment->bank_account_id)
                ->where('is_active', true)
                ->exists();

        if (!$hasValidBank) {
            $errors['bank_account'] = 'Select an active bank before preparing the Check Voucher.';
        }
        if (!$payment || trim((string) $payment->check_no) === '') {
            $errors['check_no'] = 'Enter the check number before preparing the Check Voucher.';
        }

        return $errors;
    }

    private function hasRecordedCheckVoucherPrint($paymentId)
    {
        return Schema::hasTable('check_voucher_print_events')
            && DB::table('check_voucher_print_events')
                ->where('company_id', $this->defaultCompanyId())
                ->where('payment_id', $paymentId)
                ->exists();
    }

    private function canOperateChecks()
    {
        return app(RbacService::class)->allows(auth()->user(), 'payables', 'release');
    }

    private function bankCheckPrintLayout($bankAccountId = null, $profileId = null)
    {
        $defaults = $this->defaultBankCheckPrintLayout();

        if (Schema::hasTable('check_layout_profiles') && $bankAccountId) {
            $profile = $this->bankCheckLayoutProfile($bankAccountId, $profileId);
            if (!empty($profile['id'])) {
                $saved = DB::table('check_layout_profiles')->where('id', $profile['id'])->first();
                $positions = json_decode($saved->positions ?: '{}', true) ?: [];
                $styles = json_decode($saved->field_styles ?: '{}', true) ?: [];
                $positions['font_sizes'] = isset($styles['font_sizes']) && is_array($styles['font_sizes'])
                    ? $styles['font_sizes']
                    : [];

                return $this->sanitizeBankCheckPrintLayout($positions);
            }
        }

        if (!Schema::hasTable('print_layouts')) {
            return $defaults;
        }

        $saved = DB::table('print_layouts')
            ->where('company_id', $this->defaultCompanyId())
            ->where('document_type', 'bank_check')
            ->where('layout_name', 'Default')
            ->first();

        if (!$saved) {
            return $defaults;
        }

        $positions = json_decode($saved->positions ?: '{}', true) ?: [];
        $styles = json_decode($saved->field_styles ?: '{}', true) ?: [];
        $positions['font_sizes'] = isset($styles['font_sizes']) && is_array($styles['font_sizes'])
            ? $styles['font_sizes']
            : [];

        return $this->sanitizeBankCheckPrintLayout($positions);
    }

    private function bankCheckLayoutProfile($bankAccountId, $profileId = null)
    {
        $profile = null;
        if (Schema::hasTable('check_layout_profiles') && $bankAccountId) {
            $query = DB::table('check_layout_profiles')
                ->where('company_id', $this->defaultCompanyId())
                ->where('bank_account_id', $bankAccountId);
            if ($profileId) {
                $profile = $query->where('id', $profileId)->first();
            } else {
                $profile = $query->where('is_active', true)->orderBy('version', 'desc')->orderBy('id', 'desc')->first();
            }
        }

        if ($profile) {
            return $this->checkLayoutProfileArray($profile);
        }

        return [
            'id' => null,
            'bank_account_id' => $bankAccountId ? (int) $bankAccountId : null,
            'profile_name' => 'Default',
            'printer_name' => '',
            'paper_width_mm' => 210,
            'paper_height_mm' => 88.9,
            'feed_orientation' => 'top',
            'offset_x_mm' => 0,
            'offset_y_mm' => 0,
            'version' => 0,
        ];
    }

    private function bankCheckLayoutProfiles($bankAccountId)
    {
        if (!Schema::hasTable('check_layout_profiles') || !$bankAccountId) {
            return [];
        }

        return DB::table('check_layout_profiles')
            ->where('company_id', $this->defaultCompanyId())
            ->where('bank_account_id', $bankAccountId)
            ->where('is_active', true)
            ->orderBy('profile_name')
            ->get()
            ->map(function ($profile) {
                return $this->checkLayoutProfileArray($profile);
            })
            ->all();
    }

    private function checkLayoutProfileArray($profile)
    {
        return [
            'id' => $profile ? (int) $profile->id : null,
            'bank_account_id' => $profile && $profile->bank_account_id ? (int) $profile->bank_account_id : null,
            'profile_name' => $profile ? $profile->profile_name : 'Default',
            'printer_name' => $profile ? ($profile->printer_name ?: '') : '',
            'paper_width_mm' => $profile ? (float) $profile->paper_width_mm : 210,
            'paper_height_mm' => $profile ? (float) $profile->paper_height_mm : 88.9,
            'feed_orientation' => $profile ? $profile->feed_orientation : 'top',
            'offset_x_mm' => $profile ? (float) $profile->offset_x_mm : 0,
            'offset_y_mm' => $profile ? (float) $profile->offset_y_mm : 0,
            'version' => $profile ? (int) $profile->version : 0,
        ];
    }

    private function defaultBankCheckPrintLayout()
    {
        return [
            'font_sizes' => [
                'date_m1' => 12, 'date_m2' => 12, 'date_d1' => 12, 'date_d2' => 12,
                'date_y1' => 12, 'date_y2' => 12, 'date_y3' => 12, 'date_y4' => 12,
                'payee' => 12, 'amount_words' => 12, 'amount_figures' => 12,
            ],
            'date_m1' => ['top' => 12, 'left' => 175],
            'date_m2' => ['top' => 12, 'left' => 179],
            'date_d1' => ['top' => 12, 'left' => 185],
            'date_d2' => ['top' => 12, 'left' => 189],
            'date_y1' => ['top' => 12, 'left' => 195],
            'date_y2' => ['top' => 12, 'left' => 199],
            'date_y3' => ['top' => 12, 'left' => 203],
            'date_y4' => ['top' => 12, 'left' => 207],
            'payee' => ['top' => 28, 'left' => 22],
            'amount_words' => ['top' => 36, 'left' => 22],
            'amount_figures' => ['top' => 28, 'left' => 178],
        ];
    }

    private function sanitizeBankCheckPrintLayout($layout)
    {
        $defaults = $this->defaultBankCheckPrintLayout();
        $clean = ['font_sizes' => []];
        $layout = is_array($layout) ? $layout : [];
        $fontSizes = isset($layout['font_sizes']) && is_array($layout['font_sizes'])
            ? $layout['font_sizes']
            : [];

        foreach ($defaults['font_sizes'] as $field => $defaultFontSize) {
            $point = isset($layout[$field]) && is_array($layout[$field]) ? $layout[$field] : [];
            $clean[$field] = [
                'top' => round(max(-20, min(120, (float) ($point['top'] ?? $defaults[$field]['top']))), 1),
                'left' => round(max(-50, min(260, (float) ($point['left'] ?? $defaults[$field]['left']))), 1),
            ];
            $clean['font_sizes'][$field] = round(max(8, min(24, (float) ($fontSizes[$field] ?? $defaultFontSize))), 1);
        }

        return $clean;
    }

    private function canSaveCheckPrintLayout()
    {
        return app(RbacService::class)->allows(auth()->user(), 'finance_settings', 'edit');
    }

    private function checkVoucherPrintLayoutPayload($fromRequest = false)
    {
        $saved = $this->savedCheckVoucherPrintLayout();
        $settings = $saved['settings'] ?? [];
        $defaults = $this->defaultCheckVoucherPrintPositions();

        $paperWidth = (int) request('paper_width', $settings['paper_width'] ?? 816);
        $paperHeight = (int) request('paper_height', $settings['paper_height'] ?? 1056);

        $layout = [
            'settings' => [
                'paper_size' => request('paper_size', $settings['paper_size'] ?? 'letter'),
                'paper_width' => max(400, min(1800, $paperWidth)),
                'paper_height' => max(400, min(2400, $paperHeight)),
                'template_url' => request('template_url', $settings['template_url'] ?? ''),
                'print_template' => request()->has('print_template') ? request('print_template') === '1' : (bool) ($settings['print_template'] ?? false),
            ],
            'positions' => [],
            'field_styles' => [],
            'custom_fields' => $this->checkVoucherCustomFields($saved['custom_fields'] ?? [], $fromRequest),
        ];

        $savedPositions = $saved['positions'] ?? [];
        $savedStyles = $saved['field_styles'] ?? [];
        $needsSectionSwap = !$fromRequest && empty($savedStyles['_accounting_before_description']);
        $needsSwappedSectionSpacing = !$fromRequest && empty($savedStyles['_spaced_swapped_sections']);
        $needsPayeeGap = !$fromRequest && empty($savedStyles['_payee_gap_10']);
        $swappedSectionY = [
            'account_title_header' => 334,
            'debit_header' => 334,
            'credit_header' => 334,
            'debit_account_title' => 362,
            'debit_amount' => 362,
            'credit_account_title' => 382,
            'credit_amount' => 382,
            'total_label' => 404,
            'total_debit' => 404,
            'total_credit' => 404,
            'description_label' => 436,
            'description' => 436,
            'amount_header' => 510,
            'account_name_label' => 510,
            'account_name' => 510,
            'amount' => 510,
            'account_no_label' => 540,
            'account_no' => 540,
            'amount_words' => 540,
            'prepared_by_label' => 592,
            'prepared_by' => 678,
            'prepared_date_label' => 708,
            'checked_by_label' => 592,
            'checked_by' => 678,
            'checked_date_label' => 708,
            'approved_by_label' => 592,
            'approved_by' => 678,
            'approved_date_label' => 708,
            'received_by_label' => 592,
            'received_by' => 662,
            'received_date_label' => 708,
        ];
        $legacyCompactPositions = [
            'payment_info_title' => [58, 374],
            'amount_header' => [432, 382],
            'account_name_label' => [60, 424],
            'account_name' => [196, 424],
            'amount' => [500, 424],
            'account_no_label' => [60, 462],
            'account_no' => [196, 462],
            'amount_words' => [384, 462],
            'description_label' => [56, 538],
            'description' => [204, 538],
        ];
        $defaultBoxHeights = [
            'payee' => 126,
            'amount' => 70,
            'description' => 64,
            'accounting' => 160,
            'signatures' => 145,
        ];
        $legacyBoxHeights = ['amount' => 96, 'description' => 104, 'accounting' => 176, 'signatures' => 150];
        $swappedBoxHeights = ['amount' => 70, 'accounting' => 92, 'signatures' => 145];
        $savedBoxHeights = isset($savedStyles['_box_heights']) && is_array($savedStyles['_box_heights'])
            ? $savedStyles['_box_heights']
            : [];
        $savedBoxPositions = isset($savedStyles['_box_positions']) && is_array($savedStyles['_box_positions'])
            ? $savedStyles['_box_positions']
            : [];

        $layout['box_heights'] = [];
        foreach ($defaultBoxHeights as $box => $height) {
            $savedHeight = $savedBoxHeights[$box] ?? $height;
            if ($needsSectionSwap && isset($swappedBoxHeights[$box])) {
                $savedHeight = $swappedBoxHeights[$box];
            }
            if ($needsSwappedSectionSpacing && $box === 'accounting') {
                $savedHeight = $swappedBoxHeights['accounting'];
            }
            if ($needsPayeeGap && $box === 'accounting') {
                $savedHeight = $swappedBoxHeights['accounting'];
            }
            if (!$fromRequest && isset($legacyBoxHeights[$box]) && (int) $savedHeight === $legacyBoxHeights[$box]) {
                $savedHeight = $height;
            }
            $layout['box_heights'][$box] = max(40, min(600, (int) request(
                'box_height_'.$box,
                $savedHeight
            )));
        }

        $defaultBoxPositions = [
            'payee' => [46, 190],
            'accounting' => [46, 326],
            'description' => [46, 428],
            'amount' => [46, 502],
            'signatures' => [46, 582],
        ];
        $layout['box_positions'] = [];
        foreach ($defaultBoxPositions as $box => $defaultPosition) {
            $savedPosition = $savedBoxPositions[$box] ?? $defaultPosition;
            $layout['box_positions'][$box] = [
                max(0, min($layout['settings']['paper_width'] - 100, (int) request('box_x_'.$box, $savedPosition[0] ?? $defaultPosition[0]))),
                max(0, min($layout['settings']['paper_height'] - 40, (int) request('box_y_'.$box, $savedPosition[1] ?? $defaultPosition[1]))),
            ];
        }

        foreach ($defaults as $field => $point) {
            $savedPoint = $savedPositions[$field] ?? $point;
            $isAccountingField = in_array($field, [
                'account_title_header', 'debit_header', 'credit_header',
                'debit_account_title', 'debit_amount', 'credit_account_title', 'credit_amount',
                'total_label', 'total_debit', 'total_credit',
            ], true);
            if (($needsSectionSwap || ($needsPayeeGap && $isAccountingField))
                && isset($swappedSectionY[$field])
                && ($savedPoint[0] ?? 0) >= 0
                && ($savedPoint[1] ?? 0) >= 0) {
                $savedPoint[1] = $swappedSectionY[$field];
            }
            if (isset($legacyCompactPositions[$field])
                && array_map('intval', (array) $savedPoint) === $legacyCompactPositions[$field]) {
                $savedPoint = $point;
            }
            $savedStyle = $savedStyles[$field] ?? [];
            $defaultWidth = $this->checkVoucherDefaultWidth($field);
            $defaultFontSize = in_array($field, ['company_name', 'form_title']) ? 14 : 11;

            $layout['positions'][$field] = [
                (int) request('x_'.$field, $savedPoint[0] ?? $point[0]),
                (int) request('y_'.$field, $savedPoint[1] ?? $point[1]),
            ];
            $layout['field_styles'][$field] = [
                'width' => max(30, min(1000, (int) request('w_'.$field, $savedStyle['width'] ?? $defaultWidth))),
                'height' => max(18, min(600, (int) request('h_'.$field, $savedStyle['height'] ?? ($field === 'logo_mark' ? 82 : 24)))),
                'font_size' => max(7, min(40, (int) request('fs_'.$field, $savedStyle['font_size'] ?? $defaultFontSize))),
            ];

            if ($field === 'logo_mark' && $layout['field_styles'][$field]['width'] < 320) {
                $currentWidth = $layout['field_styles'][$field]['width'];
                if ($layout['positions'][$field][0] >= 0) {
                    $layout['positions'][$field][0] = max(0, (int) round(
                        $layout['positions'][$field][0] - ((320 - $currentWidth) / 2)
                    ));
                }
                $layout['field_styles'][$field]['width'] = 320;
            }
        }

        if (empty($savedStyles['_compact_sections']) && !$fromRequest && !$needsSectionSwap) {
            $sectionOffsets = [
                'payment_info_title' => -26,
                'amount_header' => -40,
                'account_name_label' => -40,
                'account_name' => -40,
                'amount' => -40,
                'account_no_label' => -40,
                'account_no' => -40,
                'amount_words' => -40,
                'description_label' => -94,
                'description' => -94,
                'account_title_header' => -148,
                'debit_header' => -148,
                'credit_header' => -148,
                'debit_account_title' => -148,
                'debit_amount' => -148,
                'credit_account_title' => -148,
                'credit_amount' => -148,
                'total_label' => -148,
                'total_debit' => -148,
                'total_credit' => -148,
                'prepared_by_label' => -168,
                'prepared_by' => -168,
                'prepared_date_label' => -168,
                'checked_by_label' => -168,
                'checked_by' => -168,
                'checked_date_label' => -168,
                'approved_by_label' => -168,
                'approved_by' => -168,
                'approved_date_label' => -168,
                'received_by_label' => -168,
                'received_by' => -168,
                'received_date_label' => -168,
            ];
            foreach ($sectionOffsets as $field => $offset) {
                if (isset($layout['positions'][$field])) {
                    $layout['positions'][$field][1] = max(0, $layout['positions'][$field][1] + $offset);
                }
            }
        }

        $layout['field_styles']['_box_heights'] = $layout['box_heights'];
        $layout['field_styles']['_box_positions'] = $layout['box_positions'];
        $layout['field_styles']['_compact_sections'] = true;
        $layout['field_styles']['_accounting_before_description'] = true;
        $layout['field_styles']['_spaced_swapped_sections'] = true;
        $layout['field_styles']['_payee_gap_10'] = true;

        return $layout;
    }

    private function savedCheckVoucherPrintLayout()
    {
        $fallback = [];
        if (Storage::exists('erp/check-voucher-print-layout.json')) {
            $fallback = json_decode(Storage::get('erp/check-voucher-print-layout.json'), true) ?: [];
        }

        if (!Schema::hasTable('print_layouts')) {
            return $fallback;
        }

        $layout = DB::table('print_layouts')
            ->whereNull('company_id')
            ->where('document_type', 'check_voucher')
            ->where('layout_name', 'Default')
            ->first();

        if (!$layout) {
            return $fallback;
        }

        return [
            'settings' => [
                'paper_size' => $layout->paper_size ?: ($fallback['settings']['paper_size'] ?? 'letter'),
                'paper_width' => (int) ($layout->paper_width ?: ($fallback['settings']['paper_width'] ?? 816)),
                'paper_height' => (int) ($layout->paper_height ?: ($fallback['settings']['paper_height'] ?? 1056)),
                'template_url' => $layout->template_url ?: ($fallback['settings']['template_url'] ?? ''),
                'print_template' => (bool) $layout->print_template,
            ],
            'positions' => json_decode($layout->positions ?: '{}', true) ?: ($fallback['positions'] ?? []),
            'field_styles' => json_decode($layout->field_styles ?: '{}', true) ?: ($fallback['field_styles'] ?? []),
            'custom_fields' => json_decode($layout->custom_fields ?: '[]', true) ?: ($fallback['custom_fields'] ?? []),
        ];
    }

    private function defaultCheckVoucherPrintPositions()
    {
        return [
            'logo_mark' => [290, 28],
            'company_name' => [232, 108],
            'form_title' => [338, 136],
            'payee_label' => [52, 196],
            'payee' => [58, 242],
            'check_no_label' => [430, 202],
            'check_no' => [640, 196],
            'document_no_label' => [430, 242],
            'document_no' => [640, 236],
            'document_date_label' => [430, 282],
            'document_date' => [640, 276],
            'payment_info_title' => [58, 346],
            'amount_header' => [442, 392],
            'account_name_label' => [70, 392],
            'account_name' => [180, 392],
            'amount' => [530, 392],
            'account_no_label' => [70, 423],
            'account_no' => [180, 423],
            'amount_words' => [442, 423],
            'description_label' => [56, 526],
            'description' => [204, 526],
            'account_title_header' => [150, 636],
            'debit_header' => [558, 636],
            'credit_header' => [704, 636],
            'debit_account_title' => [64, 690],
            'debit_amount' => [540, 690],
            'credit_account_title' => [84, 730],
            'credit_amount' => [686, 730],
            'total_label' => [448, 794],
            'total_debit' => [540, 794],
            'total_credit' => [686, 794],
            'prepared_by_label' => [92, 846],
            'prepared_by' => [70, 938],
            'prepared_date_label' => [62, 982],
            'checked_by_label' => [304, 846],
            'checked_by' => [284, 938],
            'checked_date_label' => [274, 982],
            'approved_by_label' => [510, 846],
            'approved_by' => [478, 938],
            'approved_date_label' => [468, 982],
            'received_by_label' => [664, 846],
            'received_by' => [626, 922],
            'received_date_label' => [626, 982],
        ];
    }

    private function checkVoucherPrintFields()
    {
        return [
            'logo_mark' => 'Logo Mark',
            'company_name' => 'Company Name',
            'form_title' => 'Form Title',
            'payee_label' => 'Payee Label',
            'payee' => 'Payee',
            'check_no_label' => 'Check No Label',
            'check_no' => 'Check No',
            'document_no_label' => 'Document No Label',
            'document_no' => 'Document No',
            'document_date_label' => 'Document Date Label',
            'document_date' => 'Document Date',
            'payment_info_title' => 'Payment Info Title',
            'amount_header' => 'Amount Header',
            'account_name_label' => 'Account Name Label',
            'account_name' => 'Account Name',
            'amount' => 'Amount',
            'account_no_label' => 'Account No Label',
            'account_no' => 'Account No',
            'amount_words' => 'Amount In Words',
            'description_label' => 'Description Label',
            'description' => 'Description',
            'account_title_header' => 'Account Title Header',
            'debit_header' => 'Debit Header',
            'credit_header' => 'Credit Header',
            'debit_account_title' => 'Debit Account Title',
            'debit_amount' => 'Debit Amount',
            'credit_account_title' => 'Credit Account Title',
            'credit_amount' => 'Credit Amount',
            'total_label' => 'Total Label',
            'total_debit' => 'Total Debit',
            'total_credit' => 'Total Credit',
            'prepared_by_label' => 'Prepared By Label',
            'prepared_by' => 'Prepared By',
            'prepared_date_label' => 'Prepared Date Label',
            'checked_by_label' => 'Checked By Label',
            'checked_by' => 'Checked By',
            'checked_date_label' => 'Checked Date Label',
            'approved_by_label' => 'Approved By Label',
            'approved_by' => 'Approved By',
            'approved_date_label' => 'Approved Date Label',
            'received_by_label' => 'Received By Label',
            'received_by' => 'Received By',
            'received_date_label' => 'Received Date Label',
        ];
    }

    private function checkVoucherDefaultWidth($field)
    {
        if ($field === 'company_name') {
            return 460;
        }
        if ($field === 'form_title') {
            return 200;
        }
        if ($field === 'payment_info_title') {
            return 220;
        }
        if ($field === 'description') {
            return 390;
        }
        if (in_array($field, ['amount_words'])) {
            return 350;
        }
        if (in_array($field, ['amount', 'debit_amount', 'credit_amount', 'total_debit', 'total_credit'])) {
            return 90;
        }
        if (in_array($field, ['prepared_by', 'checked_by', 'approved_by', 'received_by'])) {
            return 150;
        }
        if (in_array($field, ['payee', 'account_name', 'debit_account_title', 'credit_account_title'])) {
            return 300;
        }
        if (in_array($field, ['logo_mark'])) {
            return 320;
        }

        return 130;
    }

    private function checkVoucherCustomFields($savedFields, $fromRequest)
    {
        if (!$fromRequest && !request()->has('custom_fields_present')) {
            return $this->sanitizeCheckVoucherCustomFields($savedFields);
        }

        $keys = (array) request('custom_key', []);
        $labels = (array) request('custom_label', []);
        $texts = (array) request('custom_text', []);
        $xs = (array) request('custom_x', []);
        $ys = (array) request('custom_y', []);
        $widths = (array) request('custom_w', []);
        $heights = (array) request('custom_h', []);
        $fontSizes = (array) request('custom_fs', []);

        $fields = [];
        foreach ($keys as $index => $key) {
            $fields[] = [
                'key' => $key,
                'label' => $labels[$index] ?? 'Custom Field',
                'text' => $texts[$index] ?? '',
                'x' => $xs[$index] ?? 60,
                'y' => $ys[$index] ?? 60,
                'width' => $widths[$index] ?? 180,
                'height' => $heights[$index] ?? 24,
                'font_size' => $fontSizes[$index] ?? 11,
            ];
        }

        return $this->sanitizeCheckVoucherCustomFields($fields);
    }

    private function sanitizeCheckVoucherCustomFields($fields)
    {
        return array_values(array_map(function ($field) {
            return [
                'key' => preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($field['key'] ?? 'custom_'.uniqid())),
                'label' => trim((string) ($field['label'] ?? 'Custom Field')),
                'text' => trim((string) ($field['text'] ?? '')),
                'x' => max(0, min(1800, (int) ($field['x'] ?? 60))),
                'y' => max(0, min(2400, (int) ($field['y'] ?? 60))),
                'width' => max(30, min(1000, (int) ($field['width'] ?? 180))),
                'height' => max(18, min(600, (int) ($field['height'] ?? 24))),
                'font_size' => max(7, min(40, (int) ($field['font_size'] ?? 11))),
            ];
        }, is_array($fields) ? $fields : []));
    }

    private function emptyPaymentPrintData()
    {
        return [
            'id' => null,
            'voucher_no' => 'No saved payment',
            'document_no' => 'No saved payment',
            'document_date' => now()->format('m/d/Y'),
            'payment_no' => '-',
            'company' => 'SEA-JET INTERNATIONAL FORWARDERS INC.',
            'supplier' => '-',
            'vendor' => '-',
            'shipment_job_id' => '-',
            'payment_method' => '-',
            'bank_account' => '-',
            'bank_name' => '-',
            'account_name' => '-',
            'account_no' => '-',
            'check_no' => '-',
            'bill_no' => '-',
            'description' => 'No AP payment has been saved yet.',
            'currency' => 'PHP',
            'gross' => 0,
            'withholding' => 0,
            'net' => 0,
            'debit_account_title' => 'A/P Trade (BDO) - PHP',
            'credit_account_title' => 'Cash in Bank - BDO (4898002879)',
            'amount_words' => 'Zero Pesos Only',
        ];
    }

    private function apBillByNumber($documentNo)
    {
        $bill = $this->apBillRecordByNumber($documentNo);
        return $bill ? $this->apBillArray($bill) : null;
    }

    private function apBillById($id)
    {
        $bill = DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->select('purchase_documents.*', 'suppliers.code as supplier_code', 'suppliers.name as supplier_name')
            ->where('purchase_documents.id', $id)
            ->first();

        return $bill ? $this->apBillArray($bill) : [];
    }

    private function apBillRecordByNumber($documentNo)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return null;
        }

        return DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->select('purchase_documents.*', 'suppliers.code as supplier_code', 'suppliers.name as supplier_name')
            ->where('purchase_documents.company_id', $this->defaultCompanyId())
            ->where('purchase_documents.document_no', $documentNo)
            ->first();
    }

    private function apBillArray($bill)
    {
        $billLines = collect($this->apBillLines($bill->id));
        $lineAmountsExcludeVat = $this->apBillUsesNetLineAmounts($bill, $billLines);
        $expenseTotal = $this->apBillExpenseTotal($bill, $billLines, $lineAmountsExcludeVat);
        $expenseBalance = max(0, $expenseTotal - (float) ($bill->paid_total ?? 0));

        return [
            'id' => $bill->id,
            'transaction_type' => $bill->transaction_type ?? $bill->document_type,
            'ap_account' => $bill->ap_account ?? null,
            'bill_received' => !empty($bill->bill_received) ? 'Yes' : 'No',
            'supplier_id' => $bill->supplier_code ?? $bill->supplier_id,
            'supplier_name' => $bill->supplier_name ?? null,
            'supplier_address' => $bill->supplier_address ?? null,
            'client_customer_id' => $bill->client_customer_id ?? null,
            'shipment_job_id' => $bill->shipment_job_id ?? null,
            'bill_no' => $bill->document_no,
            'bill_date' => $bill->document_date,
            'reference_no' => $bill->reference_no ?? null,
            'due_date' => $bill->due_date,
            'terms' => $bill->terms ?? null,
            'currency' => $bill->currency,
            'exchange_rate' => $bill->usd_to_php_rate ?? $bill->exchange_rate ?? 1,
            'usd_to_php_rate' => $bill->usd_to_php_rate ?? $bill->exchange_rate ?? 1,
            'gross_amount' => number_format((float) ($bill->gross_amount ?? $bill->subtotal), 2),
            'php_amount' => number_format((float) ($bill->php_amount ?? 0), 2),
            'withholding_tax_rate' => $bill->withholding_tax_rate ?? 0,
            'withholding_tax_amount' => number_format((float) ($bill->withholding_tax_amount ?? 0), 2),
            'net_amount' => number_format((float) ($bill->net_amount ?? $bill->grand_total), 2),
            'expense_total' => number_format($expenseTotal, 2),
            'expense_balance' => number_format($expenseBalance, 2),
            'line_amounts_exclude_vat' => $lineAmountsExcludeVat,
            'memo' => $bill->memo ?? $bill->remarks,
            'approval_status' => $bill->approval_status ?? $bill->status,
        ];
    }

    private function apBillLines($billId)
    {
        $table = Schema::hasTable('vendor_bill_lines') ? 'vendor_bill_lines' : 'purchase_document_lines';
        if (!Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table)
            ->select($table.'.*')
            ->where($table.'.purchase_document_id', $billId);

        if (Schema::hasTable('accounts') && Schema::hasColumn($table, 'account_id')) {
            $query->leftJoin('accounts', 'accounts.id', '=', $table.'.account_id')
                ->addSelect(
                    'accounts.name as account_master_name',
                    'accounts.atc_code as account_atc_code',
                    'accounts.tax_code as account_withholding_rate'
                );
        }

        if (Schema::hasTable('customers')) {
            $query->leftJoin('customers', 'customers.customer_id', '=', $table.'.customer_job_id')
                ->addSelect('customers.name as customer_job_name');
        }

        return $query->get();
    }

    private function apBillExpenseTotal($bill, $lines = null, $lineAmountsExcludeVat = null)
    {
        $lines = $lines === null ? collect($this->apBillLines($bill->id)) : collect($lines);
        if ($lines->isEmpty()) {
            return max(0, (float) ($bill->gross_amount ?? $bill->subtotal ?? 0)
                - (float) ($bill->tax_total ?? 0)
                - (float) ($bill->withholding_tax_amount ?? 0));
        }

        $lineAmountsExcludeVat = $lineAmountsExcludeVat === null
            ? $this->apBillUsesNetLineAmounts($bill, $lines)
            : $lineAmountsExcludeVat;
        $lineTotal = $lines->sum(function ($line) use ($lineAmountsExcludeVat) {
            return $this->apBillLineExpenseAmount($line, $lineAmountsExcludeVat);
        });

        return max(0, $lineTotal - (float) ($bill->withholding_tax_amount ?? 0));
    }

    private function apBillUsesNetLineAmounts($bill, $lines = null)
    {
        $tax = (float) ($bill->tax_total ?? 0);
        if ($tax <= 0) {
            return true;
        }

        $lines = $lines === null ? collect($this->apBillLines($bill->id)) : collect($lines);
        $lineTotal = $lines->sum(function ($line) {
            return (float) ($line->amount ?? $line->line_total ?? 0);
        });
        $subtotal = (float) ($bill->subtotal ?? 0);
        $gross = (float) ($bill->gross_amount ?? 0);

        return abs($subtotal - $lineTotal) < 0.01
            && abs($gross - ($subtotal + $tax)) < 0.01;
    }

    private function apBillLineExpenseAmount($line, $lineAmountsExcludeVat)
    {
        $amount = (float) ($line->amount ?? $line->line_total ?? 0);
        $taxCategory = strtoupper(trim((string) ($line->tax_category ?? '')));

        return !$lineAmountsExcludeVat && in_array($taxCategory, ['VAT12', '12%'], true)
            ? $amount / 1.12
            : $amount;
    }

    private function apBillPrintData($bill)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return ['bill' => null, 'lines' => []];
        }

        $query = DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->select('purchase_documents.*', 'suppliers.name as supplier_name', 'suppliers.address as supplier_master_address');

        $record = is_numeric($bill)
            ? $query->where('purchase_documents.id', $bill)->first()
            : $query->where('purchase_documents.document_no', $bill)->first();

        if (!$record) {
            return ['bill' => null, 'lines' => []];
        }

        $lines = $this->apBillLines($record->id);
        $grossAmount = (float) (data_get($record, 'gross_amount') ?? 0);
        $inputVat = (float) (data_get($record, 'tax_total') ?? 0);
        $whtAmount = (float) (data_get($record, 'withholding_tax_amount') ?? 0);
        $total = (float) (data_get($record, 'grand_total') ?? data_get($record, 'net_amount') ?? $grossAmount);

        return [
            'bill' => [
                'id' => $record->id,
                'vendor' => data_get($record, 'supplier_name') ?: '-',
                'vendor_address' => data_get($record, 'supplier_address') ?: (data_get($record, 'supplier_master_address') ?: ''),
                'date' => data_get($record, 'document_date') ? \Carbon\Carbon::parse(data_get($record, 'document_date'))->format('m/d/Y') : '',
                'ref_no' => data_get($record, 'reference_no') ?: data_get($record, 'document_no'),
                'bill_no' => data_get($record, 'document_no'),
                'bill_due' => data_get($record, 'due_date') ? \Carbon\Carbon::parse(data_get($record, 'due_date'))->format('m/d/Y') : '',
                'terms' => data_get($record, 'terms') ?: '',
                'memo' => data_get($record, 'memo') ?: (data_get($record, 'remarks') ?: ''),
                'currency' => data_get($record, 'currency') ?: 'PHP',
                'gross_amount' => $grossAmount,
                'input_vat' => $inputVat,
                'line_amounts_exclude_vat' => $this->apBillUsesNetLineAmounts($record, $lines),
                'withholding_tax_rate' => (float) (data_get($record, 'withholding_tax_rate') ?? 0),
                'withholding_tax_amount' => $whtAmount,
                'total' => $total,
            ],
            'lines' => $lines,
        ];
    }

    private function apBill2307PrintData($bill)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return ['form' => null, 'pages' => []];
        }

        $query = DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->leftJoin('companies', 'companies.id', '=', 'purchase_documents.company_id')
            ->select(
                'purchase_documents.*',
                'suppliers.name as payee_name',
                'suppliers.tin as payee_tin',
                'suppliers.address as payee_address',
                'suppliers.zip_code as payee_zip',
                'suppliers.bir_2307_payee_representative_name as payee_authorized_name',
                'suppliers.bir_2307_payee_representative_title as payee_authorized_title',
                'suppliers.bir_2307_payee_representative_tin as payee_authorized_tin',
                'companies.name as payer_name',
                'companies.tin as payer_tin',
                'companies.address as payer_address',
                'companies.zip_code as payer_zip',
                'companies.bir_2307_payor_representative_name as payer_authorized_name',
                'companies.bir_2307_payor_representative_title as payer_authorized_title',
                'companies.bir_2307_payor_representative_tin as payer_authorized_tin'
            );

        $record = is_numeric($bill)
            ? $query->where('purchase_documents.id', $bill)->first()
            : $query->where('purchase_documents.document_no', $bill)->first();

        if (!$record) {
            return ['form' => null, 'pages' => []];
        }

        $date = \Carbon\Carbon::parse($record->document_date);
        $quarterStartMonth = ((int) floor(($date->month - 1) / 3) * 3) + 1;
        $periodFrom = \Carbon\Carbon::create($date->year, $quarterStartMonth, 1)->startOfDay();
        $periodTo = $periodFrom->copy()->addMonths(2)->endOfMonth();
        $monthIndex = $date->month - $quarterStartMonth;
        $currencyFactor = strtoupper((string) $record->currency) === 'USD'
            ? ((float) ($record->usd_to_php_rate ?: $record->exchange_rate) ?: 1)
            : 1;
        $rows = [];
        $billLines = $this->apBillLines($record->id);
        $lineAmountsExcludeVat = $this->apBillUsesNetLineAmounts($record, $billLines);

        foreach ($billLines as $line) {
            $amount = (float) (data_get($line, 'amount') ?? data_get($line, 'line_total') ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $accountLabel = data_get($line, 'account_name') ?: data_get($line, 'account_master_name');
            $profile = $this->expenseAccountWithholdingProfile($accountLabel);
            $accountDescription = data_get($line, 'account_master_name') ?: $profile['name'];
            $atcCode = strtoupper(trim((string) (data_get($line, 'atc_code') ?: data_get($line, 'account_atc_code') ?: $profile['atc_code'])));
            $description = $this->bir2307IncomePaymentDescription($atcCode, $accountDescription);
            $rate = (float) data_get($line, 'withholding_tax_rate');
            $tax = (float) data_get($line, 'withholding_tax_amount');
            if ($rate <= 0 && $tax <= 0) {
                continue;
            }

            $base = $this->apBillLineExpenseAmount($line, $lineAmountsExcludeVat);
            if ($tax <= 0 && $rate > 0) {
                $tax = $base * ($rate / 100);
            }

            $key = $atcCode !== '' ? $atcCode : strtolower(trim((string) $description));
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'description' => $description ?: 'Vendor bill payment',
                    'atc_code' => $atcCode,
                    'months' => [0.0, 0.0, 0.0],
                    'tax_withheld' => 0.0,
                ];
            }
            $rows[$key]['months'][$monthIndex] += $base * $currencyFactor;
            $rows[$key]['tax_withheld'] += $tax * $currencyFactor;
        }

        $rows = array_values($rows);
        $expectedTax = (float) $record->withholding_tax_amount * $currencyFactor;
        $rowTax = array_sum(array_map(function ($row) {
            return $row['tax_withheld'];
        }, $rows));
        if ($expectedTax > 0 && !empty($rows) && abs($expectedTax - $rowTax) >= 0.005) {
            $rows[0]['tax_withheld'] += $expectedTax - $rowTax;
        }

        if (count($rows) > 10) {
            $overflowRows = array_slice($rows, 9);
            $overflow = [
                'description' => 'Other withholding tax items',
                'atc_code' => '',
                'months' => [0.0, 0.0, 0.0],
                'tax_withheld' => 0.0,
            ];
            foreach ($overflowRows as $overflowRow) {
                foreach ($overflowRow['months'] as $month => $monthAmount) {
                    $overflow['months'][$month] += $monthAmount;
                }
                $overflow['tax_withheld'] += $overflowRow['tax_withheld'];
            }
            $rows = array_merge(array_slice($rows, 0, 9), [$overflow]);
        }
        $pages = [$rows];

        return [
            'form' => [
                'bill_no' => $record->document_no,
                'period_from' => $periodFrom->format('mdY'),
                'period_to' => $periodTo->format('mdY'),
                'payee_tin' => $record->payee_tin ?: '',
                'payee_name' => $record->payee_name ?: '',
                'payee_address' => $record->payee_address ?: '',
                'payee_zip' => $record->payee_zip ?: $this->zipCodeFromAddress($record->payee_address),
                'payer_tin' => $record->payer_tin ?: '',
                'payer_name' => $record->payer_name ?: '',
                'payer_address' => $record->payer_address ?: '',
                'payer_zip' => $record->payer_zip ?: $this->zipCodeFromAddress($record->payer_address),
                'payer_authorized_name' => $record->payer_authorized_name ?: '',
                'payer_authorized_title' => $record->payer_authorized_title ?: '',
                'payer_authorized_tin' => $record->payer_authorized_tin ?: '',
                'payee_authorized_name' => $record->payee_authorized_name ?: '',
                'payee_authorized_title' => $record->payee_authorized_title ?: '',
                'payee_authorized_tin' => $record->payee_authorized_tin ?: '',
            ],
            'pages' => $pages,
        ];
    }

    private function defaultVendorBill2307PrintLayout()
    {
        $layout = [
            'payee_name' => ['top' => 218, 'left' => 45, 'width' => 746, 'font_size' => 11],
            'payee_zip' => ['top' => 257, 'left' => 721, 'width' => 68, 'font_size' => 12],
            'payer_name' => ['top' => 372, 'left' => 45, 'width' => 746, 'font_size' => 11],
            'payer_zip' => ['top' => 411, 'left' => 721, 'width' => 68, 'font_size' => 12],
            'payer_authorized_name' => ['top' => 976, 'left' => 188, 'width' => 440, 'font_size' => 11],
            'payer_authorized_title' => ['top' => 991, 'left' => 188, 'width' => 440, 'font_size' => 9],
            'payer_authorized_tin' => ['top' => 1004, 'left' => 188, 'width' => 440, 'font_size' => 9],
            'payee_authorized_name' => ['top' => 1079, 'left' => 188, 'width' => 440, 'font_size' => 11],
            'payee_authorized_title' => ['top' => 1094, 'left' => 188, 'width' => 440, 'font_size' => 9],
            'payee_authorized_tin' => ['top' => 1107, 'left' => 188, 'width' => 440, 'font_size' => 9],
        ];
        $characterFields = [
            'period_from' => ['top' => 141, 'left' => 202, 'step' => 13.5, 'width' => 13, 'font_size' => 12, 'limit' => 10],
            'period_to' => ['top' => 141, 'left' => 532, 'step' => 13.5, 'width' => 13, 'font_size' => 12, 'limit' => 10],
            'payee_tin' => ['top' => 181, 'left' => 276, 'step' => 19, 'width' => 18, 'font_size' => 12, 'limit' => 15],
            'payer_tin' => ['top' => 336, 'left' => 277, 'step' => 19, 'width' => 18, 'font_size' => 12, 'limit' => 17],
            'payee_address' => ['top' => 256, 'left' => 45, 'step' => 6, 'width' => 8, 'font_size' => 10, 'limit' => 112],
            'payer_address' => ['top' => 410, 'left' => 45, 'step' => 6, 'width' => 8, 'font_size' => 10, 'limit' => 112],
        ];
        foreach ($characterFields as $field => $position) {
            for ($index = 0; $index < $position['limit']; $index++) {
                $layout[$field.'_char_'.$index] = [
                    'top' => $position['top'],
                    'left' => round($position['left'] + ($index * $position['step']), 2),
                    'width' => $position['width'],
                    'font_size' => $position['font_size'],
                ];
            }
        }
        $columns = [
            'description' => ['left' => 27, 'width' => 208],
            'atc' => ['left' => 236, 'width' => 57],
            'month_1' => ['left' => 294, 'width' => 96],
            'month_2' => ['left' => 391, 'width' => 97],
            'month_3' => ['left' => 489, 'width' => 96],
            'row_total' => ['left' => 585, 'width' => 96],
            'row_tax' => ['left' => 681, 'width' => 113],
        ];

        for ($row = 0; $row < 10; $row++) {
            $top = round(488 + ($row * 18.25), 2);
            foreach ($columns as $column => $position) {
                $layout['detail_'.$row.'_'.$column] = [
                    'top' => $top,
                    'left' => $position['left'],
                    'width' => $position['width'],
                    'font_size' => $column === 'description' ? 7 : 9,
                ];
            }
        }

        foreach (['month_1', 'month_2', 'month_3', 'row_total', 'row_tax'] as $column) {
            $layout['grand_'.$column] = [
                'top' => 670,
                'left' => $columns[$column]['left'],
                'width' => $columns[$column]['width'],
                'font_size' => 10,
            ];
        }

        return $layout;
    }

    private function vendorBill2307PrintLayout()
    {
        $defaults = $this->defaultVendorBill2307PrintLayout();
        if (!Schema::hasTable('print_layouts')) {
            return $defaults;
        }

        $saved = DB::table('print_layouts')
            ->where('company_id', $this->defaultCompanyId())
            ->where('document_type', 'bir_2307')
            ->where('layout_name', 'Default')
            ->first();

        if (!$saved) {
            return $defaults;
        }

        return $this->sanitizeVendorBill2307PrintLayout(json_decode($saved->positions ?: '{}', true) ?: []);
    }

    private function sanitizeVendorBill2307PrintLayout($layout)
    {
        $defaults = $this->defaultVendorBill2307PrintLayout();
        $layout = is_array($layout) ? $layout : [];
        $clean = [];
        foreach ($defaults as $field => $fallback) {
            $position = isset($layout[$field]) && is_array($layout[$field]) ? $layout[$field] : [];
            $clean[$field] = [
                'top' => round(max(-50, min(1298, (float) ($position['top'] ?? $fallback['top']))), 2),
                'left' => round(max(-100, min(916, (float) ($position['left'] ?? $fallback['left']))), 2),
                'width' => round(max(4, min(816, (float) ($position['width'] ?? $fallback['width']))), 2),
                'font_size' => round(max(6, min(24, (float) ($position['font_size'] ?? $fallback['font_size']))), 1),
            ];
        }

        return $clean;
    }

    private function zipCodeFromAddress($address)
    {
        return preg_match('/\b(\d{4})\b/', (string) $address, $matches) ? $matches[1] : '';
    }

    private function bir2307IncomePaymentDescription($atcCode, $fallback)
    {
        $descriptions = [
            'WC100' => 'Rentals of real or personal property used in business',
            'WC158' => 'Income payments made by top withholding agents to local/resident suppliers of goods',
            'WC160' => 'Income payments made by top withholding agents to local/resident suppliers of services',
            'WI011' => 'Professional fees for services rendered by individuals',
            'WI139' => 'Brokerage fees paid to individual brokers',
        ];

        $atcCode = strtoupper(trim((string) $atcCode));

        return $descriptions[$atcCode] ?? ($fallback ?: 'Vendor bill payment');
    }

    private function latestPaymentIdForBill($billNo)
    {
        if (!$billNo || !Schema::hasTable('payments')) {
            return null;
        }

        return DB::table('payments')
            ->where('company_id', $this->defaultCompanyId())
            ->where('bill_no', $billNo)
            ->whereNotIn('status', ['voided', 'cancelled'])
            ->whereNotIn('approval_status', ['Voided', 'Cancelled'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->value('id');
    }

    private function paymentArrayById($id)
    {
        $payment = DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->select('payments.*', 'suppliers.code as supplier_code', 'suppliers.name as supplier_name')
            ->where('payments.company_id', $this->defaultCompanyId())
            ->where('payments.id', $id)
            ->first();

        return [
            'id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'voucher_no' => $payment->voucher_no ?? null,
            'supplier_id' => $payment->supplier_code ?? $payment->supplier_id,
            'supplier_name' => $payment->supplier_name,
            'payee_name' => $payment->payee_snapshot ?: $payment->supplier_name,
            'shipment_job_id' => $payment->shipment_job_id,
            'bill_no' => $payment->bill_no,
            'payment_date' => $payment->payment_date,
            'payment_method' => $payment->payment_method,
            'bank_account' => $payment->bank_account,
            'check_no' => $payment->check_no,
            'currency' => $payment->currency,
            'amount' => number_format((float) $payment->amount, 2),
            'approval_status' => $payment->approval_status ?? $payment->status,
        ];
    }

    private function storeDocumentAttachments($documentType, $documentId, $files)
    {
        if (!Schema::hasTable('document_attachments') || empty($files)) {
            return;
        }

        foreach ((array) $files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $path = $file->store('ap-attachments');
            DB::table('document_attachments')->insert([
                'document_type' => $documentType,
                'document_id' => $documentId,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function documentAttachments($documentType, $documentId)
    {
        if (!Schema::hasTable('document_attachments')) {
            return collect();
        }

        return DB::table('document_attachments')
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->orderBy('id')
            ->get();
    }

    private function nextDocumentNumber($documentType, $prefix, $padding = 5, $increment = true)
    {
        $companyId = $this->defaultCompanyId();

        if (!Schema::hasTable('number_series')) {
            return $prefix.date('YmdHis');
        }

        $series = DB::table('number_series')
            ->where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->lockForUpdate()
            ->first();

        if (!$series) {
            DB::table('number_series')->insert([
                'company_id' => $companyId,
                'document_type' => $documentType,
                'prefix' => $prefix,
                'current_number' => 0,
                'padding' => $padding,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $series = DB::table('number_series')
                ->where('company_id', $companyId)
                ->where('document_type', $documentType)
                ->first();
        }

        $next = ((int) $series->current_number) + 1;
        $seriesPrefix = $series->prefix ?: $prefix;
        $seriesPadding = (int) ($series->padding ?: $padding);
        $number = $seriesPrefix.str_pad($next, $seriesPadding, '0', STR_PAD_LEFT);
        while ($this->documentNumberExists($documentType, $companyId, $number)) {
            $next++;
            $number = $seriesPrefix.str_pad($next, $seriesPadding, '0', STR_PAD_LEFT);
        }

        if ($increment) {
            DB::table('number_series')->where('id', $series->id)->update([
                'current_number' => $next,
                'updated_at' => now(),
            ]);
        }

        return $number;
    }

    private function documentNumberExists($documentType, $companyId, $number)
    {
        $targets = [
            'vendor' => ['suppliers', 'code'],
            'vendor_bill' => ['purchase_documents', 'document_no'],
            'payment_voucher' => ['payments', 'payment_no'],
            'check_voucher' => ['payments', 'voucher_no'],
            'journal_voucher' => ['journal_entries', 'journal_no'],
        ];

        if (!isset($targets[$documentType]) || !Schema::hasTable($targets[$documentType][0])) {
            return false;
        }

        list($table, $column) = $targets[$documentType];
        if (!Schema::hasColumn($table, $column)) {
            return false;
        }

        return DB::table($table)
            ->where('company_id', $companyId)
            ->where($column, $number)
            ->exists();
    }

    private function syncDocumentNumberSeries($documentType, $number)
    {
        if (!Schema::hasTable('number_series')) {
            return;
        }

        $series = DB::table('number_series')
            ->where('company_id', $this->defaultCompanyId())
            ->where('document_type', $documentType)
            ->first();

        if (!$series || !$series->prefix) {
            return;
        }

        $prefix = (string) $series->prefix;
        if (strpos($number, $prefix) !== 0) {
            return;
        }

        $suffix = substr($number, strlen($prefix));
        if ($suffix === '' || !ctype_digit($suffix)) {
            return;
        }

        $currentNumber = (int) $suffix;
        if ($currentNumber > (int) $series->current_number) {
            DB::table('number_series')->where('id', $series->id)->update([
                'current_number' => $currentNumber,
                'updated_at' => now(),
            ]);
        }
    }

    private function tablePayload($table, array $payload)
    {
        return array_filter($payload, function ($value, $column) use ($table) {
            return Schema::hasColumn($table, $column);
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function amountToWords($amount, $currency)
    {
        $rounded = round(max(0, (float) $amount), 2);
        $whole = (int) floor($rounded);
        $cents = (int) round(($rounded - $whole) * 100);
        if ($cents >= 100) {
            $whole++;
            $cents = 0;
        }
        $major = strtoupper($currency) === 'USD' ? 'Dollars' : 'Pesos';
        $words = $this->numberToWords($whole).' '.$major;

        return $words.' and '.str_pad((string) $cents, 2, '0', STR_PAD_LEFT).'/100 Only';
    }

    private function numberToWords($number)
    {
        $number = (int) $number;
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion', 'Quadrillion'];
        $parts = [];
        $scale = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk) {
                $chunkWords = [];
                if ($chunk >= 100) {
                    $chunkWords[] = $ones[(int) floor($chunk / 100)].' Hundred';
                    $chunk %= 100;
                }
                if ($chunk >= 20) {
                    $chunkWords[] = $tens[(int) floor($chunk / 10)];
                    $chunk %= 10;
                }
                if ($chunk > 0) {
                    $chunkWords[] = $ones[$chunk];
                }
                $parts[] = trim(implode(' ', $chunkWords).' '.$scales[$scale]);
            }
            $number = (int) floor($number / 1000);
            $scale++;
        }

        return implode(' ', array_reverse($parts));
    }

    public function cashbook()
    {
        $accounts = $this->bankAccounts();
        $transactions = $this->cashbookRows();

        return view('erp.banking.cashbook', compact('accounts', 'transactions'));
    }

    public function bankTransactionCreate()
    {
        return view('erp.banking.transaction-create', [
            'accounts' => $this->bankAccounts(),
        ]);
    }

    public function bankTransactionReview()
    {
        return view('erp.banking.transaction-review', [
            'transaction' => request()->only([
                'transaction_no',
                'bank_account',
                'transaction_date',
                'transaction_type',
                'reference_no',
                'shipment_job_id',
                'currency',
                'exchange_rate',
                'foreign_amount',
                'base_amount',
                'description',
            ]),
        ]);
    }

    public function bankReconciliation()
    {
        $rows = collect($this->cashbookRows())
            ->filter(function ($row) { return $row[8] !== 'Reconciled'; })
            ->map(function ($row) {
                return [$row[0], $row[3], $row[2], $row[4], number_format($row[5] > 0 ? $row[5] : $row[6], 2), 'Outstanding in bank reconciliation'];
            })->values()->all();

        return view('erp.banking.reconciliation', [
            'accounts' => $this->bankAccounts(),
            'rows' => $rows,
        ]);
    }

    public function bankReconciliationReview()
    {
        return view('erp.banking.reconciliation-review', [
            'recon' => request()->only([
                'bank_account',
                'statement_date',
                'book_balance',
                'bank_balance',
                'outstanding_deposits',
                'outstanding_withdrawals',
                'adjusted_balance',
            ]),
        ]);
    }

    public function fxTracking()
    {
        $rows = [
            ['2026-05-23', 'SOAA-2026-00211', 'USD', 1840, 58.000000, 106720, 'Deposit conversion'],
            ['2026-05-21', 'BILL-7710', 'USD', -1325, 58.250000, -77162.50, 'AP payment conversion'],
            ['2026-05-20', 'BANK-FX-0007', 'USD', 5000, 58.100000, 290500, 'USD to PHP transfer'],
        ];

        return view('erp.banking.fx-tracking', compact('rows'));
    }

    public function multiCurrencyFx()
    {
        return view('erp.fx.index', [
            'rates' => $this->fxRates(),
            'exposures' => $this->fxExposures(),
            'gainsLosses' => $this->fxGainLossRows(),
        ]);
    }

    public function fxConversionCreate()
    {
        return view('erp.fx.conversion');
    }

    public function fxConversionReview()
    {
        $foreignAmount = (float) request('foreign_amount', 0);
        $documentRate = (float) request('document_rate', 1);
        $settlementRate = (float) request('settlement_rate', 1);
        $bookValue = $foreignAmount * $documentRate;
        $settlementValue = $foreignAmount * $settlementRate;

        return view('erp.fx.conversion-review', [
            'conversion' => request()->only([
                'reference_no',
                'shipment_job_id',
                'source_currency',
                'target_currency',
                'foreign_amount',
                'document_rate',
                'settlement_rate',
                'conversion_date',
                'conversion_type',
                'party_name',
            ]),
            'bookValue' => $bookValue,
            'settlementValue' => $settlementValue,
            'gainLoss' => $settlementValue - $bookValue,
        ]);
    }

    public function fxGainsLosses()
    {
        return view('erp.fx.gains-losses', [
            'rows' => $this->fxGainLossRows(),
            'exposures' => $this->fxExposures(),
        ]);
    }

    private function fxRates()
    {
        return [
            ['2026-05-24', 'USD', 'PHP', 58.150000, 'BSP Closing', 'Active'],
            ['2026-05-23', 'USD', 'PHP', 58.000000, 'Bank Settlement', 'Posted'],
            ['2026-05-21', 'USD', 'PHP', 58.250000, 'AP Payment', 'Posted'],
        ];
    }

    private function fxExposures()
    {
        return [
            ['Accounts Receivable', 'Blue Anchor Logistics', 'SOAA-2026-00211', 'JOB-SEA-2026-0398', 'USD', 1840.00, 58.000000, 58.150000, 276.00],
            ['Accounts Payable', 'Pacific Fuel Services', 'BILL-7710', 'JOB-SEA-2026-0398', 'USD', -1325.00, 58.250000, 58.150000, 132.50],
            ['Bank Account', 'Metrobank USD Collections', 'BANK-FX-0007', '-', 'USD', 5000.00, 58.100000, 58.150000, 250.00],
        ];
    }

    private function fxGainLossRows()
    {
        return [
            ['2026-05-24', 'Unrealized', 'SOAA-2026-00211', 'JOB-SEA-2026-0398', 'USD', 1840.00, 58.000000, 58.150000, 276.00, 'Gain'],
            ['2026-05-23', 'Realized', 'OR-2026-00618', 'JOB-SEA-2026-0398', 'USD', 1840.00, 57.900000, 58.000000, 184.00, 'Gain'],
            ['2026-05-21', 'Realized', 'BILL-7710', 'JOB-SEA-2026-0398', 'USD', -1325.00, 58.250000, 58.150000, 132.50, 'Gain'],
            ['2026-05-20', 'Unrealized', 'BANK-FX-0007', '-', 'USD', 5000.00, 58.100000, 58.150000, 250.00, 'Gain'],
        ];
    }

    public function dailyCashPosition()
    {
        $accounts = $this->bankAccounts();
        $summary = [];
        $today = date('Y-m-d');
        foreach (collect($accounts)->groupBy('currency') as $currency => $currencyAccounts) {
            $bankIds = $currencyAccounts->pluck('id')->all();
            $deposits = (float) DB::table('bank_transactions')->whereIn('bank_account_id', $bankIds)->where('transaction_date', $today)->sum('debit');
            $withdrawals = (float) DB::table('bank_transactions')->whereIn('bank_account_id', $bankIds)->where('transaction_date', $today)->sum('credit');
            $ending = $currencyAccounts->sum(function ($account) { return (float) str_replace(',', '', $account['book_balance']); });
            $summary[] = [$currency, 'Opening Cash', $ending - $deposits + $withdrawals];
            $summary[] = [$currency, 'Deposits', $deposits];
            $summary[] = [$currency, 'Withdrawals', -$withdrawals];
            $summary[] = [$currency, 'Ending Cash', $ending];
        }

        return view('erp.banking.daily-cash-position', compact('accounts', 'summary'));
    }

    private function bankAccounts()
    {
        if (!Schema::hasTable('bank_accounts')) {
            return [];
        }

        return DB::table('bank_accounts')
            ->where('company_id', $this->defaultCompanyId())
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get()
            ->map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'code' => $bank->account_no,
                    'name' => $bank->account_name,
                    'bank' => $bank->bank_name,
                    'account_no' => $bank->account_no,
                    'currency' => $bank->currency ?: 'PHP',
                    'book_balance' => number_format((float) $bank->book_balance, 2),
                    'bank_balance' => number_format((float) ($bank->bank_balance ?? $bank->book_balance), 2),
                ];
            })
            ->all();
    }

    private function cashbookRows()
    {
        if (!Schema::hasTable('bank_transactions')) {
            return [];
        }

        $records = DB::table('bank_transactions')
            ->join('bank_accounts', 'bank_accounts.id', '=', 'bank_transactions.bank_account_id')
            ->where('bank_accounts.company_id', $this->defaultCompanyId())
            ->select('bank_transactions.*', 'bank_accounts.account_name', 'bank_accounts.book_balance')
            ->orderBy('bank_transactions.transaction_date', 'desc')
            ->orderBy('bank_transactions.id', 'desc')
            ->get();
        $running = [];
        $rows = [];
        foreach ($records as $record) {
            if (!array_key_exists($record->bank_account_id, $running)) {
                $running[$record->bank_account_id] = (float) $record->book_balance;
            }
            $rows[] = [
                $record->transaction_date,
                $record->account_name,
                ucfirst($record->transaction_type),
                $record->reference_no ?: $record->transaction_no,
                $record->currency ?: 'PHP',
                (float) $record->debit,
                (float) $record->credit,
                $running[$record->bank_account_id],
                $record->is_reconciled ? 'Reconciled' : 'For Reconciliation',
            ];
            $running[$record->bank_account_id] -= (float) $record->debit - (float) $record->credit;
        }

        return $rows;
    }

    public function chartOfAccounts()
    {
        $accounts = [];

        if (Schema::hasTable('accounts')) {
            $accounts = DB::table('accounts')
                ->leftJoin('account_categories', 'account_categories.id', '=', 'accounts.account_category_id')
                ->leftJoin('accounts as parent_accounts', 'parent_accounts.id', '=', 'accounts.parent_id')
                ->select(
                    'accounts.id',
                    'accounts.code',
                    'accounts.name',
                    'accounts.type',
                    'account_categories.name as category',
                    'parent_accounts.code as parent_code',
                    'account_categories.normal_balance',
                    'accounts.opening_balance',
                    'accounts.show_in_ap',
                    'accounts.is_active'
                )
                ->orderBy('accounts.code')
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'code' => $account->code,
                        'name' => $account->name,
                        'type' => $account->type,
                        'category' => $account->category ?: '-',
                        'parent_code' => $account->parent_code ?: '-',
                        'normal_balance' => ucfirst($account->normal_balance ?: '-'),
                        'opening_balance' => number_format((float) $account->opening_balance, 2),
                        'show_in_ap' => (bool) $account->show_in_ap,
                        'is_active' => (bool) $account->is_active,
                    ];
                })
                ->all();
        }

        return view('erp.accounting.chart-of-accounts', compact('accounts'));
    }

    private function accountCategoryOptions()
    {
        if (!Schema::hasTable('account_categories')) {
            return [];
        }

        return DB::table('account_categories')->orderBy('name')->get();
    }

    private function accountParentOptions($excludeId = null)
    {
        if (!Schema::hasTable('accounts')) {
            return [];
        }

        return DB::table('accounts')
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '<>', $excludeId);
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function accountTypeOptions()
    {
        return [
            'Bank', 'Accounts Receivable', 'Other Current Asset', 'Fixed Asset', 'Other Asset',
            'Accounts Payable', 'Credit Card', 'Other Current Liability', 'Long Term Liability',
            'Equity', 'Income', 'Cost of Services', 'Cost of Goods Sold', 'Expense', 'Other Expense',
        ];
    }

    public function chartOfAccountsCreate()
    {
        return view('erp.accounting.chart-of-accounts-create', [
            'categories' => $this->accountCategoryOptions(),
            'parentAccounts' => $this->accountParentOptions(),
            'typeOptions' => $this->accountTypeOptions(),
        ]);
    }

    private function validateAccountData($accountId = null)
    {
        return request()->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'account_category_id' => 'required|exists:account_categories,id',
            'parent_id' => 'nullable|exists:accounts,id',
            'type' => 'required|string|max:100',
            'show_in_ap' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);
    }

    public function chartOfAccountsStore()
    {
        $data = $this->validateAccountData();
        $companyId = $this->defaultCompanyId();
        $code = trim($data['code']);

        $exists = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['code' => 'This account code already exists.']);
        }

        $showInAp = isset($data['show_in_ap']) && in_array($data['type'], $this->apItemAccountTypes(), true);

        DB::table('accounts')->insert([
            'company_id' => $companyId,
            'account_category_id' => $data['account_category_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'code' => $code,
            'name' => trim($data['name']),
            'type' => $data['type'],
            'show_in_ap' => $showInAp,
            'opening_balance' => $this->moneyToFloat($data['opening_balance'] ?? 0),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('erp.accounting.coa')
            ->with('status', 'Account '.$code.' was created.');
    }

    public function chartOfAccountsEdit($account)
    {
        $record = DB::table('accounts')->where('id', $account)->first();
        abort_unless($record, 404);

        return view('erp.accounting.chart-of-accounts-create', [
            'account' => $record,
            'isEdit' => true,
            'categories' => $this->accountCategoryOptions(),
            'parentAccounts' => $this->accountParentOptions($record->id),
            'typeOptions' => $this->accountTypeOptions(),
        ]);
    }

    public function chartOfAccountsUpdate($account)
    {
        $record = DB::table('accounts')->where('id', $account)->first();
        abort_unless($record, 404);

        $data = $this->validateAccountData($record->id);
        $companyId = $this->defaultCompanyId();
        $code = trim($data['code']);

        $exists = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('id', '<>', $record->id)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['code' => 'This account code already exists.']);
        }

        if ((int) ($data['parent_id'] ?? 0) === (int) $record->id) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['parent_id' => 'An account cannot be its own parent.']);
        }

        $showInAp = isset($data['show_in_ap']) && in_array($data['type'], $this->apItemAccountTypes(), true);

        DB::table('accounts')->where('id', $record->id)->update([
            'account_category_id' => $data['account_category_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'code' => $code,
            'name' => trim($data['name']),
            'type' => $data['type'],
            'show_in_ap' => $showInAp,
            'opening_balance' => $this->moneyToFloat($data['opening_balance'] ?? 0),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('erp.accounting.coa')
            ->with('status', 'Account '.$code.' was updated.');
    }

    public function chartOfAccountsToggleStatus($account)
    {
        $record = DB::table('accounts')->where('id', $account)->first();
        abort_unless($record, 404);

        DB::table('accounts')->where('id', $record->id)->update([
            'is_active' => !$record->is_active,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('erp.accounting.coa')
            ->with('status', 'Account '.$record->code.' was '.($record->is_active ? 'deactivated' : 'activated').'.');
    }

    public function journalEntries()
    {
        return view('erp.accounting.journal-entries', [
            'accounts' => $this->glAccountOptions(),
            'nextJournalNo' => $this->peekNextDocumentNumber('journal_voucher', 'JV-', 5),
            'recentEntries' => $this->recentJournalEntries(),
        ]);
    }

    public function journalEntryReview()
    {
        $data = request()->validate([
            'journal_no' => 'required|string|max:100',
            'journal_date' => 'required|date',
            'shipment_job_id' => 'nullable|string|max:100',
            'reference_no' => 'nullable|string|max:100',
            'memo' => 'nullable|string|max:2000',
            'line_account' => 'required|array|min:2',
            'line_account.*' => 'nullable|integer',
            'line_description' => 'nullable|array',
            'line_description.*' => 'nullable|string|max:255',
            'line_debit' => 'nullable|array',
            'line_debit.*' => 'nullable|string|max:50',
            'line_credit' => 'nullable|array',
            'line_credit.*' => 'nullable|string|max:50',
        ]);

        $accountIds = (array) ($data['line_account'] ?? []);
        $debits = (array) ($data['line_debit'] ?? []);
        $credits = (array) ($data['line_credit'] ?? []);
        $descriptions = (array) ($data['line_description'] ?? []);

        $lines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accountIds as $index => $accountId) {
            $accountId = (int) $accountId;
            $debit = $this->moneyToFloat($debits[$index] ?? 0);
            $credit = $this->moneyToFloat($credits[$index] ?? 0);

            if (!$accountId || ($debit <= 0 && $credit <= 0)) {
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                return back()->withInput()->withErrors(['lines' => 'Each journal line must have either a debit or a credit, not both.']);
            }

            $lines[] = [
                'account_id' => $accountId,
                'description' => $descriptions[$index] ?? null,
                'debit' => $debit,
                'credit' => $credit,
            ];
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (count($lines) < 2) {
            return back()->withInput()->withErrors(['lines' => 'A journal entry needs at least two lines (one debit, one credit).']);
        }
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->withErrors(['lines' => 'Total debits ('.number_format($totalDebit, 2).') must equal total credits ('.number_format($totalCredit, 2).') before this entry can post.']);
        }
        if (round($totalDebit, 2) <= 0) {
            return back()->withInput()->withErrors(['lines' => 'The journal entry amount must be greater than zero.']);
        }

        $companyId = $this->defaultCompanyId();
        $validAccountIds = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('id', array_unique(array_column($lines, 'account_id')))
            ->pluck('id')
            ->map(function ($id) { return (int) $id; })
            ->all();
        $invalidAccountIds = array_diff(array_unique(array_column($lines, 'account_id')), $validAccountIds);
        if (!empty($invalidAccountIds)) {
            return back()->withInput()->withErrors(['lines' => 'One or more selected accounts are inactive or do not belong to this company.']);
        }

        $this->authorizeAccountingPosting('accounting', 'approve');
        app(PeriodLockService::class)->assertOpen($companyId, $data['journal_date']);
        $journalNo = trim((string) $data['journal_no']);
        if (DB::table('journal_entries')->where('company_id', $companyId)->where('journal_no', $journalNo)->exists()) {
            return back()->withInput()->withErrors(['journal_no' => 'This journal number already exists.']);
        }

        $journalId = DB::transaction(function () use ($companyId, $journalNo, $lines, $data) {
            $id = DB::table('journal_entries')->insertGetId($this->tablePayload('journal_entries', [
                'company_id' => $companyId,
                'branch_id' => DB::table('branches')->where('company_id', $companyId)->orderBy('id')->value('id'),
                'journal_no' => $journalNo,
                'journal_date' => $data['journal_date'],
                'source_module' => 'GL',
                'reference_no' => $data['reference_no'] ?? null,
                'shipment_job_id' => $data['shipment_job_id'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => 'posted',
                'created_by' => auth()->id(),
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach ($lines as $line) {
                DB::table('journal_entry_lines')->insert([
                    'journal_entry_id' => $id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $id;
        });

        app(ActivityLogger::class)->log('posted', 'journal_entry', $journalId, [], [
            'journal_no' => $journalNo,
            'source_module' => 'GL',
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ], $companyId);

        return redirect()
            ->route('erp.accounting.journals.show', $journalId)
            ->with('status', 'Journal entry '.$journalNo.' was posted successfully.');
    }

    public function journalEntryShow($journal)
    {
        $entry = DB::table('journal_entries')
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $journal)
            ->first();
        abort_unless($entry, 404);

        $lines = DB::table('journal_entry_lines')
            ->leftJoin('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entry_id', $entry->id)
            ->select('journal_entry_lines.*', 'accounts.code as account_code', 'accounts.name as account_name')
            ->orderBy('journal_entry_lines.id')
            ->get();

        return view('erp.accounting.journal-review', [
            'journal' => $entry,
            'lines' => $lines,
            'totalDebit' => (float) $lines->sum('debit'),
            'totalCredit' => (float) $lines->sum('credit'),
        ]);
    }

    private function peekNextDocumentNumber($documentType, $prefix, $padding = 5)
    {
        return $this->nextDocumentNumber($documentType, $prefix, $padding, false);
    }

    private function recentJournalEntries()
    {
        if (!Schema::hasTable('journal_entries')) {
            return collect();
        }

        return DB::table('journal_entries')
            ->where('company_id', $this->defaultCompanyId())
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($entry) {
                $totals = DB::table('journal_entry_lines')
                    ->where('journal_entry_id', $entry->id)
                    ->selectRaw('SUM(debit) as debit_total, SUM(credit) as credit_total')
                    ->first();

                return (object) [
                    'id' => $entry->id,
                    'journal_no' => $entry->journal_no,
                    'journal_date' => $entry->journal_date,
                    'memo' => $entry->memo,
                    'status' => $entry->status,
                    'amount' => (float) ($totals->debit_total ?? 0),
                ];
            });
    }

    public function balanceSheet()
    {
        $asOf = request('as_of', date('Y-m-d'));
        $balances = $this->glAccountBalances(null, $asOf);
        $company = $this->reportingCompany();

        $assets = $this->glStatementRows($balances, 'Assets');
        $liabilities = $this->glStatementRows($balances, 'Liabilities');
        $equity = $this->glStatementRows($balances, 'Equity');
        $netIncome = $this->glNetIncome(null, $asOf);

        $rows = [];
        foreach ($assets as $row) {
            $rows[] = ['Assets', $row['label'], $row['amount']];
        }
        $totalAssets = array_sum(array_column($assets, 'amount'));
        $rows[] = ['Assets', 'Total Assets', $totalAssets];

        foreach ($liabilities as $row) {
            $rows[] = ['Liabilities', $row['label'], $row['amount']];
        }
        $totalLiabilities = array_sum(array_column($liabilities, 'amount'));
        $rows[] = ['Liabilities', 'Total Liabilities', $totalLiabilities];

        foreach ($equity as $row) {
            $rows[] = ['Equity', $row['label'], $row['amount']];
        }
        $rows[] = ['Equity', 'Current Year Earnings', $netIncome];
        $totalEquity = array_sum(array_column($equity, 'amount')) + $netIncome;
        $rows[] = ['Equity', 'Total Liabilities and Equity', $totalLiabilities + $totalEquity];

        return $this->statement('Balance Sheet', $rows, [
            'company' => $company,
            'periodLabel' => 'As of '.date('F d, Y', strtotime($asOf)),
            'tieOut' => [
                'label' => 'Assets vs. Liabilities + Equity',
                'left' => $totalAssets,
                'right' => $totalLiabilities + $totalEquity,
                'balanced' => round($totalAssets, 2) === round($totalLiabilities + $totalEquity, 2),
            ],
        ]);
    }

    public function profitLoss()
    {
        return $this->incomeStatementLike('Profit and Loss');
    }

    public function incomeStatement()
    {
        return $this->incomeStatementLike('Income Statement');
    }

    private function incomeStatementLike($title)
    {
        $period = request('period', date('Y-m'));
        list($start, $end) = $this->trialBalanceDateRange($period);
        $balances = $this->glAccountBalances($start, $end);
        $company = $this->reportingCompany();

        $revenue = $this->glStatementRows($balances, 'Revenue', 'period');
        $expenses = $this->glStatementRows($balances, 'Expenses', 'period');

        $rows = [];
        foreach ($revenue as $row) {
            $rows[] = ['Revenue', $row['label'], $row['amount']];
        }
        $totalRevenue = array_sum(array_column($revenue, 'amount'));
        $rows[] = ['Revenue', 'Total Revenue', $totalRevenue];

        foreach ($expenses as $row) {
            $rows[] = ['Expenses', $row['label'], -$row['amount']];
        }
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $rows[] = ['Expenses', 'Total Expenses', -$totalExpenses];

        $rows[] = ['Net Income', 'Net Income Before Tax', $totalRevenue - $totalExpenses];

        return $this->statement($title, $rows, [
            'company' => $company,
            'periodLabel' => 'For the period '.date('F d, Y', strtotime($start ?: date('Y-m-01'))).' to '.date('F d, Y', strtotime($end ?: date('Y-m-t'))),
        ]);
    }

    public function cashFlow()
    {
        $period = request('period', date('Y-m'));
        list($start, $end) = $this->trialBalanceDateRange($period);
        $company = $this->reportingCompany();

        $cashAccounts = $this->trialBalanceAccounts()->filter(function ($account) {
            return strtolower((string) $account->type) === 'bank';
        });
        $accountIds = $cashAccounts->pluck('id')->all();

        $beginningBalances = $this->glAccountBalances(null, $start ? date('Y-m-d', strtotime($start.' -1 day')) : date('Y-m-d'));
        $endingBalances = $this->glAccountBalances(null, $end ?: date('Y-m-d'));

        $beginningCash = 0.0;
        $endingCash = 0.0;
        foreach ($accountIds as $id) {
            $beginningCash += (float) ($beginningBalances[$id]['ending'] ?? 0);
            $endingCash += (float) ($endingBalances[$id]['ending'] ?? 0);
        }

        $netMovement = $endingCash - $beginningCash;
        $receipts = Schema::hasTable('receipts')
            ? (float) DB::table('receipts')
                ->where('company_id', $this->defaultCompanyId())
                ->whereNotIn('status', ['cancelled', 'voided'])
                ->when($start, function ($q) use ($start) { $q->where('receipt_date', '>=', $start); })
                ->when($end, function ($q) use ($end) { $q->where('receipt_date', '<=', $end); })
                ->sum('amount')
            : 0.0;
        $disbursements = Schema::hasTable('payments')
            ? (float) DB::table('payments')
                ->where('company_id', $this->defaultCompanyId())
                ->whereNotIn('status', ['cancelled', 'voided'])
                ->when($start, function ($q) use ($start) { $q->where('payment_date', '>=', $start); })
                ->when($end, function ($q) use ($end) { $q->where('payment_date', '<=', $end); })
                ->sum('amount')
            : 0.0;
        $otherMovement = $netMovement - $receipts + $disbursements;

        $rows = [
            ['Beginning', 'Beginning Cash and Bank Balance', $beginningCash],
            ['Operating', 'Cash Receipts from Customers', $receipts],
            ['Operating', 'Cash Payments to Suppliers', -$disbursements],
        ];
        if (abs($otherMovement) >= 0.01) {
            $rows[] = ['Operating', 'Other Journal Cash Movement', $otherMovement];
        }
        $rows[] = ['Ending', 'Ending Cash and Bank Balance', $endingCash];

        return $this->statement('Cash Flow Statement', $rows, [
            'company' => $company,
            'periodLabel' => 'For the period '.date('F d, Y', strtotime($start ?: date('Y-m-01'))).' to '.date('F d, Y', strtotime($end ?: date('Y-m-t'))).' (direct method, cash and bank accounts only)',
        ]);
    }

    private function reportingCompany()
    {
        return Schema::hasTable('companies') ? DB::table('companies')->where('id', $this->defaultCompanyId())->first() : null;
    }

    private function glStatementRows(array $balances, $categoryName, $field = 'ending')
    {
        $rows = [];
        foreach ($balances as $balance) {
            if ($balance['account']->category_name !== $categoryName) {
                continue;
            }
            if (abs($balance[$field]) < 0.005) {
                continue;
            }
            $rows[] = [
                'label' => trim(($balance['account']->code ? $balance['account']->code.' - ' : '').$balance['account']->name),
                'amount' => $balance[$field],
            ];
        }

        return $rows;
    }

    private function glNetIncome($startDate, $endDate)
    {
        $balances = $this->glAccountBalances($startDate, $endDate);
        $field = $startDate ? 'period' : 'ending';
        $revenue = array_sum(array_column($this->glStatementRows($balances, 'Revenue', $field), 'amount'));
        $expenses = array_sum(array_column($this->glStatementRows($balances, 'Expenses', $field), 'amount'));

        return $revenue - $expenses;
    }

    private function glAccountBalances($startDate, $endDate)
    {
        if (!Schema::hasTable('accounts')) {
            return [];
        }

        $accounts = $this->trialBalanceAccounts();
        $normals = [];
        foreach ($accounts as $account) {
            $normals[$account->id] = $this->trialBalanceNormalBalance($account);
        }

        $beginningJournal = $startDate
            ? $this->trialBalanceJournalMovements(null, date('Y-m-d', strtotime($startDate.' -1 day')), $normals, true)
            : [];
        $beginningDocuments = $startDate
            ? $this->trialBalanceDocumentMovements(null, date('Y-m-d', strtotime($startDate.' -1 day')), $accounts, $normals)
            : $this->trialBalanceDocumentMovements(null, $endDate, $accounts, $normals);
        $beginningMovements = $this->mergeMovementMaps($beginningJournal, $this->flattenMovementsToTotal($beginningDocuments));

        $periodJournal = $this->trialBalanceJournalMovements($startDate, $endDate, $normals, true);
        $periodDocuments = $startDate ? $this->trialBalanceDocumentMovements($startDate, $endDate, $accounts, $normals) : [];
        $periodMovements = $this->mergeMovementMaps($periodJournal, $this->flattenMovementsToTotal($periodDocuments));

        $balances = [];
        foreach ($accounts as $account) {
            $opening = (float) ($account->opening_balance ?? 0) + $this->sumMovementColumns($beginningMovements[$account->id] ?? []);
            $period = $this->sumMovementColumns($periodMovements[$account->id] ?? []);
            $balances[$account->id] = [
                'account' => $account,
                'normal' => $normals[$account->id],
                'opening' => $opening,
                'period' => $period,
                'ending' => $opening + $period,
            ];
        }

        return $balances;
    }

    private function sumMovementColumns(array $columns)
    {
        return array_sum($columns);
    }

    private function statement($title, $rows, array $meta = [])
    {
        return view('erp.statements.show', array_merge([
            'title' => $title,
            'rows' => $rows,
            'company' => null,
            'periodLabel' => null,
            'tieOut' => null,
        ], $meta));
    }

    public function vatSummary()
    {
        list($start, $end) = $this->taxDateRange();
        $sales = $this->salesTaxRows($start, $end);
        $purchases = $this->purchaseTaxRows($start, $end);
        $outputVat = array_sum(array_column($sales, 7));
        $inputVat = array_sum(array_column($purchases, 7));

        return view('erp.tax.vat-summary', [
            'sales' => $sales,
            'purchases' => $purchases,
            'outputVat' => $outputVat,
            'inputVat' => $inputVat,
            'vatPayable' => $outputVat - $inputVat,
        ]);
    }

    private function taxDateRange()
    {
        $period = request('period');
        if ($period) {
            return $this->trialBalanceDateRange($period);
        }

        return [request('start_date'), request('end_date')];
    }

    public function withholdingTax()
    {
        $rows = [];

        if (Schema::hasTable('purchase_documents')) {
            foreach (DB::table('purchase_documents')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
                ->where('purchase_documents.company_id', $this->defaultCompanyId())
                ->whereNotIn('purchase_documents.status', ['cancelled', 'voided'])
                ->where('purchase_documents.withholding_tax_amount', '>', 0)
                ->orderBy('purchase_documents.document_date', 'desc')
                ->select(
                    'purchase_documents.document_date',
                    'purchase_documents.document_no',
                    'suppliers.name as party',
                    'purchase_documents.shipment_job_id',
                    'purchase_documents.currency',
                    'purchase_documents.gross_amount',
                    'purchase_documents.withholding_tax_rate',
                    'purchase_documents.withholding_tax_amount',
                    'purchase_documents.status'
                )
                ->get() as $bill) {
                $rows[] = [
                    $bill->document_date,
                    $bill->document_no,
                    'Expanded Withholding Tax',
                    $bill->party ?: 'Unknown Supplier',
                    $bill->shipment_job_id ?: '-',
                    $bill->currency ?: 'PHP',
                    (float) $bill->gross_amount,
                    number_format((float) $bill->withholding_tax_rate, 0).'%',
                    (float) $bill->withholding_tax_amount,
                    $bill->status === 'paid' ? 'Remitted' : 'For Remittance',
                ];
            }
        }

        if (Schema::hasTable('sales_documents')) {
            foreach (DB::table('sales_documents')
                ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
                ->where('sales_documents.company_id', $this->defaultCompanyId())
                ->whereNotIn('sales_documents.status', ['cancelled', 'voided'])
                ->where('sales_documents.withholding_tax_amount', '>', 0)
                ->orderBy('sales_documents.document_date', 'desc')
                ->select(
                    'sales_documents.document_date',
                    'sales_documents.document_no',
                    'customers.name as party',
                    'sales_documents.shipment_reference',
                    'sales_documents.currency',
                    'sales_documents.grand_total',
                    'sales_documents.withholding_tax_rate',
                    'sales_documents.withholding_tax_amount'
                )
                ->get() as $invoice) {
                $rows[] = [
                    $invoice->document_date,
                    $invoice->document_no,
                    'Creditable Withholding Tax',
                    $invoice->party ?: 'Unknown Customer',
                    $invoice->shipment_reference ?: '-',
                    $invoice->currency ?: 'PHP',
                    (float) $invoice->grand_total,
                    number_format((float) $invoice->withholding_tax_rate, 0).'%',
                    (float) $invoice->withholding_tax_amount,
                    'Creditable',
                ];
            }
        }

        usort($rows, function ($a, $b) {
            return strcmp((string) $b[0], (string) $a[0]);
        });

        return view('erp.tax.withholding', compact('rows'));
    }

    public function taxCalculator()
    {
        return view('erp.tax.calculator');
    }

    public function taxCalculatorReview()
    {
        $amount = (float) request('amount', 0);
        $vatClassification = request('vat_classification', '12% VAT');
        $withholdingRate = (float) request('withholding_tax_rate', 0);
        $vatRate = $vatClassification === '12% VAT' ? 0.12 : 0;
        $vatAmount = $amount * $vatRate;
        $withholdingAmount = $amount * ($withholdingRate / 100);

        return view('erp.tax.calculator-review', [
            'tax' => request()->only([
                'document_type',
                'reference_no',
                'shipment_job_id',
                'party_name',
                'vat_classification',
                'withholding_type',
                'withholding_tax_rate',
                'currency',
                'amount',
            ]),
            'vatAmount' => $vatAmount,
            'withholdingAmount' => $withholdingAmount,
            'netAmount' => $amount + $vatAmount - $withholdingAmount,
        ]);
    }

    public function taxSalesPurchases()
    {
        return view('erp.tax.sales-purchases', [
            'sales' => $this->salesTaxRows(),
            'purchases' => $this->purchaseTaxRows(),
        ]);
    }

    public function taxAuditTrail()
    {
        $logs = [
            ['2026-05-24 14:10', 'Maria Santos', 'Computed Output VAT', 'BI-2026-01042', 'JOB-SEA-2026-0422', 'Output VAT changed from 0.00 to 10,553.57'],
            ['2026-05-24 13:38', 'Ana Reyes', 'Reviewed EWT', 'BILL-4902', 'JOB-SEA-2026-0423', 'EWT 5% marked for remittance'],
            ['2026-05-23 16:22', 'Maria Santos', 'Tagged Zero-rated Sale', 'SOAA-2026-00211', 'JOB-SEA-2026-0398', 'VAT classification set to zero-rated export service'],
            ['2026-05-23 10:04', 'John Cruz', 'Updated Tax Code', 'VAT12', '-', 'Output VAT Payable account verified'],
        ];

        return view('erp.tax.audit-trail', compact('logs'));
    }

    private function salesTaxRows($startDate = null, $endDate = null)
    {
        if (!Schema::hasTable('sales_documents')) {
            return [];
        }

        $query = DB::table('sales_documents')
            ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->where('sales_documents.company_id', $this->defaultCompanyId())
            ->where('sales_documents.posting_status', 'posted')
            ->whereNotIn('sales_documents.status', ['cancelled', 'voided']);

        if ($startDate) {
            $query->where('sales_documents.document_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('sales_documents.document_date', '<=', $endDate);
        }

        return $query
            ->orderBy('sales_documents.document_date', 'desc')
            ->select(
                'sales_documents.document_date',
                'sales_documents.document_no',
                'customers.name as customer_name',
                'sales_documents.shipment_reference',
                'sales_documents.vat_classification',
                'sales_documents.currency',
                'sales_documents.vatable_amount',
                'sales_documents.zero_rated_amount',
                'sales_documents.vat_exempt_amount',
                'sales_documents.tax_total'
            )
            ->get()
            ->map(function ($document) {
                $classification = strtolower(str_replace('_', '-', (string) $document->vat_classification));
                if ($classification === 'zero-rated') {
                    $label = 'Zero-rated';
                    $base = (float) $document->zero_rated_amount;
                } elseif ($classification === 'exempt') {
                    $label = 'VAT-exempt';
                    $base = (float) $document->vat_exempt_amount;
                } else {
                    $label = '12% VAT';
                    $base = (float) $document->vatable_amount;
                }

                return [
                    $document->document_date,
                    $document->document_no,
                    $document->customer_name ?: 'Walk-in / Guest',
                    $document->shipment_reference ?: '-',
                    $label,
                    $document->currency ?: 'PHP',
                    $base,
                    (float) $document->tax_total,
                ];
            })
            ->all();
    }

    private function purchaseTaxRows($startDate = null, $endDate = null)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return [];
        }

        $query = DB::table('purchase_documents')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
            ->where('purchase_documents.company_id', $this->defaultCompanyId())
            ->where('purchase_documents.posting_status', 'posted')
            ->whereNotIn('purchase_documents.status', ['cancelled', 'voided']);

        if ($startDate) {
            $query->where('purchase_documents.document_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('purchase_documents.document_date', '<=', $endDate);
        }

        return $query
            ->orderBy('purchase_documents.document_date', 'desc')
            ->select(
                'purchase_documents.document_date',
                'purchase_documents.document_no',
                'suppliers.name as supplier_name',
                'purchase_documents.shipment_job_id',
                'purchase_documents.currency',
                'purchase_documents.vatable_amount',
                'purchase_documents.zero_rated_amount',
                'purchase_documents.vat_exempt_amount',
                'purchase_documents.tax_total'
            )
            ->get()
            ->map(function ($document) {
                if ((float) $document->vatable_amount > 0) {
                    $label = '12% VAT';
                    $base = (float) $document->vatable_amount;
                } elseif ((float) $document->zero_rated_amount > 0) {
                    $label = 'Zero-rated';
                    $base = (float) $document->zero_rated_amount;
                } elseif ((float) $document->vat_exempt_amount > 0) {
                    $label = 'VAT-exempt';
                    $base = (float) $document->vat_exempt_amount;
                } else {
                    $label = 'Untagged';
                    $base = 0.0;
                }

                return [
                    $document->document_date,
                    $document->document_no,
                    $document->supplier_name ?: 'Unknown Supplier',
                    $document->shipment_job_id ?: '-',
                    $label,
                    $document->currency ?: 'PHP',
                    $base,
                    (float) $document->tax_total,
                ];
            })
            ->all();
    }

    public function invoiceCreate()
    {
        return view('erp.invoices.create', [
            'customers' => $this->customerSelectOptions(),
            'invoiceTemplates' => $this->invoiceTemplates(),
            'items' => $this->invoiceItemOptions(),
        ]);
    }

    public function invoiceIndex()
    {
        $allInvoices = $this->billingRows();
        $totalAmount = 0.0;
        $postedAmount = 0.0;
        $postedCount = 0;
        $activeInvoiceCount = 0;

        foreach ($allInvoices as $invoice) {
            $amount = $this->moneyToFloat($invoice[6]);
            $status = strtolower($invoice[7]);

            if (in_array($status, ['cancelled', 'voided'], true)) {
                continue;
            }

            $activeInvoiceCount++;
            $totalAmount += $amount;

            if ($status === 'posted') {
                $postedCount++;
                $postedAmount += $amount;
            }
        }

        $isFinalInvoice = function ($invoice) {
            return in_array(strtolower((string) $invoice[7]), ['cancelled', 'voided'], true);
        };
        $finalInvoiceCount = count(array_filter($allInvoices, $isFinalInvoice));
        $selectedInvoiceTab = request('tab', 'active');
        if (!in_array($selectedInvoiceTab, ['active', 'voided'], true)) {
            $selectedInvoiceTab = 'active';
        }
        $invoices = array_values(array_filter($allInvoices, function ($invoice) use ($isFinalInvoice, $selectedInvoiceTab) {
            return $selectedInvoiceTab === 'voided' ? $isFinalInvoice($invoice) : !$isFinalInvoice($invoice);
        }));

        return view('erp.invoices.index', [
            'invoices' => $invoices,
            'selectedInvoiceTab' => $selectedInvoiceTab,
            'invoiceTabCounts' => [
                'active' => $activeInvoiceCount,
                'voided' => $finalInvoiceCount,
            ],
            'stats' => [
                ['label' => 'Invoices', 'value' => $activeInvoiceCount, 'icon' => 'bi-receipt', 'tone' => 'primary'],
                ['label' => 'Total Billed', 'value' => 'PHP '.number_format($totalAmount, 2), 'icon' => 'bi-cash-stack', 'tone' => 'success'],
                ['label' => 'Posted Amount', 'value' => 'PHP '.number_format($postedAmount, 2), 'icon' => 'bi-check2-square', 'tone' => 'warning'],
                ['label' => 'Posted', 'value' => $postedCount, 'icon' => 'bi-check2-circle', 'tone' => 'success'],
            ],
            'workflows' => [
                ['label' => 'Create invoice', 'route' => route('erp.invoices.create'), 'icon' => 'bi-plus-lg'],
                ['label' => 'Receive payment', 'route' => route('erp.payments.create'), 'icon' => 'bi-credit-card'],
            ],
            'agingBuckets' => $this->invoiceAgingBuckets(),
            'agingRows' => $this->invoiceAgingRows(),
            'paymentCustomers' => $this->paymentCustomerOptions(),
            'paymentInvoices' => $this->paymentInvoiceOptions(),
        ]);
    }

    public function invoiceShow($invoice)
    {
        $invoiceData = $this->billingPrintInvoiceDataFromReference($invoice);
        if (empty($invoiceData)) {
            abort(404);
        }

        $record = Schema::hasTable('sales_documents')
            ? DB::table('sales_documents')
                ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
                ->select(
                    'sales_documents.*',
                    'customers.name as customer_name',
                    'customers.customer_id as customer_code',
                    'customers.billing_address as customer_billing_address'
                )
                ->where('sales_documents.company_id', $this->defaultCompanyId())
                ->where(function ($query) use ($invoice) {
                    $this->applySalesDocumentReference($query, $invoice);
                })
                ->first()
            : null;

        $totals = $this->billingTotals($invoiceData);
        $selectedTemplates = (array) ($invoiceData['invoice_template'] ?? []);
        $printDocuments = $this->billingPrintDocuments($invoiceData['vat_classification'] ?? null, $totals, $selectedTemplates, $invoiceData);

        return view('erp.invoices.show', [
            'invoice' => $invoiceData,
            'record' => $record,
            'totals' => $totals,
            'printDocuments' => $printDocuments,
            'paymentCustomers' => $this->paymentCustomerOptions(),
            'paymentInvoices' => $this->paymentInvoiceOptions(),
        ]);
    }

    public function billingIndex()
    {
        return view('erp.billings.index', ['billings' => $this->billingRows()]);
    }

    public function billingEdit($invoice)
    {
        $record = $this->salesDocumentRecordByReference($invoice);
        abort_if(in_array(strtolower((string) $record->status), ['cancelled', 'voided'], true), 403, 'A cancelled or voided invoice cannot be edited.');

        return view('erp.invoices.create', [
            'isEdit' => true,
            'editingInvoice' => $this->billingPrintInvoiceDataFromReference($record->id),
            'customers' => $this->customerSelectOptions(),
            'invoiceTemplates' => $this->invoiceTemplates(),
            'items' => $this->invoiceItemOptions(),
        ]);
    }

    public function billingVoid($invoice)
    {
        $record = $this->salesDocumentRecordByReference($invoice);
        $hasPostedJournal = Schema::hasTable('journal_entries') && DB::table('journal_entries')
            ->where('company_id', $record->company_id)
            ->where('source_type', 'sales_document')
            ->where('source_id', $record->id)
            ->where('posting_type', 'original')
            ->where('status', 'posted')
            ->exists();
        $shouldVoid = in_array(strtolower((string) $record->status), ['posted', 'approved'], true)
            || in_array(($record->posting_status ?? 'unposted'), ['posted', 'reversed'], true)
            || $hasPostedJournal;

        return view('erp.billings.void', [
            'record' => $record,
            'billing' => $this->billingPrintInvoiceDataFromReference($record->id),
            'action' => strtolower((string) $record->status) === 'voided' || $shouldVoid ? 'void' : 'cancel',
            'willReverseJournal' => $hasPostedJournal,
        ]);
    }

    public function billingVoidStore($invoice)
    {
        $data = request()->validate([
            'reason' => 'required|string|min:5|max:1000',
        ]);
        $reason = trim($data['reason']);
        $this->authorizeAccountingPosting('invoices', 'delete');

        $record = DB::transaction(function () use ($invoice, $reason) {
            $record = $this->salesDocumentRecordByReference($invoice, true);
            $oldStatus = strtolower((string) ($record->status ?: 'draft'));
            if (in_array($oldStatus, ['cancelled', 'voided'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'This invoice is already '.str_replace('_', ' ', $oldStatus).'.',
                ]);
            }

            $activeApplications = Schema::hasTable('receipt_applications')
                ? DB::table('receipt_applications')
                    ->join('receipts', 'receipts.id', '=', 'receipt_applications.receipt_id')
                    ->where('receipt_applications.sales_document_id', $record->id)
                    ->whereNotIn('receipts.status', ['cancelled', 'voided'])
                    ->sum('receipt_applications.amount_applied')
                : 0;
            if ((float) $activeApplications > 0 || (float) $record->paid_total > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'Void or cancel the applied customer receipt before voiding this invoice.',
                ]);
            }

            $hasPostedJournal = Schema::hasTable('journal_entries') && DB::table('journal_entries')
                ->where('company_id', $record->company_id)
                ->where('source_type', 'sales_document')
                ->where('source_id', $record->id)
                ->where('posting_type', 'original')
                ->where('status', 'posted')
                ->exists();
            $wasPosted = in_array(strtolower((string) $record->status), ['posted', 'approved'], true)
                || ($record->posting_status ?? 'unposted') === 'posted'
                || $hasPostedJournal;
            $newStatus = $wasPosted ? 'voided' : 'cancelled';
            $reversal = null;
            if ($hasPostedJournal) {
                $reversal = app(PostingService::class)->reverseSource('sales_document', $record->id, $reason);
                if (!$reversal) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => 'The posted invoice has no posted journal entry to reverse.',
                    ]);
                }
            }

            $auditRemark = strtoupper($newStatus).' '.now()->format('Y-m-d H:i').': '.$reason;
            $remarks = trim(implode("\n", array_filter([(string) $record->remarks, $auditRemark])));
            DB::table('sales_documents')->where('id', $record->id)->update([
                'status' => $newStatus,
                'posting_status' => $reversal ? 'reversed' : 'unposted',
                'remarks' => $remarks,
                'updated_at' => now(),
            ]);
            if (Schema::hasTable('client_portal_tokens')) {
                DB::table('client_portal_tokens')->where('sales_document_id', $record->id)->delete();
            }

            app(ActivityLogger::class)->log($newStatus, 'sales_document', $record->id, [
                'status' => $record->status,
                'posting_status' => $record->posting_status ?? 'unposted',
            ], [
                'status' => $newStatus,
                'posting_status' => $reversal ? 'reversed' : 'unposted',
                'reason' => $reason,
            ], $record->company_id);

            return DB::table('sales_documents')->where('id', $record->id)->first();
        });

        return redirect()
            ->route('erp.invoices.show', $record->id)
            ->with('success', 'Invoice '.$record->document_no.' was '.$record->status.'. The document and its lines were retained.');
    }

    public function billingStatus()
    {
        return view('erp.billings.status', ['billings' => $this->billingRows()]);
    }

    public function billingStatusReview()
    {
        $data = request()->only([
            'invoice_no',
            'customer',
            'shipment_job_id',
            'current_status',
            'new_status',
            'remarks',
        ]);
        $record = DB::table('sales_documents')
            ->where('company_id', $this->defaultCompanyId())
            ->where('document_no', trim((string) ($data['invoice_no'] ?? '')))
            ->first();
        abort_unless($record, 404);

        $newStatus = strtolower(str_replace(' ', '_', trim((string) ($data['new_status'] ?? ''))));
        if (in_array($newStatus, ['posted', 'approved'], true)) {
            $this->authorizeAccountingPosting('invoices', 'approve');
            app(PostingService::class)->postSalesDocument($record->id);
            $newStatus = 'posted';
        } elseif (!in_array($newStatus, ['draft', 'submitted', 'for_approval'], true)) {
            return back()->withInput()->withErrors(['new_status' => 'Use the void/cancel workflow for final or posted invoices.']);
        } else {
            if (($record->posting_status ?? 'unposted') === 'posted') {
                return back()->withInput()->withErrors(['new_status' => 'A posted invoice cannot return to draft. Reverse it instead.']);
            }
            DB::table('sales_documents')->where('id', $record->id)->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);
        }

        app(ActivityLogger::class)->log('status_changed', 'sales_document', $record->id, [
            'status' => $record->status,
        ], [
            'status' => $newStatus,
            'remarks' => $data['remarks'] ?? null,
        ], $record->company_id);

        return view('erp.billings.status-review', [
            'status' => array_merge($data, ['new_status' => $newStatus]),
        ]);
    }

    public function billingInvoicePrintLayout()
    {
        return view('erp.billings.print-layout-invoice', [
            'positions' => $this->invoicePrintPositions(),
            'settings' => $this->invoicePrintSettings(),
            'fontOptions' => $this->invoicePrintFontOptions(),
            'fieldStyles' => $this->invoicePrintFieldStyles(),
            'customFields' => $this->invoicePrintCustomFields(),
            'hiddenFields' => $this->invoicePrintHiddenFields(),
        ]);
    }

    public function saveBillingInvoicePrintLayout()
    {
        if (request()->hasFile('template_file') && request()->file('template_file')->isValid()) {
            $path = request()->file('template_file')->store('erp/templates', 'public');
            request()->merge([
                'template_url' => asset('storage/'.$path),
                'print_template' => '1',
            ]);
        }

        $payload = $this->invoicePrintLayoutPayload();

        try {
            if (Schema::hasTable('print_layouts')) {
                $updates = [
                    'paper_size' => $payload['settings']['paper_size'],
                    'paper_width' => $payload['settings']['paper_width'],
                    'paper_height' => $payload['settings']['paper_height'],
                    'template_url' => $payload['settings']['template_url'],
                    'print_template' => $payload['settings']['print_template'],
                    'positions' => json_encode($payload['positions']),
                    'field_styles' => json_encode($payload['field_styles']),
                    'custom_fields' => json_encode($payload['custom_fields']),
                    'is_default' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                if (Schema::hasColumn('print_layouts', 'hidden_fields')) {
                    $updates['hidden_fields'] = json_encode($payload['hidden_fields']);
                }

                DB::table('print_layouts')->updateOrInsert(
                    [
                        'company_id' => null,
                        'document_type' => 'billing_invoice_cebu_recreated',
                        'layout_name' => 'Default',
                    ],
                    $updates
                );
            }
        } catch (\Exception $exception) {
            // Keep layout editing usable even if the web server has stale DB config.
        }

        Storage::put($this->invoicePrintLayoutStoragePath(), json_encode($payload, JSON_PRETTY_PRINT));

        return redirect()
            ->route('erp.billings.print-layout.invoice')
            ->with('status', 'Billing invoice print layout saved for future invoices.');
    }

    public function soaBPrintLayout()
    {
        return view('erp.billings.print-layout-soa', $this->soaPrintLayoutViewData('soa_b'));
    }

    public function saveSoaBPrintLayout()
    {
        $this->saveSoaPrintLayoutPayload('soa_b');

        return redirect()
            ->route('erp.billings.print-layout.soa-b')
            ->with('status', 'SOA B print layout saved.');
    }

    public function soaAPrintLayout()
    {
        return view('erp.billings.print-layout-soa', $this->soaPrintLayoutViewData('soa_a'));
    }

    public function saveSoaAPrintLayout()
    {
        $this->saveSoaPrintLayoutPayload('soa_a');

        return redirect()
            ->route('erp.billings.print-layout.soa-a')
            ->with('status', 'SOA A print layout saved.');
    }

    private function soaPrintLayoutViewData($type)
    {
        return [
            'type' => $type,
            'title' => $type === 'soa_a' ? 'SOA A' : 'SOA B',
            'previewRoute' => $type === 'soa_a' ? route('erp.soa-a.preview') : route('erp.billings.print-soa'),
            'saveRoute' => $type === 'soa_a' ? route('erp.billings.print-layout.soa-a.save') : route('erp.billings.print-layout.soa-b.save'),
            'resetRoute' => $type === 'soa_a' ? route('erp.billings.print-layout.soa-a') : route('erp.billings.print-layout.soa-b'),
            'settings' => $this->soaPrintSettings($type),
            'positions' => $this->soaPrintPositions($type),
            'fieldStyles' => $this->soaPrintFieldStyles($type),
            'customFields' => $this->soaPrintCustomFields($type),
            'hiddenFields' => $this->soaPrintHiddenFields($type),
            'samples' => $this->soaPrintSamples($type),
        ];
    }

    private function saveSoaPrintLayoutPayload($type)
    {
        $payload = [
            'settings' => $this->soaPrintSettings($type),
            'positions' => $this->soaPrintPositions($type),
            'field_styles' => $this->soaPrintFieldStyles($type),
            'custom_fields' => $this->soaPrintCustomFields($type),
            'hidden_fields' => $this->soaPrintHiddenFields($type),
        ];

        try {
            if (Schema::hasTable('print_layouts')) {
                $updates = [
                    'paper_size' => $payload['settings']['paper_size'],
                    'paper_width' => $payload['settings']['paper_width'],
                    'paper_height' => $payload['settings']['paper_height'],
                    'template_url' => $payload['settings']['template_url'],
                    'print_template' => $payload['settings']['print_template'],
                    'positions' => json_encode($payload['positions']),
                    'field_styles' => json_encode($payload['field_styles']),
                    'custom_fields' => json_encode($payload['custom_fields']),
                    'is_default' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                if (Schema::hasColumn('print_layouts', 'hidden_fields')) {
                    $updates['hidden_fields'] = json_encode($payload['hidden_fields']);
                }

                DB::table('print_layouts')->updateOrInsert(
                    [
                        'company_id' => null,
                        'document_type' => $type,
                        'layout_name' => 'Default',
                    ],
                    $updates
                );
            }
        } catch (\Exception $exception) {
            // Keep layout editing usable even if the database is unavailable.
        }

        Storage::put($this->soaPrintLayoutStoragePath($type), json_encode($payload, JSON_PRETTY_PRINT));
    }

    private function soaPrintLayoutStoragePath($type)
    {
        return 'erp/'.$type.'_print_layout.json';
    }

    private function savedSoaPrintLayout($type)
    {
        try {
            if (Schema::hasTable('print_layouts')) {
                $layout = DB::table('print_layouts')
                    ->whereNull('company_id')
                    ->where('document_type', $type)
                    ->where('layout_name', 'Default')
                    ->first();

                if ($layout) {
                    return [
                        'settings' => [
                            'paper_size' => $layout->paper_size,
                            'paper_width' => (int) $layout->paper_width,
                            'paper_height' => (int) $layout->paper_height,
                            'template_url' => $layout->template_url ?: '',
                            'print_template' => (bool) $layout->print_template,
                        ],
                        'positions' => json_decode($layout->positions ?: '[]', true) ?: [],
                        'field_styles' => json_decode($layout->field_styles ?: '[]', true) ?: [],
                        'custom_fields' => json_decode($layout->custom_fields ?: '[]', true) ?: [],
                        'hidden_fields' => Schema::hasColumn('print_layouts', 'hidden_fields') ? (json_decode($layout->hidden_fields ?: '[]', true) ?: []) : [],
                    ];
                }
            }
        } catch (\Exception $exception) {
            // Fall back to local storage.
        }

        if (!Storage::exists($this->soaPrintLayoutStoragePath($type))) {
            return [];
        }

        $layout = json_decode(Storage::get($this->soaPrintLayoutStoragePath($type)), true);

        return is_array($layout) ? $layout : [];
    }

    private function soaPrintSettings($type)
    {
        $presets = [
            'letter' => ['width' => 816, 'height' => 1056, 'page_css' => 'Letter'],
            'legal' => ['width' => 816, 'height' => 1344, 'page_css' => 'Legal'],
            'a4' => ['width' => 794, 'height' => 1123, 'page_css' => 'A4'],
            'custom' => ['width' => 816, 'height' => 1056, 'page_css' => '816px 1056px'],
        ];
        $saved = $this->savedSoaPrintLayout($type);
        $savedSettings = $saved['settings'] ?? [];
        $paper = request('paper_size', $savedSettings['paper_size'] ?? 'letter');
        $paper = isset($presets[$paper]) ? $paper : 'letter';
        $width = (int) ($savedSettings['paper_width'] ?? $presets[$paper]['width']);
        $height = (int) ($savedSettings['paper_height'] ?? $presets[$paper]['height']);

        if (request()->has('paper_width')) {
            $width = max(400, min(1600, (int) request('paper_width', $width)));
        }
        if (request()->has('paper_height')) {
            $height = max(400, min(2200, (int) request('paper_height', $height)));
        }

        return [
            'paper_size' => $paper,
            'paper_width' => $width,
            'paper_height' => $height,
            'page_css' => $paper === 'custom' ? ($width.'px '.$height.'px') : $presets[$paper]['page_css'],
            'template_url' => trim((string) request('template_url', $savedSettings['template_url'] ?? '')),
            'print_template' => request()->has('print_template') ? request('print_template') === '1' : (bool) ($savedSettings['print_template'] ?? false),
        ];
    }

    private function soaPrintDefaults($type)
    {
        if ($type === 'soa_a') {
            return [
                'serial' => [690, 134],
                'customer' => [92, 228],
                'shipper' => [92, 282],
                'particulars' => [112, 320],
                'amounts' => [637, 320],
                'total_reimbursement' => [637, 797],
            ];
        }

        return [
            'serial' => [692, 134],
            'customer' => [152, 190],
            'address' => [148, 220],
            'date' => [618, 235],
            'particulars' => [115, 370],
            'amounts' => [640, 370],
            'remarks' => [115, 620],
            'payment_note' => [60, 735],
            'total_reimbursement' => [640, 899],
            'prepared_by' => [545, 1005],
        ];
    }

    private function soaPrintPositions($type)
    {
        $saved = $this->savedSoaPrintLayout($type);
        $positions = $this->soaPrintDefaults($type);

        foreach ($positions as $key => $point) {
            $savedPoint = $saved['positions'][$key] ?? $point;
            $positions[$key] = [
                (int) request('x_'.$key, $savedPoint[0] ?? $point[0]),
                (int) request('y_'.$key, $savedPoint[1] ?? $point[1]),
            ];
        }

        return $positions;
    }

    private function soaPrintFieldStyles($type)
    {
        $saved = $this->savedSoaPrintLayout($type);
        $styles = [];

        foreach ($this->soaPrintPositions($type) as $key => $point) {
            $savedStyle = $saved['field_styles'][$key] ?? [];
            $defaultWidth = in_array($key, ['amounts', 'total_reimbursement']) ? 115 : 180;
            if (in_array($key, ['particulars', 'payment_note', 'remarks'])) {
                $defaultWidth = $key === 'payment_note' ? 510 : 455;
            }
            if (in_array($key, ['customer', 'address'])) {
                $defaultWidth = 360;
            }
            if ($key === 'shipper') {
                $defaultWidth = 520;
            }
            if ($key === 'date') {
                $defaultWidth = 90;
            }

            $styles[$key] = [
                'width' => max(20, min(900, (int) request('w_'.$key, $savedStyle['width'] ?? $defaultWidth))),
                'font_size' => max(6, min(48, (int) request('fs_'.$key, $savedStyle['font_size'] ?? ($key === 'serial' ? 18 : 12)))),
            ];
        }

        return $styles;
    }

    private function soaPrintCustomFields($type)
    {
        $saved = $this->savedSoaPrintLayout($type);
        if (!request()->has('custom_fields_present')) {
            return $this->sanitizeInvoicePrintCustomFields($saved['custom_fields'] ?? []);
        }

        $keys = (array) request('custom_key', []);
        $labels = (array) request('custom_label', []);
        $texts = (array) request('custom_text', []);
        $xs = (array) request('custom_x', []);
        $ys = (array) request('custom_y', []);
        $widths = (array) request('custom_w', []);
        $fontSizes = (array) request('custom_fs', []);

        return $this->sanitizeInvoicePrintCustomFields(array_map(function ($key, $index) use ($labels, $texts, $xs, $ys, $widths, $fontSizes) {
            return [
                'key' => $key,
                'label' => $labels[$index] ?? 'Custom Field',
                'text' => $texts[$index] ?? 'Custom Text',
                'x' => $xs[$index] ?? 40,
                'y' => $ys[$index] ?? 40,
                'width' => $widths[$index] ?? 160,
                'font_size' => $fontSizes[$index] ?? 12,
            ];
        }, $keys, array_keys($keys)));
    }

    private function soaPrintHiddenFields($type)
    {
        $saved = $this->savedSoaPrintLayout($type);
        $hidden = request()->has('hidden_fields_present') ? (array) request('hidden_field', []) : ($saved['hidden_fields'] ?? ['serial']);

        return array_values(array_unique(array_filter(array_map(function ($field) {
            return preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $field);
        }, is_array($hidden) ? $hidden : []))));
    }

    private function soaPrintSamples($type)
    {
        if ($type === 'soa_a') {
            return [
                'serial' => ['label' => 'A Number', 'value' => '0907', 'class' => 'serial'],
                'customer' => ['label' => 'Customer', 'value' => 'PHILUSA CORPORATION', 'class' => ''],
                'shipper' => ['label' => 'Shipper', 'value' => 'SHIPPER: LEMOINE HOLLAND B.V.', 'class' => 'small'],
                'particulars' => ['label' => 'Particulars', 'value' => "OCEAN FREIGHT\nBUNKER SURCHARGE\nSOLAS ADMIN\n5% COLLECT FEE\n\nSHIPPER: LEMOINE HOLLAND B.V.", 'class' => ''],
                'amounts' => ['label' => 'Amounts', 'value' => "368,376.75\n120,022.50\n20,311.50\n9,232.50", 'class' => 'amount'],
                'total_reimbursement' => ['label' => 'Total Reimbursement', 'value' => '518,943.25', 'class' => 'amount'],
            ];
        }

        return [
            'serial' => ['label' => 'B Number', 'value' => '007187', 'class' => 'serial'],
            'customer' => ['label' => 'Customer', 'value' => 'PHILUSA CORPORATION', 'class' => ''],
            'address' => ['label' => 'Address', 'value' => "28 SHAW BLVD, KAPITOLYO 1603\nCITY OF PASIG", 'class' => 'small'],
            'date' => ['label' => 'Date', 'value' => '05/19/2026', 'class' => ''],
            'particulars' => ['label' => 'Particulars', 'value' => "OCEAN FREIGHT (95% OF FREIGHT CHARGES)\nBUNKER SURCHARGE\nSOLAS ADMIN\n5% COLLECT FEE", 'class' => ''],
            'amounts' => ['label' => 'Amounts', 'value' => "368,376.75\n120,022.50\n20,311.50\n9,232.50\n19,388.25", 'class' => 'amount'],
            'remarks' => ['label' => 'Remarks', 'value' => 'Remarks: none', 'class' => 'small'],
            'payment_note' => ['label' => 'Payment Note', 'value' => "PLEASE PAY THIS REIMBURSEMENT CHARGES TOGETHER WITH INVOICE NUMBER 0001031\nREIMBURSEMENT CHARGES PHP 753,495.10", 'class' => ''],
            'total_reimbursement' => ['label' => 'Total Amount Due', 'value' => '774,822.17', 'class' => 'amount'],
            'prepared_by' => ['label' => 'Prepared By', 'value' => auth()->user()->name ?? 'User', 'class' => ''],
        ];
    }

    private function invoicePrintLayoutStoragePath()
    {
        return 'erp/billing_invoice_cebu_recreated_print_layout.json';
    }

    private function savedInvoicePrintLayout()
    {
        try {
            if (Schema::hasTable('print_layouts')) {
                $layout = DB::table('print_layouts')
                    ->whereNull('company_id')
                    ->where('document_type', 'billing_invoice_cebu_recreated')
                    ->where('layout_name', 'Default')
                    ->first();

                if ($layout) {
                    $storageLayout = [];
                    if (Storage::exists($this->invoicePrintLayoutStoragePath())) {
                        $stored = json_decode(Storage::get($this->invoicePrintLayoutStoragePath()), true);
                        $storageLayout = is_array($stored) ? $stored : [];
                    }

                    $hiddenFields = $storageLayout['hidden_fields'] ?? [];
                    if (Schema::hasColumn('print_layouts', 'hidden_fields')) {
                        $hiddenFields = json_decode($layout->hidden_fields ?: '[]', true) ?: $hiddenFields;
                    }

                    return [
                        'settings' => [
                            'paper_size' => $layout->paper_size,
                            'paper_width' => (int) $layout->paper_width,
                            'paper_height' => (int) $layout->paper_height,
                            'template_url' => $layout->template_url ?: '',
                            'print_template' => (bool) $layout->print_template,
                        ],
                        'positions' => json_decode($layout->positions ?: '[]', true) ?: [],
                        'field_styles' => json_decode($layout->field_styles ?: '[]', true) ?: [],
                        'custom_fields' => json_decode($layout->custom_fields ?: '[]', true) ?: [],
                        'hidden_fields' => $hiddenFields,
                    ];
                }
            }
        } catch (\Exception $exception) {
            // Fall back to the local layout file if database config is unavailable.
        }

        if (!Storage::exists($this->invoicePrintLayoutStoragePath())) {
            return [];
        }

        $layout = json_decode(Storage::get($this->invoicePrintLayoutStoragePath()), true);

        return is_array($layout) ? $layout : [];
    }

    private function invoicePrintLayoutPayload()
    {
        return [
            'settings' => $this->invoicePrintSettings(),
            'positions' => $this->invoicePrintPositions(),
            'field_styles' => $this->invoicePrintFieldStyles(),
            'custom_fields' => $this->invoicePrintCustomFields(),
            'hidden_fields' => $this->invoicePrintHiddenFields(),
        ];
    }

    private function invoicePrintFontOptions()
    {
        return [
            'arial' => ['label' => 'Arial (Agenture)', 'stack' => 'Arial, Helvetica, sans-serif'],
            'sans_serif' => ['label' => 'Sans Serif (Epson 10 CPI)', 'stack' => '"Epson Sans Serif", "Courier New", "Lucida Console", monospace'],
            'calibri' => ['label' => 'Calibri', 'stack' => 'Calibri, Arial, Helvetica, sans-serif'],
            'times_new_roman' => ['label' => 'Times New Roman', 'stack' => '"Times New Roman", Times, serif'],
            'verdana' => ['label' => 'Verdana', 'stack' => 'Verdana, Geneva, sans-serif'],
            'tahoma' => ['label' => 'Tahoma', 'stack' => 'Tahoma, Arial, sans-serif'],
        ];
    }

    private function invoicePrintSettings()
    {
        $presets = [
            'letter' => ['label' => 'Letter', 'width' => 816, 'height' => 1056, 'page_css' => 'Letter'],
            'legal' => ['label' => 'Legal', 'width' => 816, 'height' => 1344, 'page_css' => 'Legal'],
            'a4' => ['label' => 'A4', 'width' => 794, 'height' => 1123, 'page_css' => 'A4'],
            'custom' => ['label' => 'Custom', 'width' => 816, 'height' => 1056, 'page_css' => '816px 1056px'],
        ];

        $saved = $this->savedInvoicePrintLayout();
        $savedSettings = $saved['settings'] ?? [];
        $fontOptions = $this->invoicePrintFontOptions();
        $defaultTemplateUrl = asset('images/billing-invoice-template.svg');

        $paper = request('paper_size', $savedSettings['paper_size'] ?? 'letter');
        if (!isset($presets[$paper])) {
            $paper = 'letter';
        }

        $width = (int) ($savedSettings['paper_width'] ?? $presets[$paper]['width']);
        $height = (int) ($savedSettings['paper_height'] ?? $presets[$paper]['height']);

        if (request()->has('paper_width')) {
            $width = max(400, min(1600, (int) request('paper_width', $width)));
        }

        if (request()->has('paper_height')) {
            $height = max(400, min(2200, (int) request('paper_height', $height)));
        }

        $savedTemplateUrl = trim((string) ($savedSettings['template_url'] ?? ''));
        $templateUrl = trim((string) request('template_url', $savedTemplateUrl !== '' ? $savedTemplateUrl : $defaultTemplateUrl));
        $printTemplate = request()->has('print_template')
            ? request('print_template') === '1'
            : (array_key_exists('print_template', $savedSettings) ? (bool) $savedSettings['print_template'] : true);
        $savedFontFamily = $savedSettings['font_family'] ?? ($saved['field_styles']['_font_family'] ?? 'arial');
        $fontFamily = strtolower(trim((string) request('font_family', $savedFontFamily)));
        if (!isset($fontOptions[$fontFamily])) {
            $fontFamily = 'arial';
        }
        $savedBoldAll = $savedSettings['bold_all'] ?? ($saved['field_styles']['_bold_all'] ?? true);
        $boldAll = request()->has('bold_all')
            ? request('bold_all') === '1'
            : filter_var($savedBoldAll, FILTER_VALIDATE_BOOLEAN);

        return [
            'paper_size' => $paper,
            'paper_width' => $width,
            'paper_height' => $height,
            'page_css' => $paper === 'custom' ? ($width.'px '.$height.'px') : $presets[$paper]['page_css'],
            'template_url' => $templateUrl,
            'print_template' => $printTemplate,
            'font_family' => $fontFamily,
            'font_stack' => $fontOptions[$fontFamily]['stack'],
            'bold_all' => $boldAll,
            'font_weight' => $boldAll ? 700 : 400,
        ];
    }

    private function invoicePrintPositions()
    {
        $saved = $this->savedInvoicePrintLayout();
        $savedPositions = $saved['positions'] ?? [];
        $defaults = [
            'cash_sales' => [56, 118],
            'charge_sales' => [165, 118],
            'customer' => [96, 148],
            'address' => [96, 175],
            'tin' => [96, 202],
            'mawb' => [122, 244],
            'hawb' => [122, 270],
            'date_shipped' => [122, 296],
            'origin_dest' => [455, 244],
            'commodity' => [455, 270],
            'invoice_no' => [662, 123],
            'invoice_date' => [682, 170],
            'line_description' => [65, 384],
            'soa_no' => [65, 450],
            'remarks' => [65, 490],
            'line_qty' => [450, 384],
            'line_unit_price' => [565, 384],
            'line_amount' => [705, 384],
            'vatable_sales' => [126, 790],
            'vat' => [126, 815],
            'zero_rated_sales' => [126, 840],
            'vat_exempt_sales' => [126, 865],
            'total_sales' => [660, 790],
            'less_vat' => [660, 815],
            'amount_net_vat' => [660, 840],
            'discount' => [660, 865],
            'add_vat' => [660, 890],
            'withholding_tax' => [660, 915],
            'amount_due' => [690, 948],
            'prepared_by' => [490, 1003],
        ];

        foreach ($defaults as $key => $point) {
            $savedPoint = $savedPositions[$key] ?? $point;

            $defaults[$key] = [
                (int) request('x_'.$key, $savedPoint[0] ?? $point[0]),
                (int) request('y_'.$key, $savedPoint[1] ?? $point[1]),
            ];
        }

        return $defaults;
    }

    private function invoicePrintFieldStyles()
    {
        $positions = $this->invoicePrintPositions();
        $saved = $this->savedInvoicePrintLayout();
        $savedStyles = $saved['field_styles'] ?? [];
        $amountFields = [
            'line_qty', 'line_unit_price', 'line_amount', 'vatable_sales', 'vat',
            'zero_rated_sales', 'vat_exempt_sales', 'total_sales', 'less_vat',
            'amount_net_vat', 'discount', 'add_vat', 'withholding_tax', 'amount_due',
        ];

        $styles = [];
        foreach ($positions as $key => $point) {
            $defaultWidth = in_array($key, $amountFields) ? 92 : 160;
            if ($key === 'line_description' || $key === 'remarks') {
                $defaultWidth = 350;
            }
            if ($key === 'address') {
                $defaultWidth = 270;
            }
            if (in_array($key, ['origin_dest', 'commodity'])) {
                $defaultWidth = 230;
            }
            if ($key === 'prepared_by') {
                $defaultWidth = 250;
            }
            if ($key === 'invoice_no') {
                $defaultWidth = 125;
            }
            $savedStyle = $savedStyles[$key] ?? [];

            $styles[$key] = [
                'width' => max(20, min(900, (int) request('w_'.$key, $savedStyle['width'] ?? $defaultWidth))),
                'font_size' => max(6, min(48, (int) request('fs_'.$key, $savedStyle['font_size'] ?? ($key === 'address' ? 10 : 12)))),
            ];
        }

        $styles['_font_family'] = $this->invoicePrintSettings()['font_family'];
        $styles['_bold_all'] = $this->invoicePrintSettings()['bold_all'];

        return $styles;
    }

    private function invoicePrintCustomFields()
    {
        $saved = $this->savedInvoicePrintLayout();
        if (!request()->has('custom_fields_present')) {
            return $this->sanitizeInvoicePrintCustomFields($saved['custom_fields'] ?? []);
        }

        $keys = (array) request('custom_key', []);
        $labels = (array) request('custom_label', []);
        $texts = (array) request('custom_text', []);
        $xs = (array) request('custom_x', []);
        $ys = (array) request('custom_y', []);
        $widths = (array) request('custom_w', []);
        $fontSizes = (array) request('custom_fs', []);

        return $this->sanitizeInvoicePrintCustomFields(array_map(function ($key, $index) use ($labels, $texts, $xs, $ys, $widths, $fontSizes) {
            return [
                'key' => $key,
                'label' => $labels[$index] ?? 'Custom Field',
                'text' => $texts[$index] ?? 'Custom Text',
                'x' => $xs[$index] ?? 40,
                'y' => $ys[$index] ?? 40,
                'width' => $widths[$index] ?? 160,
                'font_size' => $fontSizes[$index] ?? 12,
            ];
        }, $keys, array_keys($keys)));
    }

    private function invoicePrintHiddenFields()
    {
        $saved = $this->savedInvoicePrintLayout();
        $hidden = request()->has('hidden_fields_present')
            ? (array) request('hidden_field', [])
            : ($saved['hidden_fields'] ?? ['invoice_no']);

        return array_values(array_unique(array_filter(array_map(function ($field) {
            return preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $field);
        }, is_array($hidden) ? $hidden : []))));
    }

    private function sanitizeInvoicePrintCustomFields($rawFields)
    {
        $fields = [];
        if (!is_array($rawFields)) {
            return $fields;
        }

        foreach ($rawFields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $fields[] = [
                'key' => $key,
                'label' => trim((string) ($field['label'] ?? 'Custom Field')),
                'text' => trim((string) ($field['text'] ?? 'Custom Text')),
                'x' => max(0, min(1600, (int) ($field['x'] ?? 40))),
                'y' => max(0, min(2200, (int) ($field['y'] ?? 40))),
                'width' => max(20, min(900, (int) ($field['width'] ?? 160))),
                'font_size' => max(6, min(48, (int) ($field['font_size'] ?? 12))),
            ];
        }

        return $fields;
    }

    private function billingRows()
    {
        if (!Schema::hasTable('sales_documents')) {
            return [];
        }

        return DB::table('sales_documents')
            ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->select(
                'sales_documents.id',
                'sales_documents.document_no',
                'sales_documents.job_shipment_id',
                'sales_documents.shipment_reference',
                'sales_documents.currency',
                'sales_documents.subtotal',
                'sales_documents.tax_total',
                'sales_documents.grand_total',
                'sales_documents.status',
                'sales_documents.document_date',
                'customers.name as customer_name'
            )
            ->where('sales_documents.company_id', $this->defaultCompanyId())
            ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->orderBy('sales_documents.document_date', 'desc')
            ->orderBy('sales_documents.id', 'desc')
            ->get()
            ->map(function ($invoice) {
                $taxTotal = (float) ($invoice->tax_total ?? 0);
                $amount = (float) ($invoice->subtotal ?? 0);
                if ($amount <= 0) {
                    $amount = max(0, (float) $invoice->grand_total - $taxTotal);
                }

                return [
                    $invoice->document_no,
                    $invoice->customer_name ?: 'Guest / Non-tax Client',
                    $invoice->job_shipment_id ?: $invoice->shipment_reference,
                    $invoice->currency ?: 'PHP',
                    number_format($amount, 2),
                    number_format($taxTotal, 2),
                    number_format((float) $invoice->grand_total, 2),
                    ucwords(str_replace('_', ' ', $invoice->status ?: 'draft')),
                    $invoice->document_date,
                    $invoice->id,
                ];
            })
            ->all();
    }

    private function customerSelectOptions()
    {
        if (!Schema::hasTable('customers')) {
            return $this->customers;
        }

        return DB::table('customers')
            ->where('company_id', $this->defaultCompanyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($customer) {
                $taxClassification = $customer->tax_classification ?: '12%';
                if (stripos($taxClassification, '12%') !== false) {
                    $taxClassification = '12%';
                } elseif (stripos($taxClassification, 'zero') !== false) {
                    $taxClassification = 'zero-rated';
                } elseif (stripos($taxClassification, 'exempt') !== false) {
                    $taxClassification = 'exempt';
                }

                return [
                    'customer_id' => $customer->customer_id ?: $customer->code,
                    'name' => $customer->name,
                    'tin' => $customer->tin,
                    'address' => $customer->billing_address,
                    'terms' => $customer->payment_terms,
                    'currency' => $customer->currency ?: 'PHP',
                    'class' => $customer->default_class_code ?: 'MANILA',
                    'vat_classification' => $taxClassification,
                ];
            })
            ->all();
    }

    private function invoiceItemOptions()
    {
        if (!Schema::hasTable('items')) {
            return collect();
        }

        return DB::table('items')
            ->where('company_id', $this->defaultCompanyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $name = $item->name;
                $accountMeta = $this->invoiceItemAccountMeta($item->account_name ?? '', $item->tax_type ?? '');
                $description = trim((string) ($item->description ?? ''));
                if ($description === '') {
                    $description = $name;
                }

                return (object) [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $name,
                    'description' => $description,
                    'rate' => (float) ($item->selling_price ?? 0),
                    'qty' => 1,
                    'class' => 'MANILA',
                    'account_name' => $accountMeta['source_account'],
                    'account_tag' => $accountMeta['tag'],
                    'account' => $accountMeta['invoice_account'],
                    'tax' => $accountMeta['tax_category'],
                ];
            });
    }

    private function invoiceItemAccountMeta($accountName, $taxType = '')
    {
        $accountName = trim((string) $accountName);
        $normalized = strtolower(preg_replace('/\s+/', ' ', $accountName));
        $taxType = strtolower(trim((string) $taxType));

        if (strpos($normalized, 'reimburs') !== false) {
            $isVat = strpos($taxType, 'vat') !== false && strpos($taxType, 'non') === false;

            return [
                'source_account' => $accountName ?: 'Reimbursable charges',
                'tag' => 'Reimbursable',
                'invoice_account' => 'A/R-Reimbursable Charges',
                'tax_category' => $isVat ? '12%' : 'exempt',
                'tax_type' => $isVat ? 'VAT' : 'Non',
            ];
        }

        if (strpos($normalized, 'soa a') !== false) {
            return [
                'source_account' => $accountName ?: 'Service Income-SOA A',
                'tag' => 'SOA A',
                'invoice_account' => 'A/R Trade-SOA A',
                'tax_category' => 'zero-rated',
                'tax_type' => 'Non',
            ];
        }

        if (strpos($normalized, 'zero') !== false) {
            return [
                'source_account' => $accountName ?: 'Service Income-Zero rated',
                'tag' => 'Zero-rated',
                'invoice_account' => 'A/Trade-Billing Invoice',
                'tax_category' => 'zero-rated',
                'tax_type' => 'Non',
            ];
        }

        if (strpos($normalized, 'vatable') !== false || strpos($taxType, 'vat') !== false) {
            return [
                'source_account' => $accountName ?: 'Service Income- Vatable',
                'tag' => 'Vatable',
                'invoice_account' => 'A/Trade-Billing Invoice',
                'tax_category' => '12%',
                'tax_type' => 'VAT',
            ];
        }

        return [
            'source_account' => $accountName,
            'tag' => 'Unclassified',
            'invoice_account' => 'A/Trade-Billing Invoice',
            'tax_category' => 'exempt',
            'tax_type' => $taxType ?: 'Non',
        ];
    }

    private function invoiceAgingRows()
    {
        if (!Schema::hasTable('sales_documents')) {
            return [];
        }

        $asOf = request('as_of', date('Y-m-d'));
        $balanceExpression = $this->salesDocumentBalanceExpression();

        return DB::table('sales_documents')
            ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->select(
                'customers.customer_id',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'sales_documents.document_no',
                'sales_documents.job_shipment_id',
                'sales_documents.shipment_reference',
                'sales_documents.currency',
                'sales_documents.grand_total',
                'sales_documents.paid_total',
                'sales_documents.balance_due',
                'sales_documents.due_date',
                'sales_documents.vat_classification',
                'sales_documents.class_code'
            )
            ->whereNotIn('sales_documents.status', ['cancelled', 'voided', 'paid'])
            ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->whereRaw($balanceExpression.' > 0')
            ->orderBy('sales_documents.due_date')
            ->orderBy('sales_documents.id')
            ->get()
            ->map(function ($invoice) use ($asOf) {
                $balance = $this->salesDocumentBalance($invoice);
                $daysPastDue = $invoice->due_date ? max(0, \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($asOf), false)) : 0;
                $current = $daysPastDue <= 0 ? $balance : 0;
                $thirty = $daysPastDue > 0 && $daysPastDue <= 30 ? $balance : 0;
                $sixty = $daysPastDue > 30 && $daysPastDue <= 60 ? $balance : 0;
                $ninety = $daysPastDue > 60 ? $balance : 0;

                return [
                    $invoice->customer_id ?: ($invoice->customer_code ?: '-'),
                    $invoice->customer_name ?: 'Guest / Non-tax Client',
                    $invoice->job_shipment_id ?: $invoice->shipment_reference,
                    $invoice->document_no,
                    $invoice->currency ?: 'PHP',
                    $invoice->vat_classification ?: '-',
                    $invoice->class_code ?: '-',
                    $current,
                    $thirty,
                    $sixty,
                    $ninety,
                ];
            })
            ->all();
    }

    private function invoiceAgingBuckets()
    {
        $rows = $this->invoiceAgingRows();
        $totals = ['Current' => 0, '30 Days' => 0, '60 Days' => 0, '90+ Days' => 0];

        foreach ($rows as $row) {
            $totals['Current'] += (float) ($row[7] ?? 0);
            $totals['30 Days'] += (float) ($row[8] ?? 0);
            $totals['60 Days'] += (float) ($row[9] ?? 0);
            $totals['90+ Days'] += (float) ($row[10] ?? 0);
        }

        return [
            ['label' => 'Current', 'value' => 'PHP '.number_format($totals['Current'], 2), 'icon' => 'bi-calendar-check', 'tone' => 'success'],
            ['label' => '30 Days', 'value' => 'PHP '.number_format($totals['30 Days'], 2), 'icon' => 'bi-calendar2-week', 'tone' => 'primary'],
            ['label' => '60 Days', 'value' => 'PHP '.number_format($totals['60 Days'], 2), 'icon' => 'bi-calendar2-minus', 'tone' => 'warning'],
            ['label' => '90+ Days', 'value' => 'PHP '.number_format($totals['90+ Days'], 2), 'icon' => 'bi-calendar2-x', 'tone' => 'danger'],
        ];
    }

    private function saveInvoiceData(array $data, array $totals)
    {
        return DB::transaction(function () use ($data, $totals) {
            $companyId = $this->defaultCompanyId();
            $customerId = $this->invoiceCustomerId($companyId, $data);
            $documentNo = trim((string) $data['invoice_no']);
            $originalDocumentNo = trim((string) ($data['original_invoice_no'] ?? ''));
            $templates = array_values(array_filter((array) ($data['invoice_template'] ?? [])));
            $documentType = $this->invoiceDocumentType($documentNo, $templates);
            $paidTotal = $this->moneyToFloat($data['total_payment'] ?? 0);
            $exchangeRate = $this->moneyToFloat($data['exchange_rate'] ?? 1);
            $exchangeRate = strtoupper((string) ($data['currency'] ?? 'PHP')) === 'USD' ? max($exchangeRate, 0.000001) : 1;
            $status = 'draft';

            $payload = [
                'company_id' => $companyId,
                'branch_id' => DB::table('branches')->where('company_id', $companyId)->orderBy('id')->value('id'),
                'customer_id' => $customerId,
                'customer_id_text' => $data['customer_id'] ?? null,
                'document_type' => $documentType,
                'document_no' => $documentNo,
                'transaction_no' => $data['transaction_no'] ?? $documentNo,
                'soa_no' => $data['soa_no'] ?? null,
                'document_date' => $data['invoice_date'] ?: now()->toDateString(),
                'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
                'invoice_to' => $data['invoice_to'] ?? null,
                'consignee' => $data['consignee'] ?? null,
                'shipment_reference' => $data['job_shipment_id'] ?? null,
                'job_shipment_id' => $data['job_shipment_id'] ?? null,
                'mawb_no' => $data['mawb_no'] ?? null,
                'hawb_no' => $data['hawb_no'] ?? null,
                'date_shipped' => !empty($data['date_shipped']) ? $data['date_shipped'] : null,
                'origin_dest' => $data['origin_dest'] ?? null,
                'commodity' => $data['commodity'] ?? null,
                'vat_classification' => $data['vat_classification'] ?? null,
                'class_code' => $data['class_code'] ?? null,
                'ar_account_code' => $this->arAccountCode($data['ar_account'] ?? null),
                'template_code' => $templates[0] ?? null,
                'invoice_templates' => json_encode($templates),
                'uses_pre_printed_form' => in_array('Billing Invoice', $templates, true) || in_array('SOA_B', $templates, true),
                'credit_term_days' => (int) ($data['credit_term_days'] ?: 0),
                'currency' => $data['currency'] ?: 'PHP',
                'exchange_rate' => $exchangeRate,
                'subtotal' => $totals['billing_subtotal'],
                'vatable_amount' => $totals['vatable_sales'],
                'zero_rated_amount' => $totals['zero_rated_sales'],
                'vat_exempt_amount' => $totals['vat_exempt_sales'],
                'tax_total' => $totals['vat_amount'],
                'withholding_tax_rate' => $totals['withholding_tax_rate'],
                'withholding_tax_amount' => $totals['withholding_tax_amount'],
                'freight_total' => $totals['reimbursement_total'],
                'grand_total' => $totals['soa_total'],
                'invoice_amount' => $totals['billing_total'],
                'paid_total' => $paidTotal,
                'total_payment' => $paidTotal,
                'balance_due' => max(0, $totals['soa_total'] - $paidTotal),
                'status' => $status,
                'remarks' => $data['description'] ?? null,
                'customer_message' => $data['customer_message'] ?? null,
                'memo' => $data['memo'] ?? null,
                'customer_tax_code' => $data['customer_tax_code'] ?? null,
                'updated_at' => now(),
            ];

            $existing = null;
            if ($originalDocumentNo !== '') {
                $existing = DB::table('sales_documents')
                    ->where('company_id', $companyId)
                    ->where('document_no', $originalDocumentNo)
                    ->first();
            }
            if (!$existing) {
                $existing = DB::table('sales_documents')
                    ->where('company_id', $companyId)
                    ->where('document_no', $documentNo)
                    ->first();
            }

            if ($existing && ($existing->posting_status ?? 'unposted') === 'posted') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'invoice_no' => 'A posted invoice is immutable. Reverse it and create a correcting document instead.',
                ]);
            }
            if ($existing && in_array(strtolower((string) $existing->status), ['cancelled', 'voided'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'invoice_no' => 'A cancelled or voided invoice cannot be changed. Create a new invoice instead.',
                ]);
            }

            if ($existing) {
                DB::table('sales_documents')->where('id', $existing->id)->update($payload);
                $salesDocumentId = $existing->id;
                DB::table('sales_document_lines')->where('sales_document_id', $salesDocumentId)->delete();
            } else {
                $payload['created_at'] = now();
                $salesDocumentId = DB::table('sales_documents')->insertGetId($payload);
            }

            $this->saveInvoiceLines($salesDocumentId, $data);

            return DB::table('sales_documents')->where('id', $salesDocumentId)->first();
        });
    }

    private function saveInvoiceLines($salesDocumentId, array $data)
    {
        $items = (array) ($data['item'] ?? []);
        $descriptions = (array) ($data['item_description'] ?? []);
        $quantities = (array) ($data['qty'] ?? []);
        $unitPrices = (array) ($data['unit_price'] ?? []);
        $classes = (array) ($data['line_class'] ?? []);
        $accounts = (array) ($data['line_ar_account'] ?? []);
        $taxCategories = (array) ($data['tax_category'] ?? []);
        $withholdingRates = (array) ($data['line_withholding_rate'] ?? []);
        $defaultWithholdingRate = min(100, max(0, $this->moneyToFloat($data['withholding_tax_rate'] ?? 0)));
        $count = max(count($items), count($descriptions), count($quantities), count($unitPrices));

        for ($index = 0; $index < $count; $index++) {
            $description = trim((string) ($descriptions[$index] ?? ''));
            $item = trim((string) ($items[$index] ?? ''));
            $quantity = $this->moneyToFloat($quantities[$index] ?? 0);
            $unitPrice = $this->moneyToFloat($unitPrices[$index] ?? 0);
            $lineTotal = $unitPrice * ($quantity ?: 1);
            $isReimbursement = $this->isReimbursableInvoiceLine($data, $index);
            $withholdingRate = trim((string) ($withholdingRates[$index] ?? '')) === ''
                ? $defaultWithholdingRate
                : min(100, max(0, $this->moneyToFloat($withholdingRates[$index])));
            $withholdingAmount = $isReimbursement ? 0 : $lineTotal * ($withholdingRate / 100);

            if ($description === '' && $item === '' && $quantity === 0.0 && $unitPrice === 0.0) {
                continue;
            }

            $payload = [
                'sales_document_id' => $salesDocumentId,
                'item_name' => $item !== '' ? $item : null,
                'description' => $description !== '' ? $description : $item,
                'quantity' => $quantity ?: 1,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_code_id' => $this->taxCodeId($taxCategories[$index] ?? null),
                'line_total' => $lineTotal,
                'class_code' => $classes[$index] ?? null,
                'tax_category' => $taxCategories[$index] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('sales_document_lines', 'ar_account_code')) {
                $payload['ar_account_code'] = $this->arAccountCode($accounts[$index] ?? ($data['ar_account'] ?? null));
            }
            if (Schema::hasColumn('sales_document_lines', 'withholding_tax_rate')) {
                $payload['withholding_tax_rate'] = $isReimbursement ? 0 : $withholdingRate;
            }
            if (Schema::hasColumn('sales_document_lines', 'withholding_tax_amount')) {
                $payload['withholding_tax_amount'] = $withholdingAmount;
            }

            DB::table('sales_document_lines')->insert($payload);
        }
    }

    private function invoiceCustomerId($companyId, array $data)
    {
        $customerCode = trim((string) ($data['customer_id'] ?? ''));
        $customerName = trim((string) ($data['customer_name'] ?? 'Guest / Non-tax Client'));

        $customer = DB::table('customers')
            ->where('company_id', $companyId)
            ->where(function ($query) use ($customerCode, $customerName) {
                $query->where('code', $customerCode)
                    ->orWhere('customer_id', $customerCode)
                    ->orWhere('name', $customerName);
            })
            ->first();

        if ($customer) {
            return $customer->id;
        }

        return DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'code' => $customerCode !== '' && $customerCode !== 'GUEST' ? $customerCode : 'GUEST',
            'customer_id' => $customerCode !== '' && $customerCode !== 'GUEST' ? $customerCode : 'GUEST',
            'name' => $customerName !== '' ? $customerName : 'Guest / Non-tax Client',
            'tin' => $data['tin'] ?? null,
            'tax_classification' => $data['vat_classification'] ?? 'exempt',
            'billing_address' => $data['invoice_to'] ?? null,
            'credit_limit' => 0,
            'credit_status' => 'Within Limit',
            'payment_terms' => !empty($data['credit_term_days']) ? $data['credit_term_days'].' Days' : 'Due on Receipt',
            'credit_term_days' => (int) ($data['credit_term_days'] ?: 0),
            'currency' => $data['currency'] ?: 'PHP',
            'default_class_code' => $data['class_code'] ?: 'MANILA',
            'default_ar_account_code' => $this->arAccountCode($data['ar_account'] ?? null),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoiceDocumentType($documentNo, array $templates)
    {
        if (stripos($documentNo, 'SOAA') === 0 || in_array('SOA_A', $templates, true)) {
            return 'statement_of_account_a';
        }
        if (stripos($documentNo, 'DN') === 0 || in_array('Debit Note', $templates, true)) {
            return 'debit_note';
        }

        return 'billing_invoice';
    }

    private function nextSalesDocumentNumber(array $templates)
    {
        if (in_array('SOA_A', $templates, true)) {
            return $this->nextDocumentNumber('statement_of_account_a', 'SOAA-', 4);
        }

        if (in_array('Debit Note', $templates, true)) {
            return $this->nextDocumentNumber('debit_note', 'DN-', 6);
        }

        return $this->nextDocumentNumber('billing_invoice', 'BI-', 6);
    }

    private function arAccountCode($label)
    {
        $map = [
            'A/Trade-Billing Invoice' => 'AR_TRADE_BI',
            'A/R-Reimbursable Charges' => 'AR_REIMBURSABLE',
            'A/R Reimbursable Charges' => 'AR_REIMBURSABLE',
            'A/R Trade-SOA A' => 'AR_TRADE_SOAA',
            'A/R Trade-Debit Note' => 'AR_TRADE_DN',
        ];

        return $map[$label] ?? ($label ?: 'AR_TRADE_BI');
    }

    private function arAccountLabel($code)
    {
        $map = [
            'AR_TRADE_BI' => 'A/Trade-Billing Invoice',
            'AR_REIMBURSABLE' => 'A/R-Reimbursable Charges',
            'AR_TRADE_SOAA' => 'A/R Trade-SOA A',
            'AR_TRADE_DN' => 'A/R Trade-Debit Note',
        ];

        return $map[$code] ?? ($code ?: 'A/Trade-Billing Invoice');
    }

    private function taxCodeId($taxCategory)
    {
        if (!Schema::hasTable('tax_codes')) {
            return null;
        }

        $code = 'EXEMPT';
        if ($taxCategory === '12%') {
            $code = 'VAT12';
        } elseif ($taxCategory === 'zero-rated' || $taxCategory === 'Zero-rated') {
            $code = 'ZERO';
        }

        return DB::table('tax_codes')->where('code', $code)->value('id');
    }

    private function billingPrintInvoiceFallbacks()
    {
        return [
            'BI-2026-01042' => [
                'customer' => 'North Harbor Trading',
                'customer_id' => 'CUST-001',
                'invoice_no' => 'BI-2026-01042',
                'invoice_date' => '2026-05-24',
                'due_date' => '2026-06-23',
                'soa_no' => '01042',
                'invoice_to' => "North Harbor Trading\nNorth Harbor Center, Manila",
                'tin' => '000-345-111-000',
                'credit_term_days' => '30',
                'vat_classification' => '12%',
                'class_code' => 'MANILA',
                'ar_account' => 'A/Trade-Billing Invoice',
                'invoice_template' => ['SOA_B', 'Billing Invoice'],
                'currency' => 'PHP',
                'mawb_no' => 'MAWB-SEA-0422',
                'hawb_no' => 'HAWB-0422-A',
                'date_shipped' => '2026-05-24',
                'origin_dest' => 'ROTTERDAM / MANILA',
                'commodity' => 'Freight handling and delivery service',
                'description' => 'Freight handling and delivery service',
                'item' => ['Freight handling', 'Reimbursable Charges'],
                'item_description' => ['Freight handling and delivery service', "OCEAN FREIGHT (95% OF FREIGHT CHARGES)\nBUNKER SURCHARGE\nSOLAS ADMIN\n5% COLLECT FEE\nDESTINATION CHARGES"],
                'qty' => ['1', '1'],
                'unit_price' => ['87946.43', '753495.10'],
                'tax_category' => ['12%', 'exempt'],
                'line_amount' => ['87946.43', '753495.10'],
                'vatable_sales' => '87946.43',
                'vat_amount' => '10553.57',
                'billing_total' => '98500.00',
                'reimbursement_total' => '753495.10',
                'soa_total' => '851995.10',
            ],
            'BI-2026-01043' => [
                'customer' => 'Metro Manila Forwarders',
                'invoice_no' => 'BI-2026-01043',
                'invoice_date' => '2026-05-24',
                'due_date' => '2026-06-23',
                'soa_no' => '01043',
                'invoice_to' => "Metro Manila Forwarders\nMetro Manila",
                'credit_term_days' => '30',
                'vat_classification' => '12%',
                'class_code' => 'MANILA',
                'ar_account' => 'A/Trade-Billing Invoice',
                'invoice_template' => ['SOA_B', 'Billing Invoice'],
                'currency' => 'PHP',
                'mawb_no' => 'MAWB-SEA-0423',
                'hawb_no' => 'HAWB-0423-A',
                'date_shipped' => '2026-05-24',
                'origin_dest' => 'SINGAPORE / MANILA',
                'commodity' => 'Shipment-linked forwarding service',
                'description' => 'Shipment-linked forwarding service',
                'item' => ['Forwarding service'],
                'item_description' => ['Shipment-linked forwarding service'],
                'qty' => ['1'],
                'unit_price' => ['114196.43'],
                'tax_category' => ['12%'],
                'line_amount' => ['114196.43'],
                'vatable_sales' => '114196.43',
                'vat_amount' => '13703.57',
                'billing_total' => '127900.00',
                'reimbursement_total' => '0.00',
                'soa_total' => '127900.00',
            ],
            'SOAA-2026-00211' => [
                'customer' => 'Blue Anchor Logistics',
                'invoice_no' => 'SOAA-2026-00211',
                'invoice_date' => '2026-05-23',
                'due_date' => '2026-06-22',
                'soa_no' => '00211',
                'invoice_to' => "Blue Anchor Logistics\nCebu",
                'credit_term_days' => '30',
                'vat_classification' => 'zero-rated',
                'class_code' => 'CEBU',
                'ar_account' => 'A/R Trade-SOA A',
                'invoice_template' => ['SOA_A'],
                'currency' => 'USD',
                'mawb_no' => 'MAWB-SEA-0398',
                'hawb_no' => 'HAWB-0398-A',
                'date_shipped' => '2026-05-23',
                'origin_dest' => 'CEBU / HONG KONG',
                'commodity' => 'Export logistics service',
                'description' => 'Export logistics service',
                'item' => ['Export logistics'],
                'item_description' => ['Export logistics service'],
                'qty' => ['1'],
                'unit_price' => ['1840.00'],
                'tax_category' => ['Zero-rated'],
                'line_amount' => ['1840.00'],
                'vatable_sales' => '0.00',
                'vat_amount' => '0.00',
                'zero_rated_sales' => '1840.00',
                'billing_total' => '1840.00',
                'reimbursement_total' => '0.00',
                'soa_total' => '1840.00',
            ],
        ];
    }

    private function billingPrintInvoiceDataFromReference($reference = null)
    {
        $reference = trim((string) ($reference ?: request('invoice') ?: request('invoice_no') ?: request('document_no')));
        $customerReference = trim((string) request('customer'));
        if ($reference === '' && $customerReference === '') {
            return [];
        }

        $fallback = $reference !== '' && isset($this->billingPrintInvoiceFallbacks()[$reference]) ? $this->billingPrintInvoiceFallbacks()[$reference] : [
            'invoice_no' => $reference,
        ];
        $data = [];

        try {
            if (Schema::hasTable('sales_documents')) {
                $query = DB::table('sales_documents');
                if (Schema::hasTable('customers')) {
                    $query->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
                        ->select(
                            'sales_documents.*',
                            'customers.name as customer_name',
                            'customers.tin as customer_tin',
                            'customers.billing_address as customer_billing_address'
                        );
                } else {
                    $query->select('sales_documents.*');
                }

                if (auth()->check()) {
                    $query->where('sales_documents.company_id', $this->defaultCompanyId());
                }

                if ($reference !== '') {
                    $query->where(function ($referenceQuery) use ($reference) {
                        $this->applySalesDocumentReference($referenceQuery, $reference);
                    });
                } elseif ($customerReference !== '' && Schema::hasTable('customers')) {
                    $query->where(function ($customerQuery) use ($customerReference) {
                        $customerQuery->where('customers.name', $customerReference)
                            ->orWhere('customers.customer_id', $customerReference)
                            ->orWhere('customers.code', $customerReference);
                    })->orderBy('sales_documents.document_date', 'desc')
                        ->orderBy('sales_documents.id', 'desc');
                } else {
                    $query->whereRaw('1 = 0');
                }

                $invoice = $query->first();

                if ($invoice) {
                    $column = function ($key, $default = null) use ($invoice) {
                        return property_exists($invoice, $key) && $invoice->$key !== null ? $invoice->$key : $default;
                    };
                    $subtotal = (float) ($invoice->subtotal ?? 0);
                    $taxTotal = (float) ($invoice->tax_total ?? 0);
                    $grandTotal = (float) ($invoice->grand_total ?? 0);
                    $reimbursementTotal = (float) ($invoice->freight_total ?? 0);

                    $data = [
                        'customer' => $column('customer_name', $fallback['customer'] ?? ''),
                        'customer_id' => $column('customer_id_text', $fallback['customer_id'] ?? ''),
                        'invoice_no' => $invoice->document_no,
                        'transaction_no' => $column('transaction_no', $invoice->document_no),
                        'invoice_date' => $invoice->document_date,
                        'due_date' => $column('due_date', $fallback['due_date'] ?? ''),
                        'soa_no' => $column('soa_no', $fallback['soa_no'] ?? ''),
                        'invoice_to' => $column('invoice_to', $column('customer_billing_address', $fallback['invoice_to'] ?? '')),
                        'consignee' => $column('consignee', ''),
                        'tin' => $column('customer_tin', $fallback['tin'] ?? ''),
                        'credit_term_days' => $column('credit_term_days', $fallback['credit_term_days'] ?? ''),
                        'vat_classification' => $column('vat_classification', $fallback['vat_classification'] ?? ''),
                        'class_code' => $column('class_code', $fallback['class_code'] ?? ''),
                        'ar_account' => $this->arAccountLabel($column('ar_account_code', null)),
                        'invoice_template' => json_decode($column('invoice_templates', '[]'), true) ?: ($column('template_code') ? [$column('template_code')] : ($fallback['invoice_template'] ?? [])),
                        'currency' => $invoice->currency ?? $fallback['currency'] ?? 'PHP',
                        'exchange_rate' => $column('exchange_rate', $fallback['exchange_rate'] ?? 1),
                        'mawb_no' => $column('mawb_no', $fallback['mawb_no'] ?? ''),
                        'hawb_no' => $column('hawb_no', $fallback['hawb_no'] ?? ''),
                        'date_shipped' => $column('date_shipped', $invoice->document_date ?? $fallback['date_shipped'] ?? ''),
                        'origin_dest' => $column('origin_dest', $fallback['origin_dest'] ?? ''),
                        'commodity' => $column('commodity', $fallback['commodity'] ?? ''),
                        'description' => $invoice->remarks ?? $fallback['description'] ?? '',
                        'customer_message' => $column('customer_message', $fallback['customer_message'] ?? ''),
                        'memo' => $column('memo', $fallback['memo'] ?? ''),
                        'job_shipment_id' => $column('job_shipment_id', $column('shipment_job_id', $fallback['job_shipment_id'] ?? '')),
                        'vatable_sales' => number_format((float) $column('vatable_amount', $subtotal), 2, '.', ''),
                        'zero_rated_sales' => number_format((float) $column('zero_rated_amount', 0), 2, '.', ''),
                        'vat_exempt_sales' => number_format((float) $column('vat_exempt_amount', 0), 2, '.', ''),
                        'vat_amount' => number_format($taxTotal, 2, '.', ''),
                        'withholding_tax_rate' => $column('withholding_tax_rate', 0),
                        'withholding_tax_amount' => number_format((float) $column('withholding_tax_amount', 0), 2, '.', ''),
                        'withholding_tax' => number_format((float) $column('withholding_tax_amount', 0), 2, '.', ''),
                        'billing_total' => number_format($grandTotal - $reimbursementTotal, 2, '.', ''),
                        'reimbursement_total' => number_format($reimbursementTotal, 2, '.', ''),
                        'soa_total' => number_format($grandTotal, 2, '.', ''),
                    ];

                    if (Schema::hasTable('sales_document_lines')) {
                        $lines = DB::table('sales_document_lines')
                            ->where('sales_document_id', $invoice->id)
                            ->orderBy('id')
                            ->get();

                        if ($lines->count() > 0) {
                            $data['item'] = $lines->map(function ($line) {
                                return (property_exists($line, 'item_name') && $line->item_name) ? $line->item_name : 'Service';
                            })->all();
                            $data['item_description'] = $lines->pluck('description')->all();
                            $data['qty'] = $lines->pluck('quantity')->map(function ($qty) {
                                return rtrim(rtrim(number_format((float) $qty, 4, '.', ''), '0'), '.');
                            })->all();
                            $data['unit_price'] = $lines->pluck('unit_price')->map(function ($amount) {
                                return number_format((float) $amount, 2, '.', '');
                            })->all();
                            $data['line_amount'] = $lines->pluck('line_total')->map(function ($amount) {
                                return number_format((float) $amount, 2, '.', '');
                            })->all();
                            $data['tax_category'] = $lines->map(function ($line) use ($invoice) {
                                return (property_exists($line, 'tax_category') && $line->tax_category) ? $line->tax_category : ($invoice->vat_classification ?? '12%');
                            })->all();
                            $data['line_withholding_rate'] = $lines->map(function ($line) use ($invoice) {
                                return property_exists($line, 'withholding_tax_rate') && $line->withholding_tax_rate !== null
                                    ? rtrim(rtrim(number_format((float) $line->withholding_tax_rate, 4, '.', ''), '0'), '.')
                                    : (string) ($invoice->withholding_tax_rate ?? 0);
                            })->all();
                            $data['line_withholding_amount'] = $lines->map(function ($line) {
                                return property_exists($line, 'withholding_tax_amount') && $line->withholding_tax_amount !== null
                                    ? number_format((float) $line->withholding_tax_amount, 2, '.', '')
                                    : '0.00';
                            })->all();
                            $data['line_class'] = $lines->map(function ($line) use ($invoice) {
                                return (property_exists($line, 'class_code') && $line->class_code) ? $line->class_code : ($invoice->class_code ?? '');
                            })->all();
                            $data['line_ar_account'] = $lines->map(function ($line) use ($invoice) {
                                return (property_exists($line, 'ar_account_code') && $line->ar_account_code)
                                    ? $this->arAccountLabel($line->ar_account_code)
                                    : $this->arAccountLabel($invoice->ar_account_code ?? null);
                            })->all();

                            $hasReimbursementLine = false;
                            foreach ($data['item_description'] as $index => $description) {
                                $item = strtolower((string) ($data['item'][$index] ?? ''));
                                $description = strtolower((string) $description);
                                if (strpos($item, 'reimburs') !== false || strpos($description, 'reimburs') !== false) {
                                    $hasReimbursementLine = true;
                                    break;
                                }
                            }

                            if (!$hasReimbursementLine && !empty($fallback['item_description'])) {
                                foreach ($fallback['item_description'] as $index => $description) {
                                    $item = strtolower((string) ($fallback['item'][$index] ?? ''));
                                    $descriptionCheck = strtolower((string) $description);
                                    if (strpos($item, 'reimburs') === false && strpos($descriptionCheck, 'reimburs') === false) {
                                        continue;
                                    }

                                    $data['item'][] = $fallback['item'][$index] ?? 'Reimbursable Charges';
                                    $data['item_description'][] = $description;
                                    $data['qty'][] = $fallback['qty'][$index] ?? '1';
                                    $data['unit_price'][] = $fallback['unit_price'][$index] ?? '0.00';
                                    $data['line_amount'][] = $fallback['line_amount'][$index] ?? '0.00';
                                    $data['tax_category'][] = $fallback['tax_category'][$index] ?? 'exempt';
                                    $data['line_withholding_rate'][] = '0';
                                    $data['line_withholding_amount'][] = '0.00';
                                    $data['line_ar_account'][] = $fallback['line_ar_account'][$index] ?? 'A/R-Reimbursable Charges';
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            $data = [];
        }

        $resolved = array_replace($fallback, array_filter($data, function ($value) {
            if (is_array($value)) {
                return count($value) > 0;
            }

            return $value !== null && $value !== '';
        }));

        $totals = $this->billingTotals($resolved);
        $resolved['vatable_sales'] = number_format($totals['vatable_sales'], 2, '.', '');
        $resolved['zero_rated_sales'] = number_format($totals['zero_rated_sales'], 2, '.', '');
        $resolved['vat_exempt_sales'] = number_format($totals['vat_exempt_sales'], 2, '.', '');
        $resolved['vat_amount'] = number_format($totals['vat_amount'], 2, '.', '');
        $resolved['withholding_tax_rate'] = number_format($totals['withholding_tax_rate'], 2, '.', '');
        $resolved['withholding_tax_amount'] = number_format($totals['withholding_tax_amount'], 2, '.', '');
        $resolved['withholding_tax'] = $resolved['withholding_tax_amount'];
        $resolved['billing_total'] = number_format($totals['billing_total'], 2, '.', '');
        $resolved['reimbursement_total'] = number_format($totals['reimbursement_total'], 2, '.', '');
        $resolved['soa_total'] = number_format($totals['soa_total'], 2, '.', '');

        return $resolved;
    }

    private function applySalesDocumentReference($query, $reference)
    {
        $reference = trim((string) $reference);
        if ($reference !== '' && ctype_digit($reference)) {
            return $query->where('sales_documents.id', (int) $reference);
        }

        return $query->where('sales_documents.document_no', $reference);
    }

    private function salesDocumentRecordByReference($reference, $lockForUpdate = false)
    {
        abort_unless(Schema::hasTable('sales_documents'), 404);

        $query = DB::table('sales_documents')
            ->where('sales_documents.company_id', $this->defaultCompanyId())
            ->where(function ($referenceQuery) use ($reference) {
                $this->applySalesDocumentReference($referenceQuery, $reference);
            });

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $record = $query->first();
        abort_unless($record, 404);

        return $record;
    }

    private function invoiceTemplates()
    {
        return [
            ['key' => 'SOA_B', 'label' => 'SOA B', 'account' => 'A/Trade-Billing Invoice'],
            ['key' => 'Billing Invoice', 'label' => 'Billing Invoice', 'account' => 'A/Trade-Billing Invoice'],
            ['key' => 'SOA_A', 'label' => 'SOA A', 'account' => 'A/R Trade-SOA A'],
            ['key' => 'Debit Note', 'label' => 'Debit Note', 'account' => 'A/R Trade-Debit Note'],
        ];
    }

    private function rolePermissionRoles()
    {
        $defaults = config('rbac.roles', []);
        if (!Schema::hasTable('roles')) {
            return collect($defaults)->map(function ($role, $slug) {
                return [
                    'id' => null, 'key' => $slug, 'label' => $role['name'],
                    'color' => $role['color'], 'locked' => !empty($role['locked']),
                    'note' => $role['description'], 'enabled' => [],
                ];
            })->values()->all();
        }

        $companyId = $this->defaultCompanyId();
        $roles = Role::with('permissions')->where('is_active', true);
        $companyId === null ? $roles->whereNull('company_id') : $roles->where('company_id', $companyId);
        return $roles
            ->orderBy('id')
            ->get()
            ->map(function ($role, $index) use ($defaults) {
                $default = $defaults[$role->slug] ?? [];
                $palette = ['#64748b', '#0f766e', '#7c3aed', '#b45309', '#be123c'];
                return [
                    'id' => $role->id,
                    'key' => $role->slug,
                    'label' => $role->name,
                    'color' => $default['color'] ?? $palette[$index % count($palette)],
                    'locked' => $role->slug === 'admin' || !empty($default['locked']),
                    'note' => $role->description ?: 'Custom company role',
                    'enabled' => $role->permissions->pluck('slug')->all(),
                ];
            })->all();
    }

    private function rolePermissionGroups()
    {
        return collect(app(RbacService::class)->moduleDefinitions())->map(function ($modules) {
            return collect($modules)->map(function ($module, $key) {
                return ['key' => $key, 'label' => $module['label'], 'permissions' => $module['actions']];
            })->values()->all();
        })->all();
    }

    private function billingPrintDocuments($vatClassification, array $totals = [], array $selectedTemplates = [], array $invoiceData = [])
    {
        $value = function ($key, $default = null) use ($invoiceData) {
            return $invoiceData[$key] ?? request($key, $default);
        };

        $query = array_filter([
            'customer' => $value('customer_name', $value('customer')),
            'invoice_no' => $value('invoice_no'),
            'invoice_date' => $value('invoice_date'),
            'soa_no' => $value('soa_no'),
            'invoice_to' => $value('invoice_to'),
            'consignee' => $value('consignee'),
            'tin' => $value('tin'),
            'currency' => $value('currency'),
            'exchange_rate' => $value('exchange_rate'),
            'mawb_no' => $value('mawb_no'),
            'hawb_no' => $value('hawb_no'),
            'date_shipped' => $value('date_shipped'),
            'origin_dest' => $value('origin_dest'),
            'commodity' => $value('commodity'),
            'description' => $value('description'),
            'shipper' => $value('shipper'),
            'customer_message' => $value('customer_message'),
            'memo' => $value('memo'),
            'item' => (array) $value('item', []),
            'item_description' => (array) $value('item_description', []),
            'qty' => (array) $value('qty', []),
            'unit_price' => (array) $value('unit_price', []),
            'tax_category' => (array) $value('tax_category', []),
            'line_withholding_rate' => (array) $value('line_withholding_rate', []),
            'line_withholding_amount' => (array) $value('line_withholding_amount', []),
            'line_amount' => (array) $value('line_amount', []),
            'line_ar_account' => (array) $value('line_ar_account', []),
            'withholding_tax_rate' => isset($totals['withholding_tax_rate']) ? number_format($totals['withholding_tax_rate'], 4, '.', '') : null,
            'withholding_tax' => isset($totals['withholding_tax_amount']) ? number_format($totals['withholding_tax_amount'], 2, '.', '') : null,
            'billing_total' => isset($totals['billing_total']) ? number_format($totals['billing_total'], 2, '.', '') : null,
            'reimbursement_total' => isset($totals['reimbursement_total']) ? number_format($totals['reimbursement_total'], 2, '.', '') : null,
            'soa_total' => isset($totals['soa_total']) ? number_format($totals['soa_total'], 2, '.', '') : null,
            'vatable_sales' => isset($totals['vatable_sales']) ? number_format($totals['vatable_sales'], 2, '.', '') : null,
            'vat_amount' => isset($totals['vat_amount']) ? number_format($totals['vat_amount'], 2, '.', '') : null,
        ], function ($value) {
            if (is_array($value)) {
                return count(array_filter($value, function ($item) {
                    return $item !== null && $item !== '';
                })) > 0;
            }

            return $value !== null && $value !== '';
        });

        $templateRoutes = [
            'SOA_B' => ['label' => 'Print SOA B', 'route' => route('erp.billings.print-soa', $query), 'icon' => 'bi-file-earmark-text'],
            'Billing Invoice' => ['label' => 'Print Billing Invoice', 'route' => route('erp.billings.print-invoice', $query), 'icon' => 'bi-printer'],
            'SOA_A' => ['label' => 'Print SOA A', 'route' => route('erp.soa-a.preview', $query), 'icon' => 'bi-file-earmark-text'],
            'Debit Note' => ['label' => 'Print Debit Note', 'route' => route('erp.debit-note.preview', $query), 'icon' => 'bi-file-earmark-minus'],
        ];

        if (empty($selectedTemplates)) {
            $selectedTemplates = $vatClassification === '12%' ? ['SOA_B', 'Billing Invoice'] : ['SOA_A'];
        }

        if ($vatClassification === '12%' && in_array('SOA_B', $selectedTemplates, true) && !in_array('Billing Invoice', $selectedTemplates, true)) {
            $selectedTemplates[] = 'Billing Invoice';
        }

        $documents = [];
        foreach ($selectedTemplates as $template) {
            if (isset($templateRoutes[$template])) {
                $documents[$template] = $templateRoutes[$template];
            } else {
                $documents[$template] = ['label' => 'Template: '.$template, 'route' => route('erp.billings.print-invoice', $query), 'icon' => 'bi-file-earmark-text'];
            }
        }

        return array_values($documents);
    }

    private function billingTotals(array $data)
    {
        $billingSubtotal = 0.0;
        $vatableSales = 0.0;
        $zeroRatedSales = 0.0;
        $vatExemptSales = 0.0;
        $reimbursementTotal = 0.0;
        $vatAmount = 0.0;
        $withholdingTaxAmount = 0.0;
        $withholdingBase = 0.0;
        $lineWithholdingRates = (array) ($data['line_withholding_rate'] ?? []);
        $defaultWithholdingRate = min(100, max(0, $this->moneyToFloat($data['withholding_tax_rate'] ?? 0)));

        foreach (($data['line_amount'] ?? []) as $index => $amount) {
            $lineAmount = $this->moneyToFloat($amount);
            $isReimbursement = $this->isReimbursableInvoiceLine($data, $index);

            if ($isReimbursement) {
                $reimbursementTotal += $lineAmount;
                continue;
            }

            $billingSubtotal += $lineAmount;
            $lineRate = trim((string) ($lineWithholdingRates[$index] ?? '')) === ''
                ? $defaultWithholdingRate
                : min(100, max(0, $this->moneyToFloat($lineWithholdingRates[$index])));
            $withholdingBase += $lineAmount;
            $withholdingTaxAmount += $lineAmount * ($lineRate / 100);
            $taxCategory = strtolower(trim((string) ($data['tax_category'][$index] ?? '')));
            if ($taxCategory === '') {
                $taxCategory = strtolower(trim((string) ($data['vat_classification'] ?? '')));
            }

            if (strpos($taxCategory, 'zero') !== false) {
                $zeroRatedSales += $lineAmount;
            } elseif (strpos($taxCategory, 'exempt') !== false) {
                $vatExemptSales += $lineAmount;
            } else {
                $vatableSales += $lineAmount;
            }

            if ($taxCategory === '12%' || $taxCategory === 'vatable') {
                $vatAmount += $lineAmount * 0.12;
            }
        }

        $withholdingTaxAmount = round($withholdingTaxAmount, 4);
        $withholdingTaxRate = $withholdingBase > 0
            ? round(($withholdingTaxAmount / $withholdingBase) * 100, 4)
            : 0;

        return [
            'billing_subtotal' => $billingSubtotal,
            'vatable_sales' => $vatableSales,
            'zero_rated_sales' => $zeroRatedSales,
            'vat_exempt_sales' => $vatExemptSales,
            'vat_amount' => $vatAmount,
            'withholding_tax_rate' => $withholdingTaxRate,
            'withholding_tax_amount' => $withholdingTaxAmount,
            'billing_total' => $billingSubtotal + $vatAmount,
            'reimbursement_total' => $reimbursementTotal,
            'soa_total' => $billingSubtotal + $vatAmount + $reimbursementTotal,
        ];
    }

    private function isReimbursableInvoiceLine(array $data, $index)
    {
        $accounts = (array) ($data['line_ar_account'] ?? []);
        $account = strtolower(trim((string) ($accounts[$index] ?? '')));

        if ($account !== '') {
            $normalized = str_replace([' ', '/', '-', '_'], '', $account);
            return strpos($normalized, 'arreimbursable') !== false
                || strpos($normalized, 'reimbursablecharges') !== false;
        }

        $item = strtolower((string) (($data['item'] ?? [])[$index] ?? ''));
        $description = strtolower((string) (($data['item_description'] ?? [])[$index] ?? ''));

        return strpos($item, 'reimburs') !== false || strpos($description, 'reimburs') !== false;
    }

    private function moneyToFloat($value)
    {
        return (float) str_replace(',', '', (string) $value);
    }

    private function firstPrintLineValue($key, $default = '')
    {
        $value = request($key, $default);

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($item !== null && $item !== '') {
                    return $item;
                }
            }

            return $default;
        }

        return $value;
    }

    private function displayPrintDate($date, $default)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return $default;
        }

        $timestamp = strtotime($date);
        return $timestamp ? date('m/d/Y', $timestamp) : $date;
    }

    private function billingPrintPreviewData()
    {
        $invoiceData = $this->billingPrintInvoiceDataFromReference();
        $read = function ($key, $default = null) use ($invoiceData) {
            return request()->has($key) ? request($key) : ($invoiceData[$key] ?? $default);
        };
        $firstLineValue = function ($key, $default = '') use ($read) {
            $value = $read($key, $default);

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item !== null && $item !== '') {
                        return $item;
                    }
                }

                return $default;
            }

            return $value;
        };
        $lineValue = function ($values, $index, $default = '') {
            return is_array($values) ? ($values[$index] ?? $default) : $default;
        };

        $billingTotal = $this->moneyToFloat($read('billing_total', '0'));
        $reimbursementTotal = $this->moneyToFloat($read('reimbursement_total', '0'));
        $soaTotal = request()->has('soa_total') || array_key_exists('soa_total', $invoiceData)
            ? $this->moneyToFloat($read('soa_total'))
            : $billingTotal + $reimbursementTotal;
        $items = (array) $read('item', []);
        $descriptions = (array) $read('item_description', []);
        $quantities = (array) $read('qty', []);
        $unitPrices = (array) $read('unit_price', []);
        $lineAmounts = (array) $read('line_amount', []);
        $taxCategories = (array) $read('tax_category', []);
        $lineAccounts = (array) $read('line_ar_account', []);
        $lineCount = max(count($items), count($descriptions), count($quantities), count($unitPrices), count($lineAmounts), count($lineAccounts), 1);
        $billingLineIndexes = [];
        $reimbursementLineIndexes = [];
        $zeroRatedSales = 0.0;
        $vatExemptSales = 0.0;
        $vatableSales = 0.0;

        for ($index = 0; $index < $lineCount; $index++) {
            $isReimbursement = $this->isReimbursableInvoiceLine([
                'item' => $items,
                'item_description' => $descriptions,
                'line_ar_account' => $lineAccounts,
            ], $index);
            $taxCategory = strtolower((string) ($taxCategories[$index] ?? ''));
            $amount = $lineValue($lineAmounts, $index);

            if ($isReimbursement) {
                $reimbursementLineIndexes[] = $index;
                continue;
            }

            if (trim((string) $lineValue($descriptions, $index)) !== '' || $amount !== '') {
                $billingLineIndexes[] = $index;
            }

            if (strpos($taxCategory, 'zero') !== false) {
                $zeroRatedSales += $this->moneyToFloat($amount);
            } elseif (strpos($taxCategory, 'exempt') !== false) {
                $vatExemptSales += $this->moneyToFloat($amount);
            } else {
                $vatableSales += $this->moneyToFloat($amount);
            }
        }

        if (empty($billingLineIndexes)) {
            $billingLineIndexes[] = 0;
        }

        $billingLineIndex = $billingLineIndexes[0];
        $billingDescriptions = [];
        $billingAmounts = [];
        $billingLines = [];
        foreach ($billingLineIndexes as $index) {
            $description = trim((string) $lineValue($descriptions, $index));
            $quantity = $lineValue($quantities, $index, '');
            $unitPrice = $this->moneyToFloat($lineValue($unitPrices, $index));
            $amount = $this->moneyToFloat($lineValue($lineAmounts, $index));
            if ($amount === 0.0 && $unitPrice !== 0.0) {
                $amount = $unitPrice * max(1, $this->moneyToFloat($quantity));
            }
            if ($description !== '') {
                $billingDescriptions[] = $description;
            }
            if ($amount !== 0.0) {
                $billingAmounts[] = number_format($amount, 2);
            }
            $billingLines[] = [
                'description' => $description !== '' ? $description : trim((string) $lineValue($items, $index)),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
            ];
        }

        $lineDescription = trim(implode("\n", $billingDescriptions));
        if ($lineDescription === '') {
            $lineDescription = trim((string) $read('description'));
        }
        $shipper = trim((string) $read('shipper', ''));
        if ($shipper === '' && preg_match('/SHIPPER:\s*([^\r\n]+)/i', $lineDescription, $matches)) {
            $shipper = trim($matches[1]);
        }

        $soaParticulars = [];
        $soaAmounts = [];
        foreach ($reimbursementLineIndexes as $index) {
            $description = trim((string) $lineValue($descriptions, $index));
            $amount = $this->moneyToFloat($lineValue($lineAmounts, $index));

            if ($description !== '') {
                $soaParticulars[] = $description;
            }
            if ($amount !== 0.0) {
                $soaAmounts[] = number_format($amount, 2);
            }
        }

        $resolvedVatableSales = request()->has('vatable_sales')
            ? $this->moneyToFloat($read('vatable_sales', '0'))
            : max($vatableSales, $this->moneyToFloat($invoiceData['vatable_sales'] ?? 0));
        $resolvedZeroRatedSales = request()->has('zero_rated_sales')
            ? $this->moneyToFloat($read('zero_rated_sales', '0'))
            : max($zeroRatedSales, $this->moneyToFloat($invoiceData['zero_rated_sales'] ?? 0));
        $resolvedVatExemptSales = request()->has('vat_exempt_sales')
            ? $this->moneyToFloat($read('vat_exempt_sales', '0'))
            : max($vatExemptSales, $this->moneyToFloat($invoiceData['vat_exempt_sales'] ?? 0));

        // Older invoices may have a total but no populated tax buckets or line tax category.
        // Rebuild the applicable bucket from the document-level VAT classification.
        if (($resolvedVatableSales + $resolvedZeroRatedSales + $resolvedVatExemptSales) <= 0 && $billingTotal > 0) {
            $taxClassification = strtolower(trim((string) $read('vat_classification', '')));
            $taxableBase = max(0, $billingTotal - $this->moneyToFloat($read('vat_amount', '0')));

            if (strpos($taxClassification, 'zero') !== false) {
                $resolvedZeroRatedSales = $taxableBase;
            } elseif (strpos($taxClassification, 'exempt') !== false || strpos($taxClassification, 'non-vat') !== false) {
                $resolvedVatExemptSales = $taxableBase;
            } else {
                $resolvedVatableSales = $taxableBase;
            }
        }

        return [
            'customer' => $read('customer', ''),
            'invoice_no' => $read('invoice_no', $read('invoice', '')),
            'invoice_date' => $this->displayPrintDate($read('invoice_date'), ''),
            'soa_no' => $read('soa_no', ''),
            'address' => $read('invoice_to', ''),
            'tin' => $read('tin', ''),
            'currency' => $read('currency', 'PHP'),
            'exchange_rate' => $read('exchange_rate', 1),
            'mawb' => $read('mawb_no', ''),
            'hawb' => $read('hawb_no', ''),
            'date_shipped' => $this->displayPrintDate($read('date_shipped'), ''),
            'origin_dest' => $read('origin_dest', ''),
            'commodity' => $read('commodity', ''),
            'line_description' => $lineDescription,
            'shipper' => $shipper,
            'line_qty' => $lineValue($quantities, $billingLineIndex, $firstLineValue('qty', '')),
            'line_unit_price' => $this->moneyToFloat($lineValue($unitPrices, $billingLineIndex, $firstLineValue('unit_price', '0'))),
            'line_amount' => $this->moneyToFloat($lineValue($lineAmounts, $billingLineIndex, $firstLineValue('line_amount', '0'))),
            'billing_lines' => $billingLines,
            'billing_amounts' => implode("\n", $billingAmounts),
            'soa_particulars' => implode("\n", $soaParticulars),
            'soa_amounts' => implode("\n", $soaAmounts),
            'vatable_sales' => $resolvedVatableSales,
            'vat_amount' => $this->moneyToFloat($read('vat_amount', '0')),
            'zero_rated_sales' => $resolvedZeroRatedSales,
            'vat_exempt_sales' => $resolvedVatExemptSales,
            'discount' => $this->moneyToFloat($read('discount', '0')),
            'withholding_tax' => $this->moneyToFloat($read('withholding_tax', '0')),
            'prepared_by' => trim((string) $read('prepared_by', '')) !== '' ? $read('prepared_by', '') : (auth()->user()->name ?? 'User'),
            'billing_total' => $billingTotal,
            'reimbursement_total' => $reimbursementTotal,
            'soa_total' => $soaTotal,
            'memo' => $read('memo', ''),
            'customer_message' => $read('customer_message', ''),
            'description' => $read('description', ''),
            'remarks' => trim((string) $read('memo', '')) !== '' ? $read('memo', '') : $read('description', ''),
            'job_shipment_id' => $read('job_shipment_id', $read('shipment_job_id', '')),
        ];
    }

    public function invoiceReview()
    {
        $data = request()->only([
            'customer_id',
            'customer_name',
            'job_shipment_id',
            'invoice_no',
            'transaction_no',
            'soa_no',
            'invoice_date',
            'due_date',
            'invoice_to',
            'invoice_amount',
            'total_payment',
            'consignee',
            'credit_term_days',
            'tin',
            'vat_classification',
            'class_code',
            'currency',
            'exchange_rate',
            'withholding_tax_rate',
            'ar_account',
            'invoice_template',
            'customer_message',
            'memo',
            'customer_tax_code',
            'description',
            'amount',
            'mawb_no',
            'hawb_no',
            'date_shipped',
            'origin_dest',
            'commodity',
            'item',
            'item_description',
            'qty',
            'unit_price',
            'line_class',
            'line_ar_account',
            'tax_category',
            'line_withholding_rate',
            'line_withholding_amount',
            'line_amount',
            'original_invoice_no',
        ]);

        request()->validate([
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'line_withholding_rate' => 'nullable|array',
            'line_withholding_rate.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $originalInvoiceNo = trim((string) ($data['original_invoice_no'] ?? ''));
        $selectedTemplates = array_values(array_filter(array_map('trim', (array) ($data['invoice_template'] ?? []))));
        $invoiceNo = trim((string) ($data['invoice_no'] ?? ''));
        $transactionNo = trim((string) ($data['transaction_no'] ?? ''));
        $data['invoice_no'] = $invoiceNo !== ''
            ? $invoiceNo
            : ($transactionNo !== '' ? $transactionNo : ($originalInvoiceNo !== '' ? $originalInvoiceNo : $this->nextSalesDocumentNumber($selectedTemplates)));
        $data['transaction_no'] = $data['invoice_no'];

        $duplicateQuery = DB::table('sales_documents')
            ->where('company_id', $this->defaultCompanyId())
            ->where('document_no', $data['invoice_no']);
        if ($originalInvoiceNo !== '') {
            $duplicateQuery->where('document_no', '<>', $originalInvoiceNo);
        }
        if ($duplicateQuery->exists()) {
            return redirect()->back()->withInput()->withErrors(['invoice_no' => 'This invoice number already exists.']);
        }

        $totals = $this->billingTotals($data);
        $data['invoice_amount'] = number_format($totals['billing_total'], 2);
        $data['withholding_tax_amount'] = number_format($totals['withholding_tax_amount'], 2);
        $data['amount'] = number_format($totals['soa_total'], 2);
        $data['reimbursement_total'] = number_format($totals['reimbursement_total'], 2);
        $data['soa_total'] = number_format($totals['soa_total'], 2);
        $data['invoice_template'] = $selectedTemplates;
        $data['invoice_templates_display'] = implode(', ', $selectedTemplates);
        if (request('save_mode') === 'post') {
            $this->authorizeAccountingPosting('invoices', 'approve');
        }

        $shouldPost = request('save_mode') === 'post';
        $savedInvoice = DB::transaction(function () use ($data, $totals, $shouldPost) {
            $savedInvoice = $this->saveInvoiceData($data, $totals);
            if ($shouldPost) {
                app(PostingService::class)->postSalesDocument($savedInvoice->id);
                $savedInvoice = DB::table('sales_documents')
                    ->where('company_id', $this->defaultCompanyId())
                    ->where('id', $savedInvoice->id)
                    ->first();
            }

            return $savedInvoice;
        });
        $data['id'] = $savedInvoice->id;
        $data['status'] = $savedInvoice->status;
        $data['saved_message'] = request('save_mode') === 'post'
            ? 'Invoice '.$data['invoice_no'].' was posted to the general ledger.'
            : 'Invoice '.$data['invoice_no'].' was saved as a draft.';

        $message = $originalInvoiceNo !== ''
            ? 'Invoice '.$data['invoice_no'].' was updated.'
            : (request('save_mode') === 'post'
                ? 'Invoice '.$data['invoice_no'].' was created and posted.'
                : 'Invoice '.$data['invoice_no'].' was saved as a draft.');

        return redirect()
            ->route('erp.invoices.show', $savedInvoice->id)
            ->with('success', $message);
    }

    public function paymentCreate()
    {
        $customers = $this->paymentCustomerOptions();
        $invoices = $this->paymentInvoiceOptions();
        $payments = $this->paymentRows();

        $bankAccounts = $this->bankAccounts();

        return view('erp.payments.create', compact('customers', 'invoices', 'payments', 'bankAccounts'));
    }

    private function paymentRows()
    {
        if (Schema::hasTable('receipts')) {
            $invoiceNumbers = DB::connection()->getDriverName() === 'sqlite'
                ? DB::raw('GROUP_CONCAT(sales_documents.document_no, ", ") as invoice_numbers')
                : DB::raw('GROUP_CONCAT(sales_documents.document_no ORDER BY sales_documents.document_no SEPARATOR ", ") as invoice_numbers');
            $forPaymentAmount = DB::raw('COALESCE(SUM(CASE WHEN sales_documents.id IS NULL THEN 0 '
                .'WHEN (COALESCE(sales_documents.grand_total, 0) - COALESCE(sales_documents.withholding_tax_amount, 0)) > 0 '
                .'THEN COALESCE(sales_documents.grand_total, 0) - COALESCE(sales_documents.withholding_tax_amount, 0) '
                .'ELSE 0 END), 0) as for_payment');

            return DB::table('receipts')
                ->leftJoin('customers', 'customers.id', '=', 'receipts.customer_id')
                ->leftJoin('receipt_applications', 'receipt_applications.receipt_id', '=', 'receipts.id')
                ->leftJoin('sales_documents', 'sales_documents.id', '=', 'receipt_applications.sales_document_id')
                ->select(
                    'receipts.id',
                    'receipts.status',
                    'receipts.receipt_no',
                    'receipts.receipt_date',
                    'receipts.currency',
                    'receipts.amount',
                    'receipts.payment_method',
                    'receipts.reference_no',
                    'customers.name as customer_name',
                    $invoiceNumbers,
                    $forPaymentAmount
                )
                ->where('receipts.company_id', $this->defaultCompanyId())
                ->groupBy(
                    'receipts.id',
                    'receipts.status',
                    'receipts.receipt_no',
                    'receipts.receipt_date',
                    'receipts.currency',
                    'receipts.amount',
                    'receipts.payment_method',
                    'receipts.reference_no',
                    'customers.name'
                )
                ->orderBy('receipts.receipt_date', 'desc')
                ->orderBy('receipts.id', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'status' => ucfirst($payment->status ?: 'posted'),
                        'number' => $payment->receipt_no,
                        'client' => $payment->customer_name ?: 'Unassigned Customer',
                        'currency' => $payment->currency ?: 'PHP',
                        'for_payment' => number_format((float) $payment->for_payment, 2),
                        'amount' => number_format((float) $payment->amount, 2),
                        'invoice_number' => $payment->invoice_numbers ?: '',
                        'date' => $payment->receipt_date,
                        'payment_method' => $payment->payment_method,
                        'reference_no' => $payment->reference_no,
                    ];
                })
                ->all();
        }

        return [];
    }

    private function paymentCustomerOptions()
    {
        if (!Schema::hasTable('customers')) {
            return [];
        }

        return DB::table('customers')
            ->select('id', 'code', 'customer_id', 'name', 'currency')
            ->where('company_id', $this->defaultCompanyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'customer_id' => $customer->customer_id ?: $customer->code,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'currency' => $customer->currency ?: 'PHP',
                ];
            })
            ->all();
    }

    private function paymentInvoiceOptions()
    {
        if (!Schema::hasTable('sales_documents')) {
            return [];
        }

        $invoices = DB::table('sales_documents')
            ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->select(
                'sales_documents.id',
                'sales_documents.document_no',
                'sales_documents.currency',
                'sales_documents.grand_total',
                'sales_documents.invoice_amount',
                'sales_documents.freight_total',
                'sales_documents.withholding_tax_amount',
                'sales_documents.paid_total',
                'sales_documents.customer_id',
                'customers.name as customer_name'
            )
            ->where('sales_documents.company_id', $this->defaultCompanyId())
            ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'soa', 'soa_a', 'statement_of_account_a', 'debit_note'])
            ->whereNotIn('sales_documents.status', ['cancelled', 'voided', 'paid'])
            ->orderBy('sales_documents.document_date', 'desc')
            ->limit(300)
            ->get();

        $appliedCwt = collect();
        if (Schema::hasTable('receipt_applications') && Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'cwt_amount')) {
            $appliedCwt = DB::table('receipt_applications')
                ->join('receipts', 'receipts.id', '=', 'receipt_applications.receipt_id')
                ->whereIn('receipt_applications.sales_document_id', $invoices->pluck('id')->all())
                ->whereNotIn('receipts.status', ['cancelled', 'voided'])
                ->groupBy('receipt_applications.sales_document_id')
                ->selectRaw('receipt_applications.sales_document_id, COALESCE(SUM(receipts.cwt_amount), 0) as cwt_total')
                ->pluck('cwt_total', 'sales_document_id');
        }

        return $invoices
            ->map(function ($invoice) use ($appliedCwt) {
                $amounts = $this->paymentInvoiceAmounts($invoice, (float) $appliedCwt->get($invoice->id, 0));

                return [
                    'id' => $invoice->id,
                    'document_no' => $invoice->document_no,
                    'currency' => $invoice->currency ?: 'PHP',
                    'grand_total' => (float) $invoice->grand_total,
                    'paid_total' => (float) $invoice->paid_total,
                    'balance' => $amounts['cash_balance'],
                    'cwt_balance' => $amounts['cwt_balance'],
                    'settlement_balance' => $amounts['settlement_balance'],
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer_name ?: 'Guest / Non-tax Client',
                ];
            })
            ->filter(function ($invoice) {
                return $invoice['balance'] > 0.005 || $invoice['cwt_balance'] > 0.005;
            })
            ->take(150)
            ->values()
            ->all();
    }

    private function paymentInvoiceAmounts($invoice, $appliedCwt = 0)
    {
        $grandTotal = max(0, (float) ($invoice->grand_total ?? 0));
        $invoiceTotal = $grandTotal;
        if ($invoiceTotal <= 0) {
            $invoiceTotal = max(0, (float) ($invoice->invoice_amount ?? 0))
                + max(0, (float) ($invoice->freight_total ?? 0));
        }

        $paidTotal = max(0, (float) ($invoice->paid_total ?? 0));
        $settlementBalance = max(0, $invoiceTotal - $paidTotal);
        $withholdingTotal = min($invoiceTotal, max(0, (float) ($invoice->withholding_tax_amount ?? 0)));
        $cwtBalance = min($settlementBalance, max(0, $withholdingTotal - max(0, (float) $appliedCwt)));

        return [
            'invoice_total' => $invoiceTotal,
            'settlement_balance' => $settlementBalance,
            'cwt_balance' => $cwtBalance,
            'cash_balance' => max(0, $settlementBalance - $cwtBalance),
        ];
    }

    public function paymentReceipt()
    {
        $data = request()->validate([
            'receipt_type' => 'nullable|string|max:100',
            'receipt_no' => 'nullable|string|max:100',
            'customer_id' => 'required|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'job_shipment_id' => 'nullable|string|max:100',
            'invoice_no' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'bank_account_id' => 'nullable|integer',
            'reference_no' => 'nullable|string|max:100',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'amount' => 'required',
            'cwt_amount' => 'nullable',
            'cwt_certificate_no' => 'nullable|string|max:100',
        ]);
        $this->authorizeAccountingPosting('payments', 'create');

        $payment = request()->only([
            'receipt_type',
            'receipt_no',
            'customer_id',
            'customer_name',
            'job_shipment_id',
            'invoice_no',
            'project',
            'payment_date',
            'payment_method',
            'payment_type',
            'reference_no',
            'private_notes',
            'send_email',
            'convert_currency',
            'currency',
            'amount',
            'cwt_amount',
            'cwt_certificate_no',
        ]);

        if (!Schema::hasTable('receipts') || !Schema::hasTable('customers')) {
            return back()->withInput()->withErrors(['customer_id' => 'Payments cannot be recorded right now: the receipts ledger is not set up.']);
        }

        $customer = $this->resolveArCustomerForPayment(request('customer_id'), request('customer_name'));
        if (!$customer) {
            return back()->withInput()->withErrors(['customer_id' => 'Select a valid client before recording this payment. The receipt was not saved.']);
        }

        $amount = $this->moneyToFloat($data['amount']);
        $cwtAmount = max(0, $this->moneyToFloat($data['cwt_amount'] ?? 0));
        if ($amount <= 0) {
            return back()->withInput()->withErrors(['amount' => 'Enter a payment amount greater than zero. The receipt was not saved.']);
        }

        $companyId = $customer->company_id;
        $invoice = null;

        if (Schema::hasTable('sales_documents') && request('invoice_no')) {
            $invoice = DB::table('sales_documents')
                ->where('company_id', $companyId)
                ->where('document_no', request('invoice_no'))
                ->where('customer_id', $customer->id)
                ->first();

            if ($invoice && in_array(strtolower((string) $invoice->status), ['cancelled', 'voided'], true)) {
                return back()->withInput()->withErrors(['invoice_no' => 'A cancelled or voided invoice cannot receive a payment.']);
            }
        }

        $receiptNo = request('receipt_no') ?: $this->nextReceiptNumber($companyId);

        $settlementAmount = $amount + $cwtAmount;
        $invoiceAmounts = null;
        if ($invoice) {
            $appliedCwt = Schema::hasTable('receipt_applications') && Schema::hasColumn('receipts', 'cwt_amount')
                ? (float) DB::table('receipt_applications')
                    ->join('receipts', 'receipts.id', '=', 'receipt_applications.receipt_id')
                    ->where('receipt_applications.sales_document_id', $invoice->id)
                    ->whereNotIn('receipts.status', ['cancelled', 'voided'])
                    ->sum('receipts.cwt_amount')
                : 0;
            $invoiceAmounts = $this->paymentInvoiceAmounts($invoice, $appliedCwt);
            if ($invoiceAmounts['settlement_balance'] <= 0.005) {
                return back()->withInput()->withErrors(['invoice_no' => 'The selected invoice is already fully paid.']);
            }
            if ($settlementAmount - $invoiceAmounts['settlement_balance'] > 0.005) {
                return back()->withInput()->withErrors(['amount' => 'Cash plus CWT cannot exceed the selected Billing Invoice balance.']);
            }
        }

        $currency = strtoupper($data['currency']);
        $exchangeRate = $currency === 'PHP' ? 1 : max(0.000001, $this->moneyToFloat($data['exchange_rate'] ?? 1));

        $receiptId = DB::transaction(function () use ($customer, $companyId, $invoice, $invoiceAmounts, $receiptNo, $amount, $cwtAmount, $settlementAmount, $currency, $exchangeRate, $data) {
            $receiptId = DB::table('receipts')->insertGetId([
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'receipt_no' => $receiptNo,
                'receipt_type' => $data['receipt_type'] ?? 'collection_receipt',
                'customer_id_text' => $customer->customer_id ?: $customer->code,
                'job_shipment_id' => request('job_shipment_id'),
                'class_code' => request('class_code', 'MANILA'),
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'receipt_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'amount' => $amount,
                'cwt_amount' => $cwtAmount,
                'cwt_certificate_no' => $data['cwt_certificate_no'] ?? null,
                'status' => 'posted',
                'template_code' => request('template_code'),
                'uses_pre_printed_form' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($invoice && Schema::hasTable('receipt_applications')) {
                DB::table('receipt_applications')->insert([
                    'receipt_id' => $receiptId,
                    'sales_document_id' => $invoice->id,
                    'amount_applied' => min($settlementAmount, $invoiceAmounts['settlement_balance']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $paidTotal = min($invoiceAmounts['invoice_total'], (float) $invoice->paid_total + $settlementAmount);
                DB::table('sales_documents')->where('id', $invoice->id)->update([
                    'paid_total' => $paidTotal,
                    'total_payment' => $paidTotal,
                    'balance_due' => max(0, $invoiceAmounts['invoice_total'] - $paidTotal),
                    'status' => $paidTotal >= $invoiceAmounts['invoice_total'] - 0.005 ? 'paid' : 'partial',
                    'updated_at' => now(),
                ]);
            }

            app(PostingService::class)->postReceipt($receiptId);

            return $receiptId;
        });

        $payment['receipt_no'] = $receiptNo;
        $payment['customer_name'] = $customer->name;

        $receiptQuery = array_filter([
            'receipt_no' => $payment['receipt_no'] ?? null,
            'customer' => $payment['customer_name'] ?? null,
            'invoice_no' => $payment['invoice_no'] ?? null,
            'payment_date' => $payment['payment_date'] ?? null,
            'payment_method' => $payment['payment_method'] ?? null,
            'reference_no' => $payment['reference_no'] ?? null,
            'currency' => $payment['currency'] ?? null,
            'amount' => $payment['amount'] ?? null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        return view('erp.payments.receipt', [
            'payment' => $payment,
            'receiptDocuments' => [
                ['label' => 'Print Collection Receipt', 'route' => route('erp.official-receipt.preview', $receiptQuery), 'icon' => 'bi-receipt'],
                ['label' => 'Print Acknowledgement Receipt', 'route' => route('erp.acknowledgement-receipt.preview', $receiptQuery), 'icon' => 'bi-receipt-cutoff'],
            ],
        ]);
    }

    private function resolveArCustomerForPayment($customerIdOrCode, $customerName = null)
    {
        $customerIdOrCode = trim((string) $customerIdOrCode);
        $customerName = trim((string) $customerName);

        if ($customerIdOrCode === '' && $customerName === '') {
            return null;
        }

        return DB::table('customers')
            ->where('company_id', $this->defaultCompanyId())
            ->where(function ($query) use ($customerIdOrCode, $customerName) {
                if ($customerIdOrCode !== '') {
                    $query->orWhere('code', $customerIdOrCode)
                        ->orWhere('customer_id', $customerIdOrCode);
                    if (ctype_digit($customerIdOrCode)) {
                        $query->orWhere('id', $customerIdOrCode);
                    }
                }
                if ($customerName !== '') {
                    $query->orWhere('name', $customerName);
                }
            })
            ->first();
    }

    private function nextReceiptNumber($companyId)
    {
        if (!$companyId) {
            return 'OR-' . date('YmdHis');
        }

        $lastReceipt = DB::table('receipts')
            ->where('company_id', $companyId)
            ->where('receipt_no', 'like', 'OR-%')
            ->orderBy('id', 'desc')
            ->value('receipt_no');

        $number = $lastReceipt ? (int) preg_replace('/\D/', '', $lastReceipt) : 0;

        return 'OR-' . str_pad($number + 1, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeAccountingPosting($module = 'accounting', $action = 'approve')
    {
        abort_unless(
            app(RbacService::class)->allows(auth()->user(), $module, $action),
            403,
            'Your role does not have the required '.$module.'.'.$action.' permission.'
        );
    }

    public function soaPreview()
    {
        return view('erp.print.soa', [
            'print' => $this->billingPrintPreviewData(),
            'settings' => $this->soaPrintSettings('soa_b'),
            'positions' => $this->soaPrintPositions('soa_b'),
            'fieldStyles' => $this->soaPrintFieldStyles('soa_b'),
            'customFields' => $this->soaPrintCustomFields('soa_b'),
            'hiddenFields' => $this->soaPrintHiddenFields('soa_b'),
        ]);
    }

    public function soaAPreview()
    {
        return view('erp.print.soa-a', [
            'print' => $this->billingPrintPreviewData(),
            'settings' => $this->soaPrintSettings('soa_a'),
            'positions' => $this->soaPrintPositions('soa_a'),
            'fieldStyles' => $this->soaPrintFieldStyles('soa_a'),
            'customFields' => $this->soaPrintCustomFields('soa_a'),
            'hiddenFields' => $this->soaPrintHiddenFields('soa_a'),
        ]);
    }

    public function debitNotePreview()
    {
        return view('erp.print.debit-note', [
            'print' => $this->billingPrintPreviewData(),
        ]);
    }

    public function officialReceiptPreview()
    {
        return view('erp.print.official-receipt', [
            'receipt' => $this->officialReceiptPrintData(request('receipt_no') ?: request('receipt'), 'or'),
            'receiptLayout' => $this->receiptPrintLayout('or'),
            'receiptFieldStyles' => $this->receiptPrintFieldStyles('or'),
            'receiptFontDefaults' => $this->receiptPrintFontDefaults(),
            'receiptFontOptions' => $this->receiptPrintFontOptions(),
            'receiptFieldLabels' => $this->receiptPrintFieldLabels(),
            'receiptHiddenFields' => $this->receiptPrintHiddenFields('or'),
            'adjustLayout' => request('adjust') === '1',
        ]);
    }

    public function saveOfficialReceiptPrintLayout()
    {
        return $this->saveReceiptPrintLayout('or');
    }

    private function officialReceiptPrintData($receiptRef = null, $documentKind = 'or')
    {
        $isAcknowledgement = $documentKind === 'ar';

        $fallback = [
            'receipt_no' => $isAcknowledgement ? '0007494' : '0046043',
            'payment_date' => date('M d, Y'),
            'account_no' => '',
            'registered_name' => request('customer', 'ALLEN SPECIALTY PRODUCTS COMPANY'),
            'tin' => '000-385-400-000',
            'business_address' => 'U-6A/K 20 LANNSBURGH PLC. # 170 TOMAS MORATO AVE. DISTRICT 4 QUEZON CITY',
            'description' => ($isAcknowledgement ? 'PAYMENT FOR REIMBURSABLE CHARGES' : 'PAYMENT FOR LOCAL CHARGES') . "\n" . ($isAcknowledgement ? 'SOA#5923' : 'BI# 0000007'),
            'currency' => request('currency', 'PHP'),
            'amount' => (float) str_replace(',', '', request('amount', $isAcknowledgement ? 63839.24 : 30680)),
            'payment_method' => request('payment_method', 'Check'),
            'reference_no' => request('reference_no', ''),
            'bank' => '',
            'printed_by' => auth()->user()->name ?? 'User',
        ];

        if (!Schema::hasTable('receipts')) {
            return $fallback;
        }

        $usesSqlite = DB::connection()->getDriverName() === 'sqlite';
        $invoiceNumbersSql = $usesSqlite
            ? 'GROUP_CONCAT(DISTINCT sales_documents.document_no) as invoice_numbers'
            : 'GROUP_CONCAT(DISTINCT sales_documents.document_no ORDER BY sales_documents.document_no SEPARATOR ", ") as invoice_numbers';
        $soaNumbersSql = $usesSqlite
            ? 'GROUP_CONCAT(DISTINCT NULLIF(sales_documents.soa_no, \'\')) as soa_numbers'
            : 'GROUP_CONCAT(DISTINCT NULLIF(sales_documents.soa_no, \'\') ORDER BY sales_documents.document_no SEPARATOR ", ") as soa_numbers';
        $invoiceRemarksSql = $usesSqlite
            ? 'GROUP_CONCAT(DISTINCT sales_documents.remarks) as invoice_remarks'
            : 'GROUP_CONCAT(DISTINCT sales_documents.remarks ORDER BY sales_documents.document_no SEPARATOR "\n") as invoice_remarks';

        $query = DB::table('receipts')
            ->leftJoin('customers', 'customers.id', '=', 'receipts.customer_id')
            ->leftJoin('receipt_applications', 'receipt_applications.receipt_id', '=', 'receipts.id')
            ->leftJoin('sales_documents', 'sales_documents.id', '=', 'receipt_applications.sales_document_id')
            ->select(
                'receipts.id',
                'receipts.receipt_no',
                'receipts.receipt_date',
                'receipts.payment_method',
                'receipts.reference_no',
                'receipts.amount',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'customers.tin as customer_tin',
                'customers.billing_address',
                DB::raw($invoiceNumbersSql),
                DB::raw($soaNumbersSql),
                DB::raw($invoiceRemarksSql),
                DB::raw('COUNT(receipt_applications.id) as application_count'),
                DB::raw('SUM(CASE
                    WHEN sales_documents.id IS NULL THEN 0
                    WHEN COALESCE(sales_documents.invoice_amount, 0) > 0
                        THEN CASE
                            WHEN sales_documents.invoice_amount > COALESCE(sales_documents.withholding_tax_amount, 0)
                                THEN sales_documents.invoice_amount - COALESCE(sales_documents.withholding_tax_amount, 0)
                            ELSE 0
                        END
                    WHEN COALESCE(sales_documents.freight_total, 0) > 0
                        THEN CASE
                            WHEN COALESCE(sales_documents.grand_total, 0) > COALESCE(sales_documents.freight_total, 0) + COALESCE(sales_documents.withholding_tax_amount, 0)
                                THEN sales_documents.grand_total - sales_documents.freight_total - COALESCE(sales_documents.withholding_tax_amount, 0)
                            ELSE 0
                        END
                    WHEN COALESCE(sales_documents.grand_total, 0) > COALESCE(sales_documents.withholding_tax_amount, 0)
                        THEN sales_documents.grand_total - COALESCE(sales_documents.withholding_tax_amount, 0)
                    ELSE 0
                END) as invoice_amount_total'),
                // SOA B is the reimbursement component stored in freight_total.
                // Do not infer it from grand_total because that can include unrelated adjustments.
                DB::raw('SUM(CASE
                    WHEN sales_documents.id IS NULL THEN 0
                    ELSE COALESCE(sales_documents.freight_total, 0)
                END) as soa_amount_total')
            );

        $receiptColumns = Schema::getColumnListing('receipts');

        if (in_array('currency', $receiptColumns, true)) {
            $query->addSelect('receipts.currency');
        }

        if (in_array('customer_id_text', $receiptColumns, true)) {
            $query->addSelect('receipts.customer_id_text');
        }

        if (in_array('job_shipment_id', $receiptColumns, true)) {
            $query->addSelect('receipts.job_shipment_id');
        }

        if ($receiptRef) {
            $query->where(function ($where) use ($receiptRef) {
                $where->where('receipts.receipt_no', $receiptRef);

                if (ctype_digit((string) $receiptRef)) {
                    $where->orWhere('receipts.id', (int) $receiptRef);
                }
            });
        }

        $dbReceipt = $query
            ->groupBy(
                'receipts.id',
                'receipts.receipt_no',
                'receipts.receipt_date',
                'receipts.payment_method',
                'receipts.reference_no',
                'receipts.amount',
                'customers.code',
                'customers.name',
                'customers.tin',
                'customers.billing_address'
            )
            ->when(in_array('currency', $receiptColumns, true), function ($grouped) {
                return $grouped->groupBy('receipts.currency');
            })
            ->when(in_array('customer_id_text', $receiptColumns, true), function ($grouped) {
                return $grouped->groupBy('receipts.customer_id_text');
            })
            ->when(in_array('job_shipment_id', $receiptColumns, true), function ($grouped) {
                return $grouped->groupBy('receipts.job_shipment_id');
            })
            ->orderBy('receipts.receipt_date', 'desc')
            ->orderBy('receipts.id', 'desc')
            ->first();

        if (!$dbReceipt) {
            return $fallback;
        }

        $numericReceiptNo = preg_replace('/\D/', '', $dbReceipt->receipt_no);
        $defaultDescription = $isAcknowledgement ? 'PAYMENT FOR REIMBURSABLE CHARGES' : 'PAYMENT FOR LOCAL CHARGES';
        $documentPrefix = $isAcknowledgement ? 'SOA#' : 'BI#';
        $documentNumbers = $isAcknowledgement && !empty($dbReceipt->soa_numbers) ? $dbReceipt->soa_numbers : $dbReceipt->invoice_numbers;
        $descriptionParts = array_filter([
            $dbReceipt->invoice_remarks ? trim($dbReceipt->invoice_remarks) : $defaultDescription,
            $documentNumbers ? $documentPrefix . ' ' . $documentNumbers : null,
            property_exists($dbReceipt, 'job_shipment_id') && $dbReceipt->job_shipment_id ? 'JOB ' . $dbReceipt->job_shipment_id : null,
        ]);
        $printAmount = (int) $dbReceipt->application_count > 0
            ? (float) ($isAcknowledgement ? $dbReceipt->soa_amount_total : $dbReceipt->invoice_amount_total)
            : (float) $dbReceipt->amount;

        return [
            'receipt_no' => $numericReceiptNo ? str_pad($numericReceiptNo, 7, '0', STR_PAD_LEFT) : $dbReceipt->receipt_no,
            'payment_date' => $dbReceipt->receipt_date ? date('M d, Y', strtotime($dbReceipt->receipt_date)) : date('M d, Y'),
            'account_no' => property_exists($dbReceipt, 'customer_id_text') && $dbReceipt->customer_id_text ? $dbReceipt->customer_id_text : $dbReceipt->customer_code,
            'registered_name' => $dbReceipt->customer_name ?: request('customer', $fallback['registered_name']),
            'tin' => $dbReceipt->customer_tin ?: '',
            'business_address' => $dbReceipt->billing_address ?: '',
            'description' => implode("\n", $descriptionParts),
            'currency' => property_exists($dbReceipt, 'currency') && $dbReceipt->currency ? $dbReceipt->currency : 'PHP',
            'amount' => $printAmount,
            'payment_method' => $dbReceipt->payment_method ?: 'Check',
            'reference_no' => $dbReceipt->reference_no ?: '',
            'bank' => '',
            'printed_by' => auth()->user()->name ?? 'User',
        ];
    }

    public function acknowledgementReceiptPreview()
    {
        return view('erp.print.acknowledgement-receipt', [
            'receipt' => $this->officialReceiptPrintData(request('receipt_no') ?: request('receipt'), 'ar'),
            'receiptLayout' => $this->receiptPrintLayout('ar'),
            'receiptFieldStyles' => $this->receiptPrintFieldStyles('ar'),
            'receiptFontDefaults' => $this->receiptPrintFontDefaults(),
            'receiptFontOptions' => $this->receiptPrintFontOptions(),
            'receiptFieldLabels' => $this->receiptPrintFieldLabels(),
            'receiptHiddenFields' => $this->receiptPrintHiddenFields('ar'),
            'adjustLayout' => request('adjust') === '1',
        ]);
    }

    public function saveAcknowledgementReceiptPrintLayout()
    {
        return $this->saveReceiptPrintLayout('ar');
    }

    private function receiptPrintFieldKeys()
    {
        return array_keys($this->receiptPrintFieldLabels());
    }

    private function receiptPrintFieldLabels()
    {
        return [
            'receipt_no' => 'Receipt number',
            'payment_date' => 'Payment date',
            'account_no' => 'Account number',
            'registered_name' => 'Registered name',
            'tin' => 'TIN',
            'business_address' => 'Business address',
            'description' => 'Description',
            'amount' => 'Transaction amount',
            'reference_no' => 'Reference number',
            'bank' => 'Bank',
            'total_amount' => 'Total paid amount',
            'printed_by' => 'Printed by',
        ];
    }

    private function receiptPrintLayoutStoragePath($documentKind)
    {
        return $documentKind === 'ar'
            ? 'erp/acknowledgement_receipt_print_layout.json'
            : 'erp/collection_receipt_print_layout.json';
    }

    private function receiptPrintDocumentType($documentKind)
    {
        return $documentKind === 'ar' ? 'acknowledgement_receipt' : 'collection_receipt';
    }

    private function savedReceiptPrintLayout($documentKind)
    {
        $storageLayout = [];
        $path = $this->receiptPrintLayoutStoragePath($documentKind);
        if (Storage::exists($path)) {
            $stored = json_decode(Storage::get($path), true);
            $storageLayout = is_array($stored) ? $stored : [];
        }

        try {
            if (Schema::hasTable('print_layouts')) {
                $record = DB::table('print_layouts')
                    ->whereNull('company_id')
                    ->where('document_type', $this->receiptPrintDocumentType($documentKind))
                    ->where('layout_name', 'Default')
                    ->first();

                if ($record) {
                    return [
                        'positions' => json_decode($record->positions ?: '[]', true) ?: [],
                        'field_styles' => json_decode($record->field_styles ?: '[]', true) ?: [],
                        'hidden_fields' => Schema::hasColumn('print_layouts', 'hidden_fields')
                            ? (json_decode($record->hidden_fields ?: '[]', true) ?: [])
                            : ($storageLayout['hidden_fields'] ?? []),
                    ];
                }
            }
        } catch (\Exception $exception) {
            // Fall back to local storage when the configured database is unavailable.
        }

        return $storageLayout;
    }

    private function receiptPrintLayout($documentKind)
    {
        $saved = $this->savedReceiptPrintLayout($documentKind);
        $savedPositions = $saved['positions'] ?? $saved;
        $layout = [];

        foreach ($this->receiptPrintFieldKeys() as $field) {
            $point = isset($savedPositions[$field]) && is_array($savedPositions[$field]) ? $savedPositions[$field] : [0, 0];
            $layout[$field] = [
                max(-100, min(100, round((float) ($point[0] ?? 0), 2))),
                max(-100, min(100, round((float) ($point[1] ?? 0), 2))),
            ];
        }

        return $layout;
    }

    private function receiptPrintFontDefaults()
    {
        return [
            'receipt_no' => 15,
            'payment_date' => 8.25,
            'account_no' => 8.25,
            'registered_name' => 8.25,
            'tin' => 8.25,
            'business_address' => 8.25,
            'description' => 9,
            'amount' => 9,
            'reference_no' => 7.5,
            'bank' => 7.5,
            'total_amount' => 9,
            'printed_by' => 7.5,
        ];
    }

    private function receiptPrintFontOptions()
    {
        return [
            'arial' => ['label' => 'Arial', 'stack' => 'Arial, Helvetica, sans-serif'],
            'calibri' => ['label' => 'Calibri', 'stack' => 'Calibri, Arial, sans-serif'],
            'times_new_roman' => ['label' => 'Times New Roman', 'stack' => '"Times New Roman", Times, serif'],
            'georgia' => ['label' => 'Georgia', 'stack' => 'Georgia, "Times New Roman", serif'],
            'courier_new' => ['label' => 'Courier New', 'stack' => '"Courier New", Courier, monospace'],
            'verdana' => ['label' => 'Verdana', 'stack' => 'Verdana, Geneva, sans-serif'],
            'tahoma' => ['label' => 'Tahoma', 'stack' => 'Tahoma, Arial, sans-serif'],
        ];
    }

    private function receiptPrintFieldStyles($documentKind)
    {
        $saved = $this->savedReceiptPrintLayout($documentKind);
        $savedFontSizes = $saved['field_styles']['font_sizes'] ?? [];
        $savedFontFamilies = $saved['field_styles']['font_families'] ?? [];
        $savedBoldFields = $saved['field_styles']['bold_fields'] ?? [];
        $fontOptions = $this->receiptPrintFontOptions();
        $styles = [];

        foreach ($this->receiptPrintFontDefaults() as $field => $defaultFontSize) {
            $fontFamily = (string) ($savedFontFamilies[$field] ?? 'arial');
            if (!isset($fontOptions[$fontFamily])) {
                $fontFamily = 'arial';
            }

            $styles[$field] = [
                'font_size' => max(6, min(36, round((float) ($savedFontSizes[$field] ?? $defaultFontSize), 2))),
                'font_family' => $fontFamily,
                'font_stack' => $fontOptions[$fontFamily]['stack'],
                'bold' => in_array($field, is_array($savedBoldFields) ? $savedBoldFields : [], true),
            ];
        }

        return $styles;
    }

    private function receiptPrintHiddenFields($documentKind)
    {
        $saved = $this->savedReceiptPrintLayout($documentKind);
        $knownFields = $this->receiptPrintFieldKeys();
        $hiddenFields = isset($saved['hidden_fields']) && is_array($saved['hidden_fields'])
            ? $saved['hidden_fields']
            : [];

        return array_values(array_intersect($knownFields, $hiddenFields));
    }

    private function saveReceiptPrintLayout($documentKind)
    {
        $submittedX = (array) request('layout_x', []);
        $submittedY = (array) request('layout_y', []);
        $submittedFontSizes = (array) request('font_size', []);
        $submittedFontFamilies = (array) request('font_family', []);
        $submittedBoldFields = (array) request('bold_field', []);
        $fontDefaults = $this->receiptPrintFontDefaults();
        $fontOptions = $this->receiptPrintFontOptions();
        $visibleFields = request()->has('visibility_present') ? (array) request('visible_field', []) : $this->receiptPrintFieldKeys();
        $visibleFields = array_intersect($this->receiptPrintFieldKeys(), $visibleFields);
        $hiddenFields = array_values(array_diff($this->receiptPrintFieldKeys(), $visibleFields));
        $positions = [];
        $fontSizes = [];
        $fontFamilies = [];
        $boldFields = [];

        foreach ($this->receiptPrintFieldKeys() as $field) {
            $positions[$field] = [
                max(-100, min(100, round((float) ($submittedX[$field] ?? 0), 2))),
                max(-100, min(100, round((float) ($submittedY[$field] ?? 0), 2))),
            ];
            $fontSizes[$field] = max(6, min(36, round((float) ($submittedFontSizes[$field] ?? $fontDefaults[$field]), 2)));
            $fontFamily = (string) ($submittedFontFamilies[$field] ?? 'arial');
            $fontFamilies[$field] = isset($fontOptions[$fontFamily]) ? $fontFamily : 'arial';
            if (in_array($field, $submittedBoldFields, true)) {
                $boldFields[] = $field;
            }
        }

        $fieldStyles = [
            'font_sizes' => $fontSizes,
            'font_families' => $fontFamilies,
            'bold_fields' => $boldFields,
        ];

        try {
            if (Schema::hasTable('print_layouts')) {
                $updates = [
                    'paper_size' => 'a5_landscape',
                    'paper_width' => 794,
                    'paper_height' => 561,
                    'template_url' => null,
                    'print_template' => false,
                    'positions' => json_encode($positions),
                    'field_styles' => json_encode($fieldStyles),
                    'custom_fields' => json_encode([]),
                    'is_default' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
                if (Schema::hasColumn('print_layouts', 'hidden_fields')) {
                    $updates['hidden_fields'] = json_encode($hiddenFields);
                }

                DB::table('print_layouts')->updateOrInsert(
                    [
                        'company_id' => null,
                        'document_type' => $this->receiptPrintDocumentType($documentKind),
                        'layout_name' => 'Default',
                    ],
                    $updates
                );
            }
        } catch (\Exception $exception) {
            // Keep layout editing usable even if the database is unavailable.
        }

        Storage::put($this->receiptPrintLayoutStoragePath($documentKind), json_encode([
            'positions' => $positions,
            'field_styles' => $fieldStyles,
            'hidden_fields' => $hiddenFields,
        ], JSON_PRETTY_PRINT));

        $route = $documentKind === 'ar'
            ? 'erp.acknowledgement-receipt.preview'
            : 'erp.official-receipt.preview';
        $query = ['adjust' => 1];
        if (request('receipt_no')) {
            $query['receipt_no'] = request('receipt_no');
        }

        return redirect()->route($route, $query)->with('status', 'Receipt print positions saved.');
    }

    public function publicInvoice($token)
    {
        $invoice = $this->portalInvoiceData($token);

        return view('erp.client.public-invoice', compact('invoice'));
    }

    public function publicCheckout($token)
    {
        $invoice = $this->portalInvoiceData($token);
        $checkout = [
            'token' => $token,
            'invoice_no' => $invoice['invoice_no'],
            'customer' => $invoice['customer'],
            'amount_due' => $invoice['amount_due'],
            'currency' => $invoice['currency'],
            'gateway' => config('services.stripe.key') ? 'Stripe ready' : 'Stripe test keys not configured',
        ];

        return view('erp.client.checkout', compact('checkout'));
    }

    public function stripeWebhook()
    {
        $secret = (string) config('services.stripe.webhook.secret');
        if ($secret === '') {
            return response()->json(['error' => 'Stripe webhook is not configured.'], 503);
        }

        $payload = request()->getContent();
        $signatureHeader = (string) request()->header('Stripe-Signature');
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]][] = $pair[1];
            }
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $tolerance = (int) config('services.stripe.webhook.tolerance', 300);
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        $validSignature = $timestamp > 0
            && abs(time() - $timestamp) <= $tolerance
            && !empty($parts['v1'])
            && collect($parts['v1'])->contains(function ($signature) use ($expected) {
                return hash_equals($expected, $signature);
            });

        if (!$validSignature) {
            return response()->json(['error' => 'Invalid Stripe signature.'], 400);
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
            return response()->json(['error' => 'Invalid Stripe event payload.'], 400);
        }

        if (DB::table('online_payment_events')->where('event_id', $event['id'])->exists()) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        $object = $event['data']['object'] ?? [];
        $reference = $object['metadata']['invoice_no'] ?? ($object['payment_intent'] ?? null);
        $document = $reference
            ? DB::table('sales_documents')->where('document_no', $reference)->first()
            : null;

        $paymentEventId = DB::table('online_payment_events')->insertGetId([
            'company_id' => $document->company_id ?? null,
            'gateway' => 'stripe',
            'event_id' => $event['id'],
            'event_type' => $event['type'],
            'reference_no' => $reference,
            'amount' => isset($object['amount_received']) ? ((float) $object['amount_received'] / 100) : 0,
            'currency' => isset($object['currency']) ? strtoupper($object['currency']) : null,
            'status' => 'verified',
            'payload' => json_encode($event),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ActivityLogger::class)->log('webhook_verified', 'online_payment_event', $paymentEventId, [], [
            'event_id' => $event['id'],
            'event_type' => $event['type'],
            'reference_no' => $reference,
        ], $document->company_id ?? null);

        return response()->json(['received' => true]);
    }

    public function createInvoicePortalLink($invoice)
    {
        $document = DB::table('sales_documents')
            ->where('company_id', $this->defaultCompanyId())
            ->where(function ($query) use ($invoice) {
                $query->where('id', $invoice)->orWhere('document_no', $invoice);
            })
            ->first();
        abort_unless($document, 404);
        abort_if(in_array(strtolower((string) $document->status), ['cancelled', 'voided'], true), 403, 'A cancelled or voided invoice cannot be shared.');

        $token = bin2hex(random_bytes(32));
        DB::table('client_portal_tokens')->where('sales_document_id', $document->id)->delete();
        DB::table('client_portal_tokens')->insert([
            'company_id' => $document->company_id,
            'customer_id' => $document->customer_id,
            'sales_document_id' => $document->id,
            'token' => $token,
            'purpose' => 'invoice_view',
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ActivityLogger::class)->log('portal_link_created', 'sales_document', $document->id, [], [
            'expires_at' => now()->addDays(30)->toDateTimeString(),
        ], $document->company_id);

        return redirect()->route('erp.invoices.show', $document->id)
            ->with('portal_url', route('erp.public.invoice', $token));
    }

    private function portalInvoiceData($token)
    {
        $portalToken = DB::table('client_portal_tokens')
            ->where('token', (string) $token)
            ->where('purpose', 'invoice_view')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
        abort_unless($portalToken && $portalToken->sales_document_id, 404);

        $document = DB::table('sales_documents')
            ->join('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->where('sales_documents.id', $portalToken->sales_document_id)
            ->where('sales_documents.customer_id', $portalToken->customer_id)
            ->select('sales_documents.*', 'customers.name as customer_name')
            ->first();
        abort_unless($document, 404);
        abort_if(in_array(strtolower((string) $document->status), ['cancelled', 'voided'], true), 404);

        DB::table('client_portal_tokens')->where('id', $portalToken->id)->update([
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);

        $items = DB::table('sales_document_lines')
            ->where('sales_document_id', $document->id)
            ->orderBy('id')
            ->get()
            ->map(function ($line) {
                return [
                    $line->description,
                    number_format((float) $line->quantity, 2),
                    number_format((float) $line->unit_price, 2),
                    number_format((float) $line->line_total, 2),
                ];
            })
            ->all();

        return [
            'token' => $token,
            'invoice_no' => $document->document_no,
            'customer' => $document->customer_name,
            'status' => ucwords(str_replace('_', ' ', $document->status)),
            'currency' => $document->currency,
            'amount_due' => number_format(max(0, (float) $document->grand_total - (float) $document->paid_total), 2),
            'due_date' => $document->due_date ?: '-',
            'items' => $items,
        ];
    }

    public function customerBillingTemplates()
    {
        $templates = [
            ['Billing Invoice', 'BI-PREPRINT', 'A4 / Legal', 'Yes', 'A/Trade-Billing Invoice', 'PHP / USD'],
            ['Statement of Account', 'SOA-PREPRINT', 'A4', 'Yes', 'A/R Trade-SOA', 'PHP / USD'],
            ['Statement of Account A', 'SOAA-PLAIN', 'A4', 'No', 'A/R Trade-SOA A', 'PHP / USD'],
            ['Debit Note', 'DN-PREPRINT', 'A4', 'Yes', 'A/R Trade-Debit Note', 'PHP / USD'],
            ['Collection Receipt (Supplementary)', 'CR-PREPRINT', 'A4 / Approved Form', 'Yes', 'A/Trade-Billing Invoice', 'PHP / USD'],
            ['Acknowledgement Receipt', 'AR-PREPRINT', 'A4 / Official Form', 'Yes', 'A/R Reimbursable Charges', 'PHP / USD'],
        ];

        return view('erp.reports.customer-billing-templates', compact('templates'));
    }

    public function reportsIndex()
    {
        $companyId = $this->defaultCompanyId();
        $openAr = (float) DB::table('sales_documents')->where('company_id', $companyId)
            ->whereNotIn('status', ['cancelled', 'voided', 'paid'])
            ->selectRaw('COALESCE(SUM(grand_total - paid_total), 0) as balance')->value('balance');
        $openAp = (float) DB::table('purchase_documents')->where('company_id', $companyId)
            ->whereNotIn('status', ['cancelled', 'voided', 'paid'])
            ->selectRaw('COALESCE(SUM(grand_total - paid_total), 0) as balance')->value('balance');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $monthBalances = $this->glAccountBalances($monthStart, $monthEnd);
        $revenueMtd = array_sum(array_column($this->glStatementRows($monthBalances, 'Revenue', 'period'), 'amount'));
        $endingBalances = $this->glAccountBalances(null, date('Y-m-d'));
        $outputVat = 0.0;
        $inputVat = 0.0;
        foreach ($endingBalances as $balance) {
            if ($balance['account']->code === '2200') {
                $outputVat += $balance['ending'];
            } elseif ($balance['account']->code === '1250') {
                $inputVat += $balance['ending'];
            }
        }
        $reportStats = [
            ['label' => 'Open AR', 'value' => 'PHP '.number_format($openAr, 2), 'icon' => 'bi-hourglass-split', 'tone' => 'primary'],
            ['label' => 'Revenue MTD', 'value' => 'PHP '.number_format($revenueMtd, 2), 'icon' => 'bi-graph-up-arrow', 'tone' => 'success'],
            ['label' => 'VAT Payable', 'value' => 'PHP '.number_format($outputVat - $inputVat, 2), 'icon' => 'bi-percent', 'tone' => 'warning'],
            ['label' => 'AP Exposure', 'value' => 'PHP '.number_format($openAp, 2), 'icon' => 'bi-credit-card-2-front', 'tone' => 'danger'],
        ];

        $reportGroups = [
            'Accounts Receivable' => [
                ['name' => 'AR Aging', 'description' => 'Current, 30, 60, and 90+ balances by client, job, currency, VAT, and branch class.', 'route' => route('erp.customer-billing.aging'), 'icon' => 'bi-bar-chart-steps', 'frequency' => 'Daily', 'owner' => 'Accounting'],
                ['name' => 'Statement of Account per Client', 'description' => 'Printable SOA of active invoices with unpaid balances per customer.', 'route' => route('erp.reports.client-soa'), 'icon' => 'bi-file-earmark-text', 'frequency' => 'Daily', 'owner' => 'Collections'],
                ['name' => 'Customer Statement', 'description' => 'Client billing summary with open invoices, payments, credits, and SOA-ready totals.', 'route' => route('erp.customer-billing.templates'), 'icon' => 'bi-file-earmark-text', 'frequency' => 'Weekly', 'owner' => 'Collections'],
                ['name' => 'Collection Forecast', 'description' => 'Expected receipts by due date, client terms, and payment status.', 'route' => route('erp.export', 'collection-forecast'), 'icon' => 'bi-calendar2-check', 'frequency' => 'Weekly', 'owner' => 'Finance'],
            ],
            'Financial Statements' => [
                ['name' => 'Trial Balance', 'description' => 'REPORTS.xlsx-style trial balance using account titles from Chart of Accounts.', 'route' => route('erp.reports.trial-balance'), 'icon' => 'bi-columns-gap', 'frequency' => 'Monthly', 'owner' => 'Accounting'],
                ['name' => 'Workbook Report Pack', 'description' => 'Trial balance, ledger, bank recon, disbursement, journal, cash receipt books, and sales journals from the REPORTS.xlsx layout.', 'route' => route('erp.reports.workbook'), 'icon' => 'bi-folder-check', 'frequency' => 'Monthly', 'owner' => 'Finance'],
                ['name' => 'Financial Statement Pack', 'description' => 'Complete finance report center for statements, ledgers, AR, AP, cash, tax, and management reports.', 'route' => route('erp.reports.financial-statements'), 'icon' => 'bi-file-earmark-spreadsheet', 'frequency' => 'Monthly', 'owner' => 'Finance'],
                ['name' => 'Profit & Loss', 'description' => 'Revenue, cost of services, operating expenses, and net income by period.', 'route' => route('erp.statements.profit-loss'), 'icon' => 'bi-graph-up', 'frequency' => 'Monthly', 'owner' => 'Finance'],
                ['name' => 'Balance Sheet', 'description' => 'Assets, liabilities, equity, cash, AR, AP, and VAT balances.', 'route' => route('erp.statements.balance-sheet'), 'icon' => 'bi-layout-text-window-reverse', 'frequency' => 'Monthly', 'owner' => 'Accounting'],
                ['name' => 'Cash Flow', 'description' => 'Operating collections, supplier payments, financing movement, and ending cash.', 'route' => route('erp.statements.cash-flow'), 'icon' => 'bi-cash-stack', 'frequency' => 'Monthly', 'owner' => 'Finance'],
                ['name' => 'Income Statement', 'description' => 'Income presentation for service revenue, direct costs, and operating expenses.', 'route' => route('erp.statements.income-statement'), 'icon' => 'bi-clipboard-data', 'frequency' => 'Monthly', 'owner' => 'Management'],
            ],
            'Tax & Compliance' => [
                ['name' => 'VAT Summary', 'description' => 'Output VAT, input VAT, payable amount, and supporting sales/purchases.', 'route' => route('erp.tax.vat-summary'), 'icon' => 'bi-receipt-cutoff', 'frequency' => 'Monthly', 'owner' => 'Tax'],
                ['name' => 'Withholding Tax', 'description' => 'Expanded and creditable withholding tax by document and supplier/client.', 'route' => route('erp.tax.withholding'), 'icon' => 'bi-percent', 'frequency' => 'Monthly', 'owner' => 'Tax'],
                ['name' => 'Sales & Purchases', 'description' => 'Sales and purchase tax list for VAT filing and audit support.', 'route' => route('erp.tax.sales-purchases'), 'icon' => 'bi-table', 'frequency' => 'Monthly', 'owner' => 'Accounting'],
                ['name' => 'Tax Audit Trail', 'description' => 'Traceable tax entries, adjustments, reviews, and filing controls.', 'route' => route('erp.tax.audit-trail'), 'icon' => 'bi-clock-history', 'frequency' => 'As needed', 'owner' => 'Admin'],
            ],
            'Operations' => [
                ['name' => 'AP Aging', 'description' => 'Supplier payables by aging bucket, due date, currency, and payment status.', 'route' => route('erp.payables.aging'), 'icon' => 'bi-building-exclamation', 'frequency' => 'Weekly', 'owner' => 'Payables'],
                ['name' => 'Cashbook', 'description' => 'Cash and bank movement with deposits, transfers, checks, and reconciliation state.', 'route' => route('erp.banking.cashbook'), 'icon' => 'bi-journal-text', 'frequency' => 'Daily', 'owner' => 'Finance'],
                ['name' => 'Journal Entries', 'description' => 'Posted and draft journal entries linked to shipment jobs and documents.', 'route' => route('erp.accounting.journals'), 'icon' => 'bi-journal-check', 'frequency' => 'Daily', 'owner' => 'Accounting'],
            ],
        ];

        return view('erp.reports.index', compact('reportStats', 'reportGroups'));
    }

    public function workbookReport($report = 'trial-balance')
    {
        $reports = $this->workbookReportDefinitions();
        $report = $report ?: 'trial-balance';
        if (!isset($reports[$report])) {
            $report = 'trial-balance';
        }

        $activeReport = $reports[$report];
        $company = Schema::hasTable('companies') ? DB::table('companies')->where('id', $this->defaultCompanyId())->first() : null;
        $period = request('period', date('Y-m'));
        $rows = $this->workbookReportRows($report, $period);
        $summaryCards = $this->workbookReportSummaryCards();

        return view('erp.reports.workbook', compact('reports', 'report', 'activeReport', 'company', 'period', 'rows', 'summaryCards'));
    }

    public function trialBalanceReport()
    {
        $reports = $this->workbookReportDefinitions();
        $activeReport = $reports['trial-balance'];
        $company = Schema::hasTable('companies') ? DB::table('companies')->where('id', $this->defaultCompanyId())->first() : null;
        $period = request('period', date('Y-m'));
        $rows = $this->trialBalanceWorkbookRows($period);
        $totals = $this->trialBalanceTotals($rows, $activeReport['columns']);

        return view('erp.reports.trial-balance', compact('activeReport', 'company', 'period', 'rows', 'totals'));
    }

    public function clientStatementOfAccount()
    {
        $company = Schema::hasTable('companies') ? DB::table('companies')->where('id', $this->defaultCompanyId())->first() : null;
        $customers = $this->clientSoaCustomerOptions();
        $customerId = request('customer_id');

        if (!$customerId && count($customers) > 0) {
            $customerId = $customers[0]->id;
        }

        $customer = $customerId && Schema::hasTable('customers')
            ? DB::table('customers')->where('id', $customerId)->first()
            : null;

        $invoices = $customer ? $this->clientSoaOpenInvoices($customer->id) : collect();
        $currencyTotals = $this->clientSoaCurrencyTotals($invoices);
        $asOfDate = request('as_of', date('Y-m-d'));
        $soaNo = 'SOA-'.date('Ymd').'-'.str_pad((string) ($customer->id ?? 0), 4, '0', STR_PAD_LEFT);
        $bankDetails = [
            'ACCOUNT NAME: SEA-JET INTERNATIONAL FORWARDERS, INC.',
            'BANK NAME: BDO',
            'ACCOUNT NUMBER: 00 489 000 1371',
            'BRANCH: STO. NINO',
        ];

        return view('erp.reports.client-soa', compact('company', 'customers', 'customerId', 'customer', 'invoices', 'currencyTotals', 'asOfDate', 'soaNo', 'bankDetails'));
    }

    private function clientSoaCustomerOptions()
    {
        if (!Schema::hasTable('sales_documents') || !Schema::hasTable('customers')) {
            return collect();
        }

        $balanceExpression = $this->salesDocumentBalanceExpression();

        return DB::table('customers')
            ->join('sales_documents', 'sales_documents.customer_id', '=', 'customers.id')
            ->select('customers.id', 'customers.name', DB::raw('count(sales_documents.id) as open_count'), DB::raw('sum('.$balanceExpression.') as open_balance'))
            ->whereNotIn('sales_documents.status', ['cancelled', 'voided', 'paid'])
            ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->whereRaw($balanceExpression.' > 0')
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('customers.name')
            ->get();
    }

    private function clientSoaOpenInvoices($customerId)
    {
        if (!Schema::hasTable('sales_documents')) {
            return collect();
        }

        $balanceExpression = $this->salesDocumentBalanceExpression();

        return DB::table('sales_documents')
            ->where('customer_id', $customerId)
            ->whereNotIn('status', ['cancelled', 'voided', 'paid'])
            ->whereIn('document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->whereRaw($balanceExpression.' > 0')
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(function ($invoice) use ($balanceExpression) {
                $balance = $this->salesDocumentBalance($invoice);
                $invoiceAmount = (float) ($invoice->invoice_amount ?: 0);
                $soaAmount = max(0, (float) $invoice->grand_total - $invoiceAmount);
                if ($invoiceAmount <= 0 && $soaAmount <= 0) {
                    $invoiceAmount = (float) $invoice->grand_total;
                }

                return (object) [
                    'document_date' => $invoice->document_date,
                    'document_no' => $invoice->document_no,
                    'soa_no' => $invoice->soa_no ?: ($invoice->template_code && stripos($invoice->template_code, 'SOA') !== false ? $invoice->document_no : ''),
                    'currency' => $invoice->currency ?: 'PHP',
                    'invoice_amount' => $invoiceAmount,
                    'soa_amount' => $soaAmount,
                    'total' => $balance,
                    'aging' => $this->clientSoaAgingLabel($invoice->due_date ?: $invoice->document_date),
                    'due_date' => $invoice->due_date,
                ];
            });
    }

    private function salesDocumentBalanceExpression()
    {
        if (Schema::hasColumn('sales_documents', 'balance_due')) {
            return "case when lower(sales_documents.status) in ('cancelled', 'voided') then 0 when sales_documents.balance_due > 0 then sales_documents.balance_due else (sales_documents.grand_total - sales_documents.paid_total) end";
        }

        return "case when lower(sales_documents.status) in ('cancelled', 'voided') then 0 else (sales_documents.grand_total - sales_documents.paid_total) end";
    }

    private function salesDocumentBalance($invoice)
    {
        $status = strtolower((string) ($invoice->status ?? ''));
        if (in_array($status, ['cancelled', 'voided'], true)) {
            return 0.0;
        }

        $balanceDue = property_exists($invoice, 'balance_due') ? (float) $invoice->balance_due : 0;
        if ($balanceDue > 0) {
            return $balanceDue;
        }

        return max(0, (float) $invoice->grand_total - (float) $invoice->paid_total);
    }

    private function clientSoaAgingLabel($date)
    {
        if (!$date) {
            return 'Current';
        }

        $days = max(0, now()->diffInDays(\Carbon\Carbon::parse($date), false) * -1);
        if ($days <= 0) {
            return 'Current';
        }
        if ($days <= 30) {
            return $days.' days';
        }
        if ($days <= 60) {
            return '31-60 days';
        }
        if ($days <= 90) {
            return '61-90 days';
        }

        return 'Over 90 days';
    }

    private function clientSoaCurrencyTotals($invoices)
    {
        $totals = [];
        foreach ($invoices as $invoice) {
            $currency = $invoice->currency ?: 'PHP';
            if (!isset($totals[$currency])) {
                $totals[$currency] = 0;
            }
            $totals[$currency] += (float) $invoice->total;
        }

        return $totals;
    }

    private function workbookReportDefinitions()
    {
        return [
            'trial-balance' => [
                'label' => 'TRIAL BALANCE',
                'title' => 'TRIAL BALANCE',
                'subtitle' => 'Workbook layout from REPORTS.xlsx',
                'icon' => 'bi-columns-gap',
                'columns' => ['ACCOUNT TITLES', 'BEGINNING BALANCE', 'SALES BOOK', 'SOA', 'CASH RECEIPTS BOOK', 'CASH DISBURSEMENT BOOK', 'JOURNAL BOOK', 'ENDING TRIAL BALANCE'],
            ],
            'general-ledger' => [
                'label' => 'GENERAL LEDGER',
                'title' => 'GENERAL LEDGER',
                'subtitle' => 'For the selected period',
                'icon' => 'bi-journal-text',
                'columns' => ['MONTH', 'SOURCE', 'DEBIT', 'CREDIT'],
            ],
            'bank-recon' => [
                'label' => 'BANK RECON',
                'title' => 'BANK RECON',
                'subtitle' => 'Any format',
                'icon' => 'bi-bank',
                'columns' => ['BANK ACCOUNT', 'ACCOUNT NO.', 'CURRENCY', 'BOOK BALANCE', 'BANK BALANCE', 'OUTSTANDING CHECKS', 'DEPOSITS IN TRANSIT', 'RECONCILED BALANCE'],
            ],
            'disbursement' => [
                'label' => 'DISBURSEMENT',
                'title' => 'CASH DISBURSEMENT BOOK',
                'subtitle' => 'For the selected period',
                'icon' => 'bi-credit-card-2-front',
                'columns' => ['DATE', 'CV#', 'CHECK#', 'PAYEE', 'PARTICULARS', 'CASH IN BANK- PESO', 'CASH IN BANK-USD', 'ACCOUNTS PAYABLE- TRADE', 'INPUT VAT', 'WITHHOLDING TAX PAYABLE', 'BANK CHARGES', 'OTHER EXPENSE'],
            ],
            'general-journal' => [
                'label' => 'GENERAL JOURNAL',
                'title' => 'GENERAL JOURNAL BOOK',
                'subtitle' => 'For the selected period',
                'icon' => 'bi-journal-check',
                'columns' => ['DATE', 'JV NO', 'PAYEE', 'ACCOUNT TITLE/PARTICULARS', 'CASH IN BANK-PHP', 'A/P TRADE - PHP', 'REIMBURSABLE CHARGES', 'VAT INPUT', 'EXPANDED WITHHOLDING TAX PAYABLE', 'BANK CHARGES', 'DEBIT', 'CREDIT'],
            ],
            'crb-manila' => [
                'label' => 'CRB-MANILA',
                'title' => 'CASH RECEIPTS JOURNAL',
                'subtitle' => 'MANILA',
                'icon' => 'bi-receipt',
                'branch' => 'MANILA',
                'columns' => ['DATE', 'COLLECTION RECEIPT NUMBER', 'ACKNOWLEDGEMENT RECEIPT NUMBER', 'CUSTOMER NAME', 'A/R TRADE', 'A/R REIMBURSABLES CHARGES', 'WITHHOLDING-CWT (2307)', 'CASH (PHP)', 'CASH (USD)', 'BILLING INVOICE NUMBER', 'STATEMENT OF ACCOUNT NUMBER'],
            ],
            'crb-pampanga' => [
                'label' => 'CRB-PAMPANGA',
                'title' => 'CASH RECEIPTS JOURNAL',
                'subtitle' => 'PAMPANGA',
                'icon' => 'bi-receipt',
                'branch' => 'PAMPANGA',
                'columns' => ['DATE', 'COLLECTION RECEIPT NUMBER', 'ACKNOWLEDGEMENT RECEIPT NUMBER', 'CUSTOMER NAME', 'A/R TRADE', 'A/R REIMBURSABLES CHARGES', 'WITHHOLDING-CWT (2307)', 'CASH (PHP)', 'CASH (USD)', 'BILLING INVOICE NUMBER', 'STATEMENT OF ACCOUNT NUMBER'],
            ],
            'crb-cebu' => [
                'label' => 'CRB-CEBU',
                'title' => 'CASH RECEIPTS JOURNAL',
                'subtitle' => 'CEBU',
                'icon' => 'bi-receipt',
                'branch' => 'CEBU',
                'columns' => ['DATE', 'COLLECTION RECEIPT NUMBER', 'ACKNOWLEDGEMENT RECEIPT NUMBER', 'CUSTOMER NAME', 'A/R TRADE', 'A/R REIMBURSABLES CHARGES', 'WITHHOLDING-CWT (2307)', 'CASH (PHP)', 'CASH (USD)', 'BILLING INVOICE NUMBER', 'STATEMENT OF ACCOUNT NUMBER'],
            ],
            'sales-manila' => [
                'label' => 'SALES-MANILA',
                'title' => 'SALES JOURNAL',
                'subtitle' => 'MANILA',
                'icon' => 'bi-graph-up-arrow',
                'branch' => 'MANILA',
                'columns' => ['DATE', 'BILLING INVOICE', 'STATEMENT OF ACCOUNT NUMBER', 'NAME OF COMPANY', 'A/R TRADE', 'A/R REIMBURSABLE CHARGES', 'SERVICE INCOME ZERO RATED', 'SERVICE INCOME VATABLE', 'OUTPUT VAT', 'CREDITABLE WITHHOLDING TAX', 'REIMBURSABLE CHARGES (CR)'],
            ],
            'sales-pampanga' => [
                'label' => 'SALES-PAMPANGA',
                'title' => 'SALES JOURNAL',
                'subtitle' => 'PAMPANGA',
                'icon' => 'bi-graph-up-arrow',
                'branch' => 'PAMPANGA',
                'columns' => ['DATE', 'BILLING INVOICE', 'STATEMENT OF ACCOUNT NUMBER', 'NAME OF COMPANY', 'A/R TRADE', 'A/R REIMBURSABLE CHARGES', 'SERVICE INCOME ZERO RATED', 'SERVICE INCOME VATABLE', 'OUTPUT VAT', 'CREDITABLE WITHHOLDING TAX', 'REIMBURSABLE CHARGES (CR)'],
            ],
            'sales-cebu' => [
                'label' => 'SALES CEBU',
                'title' => 'SALES JOURNAL',
                'subtitle' => 'CEBU',
                'icon' => 'bi-graph-up-arrow',
                'branch' => 'CEBU',
                'columns' => ['DATE', 'BILLING INVOICE', 'STATEMENT OF ACCOUNT NUMBER', 'NAME OF COMPANY', 'A/R TRADE', 'A/R REIMBURSABLE CHARGES', 'SERVICE INCOME ZERO RATED', 'SERVICE INCOME VATABLE', 'OUTPUT VAT', 'CREDITABLE WITHHOLDING TAX', 'REIMBURSABLE CHARGES (CR)'],
            ],
        ];
    }

    private function workbookReportRows($report, $period)
    {
        $definitions = $this->workbookReportDefinitions();
        $definition = $definitions[$report] ?? $definitions['trial-balance'];

        if ($report === 'trial-balance') {
            return $this->trialBalanceWorkbookRows($period);
        }
        if ($report === 'general-ledger') {
            return $this->generalLedgerWorkbookRows($period);
        }
        if ($report === 'bank-recon') {
            return $this->bankReconWorkbookRows();
        }
        if ($report === 'disbursement') {
            return $this->disbursementWorkbookRows($period);
        }
        if ($report === 'general-journal') {
            return $this->generalJournalWorkbookRows($period);
        }
        if (strpos($report, 'crb-') === 0) {
            return $this->cashReceiptsWorkbookRows($definition['branch'] ?? 'MANILA', $period);
        }
        if (strpos($report, 'sales-') === 0) {
            return $this->salesJournalWorkbookRows($definition['branch'] ?? 'MANILA', $period);
        }

        return [];
    }

    private function workbookReportSummaryCards()
    {
        $sales = Schema::hasTable('sales_documents')
            ? (float) DB::table('sales_documents')->whereNotIn('status', ['cancelled', 'voided'])->sum('grand_total')
            : 0;
        $receipts = Schema::hasTable('receipts') ? (float) DB::table('receipts')->sum('amount') : 0;
        $payments = Schema::hasTable('payments') ? (float) DB::table('payments')->sum('amount') : 0;
        $journals = Schema::hasTable('journal_entries') ? DB::table('journal_entries')->count() : 0;

        return [
            ['label' => 'Sales Book', 'value' => 'PHP '.number_format($sales, 2), 'icon' => 'bi-graph-up-arrow', 'tone' => 'success'],
            ['label' => 'Collection Receipts', 'value' => 'PHP '.number_format($receipts, 2), 'icon' => 'bi-receipt', 'tone' => 'primary'],
            ['label' => 'Disbursements', 'value' => 'PHP '.number_format($payments, 2), 'icon' => 'bi-credit-card', 'tone' => 'danger'],
            ['label' => 'Journal Entries', 'value' => number_format($journals), 'icon' => 'bi-journal-check', 'tone' => 'warning'],
        ];
    }

    private function trialBalanceWorkbookRows($period = null)
    {
        if (!Schema::hasTable('accounts')) {
            return [];
        }

        list($startDate, $endDate) = $this->trialBalanceDateRange($period);
        $accounts = $this->trialBalanceAccounts();
        $normals = [];

        foreach ($accounts as $account) {
            $normals[$account->id] = $this->trialBalanceNormalBalance($account);
        }

        $beginningJournal = $startDate
            ? $this->trialBalanceJournalMovements(null, date('Y-m-d', strtotime($startDate.' -1 day')), $normals, true)
            : [];
        $beginningDocuments = $startDate
            ? $this->trialBalanceDocumentMovements(null, date('Y-m-d', strtotime($startDate.' -1 day')), $accounts, $normals)
            : [];
        $beginningMovements = $this->mergeMovementMaps($beginningJournal, $this->flattenMovementsToTotal($beginningDocuments));

        $periodJournal = $this->trialBalanceJournalMovements($startDate, $endDate, $normals, false);
        $periodDocuments = $this->trialBalanceDocumentMovements($startDate, $endDate, $accounts, $normals);
        $periodMovements = $this->mergeMovementMaps($periodJournal, $periodDocuments);

        return $accounts->map(function ($account) use ($beginningMovements, $periodMovements) {
            $opening = (float) ($account->opening_balance ?? 0) + (float) ($beginningMovements[$account->id]['TOTAL'] ?? 0);
            $sales = (float) ($periodMovements[$account->id]['SALES BOOK'] ?? 0);
            $soa = (float) ($periodMovements[$account->id]['SOA'] ?? 0);
            $receipts = (float) ($periodMovements[$account->id]['CASH RECEIPTS BOOK'] ?? 0);
            $disbursements = (float) ($periodMovements[$account->id]['CASH DISBURSEMENT BOOK'] ?? 0);
            $journal = (float) ($periodMovements[$account->id]['JOURNAL BOOK'] ?? 0);
            $ending = $opening + $sales + $soa + $receipts + $disbursements + $journal;
            $title = trim(($account->code ? $account->code.' - ' : '').$account->name);

            return [
                'ACCOUNT TITLES' => $title,
                'BEGINNING BALANCE' => $opening,
                'SALES BOOK' => $sales,
                'SOA' => $soa,
                'CASH RECEIPTS BOOK' => $receipts,
                'CASH DISBURSEMENT BOOK' => $disbursements,
                'JOURNAL BOOK' => $journal,
                'ENDING TRIAL BALANCE' => $ending,
            ];
        })->all();
    }

    private function trialBalanceTotals(array $rows, array $columns)
    {
        $totals = [];

        foreach ($columns as $column) {
            $totals[$column] = $column === 'ACCOUNT TITLES' ? '' : 0.0;
            foreach ($rows as $row) {
                if (isset($row[$column]) && is_numeric($row[$column])) {
                    $totals[$column] += (float) $row[$column];
                }
            }
        }

        $totals['ACCOUNT TITLES'] = 'TOTAL';

        return $totals;
    }

    private function trialBalanceDateRange($period)
    {
        if ($period && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $start = $period.'-01';
            return [$start, date('Y-m-t', strtotime($start))];
        }

        return [null, null];
    }

    private function trialBalanceAccounts()
    {
        $query = DB::table('accounts')
            ->leftJoin('account_categories', 'account_categories.id', '=', 'accounts.account_category_id')
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                'accounts.opening_balance',
                'account_categories.normal_balance',
                'account_categories.name as category_name'
            )
            ->where('accounts.company_id', $this->defaultCompanyId())
            ->orderBy('accounts.type')
            ->orderBy('accounts.code');

        return $query->get();
    }

    private function trialBalanceNormalBalance($account)
    {
        $normal = strtolower((string) ($account->normal_balance ?? ''));
        if (in_array($normal, ['debit', 'credit'], true)) {
            return $normal;
        }

        $type = strtolower((string) ($account->type ?? $account->category_name ?? ''));
        if (strpos($type, 'liabil') !== false || strpos($type, 'equity') !== false || strpos($type, 'revenue') !== false || strpos($type, 'income') !== false) {
            return 'credit';
        }

        return 'debit';
    }

    private function trialBalanceSignedAmount($debit, $credit, $normal)
    {
        $debit = (float) $debit;
        $credit = (float) $credit;

        return $normal === 'credit' ? $credit - $debit : $debit - $credit;
    }

    private function trialBalanceJournalMovements($startDate, $endDate, array $normals, $singleTotalColumn = false)
    {
        if (!Schema::hasTable('journal_entry_lines') || !Schema::hasTable('journal_entries')) {
            return [];
        }

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->select(
                'journal_entry_lines.account_id',
                'journal_entries.source_module',
                DB::raw('SUM(journal_entry_lines.debit) as debit_total'),
                DB::raw('SUM(journal_entry_lines.credit) as credit_total')
            )
            ->where('journal_entries.company_id', $this->defaultCompanyId())
            ->groupBy('journal_entry_lines.account_id', 'journal_entries.source_module');

        $this->excludeVoidedSalesDocumentJournals($query);

        if ($startDate) {
            $query->where('journal_entries.journal_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('journal_entries.journal_date', '<=', $endDate);
        }

        $movements = [];
        foreach ($query->get() as $line) {
            $accountId = (int) $line->account_id;
            $column = $singleTotalColumn ? 'TOTAL' : $this->trialBalanceColumnForSource($line->source_module);
            $amount = $this->trialBalanceSignedAmount($line->debit_total, $line->credit_total, $normals[$accountId] ?? 'debit');
            $movements[$accountId][$column] = ($movements[$accountId][$column] ?? 0) + $amount;
        }

        return $movements;
    }

    private function trialBalanceColumnForSource($source)
    {
        $source = strtolower((string) $source);

        if (strpos($source, 'soa') !== false || strpos($source, 'statement') !== false) {
            return 'SOA';
        }
        if (strpos($source, 'sale') !== false || strpos($source, 'invoice') !== false || strpos($source, 'billing') !== false) {
            return 'SALES BOOK';
        }
        if (strpos($source, 'receipt') !== false || strpos($source, 'collection') !== false || $source === 'or') {
            return 'CASH RECEIPTS BOOK';
        }
        if (strpos($source, 'disbursement') !== false || strpos($source, 'payment') !== false || strpos($source, 'payable') !== false || $source === 'ap') {
            return 'CASH DISBURSEMENT BOOK';
        }

        return 'JOURNAL BOOK';
    }

    private function mergeMovementMaps(array $a, array $b)
    {
        foreach ($b as $accountId => $columns) {
            foreach ($columns as $column => $amount) {
                $a[$accountId][$column] = ($a[$accountId][$column] ?? 0) + $amount;
            }
        }

        return $a;
    }

    private function flattenMovementsToTotal(array $movements)
    {
        $flat = [];
        foreach ($movements as $accountId => $columns) {
            $flat[$accountId]['TOTAL'] = array_sum($columns);
        }

        return $flat;
    }

    private function trialBalanceDocumentMovements($startDate, $endDate, $accounts, array $normals)
    {
        // Source documents affect financial statements only through posted journals.
        // This prevents invoices, receipts, bills, and payments from being counted twice.
        return [];
    }

    private function trialBalanceAccountIds($accounts)
    {
        return [
            'cash' => $this->trialBalanceFindAccountId($accounts, ['cash in bank', 'cash on hand', 'cash']),
            'ar_trade' => $this->trialBalanceFindAccountId($accounts, ['a/r-trade', 'a/trade-billing invoice', 'a/r trade']),
            'ar_soa' => $this->trialBalanceFindAccountId($accounts, ['a/r trade-soa a', 'a/r-soa', 'a/r soa']),
            'ar_reimbursable' => $this->trialBalanceFindAccountId($accounts, ['a/r-reimbursable charges', 'a/r reimbursable charges', 'reimbursable']),
            'revenue' => $this->trialBalanceFindAccountId($accounts, ['service income', 'service revenue', 'revenue', 'income']),
            'reimbursable_revenue' => $this->trialBalanceFindAccountId($accounts, ['reimbursable charges (cr)', 'reimbursable income', 'reimbursable revenue']),
            'expense' => $this->trialBalanceFindAccountId($accounts, ['expense', 'cost of services', 'cost']),
            'output_vat' => $this->trialBalanceFindAccountId($accounts, ['output vat', 'vat payable']),
            'input_vat' => $this->trialBalanceFindAccountId($accounts, ['input vat', 'vat input']),
            'ap_trade' => $this->trialBalanceFindAccountId($accounts, ['accounts payable', 'a/p trade', 'payable']),
            'wht_payable' => $this->trialBalanceFindAccountId($accounts, ['expanded withholding tax payable', 'withholding tax payable']),
        ];
    }

    private function trialBalanceFindAccountId($accounts, array $needles)
    {
        foreach ($needles as $needle) {
            $needle = strtolower($needle);
            foreach ($accounts as $account) {
                $haystack = strtolower(trim(($account->code ?? '').' '.($account->name ?? '').' '.($account->type ?? '').' '.($account->category_name ?? '')));
                if (strpos($haystack, $needle) !== false) {
                    return (int) $account->id;
                }
            }
        }

        return null;
    }

    private function trialBalanceAddMovement(array &$movements, $accountId, $column, $debit, $credit, array $normals)
    {
        if (!$accountId) {
            return;
        }

        $amount = $this->trialBalanceSignedAmount($debit, $credit, $normals[$accountId] ?? 'debit');
        $movements[$accountId][$column] = ($movements[$accountId][$column] ?? 0) + $amount;
    }

    private function trialBalanceSalesDocumentMovements(array &$movements, array $ids, array $normals, $startDate, $endDate)
    {
        if (!Schema::hasTable('sales_documents')) {
            return;
        }

        $query = DB::table('sales_documents')
            ->whereNotIn('status', ['cancelled', 'voided']);

        if ($startDate) {
            $query->where('document_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('document_date', '<=', $endDate);
        }

        $columns = ['id', 'document_type', 'template_code', 'ar_account_code', 'subtotal', 'tax_total', 'freight_total', 'grand_total'];
        if (!Schema::hasColumn('sales_documents', 'template_code')) {
            $columns[2] = DB::raw('null as template_code');
        }
        if (!Schema::hasColumn('sales_documents', 'ar_account_code')) {
            $columns[3] = DB::raw('null as ar_account_code');
        }

        foreach ($query->get($columns) as $document) {
            $isSoa = stripos((string) $document->template_code, 'SOA') !== false || stripos((string) $document->document_type, 'soa') !== false;
            $column = $isSoa ? 'SOA' : 'SALES BOOK';
            $subtotal = (float) ($document->subtotal ?: max(0, (float) $document->grand_total - (float) $document->tax_total - (float) $document->freight_total));
            $tax = (float) $document->tax_total;
            $freight = (float) $document->freight_total;
            $arAccountId = $this->trialBalanceArAccountId($document->ar_account_code, $ids, $isSoa);
            $reimbursableDebit = $document->ar_account_code === 'AR_REIMBURSABLE' ? (float) $document->grand_total : $freight;
            $tradeDebit = max(0, (float) $document->grand_total - $reimbursableDebit);

            $this->trialBalanceAddMovement($movements, $arAccountId, $column, $tradeDebit, 0, $normals);
            $this->trialBalanceAddMovement($movements, $ids['revenue'], $column, 0, $subtotal, $normals);
            $this->trialBalanceAddMovement($movements, $ids['output_vat'], $column, 0, $tax, $normals);
            $this->trialBalanceAddMovement($movements, $ids['ar_reimbursable'], $column, $reimbursableDebit, 0, $normals);
            $this->trialBalanceAddMovement($movements, $ids['reimbursable_revenue'] ?: $ids['revenue'], $column, 0, $freight, $normals);
        }
    }

    private function trialBalanceArAccountId($code, array $ids, $isSoa)
    {
        if ($code === 'AR_REIMBURSABLE') {
            return $ids['ar_reimbursable'] ?: $ids['ar_trade'];
        }
        if ($code === 'AR_TRADE_SOAA' || $isSoa) {
            return $ids['ar_soa'] ?: $ids['ar_trade'];
        }

        return $ids['ar_trade'] ?: $ids['ar_soa'] ?: $ids['ar_reimbursable'];
    }

    private function trialBalanceReceiptMovements(array &$movements, array $ids, array $normals, $startDate, $endDate)
    {
        if (!Schema::hasTable('receipts')) {
            return;
        }

        $query = DB::table('receipts')->whereNotIn('status', ['cancelled', 'voided']);
        if ($startDate) {
            $query->where('receipt_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('receipt_date', '<=', $endDate);
        }

        foreach ($query->get(['amount']) as $receipt) {
            $amount = (float) $receipt->amount;
            $this->trialBalanceAddMovement($movements, $ids['cash'], 'CASH RECEIPTS BOOK', $amount, 0, $normals);
            $this->trialBalanceAddMovement($movements, $ids['ar_trade'] ?: $ids['ar_soa'], 'CASH RECEIPTS BOOK', 0, $amount, $normals);
        }
    }

    private function trialBalancePurchaseDocumentMovements(array &$movements, array $ids, array $normals, $startDate, $endDate)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return;
        }

        $query = DB::table('purchase_documents')->whereNotIn('status', ['cancelled', 'voided']);
        if ($startDate) {
            $query->where('document_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('document_date', '<=', $endDate);
        }

        foreach ($query->get(['id', 'grand_total', 'gross_amount', 'tax_total', 'withholding_tax_amount']) as $bill) {
            $lineTotal = $this->trialBalancePurchaseLineMovements($movements, $bill->id, $normals, $ids);
            $tax = (float) ($bill->tax_total ?? 0);
            $wht = (float) ($bill->withholding_tax_amount ?? 0);
            $gross = (float) ($bill->gross_amount ?: ($bill->grand_total + $wht));
            $expenseTotal = max(0, $gross - $tax);

            if ($lineTotal <= 0) {
                $this->trialBalanceAddMovement($movements, $ids['expense'], 'JOURNAL BOOK', $expenseTotal, 0, $normals);
                $this->trialBalanceAddMovement($movements, $ids['input_vat'], 'JOURNAL BOOK', $tax, 0, $normals);
            }

            $this->trialBalanceAddMovement($movements, $ids['ap_trade'], 'JOURNAL BOOK', 0, (float) $bill->grand_total, $normals);
            if ($wht > 0) {
                $this->trialBalanceAddMovement($movements, $ids['wht_payable'] ?: $ids['ap_trade'], 'JOURNAL BOOK', 0, $wht, $normals);
            }
        }
    }

    private function trialBalancePurchaseLineMovements(array &$movements, $billId, array $normals, array $ids = [])
    {
        $table = Schema::hasTable('vendor_bill_lines') ? 'vendor_bill_lines' : (Schema::hasTable('purchase_document_lines') ? 'purchase_document_lines' : null);
        if (!$table) {
            return 0;
        }

        $columns = Schema::getColumnListing($table);
        $amountColumn = in_array('amount', $columns, true) ? 'amount' : 'line_total';
        $selectColumns = ['account_id', $amountColumn.' as amount'];
        if (in_array('tax_category', $columns, true)) {
            $selectColumns[] = 'tax_category';
        }
        $lines = DB::table($table)
            ->where('purchase_document_id', $billId)
            ->get($selectColumns);

        $lineTotal = 0.0;
        foreach ($lines as $line) {
            $amount = (float) $line->amount;
            $taxCategory = strtoupper(trim((string) ($line->tax_category ?? '')));
            $vatAmount = in_array($taxCategory, ['VAT12', '12%'], true) ? round($amount - ($amount / 1.12), 4) : 0.0;
            $netAmount = $amount - $vatAmount;

            $this->trialBalanceAddMovement($movements, $line->account_id, 'JOURNAL BOOK', $netAmount, 0, $normals);
            if ($vatAmount > 0) {
                $this->trialBalanceAddMovement($movements, $ids['input_vat'] ?? null, 'JOURNAL BOOK', $vatAmount, 0, $normals);
            }
            $lineTotal += $amount;
        }

        return $lineTotal;
    }

    private function trialBalancePaymentMovements(array &$movements, array $ids, array $normals, $startDate, $endDate)
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        $query = DB::table('payments')->whereNotIn('status', ['cancelled', 'voided']);
        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }

        foreach ($query->get(['amount']) as $payment) {
            $amount = (float) $payment->amount;
            $this->trialBalanceAddMovement($movements, $ids['ap_trade'], 'CASH DISBURSEMENT BOOK', $amount, 0, $normals);
            $this->trialBalanceAddMovement($movements, $ids['cash'], 'CASH DISBURSEMENT BOOK', 0, $amount, $normals);
        }
    }

    private function journalBalancesByAccount()
    {
        if (!Schema::hasTable('journal_entry_lines')) {
            return [];
        }

        return DB::table('journal_entry_lines')
            ->select('account_id', DB::raw('sum(debit - credit) as balance'))
            ->groupBy('account_id')
            ->pluck('balance', 'account_id')
            ->all();
    }

    private function generalLedgerWorkbookRows($period)
    {
        if (!Schema::hasTable('journal_entry_lines') || !Schema::hasTable('journal_entries')) {
            return [];
        }

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.company_id', $this->defaultCompanyId())
            ->when($period, function ($query) use ($period) {
                $this->applyMonthFilter($query, 'journal_entries.journal_date', $period);
            });

        $this->excludeVoidedSalesDocumentJournals($query);

        return $query
            ->orderBy('journal_entries.journal_date')
            ->orderBy('journal_entry_lines.id')
            ->limit(500)
            ->get([
                'journal_entries.journal_date',
                'journal_entries.journal_no',
                'journal_entries.source_module',
                'accounts.name as account_name',
                'journal_entry_lines.description',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
            ])
            ->map(function ($line) {
                return [
                    'MONTH' => $line->journal_date ? date('F', strtotime($line->journal_date)) : '',
                    'SOURCE' => trim(($line->source_module ?: 'GL').' '.$line->journal_no.' '.($line->account_name ?: '').' '.($line->description ?: '')),
                    'DEBIT' => (float) $line->debit,
                    'CREDIT' => (float) $line->credit,
                ];
            })
            ->all();
    }

    private function bankReconWorkbookRows()
    {
        if (!Schema::hasTable('bank_accounts')) {
            return [];
        }

        return DB::table('bank_accounts')
            ->orderBy('bank_name')
            ->get()
            ->map(function ($bank) {
                $bookBalance = (float) ($bank->book_balance ?? 0);
                return [
                    'BANK ACCOUNT' => trim(($bank->bank_name ?? '').' '.($bank->account_name ?? '')),
                    'ACCOUNT NO.' => $bank->account_no ?? '',
                    'CURRENCY' => $bank->currency ?? 'PHP',
                    'BOOK BALANCE' => $bookBalance,
                    'BANK BALANCE' => $bookBalance,
                    'OUTSTANDING CHECKS' => 0,
                    'DEPOSITS IN TRANSIT' => 0,
                    'RECONCILED BALANCE' => $bookBalance,
                ];
            })
            ->all();
    }

    private function disbursementWorkbookRows($period)
    {
        if (!Schema::hasTable('payments')) {
            return [];
        }

        return DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->when($period, function ($query) use ($period) {
                $this->applyMonthFilter($query, 'payments.payment_date', $period);
            })
            ->orderBy('payments.payment_date')
            ->orderBy('payments.id')
            ->limit(500)
            ->get($this->paymentReportColumns())
            ->map(function ($payment) {
                $amount = (float) $payment->amount;
                return [
                    'DATE' => $payment->payment_date,
                    'CV#' => $payment->payment_no,
                    'CHECK#' => $payment->check_no ?: $payment->reference_no,
                    'PAYEE' => $payment->supplier_name,
                    'PARTICULARS' => $payment->payment_method,
                    'CASH IN BANK- PESO' => strtoupper($payment->currency) === 'USD' ? 0 : $amount,
                    'CASH IN BANK-USD' => strtoupper($payment->currency) === 'USD' ? $amount : 0,
                    'ACCOUNTS PAYABLE- TRADE' => $amount,
                    'INPUT VAT' => 0,
                    'WITHHOLDING TAX PAYABLE' => 0,
                    'BANK CHARGES' => 0,
                    'OTHER EXPENSE' => 0,
                ];
            })
            ->all();
    }

    private function paymentReportColumns()
    {
        $columns = [
            'payments.payment_date',
            'payments.payment_no',
            'payments.check_no',
            'payments.amount',
            'payments.currency',
            'payments.payment_method',
            'suppliers.name as supplier_name',
        ];

        $columns[] = Schema::hasColumn('payments', 'reference_no')
            ? 'payments.reference_no'
            : DB::raw('null as reference_no');

        return $columns;
    }

    private function generalJournalWorkbookRows($period)
    {
        if (!Schema::hasTable('journal_entry_lines') || !Schema::hasTable('journal_entries')) {
            return [];
        }

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.company_id', $this->defaultCompanyId())
            ->when($period, function ($query) use ($period) {
                $this->applyMonthFilter($query, 'journal_entries.journal_date', $period);
            });

        $this->excludeVoidedSalesDocumentJournals($query);

        return $query
            ->orderBy('journal_entries.journal_date')
            ->orderBy('journal_entry_lines.id')
            ->limit(500)
            ->get([
                'journal_entries.journal_date',
                'journal_entries.journal_no',
                'journal_entries.memo',
                'accounts.name as account_name',
                'journal_entry_lines.description',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
            ])
            ->map(function ($line) {
                $account = strtoupper((string) $line->account_name);
                $amount = (float) $line->debit - (float) $line->credit;
                return [
                    'DATE' => $line->journal_date,
                    'JV NO' => $line->journal_no,
                    'PAYEE' => '',
                    'ACCOUNT TITLE/PARTICULARS' => trim(($line->account_name ?: '').' '.($line->description ?: $line->memo)),
                    'CASH IN BANK-PHP' => strpos($account, 'CASH') !== false ? $amount : 0,
                    'A/P TRADE - PHP' => strpos($account, 'PAYABLE') !== false ? $amount : 0,
                    'REIMBURSABLE CHARGES' => strpos($account, 'REIMBURS') !== false ? $amount : 0,
                    'VAT INPUT' => strpos($account, 'VAT') !== false ? $amount : 0,
                    'EXPANDED WITHHOLDING TAX PAYABLE' => strpos($account, 'WITHHOLD') !== false ? $amount : 0,
                    'BANK CHARGES' => strpos($account, 'BANK CHARGE') !== false ? $amount : 0,
                    'DEBIT' => (float) $line->debit,
                    'CREDIT' => (float) $line->credit,
                ];
            })
            ->all();
    }

    private function excludeVoidedSalesDocumentJournals($query)
    {
        if (!Schema::hasTable('sales_documents')
            || !Schema::hasColumn('journal_entries', 'source_type')
            || !Schema::hasColumn('journal_entries', 'source_id')) {
            return $query;
        }

        $voidedDocumentIds = DB::table('sales_documents')
            ->select('id')
            ->where('company_id', $this->defaultCompanyId())
            ->whereIn('status', ['cancelled', 'voided']);

        return $query->where(function ($journalQuery) use ($voidedDocumentIds) {
            $journalQuery->whereNull('journal_entries.source_type')
                ->orWhere('journal_entries.source_type', '<>', 'sales_document')
                ->orWhereNotIn('journal_entries.source_id', $voidedDocumentIds);
        });
    }

    private function cashReceiptsWorkbookRows($branch, $period)
    {
        if (!Schema::hasTable('receipts')) {
            return [];
        }

        $query = DB::table('receipts')
            ->leftJoin('customers', 'customers.id', '=', 'receipts.customer_id')
            ->leftJoin('receipt_applications', 'receipt_applications.receipt_id', '=', 'receipts.id')
            ->leftJoin('sales_documents', 'sales_documents.id', '=', 'receipt_applications.sales_document_id');

        if (Schema::hasColumn('receipts', 'class_code')) {
            $query->where('receipts.class_code', $branch);
        }

        $this->applyMonthFilter($query, 'receipts.receipt_date', $period);

        return $query
            ->orderBy('receipts.receipt_date')
            ->orderBy('receipts.id')
            ->limit(500)
            ->get([
                'receipts.receipt_date',
                'receipts.receipt_no',
                'receipts.receipt_type',
                'receipts.amount',
                'receipts.currency',
                'customers.name as customer_name',
                'sales_documents.document_no',
                'sales_documents.template_code',
            ])
            ->map(function ($receipt) {
                $isAcknowledgement = $receipt->receipt_type === 'acknowledgement_receipt';
                return [
                    'DATE' => $receipt->receipt_date,
                    'COLLECTION RECEIPT NUMBER' => $isAcknowledgement ? '' : $receipt->receipt_no,
                    'ACKNOWLEDGEMENT RECEIPT NUMBER' => $isAcknowledgement ? $receipt->receipt_no : '',
                    'CUSTOMER NAME' => $receipt->customer_name,
                    'A/R TRADE' => (float) $receipt->amount,
                    'A/R REIMBURSABLES CHARGES' => 0,
                    'WITHHOLDING-CWT (2307)' => 0,
                    'CASH (PHP)' => strtoupper($receipt->currency) === 'USD' ? 0 : (float) $receipt->amount,
                    'CASH (USD)' => strtoupper($receipt->currency) === 'USD' ? (float) $receipt->amount : 0,
                    'BILLING INVOICE NUMBER' => strpos((string) $receipt->template_code, 'SOA') === false ? $receipt->document_no : '',
                    'STATEMENT OF ACCOUNT NUMBER' => strpos((string) $receipt->template_code, 'SOA') !== false ? $receipt->document_no : '',
                ];
            })
            ->all();
    }

    private function salesJournalWorkbookRows($branch, $period)
    {
        if (!Schema::hasTable('sales_documents')) {
            return [];
        }

        $query = DB::table('sales_documents')
            ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->whereNotIn('sales_documents.status', ['cancelled', 'voided']);

        if (Schema::hasColumn('sales_documents', 'class_code')) {
            $query->where('sales_documents.class_code', $branch);
        }

        $this->applyMonthFilter($query, 'sales_documents.document_date', $period);

        return $query
            ->orderBy('sales_documents.document_date')
            ->orderBy('sales_documents.id')
            ->limit(500)
            ->get([
                'sales_documents.document_date',
                'sales_documents.document_no',
                'sales_documents.template_code',
                'sales_documents.vat_classification',
                'sales_documents.grand_total',
                'sales_documents.subtotal',
                'sales_documents.tax_total',
                'customers.name as customer_name',
            ])
            ->map(function ($document) {
                $subtotal = (float) ($document->subtotal ?: $document->grand_total);
                $isZeroRated = $document->vat_classification === 'zero-rated';
                $isSoa = strpos((string) $document->template_code, 'SOA') !== false;

                return [
                    'DATE' => $document->document_date,
                    'BILLING INVOICE' => $isSoa ? '' : $document->document_no,
                    'STATEMENT OF ACCOUNT NUMBER' => $isSoa ? $document->document_no : '',
                    'NAME OF COMPANY' => $document->customer_name,
                    'A/R TRADE' => (float) $document->grand_total,
                    'A/R REIMBURSABLE CHARGES' => 0,
                    'SERVICE INCOME ZERO RATED' => $isZeroRated ? $subtotal : 0,
                    'SERVICE INCOME VATABLE' => $isZeroRated ? 0 : $subtotal,
                    'OUTPUT VAT' => (float) $document->tax_total,
                    'CREDITABLE WITHHOLDING TAX' => 0,
                    'REIMBURSABLE CHARGES (CR)' => 0,
                ];
            })
            ->all();
    }

    private function applyMonthFilter($query, $column, $period)
    {
        if (!$period || !preg_match('/^\d{4}-\d{2}$/', $period)) {
            return $query;
        }

        return $query->whereBetween($column, [$period.'-01', date('Y-m-t', strtotime($period.'-01'))]);
    }

    public function financialStatementsReport()
    {
        $period = request('period', 'May 2026');
        $currency = request('currency', 'PHP');
        $company = Schema::hasTable('companies') ? DB::table('companies')->where('id', $this->defaultCompanyId())->first() : null;
        $incomeStatement = $this->incomeStatementBudgetSummary($period, $currency);

        $summaryCards = [
            ['label' => 'Revenue MTD', 'value' => 'PHP '.number_format($incomeStatement['totals']['operating_revenue']['actual'], 2), 'icon' => 'bi-graph-up-arrow', 'tone' => 'success'],
            ['label' => 'Net Income', 'value' => 'PHP '.number_format($incomeStatement['totals']['total_net']['actual'], 2), 'icon' => 'bi-piggy-bank', 'tone' => 'primary'],
            ['label' => 'Cash Balance', 'value' => 'PHP 1,268,500.00', 'icon' => 'bi-bank', 'tone' => 'info'],
            ['label' => 'Budget Variance', 'value' => 'PHP '.number_format($incomeStatement['totals']['total_net']['variance'], 2), 'icon' => 'bi-arrow-left-right', 'tone' => 'warning'],
        ];

        $statementSections = [
            'Income Statement' => [
                ['Service Revenue', 845230, 'Revenue from billing invoices, SOA, debit notes, and service charges.'],
                ['Cost of Services', -240300, 'Shipment-linked vendor bills, handling, brokerage, trucking, and direct costs.'],
                ['Gross Profit', 604930, 'Revenue less direct shipment costs.'],
                ['Operating Expenses', -142110, 'Salaries, rent, bank charges, utilities, and admin expenses.'],
                ['Net Income Before Tax', 462820, 'Operating result before income tax provision.'],
            ],
            'Balance Sheet' => [
                ['Cash and Bank', 1268500, 'PHP and USD bank balances converted to reporting currency.'],
                ['Accounts Receivable', 238700, 'Open customer invoices and SOA balances.'],
                ['Accounts Payable', -174250, 'Open vendor bills and approved unpaid payables.'],
                ['VAT Payable', -25932, 'Output VAT less input VAT.'],
                ['Owner Equity and Retained Earnings', -1307018, 'Capital accounts and retained earnings.'],
            ],
            'Cash Flow Statement' => [
                ['Collections from Customers', 328900, 'Official receipts, acknowledgement receipts, bank deposits, and online transfers.'],
                ['Payments to Suppliers', -174250, 'Checks, bank transfers, online payments, and AP payment runs.'],
                ['Tax Payments', -25932, 'VAT and withholding remittances.'],
                ['Owner Contributions', 250000, 'Financing cash movements.'],
                ['Ending Cash Balance', 1268500, 'Cash and bank ending balance.'],
            ],
        ];

        $reportCatalog = [
            'Core Financial Statements' => [
                ['name' => 'Balance Sheet', 'purpose' => 'Assets, liabilities, equity, AR, AP, bank, VAT, and retained earnings.', 'route' => route('erp.statements.balance-sheet'), 'frequency' => 'Monthly'],
                ['name' => 'Income Statement', 'purpose' => 'Revenue, direct costs, gross profit, operating expenses, and net income.', 'route' => route('erp.statements.income-statement'), 'frequency' => 'Monthly'],
                ['name' => 'Profit and Loss', 'purpose' => 'Management P&L with branch, client, shipment, and cost center filters.', 'route' => route('erp.statements.profit-loss'), 'frequency' => 'Monthly'],
                ['name' => 'Cash Flow Statement', 'purpose' => 'Operating, investing, financing, and ending cash movement.', 'route' => route('erp.statements.cash-flow'), 'frequency' => 'Monthly'],
                ['name' => 'Statement of Changes in Equity', 'purpose' => 'Capital movements, drawings, dividends, and retained earnings.', 'route' => route('erp.export', 'equity-statement'), 'frequency' => 'Monthly'],
            ],
            'General Ledger and Audit' => [
                ['name' => 'Trial Balance', 'purpose' => 'Debit and credit balances before generating financial statements.', 'route' => route('erp.export', 'trial-balance'), 'frequency' => 'Monthly'],
                ['name' => 'General Ledger', 'purpose' => 'Account-level ledger with source document, job ID, and posting reference.', 'route' => route('erp.export', 'general-ledger'), 'frequency' => 'Daily'],
                ['name' => 'Journal Entry Register', 'purpose' => 'Posted and draft journal entries with shipment job ID and approval status.', 'route' => route('erp.accounting.journals'), 'frequency' => 'Daily'],
                ['name' => 'Audit Trail', 'purpose' => 'Changes to finance records, layouts, approvals, exports, and posting entries.', 'route' => route('erp.action', 'audit-logs'), 'frequency' => 'As needed'],
            ],
            'Receivables and Billing' => [
                ['name' => 'AR Aging', 'purpose' => 'Current, 30, 60, 90+ balances by client, invoice, job, and currency.', 'route' => route('erp.customer-billing.aging'), 'frequency' => 'Daily'],
                ['name' => 'Customer Statement', 'purpose' => 'Open invoices, SOA, payments, reimbursables, and customer balances.', 'route' => route('erp.customer-billing.templates'), 'frequency' => 'Weekly'],
                ['name' => 'Sales Register', 'purpose' => 'Billing invoices, SOA A/B, debit notes, VAT class, and customer/job details.', 'route' => route('erp.export', 'sales-register'), 'frequency' => 'Monthly'],
                ['name' => 'Collection Report', 'purpose' => 'OR/AR receipts, payment applications, bank deposit, and withholding deductions.', 'route' => route('erp.export', 'collection-report'), 'frequency' => 'Daily'],
            ],
            'Payables and Cash' => [
                ['name' => 'AP Aging', 'purpose' => 'Vendor bills by due date, bucket, currency, approval, and payment status.', 'route' => route('erp.payables.aging'), 'frequency' => 'Weekly'],
                ['name' => 'Disbursement Report', 'purpose' => 'Checks, vouchers, bank transfers, online payments, and payment approvals.', 'route' => route('erp.export', 'disbursement-report'), 'frequency' => 'Daily'],
                ['name' => 'Cashbook', 'purpose' => 'Cash and bank deposits, withdrawals, transfers, and reconciliation status.', 'route' => route('erp.banking.cashbook'), 'frequency' => 'Daily'],
                ['name' => 'Bank Reconciliation', 'purpose' => 'Book balance versus bank balance with outstanding checks and deposits.', 'route' => route('erp.banking.reconciliation'), 'frequency' => 'Monthly'],
            ],
            'Tax and Compliance' => [
                ['name' => 'VAT Summary', 'purpose' => 'Output VAT, input VAT, VAT payable, zero-rated, and exempt sales.', 'route' => route('erp.tax.vat-summary'), 'frequency' => 'Monthly'],
                ['name' => 'Withholding Tax Report', 'purpose' => 'EWT/CWT computation by document, vendor, client, and tax rate.', 'route' => route('erp.tax.withholding'), 'frequency' => 'Monthly'],
                ['name' => 'Sales and Purchases Schedule', 'purpose' => 'VAT filing support for taxable, exempt, and zero-rated transactions.', 'route' => route('erp.tax.sales-purchases'), 'frequency' => 'Monthly'],
                ['name' => 'BIR Compliance Audit Trail', 'purpose' => 'Tax source documents, adjustments, approvals, and filing history.', 'route' => route('erp.tax.audit-trail'), 'frequency' => 'As needed'],
            ],
            'Management Reports' => [
                ['name' => 'Shipment Profitability', 'purpose' => 'Revenue, direct costs, reimbursables, margin, and profit by job.', 'route' => route('erp.export', 'shipment-profitability'), 'frequency' => 'Weekly'],
                ['name' => 'Client Profitability', 'purpose' => 'Revenue and margin by customer, branch, route, and service item.', 'route' => route('erp.export', 'client-profitability'), 'frequency' => 'Monthly'],
                ['name' => 'Cost Center Report', 'purpose' => 'Income and expenses by branch, department, and operation cost center.', 'route' => route('erp.settings.index').'#cost-centers', 'frequency' => 'Monthly'],
                ['name' => 'FX Gain/Loss Report', 'purpose' => 'USD/PHP realized and unrealized gains and losses.', 'route' => route('erp.fx.gains-losses'), 'frequency' => 'Monthly'],
            ],
        ];

        return view('erp.reports.financial-statements', compact('period', 'currency', 'company', 'incomeStatement', 'summaryCards', 'statementSections', 'reportCatalog'));
    }

    private function incomeStatementBudgetSummary($period, $currency)
    {
        $currency = str_replace(' Consolidated', '', $currency ?: 'PHP');
        $serviceRevenue = $this->statementSalesActual($currency);
        $directCosts = $this->statementPurchaseActual($currency);

        $operatingRevenueRows = [
            ['Service Revenue', $serviceRevenue > 0 ? $serviceRevenue : 845230.00, 862500.00],
            ['Documentation & Local Charges', 87946.43, 90000.00],
            ['Reimbursable Revenue', 63839.24, 62000.00],
            ['Freight and Handling Income', 127900.00, 125000.00],
            ['Other Operating Revenue', 48900.00, 46000.00],
        ];

        $operatingExpenseRows = [
            ['Cost of Services', $directCosts > 0 ? $directCosts : 240300.00, 252000.00],
            ['Salaries & Benefits', 53716.50, 54924.96],
            ['Brokerage and Trucking', 68250.00, 70500.00],
            ['Supplies/Expenses', 6518.77, 6446.34],
            ['Bank Charges and Fees', 2665.62, 2646.62],
            ['Building & Maintenance Exp.', 8507.26, 8612.17],
            ['Other Operating Expenses', 4132.82, 3666.47],
        ];

        $nonOperatingRevenueRows = [
            ['FX Gain / Revaluation Income', 70936.97, 0.00],
            ['Interest Income', 828.58, 0.00],
        ];

        $nonOperatingExpenseRows = [
            ['FX Loss / Revaluation Expense', 83484.11, 0.00],
            ['Tax Penalties and Other Charges', 200.00, 0.00],
        ];

        $operatingRevenueRows = $this->statementVarianceRows($operatingRevenueRows);
        $operatingExpenseRows = $this->statementVarianceRows($operatingExpenseRows);
        $nonOperatingRevenueRows = $this->statementVarianceRows($nonOperatingRevenueRows);
        $nonOperatingExpenseRows = $this->statementVarianceRows($nonOperatingExpenseRows);

        $operatingRevenue = $this->statementRowTotals($operatingRevenueRows);
        $operatingExpenses = $this->statementRowTotals($operatingExpenseRows);
        $netOperating = [
            'actual' => $operatingRevenue['actual'] - $operatingExpenses['actual'],
            'budget' => $operatingRevenue['budget'] - $operatingExpenses['budget'],
        ];
        $netOperating['variance'] = $netOperating['actual'] - $netOperating['budget'];

        $nonOperatingRevenue = $this->statementRowTotals($nonOperatingRevenueRows);
        $nonOperatingExpenses = $this->statementRowTotals($nonOperatingExpenseRows);
        $netNonOperating = [
            'actual' => $nonOperatingRevenue['actual'] - $nonOperatingExpenses['actual'],
            'budget' => $nonOperatingRevenue['budget'] - $nonOperatingExpenses['budget'],
        ];
        $netNonOperating['variance'] = $netNonOperating['actual'] - $netNonOperating['budget'];

        $totalNet = [
            'actual' => $netOperating['actual'] + $netNonOperating['actual'],
            'budget' => $netOperating['budget'] + $netNonOperating['budget'],
        ];
        $totalNet['variance'] = $totalNet['actual'] - $totalNet['budget'];

        return [
            'title' => 'Income Statement - Summary With Budgets',
            'subtitle' => 'For '.$period,
            'currency' => $currency,
            'sections' => [
                'Operating Revenue' => $operatingRevenueRows,
                'Operating Expenses' => $operatingExpenseRows,
                'Non-Operating Revenue' => $nonOperatingRevenueRows,
                'Non-Operating Expenses' => $nonOperatingExpenseRows,
            ],
            'totals' => [
                'operating_revenue' => $operatingRevenue,
                'operating_expenses' => $operatingExpenses,
                'net_operating' => $netOperating,
                'non_operating_revenue' => $nonOperatingRevenue,
                'non_operating_expenses' => $nonOperatingExpenses,
                'net_non_operating' => $netNonOperating,
                'total_net' => $totalNet,
            ],
        ];
    }

    private function statementVarianceRows(array $rows)
    {
        return array_map(function ($row) {
            $actual = (float) $row[1];
            $budget = (float) $row[2];

            return [
                'label' => $row[0],
                'actual' => $actual,
                'budget' => $budget,
                'variance' => $actual - $budget,
            ];
        }, $rows);
    }

    private function statementRowTotals(array $rows)
    {
        return [
            'actual' => array_sum(array_column($rows, 'actual')),
            'budget' => array_sum(array_column($rows, 'budget')),
            'variance' => array_sum(array_column($rows, 'variance')),
        ];
    }

    private function statementSalesActual($currency)
    {
        if (!Schema::hasTable('sales_documents')) {
            return 0.0;
        }

        return (float) DB::table('sales_documents')
            ->where('currency', $currency)
            ->whereNotIn('status', ['cancelled', 'voided'])
            ->sum('grand_total');
    }

    private function statementPurchaseActual($currency)
    {
        if (!Schema::hasTable('purchase_documents')) {
            return 0.0;
        }

        return (float) DB::table('purchase_documents')
            ->where('currency', $currency)
            ->whereNotIn('status', ['cancelled', 'voided'])
            ->sum('grand_total');
    }

    public function financeSettings()
    {
        $companyId = $this->defaultCompanyId();
        $company = Schema::hasTable('companies') ? DB::table('companies')->where('id', $companyId)->first() : null;
        $branches = $this->financeBranches($companyId);
        $costCenters = $this->financeCostCenters($companyId);
        $items = $this->financeItems($companyId);
        $numberSeries = $this->financeNumberSeries($companyId);
        $taxCodes = $this->financeTaxCodes($companyId);
        $currencyRates = $this->financeCurrencyRates($companyId);
        $bankAccounts = $this->financeBankAccounts($companyId);
        $accounts = $this->financeAccountOptions($companyId);
        $payeeRepresentatives = Schema::hasTable('suppliers')
            ? DB::table('suppliers')
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get([
                    'id as supplier_id', 'code', 'name',
                    'bir_2307_payee_representative_name',
                    'bir_2307_payee_representative_title',
                    'bir_2307_payee_representative_tin',
                ])
            : collect();

        $settingStats = [
            ['label' => 'Cost Centers', 'value' => $costCenters->count(), 'icon' => 'bi-diagram-3', 'tone' => 'primary'],
            ['label' => 'Items', 'value' => $items->count(), 'icon' => 'bi-box-seam', 'tone' => 'success'],
            ['label' => 'Bank Accounts', 'value' => $bankAccounts->count(), 'icon' => 'bi-bank', 'tone' => 'warning'],
            ['label' => 'Tax Codes', 'value' => $taxCodes->count(), 'icon' => 'bi-percent', 'tone' => 'danger'],
        ];

        $settingGroups = [
            'Accounting Setup' => [
                ['name' => 'Chart of Accounts', 'description' => 'GL accounts for AR, AP, revenue, expenses, VAT, WHT, bank, cash, and equity.', 'route' => route('erp.accounting.coa'), 'icon' => 'bi-journal-text', 'status' => 'Active'],
                ['name' => 'Cost Centers', 'description' => 'Department, branch, and operation-level cost tracking for management reporting.', 'route' => route('erp.settings.index').'#cost-centers', 'icon' => 'bi-diagram-3', 'status' => 'Required'],
                ['name' => 'Number Series', 'description' => 'Document numbering for BI, SOA, OR, AP bills, check vouchers, payments, and journal entries.', 'route' => route('erp.settings.index').'#number-series', 'icon' => 'bi-123', 'status' => 'Active'],
            ],
            'Billing & Item Setup' => [
                ['name' => 'Item List', 'description' => 'Service, freight, documentation, terminal handling, reimbursable, and tax treatment defaults.', 'route' => route('erp.settings.index').'#item-list', 'icon' => 'bi-box-seam', 'status' => 'Required'],
                ['name' => 'Invoice Templates', 'description' => 'Billing Invoice, SOA B, SOA A, Debit Note, Collection Receipt, and custom templates.', 'route' => route('erp.customer-billing.templates'), 'icon' => 'bi-file-earmark-text', 'status' => 'Active'],
                ['name' => 'Print Layouts', 'description' => 'Pre-printed form alignment, paper size, field positions, and custom printable fields.', 'route' => route('erp.billings.print-layout.invoice'), 'icon' => 'bi-sliders', 'status' => 'Active'],
            ],
            'Tax, Currency & Banking' => [
                ['name' => 'Tax Codes', 'description' => '12% VAT, zero-rated, exempt, EWT, CWT, and withholding defaults for AR/AP.', 'route' => route('erp.settings.index').'#tax-codes', 'icon' => 'bi-percent', 'status' => 'Active'],
                ['name' => 'BIR 2307 Representatives', 'description' => 'Authorized payor and payee names, titles, and TINs printed on Form 2307.', 'route' => route('erp.settings.index').'#bir-2307-representatives', 'icon' => 'bi-person-vcard', 'status' => 'Active'],
                ['name' => 'Currency Rates', 'description' => 'USD/PHP exchange rates for invoice, AP, payment, bank, and FX revaluation.', 'route' => route('erp.settings.index').'#currency-rates', 'icon' => 'bi-currency-dollar', 'status' => 'Required'],
                ['name' => 'Bank Accounts', 'description' => 'PHP and USD bank accounts for receipts, payments, check printing, and reconciliation.', 'route' => route('erp.settings.index').'#bank-accounts', 'icon' => 'bi-bank', 'status' => 'Active'],
            ],
            'Controls' => [
                ['name' => 'Approval Matrix', 'description' => 'Approval limits for billing, AP payments, credit overrides, FX, tax, and user access.', 'route' => route('erp.approval-matrix'), 'icon' => 'bi-check2-square', 'status' => 'Active'],
                ['name' => 'User Access Control', 'description' => 'Role permissions by module for view, create, edit, and delete access.', 'route' => route('erp.users.index'), 'icon' => 'bi-person-lock', 'status' => 'Active'],
                ['name' => 'Audit Trail', 'description' => 'Activity logs for finance settings, documents, approvals, print, export, and posting.', 'route' => route('erp.action', 'audit-logs'), 'icon' => 'bi-clock-history', 'status' => 'Enabled'],
            ],
        ];

        return view('erp.settings.index', compact(
            'settingStats',
            'settingGroups',
            'company',
            'branches',
            'costCenters',
            'items',
            'numberSeries',
            'taxCodes',
            'currencyRates',
            'bankAccounts',
            'accounts',
            'payeeRepresentatives'
        ));
    }

    public function saveFinanceCompanySettings()
    {
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'tin' => ['nullable', 'string', 'max:17', 'regex:/^\d{3}-\d{3}-\d{3}-\d{5}$/'],
            'address' => 'nullable|string|max:1000',
            'zip_code' => ['nullable', 'string', 'max:10', 'regex:/^\d{4}$/'],
            'base_currency' => 'required|string|max:3',
            'bir_2307_payor_representative_name' => 'nullable|string|max:255',
            'bir_2307_payor_representative_title' => 'nullable|string|max:255',
            'bir_2307_payor_representative_tin' => ['nullable', 'string', 'max:30', 'regex:/^\d{3}-\d{3}-\d{3}-\d{3}$/'],
        ]);

        if (Schema::hasTable('companies')) {
            DB::table('companies')->where('id', $this->defaultCompanyId())->update($this->tablePayload('companies', $data + ['updated_at' => now()]));
        }

        return redirect()->route('erp.settings.index')->with('status', 'Company finance settings saved.');
    }

    public function saveFinanceBir2307PayeeRepresentative()
    {
        $data = request()->validate([
            'supplier_id' => 'required|integer',
            'bir_2307_payee_representative_name' => 'nullable|string|max:255',
            'bir_2307_payee_representative_title' => 'nullable|string|max:255',
            'bir_2307_payee_representative_tin' => ['nullable', 'string', 'max:30', 'regex:/^\d{3}-\d{3}-\d{3}-\d{3}$/'],
        ]);

        $supplier = DB::table('suppliers')
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $data['supplier_id'])
            ->first();
        abort_unless($supplier, 422, 'The selected payee is invalid for this company.');

        DB::table('suppliers')->where('id', $supplier->id)->update([
            'bir_2307_payee_representative_name' => trim((string) ($data['bir_2307_payee_representative_name'] ?? '')) ?: null,
            'bir_2307_payee_representative_title' => trim((string) ($data['bir_2307_payee_representative_title'] ?? '')) ?: null,
            'bir_2307_payee_representative_tin' => trim((string) ($data['bir_2307_payee_representative_tin'] ?? '')) ?: null,
            'updated_at' => now(),
        ]);

        return redirect(route('erp.settings.index').'#bir-2307-representatives')
            ->with('status', 'BIR 2307 payee representative saved for '.$supplier->name.'.');
    }

    public function saveFinanceBranch()
    {
        $data = request()->validate([
            'id' => 'nullable|integer',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'is_active' => 'nullable|string',
        ]);

        $this->upsertFinanceRow('branches', $data['id'] ?? null, [
            'company_id' => $this->defaultCompanyId(),
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'is_active' => isset($data['is_active']),
        ]);

        return redirect()->route('erp.settings.index')->with('status', 'Branch saved.');
    }

    public function saveFinanceCostCenter()
    {
        $data = request()->validate([
            'id' => 'nullable|integer',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'branch_id' => 'nullable|integer',
            'is_active' => 'nullable|string',
        ]);

        $this->upsertFinanceRow('cost_centers', $data['id'] ?? null, [
            'company_id' => $this->defaultCompanyId(),
            'branch_id' => $data['branch_id'] ?? null,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'department' => $data['department'] ?? null,
            'is_active' => isset($data['is_active']),
        ]);

        return redirect(route('erp.settings.index').'#cost-centers')->with('status', 'Cost center saved.');
    }

    public function saveFinanceItem()
    {
        $data = request()->validate([
            'id' => 'nullable|integer',
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'nullable|string|max:255',
            'account_name' => 'required|string|in:Reimbursable charges,Service Income-SOA A,Service Income- Vatable,Service Income-Zero rated',
            'unit_of_measure' => 'required|string|max:50',
            'standard_cost' => 'nullable',
            'selling_price' => 'nullable',
            'is_active' => 'nullable|string',
        ]);

        $this->upsertFinanceRow('items', $data['id'] ?? null, [
            'company_id' => $this->defaultCompanyId(),
            'sku' => strtoupper($data['sku']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'account_name' => $data['account_name'],
            'tax_type' => $this->invoiceItemAccountMeta($data['account_name'])['tax_type'],
            'unit_of_measure' => $data['unit_of_measure'],
            'standard_cost' => $this->moneyToFloat($data['standard_cost'] ?? 0),
            'selling_price' => $this->moneyToFloat($data['selling_price'] ?? 0),
            'is_active' => isset($data['is_active']),
        ]);

        return redirect(route('erp.settings.index').'#item-list')->with('status', 'Item saved.');
    }

    public function saveFinanceNumberSeries()
    {
        $data = request()->validate([
            'id' => 'nullable|integer',
            'document_type' => 'required|string|max:100',
            'prefix' => 'nullable|string|max:20',
            'current_number' => 'required|integer|min:0',
            'padding' => 'required|integer|min:1|max:12',
            'is_active' => 'nullable|string',
        ]);

        $this->upsertFinanceRow('number_series', $data['id'] ?? null, [
            'company_id' => $this->defaultCompanyId(),
            'document_type' => $data['document_type'],
            'prefix' => $data['prefix'] ?? null,
            'current_number' => (int) $data['current_number'],
            'padding' => (int) $data['padding'],
            'is_active' => isset($data['is_active']),
        ]);

        return redirect(route('erp.settings.index').'#number-series')->with('status', 'Number series saved.');
    }

    public function saveFinanceTaxCode()
    {
        $data = request()->validate([
            'id' => 'nullable|integer',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'rate' => 'required',
            'tax_type' => 'required|string|max:100',
            'classification' => 'nullable|string|max:100',
            'is_withholding' => 'nullable|string',
            'withholding_kind' => 'nullable|string|max:100',
            'account_id' => 'nullable|integer',
            'is_active' => 'nullable|string',
        ]);

        $this->upsertFinanceRow('tax_codes', $data['id'] ?? null, [
            'company_id' => $this->defaultCompanyId(),
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'rate' => $this->moneyToFloat($data['rate']),
            'tax_type' => $data['tax_type'],
            'classification' => $data['classification'] ?? null,
            'is_withholding' => isset($data['is_withholding']),
            'withholding_kind' => $data['withholding_kind'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'is_active' => isset($data['is_active']),
        ]);

        return redirect(route('erp.settings.index').'#tax-codes')->with('status', 'Tax code saved.');
    }

    public function saveFinanceCurrencyRate()
    {
        $data = request()->validate([
            'id' => 'nullable|integer',
            'source_currency' => 'required|string|max:3',
            'target_currency' => 'required|string|max:3',
            'rate_date' => 'required|date',
            'rate' => 'required',
            'rate_source' => 'nullable|string|max:100',
            'is_active' => 'nullable|string',
        ]);

        $this->upsertFinanceRow('currency_rates', $data['id'] ?? null, [
            'company_id' => $this->defaultCompanyId(),
            'source_currency' => strtoupper($data['source_currency']),
            'target_currency' => strtoupper($data['target_currency']),
            'rate_date' => $data['rate_date'],
            'rate' => $this->moneyToFloat($data['rate']),
            'rate_source' => $data['rate_source'] ?? null,
            'is_active' => isset($data['is_active']),
        ]);

        return redirect(route('erp.settings.index').'#currency-rates')->with('status', 'Currency rate saved.');
    }

    public function saveFinanceBankAccount()
    {
        $data = request()->validate([
            'id' => 'nullable|integer',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_no' => 'required|string|max:100',
            'swift_code' => 'nullable|string|max:50',
            'currency' => 'required|string|max:3',
            'book_balance' => 'nullable',
            'bank_balance' => 'nullable',
            'account_id' => 'nullable|integer',
            'is_active' => 'nullable|string',
        ]);

        $this->upsertFinanceRow('bank_accounts', $data['id'] ?? null, [
            'company_id' => $this->defaultCompanyId(),
            'account_id' => $data['account_id'] ?? null,
            'bank_name' => $data['bank_name'],
            'branch_name' => $data['branch_name'] ?? null,
            'account_name' => $data['account_name'],
            'account_no' => $data['account_no'],
            'swift_code' => $data['swift_code'] ?? null,
            'currency' => strtoupper($data['currency']),
            'book_balance' => $this->moneyToFloat($data['book_balance'] ?? 0),
            'bank_balance' => $this->moneyToFloat($data['bank_balance'] ?? 0),
            'is_active' => isset($data['is_active']),
        ]);

        return redirect(route('erp.settings.index').'#bank-accounts')->with('status', 'Bank account saved.');
    }

    private function upsertFinanceRow($table, $id, array $payload)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $payload = $this->tablePayload($table, $payload + ['updated_at' => now()]);
        if ($id && DB::table($table)->where('id', $id)->exists()) {
            DB::table($table)->where('id', $id)->update($payload);
            return;
        }

        $criteria = $this->financeUniqueCriteria($table, $payload);
        if (!empty($criteria) && DB::table($table)->where($criteria)->exists()) {
            DB::table($table)->where($criteria)->update($payload);
            return;
        }

        $payload['created_at'] = now();
        DB::table($table)->insert($payload);
    }

    private function financeUniqueCriteria($table, array $payload)
    {
        $companyId = $payload['company_id'] ?? null;

        if ($table === 'branches' || $table === 'cost_centers' || $table === 'tax_codes') {
            return [
                'company_id' => $companyId,
                'code' => $payload['code'] ?? null,
            ];
        }

        if ($table === 'items') {
            return [
                'company_id' => $companyId,
                'sku' => $payload['sku'] ?? null,
            ];
        }

        if ($table === 'number_series') {
            return [
                'company_id' => $companyId,
                'document_type' => $payload['document_type'] ?? null,
            ];
        }

        if ($table === 'currency_rates') {
            return [
                'company_id' => $companyId,
                'source_currency' => $payload['source_currency'] ?? null,
                'target_currency' => $payload['target_currency'] ?? null,
                'rate_date' => $payload['rate_date'] ?? null,
            ];
        }

        return [];
    }

    private function financeBranches($companyId)
    {
        if (!Schema::hasTable('branches')) {
            return collect();
        }

        return DB::table('branches')
            ->where('company_id', $companyId)
            ->orderBy('code')
            ->get();
    }

    private function financeCostCenters($companyId)
    {
        if (!Schema::hasTable('cost_centers')) {
            return collect();
        }

        return DB::table('cost_centers')
            ->leftJoin('branches', 'branches.id', '=', 'cost_centers.branch_id')
            ->select('cost_centers.*', 'branches.code as branch_code', 'branches.name as branch_name')
            ->where('cost_centers.company_id', $companyId)
            ->orderBy('cost_centers.code')
            ->get();
    }

    private function financeItems($companyId)
    {
        if (!Schema::hasTable('items')) {
            return collect();
        }

        return DB::table('items')
            ->where('company_id', $companyId)
            ->orderBy('sku')
            ->get()
            ->map(function ($item) {
                $meta = $this->invoiceItemAccountMeta($item->account_name ?? '', $item->tax_type ?? '');
                $item->account_tag = $meta['tag'];
                $item->invoice_tax_category = $meta['tax_category'];

                return $item;
            });
    }

    private function financeNumberSeries($companyId)
    {
        if (!Schema::hasTable('number_series')) {
            return collect();
        }

        return DB::table('number_series')
            ->where('company_id', $companyId)
            ->orderBy('document_type')
            ->get();
    }

    private function financeTaxCodes($companyId)
    {
        if (!Schema::hasTable('tax_codes')) {
            return collect();
        }

        return DB::table('tax_codes')
            ->leftJoin('accounts', 'accounts.id', '=', 'tax_codes.account_id')
            ->select('tax_codes.*', 'accounts.code as account_code', 'accounts.name as account_name')
            ->where('tax_codes.company_id', $companyId)
            ->orderBy('tax_codes.code')
            ->get();
    }

    private function financeCurrencyRates($companyId)
    {
        if (!Schema::hasTable('currency_rates')) {
            return collect();
        }

        return DB::table('currency_rates')
            ->where('company_id', $companyId)
            ->orderBy('rate_date', 'desc')
            ->orderBy('source_currency')
            ->limit(20)
            ->get();
    }

    private function financeBankAccounts($companyId)
    {
        if (!Schema::hasTable('bank_accounts')) {
            return collect();
        }

        return DB::table('bank_accounts')
            ->leftJoin('accounts', 'accounts.id', '=', 'bank_accounts.account_id')
            ->select('bank_accounts.*', 'accounts.code as gl_code', 'accounts.name as gl_name')
            ->where('bank_accounts.company_id', $companyId)
            ->orderBy('bank_accounts.bank_name')
            ->get();
    }

    private function financeAccountOptions($companyId)
    {
        if (!Schema::hasTable('accounts')) {
            return collect();
        }

        return DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function agingReport()
    {
        $filters = [
            'as_of' => request('as_of', date('Y-m-d')),
            'class' => request('class', 'All Classes'),
            'currency' => request('currency', 'All Currency'),
            'vat' => request('vat', 'All VAT'),
        ];

        $rows = $this->agingReportRows($filters);
        $summary = $this->agingReportSummary($rows);

        return view('erp.reports.aging', compact('rows', 'filters', 'summary'));
    }

    private function agingReportRows(array $filters)
    {
        if (!Schema::hasTable('sales_documents')) {
            return [];
        }

        $balanceExpression = $this->salesDocumentBalanceExpression();
        $asOf = $filters['as_of'] ?: date('Y-m-d');

        return DB::table('sales_documents')
            ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->select(
                'customers.customer_id',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'sales_documents.document_no',
                'sales_documents.job_shipment_id',
                'sales_documents.shipment_reference',
                'sales_documents.currency',
                'sales_documents.grand_total',
                'sales_documents.paid_total',
                'sales_documents.balance_due',
                'sales_documents.due_date',
                'sales_documents.vat_classification',
                'sales_documents.class_code'
            )
            ->whereNotIn('sales_documents.status', ['cancelled', 'voided', 'paid'])
            ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->whereRaw($balanceExpression.' > 0')
            ->when(($filters['class'] ?? 'All Classes') !== 'All Classes', function ($query) use ($filters) {
                return $query->where('sales_documents.class_code', $filters['class']);
            })
            ->when(($filters['currency'] ?? 'All Currency') !== 'All Currency', function ($query) use ($filters) {
                return $query->where('sales_documents.currency', $filters['currency']);
            })
            ->when(($filters['vat'] ?? 'All VAT') !== 'All VAT', function ($query) use ($filters) {
                return $query->where('sales_documents.vat_classification', $filters['vat']);
            })
            ->orderBy('sales_documents.due_date')
            ->orderBy('customers.name')
            ->orderBy('sales_documents.id')
            ->get()
            ->map(function ($invoice) use ($asOf) {
                $balance = $this->salesDocumentBalance($invoice);
                $daysPastDue = $invoice->due_date ? max(0, \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($asOf), false)) : 0;
                $current = $daysPastDue <= 0 ? $balance : 0;
                $thirty = $daysPastDue > 0 && $daysPastDue <= 30 ? $balance : 0;
                $sixty = $daysPastDue > 30 && $daysPastDue <= 60 ? $balance : 0;
                $ninety = $daysPastDue > 60 ? $balance : 0;

                return [
                    $invoice->customer_id ?: ($invoice->customer_code ?: '-'),
                    $invoice->customer_name ?: 'Guest / Non-tax Client',
                    $invoice->job_shipment_id ?: $invoice->shipment_reference,
                    $invoice->document_no,
                    $invoice->currency ?: 'PHP',
                    $invoice->vat_classification ?: '-',
                    $invoice->class_code ?: '-',
                    $current,
                    $thirty,
                    $sixty,
                    $ninety,
                ];
            })
            ->all();
    }

    private function agingReportSummary(array $rows)
    {
        $summary = ['current' => 0, 'thirty' => 0, 'sixty' => 0, 'ninety' => 0, 'total' => 0];

        foreach ($rows as $row) {
            $summary['current'] += (float) ($row[7] ?? 0);
            $summary['thirty'] += (float) ($row[8] ?? 0);
            $summary['sixty'] += (float) ($row[9] ?? 0);
            $summary['ninety'] += (float) ($row[10] ?? 0);
        }

        $summary['total'] = $summary['current'] + $summary['thirty'] + $summary['sixty'] + $summary['ninety'];

        return $summary;
    }

    private function modules()
    {
        return [
            'accounting' => [
                'title' => 'Accounting & General Ledger',
                'subtitle' => 'Chart of accounts, shipment-linked journal entries, financial statements, and audit trail.',
                'actions' => ['Chart of Accounts', 'Journal Entries', 'Balance Sheet', 'Profit & Loss'],
                'features' => ['Chart of Accounts', 'Journal Entries', 'Shipment Job ID Required', 'Balance Sheet', 'Profit and Loss', 'Income Statement', 'Cash Flow Statement', 'Audit Trail'],
                'columns' => ['Journal No.', 'Shipment Job ID', 'Reference', 'Debit', 'Credit', 'Status'],
                'rows' => [
                    ['JV-2026-00218', 'JOB-SEA-2026-0423', 'BI-2026-01043', 'Accounts Receivable', 'Service Revenue', 'Draft'],
                    ['JV-2026-00217', 'JOB-SEA-2026-0422', 'BI-2026-01042', 'A/Trade-Billing Invoice', 'Service Revenue', 'Posted'],
                    ['JV-2026-00216', 'JOB-SEA-2026-0398', 'SOAA-2026-00211', 'A/R Trade-SOA A', 'Service Revenue', 'Posted'],
                ],
            ],
            'receivables' => [
                'title' => 'Accounts Receivable',
                'subtitle' => 'Customer masterlist, create invoice, enter billing information, receive payment, and monitor aging.',
                'actions' => ['Customer Masterlist', 'Create Invoice', 'Receive Payment', 'Aging Report'],
                'features' => ['Customer ID', 'Tax Information', 'Contact Information', 'Job/Shipment ID', 'Invoice No.', 'VAT 12% / Zero-rated / Exempt', 'Class: MANILA / PAMPANGA / CEBU', '30/60/90 Terms', 'PHP / USD'],
                'columns' => ['Customer ID', 'Customer', 'Terms', 'Currency', 'AR Account', 'Class'],
                'rows' => [
                    ['CUST-001', 'North Harbor Trading', '30 Days', 'PHP', 'A/Trade-Billing Invoice', 'MANILA'],
                    ['CUST-002', 'Blue Anchor Logistics', '60 Days', 'USD', 'A/R Trade-SOA A', 'CEBU'],
                    ['CUST-003', 'Island Prime Foods', '90 Days', 'PHP', 'A/R Trade-Debit Note', 'PAMPANGA'],
                ],
            ],
            'billing' => [
                'title' => 'Customer Invoicing',
                'subtitle' => 'Create invoices first, enter billing and shipment information, then proceed to payment.',
                'actions' => ['Billing CRUD', 'Create Invoice', 'Invoice Preview', 'Receive Payment', 'Templates'],
                'features' => ['BI Pre-printed', 'SOA Pre-printed', 'SOA A Plain Form', 'Debit Note Pre-printed', 'Invoice No.', 'Customer ID', 'Job/Shipment ID', 'VAT Classification'],
                'columns' => ['Doc Type', 'Doc No.', 'Customer ID', 'Job/Shipment ID', 'VAT', 'Class'],
                'rows' => [
                    ['Billing Invoice', 'BI-2026-01042', 'CUST-001', 'JOB-SEA-2026-0422', '12%', 'MANILA'],
                    ['Statement A', 'SOAA-2026-00211', 'CUST-002', 'JOB-SEA-2026-0398', 'zero-rated', 'CEBU'],
                    ['Debit Note', 'DN-2026-00042', 'CUST-003', 'JOB-SEA-2026-0365', 'exempt', 'PAMPANGA'],
                ],
            ],
            'collections' => [
                'title' => 'Collections / Receive Payment',
                'subtitle' => 'Collection Receipts, Acknowledgement Receipts, SOA A collections, and payment allocation.',
                'actions' => ['Collection Receipt', 'Acknowledgement Receipt', 'SOA A Collection', 'Deposit Collection'],
                'features' => ['OR Pre-printed', 'AR Pre-printed', 'SOA A No Pre-printed Form', 'Cash', 'Check', 'Bank Transfer', 'Partial Payment', 'Multiple Invoice Payment'],
                'columns' => ['Receipt Type', 'Receipt No.', 'Customer ID', 'Job/Shipment ID', 'Currency', 'Amount'],
                'rows' => [
                    ['Collection Receipt', 'OR-2026-00618', 'CUST-001', 'JOB-SEA-2026-0422', 'PHP', '43,200.00'],
                    ['Acknowledgement Receipt', 'AR-2026-00109', 'CUST-003', 'JOB-SEA-2026-0365', 'PHP', '12,800.00'],
                    ['SOA A Collection', 'COLA-2026-00031', 'CUST-002', 'JOB-SEA-2026-0398', 'USD', '1,840.00'],
                ],
            ],
            'payables' => [
                'title' => 'Accounts Payable',
                'subtitle' => 'Vendor bill entry, shipment-linked expenses, approvals, payments, vouchers, checks, WHT, and aging.',
                'actions' => ['Vendor Bill Entry', 'Payment Processing', 'Payment Approvals', 'AP Aging'],
                'features' => ['Vendor Bill Entry', 'Shipment-linked Expenses', 'Client Tagging', 'Check Voucher Printing', 'Payment Approval', 'Bank / Cheque / Online Transfer', 'Check Printing', 'Withholding Tax', 'USD / PHP'],
                'columns' => ['Supplier', 'Bill No.', 'Shipment Job ID', 'Currency', 'WHT', 'Status'],
                'rows' => [
                    ['Pacific Fuel Services', 'BILL-7710', 'JOB-SEA-2026-0398', 'USD', '2%', 'For Payment'],
                    ['Metro Industrial Supply', 'BILL-4902', 'JOB-SEA-2026-0423', 'PHP', '5%', 'For Approval'],
                    ['Harbor Office Center', 'BILL-2420', 'JOB-SEA-2026-0365', 'PHP', '0%', 'Approved'],
                ],
            ],
            'banking' => [
                'title' => 'Banking',
                'subtitle' => 'Cashbook, multi-bank accounts, deposits, withdrawals, FX tracking, reconciliation, and daily cash position.',
                'actions' => ['Cashbook', 'Record Bank Transaction', 'Bank Reconciliation', 'Daily Cash Position'],
                'features' => ['Bank Reconciliation', 'Cashbook Management', 'Deposit Tracking', 'Withdrawal Tracking', 'Multi-bank Support', 'FX Conversion Tracking', 'Daily Cash Position Report'],
                'columns' => ['Account', 'Bank', 'Currency', 'Book Balance', 'Bank Balance', 'Status'],
                'rows' => [
                    ['BPI Operating - PHP', 'BPI', 'PHP', '945,195.00', '1,066,700.00', 'Reconciling'],
                    ['BDO Payroll - PHP', 'BDO', 'PHP', '200,000.00', '200,000.00', 'Reconciled'],
                    ['Metrobank USD Collections - USD', 'Metrobank', 'USD', '20,260.00', '20,260.00', 'Reconciled'],
                ],
            ],
            'fx' => [
                'title' => 'Multi-Currency & FX',
                'subtitle' => 'USD and PHP conversion, realized FX gains/losses, unrealized revaluation, and accounting traceability.',
                'actions' => ['Currency Conversion', 'FX Gains / Losses', 'Bank FX Tracking', 'Journal Entries'],
                'features' => ['USD / PHP', 'Currency Conversion', 'Realized FX Gain/Loss', 'Unrealized FX Gain/Loss', 'Document Rate', 'Settlement Rate', 'Month-end Revaluation', 'Shipment Job ID Traceability'],
                'columns' => ['Date', 'Type', 'Reference', 'Currency', 'Foreign Amount', 'Gain/Loss'],
                'rows' => [
                    ['2026-05-24', 'Unrealized', 'SOAA-2026-00211', 'USD', '1,840.00', '276.00 Gain'],
                    ['2026-05-23', 'Realized', 'OR-2026-00618', 'USD', '1,840.00', '184.00 Gain'],
                    ['2026-05-21', 'Realized', 'BILL-7710', 'USD', '-1,325.00', '132.50 Gain'],
                ],
            ],
            'reports' => [
                'title' => 'Financial Reports',
                'subtitle' => 'Balance sheet, profit and loss, cash flow, tax reports, KPI analytics, PDF, and Excel.',
                'actions' => ['Profit & Loss', 'Balance Sheet', 'Cash Flow', 'Export Excel'],
                'features' => ['Balance Sheet', 'Profit & Loss', 'Income Statement', 'Cash Flow Statement', 'Shipment Job ID Drilldown', 'Revenue Summary', 'Expense Summary', 'Tax Reports'],
                'columns' => ['Report', 'Period', 'Branch', 'Currency', 'Format', 'Last Run'],
                'rows' => [
                    ['Profit & Loss', 'May 2026', 'All', 'PHP', 'PDF/Excel', '2026-05-24'],
                    ['Balance Sheet', 'May 2026', 'All', 'PHP', 'PDF/Excel', '2026-05-24'],
                    ['VAT Summary', 'Q2 2026', 'Main', 'PHP', 'Excel', '2026-05-23'],
                ],
            ],
            'tax' => [
                'title' => 'Tax Management',
                'subtitle' => 'VAT computation, zero-rated export services, VAT-exempt transactions, withholding tax, summary lists, and audit trail.',
                'actions' => ['VAT Summary', 'Tax Calculator', 'Withholding Tax', 'Sales & Purchases'],
                'features' => ['12% VAT', 'Zero-rated Export Services', 'VAT-exempt', 'Expanded Withholding Tax', 'Creditable Withholding Tax', 'Sales Summary List', 'Purchase Summary List', 'Audit Trail'],
                'columns' => ['Tax Code', 'Description', 'Rate', 'Tax Type', 'GL Account', 'Status'],
                'rows' => [
                    ['VAT12', 'Output VAT / Input VAT 12%', '12%', 'VAT', 'Output VAT Payable / Input VAT', 'Active'],
                    ['VATZERO', 'Zero-rated Export Services', '0%', 'VAT', 'Zero-rated Sales', 'Active'],
                    ['VATEXEMPT', 'VAT-exempt Transactions', '0%', 'VAT', 'Exempt Sales/Purchases', 'Active'],
                    ['EWT2', 'Expanded Withholding Tax', '2%', 'Withholding', 'EWT Payable', 'Active'],
                    ['CWT2', 'Creditable Withholding Tax', '2%', 'Withholding', 'CWT Receivable', 'Active'],
                ],
            ],
            'recurring-invoices' => [
                'title' => 'Recurring Invoices',
                'subtitle' => 'Scheduled customer billings for repeat service contracts and monthly retainers.',
                'actions' => ['Create Schedule', 'Generate Due Invoices', 'Email Queue', 'Open Checkout'],
                'features' => ['Recurring Template', 'Frequency', 'Start Date', 'Remaining Cycles', 'Due Date Rule', 'Auto Email', 'Optional Auto Billing', 'No Duplicate Generation'],
                'columns' => ['Schedule No.', 'Customer', 'Cycle', 'Next Run', 'Auto Bill', 'Status'],
                'rows' => [
                    ['RI-2026-00014', 'North Harbor Trading', 'Monthly', '2026-06-01', 'Manual', 'Active'],
                    ['RI-2026-00013', 'Blue Anchor Logistics', 'Quarterly', '2026-07-01', 'Gateway Token', 'For Review'],
                    ['RI-2026-00012', 'Island Prime Foods', 'Monthly', '2026-06-05', 'None', 'Paused'],
                ],
            ],
            'online-checkout' => [
                'title' => 'Online Checkout',
                'subtitle' => 'Hosted payment links, Stripe-ready checkout flow, tokenized payment methods, webhook handling, and invoice balance updates.',
                'actions' => ['Open Public Invoice', 'Open Checkout', 'Stripe Setup', 'Webhook Monitor'],
                'features' => ['Secure Payment Link', 'Stripe Checkout / Payment Element', 'No Raw Card Storage', 'Gateway Transaction ID', 'Partial Payment', 'Refund Handling', 'Failed Payment Handling', 'Webhook Idempotency'],
                'columns' => ['Payment Link', 'Invoice', 'Gateway', 'Amount Due', 'Token Mode', 'Status'],
                'rows' => [
                    ['demo-invoice-token', 'BI-2026-01043', 'Stripe', 'PHP 21,327.07', 'Hosted Checkout', 'Ready'],
                    ['soa-payment-token', 'SOA-2026-07187', 'Stripe', 'PHP 98,500.00', 'Customer Token', 'Ready'],
                    ['usd-payment-token', 'SOAA-2026-00211', 'Stripe', 'USD 1,840.00', 'Hosted Checkout', 'Requires FX Rate'],
                ],
            ],
            'client-portal' => [
                'title' => 'Client Portal',
                'subtitle' => 'Secure customer-facing pages for invoice viewing, quote approval, payment history, documents, and payment methods.',
                'actions' => ['Portal Preview', 'Open Checkout', 'Magic Link Settings', 'Email Queue'],
                'features' => ['Magic Link Access', 'Invoice List', 'Quote Approval', 'Payment History', 'PDF Download', 'Documents', 'Payment Method Update', 'Client Activity Tracking'],
                'columns' => ['Portal User', 'Client', 'Access Type', 'Last Activity', 'Open Balance', 'Status'],
                'rows' => [
                    ['Angela Reyes', 'North Harbor Trading', 'Magic Link', 'Viewed BI-2026-01042', 'PHP 55,300.00', 'Active'],
                    ['Mark Lim', 'Blue Anchor Logistics', 'Login', 'Paid SOAA-2026-00211', 'USD 0.00', 'Active'],
                    ['Grace Tan', 'Island Prime Foods', 'Magic Link', 'Downloaded SOA', 'PHP 12,400.00', 'Pending'],
                ],
            ],
            'quotes' => [
                'title' => 'Quotes',
                'subtitle' => 'Customer estimates with approval tracking and controlled quote-to-invoice conversion.',
                'actions' => ['View Quote Register', 'Export Quotes', 'Attach Approval', 'Create Invoice'],
                'features' => ['Quote No.', 'Customer', 'Shipment Job ID', 'Validity Date', 'Currency', 'Client Approval', 'Signature Capture', 'Quote-to-Invoice'],
                'columns' => ['Quote No.', 'Customer', 'Shipment Job ID', 'Amount', 'Valid Until', 'Status'],
                'rows' => [
                    ['QT-2026-0042', 'North Harbor Trading', 'JOB-SEA-2026-0422', '98,500.00', '2026-06-15', 'Accepted'],
                    ['QT-2026-0041', 'Blue Anchor Logistics', 'JOB-SEA-2026-0398', '1,840.00 USD', '2026-06-10', 'For Review'],
                    ['QT-2026-0040', 'Island Prime Foods', 'JOB-SEA-2026-0365', '48,900.00', '2026-06-01', 'Expired'],
                ],
            ],
            'credits' => [
                'title' => 'Credits',
                'subtitle' => 'Customer credit memos, invoice adjustments, overpayment credits, and application tracking.',
                'actions' => ['Create Credit Memo', 'Apply to Invoice', 'Review Credit Balance', 'Export Credits'],
                'features' => ['Credit Memo No.', 'Customer', 'Invoice Reference', 'Shipment Job ID', 'VAT Reversal', 'Approval Workflow', 'Audit Trail'],
                'columns' => ['Credit No.', 'Customer', 'Invoice', 'Shipment Job ID', 'Amount', 'Status'],
                'rows' => [
                    ['CM-2026-00031', 'North Harbor Trading', 'BI-2026-01042', 'JOB-SEA-2026-0422', '2,500.00', 'Applied'],
                    ['CM-2026-00030', 'Island Prime Foods', 'DN-2026-00042', 'JOB-SEA-2026-0365', '1,200.00', 'For Approval'],
                    ['CM-2026-00029', 'Blue Anchor Logistics', 'SOAA-2026-00211', 'JOB-SEA-2026-0398', '80.00 USD', 'Open'],
                ],
            ],
            'projects' => [
                'title' => 'Projects',
                'subtitle' => 'Shipment jobs and client projects with billing, cost, collection, and document tracking.',
                'actions' => ['Open Shipment Job', 'Review Billing', 'Track Expenses', 'Attach Documents'],
                'features' => ['Shipment Job ID', 'Customer', 'Origin/Destination', 'Class', 'Branch', 'Revenue', 'Expense', 'Profitability'],
                'columns' => ['Shipment Job ID', 'Customer', 'Origin/Destination', 'Revenue', 'Expense', 'Margin'],
                'rows' => [
                    ['JOB-SEA-2026-0423', 'Metro Manila Forwarders', 'ROTTERDAM / MANILA', '127,900.00', '76,880.00', '51,020.00'],
                    ['JOB-SEA-2026-0422', 'North Harbor Trading', 'SINGAPORE / MANILA', '98,500.00', '43,200.00', '55,300.00'],
                    ['JOB-SEA-2026-0398', 'Blue Anchor Logistics', 'CEBU / TOKYO', '1,840.00 USD', '1,325.00 USD', '515.00 USD'],
                ],
            ],
            'tasks' => [
                'title' => 'Tasks',
                'subtitle' => 'Operational, accounting, collection, approval, and document follow-up tasks.',
                'actions' => ['Create Task', 'Assign Owner', 'Update Due Date', 'Mark Complete'],
                'features' => ['Task Owner', 'Due Date', 'Shipment Job ID', 'Priority', 'Module Link', 'Comment Thread', 'Activity Timeline'],
                'columns' => ['Task', 'Owner', 'Module', 'Due Date', 'Priority', 'Status'],
                'rows' => [
                    ['Review BI print alignment', 'Maria Santos', 'Invoices', '2026-05-24', 'High', 'Open'],
                    ['Follow up SOA payment', 'Ana Reyes', 'Payments', '2026-05-25', 'Medium', 'In Progress'],
                    ['Attach supplier bill docs', 'Leo Garcia', 'Expenses', '2026-05-26', 'Medium', 'Open'],
                ],
            ],
            'expenses' => [
                'title' => 'Expenses',
                'subtitle' => 'Shipment-linked expenses, vendor bills, tax computation, payment approval, and AP aging.',
                'actions' => ['Vendor Bill Entry', 'Payment Processing', 'Check Voucher', 'AP Aging'],
                'features' => ['Expense Category', 'Supplier', 'Shipment Job ID', 'Client Tagging', 'Input VAT', 'Withholding Tax', 'USD / PHP', 'Payment Approval'],
                'columns' => ['Expense No.', 'Supplier', 'Shipment Job ID', 'Currency', 'Amount', 'Status'],
                'rows' => [
                    ['EXP-2026-00871', 'Pacific Fuel Services', 'JOB-SEA-2026-0398', 'USD', '1,325.00', 'For Payment'],
                    ['EXP-2026-00870', 'Metro Industrial Supply', 'JOB-SEA-2026-0423', 'PHP', '127,900.00', 'For Approval'],
                    ['EXP-2026-00869', 'Harbor Office Center', 'JOB-SEA-2026-0365', 'PHP', '18,000.00', 'Approved'],
                ],
            ],
            'recurring-expenses' => [
                'title' => 'Recurring Expenses',
                'subtitle' => 'Scheduled vendor bills and repeat operating expenses with approval and tax rules.',
                'actions' => ['Create Expense Schedule', 'Generate Bills', 'Review Taxes', 'Pause Schedule'],
                'features' => ['Vendor', 'Cycle', 'Next Run Date', 'Default Expense Account', 'VAT / WHT Setup', 'Approval Required', 'Auto Voucher Draft'],
                'columns' => ['Schedule No.', 'Vendor', 'Cycle', 'Next Run', 'Amount', 'Status'],
                'rows' => [
                    ['RE-2026-00022', 'Harbor Office Center', 'Monthly', '2026-06-01', '18,000.00', 'Active'],
                    ['RE-2026-00021', 'Cloud Mail Services', 'Monthly', '2026-06-03', '4,500.00', 'Active'],
                    ['RE-2026-00020', 'Warehouse Lease', 'Quarterly', '2026-07-01', '150,000.00', 'For Review'],
                ],
            ],
            'email-reminders' => [
                'title' => 'Email & Reminders',
                'subtitle' => 'Original invoice emails, quote emails, payment receipts, overdue reminders, and delivery/activity tracking.',
                'actions' => ['Reminder Rules', 'Email Queue', 'Portal Preview', 'Audit Logs'],
                'features' => ['Invoice Sent Email', 'Quote Sent Email', 'Payment Received Email', 'Before Due Reminder', 'After Due Reminder', 'Custom Subject', 'BCC Rules', 'Activity Log'],
                'columns' => ['Template / Rule', 'Trigger', 'Audience', 'Last Run', 'Delivery State', 'Status'],
                'rows' => [
                    ['Invoice Sent', 'On send invoice', 'Client contacts', '2026-05-24 13:20', 'Queued 4 / Sent 4', 'Active'],
                    ['Payment Received', 'On successful payment', 'Client + Finance', '2026-05-24 14:02', 'Sent', 'Active'],
                    ['Overdue Reminder 1', '3 days after due date', 'Primary contact', '2026-05-24 08:00', 'No due invoices', 'Active'],
                ],
            ],
            'transactions' => [
                'title' => 'Transactions',
                'subtitle' => 'Unified ledger of invoices, payments, expenses, bank movements, FX entries, and journal postings.',
                'actions' => ['View Cashbook', 'Journal Entries', 'Bank Reconciliation', 'Export Transactions'],
                'features' => ['Transaction Date', 'Reference No.', 'Module Source', 'Shipment Job ID', 'Debit/Credit', 'Currency', 'Audit Trail', 'Posting Status'],
                'columns' => ['Date', 'Reference', 'Module', 'Shipment Job ID', 'Amount', 'Status'],
                'rows' => [
                    ['2026-05-24', 'BI-2026-01043', 'Invoices', 'JOB-SEA-2026-0423', '127,900.00', 'Draft'],
                    ['2026-05-24', 'OR-2026-00618', 'Payments', 'JOB-SEA-2026-0422', '43,200.00', 'Posted'],
                    ['2026-05-23', 'BILL-7710', 'Expenses', 'JOB-SEA-2026-0398', '1,325.00 USD', 'For Payment'],
                ],
            ],
            'documents' => [
                'title' => 'Documents',
                'subtitle' => 'Central document cabinet for invoice PDFs, SOA, receipts, shipment files, vouchers, and approvals.',
                'actions' => ['Upload Document', 'Attach to Shipment', 'Print Forms', 'Export Register'],
                'features' => ['Attachment Upload', 'Document Type', 'Reference No.', 'Shipment Job ID', 'Versioning', 'Commenting', 'Access Control', 'Audit Trail'],
                'columns' => ['Document', 'Type', 'Reference', 'Shipment Job ID', 'Uploaded By', 'Status'],
                'rows' => [
                    ['BI-2026-01042.pdf', 'Billing Invoice', 'BI-2026-01042', 'JOB-SEA-2026-0422', 'Maria Santos', 'Approved'],
                    ['SOA-2026-07187.pdf', 'Statement of Account', 'SOA-2026-07187', 'JOB-SEA-2026-0423', 'Ana Reyes', 'Ready'],
                    ['CV-2026-00418.pdf', 'Check Voucher', 'BILL-7710', 'JOB-SEA-2026-0398', 'Leo Garcia', 'For Signature'],
                ],
            ],
            'api-webhooks' => [
                'title' => 'API & Webhooks',
                'subtitle' => 'API-first integration surface for clients, invoices, quotes, payments, expenses, reports, and outgoing events.',
                'actions' => ['API Tokens', 'Outgoing Webhooks', 'Webhook Monitor', 'Security Checklist'],
                'features' => ['API Tokens', 'HTTPS Required', 'Tenant-scoped Access', 'Invoice Created Event', 'Invoice Paid Event', 'Quote Approved Event', 'Payment Webhooks', 'Retry Log'],
                'columns' => ['Integration', 'Scope / Event', 'Endpoint', 'Last Delivery', 'Retries', 'Status'],
                'rows' => [
                    ['Operations API Token', 'clients invoices payments', '/api/v1/*', '2026-05-24 12:44', '0', 'Active'],
                    ['Finance Webhook', 'invoice.paid', 'https://example.test/hooks/finance', '2026-05-24 13:18', '0', 'Active'],
                    ['Stripe Incoming', 'payment_intent.succeeded', '/webhooks/stripe', '2026-05-24 14:02', '0', 'Signature Required'],
                ],
            ],
            'security' => [
                'title' => 'Security & Tenant Controls',
                'subtitle' => 'Controls for tenant isolation, signed client links, payment tokenization, audit logs, backups, and rate limiting.',
                'actions' => ['Security Checklist', 'Audit Logs', 'Approval Matrix', 'API Tokens'],
                'features' => ['Tenant Isolation', 'Role-based Access', 'Signed Public Links', 'Payment Tokenization', 'Webhook Signature Verification', 'Audit Logs', 'Input Validation', 'Backups'],
                'columns' => ['Control', 'Implementation', 'Risk Reduced', 'Owner', 'Review Date', 'Status'],
                'rows' => [
                    ['Tenant Isolation', 'company_id scoped records and memberships', 'Cross-company data access', 'Admin', '2026-05-24', 'Required'],
                    ['Webhook Verification', 'gateway signature + event id idempotency', 'Fake or duplicate payments', 'Finance', '2026-05-24', 'Required'],
                    ['Signed Client Links', 'tokenized public invoice access', 'Unauthorized invoice views', 'IT', '2026-05-24', 'Required'],
                ],
            ],
            'settings' => [
                'title' => 'Settings',
                'subtitle' => 'Company setup, users, roles, approval matrix, numbering, branches, currencies, payment gateways, email, and audit configuration.',
                'actions' => ['Users CRUD', 'Approval Matrix', 'Number Series', 'Security Checklist'],
                'features' => ['Role-based Access', 'Multi-company', 'Multi-branch', 'Currency Setup', 'Tax Defaults', 'Document Numbering', 'Email Settings', 'Stripe Settings', 'Activity Logs'],
                'columns' => ['Setting Area', 'Description', 'Owner', 'Last Updated', 'Status', 'Action'],
                'rows' => [
                    ['Users & Roles', 'Admin, Accounting, Finance, Sales, Payables, Viewer', 'Admin', '2026-05-24', 'Active', 'Review'],
                    ['Approval Matrix', 'Payment, credit, FX, and posting approvals', 'Finance', '2026-05-24', 'Active', 'Review'],
                    ['Number Series', 'BI, SOA, OR, AP, CV, JV numbering', 'Accounting', '2026-05-23', 'Active', 'Review'],
                ],
            ],
            'users' => [
                'title' => 'Users & Role-Based Access',
                'subtitle' => 'Roles, permissions, departments, approval workflows, login logs, and audit trails.',
                'actions' => ['Invite User', 'Create Role', 'Approval Matrix', 'Audit Logs'],
                'features' => ['Admin', 'Accounting', 'Finance', 'Sales', 'Payables', 'Operations', 'Viewer', 'Department Access'],
                'columns' => ['User', 'Role', 'Department', 'Branch', 'Last Login', 'Status'],
                'rows' => [
                    ['Maria Santos', 'Admin', 'Accounting', 'Main', '2026-05-24 09:15', 'Active'],
                    ['John Cruz', 'Operations', 'Operations', 'North Branch', '2026-05-24 08:55', 'Active'],
                    ['Ana Reyes', 'Viewer', 'Management', 'All', '2026-05-23 17:20', 'Active'],
                ],
            ],
        ];
    }
}
