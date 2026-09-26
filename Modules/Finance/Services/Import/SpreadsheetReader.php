<?php

namespace Modules\Finance\Services\Import;

use Illuminate\Support\Str;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Reads the first sheet of a CSV or XLSX file into rows keyed by snake_case header ("Account Code" becomes
 * account_code). Blank rows are skipped; line numbers match what the user sees in their spreadsheet.
 */
class SpreadsheetReader
{
    public const MAX_ROWS = 5000;

    /**
     * @return list<array{line: int, data: array<string, string>}>
     */
    public function rows(string $path, string $extension): array
    {
        $reader = strtolower($extension) === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);
        $headers = null;
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $values = array_map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : trim((string) $value), $row->toArray());

                    if ($headers === null) {
                        $headers = array_map(fn (string $header) => Str::snake(Str::lower(preg_replace('/^\x{FEFF}/u', '', $header))), $values);

                        continue;
                    }

                    if (implode('', $values) === '') {
                        continue;
                    }

                    if (count($rows) >= self::MAX_ROWS) {
                        throw new InvalidAccountingTransactionException(__('A file may have at most :max rows.', ['max' => self::MAX_ROWS]));
                    }

                    $rows[] = ['line' => $index, 'data' => array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), ''))];
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }
}
