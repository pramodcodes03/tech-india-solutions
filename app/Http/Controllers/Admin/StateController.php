<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.view'), 403);

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
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);
        return view('admin.states.create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);
        $request->validate([
            'name' => 'required|string|max:255|unique:states,name',
            'code' => 'nullable|string|max:10',
        ]);

        State::create($request->only('name', 'code'));

        return redirect()->route('admin.states.index')->with('success', 'State created successfully.');
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);
        $state = State::findOrFail($id);

        return view('admin.states.edit', compact('state'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);
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
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);
        $state = State::findOrFail($id);
        $state->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'State deleted successfully.']);
        }

        return redirect()->route('admin.states.index')->with('success', 'State deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);
        $state = State::findOrFail($id);
        $state->update(['is_active' => ! $state->is_active]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'State status updated.');
    }

    /**
     * API: return cities for a state (by state name).
     * No permission check — this is a lightweight lookup used by form
     * dropdowns across the app; gating it would break every form that
     * cascades a state → city picker.
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
