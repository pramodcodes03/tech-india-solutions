<x-layout.admin title="Review Correction">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Attendance Corrections', 'url' => route('admin.hr.regularizations.index')], ['label' => 'Review']]" />

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-extrabold">{{ $regularization->employee?->full_name ?? 'Employee unavailable' }}</h1>
                @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$regularization->status]; @endphp
                <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($regularization->status) }}</span>
            </div>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Employee Code</dt><dd>{{ $regularization->employee?->employee_code ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Department</dt><dd>{{ $regularization->employee?->department?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Date</dt><dd>{{ $regularization->date->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-500">Request Type</dt><dd>{{ $regularization->type_label }}</dd></div>
                <div><dt class="text-gray-500">Expected Check-in</dt><dd>{{ $regularization->expected_in_time ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Expected Check-out</dt><dd>{{ $regularization->expected_out_time ?? '—' }}</dd></div>
            </dl>
            {{-- Assigned shift. The approve decision is a judgement about
                 whether the requested times fit the employee's shift, so put
                 that shift in front of HR instead of making them look it up. --}}
            @php
                $empShift = $regularization->employee?->shift;
                $halfHrs = null;
                if ($empShift) {
                    $win = \App\Services\Attendance\AttendanceStatusCalculator::windowMinutes(
                        (string) $empShift->start_time, (string) $empShift->end_time);
                    $halfHrs = $win ? round((int) ceil($win / 2) / 60, 1) : null;
                }
            @endphp
            <div class="rounded-lg border {{ $empShift ? 'border-primary/20 bg-primary/5' : 'border-warning/30 bg-warning/5' }} p-3">
                <div class="text-[11px] font-semibold text-gray-500 uppercase mb-1.5">Assigned Shift</div>
                @if($empShift)
                    <x-shift-badge :shift="$empShift" />
                    <div class="text-xs text-gray-500 mt-1.5">
                        Grace {{ $empShift->grace_minutes }} min
                        @if($halfHrs) &middot; half-day if less than {{ $halfHrs }} hrs fall inside the shift @endif
                    </div>
                @else
                    <x-shift-badge :shift="null" />
                    <div class="text-xs text-gray-500 mt-1.5">
                        Attendance for this employee is judged on total hours worked, not on a shift window.
                    </div>
                @endif
            </div>
            <div>
                <dt class="text-gray-500 text-sm">Reason</dt>
                <dd class="mt-1 p-3 bg-gray-50 dark:bg-[#0e1726] rounded-lg text-sm whitespace-pre-line">{{ $regularization->reason }}</dd>
            </div>
            @if($regularization->attendance)
                <div class="text-sm text-gray-500">Current record: in {{ $regularization->attendance->check_in ? \Carbon\Carbon::parse($regularization->attendance->check_in)->format('H:i') : '—' }}, out {{ $regularization->attendance->check_out ? \Carbon\Carbon::parse($regularization->attendance->check_out)->format('H:i') : '—' }} ({{ $regularization->attendance->status }})</div>
            @endif
            @if($regularization->reviewed_at)
                <div class="text-sm text-gray-500 border-t pt-3">Reviewed by {{ $regularization->reviewer?->name }} on {{ $regularization->reviewed_at->format('d M Y H:i') }}. {{ $regularization->review_remarks }}</div>
            @endif
        </div>

        <div class="panel p-6">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Resolution</div>
            <div class="mb-3 text-sm {{ $regularization->isBreaching() ? 'text-danger font-semibold' : 'text-gray-500' }}">
                Target: {{ optional($regularization->sla_due_at)->format('d M Y H:i') }} ({{ optional($regularization->sla_due_at)->diffForHumans() }})
            </div>
            @if($regularization->status === 'pending')
                @can('attendance_corrections.manage')
                    <form method="POST" action="{{ route('admin.hr.regularizations.approve', $regularization) }}"
                          class="space-y-2 mb-3"
                          x-data="{
                              status: '',
                              projection: @js($weekOffProjection),
                              get needsHalf() { return ['half_day', 'half_day_week_off'].includes(this.status) },
                              get warn() {
                                  const p = this.projection[this.status];
                                  return p && p.exceeds ? p : null;
                              },
                              confirmApprove() {
                                  const p = this.warn;
                                  if (! p) { return confirm('Approve and correct attendance?'); }
                                  return confirm(
                                      'Week Off Limit Reached\n\n'
                                      + `Employee already has ${p.current} week off(s) considered in this month.\n`
                                      + `Approving this request adds ${p.adding} and makes the total ${p.projected} `
                                      + `for this month, above the allowance of ${p.allowance}.\n\n`
                                      + 'Do you want to approve this Week Off?'
                                  );
                              },
                          }">
                        @csrf
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase">Mark Attendance As</label>
                            <select name="resulting_status" x-model="status" class="form-select mt-1">
                                <option value="">Auto (from corrected times)</option>
                                <option value="present">Present</option>
                                <option value="half_day">Half-day</option>
                                <option value="on_leave">On Leave</option>
                                <option value="absent">Absent</option>
                                {{-- Stored as the attendance table's 'weekend'
                                     status; called a week-off everywhere else. --}}
                                <option value="weekend">Week-Off</option>
                                {{-- 0.5 day present + 0.5 day week-off. --}}
                                <option value="half_day_week_off">Half Day – Week Off</option>
                                {{-- 0.5 day leave + 0.5 day week-off: a day the
                                     employee was never due in for at all. --}}
                                <option value="leave_week_off">Leave – Week Off</option>
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1">"Auto" derives the status from the corrected check-in/out (short day → half-day).</p>
                        </div>

                        {{-- Which half was actually worked. Only asked for when the
                             resolution is a half-day, because only then does the
                             calendar have a duty window to place. --}}
                        <div x-show="needsHalf" x-cloak>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase">Half Worked</label>
                            <select name="half_day_portion" class="form-select mt-1">
                                <option value="first_half">First half duty · second half off</option>
                                <option value="second_half">First half off · second half duty</option>
                            </select>
                        </div>

                        {{-- The client asked to be warned, never blocked, so this is
                             advisory and the Approve button stays live. --}}
                        <template x-if="warn">
                            <div class="p-2.5 rounded-lg bg-warning/10 text-warning text-[11px] leading-relaxed">
                                <b>Week Off Limit Reached.</b>
                                Employee already has <b x-text="warn.current"></b> week off(s) this month.
                                Approving adds <b x-text="warn.adding"></b>, making
                                <b x-text="warn.projected"></b> against an allowance of
                                <b x-text="warn.allowance"></b>. You can still approve.
                            </div>
                        </template>

                        <input type="text" name="review_remarks" placeholder="Remarks (optional)" class="form-input">
                        <button class="btn btn-success w-full"
                                @click="if (! confirmApprove()) $event.preventDefault()">Approve & Apply</button>
                    </form>
                    <form method="POST" action="{{ route('admin.hr.regularizations.reject', $regularization) }}" class="space-y-2">
                        @csrf
                        <input type="text" name="review_remarks" placeholder="Reason for rejection *" class="form-input" required>
                        <button class="btn btn-outline-danger w-full">Reject</button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">You don't have permission to resolve this request.</p>
                @endcan
            @else
                <p class="text-sm text-gray-400">This request is {{ $regularization->status }}.</p>
            @endif
        </div>
    </div>
</x-layout.admin>
