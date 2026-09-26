<?php

namespace Modules\Finance\Services\Import;

/**
 * One kind of data that can be imported from a spreadsheet.
 */
interface Importer
{
    public function label(): string;

    /**
     * Columns of the template: name => [required, what it holds].
     *
     * @return array<string, array{0: bool, 1: string}>
     */
    public function columns(): array;

    /**
     * Example rows for the downloadable template.
     *
     * @return list<array<string, string>>
     */
    public function example(): array;

    /**
     * Check every row against the company's data and the rest of the file.
     *
     * @param  list<array{line: int, data: array<string, string>}>  $rows
     * @param  array<string, mixed>  $options
     * @return array{rows: list<array{line: int, data: array<string, string>, action: string, errors: list<string>}>, errors: list<string>}
     */
    public function validate(array $rows, array $options): array;

    /**
     * Import rows that passed validation, inside the caller's transaction; returns a one-line summary.
     *
     * @param  list<array{line: int, data: array<string, string>, action: string, errors: list<string>}>  $rows
     * @param  array<string, mixed>  $options
     */
    public function import(array $rows, array $options): string;
}
