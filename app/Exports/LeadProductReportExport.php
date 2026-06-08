<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeadProductReportExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filters = [])
    {
    }

    public function collection()
    {
        return Lead::with(['product', 'assignedTo'])
            ->when($this->filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
            ->when($this->filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($this->filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('lead_date', '>=', $v))
            ->when($this->filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('lead_date', '<=', $v))
            ->latest('lead_date')
            ->get()
            ->map(fn (Lead $l) => [
                $l->code,
                $l->name,
                $l->company,
                $l->product?->name,
                Lead::sourceLabel($l->source),
                ucfirst($l->status),
                $l->assignedTo?->name,
                $l->expected_value,
                optional($l->lead_date)->format('Y-m-d'),
                optional($l->next_follow_up_at)->format('Y-m-d'),
                $l->age_in_days,
            ]);
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Company', 'Product', 'Source', 'Status', 'Assigned To',
            'Expected Value', 'Lead Date', 'Next Follow-up', 'Age (days)'];
    }
}
