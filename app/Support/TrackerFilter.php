<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The Monthly / Date-wise / Yearly period filter shared by all three trackers.
 *
 * One object parses the request, narrows the query, labels itself for the
 * screen heading and the export filename, and hands back the query string so
 * "Export", "Analytics" and pagination links all stay on the same period.
 *
 * Defaults to the current month — opening a register should show this month's
 * rows, not every row ever recorded.
 */
class TrackerFilter
{
    public const MODES = [
        'month' => 'Monthly',
        'date' => 'Date-wise',
        'range' => 'Date Range',
        'year' => 'Yearly',
        'all' => 'All Time',
    ];

    public function __construct(
        public readonly string $mode,
        public readonly ?Carbon $start,
        public readonly ?Carbon $end,
        public readonly string $rawMonth = '',
        public readonly string $rawDate = '',
        public readonly string $rawFrom = '',
        public readonly string $rawTo = '',
        public readonly string $rawYear = '',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $mode = $request->input('period', 'month');
        if (! array_key_exists($mode, self::MODES)) {
            $mode = 'month';
        }

        $month = (string) $request->input('month', now()->format('Y-m'));
        $date = (string) $request->input('date', now()->toDateString());
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');
        $year = (string) $request->input('year', now()->format('Y'));

        [$start, $end] = match ($mode) {
            'month' => self::monthBounds($month),
            'date' => self::dayBounds($date),
            'range' => self::rangeBounds($from, $to),
            'year' => self::yearBounds($year),
            default => [null, null],
        };

        return new self($mode, $start, $end, $month, $date, $from, $to, $year);
    }

    /** Constrain a query's date column to the selected period. */
    public function apply(Builder $query, string $column): Builder
    {
        if ($this->start) {
            $query->whereDate($column, '>=', $this->start);
        }
        if ($this->end) {
            $query->whereDate($column, '<=', $this->end);
        }

        return $query;
    }

    /** Human label for the page heading, e.g. "August 2026". */
    public function label(): string
    {
        return match ($this->mode) {
            'month' => $this->start?->format('F Y') ?? 'This month',
            'date' => $this->start?->format('d M Y') ?? 'Today',
            'range' => $this->start && $this->end
                ? $this->start->format('d M Y').' — '.$this->end->format('d M Y')
                : 'All time',
            'year' => $this->start?->format('Y') ?? 'This year',
            default => 'All time',
        };
    }

    /** Filename-safe version of the same label. */
    public function slug(): string
    {
        return str_replace([' ', '—', '/'], ['-', 'to', '-'], strtolower($this->label()));
    }

    /**
     * The filter as query parameters, so every link on the page (export,
     * analytics, pagination, sorting) carries the current period forward.
     */
    public function toQuery(): array
    {
        return array_filter([
            'period' => $this->mode,
            'month' => $this->mode === 'month' ? $this->rawMonth : null,
            'date' => $this->mode === 'date' ? $this->rawDate : null,
            'from' => $this->mode === 'range' ? $this->rawFrom : null,
            'to' => $this->mode === 'range' ? $this->rawTo : null,
            'year' => $this->mode === 'year' ? $this->rawYear : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Number of days covered — used to turn totals into daily averages. */
    public function days(): int
    {
        if (! $this->start || ! $this->end) {
            return 0;
        }

        return (int) $this->start->diffInDays($this->end) + 1;
    }

    private static function monthBounds(string $month): array
    {
        try {
            $base = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $base = now()->startOfMonth();
        }

        return [$base->copy(), $base->copy()->endOfMonth()];
    }

    private static function dayBounds(string $date): array
    {
        try {
            $day = Carbon::parse($date);
        } catch (\Throwable) {
            $day = now();
        }

        return [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
    }

    private static function rangeBounds(string $from, string $to): array
    {
        $start = $from ? Carbon::parse($from) : null;
        $end = $to ? Carbon::parse($to) : null;

        // A backwards range is a typo, not an empty result — swap it.
        if ($start && $end && $start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private static function yearBounds(string $year): array
    {
        $y = ctype_digit($year) ? (int) $year : (int) now()->format('Y');
        $base = Carbon::create($y, 1, 1);

        return [$base->copy()->startOfYear(), $base->copy()->endOfYear()];
    }
}
