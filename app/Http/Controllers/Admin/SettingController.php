<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.view'), 403);

        $settings = Setting::all()->pluck('value', 'key')->toArray();

        return view('admin.settings.index', compact('settings'));
    }

    public function update(UpdateSettingsRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);

        $settingsData = $request->input('settings', []);

        $groupMap = [
            'company_name' => 'company', 'company_address' => 'company',
            'company_phone' => 'company', 'company_email' => 'company',
            'company_gst' => 'company', 'invoice_prefix' => 'document',
            'quotation_prefix' => 'document', 'currency_symbol' => 'document',
            'terms_and_conditions' => 'document',
        ];

        foreach ($settingsData as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $groupMap[$key] ?? 'general'],
            );
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
