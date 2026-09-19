<?php

namespace App\Seeders;

use App\Services\Accounting\OpeningBalanceImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OpeningBalanceImportSeeder extends Seeder
{
    public function run(OpeningBalanceImportService $importer)
    {
        $path = env('OPENING_BALANCE_IMPORT_FILE', 'C:\Users\RUSHPOINT IT\Downloads\AR TRADE & AR REIMBURSABLE.xlsx');
        $isWindowsAbsolute = strlen($path) >= 3
            && ctype_alpha($path[0])
            && $path[1] === ':'
            && in_array($path[2], ['\\', '/'], true);
        $isAbsolute = $isWindowsAbsolute || substr($path, 0, 1) === '/';
        if (!$isAbsolute) {
            $path = base_path($path);
        }
        if (!is_file($path)) {
            throw new RuntimeException('Opening-balance workbook was not found: '.$path);
        }
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('Opening-balance seed file must be an XLSX workbook.');
        }

        $companyCode = env('OPENING_BALANCE_COMPANY_CODE', 'SJ');
        $company = DB::table('companies')
            ->where('code', $companyCode)
            ->where('is_active', true)
            ->first();
        if (!$company) {
            $company = DB::table('companies')->where('is_active', true)->orderBy('id')->first();
        }
        if (!$company) {
            throw new RuntimeException('No active company exists. Seed the company and chart of accounts first.');
        }

        $mapping = $this->accountMapping($company->id);
        $fileHash = hash_file('sha256', $path);
        $preview = $importer->preview($path, $company->id, $mapping, $fileHash);

        $this->line('Workbook: '.basename($path));
        $this->line('Ready rows: '.$preview['valid_count']);
        $this->line('Skipped rows: '.$preview['skipped_count']);
        $this->line('Ready amount: PHP '.number_format($preview['total_amount'], 2));

        if ($preview['skipped_count'] > 0) {
            $exceptions = [];
            foreach ($preview['rows'] as $row) {
                if (!$row['is_valid']) {
                    foreach ($row['errors'] as $error) {
                        $exceptions[$error] = ($exceptions[$error] ?? 0) + 1;
                    }
                }
            }
            foreach ($exceptions as $error => $count) {
                $this->line('Skipped '.$count.' row(s): '.$error);
            }
        }

        if ($preview['valid_count'] < 1) {
            $this->line('Nothing was imported. All rows were invalid or already imported.');
            return;
        }

        $userId = DB::table('users')->where('company_id', $company->id)->orderBy('id')->value('id');
        if (!$userId) {
            $userId = DB::table('users')->orderBy('id')->value('id');
        }

        $result = $importer->import(
            $path,
            $company->id,
            $mapping,
            $fileHash,
            basename($path),
            $userId
        );

        $this->line('Created journals: '.$result['journal_count'].' (Excel dates '.$result['source_date_from'].' to '.$result['source_date_to'].')');
        $this->line('Created customers: '.$result['customers_created']);
        $this->line('Created open invoices: '.$result['invoices_created']);
        $this->line('Created open vendor bills: '.$result['vendor_bills_created']);
    }

    private function accountMapping($companyId)
    {
        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $find = function (array $preferredNames, array $contains, $currency = null) use ($accounts) {
            foreach ($preferredNames as $preferredName) {
                foreach ($accounts as $account) {
                    if (strcasecmp(trim($account->name), $preferredName) === 0) {
                        return (int) $account->id;
                    }
                }
            }

            foreach ($accounts as $account) {
                $name = strtoupper((string) $account->name);
                $normalized = preg_replace('/[^A-Z0-9]+/', '', $name);
                $currencyMatches = $currency === null
                    || ($currency === 'USD' ? strpos($name, 'USD') !== false : strpos($name, 'USD') === false);
                if (!$currencyMatches) {
                    continue;
                }
                foreach ($contains as $needle) {
                    if (strpos($normalized, $needle) !== false) {
                        return (int) $account->id;
                    }
                }
            }

            return null;
        };

        $opening = $find(['Opening Balance Equity'], ['OPENINGBALANCE']);
        $arTradePhp = $find(['A/R-Trade( BDO-PHP )', 'A/Trade-Billing Invoice'], ['ARTRADE', 'ATRADEBILLINGINVOICE'], 'PHP');
        $arTradeUsd = $find(['A/R-Trade( BDO-USD )'], ['ARTRADE', 'ATRADEBILLINGINVOICE'], 'USD') ?: $arTradePhp;
        $arReimbursablePhp = $find(['A/R-Reimbursable Charges (PHP)', 'A/R Reimbursable Charges'], ['ARREIMBURSABLE', 'REIMBURSABLECHARGES'], 'PHP');
        $arReimbursableUsd = $find(['A/R-Reimbursable Charges (USD)'], ['ARREIMBURSABLE', 'REIMBURSABLECHARGES'], 'USD') ?: $arReimbursablePhp;
        $apTradePhp = $find(['A/P-Trade (BDO - PHP)', 'Accounts Payable'], ['APTRADE', 'ACCOUNTSPAYABLE'], 'PHP');
        $apTradeUsd = $find(['A/P-Trade (BDO - USD)'], ['APTRADE', 'ACCOUNTSPAYABLE'], 'USD') ?: $apTradePhp;

        $mapping = [
            'opening_balance' => $opening,
            'ar_trade_php' => $arTradePhp,
            'ar_trade_usd' => $arTradeUsd,
            'ar_reimbursable_php' => $arReimbursablePhp,
            'ar_reimbursable_usd' => $arReimbursableUsd,
            'ap_trade_php' => $apTradePhp,
            'ap_trade_usd' => $apTradeUsd,
        ];

        $missing = array_keys(array_filter($mapping, function ($accountId) {
            return !$accountId;
        }));
        if ($missing) {
            throw new RuntimeException('Missing opening-balance account mappings: '.implode(', ', $missing).'.');
        }

        return $mapping;
    }

    private function line($message)
    {
        if ($this->command) {
            $this->command->line($message);
        }
    }
}
