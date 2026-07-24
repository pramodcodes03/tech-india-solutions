<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Reusable export for any pre-built [headings, rows] dataset — used by the HR
 * reports hub and the custom report builder so each report doesn't need its
 * own Export class.
 */
class GenericArrayExport implements FromCollection, WithHeadings
{
    public function __construct(private array $headings, private array $rows)
    {
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
