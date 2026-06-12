<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\AssetStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    public function index()
    {
        abort_unless(auth('admin')->user()->can('asset_statuses.view'), 403);

        $statuses = AssetStatus::orderBy('sort_order')->orderBy('label')->paginate(50);

        return view('admin.assets.statuses.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        abort_unless(auth('admin')->user()->can('asset_statuses.create'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'in:primary,secondary,success,info,warning,danger'],
        ]);

        // Slug the label into a stable key; bump with -2 / -3 / … if it
        // collides with an existing status in this business so the unique
        // index never blocks the save.
        $key = $this->uniqueKey($data['label']);

        AssetStatus::create([
            'key'        => $key,
            'label'      => $data['label'],
            'color'      => $data['color'] ?? 'secondary',
            'sort_order' => (int) AssetStatus::max('sort_order') + 1,
            'is_active'  => true,
            'is_system'  => false,
        ]);

        return back()->with('success', 'Status added.');
    }

    public function update(Request $request, AssetStatus $status)
    {
        abort_unless(auth('admin')->user()->can('asset_statuses.edit'), 403);

        $data = $request->validate([
            'label'     => ['required', 'string', 'max:100'],
            'color'     => ['nullable', 'string', 'in:primary,secondary,success,info,warning,danger'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $status->update([
            'label'     => $data['label'],
            'color'     => $data['color'] ?? $status->color,
            'is_active' => (bool) ($data['is_active'] ?? $status->is_active),
        ]);

        return back()->with('success', 'Status updated.');
    }

    public function toggle(AssetStatus $status)
    {
        abort_unless(auth('admin')->user()->can('asset_statuses.edit'), 403);

        $status->update(['is_active' => ! $status->is_active]);

        return back()->with('success', 'Status '.($status->is_active ? 'enabled' : 'disabled').'.');
    }

    public function destroy(AssetStatus $status)
    {
        abort_unless(auth('admin')->user()->can('asset_statuses.delete'), 403);

        // System defaults are required by built-in flows (e.g. dispose →
        // 'disposed'). Block deletion; admins can still toggle is_active
        // to hide them from the dropdown.
        if ($status->is_system) {
            return back()->with('error', 'System defaults cannot be deleted — disable them instead.');
        }

        $status->delete();

        return back()->with('success', 'Status removed.');
    }

    protected function uniqueKey(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'status';
        $key = $base;
        $i = 2;
        while (AssetStatus::where('key', $key)->exists()) {
            $key = $base.'_'.$i;
            $i++;
        }
        return $key;
    }
}
