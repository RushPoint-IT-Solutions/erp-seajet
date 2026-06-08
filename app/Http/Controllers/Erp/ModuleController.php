<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

        $rolePermissions = [
            ['Admin', 'Full access', 'Approve all', 'Manage users, roles, company, branch, and posting controls'],
            ['Accounting', 'AR, AP, GL, Tax', 'Prepare only', 'Create invoices, journals, bills, receipts, tax summaries'],
            ['Finance', 'Banking, payments, reports', 'Level 1 approval', 'Approve payments, bank reconciliation, cash position'],
            ['Sales', 'Customers, billing requests', 'Prepare only', 'Create customer records and billing drafts'],
            ['Payables', 'Vendors and vendor bills', 'Prepare only', 'Create suppliers and vendor bill drafts'],
            ['Viewer', 'Read-only reports', 'No approval', 'Dashboard, reports, printable forms'],
        ];

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
                'title' => 'Email Official Receipt',
                'subtitle' => 'Prepare Official Receipt email with PDF attachment and delivery audit trail.',
                'primary' => ['label' => 'Official Receipt', 'route' => route('erp.official-receipt.preview'), 'icon' => 'bi-receipt'],
                'cards' => [['Queued Emails', '2'], ['Sent Today', '9'], ['PDF Attached', 'Yes'], ['Audit Trail', 'Enabled']],
                'steps' => ['Review customer email', 'Attach Official Receipt PDF', 'Add email message', 'Send and log delivery status'],
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
        $nextCustomerNumber = ((int) DB::table('customers')->max('id')) + 1;

        return view('erp.customers.create', [
            'nextCustomerCode' => 'CUST-'.str_pad($nextCustomerNumber, 3, '0', STR_PAD_LEFT),
        ]);
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
        $nextSupplierNumber = ((int) DB::table('suppliers')->max('id')) + 1;

        return view('erp.suppliers.create', [
            'nextSupplierCode' => 'SUP-'.str_pad($nextSupplierNumber, 3, '0', STR_PAD_LEFT),
        ]);
    }

    public function supplierStore()
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
            'default_ap_account' => 'nullable|string|max:255',
            'preferred_payment_method' => 'nullable|string|max:255',
            'withholding_tax' => 'nullable|string|max:50',
        ]);

        $companyId = $this->defaultCompanyId();
        $code = strtoupper(trim($data['supplier_id']));
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
            'payment_terms' => $data['payment_terms'],
            'payment_term_days' => $this->paymentTermDays($data['payment_terms']),
            'currency' => $data['currency'],
            'preferred_payment_method' => isset($data['preferred_payment_method']) ? $data['preferred_payment_method'] : null,
            'default_class_code' => $data['default_class_code'],
            'default_ap_account' => isset($data['default_ap_account']) ? $data['default_ap_account'] : null,
            'withholding_tax' => isset($data['withholding_tax']) ? $data['withholding_tax'] : '0%',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('erp.suppliers.index')->with('success', 'Vendor '.$code.' was saved to the database.');
    }

    private function vendorRows()
    {
        if (!Schema::hasTable('suppliers')) {
            return $this->suppliers;
        }

        return DB::table('suppliers')
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
            'users' => $this->users,
            'roles' => $this->rolePermissionRoles(),
            'permissionGroups' => $this->rolePermissionGroups(),
        ]);
    }

    public function userCreate()
    {
        return view('erp.users.create');
    }

    public function userEdit($user)
    {
        return view('erp.users.edit', [
            'userId' => $user,
            'users' => $this->users,
        ]);
    }

    public function userDelete($user)
    {
        return view('erp.users.delete', ['userId' => $user]);
    }

    public function userDeleteReview()
    {
        return view('erp.users.delete-review', [
            'delete' => request()->only(['user_id', 'name', 'delete_action', 'reason']),
        ]);
    }

    public function userReview()
    {
        return view('erp.users.review', [
            'user' => request()->only([
                'user_id',
                'name',
                'email',
                'role',
                'department',
                'branch',
                'status',
                'access_scope',
            ]),
        ]);
    }

    public function userStatus()
    {
        return view('erp.users.status', ['users' => $this->users]);
    }

    public function userStatusReview()
    {
        return view('erp.users.status-review', [
            'status' => request()->only([
                'user_id',
                'name',
                'current_status',
                'new_status',
                'reason',
            ]),
        ]);
    }

    public function vendorBillCreate()
    {
        $bill = request('bill') && Schema::hasTable('purchase_documents')
            ? $this->apBillByNumber(request('bill'))
            : null;

        return view('erp.payables.vendor-bill-create', [
            'suppliers' => $this->vendorRows(),
            'customers' => $this->customerSelectOptions(),
            'bill' => $bill,
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
            ['label' => 'Print check voucher', 'route' => route('erp.payables.check-voucher'), 'icon' => 'bi-printer'],
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
            'expense_amount' => 'nullable|array',
            'expense_memo' => 'nullable|array',
            'expense_customer_job' => 'nullable|array',
            'expense_billable' => 'nullable|array',
            'expense_class' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $bill = $this->saveApBill($data);

        return view('erp.payables.vendor-bill-review', [
            'bill' => $bill,
            'lines' => $this->apBillLines($bill['id']),
            'attachments' => $this->documentAttachments('purchase_document', $bill['id']),
        ]);
    }

    public function apPaymentCreate()
    {
        $selectedBill = request('bill') ? $this->apBillByNumber(request('bill')) : null;

        return view('erp.payables.payment-create', [
            'suppliers' => $this->vendorRows(),
            'openBills' => $this->openApBills(),
            'selectedBill' => $selectedBill,
            'nextPaymentNo' => $this->nextDocumentNumber('payment_voucher', 'PV-', 5, false),
        ]);
    }

    public function apPaymentReview()
    {
        $data = request()->validate([
            'payment_no' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'bill_no' => 'required|string|max:100',
            'shipment_job_id' => 'nullable|string|max:100',
            'supplier_id' => 'required|string|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'payment_method' => 'required|string|max:100',
            'bank_account' => 'nullable|string|max:255',
            'check_no' => 'nullable|string|max:100',
            'currency' => 'required|string|max:3',
            'amount' => 'required',
            'approval_status' => 'required|string|max:100',
            'attachments.*' => 'file|max:10240',
        ]);

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

    public function checkVoucherPreview($payment = null)
    {
        return view('erp.payables.check-voucher', [
            'voucher' => $this->paymentPrintData($payment),
        ]);
    }

    public function checkPrint($payment = null)
    {
        return view('erp.payables.check-print', [
            'voucher' => $this->paymentPrintData($payment),
        ]);
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

    private function saveApBill(array $data)
    {
        return DB::transaction(function () use ($data) {
            $companyId = $this->defaultCompanyId();
            $supplierId = $this->supplierIdForAp($companyId, $data);
            $documentNo = trim((string) ($data['bill_no'] ?? ''));
            if ($documentNo === '') {
                $documentNo = $this->nextDocumentNumber('vendor_bill', 'BILL-', 5);
            }

            $lineTotal = 0.0;
            foreach ((array) ($data['expense_amount'] ?? []) as $amount) {
                $lineTotal += $this->moneyToFloat($amount);
            }

            $grossAmount = $lineTotal > 0 ? $lineTotal : $this->moneyToFloat($data['gross_amount'] ?? 0);
            $whtRate = $this->moneyToFloat($data['withholding_tax_rate'] ?? 0);
            $whtAmount = $this->moneyToFloat($data['withholding_tax_amount'] ?? 0);
            if ($whtAmount <= 0 && $whtRate > 0) {
                $whtAmount = $grossAmount * ($whtRate / 100);
            }
            $netAmount = max(0, $grossAmount - $whtAmount);
            $usdToPhpRate = $this->moneyToFloat($data['exchange_rate'] ?? 1) ?: 1;
            $exchangeRate = strtoupper($data['currency']) === 'USD' ? $usdToPhpRate : 1;
            $phpAmount = strtoupper($data['currency']) === 'USD' ? $grossAmount * $usdToPhpRate : $grossAmount;

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
                'subtotal' => $grossAmount,
                'tax_total' => 0,
                'withholding_tax_rate' => $whtRate,
                'withholding_tax_amount' => $whtAmount,
                'grand_total' => $netAmount,
                'net_amount' => $netAmount,
                'paid_total' => 0,
                'status' => 'for_payment',
                'approval_status' => 'For Approval',
                'remarks' => $data['memo'] ?? null,
                'memo' => $data['memo'] ?? null,
                'updated_at' => now(),
            ]);

            $existingId = DB::table('purchase_documents')
                ->where('company_id', $companyId)
                ->where('document_no', $documentNo)
                ->value('id');

            if ($existingId) {
                DB::table('purchase_documents')->where('id', $existingId)->update($payload);
                $billId = $existingId;
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

            DB::table($table)->insert($this->tablePayload($table, [
                'purchase_document_id' => $billId,
                'account_name' => $account,
                'tax_category' => $taxes[$index] ?? null,
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

        return DB::table('suppliers')->insertGetId($this->tablePayload('suppliers', [
            'company_id' => $companyId,
            'code' => $supplierCode ?: 'SUP-'.date('YmdHis'),
            'name' => $supplierName ?: $supplierCode,
            'address' => $data['supplier_address'] ?? null,
            'payment_terms' => $data['terms'] ?? 'COD',
            'payment_term_days' => $this->paymentTermDays($data['terms'] ?? 'COD'),
            'currency' => strtoupper($data['currency'] ?? 'PHP'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
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
            $paymentNo = trim((string) ($data['payment_no'] ?? ''));
            if ($paymentNo === '') {
                $paymentNo = $this->nextDocumentNumber('payment_voucher', 'PV-', 5);
            }
            $voucherNo = $this->nextDocumentNumber('check_voucher', 'CV-', 5);

            $paymentId = DB::table('payments')->insertGetId($this->tablePayload('payments', [
                'company_id' => $companyId,
                'supplier_id' => $supplierId,
                'shipment_job_id' => $data['shipment_job_id'] ?? ($bill->shipment_job_id ?? null),
                'bill_no' => $data['bill_no'],
                'payment_no' => $paymentNo,
                'voucher_no' => $voucherNo,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'bank_account' => $data['bank_account'] ?? null,
                'check_no' => $data['check_no'] ?? null,
                'currency' => strtoupper($data['currency']),
                'exchange_rate' => 1,
                'amount' => $amount,
                'status' => strtolower(str_replace(' ', '_', $data['approval_status'])),
                'approval_status' => $data['approval_status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $isReleasedPayment = in_array($data['approval_status'], ['Approved', 'Released']);

            if ($bill && Schema::hasTable('payment_applications')) {
                DB::table('payment_applications')->insert($this->tablePayload('payment_applications', [
                    'payment_id' => $paymentId,
                    'purchase_document_id' => $bill->id,
                    'amount_applied' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            if ($bill && $isReleasedPayment) {
                $paidTotal = min((float) $bill->grand_total, (float) $bill->paid_total + $amount);
                DB::table('purchase_documents')->where('id', $bill->id)->update([
                    'paid_total' => $paidTotal,
                    'status' => $paidTotal >= (float) $bill->grand_total ? 'paid' : 'partially_paid',
                    'approval_status' => $data['approval_status'],
                    'updated_at' => now(),
                ]);
            }

            $this->storeDocumentAttachments('payment', $paymentId, request()->file('attachments', []));

            return $this->paymentArrayById($paymentId);
        });
    }

    private function apPaymentRows()
    {
        if (!Schema::hasTable('payments')) {
            return [];
        }

        return DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->select('payments.*', 'suppliers.name as supplier_name')
            ->orderBy('payments.payment_date', 'desc')
            ->orderBy('payments.id', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_no' => $payment->payment_no,
                    'supplier' => $payment->supplier_name ?: '-',
                    'shipment_job_id' => $payment->shipment_job_id ?: '-',
                    'currency' => $payment->currency ?: 'PHP',
                    'amount' => number_format((float) $payment->amount, 2),
                    'status' => $payment->approval_status ?: ucwords(str_replace('_', ' ', $payment->status)),
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
            ->select('payments.*', 'suppliers.name as supplier_name');

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

        return [
            'id' => $payment->id,
            'voucher_no' => $payment->voucher_no ?: 'CV-'.$payment->id,
            'payment_no' => $payment->payment_no,
            'company' => DB::table('companies')->where('id', $payment->company_id)->value('name') ?: 'SeaJet Holdings Inc.',
            'supplier' => $payment->supplier_name ?: '-',
            'shipment_job_id' => $payment->shipment_job_id ?: '-',
            'payment_method' => $payment->payment_method,
            'bank_account' => $payment->bank_account ?: '-',
            'check_no' => $payment->check_no ?: '-',
            'bill_no' => $payment->bill_no,
            'description' => $bill && $bill->memo ? $bill->memo : 'Shipment-linked vendor expense',
            'currency' => $payment->currency ?: 'PHP',
            'gross' => $gross,
            'withholding' => $wht,
            'net' => $net,
            'amount_words' => $this->amountToWords($net, $payment->currency ?: 'PHP'),
        ];
    }

    private function emptyPaymentPrintData()
    {
        return [
            'id' => null,
            'voucher_no' => 'No saved payment',
            'payment_no' => '-',
            'company' => 'SeaJet Holdings Inc.',
            'supplier' => '-',
            'shipment_job_id' => '-',
            'payment_method' => '-',
            'bank_account' => '-',
            'check_no' => '-',
            'bill_no' => '-',
            'description' => 'No AP payment has been saved yet.',
            'currency' => 'PHP',
            'gross' => 0,
            'withholding' => 0,
            'net' => 0,
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
            ->where('purchase_documents.document_no', $documentNo)
            ->first();
    }

    private function apBillArray($bill)
    {
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

        return DB::table($table)->where('purchase_document_id', $billId)->get();
    }

    private function paymentArrayById($id)
    {
        $payment = DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->select('payments.*', 'suppliers.code as supplier_code', 'suppliers.name as supplier_name')
            ->where('payments.id', $id)
            ->first();

        return [
            'id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'voucher_no' => $payment->voucher_no ?? null,
            'supplier_id' => $payment->supplier_code ?? $payment->supplier_id,
            'supplier_name' => $payment->supplier_name,
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
        if ($increment) {
            DB::table('number_series')->where('id', $series->id)->update([
                'current_number' => $next,
                'updated_at' => now(),
            ]);
        }

        return ($series->prefix ?: $prefix).str_pad($next, (int) ($series->padding ?: $padding), '0', STR_PAD_LEFT);
    }

    private function tablePayload($table, array $payload)
    {
        return array_filter($payload, function ($value, $column) use ($table) {
            return Schema::hasColumn($table, $column);
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function amountToWords($amount, $currency)
    {
        $whole = (int) floor((float) $amount);
        $cents = (int) round(((float) $amount - $whole) * 100);
        $major = strtoupper($currency) === 'USD' ? 'Dollars' : 'Pesos';
        $words = $this->numberToWords($whole).' '.$major;

        if ($cents > 0) {
            $words .= ' and '.$this->numberToWords($cents).'/100';
        }

        return $words.' Only';
    }

    private function numberToWords($number)
    {
        $number = (int) $number;
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $scales = ['', 'Thousand', 'Million', 'Billion'];
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
        $transactions = [
            ['2026-05-24', 'BPI Operating - PHP', 'Deposit', 'OR-2026-00618', 'PHP', 43200, 0, 1066700, 'Matched'],
            ['2026-05-24', 'BPI Operating - PHP', 'Withdrawal', 'PV-2026-00313', 'PHP', 0, 121505, 945195, 'For Reconciliation'],
            ['2026-05-23', 'Metrobank USD Collections - USD', 'Deposit', 'SOAA-2026-00211', 'USD', 1840, 0, 20260, 'Matched'],
            ['2026-05-23', 'BDO Payroll - PHP', 'Withdrawal', 'PAY-2026-00112', 'PHP', 0, 76000, 200000, 'Cleared'],
        ];

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
        $rows = [
            ['2026-05-24', 'OR-2026-00618', 'Deposit', 'PHP', '43,200.00', 'Books and bank matched'],
            ['2026-05-24', 'PV-2026-00313', 'Withdrawal', 'PHP', '121,505.00', 'Outstanding check'],
            ['2026-05-23', 'SOAA-2026-00211', 'Deposit', 'USD', '1,840.00', 'FX deposit matched'],
        ];

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
        $summary = [
            ['PHP', 'Opening Cash', 1219500],
            ['PHP', 'Deposits', 43200],
            ['PHP', 'Withdrawals', -197505],
            ['USD', 'Opening Cash', 18420],
            ['USD', 'Deposits', 1840],
            ['USD', 'Withdrawals', 0],
        ];

        return view('erp.banking.daily-cash-position', compact('accounts', 'summary'));
    }

    private function bankAccounts()
    {
        return [
            ['code' => 'BPI-PHP-001', 'name' => 'BPI Operating - PHP', 'bank' => 'BPI', 'account_no' => '0012-3456-78', 'currency' => 'PHP', 'book_balance' => '945,195.00', 'bank_balance' => '1,066,700.00'],
            ['code' => 'MBTC-USD-001', 'name' => 'Metrobank USD Collections - USD', 'bank' => 'Metrobank', 'account_no' => '9988-1200-45', 'currency' => 'USD', 'book_balance' => '20,260.00', 'bank_balance' => '20,260.00'],
            ['code' => 'BDO-PHP-002', 'name' => 'BDO Payroll - PHP', 'bank' => 'BDO', 'account_no' => '7755-3001-22', 'currency' => 'PHP', 'book_balance' => '200,000.00', 'bank_balance' => '200,000.00'],
        ];
    }

    public function chartOfAccounts()
    {
        $accounts = [
            ['1000', 'Cash on Hand', 'Assets', '-', 'Debit', '245,000.00', 'Active'],
            ['1010', 'Bank - Operating Account', 'Assets', '-', 'Debit', '1,023,500.00', 'Active'],
            ['1200', 'Accounts Receivable', 'Assets', '-', 'Debit', '238,700.00', 'Active'],
            ['1210', 'A/Trade-Billing Invoice', 'Assets', '1200', 'Debit', '98,500.00', 'Active'],
            ['1220', 'A/R Reimbursable Charges', 'Assets', '1200', 'Debit', '12,800.00', 'Active'],
            ['1230', 'A/R Trade-SOA A', 'Assets', '1200', 'Debit', '106,720.00', 'Active'],
            ['1240', 'A/R Trade-Debit Note', 'Assets', '1200', 'Debit', '48,900.00', 'Active'],
            ['2100', 'Accounts Payable', 'Liabilities', '-', 'Credit', '174,250.00', 'Active'],
            ['2200', 'Output VAT Payable', 'Liabilities', '-', 'Credit', '41,280.00', 'Active'],
            ['4000', 'Service Revenue', 'Revenue', '-', 'Credit', '845,230.00', 'Active'],
            ['5100', 'Operating Expenses', 'Expenses', '-', 'Debit', '142,110.00', 'Active'],
        ];

        return view('erp.accounting.chart-of-accounts', compact('accounts'));
    }

    public function journalEntries()
    {
        return view('erp.accounting.journal-entries');
    }

    public function journalEntryReview()
    {
        return view('erp.accounting.journal-review', [
            'journal' => request()->only([
                'journal_no',
                'journal_date',
                'shipment_job_id',
                'reference_no',
                'memo',
                'debit_account',
                'credit_account',
                'amount',
            ]),
        ]);
    }

    public function balanceSheet()
    {
        return $this->statement('Balance Sheet', [
            ['Assets', 'Cash and Bank', 1268500],
            ['Assets', 'Accounts Receivable', 238700],
            ['Liabilities', 'Accounts Payable', -174250],
            ['Liabilities', 'Output VAT Payable', -41280],
            ['Equity', 'Owner Equity', -1291670],
        ]);
    }

    public function profitLoss()
    {
        return $this->statement('Profit and Loss', [
            ['Revenue', 'Service Revenue', 845230],
            ['Cost', 'Cost of Services', -240300],
            ['Expense', 'Operating Expenses', -142110],
        ]);
    }

    public function incomeStatement()
    {
        return $this->statement('Income Statement', [
            ['Revenue', 'Service Revenue', 845230],
            ['Less', 'Direct Costs', -240300],
            ['Less', 'Operating Expenses', -142110],
            ['Net Income', 'Net Income Before Tax', 462820],
        ]);
    }

    public function cashFlow()
    {
        return $this->statement('Cash Flow Statement', [
            ['Operating', 'Collections from Customers', 328900],
            ['Operating', 'Payments to Suppliers', -174250],
            ['Financing', 'Owner Contributions', 250000],
            ['Ending Cash', 'Cash and Bank Balance', 1268500],
        ]);
    }

    private function statement($title, $rows)
    {
        return view('erp.statements.show', compact('title', 'rows'));
    }

    public function vatSummary()
    {
        $sales = $this->salesTaxRows();
        $purchases = $this->purchaseTaxRows();

        return view('erp.tax.vat-summary', [
            'sales' => $sales,
            'purchases' => $purchases,
            'outputVat' => 41280,
            'inputVat' => 15348,
            'vatPayable' => 25932,
        ]);
    }

    public function withholdingTax()
    {
        $rows = [
            ['2026-05-24', 'BILL-4902', 'Expanded Withholding Tax', 'Metro Industrial Supply', 'JOB-SEA-2026-0423', 'PHP', 127900, '5%', 6395, 'For Remittance'],
            ['2026-05-23', 'BI-2026-01042', 'Creditable Withholding Tax', 'North Harbor Trading', 'JOB-SEA-2026-0422', 'PHP', 98500, '2%', 1970, 'Creditable'],
            ['2026-05-21', 'BILL-7710', 'Expanded Withholding Tax', 'Pacific Fuel Services', 'JOB-SEA-2026-0398', 'USD', 1325, '2%', 26.50, 'For Remittance'],
        ];

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

    private function salesTaxRows()
    {
        return [
            ['2026-05-24', 'BI-2026-01042', 'North Harbor Trading', 'JOB-SEA-2026-0422', '12% VAT', 'PHP', 87946.43, 10553.57, 1970.00],
            ['2026-05-23', 'SOAA-2026-00211', 'Blue Anchor Logistics', 'JOB-SEA-2026-0398', 'Zero-rated export services', 'USD', 1840.00, 0.00, 0.00],
            ['2026-05-22', 'DN-2026-00042', 'Island Prime Foods', 'JOB-SEA-2026-0365', 'VAT-exempt', 'PHP', 48900.00, 0.00, 0.00],
        ];
    }

    private function purchaseTaxRows()
    {
        return [
            ['2026-05-24', 'BILL-4902', 'Metro Industrial Supply', 'JOB-SEA-2026-0423', '12% VAT', 'PHP', 114196.43, 13703.57, 6395.00],
            ['2026-05-21', 'BILL-7710', 'Pacific Fuel Services', 'JOB-SEA-2026-0398', '12% VAT', 'USD', 1183.04, 141.96, 26.50],
            ['2026-05-20', 'BILL-2420', 'Harbor Office Center', 'JOB-SEA-2026-0365', 'VAT-exempt', 'PHP', 18000.00, 0.00, 0.00],
        ];
    }

    public function invoiceCreate()
    {
        return view('erp.invoices.create', [
            'customers' => $this->customerSelectOptions(),
            'invoiceTemplates' => $this->invoiceTemplates(),
        ]);
    }

    public function invoiceIndex()
    {
        $invoices = $this->billingRows();
        $totalAmount = 0.0;
        $postedAmount = 0.0;
        $postedCount = 0;

        foreach ($invoices as $invoice) {
            $amount = $this->moneyToFloat($invoice[4]);
            $status = strtolower($invoice[5]);

            $totalAmount += $amount;

            if ($status === 'posted') {
                $postedCount++;
                $postedAmount += $amount;
            }
        }

        return view('erp.invoices.index', [
            'invoices' => $invoices,
            'stats' => [
                ['label' => 'Invoices', 'value' => count($invoices), 'icon' => 'bi-receipt', 'tone' => 'primary'],
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
        ]);
    }

    public function billingIndex()
    {
        return view('erp.billings.index', ['billings' => $this->billingRows()]);
    }

    public function billingEdit($invoice)
    {
        return view('erp.invoices.create', [
            'isEdit' => true,
            'editingInvoice' => $this->billingPrintInvoiceDataFromReference($invoice),
            'customers' => $this->customerSelectOptions(),
            'invoiceTemplates' => $this->invoiceTemplates(),
        ]);
    }

    public function billingDelete($invoice)
    {
        return view('erp.billings.delete', [
            'invoiceNo' => $invoice,
            'billing' => $this->billingPrintInvoiceDataFromReference($invoice),
        ]);
    }

    public function billingDeleteReview()
    {
        $data = request()->only(['invoice_no', 'customer', 'shipment_job_id', 'delete_action', 'reason']);
        $invoiceNo = trim((string) ($data['invoice_no'] ?? ''));
        $newStatus = stripos((string) ($data['delete_action'] ?? ''), 'cancel') !== false ? 'cancelled' : 'voided';

        if ($invoiceNo !== '' && Schema::hasTable('sales_documents')) {
            DB::table('sales_documents')
                ->where('document_no', $invoiceNo)
                ->update([
                    'status' => $newStatus,
                    'remarks' => trim((string) ($data['reason'] ?? '')),
                    'updated_at' => now(),
                ]);
        }

        return view('erp.billings.delete-review', [
            'delete' => array_merge($data, ['new_status' => $newStatus]),
        ]);
    }

    public function billingStatus()
    {
        return view('erp.billings.status', ['billings' => $this->billingRows()]);
    }

    public function billingStatusReview()
    {
        return view('erp.billings.status-review', [
            'status' => request()->only([
                'invoice_no',
                'customer',
                'shipment_job_id',
                'current_status',
                'new_status',
                'remarks',
            ]),
        ]);
    }

    public function billingInvoicePrintLayout()
    {
        return view('erp.billings.print-layout-invoice', [
            'positions' => $this->invoicePrintPositions(),
            'settings' => $this->invoicePrintSettings(),
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
                'particulars' => [112, 250],
                'amounts' => [637, 250],
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
            if (in_array($key, ['particulars', 'payment_note'])) {
                $defaultWidth = $key === 'payment_note' ? 510 : 455;
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
        $hidden = request()->has('hidden_fields_present') ? (array) request('hidden_field', []) : ($saved['hidden_fields'] ?? []);

        return array_values(array_unique(array_filter(array_map(function ($field) {
            return preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $field);
        }, is_array($hidden) ? $hidden : []))));
    }

    private function soaPrintSamples($type)
    {
        if ($type === 'soa_a') {
            return [
                'serial' => ['label' => 'A Number', 'value' => '0907', 'class' => 'serial'],
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
            'payment_note' => ['label' => 'Payment Note', 'value' => "PLEASE PAY THIS REIMBURSEMENT CHARGES TOGETHER WITH INVOICE NUMBER 0001031\nREIMBURSEMENT CHARGES PHP 753,495.10", 'class' => ''],
            'total_reimbursement' => ['label' => 'Total Amount Due', 'value' => '774,822.17', 'class' => 'amount'],
            'prepared_by' => ['label' => 'Prepared By', 'value' => 'MABEL PLATA', 'class' => ''],
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

        return [
            'paper_size' => $paper,
            'paper_width' => $width,
            'paper_height' => $height,
            'page_css' => $paper === 'custom' ? ($width.'px '.$height.'px') : $presets[$paper]['page_css'],
            'template_url' => $templateUrl,
            'print_template' => $printTemplate,
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
            if ($key === 'line_description') {
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
            : ($saved['hidden_fields'] ?? []);

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
                'sales_documents.document_no',
                'sales_documents.job_shipment_id',
                'sales_documents.shipment_reference',
                'sales_documents.currency',
                'sales_documents.grand_total',
                'sales_documents.status',
                'sales_documents.document_date',
                'customers.name as customer_name'
            )
            ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->orderBy('sales_documents.document_date', 'desc')
            ->orderBy('sales_documents.id', 'desc')
            ->get()
            ->map(function ($invoice) {
                return [
                    $invoice->document_no,
                    $invoice->customer_name ?: 'Guest / Non-tax Client',
                    $invoice->job_shipment_id ?: $invoice->shipment_reference,
                    $invoice->currency ?: 'PHP',
                    number_format((float) $invoice->grand_total, 2),
                    ucwords(str_replace('_', ' ', $invoice->status ?: 'draft')),
                    $invoice->document_date,
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
                    'terms' => $customer->payment_terms,
                    'currency' => $customer->currency ?: 'PHP',
                    'class' => $customer->default_class_code ?: 'MANILA',
                    'vat_classification' => $taxClassification,
                ];
            })
            ->all();
    }

    private function invoiceAgingRows()
    {
        if (!Schema::hasTable('sales_documents')) {
            return [];
        }

        return DB::table('sales_documents')
            ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
            ->select(
                'sales_documents.document_no',
                'sales_documents.currency',
                'sales_documents.grand_total',
                'sales_documents.paid_total',
                'sales_documents.due_date',
                'customers.name as customer_name'
            )
            ->whereNotIn('sales_documents.status', ['cancelled', 'voided'])
            ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'])
            ->orderBy('sales_documents.due_date')
            ->get()
            ->map(function ($invoice) {
                $balance = max(0, (float) $invoice->grand_total - (float) $invoice->paid_total);
                $daysPastDue = $invoice->due_date ? now()->diffInDays(\Carbon\Carbon::parse($invoice->due_date), false) * -1 : 0;
                $current = $daysPastDue <= 0 ? $balance : 0;
                $thirty = $daysPastDue > 0 && $daysPastDue <= 30 ? $balance : 0;
                $sixty = $daysPastDue > 30 && $daysPastDue <= 60 ? $balance : 0;
                $ninety = $daysPastDue > 60 ? $balance : 0;

                return [
                    $invoice->customer_name ?: 'Guest / Non-tax Client',
                    $invoice->document_no,
                    $invoice->currency ?: 'PHP',
                    number_format($current, 2),
                    number_format($thirty, 2),
                    number_format($sixty, 2),
                    number_format($ninety, 2),
                ];
            })
            ->all();
    }

    private function invoiceAgingBuckets()
    {
        $rows = $this->invoiceAgingRows();
        $totals = ['Current' => 0, '30 Days' => 0, '60 Days' => 0, '90+ Days' => 0];

        foreach ($rows as $row) {
            $totals['Current'] += $this->moneyToFloat($row[3]);
            $totals['30 Days'] += $this->moneyToFloat($row[4]);
            $totals['60 Days'] += $this->moneyToFloat($row[5]);
            $totals['90+ Days'] += $this->moneyToFloat($row[6]);
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
            $status = $paidTotal > 0 && $paidTotal >= $totals['soa_total'] ? 'paid' : 'posted';

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
                'subtotal' => $totals['vatable_sales'],
                'vatable_amount' => $totals['vatable_sales'],
                'zero_rated_amount' => ($data['vat_classification'] ?? '') === 'zero-rated' ? $totals['vatable_sales'] : 0,
                'vat_exempt_amount' => ($data['vat_classification'] ?? '') === 'exempt' ? $totals['vatable_sales'] : 0,
                'tax_total' => $totals['vat_amount'],
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
                $existing = DB::table('sales_documents')->where('document_no', $originalDocumentNo)->first();
            }
            if (!$existing) {
                $existing = DB::table('sales_documents')->where('document_no', $documentNo)->first();
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
        $taxCategories = (array) ($data['tax_category'] ?? []);
        $count = max(count($items), count($descriptions), count($quantities), count($unitPrices));

        for ($index = 0; $index < $count; $index++) {
            $description = trim((string) ($descriptions[$index] ?? ''));
            $item = trim((string) ($items[$index] ?? ''));
            $quantity = $this->moneyToFloat($quantities[$index] ?? 0);
            $unitPrice = $this->moneyToFloat($unitPrices[$index] ?? 0);

            if ($description === '' && $item === '' && $quantity === 0.0 && $unitPrice === 0.0) {
                continue;
            }

            DB::table('sales_document_lines')->insert([
                'sales_document_id' => $salesDocumentId,
                'item_name' => $item !== '' ? $item : null,
                'description' => $description !== '' ? $description : $item,
                'quantity' => $quantity ?: 1,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_code_id' => $this->taxCodeId($taxCategories[$index] ?? null),
                'line_total' => $unitPrice * ($quantity ?: 1),
                'class_code' => $classes[$index] ?? null,
                'tax_category' => $taxCategories[$index] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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

                if ($reference !== '') {
                    $query->where('sales_documents.document_no', $reference);
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
                        return property_exists($invoice, $key) ? $invoice->$key : $default;
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
                        'mawb_no' => $column('mawb_no', $fallback['mawb_no'] ?? ''),
                        'hawb_no' => $column('hawb_no', $fallback['hawb_no'] ?? ''),
                        'date_shipped' => $column('date_shipped', $invoice->document_date ?? $fallback['date_shipped'] ?? ''),
                        'origin_dest' => $column('origin_dest', $fallback['origin_dest'] ?? ''),
                        'commodity' => $column('commodity', $fallback['commodity'] ?? ''),
                        'description' => $invoice->remarks ?? $fallback['description'] ?? '',
                        'customer_message' => $column('customer_message', $fallback['customer_message'] ?? ''),
                        'memo' => $column('memo', $fallback['memo'] ?? ''),
                        'job_shipment_id' => $column('job_shipment_id', $column('shipment_job_id', $fallback['job_shipment_id'] ?? '')),
                        'vatable_sales' => number_format($subtotal, 2, '.', ''),
                        'vat_amount' => number_format($taxTotal, 2, '.', ''),
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
                            $data['line_class'] = $lines->map(function ($line) use ($invoice) {
                                return (property_exists($line, 'class_code') && $line->class_code) ? $line->class_code : ($invoice->class_code ?? '');
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
        $resolved['vat_amount'] = number_format($totals['vat_amount'], 2, '.', '');
        $resolved['billing_total'] = number_format($totals['billing_total'], 2, '.', '');
        $resolved['reimbursement_total'] = number_format($totals['reimbursement_total'], 2, '.', '');
        $resolved['soa_total'] = number_format($totals['soa_total'], 2, '.', '');

        return $resolved;
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
        return [
            ['key' => 'administrator', 'label' => 'Administrator', 'color' => '#f15b45', 'locked' => true, 'note' => 'Full ERP access'],
            ['key' => 'accounting', 'label' => 'Accounting Staff', 'color' => '#2f5597', 'locked' => false, 'note' => 'Billing, payments, reports'],
            ['key' => 'finance', 'label' => 'Finance Staff', 'color' => '#00a99d', 'locked' => false, 'note' => 'Collections and cash controls'],
        ];
    }

    private function rolePermissionGroups()
    {
        return [
            'Core Modules' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'permissions' => ['view']],
                ['key' => 'clients', 'label' => 'Clients', 'permissions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'invoices', 'label' => 'Invoices', 'permissions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'payments', 'label' => 'Payments', 'permissions' => ['view', 'create', 'edit', 'delete']],
            ],
            'Finance & Operations' => [
                ['key' => 'vendors', 'label' => 'Vendors', 'permissions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'expenses', 'label' => 'Accounts Payable (AP)', 'permissions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'reports', 'label' => 'Reports', 'permissions' => ['view', 'create', 'edit', 'delete']],
            ],
            'Administration' => [
                ['key' => 'user_access_control', 'label' => 'User Access Control', 'permissions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'finance_settings', 'label' => 'Finance Settings', 'permissions' => ['view', 'create', 'edit', 'delete']],
            ],
        ];
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
            'mawb_no' => $value('mawb_no'),
            'hawb_no' => $value('hawb_no'),
            'date_shipped' => $value('date_shipped'),
            'origin_dest' => $value('origin_dest'),
            'commodity' => $value('commodity'),
            'description' => $value('description'),
            'customer_message' => $value('customer_message'),
            'memo' => $value('memo'),
            'item' => (array) $value('item', []),
            'item_description' => (array) $value('item_description', []),
            'qty' => (array) $value('qty', []),
            'unit_price' => (array) $value('unit_price', []),
            'tax_category' => (array) $value('tax_category', []),
            'line_amount' => (array) $value('line_amount', []),
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
        $reimbursementTotal = 0.0;
        $vatAmount = 0.0;

        foreach (($data['line_amount'] ?? []) as $index => $amount) {
            $lineAmount = $this->moneyToFloat($amount);
            $item = strtolower((string) ($data['item'][$index] ?? ''));
            $description = strtolower((string) ($data['item_description'][$index] ?? ''));
            $isReimbursement = strpos($item, 'reimburs') !== false || strpos($description, 'reimburs') !== false;

            if ($isReimbursement) {
                $reimbursementTotal += $lineAmount;
                continue;
            }

            $billingSubtotal += $lineAmount;
            if (($data['tax_category'][$index] ?? '') === '12%') {
                $vatAmount += $lineAmount * 0.12;
            }
        }

        return [
            'vatable_sales' => $billingSubtotal,
            'vat_amount' => $vatAmount,
            'billing_total' => $billingSubtotal + $vatAmount,
            'reimbursement_total' => $reimbursementTotal,
            'soa_total' => $billingSubtotal + $vatAmount + $reimbursementTotal,
        ];
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

        $billingTotal = $this->moneyToFloat($read('billing_total', '21327.07'));
        $reimbursementTotal = $this->moneyToFloat($read('reimbursement_total', '753495.10'));
        $soaTotal = request()->has('soa_total') || array_key_exists('soa_total', $invoiceData)
            ? $this->moneyToFloat($read('soa_total'))
            : $billingTotal + $reimbursementTotal;
        $items = (array) $read('item', []);
        $descriptions = (array) $read('item_description', []);
        $quantities = (array) $read('qty', []);
        $unitPrices = (array) $read('unit_price', []);
        $lineAmounts = (array) $read('line_amount', []);
        $taxCategories = (array) $read('tax_category', []);
        $lineCount = max(count($items), count($descriptions), count($quantities), count($unitPrices), count($lineAmounts), 1);
        $billingLineIndexes = [];
        $reimbursementLineIndexes = [];
        $zeroRatedSales = 0.0;
        $vatExemptSales = 0.0;

        for ($index = 0; $index < $lineCount; $index++) {
            $item = strtolower((string) $lineValue($items, $index));
            $description = strtolower((string) $lineValue($descriptions, $index));
            $isReimbursement = strpos($item, 'reimburs') !== false || strpos($description, 'reimburs') !== false;
            $taxCategory = strtolower((string) ($taxCategories[$index] ?? ''));
            $amount = $lineValue($lineAmounts, $index);

            if ($isReimbursement) {
                $reimbursementLineIndexes[] = $index;
            } elseif (trim((string) $lineValue($descriptions, $index)) !== '' || $amount !== '') {
                $billingLineIndexes[] = $index;
            }

            if (strpos($taxCategory, 'zero') !== false) {
                $zeroRatedSales += $this->moneyToFloat($amount);
            }
            if (strpos($taxCategory, 'exempt') !== false) {
                $vatExemptSales += $this->moneyToFloat($amount);
            }
        }

        if (empty($billingLineIndexes)) {
            $billingLineIndexes[] = 0;
        }

        $billingLineIndex = $billingLineIndexes[0];
        $billingDescriptions = [];
        $billingAmounts = [];
        foreach ($billingLineIndexes as $index) {
            $description = trim((string) $lineValue($descriptions, $index));
            $amount = $this->moneyToFloat($lineValue($lineAmounts, $index));
            if ($description !== '') {
                $billingDescriptions[] = $description;
            }
            if ($amount !== 0.0) {
                $billingAmounts[] = number_format($amount, 2);
            }
        }

        $lineDescription = trim(implode("\n", $billingDescriptions));
        if ($lineDescription === '') {
            $lineDescription = trim((string) $read('description'));
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

        return [
            'customer' => $read('customer', 'PHILUSA CORPORATION'),
            'invoice_no' => $read('invoice_no', $read('invoice', '0001031')),
            'invoice_date' => $this->displayPrintDate($read('invoice_date'), '05/19/2026'),
            'soa_no' => $read('soa_no', '7187'),
            'address' => $read('invoice_to', "28 SHAW BLVD, KAPITOLYO 1603\nCITY OF PASIG"),
            'tin' => $read('tin', '000-281-014-00000'),
            'currency' => $read('currency', 'PHP'),
            'mawb' => $read('mawb_no', 'OOLU32322350120'),
            'hawb' => $read('hawb_no', '2GJXM412-G1'),
            'date_shipped' => $this->displayPrintDate($read('date_shipped'), '05/19/2026'),
            'origin_dest' => $read('origin_dest', 'ROTTERDAM/MNL'),
            'commodity' => $read('commodity', "3 X 40'HC"),
            'line_description' => $lineDescription !== '' ? $lineDescription : "OCEAN FREIGHT (5% OF FREIGHT CHARGES)\n(2,100 x 3 CNTRS = \$6,300 x 61.55 = 387,765 x 5%)\n\nSHIPPER: LEMOINE HOLLAND B.V.",
            'line_qty' => $lineValue($quantities, $billingLineIndex, $firstLineValue('qty', '1')),
            'line_unit_price' => $this->moneyToFloat($lineValue($unitPrices, $billingLineIndex, $firstLineValue('unit_price', '19388.25'))),
            'line_amount' => $this->moneyToFloat($lineValue($lineAmounts, $billingLineIndex, $firstLineValue('line_amount', '19388.25'))),
            'billing_amounts' => implode("\n", $billingAmounts),
            'soa_particulars' => implode("\n", $soaParticulars),
            'soa_amounts' => implode("\n", $soaAmounts),
            'vatable_sales' => $this->moneyToFloat($read('vatable_sales', '19388.25')),
            'vat_amount' => $this->moneyToFloat($read('vat_amount', '2326.59')),
            'zero_rated_sales' => request()->has('zero_rated_sales') || array_key_exists('zero_rated_sales', $invoiceData) ? $this->moneyToFloat($read('zero_rated_sales')) : $zeroRatedSales,
            'vat_exempt_sales' => request()->has('vat_exempt_sales') || array_key_exists('vat_exempt_sales', $invoiceData) ? $this->moneyToFloat($read('vat_exempt_sales')) : $vatExemptSales,
            'discount' => $this->moneyToFloat($read('discount', '0')),
            'withholding_tax' => $this->moneyToFloat($read('withholding_tax', '0')),
            'prepared_by' => $read('prepared_by', ''),
            'billing_total' => $billingTotal,
            'reimbursement_total' => $reimbursementTotal,
            'soa_total' => $soaTotal,
            'memo' => $read('memo', ''),
            'customer_message' => $read('customer_message', ''),
            'description' => $read('description', ''),
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
            'tax_category',
            'line_amount',
            'original_invoice_no',
        ]);

        $invoiceNo = trim((string) ($data['invoice_no'] ?? ''));
        $transactionNo = trim((string) ($data['transaction_no'] ?? ''));
        $data['invoice_no'] = $invoiceNo !== '' ? $invoiceNo : $transactionNo;
        $data['transaction_no'] = $data['invoice_no'];
        $originalInvoiceNo = trim((string) ($data['original_invoice_no'] ?? ''));

        if ($data['invoice_no'] === '') {
            return redirect()->back()->withInput()->withErrors(['invoice_no' => 'Invoice number is required.']);
        }

        $duplicateQuery = DB::table('sales_documents')->where('document_no', $data['invoice_no']);
        if ($originalInvoiceNo !== '') {
            $duplicateQuery->where('document_no', '<>', $originalInvoiceNo);
        }
        if ($duplicateQuery->exists()) {
            return redirect()->back()->withInput()->withErrors(['invoice_no' => 'This invoice number already exists.']);
        }

        $totals = $this->billingTotals($data);
        $data['invoice_amount'] = number_format($totals['billing_total'], 2);
        $data['amount'] = number_format($totals['soa_total'], 2);
        $data['reimbursement_total'] = number_format($totals['reimbursement_total'], 2);
        $data['soa_total'] = number_format($totals['soa_total'], 2);
        $selectedTemplates = array_values(array_filter(array_map('trim', (array) ($data['invoice_template'] ?? []))));
        $data['invoice_template'] = $selectedTemplates;
        $data['invoice_templates_display'] = implode(', ', $selectedTemplates);
        $savedInvoice = $this->saveInvoiceData($data, $totals);
        $data['id'] = $savedInvoice->id;
        $data['status'] = $savedInvoice->status;
        $data['saved_message'] = 'Invoice '.$data['invoice_no'].' was saved to the database.';

        return view('erp.invoices.review', [
            'invoice' => $data,
            'printDocuments' => $this->billingPrintDocuments($data['vat_classification'] ?? null, $totals, $selectedTemplates, $data),
        ]);
    }

    public function paymentCreate()
    {
        $customers = [];
        $invoices = [];
        $payments = [];

        if (Schema::hasTable('customers')) {
            $customers = DB::table('customers')
                ->select('id', 'code', 'customer_id', 'name', 'currency')
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

        if (Schema::hasTable('sales_documents')) {
            $invoices = DB::table('sales_documents')
                ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
                ->select(
                    'sales_documents.id',
                    'sales_documents.document_no',
                    'sales_documents.currency',
                    'sales_documents.grand_total',
                    'sales_documents.paid_total',
                    'sales_documents.customer_id',
                    'customers.name as customer_name'
                )
                ->whereIn('sales_documents.document_type', ['invoice', 'billing_invoice', 'soa', 'soa_a', 'debit_note'])
                ->orderBy('sales_documents.document_date', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'document_no' => $invoice->document_no,
                        'currency' => $invoice->currency ?: 'PHP',
                        'grand_total' => (float) $invoice->grand_total,
                        'paid_total' => (float) $invoice->paid_total,
                        'balance' => max(0, (float) $invoice->grand_total - (float) $invoice->paid_total),
                        'customer_id' => $invoice->customer_id,
                        'customer_name' => $invoice->customer_name,
                    ];
                })
                ->all();
        }

        if (Schema::hasTable('receipts')) {
            $payments = DB::table('receipts')
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
                    DB::raw('GROUP_CONCAT(sales_documents.document_no ORDER BY sales_documents.document_no SEPARATOR ", ") as invoice_numbers')
                )
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
                        'amount' => number_format((float) $payment->amount, 2),
                        'invoice_number' => $payment->invoice_numbers ?: '',
                        'date' => $payment->receipt_date,
                        'payment_method' => $payment->payment_method,
                        'reference_no' => $payment->reference_no,
                    ];
                })
                ->all();
        }

        return view('erp.payments.create', [
            'customers' => $customers,
            'invoices' => $invoices,
            'payments' => $payments,
        ]);
    }

    public function paymentReceipt()
    {
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
        ]);

        if (Schema::hasTable('receipts') && ctype_digit((string) request('customer_id'))) {
            $customer = DB::table('customers')->where('id', request('customer_id'))->first();
            $companyId = $customer ? $customer->company_id : DB::table('companies')->value('id');
            $invoice = null;

            if (Schema::hasTable('sales_documents') && request('invoice_no')) {
                $invoice = DB::table('sales_documents')
                    ->where('document_no', request('invoice_no'))
                    ->when($customer, function ($query) use ($customer) {
                        return $query->where('customer_id', $customer->id);
                    })
                    ->first();
            }

            $receiptNo = request('receipt_no') ?: $this->nextReceiptNumber($companyId);
            $amount = (float) str_replace(',', '', request('amount', 0));

            DB::transaction(function () use ($customer, $companyId, $invoice, $receiptNo, $amount) {
                $receiptId = DB::table('receipts')->insertGetId([
                    'company_id' => $companyId,
                    'customer_id' => $customer ? $customer->id : request('customer_id'),
                    'receipt_no' => $receiptNo,
                    'receipt_type' => request('receipt_type', 'official_receipt'),
                    'customer_id_text' => $customer ? ($customer->customer_id ?: $customer->code) : null,
                    'job_shipment_id' => request('job_shipment_id'),
                    'class_code' => request('class_code', 'MANILA'),
                    'currency' => request('currency', 'PHP'),
                    'receipt_date' => request('payment_date', date('Y-m-d')),
                    'payment_method' => request('payment_method', 'Check'),
                    'reference_no' => request('reference_no'),
                    'amount' => $amount,
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
                        'amount_applied' => min($amount, max(0, (float) $invoice->grand_total - (float) $invoice->paid_total)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('sales_documents')->where('id', $invoice->id)->update([
                        'paid_total' => min((float) $invoice->grand_total, (float) $invoice->paid_total + $amount),
                        'status' => ((float) $invoice->paid_total + $amount) >= (float) $invoice->grand_total ? 'paid' : 'partial',
                        'updated_at' => now(),
                    ]);
                }
            });

            $payment['receipt_no'] = $receiptNo;
            $payment['customer_name'] = $customer ? $customer->name : request('customer_name');
        }

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
                ['label' => 'Print Official Receipt', 'route' => route('erp.official-receipt.preview', $receiptQuery), 'icon' => 'bi-receipt'],
                ['label' => 'Print Acknowledgement Receipt', 'route' => route('erp.acknowledgement-receipt.preview', $receiptQuery), 'icon' => 'bi-receipt-cutoff'],
            ],
        ]);
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
        return view('erp.print.official-receipt');
    }

    public function acknowledgementReceiptPreview()
    {
        return view('erp.print.acknowledgement-receipt');
    }

    public function publicInvoice($token)
    {
        $invoice = [
            'token' => $token,
            'invoice_no' => 'BI-2026-01043',
            'customer' => 'Philusa Corporation',
            'status' => 'Sent',
            'currency' => 'PHP',
            'amount_due' => '21,327.07',
            'due_date' => '2026-06-23',
            'items' => [
                ['Ocean Freight Service Charge', '1', '19,388.25', '19,388.25'],
                ['Output VAT', '1', '2,326.59', '2,326.59'],
                ['Creditable Withholding Tax', '1', '-387.77', '-387.77'],
            ],
        ];

        return view('erp.client.public-invoice', compact('invoice'));
    }

    public function publicCheckout($token)
    {
        $checkout = [
            'token' => $token,
            'invoice_no' => 'BI-2026-01043',
            'customer' => 'Philusa Corporation',
            'amount_due' => '21,327.07',
            'currency' => 'PHP',
            'gateway' => config('services.stripe.key') ? 'Stripe ready' : 'Stripe test keys not configured',
        ];

        return view('erp.client.checkout', compact('checkout'));
    }

    public function stripeWebhook()
    {
        return response()->json([
            'received' => true,
            'handler' => 'stripe_webhook_placeholder',
            'rules' => [
                'verify_signature',
                'check_event_id_idempotency',
                'create_payment_record',
                'update_invoice_balance',
                'write_activity_log',
            ],
        ]);
    }

    public function customerBillingTemplates()
    {
        $templates = [
            ['Billing Invoice', 'BI-PREPRINT', 'A4 / Legal', 'Yes', 'A/Trade-Billing Invoice', 'PHP / USD'],
            ['Statement of Account', 'SOA-PREPRINT', 'A4', 'Yes', 'A/R Trade-SOA', 'PHP / USD'],
            ['Statement of Account A', 'SOAA-PLAIN', 'A4', 'No', 'A/R Trade-SOA A', 'PHP / USD'],
            ['Debit Note', 'DN-PREPRINT', 'A4', 'Yes', 'A/R Trade-Debit Note', 'PHP / USD'],
            ['Official Receipt', 'OR-PREPRINT', 'A4 / Official Form', 'Yes', 'A/Trade-Billing Invoice', 'PHP / USD'],
            ['Acknowledgement Receipt', 'AR-PREPRINT', 'A4 / Official Form', 'Yes', 'A/R Reimbursable Charges', 'PHP / USD'],
        ];

        return view('erp.reports.customer-billing-templates', compact('templates'));
    }

    public function reportsIndex()
    {
        $reportStats = [
            ['label' => 'Open AR', 'value' => 'PHP 238,700.00', 'icon' => 'bi-hourglass-split', 'tone' => 'primary'],
            ['label' => 'Revenue MTD', 'value' => 'PHP 845,230.00', 'icon' => 'bi-graph-up-arrow', 'tone' => 'success'],
            ['label' => 'VAT Payable', 'value' => 'PHP 25,932.00', 'icon' => 'bi-percent', 'tone' => 'warning'],
            ['label' => 'AP Exposure', 'value' => 'PHP 174,250.00', 'icon' => 'bi-credit-card-2-front', 'tone' => 'danger'],
        ];

        $reportGroups = [
            'Accounts Receivable' => [
                ['name' => 'AR Aging', 'description' => 'Current, 30, 60, and 90+ balances by client, job, currency, VAT, and branch class.', 'route' => route('erp.customer-billing.aging'), 'icon' => 'bi-bar-chart-steps', 'frequency' => 'Daily', 'owner' => 'Accounting'],
                ['name' => 'Customer Statement', 'description' => 'Client billing summary with open invoices, payments, credits, and SOA-ready totals.', 'route' => route('erp.customer-billing.templates'), 'icon' => 'bi-file-earmark-text', 'frequency' => 'Weekly', 'owner' => 'Collections'],
                ['name' => 'Collection Forecast', 'description' => 'Expected receipts by due date, client terms, and payment status.', 'route' => route('erp.export', 'collection-forecast'), 'icon' => 'bi-calendar2-check', 'frequency' => 'Weekly', 'owner' => 'Finance'],
            ],
            'Financial Statements' => [
                ['name' => 'Financial Statement Pack', 'description' => 'Complete finance report center for statements, ledgers, AR, AP, cash, tax, and management reports.', 'route' => route('erp.reports.financial-statements'), 'icon' => 'bi-folder-check', 'frequency' => 'Monthly', 'owner' => 'Finance'],
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

    public function financialStatementsReport()
    {
        $period = request('period', 'May 2026');
        $currency = request('currency', 'PHP');

        $summaryCards = [
            ['label' => 'Revenue MTD', 'value' => 'PHP 845,230.00', 'icon' => 'bi-graph-up-arrow', 'tone' => 'success'],
            ['label' => 'Net Income', 'value' => 'PHP 462,820.00', 'icon' => 'bi-piggy-bank', 'tone' => 'primary'],
            ['label' => 'Cash Balance', 'value' => 'PHP 1,268,500.00', 'icon' => 'bi-bank', 'tone' => 'info'],
            ['label' => 'Working Capital', 'value' => 'PHP 64,450.00', 'icon' => 'bi-arrow-left-right', 'tone' => 'warning'],
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

        return view('erp.reports.financial-statements', compact('period', 'currency', 'summaryCards', 'statementSections', 'reportCatalog'));
    }

    public function financeSettings()
    {
        $settingStats = [
            ['label' => 'Cost Centers', 'value' => '4', 'icon' => 'bi-diagram-3', 'tone' => 'primary'],
            ['label' => 'Item List', 'value' => '8', 'icon' => 'bi-box-seam', 'tone' => 'success'],
            ['label' => 'Currencies', 'value' => 'PHP / USD', 'icon' => 'bi-currency-exchange', 'tone' => 'warning'],
            ['label' => 'Tax Codes', 'value' => 'VAT / WHT', 'icon' => 'bi-percent', 'tone' => 'danger'],
        ];

        $settingGroups = [
            'Accounting Setup' => [
                ['name' => 'Chart of Accounts', 'description' => 'GL accounts for AR, AP, revenue, expenses, VAT, WHT, bank, cash, and equity.', 'route' => route('erp.accounting.coa'), 'icon' => 'bi-journal-text', 'status' => 'Active'],
                ['name' => 'Cost Centers', 'description' => 'Department, branch, and operation-level cost tracking for management reporting.', 'route' => route('erp.settings.index').'#cost-centers', 'icon' => 'bi-diagram-3', 'status' => 'Required'],
                ['name' => 'Number Series', 'description' => 'Document numbering for BI, SOA, OR, AP bills, check vouchers, payments, and journal entries.', 'route' => route('erp.action', 'number-series'), 'icon' => 'bi-123', 'status' => 'Active'],
            ],
            'Billing & Item Setup' => [
                ['name' => 'Item List', 'description' => 'Service, freight, documentation, terminal handling, reimbursable, and tax treatment defaults.', 'route' => route('erp.settings.index').'#item-list', 'icon' => 'bi-box-seam', 'status' => 'Required'],
                ['name' => 'Invoice Templates', 'description' => 'Billing Invoice, SOA B, SOA A, Debit Note, Official Receipt, and custom templates.', 'route' => route('erp.customer-billing.templates'), 'icon' => 'bi-file-earmark-text', 'status' => 'Active'],
                ['name' => 'Print Layouts', 'description' => 'Pre-printed form alignment, paper size, field positions, and custom printable fields.', 'route' => route('erp.billings.print-layout.invoice'), 'icon' => 'bi-sliders', 'status' => 'Active'],
            ],
            'Tax, Currency & Banking' => [
                ['name' => 'Tax Codes', 'description' => '12% VAT, zero-rated, exempt, EWT, CWT, and withholding defaults for AR/AP.', 'route' => route('erp.tax.calculator'), 'icon' => 'bi-percent', 'status' => 'Active'],
                ['name' => 'Currency Rates', 'description' => 'USD/PHP exchange rates for invoice, AP, payment, bank, and FX revaluation.', 'route' => route('erp.fx.index'), 'icon' => 'bi-currency-dollar', 'status' => 'Required'],
                ['name' => 'Bank Accounts', 'description' => 'PHP and USD bank accounts for receipts, payments, check printing, and reconciliation.', 'route' => route('erp.banking.cashbook'), 'icon' => 'bi-bank', 'status' => 'Active'],
            ],
            'Controls' => [
                ['name' => 'Approval Matrix', 'description' => 'Approval limits for billing, AP payments, credit overrides, FX, tax, and user access.', 'route' => route('erp.approval-matrix'), 'icon' => 'bi-check2-square', 'status' => 'Active'],
                ['name' => 'User Access Control', 'description' => 'Role permissions by module for view, create, edit, and delete access.', 'route' => route('erp.users.index'), 'icon' => 'bi-person-lock', 'status' => 'Active'],
                ['name' => 'Audit Trail', 'description' => 'Activity logs for finance settings, documents, approvals, print, export, and posting.', 'route' => route('erp.action', 'audit-logs'), 'icon' => 'bi-clock-history', 'status' => 'Enabled'],
            ],
        ];

        $costCenters = [
            ['CC-MNL-OPS', 'Manila Operations', 'Operations', 'MANILA', 'SeaJet Logistics', 'Active'],
            ['CC-CEB-OPS', 'Cebu Operations', 'Operations', 'CEBU', 'SeaJet Logistics', 'Active'],
            ['CC-PAM-OPS', 'Pampanga Operations', 'Operations', 'PAMPANGA', 'SeaJet Logistics', 'Active'],
            ['CC-HQ-FIN', 'Head Office Finance', 'Finance', 'Main', 'SeaJet Holdings', 'Active'],
        ];

        $items = [
            ['SVC-FREIGHT', 'Ocean Freight', 'Service Revenue', 'Zero-rated', 'USD / PHP', 'Billable'],
            ['SVC-THC', 'Terminal Handling', 'Service Revenue', '12% VAT', 'PHP', 'Billable'],
            ['SVC-DOC', 'Documentation Fee', 'Service Revenue', '12% VAT', 'PHP', 'Billable'],
            ['REIMB-PORT', 'Port Advances / Reimbursable', 'A/R Reimbursable Charges', 'Exempt', 'USD / PHP', 'Reimbursable'],
            ['AP-FUEL', 'Fuel / Trucking Expense', 'Cost of Services', '12% VAT + EWT', 'PHP', 'AP Expense'],
            ['AP-BROKER', 'Brokerage Expense', 'Cost of Services', '12% VAT + EWT', 'PHP', 'AP Expense'],
            ['BANK-FEE', 'Bank Charges', 'Bank Fees', 'Exempt', 'USD / PHP', 'Expense'],
            ['FX-ADJ', 'FX Gain / Loss Adjustment', 'FX Gain/Loss', 'N/A', 'USD / PHP', 'Journal'],
        ];

        $numberSeries = [
            ['Billing Invoice', 'BI-', '000001', '6 digits', 'Active'],
            ['Statement of Account', 'SOA-', '000001', '6 digits', 'Active'],
            ['Official Receipt', 'OR-', '000001', '6 digits', 'Active'],
            ['AP Bill', 'BILL-', '000001', '6 digits', 'Active'],
            ['Check Voucher', 'CV-', '000001', '6 digits', 'Active'],
            ['Journal Voucher', 'JV-', '000001', '6 digits', 'Active'],
        ];

        $taxCodes = [
            ['VAT12', 'Output/Input VAT 12%', '12%', 'VAT', 'Sales and purchases'],
            ['ZERO', 'Zero-rated', '0%', 'VAT', 'Export services'],
            ['EXEMPT', 'VAT Exempt', '0%', 'VAT', 'Reimbursements and exempt items'],
            ['EWT2', 'Expanded Withholding Tax', '2%', 'Withholding', 'Supplier services'],
            ['CWT2', 'Creditable Withholding Tax', '2%', 'Withholding', 'Customer collections'],
        ];

        return view('erp.settings.index', compact('settingStats', 'settingGroups', 'costCenters', 'items', 'numberSeries', 'taxCodes'));
    }

    public function agingReport()
    {
        $rows = [
            ['CUST-001', 'North Harbor Trading', 'JOB-SEA-2026-0422', 'BI-2026-01042', 'PHP', '12%', 'MANILA', 98500, 0, 0, 0],
            ['CUST-002', 'Blue Anchor Logistics', 'JOB-SEA-2026-0398', 'SOAA-2026-00211', 'USD', 'zero-rated', 'CEBU', 0, 1840, 0, 0],
            ['CUST-003', 'Island Prime Foods', 'JOB-SEA-2026-0365', 'DN-2026-00042', 'PHP', 'exempt', 'PAMPANGA', 0, 0, 36500, 12400],
        ];

        return view('erp.reports.aging', compact('rows'));
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
                'subtitle' => 'Official Receipts, Acknowledgement Receipts, SOA A collections, and payment allocation.',
                'actions' => ['Official Receipt', 'Acknowledgement Receipt', 'SOA A Collection', 'Deposit Collection'],
                'features' => ['OR Pre-printed', 'AR Pre-printed', 'SOA A No Pre-printed Form', 'Cash', 'Check', 'Bank Transfer', 'Partial Payment', 'Multiple Invoice Payment'],
                'columns' => ['Receipt Type', 'Receipt No.', 'Customer ID', 'Job/Shipment ID', 'Currency', 'Amount'],
                'rows' => [
                    ['Official Receipt', 'OR-2026-00618', 'CUST-001', 'JOB-SEA-2026-0422', 'PHP', '43,200.00'],
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
