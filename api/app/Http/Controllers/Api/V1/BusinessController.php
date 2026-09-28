<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\AccountMovement;
use App\Models\InventoryMovement;
use App\Models\ActivityLog;
use App\Services\AuditService;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of businesses the authenticated user belongs to.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $businesses = $user->businesses;

        return response()->json(['data' => $businesses]);
    }

    /**
     * Store a newly created business and setup default primary branch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'currency' => 'nullable|string|max:10',
        ]);

        $business = Business::create($validated);

        // Attach user membership
        $user = $request->user();
        $user->businesses()->syncWithoutDetaching([$business->id]);

        // Create initial primary branch
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->auditService->log(
            logName: 'governance',
            event: 'created',
            description: "Business '{$business->name}' created",
            subject: $business,
            properties: ['name' => $business->name, 'currency' => $business->currency],
            businessId: $business->id
        );

        return response()->json([
            'message' => 'Business created successfully.',
            'data' => $business,
            'branch' => $branch
        ], 201);
    }

    /**
     * Display the specified business if user belongs to it.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $belongsToBusiness = $user->businesses()->where('businesses.id', $id)->exists();

        if (!$belongsToBusiness) {
            return response()->json(['message' => 'Unauthorized access to business.'], 403);
        }

        $business = Business::findOrFail($id);
        return response()->json(['data' => $business]);
    }

    /**
     * Update the specified business.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $belongsToBusiness = $user->businesses()->where('businesses.id', $id)->exists();

        if (!$belongsToBusiness) {
            return response()->json(['message' => 'Unauthorized access to business.'], 403);
        }

        if (!$user->hasPermissionTo('manage business settings') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permissions to update business settings.'], 403);
        }

        $business = Business::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'currency' => 'nullable|string|max:10',
        ]);

        // Currency Governance Invariant: Cannot change currency if financial/transactional activity exists
        if (isset($validated['currency']) && $validated['currency'] !== $business->currency) {
            $hasFinancialActivity = Sale::where('business_id', $id)->exists()
                || Purchase::where('business_id', $id)->exists()
                || AccountMovement::where('business_id', $id)->exists()
                || InventoryMovement::where('business_id', $id)->exists();

            if ($hasFinancialActivity) {
                return response()->json([
                    'message' => 'Cannot change currency after financial or transactional records have been created.'
                ], 422);
            }
        }

        $oldValues = $business->only(array_keys($validated));
        $business->update($validated);
        $newValues = $business->only(array_keys($validated));

        $this->auditService->logMutation(
            category: 'settings',
            event: 'updated',
            description: "Business '{$business->name}' profile updated",
            subject: $business,
            oldValues: $oldValues,
            newValues: $newValues
        );

        return response()->json([
            'message' => 'Business updated successfully.',
            'data' => $business
        ]);
    }

    /**
     * Remove the specified business.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // 1. Strictly require Business Owner authorization
        if (!$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Only a Business Owner can delete a business.'], 403);
        }

        $belongsToBusiness = $user->businesses()->where('businesses.id', $id)->exists();
        if (!$belongsToBusiness) {
            return response()->json(['message' => 'Unauthorized access to business.'], 403);
        }

        // 2. Immutability Invariant: Business with transactional/financial or audit history cannot be deleted
        $hasHistory = Sale::where('business_id', $id)->exists()
            || Purchase::where('business_id', $id)->exists()
            || AccountMovement::where('business_id', $id)->exists()
            || InventoryMovement::where('business_id', $id)->exists()
            || ActivityLog::where('business_id', $id)->exists();

        if ($hasHistory) {
            return response()->json([
                'message' => 'Cannot delete business with existing operational, financial, or audit history.'
            ], 422);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId === (int) $id) {
            return response()->json([
                'message' => 'Cannot delete the currently active business branch.'
            ], 403);
        }

        $business = Business::findOrFail($id);
        $businessName = $business->name;
        $business->delete();

        return response()->json(['message' => 'Business deleted successfully.']);
    }
}
