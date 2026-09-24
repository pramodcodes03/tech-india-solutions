<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.view'), 403);
        $shifts = Shift::withCount('employees')->orderBy('start_time')->paginate(20);

        return view('admin.hr.shifts.index', compact('shifts'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.create'), 403);

        return view('admin.hr.shifts.create');
    }

    /**
     * Shared rules. `half_day_after_minutes` is the MINIMUM time that has to be
     * worked to earn a half day (e.g. 270 = 4h30m); below it the day is Absent.
     * It must stay under the shift length, otherwise a half day could never be
     * earned.
     */
    private function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'half_day_after_minutes' => [
                'required', 'integer', 'min:30',
                function (string $attribute, $value, \Closure $fail) use ($request) {
                    $length = $this->shiftLengthMinutes($request->input('start_time'), $request->input('end_time'));
                    if ($length === null) {
                        return;
                    }
                    if ($length > self::MAX_SANE_SHIFT_MINUTES) {
                        $fail('The shift works out to '.round($length / 60, 1).' hours. Check the start and end times.');

                        return;
                    }
                    if ($value >= $length) {
                        $fail('The half-day minimum must be less than the shift itself ('
                            .$length.' minutes for this '.round($length / 60, 1)
                            .'-hour shift), otherwise a half day can never be earned.');
                    }
                },
            ],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /** Shift length in minutes, wrapping past midnight for night shifts. */
    private function shiftLengthMinutes(?string $start, ?string $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        try {
            $minutes = Carbon::parse($start)->diffInMinutes(Carbon::parse($end), false);
        } catch (\Throwable) {
            return null;
        }

        if ($minutes <= 0) {
            $minutes += 24 * 60;
        }

        return $minutes > 0 ? $minutes : null;
    }

    /** Mirrors AttendanceService: anything longer is treated as bad data. */
    private const MAX_SANE_SHIFT_MINUTES = 16 * 60;

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.create'), 403);
        $data = $request->validate($this->rules($request));
        Shift::create($data);

        return redirect()->route('admin.hr.shifts.index')->with('success', 'Shift created.');
    }

    public function edit(Shift $shift)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.edit'), 403);

        return view('admin.hr.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.edit'), 403);
        $data = $request->validate($this->rules($request));
        $shift->update($data);

        return redirect()->route('admin.hr.shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.delete'), 403);
        $shift->delete();

        return back()->with('success', 'Shift deleted.');
    }
}
