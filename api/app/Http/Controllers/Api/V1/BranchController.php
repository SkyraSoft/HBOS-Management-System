<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\InventoryMovement;
use App\Models\BranchInventory;
use App\Models\FinancialAccount;
use App\Models\Expense;
use App\Services\AuditService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of branches for the active business.
     */
    public function index(Request $request)
    {
        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branches = Branch::where('business_id', $activeBusinessId)->get();

        return response()->json(['data' => $branches]);
    }

    /**
     * Store a newly created branch under the active business.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage branches') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permission to manage branches.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        $validated['business_id'] = $activeBusinessId;

        // If setting is_primary true, remove is_primary from other branches in business
        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            Branch::where('business_id', $activeBusinessId)->update(['is_primary' => false]);
        }

        $branch = new Branch($validated);
        $branch->disableLogging();
        $branch->save();

        $this->auditService->log(
            logName: 'governance',
            event: 'created',
            description: "Branch '{$branch->name}' created",
            subject: $branch,
            properties: [
                'name' => $branch->name,
                'code' => $branch->code,
                'is_primary' => (bool) $branch->is_primary,
                'status' => $branch->status,
            ],
            branchId: $branch->id
        );

        return response()->json([
            'message' => 'Branch created successfully.',
            'data' => $branch
        ], 201);
    }

    /**
     * Display the specified branch.
     */
    public function show(Request $request, $id)
    {
        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($id);

        return response()->json(['data' => $branch]);
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $isOwner = $user->hasRole('Business Owner');
        $hasPermission = $isOwner
            || $user->can('manage branches')
            || $user->can('manage branch settings');

        if (!$hasPermission) {
            return response()->json(['message' => 'Insufficient permission to manage branch settings.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($id);

        // AC-9.24: Managers cannot alter unassigned Branches
        if (!$isOwner && !$user->can('manage branches')) {
            $assignedBranchIds = $user->branches()->pluck('branches.id')->map(fn($bId) => (int)$bId)->toArray();
            if ($user->branch_id) {
                $assignedBranchIds[] = (int) $user->branch_id;
            }
            if (!in_array((int) $branch->id, $assignedBranchIds, true)) {
                return response()->json(['message' => 'Cannot alter an unassigned branch.'], 403);
            }
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        // Primary Branch Invariant: Cannot deactivate the primary branch
        if (isset($validated['status']) && $validated['status'] === 'inactive' && $branch->is_primary) {
            return response()->json(['message' => 'Cannot deactivate the primary branch.'], 422);
        }

        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            Branch::where('business_id', $activeBusinessId)->where('id', '!=', $id)->update(['is_primary' => false]);
        }

        $oldValues = $branch->only(array_keys($validated));
        $branch->disableLogging();
        $branch->update($validated);
        $newValues = $branch->only(array_keys($validated));

        $this->auditService->logMutation(
            category: 'governance',
            event: 'updated',
            description: "Branch '{$branch->name}' updated",
            subject: $branch,
            oldValues: $oldValues,
            newValues: $newValues,
            branchId: $branch->id
        );

        return response()->json([
            'message' => 'Branch updated successfully.',
            'data' => $branch
        ]);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage branches') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permission to manage branches.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($id);

        // 1. Primary Branch Invariant
        if ($branch->is_primary) {
            return response()->json(['message' => 'Cannot delete the primary branch.'], 422);
        }

        // 2. Operational History Invariant: Do not cascade delete operational ledger truth
        $hasHistory = Sale::where('branch_id', $id)->exists()
            || Purchase::where('branch_id', $id)->exists()
            || InventoryMovement::where('branch_id', $id)->exists()
            || BranchInventory::where('branch_id', $id)->where('quantity_on_hand', '>', 0)->exists()
            || FinancialAccount::where('branch_id', $id)->exists()
            || Expense::where('branch_id', $id)->exists();

        if ($hasHistory) {
            return response()->json([
                'message' => 'Cannot delete branch with existing operational history. Please deactivate the branch instead.'
            ], 422);
        }

        $branchName = $branch->name;
        $branch->disableLogging();
        $branch->delete();

        $this->auditService->log(
            logName: 'governance',
            event: 'deleted',
            description: "Branch '{$branchName}' deleted",
            subject: null,
            properties: ['branch_id' => $id, 'name' => $branchName]
        );

        return response()->json(['message' => 'Branch deleted successfully.']);
    }
}
