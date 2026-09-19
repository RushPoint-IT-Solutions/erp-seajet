<?php

namespace App\Services\Accounting;

use DateTimeImmutable;
use RuntimeException;
use ZipArchive;

class XlsxOpeningBalanceReader
{
    public function read($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The uploaded file is not a readable XLSX workbook.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheets = $this->worksheetPaths($zip);
            $rows = [];

            foreach ($sheets as $sheetName => $sheetPath) {
                $xml = $zip->getFromName($sheetPath);
                if ($xml === false) {
                    continue;
                }

                foreach ($this->worksheetRows($xml, $sharedStrings) as $rowNumber => $cells) {
                    $rows[] = [
                        'sheet_name' => $sheetName,
                        'row_number' => $rowNumber,
                        'cells' => $cells,
                    ];
                }
            }

            return $this->normalizeRows($rows);
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip)
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = $this->xml($xml, 'shared strings');
        $strings = [];
        foreach ($document->xpath('//*[local-name()="si"]') as $item) {
            $parts = [];
            foreach ($item->xpath('.//*[local-name()="t"]') as $text) {
                $parts[] = (string) $text;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function worksheetPaths(ZipArchive $zip)
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('The workbook is missing its worksheet index.');
        }

        $relationships = [];
        foreach ($this->xml($relsXml, 'workbook relationships')->xpath('//*[local-name()="Relationship"]') as $relationship) {
            $attributes = $relationship->attributes();
            $relationships[(string) $attributes['Id']] = $this->normalizeZipPath('xl/'.(string) $attributes['Target']);
        }

        $paths = [];
        foreach ($this->xml($workbookXml, 'workbook')->xpath('//*[local-name()="sheet"]') as $sheet) {
            $attributes = $sheet->attributes();
            $relationshipAttributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationshipId = (string) $relationshipAttributes['id'];
            if ($relationshipId !== '' && isset($relationships[$relationshipId])) {
                $paths[(string) $attributes['name']] = $relationships[$relationshipId];
            }
        }

        if (empty($paths)) {
            throw new RuntimeException('No worksheets were found in the uploaded workbook.');
        }

        return $paths;
    }

    private function worksheetRows($xml, array $sharedStrings)
    {
        $document = $this->xml($xml, 'worksheet');
        $rows = [];

        foreach ($document->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
            $rowNumber = (int) $row->attributes()['r'];
            $cells = [];
            foreach ($row->xpath('./*[local-name()="c"]') as $cell) {
                $reference = (string) $cell->attributes()['r'];
                $column = $this->columnNumber($reference);
                if ($column < 1 || $column > 20) {
                    continue;
                }
                $cells[$column] = $this->cellValue($cell, $sharedStrings);
            }
            $rows[$rowNumber] = $cells;
        }

        return $rows;
    }

