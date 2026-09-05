<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::where('business_id', $request->user()->business_id)->get();
        return response()->json($settings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
            'settings.*.type' => 'nullable|string'
        ]);

        $businessId = $request->user()->business_id;
        $updatedSettings = [];

        foreach ($request->settings as $settingData) {
            $setting = Setting::updateOrCreate(
                ['business_id' => $businessId, 'key' => $settingData['key']],
                ['value' => $settingData['value'] ?? null, 'type' => $settingData['type'] ?? 'string']
            );
            $updatedSettings[] = $setting;
        }

        return response()->json($updatedSettings);
    }

    public function show(Request $request, string $key)
    {
        $setting = Setting::where('business_id', $request->user()->business_id)->where('key', $key)->firstOrFail();
        return response()->json($setting);
    }

    public function destroy(Request $request, string $key)
    {
        $setting = Setting::where('business_id', $request->user()->business_id)->where('key', $key)->firstOrFail();
        $setting->delete();

        return response()->json(['message' => 'Setting deleted']);
    }
}
