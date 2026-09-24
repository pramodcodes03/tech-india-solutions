<?php

namespace App\Http\Controllers\Admin\Hr\Trackers;

use App\Http\Controllers\Controller;
use App\Models\BreakSheet;
use App\Models\TrackerOption;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Tracker Settings — the one screen where HR maintains every dynamic dropdown
 * the trackers use (Break Type, Visitor Source, Purpose of Visit).
 */
class TrackerOptionController extends Controller
{
    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("tracker_settings.{$action}"), 403);
    }

    public function index()
    {
        $this->gate('view');

        $options = TrackerOption::orderBy('type')->orderBy('sort_order')->orderBy('name')->get()->groupBy('type');

        // Usage counts turn "Delete" into an informed decision — an option
        // already attached to records is deactivated, never removed.
        $usage = [
            TrackerOption::TYPE_BREAK => BreakSheet::query()
                ->whereNotNull('break_type_id')->groupBy('break_type_id')
                ->selectRaw('break_type_id as id, COUNT(*) as total')->pluck('total', 'id'),
            TrackerOption::TYPE_SOURCE => VisitorLog::query()
                ->whereNotNull('source_id')->groupBy('source_id')
                ->selectRaw('source_id as id, COUNT(*) as total')->pluck('total', 'id'),
            TrackerOption::TYPE_PURPOSE => VisitorLog::query()
                ->whereNotNull('purpose_id')->groupBy('purpose_id')
                ->selectRaw('purpose_id as id, COUNT(*) as total')->pluck('total', 'id'),
        ];

        return view('admin.hr.trackers.options.index', compact('options', 'usage'));
    }

    public function store(Request $request)
    {
        $this->gate('manage');

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(TrackerOption::TYPES))],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $exists = TrackerOption::ofType($data['type'])->where('name', $data['name'])->exists();
        if ($exists) {
            return back()->with('error', 'That value already exists in this list.');
        }

        TrackerOption::create($data + ['is_active' => true]);

        return back()->with('success', TrackerOption::TYPES[$data['type']].' added.');
    }

    public function update(Request $request, TrackerOption $option)
    {
        $this->gate('manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $clash = TrackerOption::ofType($option->type)
            ->where('name', $data['name'])
            ->where('id', '!=', $option->id)
            ->exists();

        if ($clash) {
            return back()->with('error', 'Another value in this list already uses that name.');
        }

        $option->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? $option->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Value updated.');
    }

    public function destroy(TrackerOption $option)
    {
        $this->gate('manage');

        // Records referencing this option would lose their label (the FK is
        // nullOnDelete). Deactivating hides it from new entries while keeping
        // every historical row readable.
        if ($this->usageCount($option) > 0) {
            $option->update(['is_active' => false]);

            return back()->with('warning', 'This value is used by existing records, so it was deactivated instead of deleted. It will no longer appear on new entries.');
        }

        $option->delete();

        return back()->with('success', 'Value deleted.');
    }

    private function usageCount(TrackerOption $option): int
    {
        return match ($option->type) {
            TrackerOption::TYPE_BREAK => BreakSheet::where('break_type_id', $option->id)->count(),
            TrackerOption::TYPE_SOURCE => VisitorLog::where('source_id', $option->id)->count(),
            TrackerOption::TYPE_PURPOSE => VisitorLog::where('purpose_id', $option->id)->count(),
            default => 0,
        };
    }
}
