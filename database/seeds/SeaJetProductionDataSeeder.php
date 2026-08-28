<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeaJetProductionDataSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            $now = now();
            $companyId = $this->company($now);
            $branches = $this->branches($companyId, $now);

            $this->currencies($now);
            $accounts = $this->accounts($companyId, $now);
            $this->taxCodes($companyId, $accounts, $now);
            $this->customers($companyId, $now);
            $this->suppliers($companyId, $accounts, $now);
            $this->items($companyId, $now);
            $this->associateAdmin($companyId, $branches['MAIN'], $now);
        });
    }

    private function company($now)
    {
        $this->upsert('companies', ['code' => 'SJ'], [
            'code' => 'SJ',
            'name' => 'SEA-JET INTERNATIONAL FORWARDERS INC.',
            'tin' => '000-143-896-00000',
            'bir_2307_payor_representative_name' => 'Gina C. Boloy',
            'bir_2307_payor_representative_title' => 'Finance Manager',
            'bir_2307_payor_representative_tin' => '293-263-579-000',
            'address' => 'UNIT 18 AND 19 BUILDING B CARGO VILLAGE COMPLEX NAIA AVE. BRGY. STO. NIÑO, PARAÑAQUE CITY',
            'base_currency' => 'PHP',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('companies')->where('code', 'SJ')->value('id');
    }

    private function branches($companyId, $now)
    {
        $rows = [
            'MAIN' => ['Manila', 'UNIT 18 AND 19 BUILDING B CARGO VILLAGE COMPLEX NAIA AVE. BRGY. STO. NIÑO, PARAÑAQUE CITY'],
            'PAM' => ['Pampanga', 'UNIT 1 ESSEL PARK BLDG. MC ARTHUR HI-WAY, TELABASTAGAN, CITY OF SAN FERNANDO, PAMPANGA'],
            'CEB' => ['Cebu', 'ROOM 2 A&B BTDT BLDG., A.C. CORTES AVE., IBABAO, MANDAUE CITY, CEBU'],
        ];

        $ids = [];
        foreach ($rows as $code => $row) {
            $this->upsert('branches', ['company_id' => $companyId, 'code' => $code], [
                'company_id' => $companyId,
                'code' => $code,
                'name' => $row[0],
                'address' => $row[1],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$code] = DB::table('branches')
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->value('id');
        }

        return $ids;
    }

    private function currencies($now)
    {
        foreach ([
            ['PHP', 'Philippine Peso', 1, true],
            ['USD', 'US Dollar', 1, false],
        ] as $row) {
            $this->upsert('currencies', ['code' => $row[0]], [
                'code' => $row[0],
                'name' => $row[1],
                'exchange_rate' => $row[2],
                'is_base' => $row[3],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function accounts($companyId, $now)
    {
        $categories = [
            'Assets' => 'debit',
            'Liabilities' => 'credit',
            'Equity' => 'credit',
            'Revenue' => 'credit',
            'Expenses' => 'debit',
        ];
        $categoryIds = [];
        foreach ($categories as $name => $normalBalance) {
            $this->upsert('account_categories', ['name' => $name], [
                'name' => $name,
                'normal_balance' => $normalBalance,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryIds[$name] = DB::table('account_categories')->where('name', $name)->value('id');
        }

        $counters = [
            'Assets' => 1000,
            'Liabilities' => 2000,
            'Equity' => 3000,
            'Revenue' => 4000,
            'Expenses' => 5000,
        ];
        $idsByName = [];

        foreach ($this->source('accounts') as $row) {
            $category = $this->accountCategory($row['type']);
            $code = (string) $counters[$category];
            $counters[$category] += 10;

            $this->upsert('accounts', ['company_id' => $companyId, 'code' => $code], [
                'company_id' => $companyId,
                'account_category_id' => $categoryIds[$category],
                'parent_id' => null,
                'code' => $code,
                'name' => $row['name'],
                'type' => $row['type'],
                'tax_code' => $this->nullable($row['tax']),
                'atc_code' => $this->nullable($row['atc']),
                'opening_balance' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $idsByName[$row['name']] = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->value('id');
        }

        return $idsByName;
    }

    private function taxCodes($companyId, array $accounts, $now)
    {
        foreach ([
            ['VAT12', 'VAT 12%', 12, 'output', 'Vat Output Tax'],
            ['ZERO', 'Zero-rated', 0, 'output', null],
            ['EXEMPT', 'VAT-exempt', 0, 'output', null],
        ] as $row) {
            $this->upsert('tax_codes', ['company_id' => $companyId, 'code' => $row[0]], [
                'company_id' => $companyId,
                'code' => $row[0],
                'name' => $row[1],
                'rate' => $row[2],
                'tax_type' => $row[3],
                'classification' => $row[1],
                'account_id' => $row[4] ? ($accounts[$row[4]] ?? null) : null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function customers($companyId, $now)
    {
        foreach ($this->source('customers') as $index => $row) {
            $code = 'CL-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $taxClassification = $this->customerTaxClassification($row['tax_classification']);
            $this->upsert('customers', ['company_id' => $companyId, 'code' => $code], [
                'company_id' => $companyId,
                'code' => $code,
                'customer_id' => $code,
                'name' => $row['name'],
                'tin' => $this->nullable($row['tin']),
                'tax_classification' => $taxClassification,
                'contact_person' => null,
                'email' => null,
                'phone' => null,
                'billing_address' => $this->nullable($row['address']),
                'credit_limit' => 0,
                'credit_status' => 'Within Limit',
                'payment_terms' => '30 Days',
                'credit_term_days' => 30,
                'currency' => 'PHP',
                'default_class_code' => $this->branchFromAddress($row['address']),
                'default_ar_account_code' => $taxClassification === '12% VAT' ? 'AR_TRADE_BI' : 'AR_TRADE_SOAA',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function suppliers($companyId, array $accounts, $now)
    {
        $defaultExpenseAccountId = $this->defaultExpenseAccountId($accounts);
        foreach ($this->source('suppliers') as $index => $row) {
            $code = 'VND-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $this->upsert('suppliers', ['company_id' => $companyId, 'code' => $code], [
                'company_id' => $companyId,
                'code' => $code,
                'name' => $row['name'],
                'tin' => $this->nullable($row['tin']),
                'tax_classification' => $this->supplierTaxClassification($row['tax_type']),
                'contact_person' => null,
                'email' => null,
                'phone' => null,
                'address' => $this->nullable($row['address']),
                'payment_terms' => '30 Days',
                'payment_term_days' => 30,
                'currency' => 'PHP',
                'preferred_payment_method' => null,
                'default_class_code' => $this->branchFromAddress($row['address']),
                'default_ap_account' => 'Accounts Payable Trade',
                'default_expense_account_id' => $defaultExpenseAccountId,
                'withholding_tax' => '0%',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function items($companyId, $now)
    {
        foreach ($this->source('items') as $index => $row) {
            $code = 'ITM-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $this->upsert('items', ['company_id' => $companyId, 'sku' => $code], [
                'company_id' => $companyId,
                'sku' => $code,
                'barcode' => null,
                'name' => $row['name'],
                'description' => $this->nullable($row['description']),
                'category' => $row['type'] ?: 'Service',
                'tax_type' => $this->nullable($row['tax_type']),
                'account_name' => $this->nullable($row['account']),
                'unit_of_measure' => 'service',
                'standard_cost' => 0,
                'selling_price' => 0,
                'reorder_level' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function associateAdmin($companyId, $branchId, $now)
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $this->upsert('users', ['email' => 'admin@seajet.test'], [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'is_active' => true,
            'updated_at' => $now,
        ]);
    }

    private function accountCategory($type)
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
        if (strpos($type, 'expense') !== false || strpos($type, 'cost of service') !== false || strpos($type, 'cost of goods') !== false) {
            return 'Expenses';
        }

        return 'Assets';
    }

    private function customerTaxClassification($value)
    {
        return stripos($value, 'ZERO') !== false ? 'Zero-rated export services' : '12% VAT';
    }

    private function supplierTaxClassification($value)
    {
        $value = strtoupper(str_replace('-', ' ', $value));
        return strpos($value, 'NON VAT') !== false ? 'VAT-exempt' : '12% VAT';
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

    private function defaultExpenseAccountId(array $accounts)
    {
        foreach (['Operating Expense', 'Miscellaneous', 'Cost of Services'] as $name) {
            if (isset($accounts[$name])) {
                return $accounts[$name];
            }
        }

        return null;
    }

    private function source($name)
    {
        $path = database_path('data/seajet/'.$name.'.json');
        $rows = is_file($path) ? json_decode(file_get_contents($path), true) : null;
        if (!is_array($rows)) {
            throw new \RuntimeException('Missing or invalid SeaJet seed data: '.$path);
        }

        return $rows;
    }

    private function nullable($value)
    {
        return trim((string) $value) === '' ? null : $value;
    }

    private function upsert($table, array $keys, array $values)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $payload = array_intersect_key($values, array_flip(Schema::getColumnListing($table)));
        DB::table($table)->updateOrInsert($keys, $payload);
    }
}
