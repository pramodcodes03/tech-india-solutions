<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\GenericArrayExport;
use App\Support\Tenancy\CurrentBusiness;
use App\Support\TrackerFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared list plumbing for the three operational trackers: sorting on any
 * column, and the Excel / PDF export of whatever the register is currently
 * showing.
 *
 * The exports deliberately run off the same query the screen does, so what a
 * user downloads is exactly the filtered, sorted set they were looking at.
 */
trait ExportsTrackerRegisters
{
    /**
     * Order a register by a whitelisted column.
     *
     * @param  array<string,string>  $allowed  request key => column expression
     * @return array{sort:string, dir:string} echoed back to the view so the
     *                                        header arrows point the right way
     */
    protected function applySort(
        Builder $query,
        Request $request,
        array $allowed,
        string $default,
        array $secondary = [],
    ): array {
        $sort = (string) $request->input('sort', $default);
        if (! array_key_exists($sort, $allowed)) {
            $sort = $default;
        }

        $dir = strtolower((string) $request->input('dir')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($allowed[$sort], $dir);

        // Tie-breakers the register wants applied *before* the id, so rows that
        // share the sorted value still come out in a meaningful order — the
        // break sheet uses this to keep one employee's segments for a date
        // adjacent, which is what makes their combined total readable.
        foreach ($secondary as $column) {
            if ($column !== $allowed[$sort]) {
                $query->orderBy($column);
            }
        }

        // A secondary key keeps pagination stable when the sort column ties
        // (e.g. twenty breaks all on the same date).
        if ($allowed[$sort] !== $query->getModel()->getQualifiedKeyName()) {
            $query->orderBy($query->getModel()->getQualifiedKeyName(), 'desc');
        }

        return ['sort' => $sort, 'dir' => $dir];
    }

    /**
     * Stream the current register as Excel or PDF.
     *
     * @param  array<int,string>  $headings
     * @param  array<int,array>  $rows
     * @param  array<string,string>  $summary  label => value strip printed above the PDF table
     */
    protected function streamExport(
        Request $request,
        TrackerFilter $filter,
        string $title,
        array $headings,
        array $rows,
        array $summary = [],
    ): Response {
        $slug = str($title)->slug().'-'.$filter->slug();

        if ($request->get('format') === 'pdf') {
            $business = app(CurrentBusiness::class)->get();

            $pdf = Pdf::loadView('admin.hr.trackers.pdf', [
                'title' => $title,
                'period' => $filter->label(),
                'headings' => $headings,
                'rows' => $rows,
                'summary' => $summary,
                'business' => $business,
            ])->setPaper('a4', count($headings) > 7 ? 'landscape' : 'portrait');

            return $pdf->download("{$slug}.pdf");
        }

        return Excel::download(new GenericArrayExport($headings, $rows), "{$slug}.xlsx");
    }
}
