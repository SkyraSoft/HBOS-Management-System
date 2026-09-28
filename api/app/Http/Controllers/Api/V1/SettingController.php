<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Allowed setting keys in Phase 9.
     */
    protected const ALLOWED_KEYS = [
        'receipt_header',
        'receipt_footer',
        'receipt_printer_size',
        'show_tax_number',
        'tax_number',
        'invoice_prefix',
        'invoice_footer_note',
        'whatsapp_receipt_template',
        'receipt_settings',
        'low_stock_threshold_default',
        'enable_sms_alerts',
        'enable_sound_effects',
        'currency_symbol',
        'business_tagline',
    ];

    /**
     * Display all settings for the active business.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage business settings') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permission to view business settings.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $settings = Setting::where('business_id', $activeBusinessId)->get();
        $map = [];
        foreach ($settings as $s) {
            $map[$s->key] = $this->decodeTypedValue($s->value, $s->type);
        }

        return response()->json([
            'data' => $settings,
            'settings' => $map,
        ]);
    }

    /**
     * Store or update settings for the active business.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage business settings') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permission to manage business settings.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $rawSettings = $request->input('settings');
        if (!is_array($rawSettings)) {
            return response()->json(['message' => 'The settings field must be an array.'], 422);
        }

        // Normalize settings into standard list format
        $normalized = [];
        $isAssociative = empty($rawSettings) ? false : (array_keys($rawSettings) !== range(0, count($rawSettings) - 1));
        if ($isAssociative) {
            foreach ($rawSettings as $k => $v) {
                $determinedType = 'string';
                if (is_bool($v)) {
                    $determinedType = 'boolean';
                } elseif (is_int($v)) {
                    $determinedType = 'integer';
                } elseif (is_array($v)) {
                    $determinedType = 'json';
                }
                $normalized[] = [
                    'key' => (string) $k,
                    'value' => $v,
                    'type' => $determinedType
                ];
            }
        } else {
            $normalized = $rawSettings;
        }

        $updatedSettings = [];

        foreach ($normalized as $settingData) {
            if (!is_array($settingData) || !isset($settingData['key']) || !is_string($settingData['key'])) {
                return response()->json(['message' => 'Each setting must contain a valid key.'], 422);
            }

            $key = trim($settingData['key']);
            $type = $settingData['type'] ?? 'string';
            $rawValue = $settingData['value'] ?? null;

            // Strict allowlist check
            if (!in_array($key, self::ALLOWED_KEYS, true)) {
                return response()->json(['message' => "Disallowed or invalid setting key: {$key}"], 422);
            }

            // Typed encoding & validation
            $validatedValue = $this->encodeTypedValue($rawValue, $type);

            $existing = Setting::where('business_id', $activeBusinessId)->where('key', $key)->first();
            $oldValue = $existing ? $existing->value : null;

            $setting = Setting::updateOrCreate(
                ['business_id' => $activeBusinessId, 'key' => $key],
                ['value' => $validatedValue, 'type' => $type]
            );

            // Audit the setting change
            $this->auditService->log(
                logName: 'settings',
                event: $existing ? 'updated' : 'created',
                description: "Setting '{$key}' updated for business",
                subject: $setting,
                properties: [
                    'key' => $key,
                    'type' => $type,
                    'old' => $oldValue,
                    'new' => $validatedValue,
                ]
            );

            $updatedSettings[] = $setting;
        }

        return response()->json(['data' => $updatedSettings]);
    }

    /**
     * Display a specific setting.
     */
    public function show(Request $request, string $key)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage business settings') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permission to view business settings.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $setting = Setting::where('business_id', $activeBusinessId)->where('key', $key)->firstOrFail();
        return response()->json(['data' => $setting]);
    }

    /**
     * Delete a dynamic setting.
     */
    public function destroy(Request $request, string $key)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage business settings') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permission to manage business settings.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $setting = Setting::where('business_id', $activeBusinessId)->where('key', $key)->firstOrFail();
        $oldValue = $setting->value;
        $setting->delete();

        // Audit the setting deletion
        $this->auditService->log(
            logName: 'settings',
            event: 'deleted',
            description: "Setting '{$key}' deleted from business",
            subject: null,
            properties: [
                'key' => $key,
                'old' => $oldValue,
            ]
        );

        return response()->json(['message' => 'Setting deleted']);
    }

    /**
     * Encode and validate value based on declared type.
     */
    protected function encodeTypedValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

            case 'integer':
                return (string) (int) $value;

            case 'json':
                if (is_array($value)) {
                    return json_encode($value);
                }
                // Verify it is valid JSON
                json_decode((string) $value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'value' => ['The value must be a valid JSON string.'],
                    ]);
                }
                return (string) $value;

            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Decode stored value based on declared type.
     */
    protected function decodeTypedValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return (bool) $value;

            case 'integer':
                return (int) $value;

            case 'json':
                $decoded = json_decode((string) $value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;

            case 'string':
            default:
                return (string) $value;
        }
    }
}

