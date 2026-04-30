<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }

        return view('employee.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'], // email OR employee_code
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'employee_code';

        if (Auth::guard('employee')->attempt([$field => $data['login'], 'password' => $data['password']], $request->boolean('remember'))) {
            $employee = Auth::guard('employee')->user();

            if (in_array($employee->status, ['terminated', 'resigned', 'absconded', 'inactive'])) {
                Auth::guard('employee')->logout();

                return back()->with('error', 'Your account is inactive. Please contact HR.');
            }

            $request->session()->regenerate();
            $employee->update(['last_login_at' => now()]);

            $intended = $request->session()->pull('url.intended');
            $target = ($intended && str_contains($intended, '/employee'))
                ? $intended
                : route('employee.dashboard');

            return redirect()->to($target)->with('success', 'Welcome back, '.$employee->first_name.'!');
        }

        return back()->withInput($request->only('login'))->with('error', 'Invalid credentials.');
    }

    public function logout(Request $request)
    {
        Auth::guard('employee')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $employee = Auth::guard('employee')->user();
        if (! Hash::check($data['current_password'], $employee->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $employee->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password updated.');
    }
}
