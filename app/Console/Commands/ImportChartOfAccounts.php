<?php

namespace App\Console\Commands;

use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class ImportChartOfAccounts extends Command
{
    protected $signature = 'seajet:import-chart-of-accounts {path=C:\Users\Thinkpad\Documents\seajet\masterlists\CHART OF ACCOUNTS.xlsx}';

    protected $description = 'Replace the chart of accounts from the SeaJet workbook.';

    public function handle()
    {
        $path = $this->argument('path');

        if (!is_file($path)) {
            $this->error('Workbook not found: '.$path);
            return 1;
        }

        $rows = $this->readAccountRows($path);
        if (count($rows) === 0) {
            $this->error('No account rows found in workbook.');
            return 1;
        }

        $companyId = $this->defaultCompanyId();
        $now = now();
        $stats = [
            'deleted_accounts' => 0,
            'deleted_journal_entries' => 0,
            'inserted_accounts' => 0,
            'Assets' => 0,
            'Liabilities' => 0,
            'Equity' => 0,
            'Revenue' => 0,
            'Expenses' => 0,
        ];

        DB::transaction(function () use ($rows, $companyId, $now, &$stats) {
            $accountIds = DB::table('accounts')->where('company_id', $companyId)->pluck('id')->all();
            $journalIds = Schema::hasTable('journal_entry_lines')
                ? DB::table('journal_entry_lines')->whereIn('account_id', $accountIds)->pluck('journal_entry_id')->unique()->all()
                : [];

            $stats['deleted_accounts'] = count($accountIds);
            $stats['deleted_journal_entries'] = count($journalIds);

            $this->nullAccountReference('tax_codes', 'account_id', $accountIds);
            $this->nullAccountReference('bank_accounts', 'account_id', $accountIds);
            $this->nullAccountReference('ar_account_mappings', 'account_id', $accountIds);
            $this->nullAccountReference('purchase_document_lines', 'account_id', $accountIds);
            $this->nullAccountReference('service_products', 'revenue_account_id', $accountIds);

            $this->deleteWhereIn('journal_entry_lines', 'account_id', $accountIds);
            $this->deleteWhereIn('journal_entries', 'id', $journalIds);

            if (!empty($accountIds)) {
                DB::table('accounts')->whereIn('id', $accountIds)->update(['parent_id' => null]);
                DB::table('accounts')->whereIn('id', $accountIds)->delete();
            }

            $categoryIds = $this->accountCategoryIds($now);
            $categoryCounters = [
                'Assets' => 1000,
                'Liabilities' => 2000,
                'Equity' => 3000,
                'Revenue' => 4000,
                'Expenses' => 5000,
            ];

            foreach ($rows as $row) {
                $category = $this->categoryForType($row['type']);
                $code = (string) $categoryCounters[$category];
                $categoryCounters[$category] += 10;

                DB::table('accounts')->insert([
                    'company_id' => $companyId,
                    'account_category_id' => $categoryIds[$category],
                    'parent_id' => null,
                    'code' => $code,
                    'name' => $row['account'],
                    'type' => $row['type'] ?: $category,
                    'opening_balance' => 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $stats['inserted_accounts']++;
                $stats[$category]++;
            }
        });

        $this->info('Chart of accounts imported.');
        $this->line('Deleted accounts: '.$stats['deleted_accounts']);
        $this->line('Deleted journal entries: '.$stats['deleted_journal_entries']);
        $this->line('Inserted accounts: '.$stats['inserted_accounts']);
        $this->line('Category counts: Assets '.$stats['Assets'].', Liabilities '.$stats['Liabilities'].', Equity '.$stats['Equity'].', Revenue '.$stats['Revenue'].', Expenses '.$stats['Expenses']);

        return 0;
    }

    private function readAccountRows($path)
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
            if ($rowNumber < 2) {
                continue;
            }

            $cells = [];
            foreach ($row->getElementsByTagName('c') as $cell) {
                $column = preg_replace('/\d+/', '', $cell->getAttribute('r'));
                $cells[$column] = $this->cellValue($cell, $sharedStrings);
            }

            $account = $this->clean($cells['C'] ?? '');
            if ($account === '') {
                continue;
            }

            $rows[] = [
                'account' => $account,
                'type' => $this->clean($cells['D'] ?? ''),
                'tax' => $this->clean($cells['E'] ?? ''),
                'atc' => $this->clean($cells['F'] ?? ''),
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

    private function accountCategoryIds($now)
    {
        $categories = [
            'Assets' => 'debit',
            'Liabilities' => 'credit',
            'Equity' => 'credit',
            'Revenue' => 'credit',
            'Expenses' => 'debit',
        ];
        $ids = [];

        foreach ($categories as $name => $normalBalance) {
            DB::table('account_categories')->updateOrInsert(
                ['name' => $name],
                ['name' => $name, 'normal_balance' => $normalBalance, 'updated_at' => $now, 'created_at' => $now]
            );
            $ids[$name] = DB::table('account_categories')->where('name', $name)->value('id');
        }

        return $ids;
    }

    private function categoryForType($type)
    {
        $type = strtolower($type);

        if (strpos($type, 'payable') !== false || strpos($type, 'liability') !== false) {
            return 'Liabilities';
        }
        if (strpos($type, 'equity') !== false) {
            return 'Equity';
        }
        if (strpos($type, 'income') !== false || strpos($type, 'revenue') !== false) {
            return 'Revenue';
        }
        if (strpos($type, 'expense') !== false || strpos($type, 'cost of services') !== false || strpos($type, 'cost of goods') !== false) {
            return 'Expenses';
        }

        return 'Assets';
    }

    private function nullAccountReference($table, $column, array $accountIds)
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || empty($accountIds)) {
            return;
        }

        DB::table($table)->whereIn($column, $accountIds)->update([$column => null]);
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
