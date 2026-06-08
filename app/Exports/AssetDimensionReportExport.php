<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssetDimensionReportExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filters = [])
    {
    }

    public function collection()
    {
        return Asset::with(['category', 'location', 'custodian'])
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['condition_rating'] ?? null, fn ($q, $v) => $q->where('condition_rating', $v))
            ->when($this->filters['location_id'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->when($this->filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->orderBy('asset_code')
            ->get()
            ->map(fn (Asset $a) => [
                $a->asset_code,
                $a->name,
                $a->serial_number,
                $a->category?->name,
                $a->location?->name,
                $a->custodian?->full_name,
                $a->status,
                $a->condition_rating,
                $a->purchase_cost,
                $a->current_book_value,
            ]);
    }

    public function headings(): array
    {
        return ['Asset Code', 'Name', 'Serial', 'Category', 'Location', 'Custodian',
            'Status', 'Condition', 'Purchase Cost', 'Book Value'];
    }
}
