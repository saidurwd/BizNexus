<?php

namespace Modules\Finance\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams rows to the browser as an .xlsx workbook. Pass amounts as floats so Excel treats them as numbers and codes
 * as strings so they keep their leading zeros.
 */
class SpreadsheetExport
{
    /**
     * @param  list<string>  $title  lines written above the table (report name, company, period)
     * @param  list<string>  $headings
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public static function download(string $filename, array $title, array $headings, iterable $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($title, $headings, $rows) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $bold = new Style(fontBold: true);

            foreach ($title as $line) {
                $writer->addRow(Row::fromValuesWithStyle([$line], $bold));
            }

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValuesWithStyle($headings, $bold));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
        ]);
    }
}
