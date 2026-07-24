<?php

namespace App\Exports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CandidateReportExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filters = [])
    {
    }

    public function collection()
    {
        return Candidate::with(['stage', 'designation', 'department', 'referrer', 'batch'])
            ->when($this->filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($this->filters['stage_id'] ?? null, fn ($q, $v) => $q->where('stage_id', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['from'] ?? null, fn ($q, $v) => $q->whereDate('applied_at', '>=', $v))
            ->when($this->filters['to'] ?? null, fn ($q, $v) => $q->whereDate('applied_at', '<=', $v))
            ->latest()
            ->get()
            ->map(fn (Candidate $c) => [
                $c->candidate_code,
                $c->full_name,
                $c->email,
                $c->phone,
                $c->source_label,
                $c->designation?->name,
                $c->department?->name,
                $c->stage?->name,
                ucfirst($c->status),
                $c->referrer?->full_name,
                $c->batch?->name,
                optional($c->applied_at)->format('Y-m-d'),
                $c->expected_ctc,
            ]);
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Email', 'Phone', 'Source', 'Designation', 'Department',
            'Stage', 'Status', 'Referred By', 'Batch', 'Applied On', 'Expected CTC'];
    }
}
