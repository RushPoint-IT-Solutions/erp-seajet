<?php

namespace App\Console\Commands;

use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class ImportItemsSeajet extends Command
{
    protected $signature = 'seajet:import-items {path=C:\Users\Thinkpad\Documents\seajet\masterlists\ITEMS.xlsx}';

    protected $description = 'Replace the item masterlist from the SeaJet workbook.';

    public function handle()
    {
        $path = $this->argument('path');

        if (!is_file($path)) {
            $this->error('Workbook not found: '.$path);
            return 1;
        }

        $rows = $this->readItemRows($path);
        if (count($rows) === 0) {
            $this->error('No item rows found in workbook.');
            return 1;
        }

        $companyId = $this->defaultCompanyId();
        $now = now();
        $stats = [
            'deleted_items' => 0,
            'inserted_items' => 0,
            'service' => 0,
            'inventory' => 0,
        ];

        DB::transaction(function () use ($rows, $companyId, $now, &$stats) {
            $itemIds = DB::table('items')->where('company_id', $companyId)->pluck('id')->all();
            $stats['deleted_items'] = count($itemIds);

            $this->deleteWhereIn('inventory_balances', 'item_id', $itemIds);
            $this->deleteWhereIn('inventory_movements', 'item_id', $itemIds);
            $this->nullItemReference('sales_document_lines', 'item_id', $itemIds);
            $this->nullItemReference('purchase_document_lines', 'item_id', $itemIds);

            if (!empty($itemIds)) {
                DB::table('items')->whereIn('id', $itemIds)->delete();
            }

            foreach ($rows as $index => $row) {
                $sku = 'ITM-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
                $category = $row['type'] ?: 'Service';

                DB::table('items')->insert([
                    'company_id' => $companyId,
                    'sku' => $sku,
                    'barcode' => null,
                    'name' => $row['item'],
                    'category' => $category,
                    'unit_of_measure' => strtolower($category) === 'service' ? 'service' : 'pcs',
                    'standard_cost' => 0,
                    'selling_price' => 0,
                    'reorder_level' => 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $stats['inserted_items']++;
                strtolower($category) === 'service' ? $stats['service']++ : $stats['inventory']++;
            }
        });

        $this->info('Item masterlist imported.');
        $this->line('Deleted items: '.$stats['deleted_items']);
        $this->line('Inserted items: '.$stats['inserted_items']);
        $this->line('Type counts: Service '.$stats['service'].', Other '.$stats['inventory']);

        return 0;
    }

    private function readItemRows($path)
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

            $item = $this->clean($cells['D'] ?? '');
            if ($item === '') {
                continue;
            }

            $rows[] = [
                'type' => $this->clean($cells['C'] ?? ''),
                'item' => $item,
                'description' => $this->clean($cells['E'] ?? ''),
                'tax_type' => $this->clean($cells['F'] ?? ''),
                'account' => $this->clean($cells['G'] ?? ''),
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

    private function nullItemReference($table, $column, array $itemIds)
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || empty($itemIds)) {
            return;
        }

        DB::table($table)->whereIn($column, $itemIds)->update([$column => null]);
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
