<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give break_sheets the natural key its importer already writes against.
 *
 * BreakSheetImporter keys its writes on (business, employee, date, out_time) —
 * one employee cannot start two breaks at the same minute on the same day — but
 * nothing below the application enforced that, so:
 *
 *   - rows imported before the importer was keyed are still duplicated, and
 *     updateOrCreate() only ever updates the FIRST match, so re-importing the
 *     sheet never converges on them; and
 *   - two imports running at once can both miss on the SELECT and both INSERT.
 *
 * Duplicates are invisible on a register that prints one segment per line, but
 * they double the combined per-employee-per-day total the tracker reports.
 *
 * So: collapse what is already duplicated, then make it unrepeatable.
 */
return new class extends Migration
{
    private const KEY = ['business_id', 'employee_id', 'break_date', 'out_time'];

    public function up(): void
    {
        $this->collapseDuplicates();

        Schema::table('break_sheets', function (Blueprint $table) {
            $table->unique(self::KEY, 'break_sheets_natural_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('break_sheets', function (Blueprint $table) {
            $table->dropUnique('break_sheets_natural_key_unique');
        });
    }

    /**
     * Keep the OLDEST row of each duplicated group — it holds the original
     * created_at and whatever activity-log history points at its id — but carry
     * the NEWEST copy's values onto it first, so the most recently imported
     * version of the break is the one that survives. Then drop the rest.
     */
    private function collapseDuplicates(): void
    {
        $groups = DB::table('break_sheets')
            ->select(self::KEY)
            ->selectRaw('MIN(id) as keep_id, MAX(id) as newest_id, COUNT(*) as copies')
            ->groupBy(self::KEY)
            ->having('copies', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $newest = DB::table('break_sheets')->find($group->newest_id);

            DB::table('break_sheets')->where('id', $group->keep_id)->update([
                'in_time' => $newest->in_time,
                'duration_minutes' => $newest->duration_minutes,
                'break_type_id' => $newest->break_type_id,
                'remarks' => $newest->remarks,
                'updated_at' => now(),
            ]);

            DB::table('break_sheets')
                ->where('business_id', $group->business_id)
                ->where('employee_id', $group->employee_id)
                ->where('break_date', $group->break_date)
                ->where('out_time', $group->out_time)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }
    }
};
