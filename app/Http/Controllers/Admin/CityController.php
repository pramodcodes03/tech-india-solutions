<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $cities = City::query()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('state', 'like', "%{$s}%")))
            ->when($request->state, fn ($q, $s) => $q->where('state', $s))
            ->latest()
            ->paginate(15);

        if ($request->ajax()) {
            return response()->json([
                'data' => $cities->items(),
                'pagination' => [
                    'total' => $cities->total(),
                    'per_page' => $cities->perPage(),
                    'current_page' => $cities->currentPage(),
                    'last_page' => $cities->lastPage(),
                    'from' => $cities->firstItem() ?? 0,
                    'to' => $cities->lastItem() ?? 0,
                ],
            ]);
        }

        $states = State::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.cities.index', compact('cities', 'states'));
    }

    public function create()
    {
        $states = State::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.cities.create', compact('states'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
        ]);

        City::create($request->only('name', 'state'));

        return redirect()->route('admin.cities.index')->with('success', 'City created successfully.');
    }

    public function edit($id)
    {
        $city = City::findOrFail($id);
        $states = State::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.cities.edit', compact('city', 'states'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
        ]);

        $city = City::findOrFail($id);
        $city->update($request->only('name', 'state'));

        return redirect()->route('admin.cities.index')->with('success', 'City updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $city = City::findOrFail($id);
        $city->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'City deleted successfully.']);
        }

        return redirect()->route('admin.cities.index')->with('success', 'City deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $city = City::findOrFail($id);
        $city->update(['is_active' => ! $city->is_active]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'City status updated.');
    }
}
