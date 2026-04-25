<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AssetLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_locations.view'), 403);

        $locations = AssetLocation::with('manager')
            ->withCount('assets')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")->orWhere('city', 'like', "%{$s}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.assets.locations.index', compact('locations'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_locations.create'), 403);
        $managers = Admin::orderBy('name')->get();

        return view('admin.assets.locations.create', compact('managers'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_locations.create'), 403);
        $data = $this->validateData($request);
        $data['created_by'] = Auth::guard('admin')->id();
        AssetLocation::create($data);

        return redirect()->route('admin.assets.locations.index')->with('success', 'Location created.');
    }

    public function edit(AssetLocation $location)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_locations.edit'), 403);
        $managers = Admin::orderBy('name')->get();

        return view('admin.assets.locations.edit', compact('location', 'managers'));
    }

    public function update(Request $request, AssetLocation $location)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_locations.edit'), 403);
        $data = $this->validateData($request, $location->id);
        $data['updated_by'] = Auth::guard('admin')->id();
        $location->update($data);

        return redirect()->route('admin.assets.locations.index')->with('success', 'Location updated.');
    }

    public function destroy(AssetLocation $location)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_locations.delete'), 403);

        if ($location->assets()->exists()) {
            return back()->with('error', 'Cannot delete location — assets are stored here. Mark it inactive instead.');
        }
        $location->delete();

        return back()->with('success', 'Location deleted.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $unique = $id ? ',code,'.$id : ',code';

        return $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:asset_locations'.$unique],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:office,warehouse,site,branch,other'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'manager_id' => ['nullable', 'exists:admins,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
