<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get the onboarding status and configuration for the active business.
     */
    public function status(Request $request)
    {
        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $business = Business::findOrFail($activeBusinessId);
        $primaryBranch = Branch::where('business_id', $activeBusinessId)
            ->where('is_primary', true)
            ->first();

        $settings = Setting::where('business_id', $activeBusinessId)
            ->pluck('value', 'key');

        return response()->json([
            'onboarding_status' => $business->onboarding_status ?? 'completed',
            'onboarding_step' => (int) ($business->onboarding_step ?? 5),
            'current_step' => (int) ($business->onboarding_step ?? 5),
            'onboarding_completed_at' => $business->onboarding_completed_at,
            'business' => $business,
            'primary_branch' => $primaryBranch,
            'settings' => $settings,
        ]);
    }

    /**
     * Process and persist a specific step in the staged onboarding wizard.
     */
    public function saveStep(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Only a Business Owner can configure business onboarding.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $business = Business::findOrFail($activeBusinessId);
        $step = (int) $request->input('step', 2);

        return DB::transaction(function () use ($request, $step, $business, $activeBusinessId, $user) {
            switch ($step) {
                case 2:
                    // Step 2: Business Identity
                    $validated = $request->validate([
                        'name' => 'nullable|string|max:255',
                        'legal_name' => 'nullable|string|max:255',
                        'type' => 'nullable|string|max:255',
                        'country' => 'nullable|string|max:100',
                        'currency' => 'nullable|string|max:10',
                        'timezone' => 'nullable|string|max:100',
                        'phone' => 'nullable|string|max:50',
                        'address' => 'nullable|string',
                        'city' => 'nullable|string|max:100',
                        'email' => 'nullable|email|max:255',
                        'website' => 'nullable|string|max:255',
                    ]);

                    $business->update(array_filter([
                        'name' => $validated['name'] ?? $business->name,
                        'legal_name' => $validated['legal_name'] ?? $business->legal_name,
                        'type' => $validated['type'] ?? $business->type,
                        'country' => $validated['country'] ?? $business->country,
                        'currency' => $validated['currency'] ?? $business->currency,
                        'timezone' => $validated['timezone'] ?? $business->timezone,
                        'phone' => $validated['phone'] ?? $business->phone,
                        'address' => $validated['address'] ?? $business->address,
                        'email' => $validated['email'] ?? $business->email,
                        'website' => $validated['website'] ?? $business->website,
                        'onboarding_status' => 'in_progress',
                        'onboarding_step' => max((int)$business->onboarding_step, 3),
                    ], fn($val) => !is_null($val)));

                    $this->auditService->log(
                        logName: 'governance',
                        event: 'onboarding_step_2_completed',
                        description: "Business identity configured during onboarding",
                        subject: $business,
                        properties: $validated,
                        businessId: $activeBusinessId,
                        causer: $user
                    );
                    break;

                case 3:
                    // Step 3: Primary Branch Configuration
                    $request->merge([
                        'name' => $request->input('branch_name') ?: $request->input('name') ?: 'Main Branch',
                        'code' => $request->input('branch_code') ?: $request->input('code') ?: 'MAIN',
                        'phone' => $request->input('branch_phone') ?: $request->input('phone'),
                        'address' => $request->input('branch_address') ?: $request->input('address'),
                        'city' => $request->input('branch_city') ?: $request->input('city'),
                    ]);

                    $validated = $request->validate([
                        'name' => 'required|string|max:255',
                        'code' => 'required|string|max:50',
                        'phone' => 'nullable|string|max:50',
                        'address' => 'nullable|string',
                        'city' => 'nullable|string|max:100',
                    ]);

                    $primaryBranch = Branch::where('business_id', $activeBusinessId)
                        ->where('is_primary', true)
                        ->first();

                    if ($primaryBranch) {
                        $primaryBranch->update([
                            'name' => $validated['name'],
                            'code' => $validated['code'],
                            'phone' => $validated['phone'] ?? $primaryBranch->phone,
                            'address' => $validated['address'] ?? $primaryBranch->address,
                            'city' => $validated['city'] ?? $primaryBranch->city,
                        ]);
                    } else {
                        $primaryBranch = Branch::create([
                            'business_id' => $activeBusinessId,
                            'name' => $validated['name'],
                            'code' => $validated['code'],
                            'phone' => $validated['phone'] ?? null,
                            'address' => $validated['address'] ?? null,
                            'city' => $validated['city'] ?? null,
                            'is_primary' => true,
                            'status' => 'active',
                        ]);
                    }

                    $business->update([
                        'onboarding_step' => max((int)$business->onboarding_step, 4),
                    ]);

                    $this->auditService->log(
                        logName: 'governance',
                        event: 'onboarding_step_3_completed',
                        description: "Primary branch configured during onboarding",
                        subject: $primaryBranch,
                        properties: $validated,
                        branchId: $primaryBranch->id,
                        businessId: $activeBusinessId,
                        causer: $user
                    );
                    break;

                case 4:
                    // Step 4: Business Defaults & Tax Configuration
                    $validated = $request->validate([
                        'receipt_header' => 'nullable|string|max:255',
                        'receipt_footer' => 'nullable|string|max:255',
                        'invoice_prefix' => 'nullable|string|max:20',
                        'tax_number' => 'nullable|string|max:100',
                        'negative_stock_policy' => 'nullable|string|in:block,allow,warn',
                    ]);

                    if (!empty($validated['tax_number'])) {
                        $business->update(['tax_number' => $validated['tax_number']]);
                    }

                    $settingsMap = [
                        'receipt_header' => $validated['receipt_header'] ?? $business->name,
                        'receipt_footer' => $validated['receipt_footer'] ?? 'Thank you for your business!',
                        'invoice_prefix' => $validated['invoice_prefix'] ?? 'INV-',
                        'negative_stock_policy' => $validated['negative_stock_policy'] ?? 'block',
                    ];

                    foreach ($settingsMap as $key => $value) {
                        Setting::updateOrCreate(
                            ['business_id' => $activeBusinessId, 'key' => $key],
                            ['value' => $value]
                        );
                    }

                    $business->update([
                        'onboarding_step' => max((int)$business->onboarding_step, 5),
                    ]);

                    $this->auditService->log(
                        logName: 'governance',
                        event: 'onboarding_step_4_completed',
                        description: "Business defaults and preferences configured during onboarding",
                        subject: $business,
                        properties: $validated,
                        businessId: $activeBusinessId,
                        causer: $user
                    );
                    break;

                case 5:
                    // Step 5: Final Review & Workspace Ready
                    $business->update([
                        'onboarding_status' => 'completed',
                        'onboarding_step' => 5,
                        'onboarding_completed_at' => now(),
                    ]);

                    $this->auditService->log(
                        logName: 'governance',
                        event: 'business_onboarding_completed',
                        description: "Business onboarding completed successfully",
                        subject: $business,
                        properties: [
                            'business_name' => $business->name,
                            'completed_at' => now()->toIso8601String(),
                        ],
                        businessId: $activeBusinessId,
                        causer: $user
                    );
                    break;

                default:
                    throw ValidationException::withMessages([
                        'step' => ['Invalid onboarding step specified. Valid: 2, 3, 4, 5']
                    ]);
            }

            return response()->json([
                'message' => "Onboarding step {$step} saved successfully.",
                'onboarding_status' => $business->onboarding_status,
                'onboarding_step' => $business->onboarding_step,
                'business' => $business->fresh(),
            ]);
        });
    }
}
