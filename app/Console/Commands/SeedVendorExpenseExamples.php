<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedVendorExpenseExamples extends Command
{
    protected $signature = 'seajet:seed-vendor-expense-examples';

    protected $description = 'Create three vendor records linked to expense chart accounts.';

    public function handle()
    {
        if (!Schema::hasTable('suppliers')) {
            $this->error('Suppliers table not found.');
            return 1;
        }

        $companyId = $this->defaultCompanyId();
        $now = now();

        $vendors = [
            [
                'code' => 'VND-FUEL-001',
                'name' => 'Harbor Fuel Services',
                'tin' => '009-421-785-000',
                'address' => 'Pier 12, North Harbor, Manila',
                'class' => 'MANILA',
                'account' => ['5100', 'Operating Expenses'],
            ],
            [
                'code' => 'VND-TRUCK-002',
                'name' => 'Luzon Trucking Partners',
                'tin' => '010-552-918-000',
                'address' => 'Jose Abad Santos Avenue, San Fernando, Pampanga',
                'class' => 'PAMPANGA',
                'account' => ['5000', 'Cost of Services'],
            ],
            [
                'code' => 'VND-BROKER-003',
                'name' => 'Cebu Port Brokerage',
                'tin' => '011-673-204-000',
                'address' => 'Sergio Osmena Boulevard, Cebu City',
                'class' => 'CEBU',
                'account' => ['5101', 'Brokerage and Trucking'],
            ],
        ];

        foreach ($vendors as $vendor) {
            $accountId = $this->expenseAccountId($companyId, $vendor['account'][0], $vendor['account'][1], $now);

            DB::table('suppliers')->updateOrInsert(
                ['company_id' => $companyId, 'code' => $vendor['code']],
                $this->supplierPayload([
                    'company_id' => $companyId,
                    'code' => $vendor['code'],
                    'name' => $vendor['name'],
                    'tin' => $vendor['tin'],
                    'tax_classification' => '12% VAT',
                    'contact_person' => 'Accounts Payable',
                    'email' => strtolower(str_replace([' ', '-'], '.', $vendor['name'])).'@example.com',
                    'phone' => '+63 2 8555 '.substr($vendor['code'], -4),
                    'address' => $vendor['address'],
                    'payment_terms' => '30 Days',
                    'payment_term_days' => 30,
                    'currency' => 'PHP',
                    'preferred_payment_method' => 'Bank Transfer',
                    'default_class_code' => $vendor['class'],
                    'default_ap_account' => 'Accounts Payable Trade',
                    'default_expense_account_id' => $accountId,
                    'withholding_tax' => '2% EWT',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->info('Vendor records created: '.count($vendors));

        return 0;
    }

    private function expenseAccountId($companyId, $code, $name, $now)
    {
        if (!Schema::hasTable('accounts')) {
            return null;
        }

        $account = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where(function ($query) use ($code, $name) {
                $query->where('code', $code)->orWhere('name', $name);
            })
            ->first();

        if ($account) {
            return $account->id;
        }

        $categoryId = $this->expenseCategoryId($now);

        return DB::table('accounts')->insertGetId(array_intersect_key([
            'company_id' => $companyId,
            'account_category_id' => $categoryId,
            'code' => $code,
            'name' => $name,
            'type' => 'Expenses',
            'normal_balance' => 'debit',
            'opening_balance' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_flip(Schema::getColumnListing('accounts'))));
    }

    private function expenseCategoryId($now)
    {
        if (!Schema::hasTable('account_categories')) {
            return null;
        }

        DB::table('account_categories')->updateOrInsert(
            ['name' => 'Expenses'],
            array_intersect_key([
                'normal_balance' => 'debit',
                'statement_type' => 'income_statement',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ], array_flip(Schema::getColumnListing('account_categories')))
        );

        return DB::table('account_categories')->where('name', 'Expenses')->value('id');
    }

    private function supplierPayload(array $payload)
    {
        return array_intersect_key($payload, array_flip(Schema::getColumnListing('suppliers')));
    }

    private function defaultCompanyId()
    {
        if (Schema::hasTable('companies')) {
            $id = DB::table('companies')->orderBy('id')->value('id');
            if ($id) {
                return $id;
            }
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
}
