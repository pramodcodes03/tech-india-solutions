<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('city')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%");
            }))
            ->when($request->city_id, fn ($q, $c) => $q->where('city_id', $c))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $users->items(),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem() ?? 0,
                    'to' => $users->lastItem() ?? 0,
                ],
            ]);
        }

        $cities = City::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'cities'));
    }

    public function create()
    {
        $cities = City::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|string|max:20',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'password' => 'required|min:6|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'status' => $request->status,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $cities = City::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'cities'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'mobile' => 'nullable|string|max:20',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $data = $request->only('name', 'email', 'mobile', 'city_id', 'address', 'status');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'User status updated.');
    }
}
