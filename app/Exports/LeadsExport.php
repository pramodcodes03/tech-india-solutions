<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Exports the Leads list to Excel/CSV using the SAME search + filter
 * logic as LeadController::index() so the file matches what the user
 * sees on screen. Includes the phone number column.
 */
class LeadsExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filters = [])
    {
    }

    public function collection()
    {
        $search = $this->filters['search'] ?? null;

        return Lead::with(['assignedTo', 'product'])
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%")
                    ->orWhere('state', 'like', "%{$s}%")
                    ->orWhere('bid_number', 'like', "%{$s}%")
                    ->orWhere('ra_emd', 'like', "%{$s}%");
            }))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($this->filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($this->filters['city'] ?? null, fn ($q, $v) => $q->where('city', $v))
            ->when($this->filters['from_date'] ?? null, fn ($q, $v) => $q->whereRaw('DATE(COALESCE(lead_date, created_at)) >= ?', [$v]))
            ->when($this->filters['to_date'] ?? null, fn ($q, $v) => $q->whereRaw('DATE(COALESCE(lead_date, created_at)) <= ?', [$v]))
            ->latest()
            ->get()
            ->map(fn (Lead $l) => [
                $l->code,
                $l->name,
                $l->company,
                $l->phone,
                $l->email,
                $l->city,
                $l->state,
                $l->bid_number,
                $l->ra_emd,
                $l->product?->name,
                Lead::sourceLabel($l->source),
                ucfirst($l->status),
                $l->assignedTo?->name,
                $l->expected_value,
                optional($l->lead_date ?? $l->created_at)->format('Y-m-d'),
                optional($l->next_follow_up_at)->format('Y-m-d'),
            ]);
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Company', 'Phone', 'Email', 'City', 'State', 'Bid Number', 'RA/EMD',
            'Product', 'Source', 'Status', 'Assigned To', 'Expected Value', 'Lead Received Date', 'Next Follow-up'];
    }
}
