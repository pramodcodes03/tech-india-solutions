<?php

namespace App\Console\Commands;

use App\Models\BreakSheet;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove break-sheet rows that are duplicates of one another.
 *
 * Before the importer keyed its writes (see BreakSheetImporter::importRow),
 * re-sending a sheet created a second copy of every row instead of updating
 * the first. That is invisible on a register that shows one segment per line,
 * but it doubles the combined per-employee-per-day total the tracker reports.
 *
 * A duplicate here means: same business, same employee, same date, same
 * out_time. One employee cannot start two breaks at the same minute, so any
 * such pair is a re-import rather than two real breaks. The OLDEST row of each
 * group is kept (it carries the original created_at and any activity-log
 * history); the later copies go.
 *
 * Reports by default and changes nothing. Pass --force to actually delete.
 */
class DeduplicateBreakSheets extends Command
{
    protected $signature = 'break-sheets:deduplicate
                            {--force : Delete the duplicates instead of only reporting them}';

    protected $description = 'Find (and optionally remove) duplicated break-sheet rows from repeated imports';

    public function handle(): int
    {
        $groups = BreakSheet::withoutGlobalScopes()
            ->select('business_id', 'employee_id', 'break_date', 'out_time')
            ->selectRaw('COUNT(*) as copies, MIN(id) as keep_id')
            ->groupBy('business_id', 'employee_id', 'break_date', 'out_time')
            ->having('copies', '>', 1)
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicated break-sheet rows found.');

            return self::SUCCESS;
        }

        $doomed = [];
        foreach ($groups as $group) {
            $ids = BreakSheet::withoutGlobalScopes()
                ->where('business_id', $group->business_id)
                ->where('employee_id', $group->employee_id)
                ->whereDate('break_date', $group->break_date)
                ->where('out_time', $group->out_time)
                ->where('id', '!=', $group->keep_id)
                ->pluck('id')
                ->all();

            $doomed = array_merge($doomed, $ids);
        }

        $this->warn(sprintf(
            '%d duplicated break%s across %d group%s — %d row%s would be removed, %d kept.',
            count($doomed) + $groups->count(),
            count($doomed) + $groups->count() === 1 ? '' : 's',
            $groups->count(),
            $groups->count() === 1 ? '' : 's',
            count($doomed),
            count($doomed) === 1 ? '' : 's',
            $groups->count(),
        ));

        $this->newLine();
        $this->table(
            ['Employee', 'Date', 'Out time', 'Copies'],
            $groups->take(15)->map(fn ($g) => [
                optional(Employee::withoutGlobalScopes()->find($g->employee_id))->employee_code ?? $g->employee_id,
                substr((string) $g->break_date, 0, 10),
                substr((string) $g->out_time, 0, 5),
                $g->copies,
            ])->all(),
        );

        if ($groups->count() > 15) {
            $this->line(sprintf('  … and %d more groups.', $groups->count() - 15));
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->info('Dry run — nothing was changed. Re-run with --force to delete the duplicates.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($doomed) {
            BreakSheet::withoutGlobalScopes()->whereIn('id', $doomed)->delete();
        });

        $this->newLine();
        $this->info(sprintf('Removed %d duplicated break row%s.', count($doomed), count($doomed) === 1 ? '' : 's'));

        return self::SUCCESS;
    }
}