    private function cellValue($cell, array $sharedStrings)
    {
        $type = (string) $cell->attributes()['t'];
        $valueNodes = $cell->xpath('./*[local-name()="v"]');
        $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }
        if ($type === 'inlineStr') {
            $parts = [];
            foreach ($cell->xpath('.//*[local-name()="t"]') as $text) {
                $parts[] = (string) $text;
            }
            return implode('', $parts);
        }
        if ($type === 'str') {
            return $value;
        }
        if ($type === 'b') {
            return $value === '1';
        }
        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    private function normalizeRows(array $worksheetRows)
    {
        $bySheet = [];
        foreach ($worksheetRows as $row) {
            $bySheet[$row['sheet_name']][$row['row_number']] = $row['cells'];
        }

        $normalized = [];
        foreach ($bySheet as $sheetName => $rows) {
            $headerRow = $this->headerRow($rows);
            if ($headerRow === null) {
                continue;
            }
            $headers = $this->headerMap($rows[$headerRow]);

            foreach ($rows as $rowNumber => $cells) {
                if ($rowNumber <= $headerRow) {
                    continue;
                }

                $branch = $this->text($this->mappedCell($cells, $headers, ['BRANCH'], 1));
                $accountType = $this->text($this->mappedCell($cells, $headers, ['ACCOUNT TYPE', 'ACCOUNT'], 2));
                $documentType = $this->text($this->mappedCell($cells, $headers, ['DOCUMENT TYPE', 'TYPE'], 3));
                $partyName = $this->text($this->mappedCell($cells, $headers, ['CUSTOMER NAME', 'VENDOR NAME', 'SUPPLIER NAME', 'PARTY NAME'], 5));
                $reference = $this->text($this->mappedCell($cells, $headers, ['NUMBER', 'REFERENCE', 'REFERENCE NO', 'DOCUMENT NO'], 6));
                $rawDate = $this->mappedCell($cells, $headers, ['DATE', 'DOCUMENT DATE'], 4);
                $amount = $this->number($this->mappedCell($cells, $headers, ['OPENING BALANCE', 'AMOUNT', 'BALANCE'], 7));

                if ($branch === '' && $accountType === '' && $partyName === '' && $reference === '') {
                    continue;
                }

                $foreignAmount = $this->number($this->mappedCell($cells, $headers, ['USD AMOUNT', 'FOREIGN AMOUNT'], 8));
                $exchangeRate = $this->number($this->mappedCell($cells, $headers, ['EXCHANGE RATE', 'RATE'], 9));
                $currency = strtoupper($this->text($this->mappedCell($cells, $headers, ['CURRENCY'], 0)));
                if ($currency === '') {
                    $currency = $foreignAmount > 0 && $exchangeRate > 0 ? 'USD' : 'PHP';
                }

                $normalized[] = [
                    'sheet_name' => $sheetName,
                    'row_number' => $rowNumber,
                    'branch' => $branch,
                    'account_type' => $accountType,
                    'document_type' => $documentType,
                    'source_date' => $this->date($rawDate),
                    'party_name' => $partyName,
                    'reference_no' => $reference,
                    'base_amount' => $amount,
                    'currency' => in_array($currency, ['PHP', 'USD'], true) ? $currency : $currency,
                    'foreign_amount' => $foreignAmount > 0 ? $foreignAmount : null,
                    'exchange_rate' => $exchangeRate > 0 ? $exchangeRate : 1,
                ];
            }
        }

        if (empty($normalized)) {
            throw new RuntimeException('No opening-balance rows were found. Check that the workbook has BRANCH, ACCOUNT TYPE, Date, party, NUMBER, and OPENING BALANCE columns.');
        }

        return $normalized;
    }

    private function headerRow(array $rows)
    {
        foreach (array_slice($rows, 0, 20, true) as $rowNumber => $cells) {
            $headers = array_map(function ($value) {
                return $this->normalizeHeader($value);
            }, $cells);
            if (in_array('BRANCH', $headers, true)
                && in_array('ACCOUNT TYPE', $headers, true)
                && in_array('OPENING BALANCE', $headers, true)) {
                return $rowNumber;
            }
        }

        return null;
    }

    private function headerMap(array $cells)
    {
        $headers = [];
        foreach ($cells as $column => $value) {
            $header = $this->normalizeHeader($value);
            if ($header !== '') {
                $headers[$header] = $column;
            }
        }

        return $headers;
    }

    private function mappedCell(array $cells, array $headers, array $names, $fallbackColumn)
    {
        foreach ($names as $name) {
            if (isset($headers[$name])) {
                return $cells[$headers[$name]] ?? null;
            }
        }

        return $fallbackColumn > 0 ? ($cells[$fallbackColumn] ?? null) : null;
    }

    private function date($value)
    {
        if (is_numeric($value) && (float) $value > 0) {
            $timestamp = (int) round(((float) $value - 25569) * 86400);
            return (new DateTimeImmutable('@'.$timestamp))->format('Y-m-d');
        }

        $timestamp = strtotime(trim((string) $value));
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function number($value)
    {
        if (is_numeric($value)) {
            return round((float) $value, 4);
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($clean) ? round((float) $clean, 4) : 0.0;
    }

    private function text($value)
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }

    private function normalizeHeader($value)
    {
        return trim(preg_replace('/\s+/', ' ', strtoupper((string) $value)));
    }

    private function columnNumber($reference)
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? '');
        $number = 0;
        for ($index = 0; $index < strlen($letters); $index++) {
            $number = ($number * 26) + (ord($letters[$index]) - 64);
        }

        return $number;
    }

    private function normalizeZipPath($path)
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', ltrim($path, '/'))) as $part) {
            if ($part === '..') {
                array_pop($parts);
            } elseif ($part !== '' && $part !== '.') {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }

    private function xml($contents, $label)
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($xml === false) {
            throw new RuntimeException('The XLSX '.$label.' XML is invalid.');
        }

        return $xml;
    }
}
