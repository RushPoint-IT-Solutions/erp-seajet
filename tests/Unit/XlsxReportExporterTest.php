<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Reports\XlsxReportExporter;
use PHPUnit\Framework\TestCase;

class XlsxReportExporterTest extends TestCase
{
    public function test_export_has_valid_xml_and_worksheet_elements_in_schema_order()
    {
        $sheet = [
            'name' => 'DISBURSEMENT',
            'company' => 'Sea-Jet & Company',
            'title' => 'CASH DISBURSEMENT BOOK',
            'columns' => ['DATE', 'CHECK#', 'PARTICULARS', 'AMOUNT'],
            'rows' => [[
                'DATE' => '2026-10-01', 'CHECK#' => '000471072',
                'PARTICULARS' => "Freight & handling\nMemo", 'AMOUNT' => 6600.25,
            ]],
        ];
        $emptySheet = $sheet;
        $emptySheet['name'] = 'EMPTY';
        $emptySheet['rows'] = [];
        $path = (new XlsxReportExporter())->create([$sheet, $emptySheet]);
        $zip = new \ZipArchive();
        try {
            $this->assertTrue($zip->open($path, \ZipArchive::CHECKCONS));
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                $document = new \DOMDocument();
                $this->assertTrue($document->loadXML($zip->getFromIndex($index)), $name);
            }
            foreach ([1, 2] as $number) {
                $document = new \DOMDocument();
                $document->loadXML($zip->getFromName('xl/worksheets/sheet'.$number.'.xml'));
                $elements = [];
                foreach ($document->documentElement->childNodes as $child) {
                    $elements[] = $child->localName;
                }
                $this->assertSame([
                    'sheetViews', 'sheetFormatPr', 'cols', 'sheetData',
                    'autoFilter', 'mergeCells', 'pageMargins', 'pageSetup',
                ], $elements);
                $this->assertSame($number === 1 ? 'A5:D6' : 'A5:D5',
                    $document->getElementsByTagName('autoFilter')->item(0)->getAttribute('ref'));
            }
        } finally {
            $zip->close();
            unlink($path);
        }
    }
}
