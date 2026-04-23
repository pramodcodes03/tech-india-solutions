<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        $employee = Auth::guard('employee')->user()->load(
            'department', 'designation', 'shift', 'reportingManager', 'documents'
        );

        return view('employee.profile.show', compact('employee'));
    }

    public function edit()
    {
        $employee = Auth::guard('employee')->user();

        return view('employee.profile.edit', compact('employee'));
    }

    public function update(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'bank_branch' => ['nullable', 'string', 'max:100'],
        ]);
        $employee->update($data);

        return redirect()->route('employee.profile.show')->with('success', 'Profile updated.');
    }
}
