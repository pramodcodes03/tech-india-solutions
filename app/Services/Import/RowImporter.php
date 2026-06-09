<?php

namespace App\Services\Import;

/**
 * Contract every bulk importer implements so the standardized framework can
 * drive the validate → preview → confirm → log pipeline uniformly across
 * modules (employees, payroll adjustments, leave balances, …).
 */
interface RowImporter
{
    /** Unique key used in routes/sessions, e.g. "employees". */
    public function key(): string;

    /** Human label shown in the UI. */
    public function label(): string;

    /** Permission required to run this import. */
    public function permission(): string;

    /** Expected header columns (also the downloadable template header). */
    public function templateHeaders(): array;

    /** One example data row for the template. */
    public function sampleRow(): array;

    /**
     * Validate a single associative row (lowercased header => value).
     *
     * @return string[] list of error messages (empty = valid)
     */
    public function validateRow(array $row, int $businessId): array;

    /** Persist a single validated row. */
    public function importRow(array $row, int $businessId): void;
}
