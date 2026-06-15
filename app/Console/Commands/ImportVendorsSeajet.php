<?php

namespace App\Console\Commands;

use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use XMLReader;
use ZipArchive;

class ImportVendorsSeajet extends Command
{
    protected $signature = 'seajet:import-vendors {path=C:\Users\Thinkpad\Documents\seajet\masterlists\VENDOR.xlsx}';

    protected $description = 'Replace the vendor masterlist from the SeaJet workbook.';

    public function handle()
    {
        $path = $this->argument('path');

        if (!is_file($path)) {
            $this->error('Workbook not found: '.$path);
            return 1;
        }

        $rows = $this->readVendorRows($path);
        if (count($rows) === 0) {
            $this->error('No vendor rows found in workbook.');
            return 1;
        }

        $companyId = $this->defaultCompanyId();
        $now = now();
        $stats = [
            'deleted_vendors' => 0,
            'deleted_purchase_documents' => 0,
            'deleted_payments' => 0,
            'inserted_vendors' => 0,
            'manila' => 0,
            'cebu' => 0,
            'pampanga' => 0,
            'vat' => 0,
            'zero_rated' => 0,
            'exempt' => 0,
        ];

        DB::transaction(function () use ($rows, $companyId, $now, &$stats) {
            $supplierIds = Schema::hasTable('suppliers')
                ? DB::table('suppliers')->where('company_id', $companyId)->pluck('id')->all()
                : [];

            $purchaseDocumentIds = Schema::hasTable('purchase_documents')
                ? DB::table('purchase_documents')->whereIn('supplier_id', $supplierIds)->pluck('id')->all()
                : [];
            $paymentIds = Schema::hasTable('payments')
                ? DB::table('payments')->whereIn('supplier_id', $supplierIds)->pluck('id')->all()
                : [];

            $stats['deleted_vendors'] = count($supplierIds);
            $stats['deleted_purchase_documents'] = count($purchaseDocumentIds);
            $stats['deleted_payments'] = count($paymentIds);

            $this->deleteWhereIn('payment_applications', 'payment_id', $paymentIds);
            $this->deleteWhereIn('payment_applications', 'purchase_document_id', $purchaseDocumentIds);
            $this->deleteWhereIn('payments', 'supplier_id', $supplierIds);
            $this->deleteWhereIn('vendor_bill_lines', 'purchase_document_id', $purchaseDocumentIds);
            $this->deleteWhereIn('purchase_document_lines', 'purchase_document_id', $purchaseDocumentIds);
            $this->deleteWhereIn('purchase_documents', 'supplier_id', $supplierIds);
            $this->deleteWhereIn('purchase_orders', 'supplier_id', $supplierIds);
            $this->nullWhereIn('expenses', 'supplier_id', $supplierIds);
            $this->nullWhereIn('journal_entry_lines', 'supplier_id', $supplierIds);
            $this->deleteWhereIn('suppliers', 'id', $supplierIds);

            foreach ($rows as $index => $row) {
                $branch = $this->branchFromAddress($row['address']);
                $tax = $this->taxClassification($row['tax_type']);
                $defaultExpenseAccountId = $this->defaultExpenseAccountId($companyId);
                $stats[strtolower($branch)]++;
                $stats[$tax === '12%' ? 'vat' : str_replace('-', '_', $tax)]++;

                DB::table('suppliers')->insert($this->supplierPayload([
                    'company_id' => $companyId,
                    'code' => 'VND-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'name' => $row['vendor'],
                    'tin' => $row['tin'] ?: null,
                    'tax_classification' => $tax,
                    'contact_person' => null,
                    'email' => null,
                    'phone' => null,
                    'address' => $row['address'] ?: null,
                    'payment_terms' => '30 Days',
                    'payment_term_days' => 30,
                    'currency' => 'PHP',
                    'preferred_payment_method' => null,
                    'default_class_code' => $branch,
                    'default_ap_account' => 'Accounts Payable Trade',
                    'default_expense_account_id' => $defaultExpenseAccountId,
                    'withholding_tax' => '0%',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));

                $stats['inserted_vendors']++;
            }
        });

        $this->info('Vendor masterlist imported.');
        $this->line('Deleted vendors: '.$stats['deleted_vendors']);
        $this->line('Deleted purchase documents: '.$stats['deleted_purchase_documents']);
        $this->line('Deleted payments: '.$stats['deleted_payments']);
        $this->line('Inserted vendors: '.$stats['inserted_vendors']);
        $this->line('Branch counts: MANILA '.$stats['manila'].', CEBU '.$stats['cebu'].', PAMPANGA '.$stats['pampanga']);
        $this->line('Tax counts: VAT '.$stats['vat'].', zero-rated '.$stats['zero_rated'].', exempt '.$stats['exempt']);

        return 0;
    }

    private function readVendorRows($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sheet = tempnam(sys_get_temp_dir(), 'seajet-vendors-');
        file_put_contents($sheet, $zip->getFromName('xl/worksheets/sheet1.xml'));
        $sharedStrings = $this->sharedStrings($zip);
        $zip->close();

        $rows = [];
        $reader = new XMLReader();
        $reader->open($sheet);

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }

            $rowNumber = (int) $reader->getAttribute('r');
            if ($rowNumber < 2) {
                continue;
            }

            $cells = [];
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'row') {
                    break;
                }
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'c') {
                    continue;
                }

                $column = preg_replace('/\d+/', '', $reader->getAttribute('r'));
                if (!in_array($column, ['B', 'C', 'D', 'E'], true)) {
                    if ($column && strcmp($column, 'E') > 0) {
                        break;
                    }
                    continue;
                }

                $cells[$column] = $this->cellValueFromXml($reader->readOuterXML(), $sharedStrings);
            }

            $vendor = $this->clean($cells['B'] ?? '');
            if ($vendor === '') {
                continue;
            }

            $rows[] = [
                'vendor' => $vendor,
                'address' => $this->clean($cells['C'] ?? ''),
                'tin' => $this->clean($cells['D'] ?? ''),
                'tax_type' => $this->clean($cells['E'] ?? ''),
            ];
        }

        $reader->close();
        @unlink($sheet);

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

    private function cellValueFromXml($cellXml, array $sharedStrings)
    {
        $xml = new DOMDocument();
        $xml->loadXML($cellXml);
        $cell = $xml->documentElement;
        $nodes = $cell->getElementsByTagName('v');
        $value = $nodes->length ? $nodes->item(0)->textContent : '';

        if ($cell->getAttribute('t') === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function supplierPayload(array $payload)
    {
        return array_intersect_key($payload, array_flip(Schema::getColumnListing('suppliers')));
    }

    private function defaultExpenseAccountId($companyId)
    {
        if (!Schema::hasTable('accounts')) {
            return null;
        }

        $account = DB::table('accounts')
            ->leftJoin('account_categories', 'account_categories.id', '=', 'accounts.account_category_id')
            ->where('accounts.company_id', $companyId)
            ->where('accounts.is_active', true)
            ->where(function ($query) {
                $query->where('accounts.name', 'like', '%Operating Expense%')
                    ->orWhere('accounts.name', 'like', '%Expense%')
                    ->orWhere('account_categories.name', 'like', '%Expense%')
                    ->orWhere('account_categories.name', 'like', '%Cost%');
            })
            ->orderBy('accounts.code')
            ->select('accounts.id')
            ->first();

        return $account ? $account->id : null;
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
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || empty($values)) {
            return;
        }

        DB::table($table)->whereIn($column, $values)->delete();
    }

    private function nullWhereIn($table, $column, array $values)
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || empty($values)) {
            return;
        }

        DB::table($table)->whereIn($column, $values)->update([$column => null]);
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
