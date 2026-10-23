<?php

namespace App\Services\Reports;

use RuntimeException;
use ZipArchive;

class XlsxReportExporter
{
    public function create(array $sheets)
    {
        if (empty($sheets)) {
            throw new RuntimeException('At least one worksheet is required.');
        }

        $path = tempnam(sys_get_temp_dir(), 'seajet-report-');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The Excel workbook could not be created.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->packageRelationships());
        $zip->addFromString('docProps/app.xml', $this->appProperties(count($sheets)));
        $zip->addFromString('docProps/core.xml', $this->coreProperties());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach (array_values($sheets) as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheet($sheet));
        }

        if (!$zip->close()) {
            @unlink($path);
            throw new RuntimeException('The Excel workbook could not be finalized.');
        }

        return $path;
    }

    private function worksheet(array $sheet)
    {
        $columns = array_values($sheet['columns'] ?? []);
        $rows = array_values($sheet['rows'] ?? []);
        $lastColumn = $this->columnName(max(1, count($columns)));
        $xml = $this->xmlHeader().'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';
        $xml .= '<cols>';
        foreach ($columns as $index => $column) {
            $width = strlen((string) $column);
            foreach (array_slice($rows, 0, 100) as $row) {
                $width = max($width, strlen((string) ($row[$column] ?? '')));
            }
            $width = min(45, max(12, $width + 2));
            $xml .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';
        $xml .= $this->rowXml(1, [['value' => $sheet['company'] ?? '', 'style' => 1]], 24);
        $xml .= $this->rowXml(2, [['value' => $sheet['title'] ?? '', 'style' => 2]], 22);
        $xml .= $this->rowXml(3, [['value' => $sheet['period'] ?? '', 'style' => 3]], 20);
        $headerCells = [];
        foreach ($columns as $column) {
            $headerCells[] = ['value' => $column, 'style' => 4];
        }
        $xml .= $this->rowXml(5, $headerCells, 30);

        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach ($columns as $column) {
                $value = $row[$column] ?? '';
                $cells[] = [
                    'value' => $value,
                    'style' => $column === 'DATE' && $this->isIsoDate($value) ? 6 : (is_numeric($value) ? 5 : 0),
                    'date' => $column === 'DATE' && $this->isIsoDate($value),
                ];
            }
            $xml .= $this->rowXml($rowIndex + 6, $cells);
        }

        $xml .= '</sheetData>';
        if (count($columns) > 1) {
            $xml .= '<mergeCells count="3"><mergeCell ref="A1:'.$lastColumn.'1"/><mergeCell ref="A2:'.$lastColumn.'2"/><mergeCell ref="A3:'.$lastColumn.'3"/></mergeCells>';
        }
        $xml .= '<autoFilter ref="A5:'.$lastColumn.max(5, count($rows) + 5).'"/>';
        $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';
        $xml .= '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function rowXml($number, array $cells, $height = null)
    {
        $xml = '<row r="'.$number.'"'.($height ? ' ht="'.$height.'" customHeight="1"' : '').'>';
        foreach ($cells as $index => $cell) {
            $reference = $this->columnName($index + 1).$number;
            $style = (int) ($cell['style'] ?? 0);
            $value = $cell['value'] ?? '';
            if (!empty($cell['date'])) {
                $value = $this->excelDate($value);
                $xml .= '<c r="'.$reference.'" s="'.$style.'"><v>'.$value.'</v></c>';
            } elseif (is_numeric($value) && !is_string($value)) {
                $xml .= '<c r="'.$reference.'" s="'.$style.'"><v>'.(0 + $value).'</v></c>';
            } else {
                $xml .= '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape($value).'</t></is></c>';
            }
        }
        return $xml.'</row>';
    }

    private function styles()
    {
        return $this->xmlHeader().'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4">'
            .'<font><sz val="10"/><name val="Arial"/></font>'
            .'<font><b/><sz val="12"/><name val="Arial"/></font>'
            .'<font><b/><sz val="14"/><name val="Arial"/></font>'
            .'<font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Arial"/></font>'
            .'</fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1D4ED8"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD8DDE6"/></left><right style="thin"><color rgb="FFD8DDE6"/></right><top style="thin"><color rgb="FFD8DDE6"/></top><bottom style="thin"><color rgb="FFD8DDE6"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="7">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>'
            .'<xf numFmtId="14" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function workbook(array $sheets)
    {
        $xml = $this->xmlHeader().'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach (array_values($sheets) as $index => $sheet) {
            $xml .= '<sheet name="'.$this->escape($this->sheetName($sheet['name'] ?? 'Report', $index)).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }
        return $xml.'</sheets></workbook>';
    }

    private function workbookRelationships($sheetCount)
    {
        $xml = $this->xmlHeader().'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }
        $xml .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return $xml.'</Relationships>';
    }

    private function contentTypes($sheetCount)
    {
        $xml = $this->xmlHeader().'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return $xml.'</Types>';
    }

    private function packageRelationships()
    {
        return $this->xmlHeader().'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function appProperties($sheetCount)
    {
        return $this->xmlHeader().'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>SEA-JET ERP</Application><DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop><HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>'.$sheetCount.'</vt:i4></vt:variant></vt:vector></HeadingPairs></Properties>';
    }

    private function coreProperties()
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return $this->xmlHeader().'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>SEA-JET ERP</dc:creator><cp:lastModifiedBy>SEA-JET ERP</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified></cp:coreProperties>';
    }

    private function sheetName($name, $index)
    {
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', (string) $name);
        $name = trim($name, " '\t\n\r\0\x0B");
        return substr($name !== '' ? $name : 'Report '.($index + 1), 0, 31);
    }

    private function columnName($number)
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }
        return $name;
    }

    private function isIsoDate($value)
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    private function excelDate($date)
    {
        return (int) floor(strtotime($date.' UTC') / 86400) + 25569;
    }

    private function escape($value)
    {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $value);
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xmlHeader()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    }
}
