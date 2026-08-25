<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/export_seajet_seed_data.php <source-directory> <output-directory>\n");
    exit(1);
}

$sourceDirectory = rtrim($argv[1], "\\/");
$outputDirectory = rtrim($argv[2], "\\/");

$sources = [
    'accounts' => [$sourceDirectory.DIRECTORY_SEPARATOR.'CHART OF ACCOUNTS.xlsx', 2, [
        'name' => 'C', 'type' => 'D', 'tax' => 'E', 'atc' => 'F',
    ]],
    'customers' => [$sourceDirectory.DIRECTORY_SEPARATOR.'CLIENTS.xlsx', 2, [
        'tax_classification' => 'B', 'name' => 'C', 'address' => 'D', 'tin' => 'E',
    ]],
    'items' => [$sourceDirectory.DIRECTORY_SEPARATOR.'ITEMS.xlsx', 2, [
        'type' => 'C', 'name' => 'D', 'description' => 'E', 'tax_type' => 'F', 'account' => 'G',
    ]],
    'suppliers' => [$sourceDirectory.DIRECTORY_SEPARATOR.'VENDOR.xlsx', 2, [
        'name' => 'B', 'address' => 'C', 'tin' => 'D', 'tax_type' => 'E',
    ]],
];

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
    fwrite(STDERR, 'Unable to create output directory: '.$outputDirectory."\n");
    exit(1);
}

foreach ($sources as $name => $definition) {
    list($file, $firstDataRow, $mapping) = $definition;
    if (!is_file($file)) {
        fwrite(STDERR, 'Workbook not found: '.$file."\n");
        exit(1);
    }

    $rows = readFirstSheet($file, $firstDataRow, $mapping);
    $rows = array_values(array_filter($rows, function ($row) {
        return isset($row['name']) && $row['name'] !== '';
    }));

    $target = $outputDirectory.DIRECTORY_SEPARATOR.$name.'.json';
    file_put_contents($target, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $duplicates = duplicateNames($rows);
    echo $name.': '.count($rows).' rows';
    echo $duplicates ? '; duplicate names: '.implode(', ', $duplicates) : '; duplicate names: none';
    echo "\n";
}

function readFirstSheet($file, $firstDataRow, array $mapping)
{
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) {
        throw new RuntimeException('Unable to open workbook: '.$file);
    }

    $sharedStrings = sharedStrings($zip);
    $contents = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    $rows = strlen($contents) > 10000000
        ? valuedCellsFromLargeSheet($contents, $sharedStrings)
        : valuedCellsFromSheet($contents, $sharedStrings);

    $result = [];
    foreach ($rows as $rowNumber => $cells) {
        if ($rowNumber < $firstDataRow) {
            continue;
        }

        $row = [];
        foreach ($mapping as $field => $column) {
            $row[$field] = clean($cells[$column] ?? '');
        }
        $result[] = $row;
    }

    return $result;
}

function sharedStrings(ZipArchive $zip)
{
    $contents = $zip->getFromName('xl/sharedStrings.xml');
    if ($contents === false) {
        return [];
    }

    $xml = new DOMDocument();
    $xml->loadXML($contents);
    $strings = [];
    foreach ($xml->getElementsByTagName('si') as $item) {
        $strings[] = clean($item->textContent);
    }

    return $strings;
}

function valuedCellsFromSheet($contents, array $sharedStrings)
{
    $xml = new DOMDocument();
    $xml->loadXML($contents);
    $rows = [];

    foreach ($xml->getElementsByTagName('row') as $row) {
        $rowNumber = (int) $row->getAttribute('r');
        foreach ($row->getElementsByTagName('c') as $cell) {
            $column = preg_replace('/\d+/', '', $cell->getAttribute('r'));
            $value = cellValue($cell, $sharedStrings);
            if ($value !== '') {
                $rows[$rowNumber][$column] = $value;
            }
        }
    }

    return $rows;
}

function valuedCellsFromLargeSheet($contents, array $sharedStrings)
{
    $rows = [];
    preg_match_all('/<c\b(?![^>]*\/\>)([^>]*\br="([A-Z]+)(\d+)"[^>]*)>.*?<v>(.*?)<\/v>.*?<\/c>/s', $contents, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $value = html_entity_decode(strip_tags($match[4]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        if (preg_match('/\bt="s"/', $match[1])) {
            $value = $sharedStrings[(int) $value] ?? '';
        }
        $value = clean($value);
        if ($value !== '') {
            $rows[(int) $match[3]][$match[2]] = $value;
        }
    }

    return $rows;
}

function cellValue(DOMElement $cell, array $sharedStrings)
{
    if ($cell->getAttribute('t') === 'inlineStr') {
        $nodes = $cell->getElementsByTagName('is');
        return $nodes->length ? clean($nodes->item(0)->textContent) : '';
    }

    $nodes = $cell->getElementsByTagName('v');
    $value = $nodes->length ? $nodes->item(0)->textContent : '';
    if ($cell->getAttribute('t') === 's') {
        $value = $sharedStrings[(int) $value] ?? '';
    }

    return clean($value);
}

function duplicateNames(array $rows)
{
    $counts = [];
    foreach ($rows as $row) {
        $key = mb_strtoupper($row['name']);
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    return array_keys(array_filter($counts, function ($count) {
        return $count > 1;
    }));
}

function clean($value)
{
    return trim(preg_replace('/\s+/', ' ', (string) $value));
}
