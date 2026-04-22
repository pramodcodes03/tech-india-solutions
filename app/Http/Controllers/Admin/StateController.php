<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $states = State::when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $states->items(),
                'pagination' => [
                    'total' => $states->total(),
                    'per_page' => $states->perPage(),
                    'current_page' => $states->currentPage(),
                    'last_page' => $states->lastPage(),
                    'from' => $states->firstItem() ?? 0,
                    'to' => $states->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.states.index', compact('states'));
    }

    public function create()
    {
        return view('admin.states.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:states,name',
            'code' => 'nullable|string|max:10',
        ]);

        State::create($request->only('name', 'code'));

        return redirect()->route('admin.states.index')->with('success', 'State created successfully.');
    }

    public function edit($id)
    {
        $state = State::findOrFail($id);

        return view('admin.states.edit', compact('state'));
    }

    public function update(Request $request, $id)
    {
        $state = State::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:states,name,' . $state->id,
            'code' => 'nullable|string|max:10',
        ]);

        $state->update($request->only('name', 'code'));

        return redirect()->route('admin.states.index')->with('success', 'State updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $state = State::findOrFail($id);
        $state->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'State deleted successfully.']);
        }

        return redirect()->route('admin.states.index')->with('success', 'State deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $state = State::findOrFail($id);
        $state->update(['is_active' => ! $state->is_active]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'State status updated.');
    }

    /**
     * API: return cities for a state (by state name).
     */
    public function cities(Request $request)
    {
        $state = $request->query('state');

        $cities = \App\Models\City::query()
            ->when($state, fn ($q) => $q->where('state', $state))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'state']);

        return response()->json($cities);
    }
}
