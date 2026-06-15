<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use DOMDocument;
use ZipArchive;

class ImportClientsSeajet extends Command
{
    protected $signature = 'seajet:import-clients {path=C:\Users\Thinkpad\Documents\seajet\masterlists\CLIENTS_SEAJET.xlsx}';

    protected $description = 'Replace the customer masterlist from the SeaJet clients workbook.';

    public function handle()
    {
        $path = $this->argument('path');

        if (!is_file($path)) {
            $this->error('Workbook not found: '.$path);
            return 1;
        }

        $rows = $this->readClientRows($path);
        if (count($rows) === 0) {
            $this->error('No client rows found in workbook.');
            return 1;
        }

        $companyId = $this->defaultCompanyId();
        $now = now();
        $stats = [
            'deleted_customers' => 0,
            'deleted_sales_documents' => 0,
            'deleted_receipts' => 0,
            'inserted_customers' => 0,
            'manila' => 0,
            'cebu' => 0,
            'pampanga' => 0,
        ];

        DB::transaction(function () use ($rows, $companyId, $now, &$stats) {
            $customerIds = Schema::hasTable('customers')
                ? DB::table('customers')->where('company_id', $companyId)->pluck('id')->all()
                : [];

            $salesDocumentIds = Schema::hasTable('sales_documents')
                ? DB::table('sales_documents')->whereIn('customer_id', $customerIds)->pluck('id')->all()
                : [];
            $receiptIds = Schema::hasTable('receipts')
                ? DB::table('receipts')->whereIn('customer_id', $customerIds)->pluck('id')->all()
                : [];
            $projectIds = Schema::hasTable('projects')
                ? DB::table('projects')->whereIn('customer_id', $customerIds)->pluck('id')->all()
                : [];
            $projectTaskIds = Schema::hasTable('project_tasks')
                ? DB::table('project_tasks')->whereIn('project_id', $projectIds)->pluck('id')->all()
                : [];
            $quoteIds = Schema::hasTable('quotes')
                ? DB::table('quotes')->whereIn('customer_id', $customerIds)->pluck('id')->all()
                : [];

            $stats['deleted_sales_documents'] = count($salesDocumentIds);
            $stats['deleted_receipts'] = count($receiptIds);
            $stats['deleted_customers'] = count($customerIds);

            $this->deleteWhereIn('time_entries', 'project_task_id', $projectTaskIds);
            $this->deleteWhereIn('project_tasks', 'project_id', $projectIds);
            $this->deleteWhereIn('projects', 'customer_id', $customerIds);
            $this->deleteWhereIn('quote_items', 'quote_id', $quoteIds);
            $this->deleteWhereIn('quotes', 'customer_id', $customerIds);
            $this->deleteWhereIn('recurring_invoice_templates', 'customer_id', $customerIds);
            $this->deleteWhereIn('customer_payment_methods', 'customer_id', $customerIds);
            $this->deleteWhereIn('client_portal_tokens', 'customer_id', $customerIds);
            $this->deleteWhereIn('client_contacts', 'customer_id', $customerIds);
            $this->deleteWhereIn('expenses', 'customer_id', $customerIds);
            $this->deleteWhereIn('receipt_applications', 'receipt_id', $receiptIds);
            $this->deleteWhereIn('receipt_applications', 'sales_document_id', $salesDocumentIds);
            $this->deleteWhereIn('receipts', 'customer_id', $customerIds);
            $this->deleteWhereIn('sales_document_lines', 'sales_document_id', $salesDocumentIds);
            $this->deleteWhereIn('sales_documents', 'customer_id', $customerIds);

            if (Schema::hasTable('journal_entry_lines') && Schema::hasColumn('journal_entry_lines', 'customer_id')) {
                DB::table('journal_entry_lines')->whereIn('customer_id', $customerIds)->update(['customer_id' => null]);
            }

            $this->deleteWhereIn('customers', 'id', $customerIds);

            foreach ($rows as $index => $row) {
                $branch = $this->branchFromAddress($row['address']);
                $tax = $this->taxClassification($row['tax_classification']);
                $stats[strtolower($branch)]++;

                DB::table('customers')->insert([
                    'company_id' => $companyId,
                    'code' => 'CL-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'customer_id' => 'CL-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'name' => $row['customer'],
                    'tin' => $row['tin'] ?: null,
                    'tax_classification' => $tax,
                    'contact_person' => null,
                    'email' => null,
                    'phone' => null,
                    'billing_address' => $row['address'] ?: null,
                    'credit_limit' => 0,
                    'credit_status' => 'Within Limit',
                    'payment_terms' => '30 Days',
                    'credit_term_days' => 30,
                    'currency' => 'PHP',
                    'default_class_code' => $branch,
                    'default_ar_account_code' => $tax === '12%' ? 'AR_TRADE_BI' : 'AR_TRADE_SOAA',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $stats['inserted_customers']++;
            }
        });

        $this->info('Client masterlist imported.');
        $this->line('Deleted customers: '.$stats['deleted_customers']);
        $this->line('Deleted sales documents: '.$stats['deleted_sales_documents']);
        $this->line('Deleted receipts: '.$stats['deleted_receipts']);
        $this->line('Inserted customers: '.$stats['inserted_customers']);
        $this->line('Branch counts: MANILA '.$stats['manila'].', CEBU '.$stats['cebu'].', PAMPANGA '.$stats['pampanga']);

        return 0;
    }

    private function readClientRows($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheetXml = new DOMDocument();
        $sheetXml->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();

        $rows = [];
        foreach ($sheetXml->getElementsByTagName('row') as $row) {
            $rowNumber = (int) $row->getAttribute('r');
            if ($rowNumber < 3) {
                continue;
            }

            $cells = [];
            foreach ($row->getElementsByTagName('c') as $cell) {
                $column = preg_replace('/\d+/', '', $cell->getAttribute('r'));
                $cells[$column] = $this->cellValue($cell, $sharedStrings);
            }

            $customer = $this->clean($cells['C'] ?? '');
            if ($customer === '') {
                continue;
            }

            $rows[] = [
                'tax_classification' => $this->clean($cells['B'] ?? ''),
                'customer' => $customer,
                'address' => $this->clean($cells['D'] ?? ''),
                'tin' => $this->clean($cells['E'] ?? ''),
            ];
        }

        return $rows;
    }

    private function sharedStrings(ZipArchive $zip)
    {
        $xml = new DOMDocument();
        $xml->loadXML($zip->getFromName('xl/sharedStrings.xml'));
        $strings = [];

        foreach ($xml->getElementsByTagName('si') as $item) {
            $strings[] = $this->clean($item->textContent);
        }

        return $strings;
    }

    private function cellValue($cell, array $sharedStrings)
    {
        $nodes = $cell->getElementsByTagName('v');
        $value = $nodes->length ? $nodes->item(0)->textContent : '';
        if ($cell->getAttribute('t') === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function clean($value)
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }

    private function taxClassification($value)
    {
        $value = strtoupper($value);
        if (strpos($value, 'ZERO') !== false) {
            return 'zero-rated';
        }
        if (strpos($value, 'EXEMPT') !== false) {
            return 'exempt';
        }

        return '12%';
    }

    private function branchFromAddress($address)
    {
        $address = strtoupper($address);
        if (preg_match('/\b(CEBU|MACTAN|LAPU[\s-]?LAPU|MANDAUE|BOGO)\b/', $address)) {
            return 'CEBU';
        }
        if (preg_match('/\b(PAMPANGA|CLARK|ANGELES|MABALACAT|PORAC|MEXICO|SAN SIMON|STO TOMAS|GUAGUA|APALIT)\b/', $address)) {
            return 'PAMPANGA';
        }

        return 'MANILA';
    }

    private function deleteWhereIn($table, $column, array $values)
    {
        if (!Schema::hasTable($table) || empty($values)) {
            return;
        }

        DB::table($table)->whereIn($column, $values)->delete();
    }

    private function defaultCompanyId()
    {
        if (Schema::hasTable('companies')) {
            $id = DB::table('companies')->orderBy('id')->value('id');
            if ($id) {
                return $id;
            }
        }

        return 1;
    }
}
