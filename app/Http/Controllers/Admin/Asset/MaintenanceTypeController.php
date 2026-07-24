<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\AssetMaintenanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MaintenanceTypeController extends Controller
{
    public function index()
    {
        abort_unless(auth('admin')->user()->can('asset_maintenance_types.view'), 403);

        $types = AssetMaintenanceType::orderBy('sort_order')->orderBy('label')->paginate(50);

        return view('admin.assets.maintenance-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        abort_unless(auth('admin')->user()->can('asset_maintenance_types.create'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'in:primary,secondary,success,info,warning,danger'],
        ]);

        $key = $this->uniqueKey($data['label']);

        AssetMaintenanceType::create([
            'key'        => $key,
            'label'      => $data['label'],
            'color'      => $data['color'] ?? 'secondary',
            'sort_order' => (int) AssetMaintenanceType::max('sort_order') + 1,
            'is_active'  => true,
            'is_system'  => false,
        ]);

        return back()->with('success', 'Maintenance type added.');
    }

    public function update(Request $request, AssetMaintenanceType $type)
    {
        abort_unless(auth('admin')->user()->can('asset_maintenance_types.edit'), 403);

        $data = $request->validate([
            'label'     => ['required', 'string', 'max:100'],
            'color'     => ['nullable', 'string', 'in:primary,secondary,success,info,warning,danger'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $type->update([
            'label'     => $data['label'],
            'color'     => $data['color'] ?? $type->color,
            'is_active' => (bool) ($data['is_active'] ?? $type->is_active),
        ]);

        return back()->with('success', 'Maintenance type updated.');
    }

    public function toggle(AssetMaintenanceType $type)
    {
        abort_unless(auth('admin')->user()->can('asset_maintenance_types.edit'), 403);

        $type->update(['is_active' => ! $type->is_active]);

        return back()->with('success', 'Type '.($type->is_active ? 'enabled' : 'disabled').'.');
    }

    public function destroy(AssetMaintenanceType $type)
    {
        abort_unless(auth('admin')->user()->can('asset_maintenance_types.delete'), 403);

        if ($type->is_system) {
            return back()->with('error', 'System defaults cannot be deleted — disable them instead.');
        }

        $type->delete();

        return back()->with('success', 'Maintenance type removed.');
    }

    protected function uniqueKey(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'type';
        $key = $base;
        $i = 2;
        while (AssetMaintenanceType::where('key', $key)->exists()) {
            $key = $base.'_'.$i;
            $i++;
        }
        return $key;
    }
}
