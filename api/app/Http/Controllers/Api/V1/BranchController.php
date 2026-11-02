<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchActionNote;
use App\Models\BranchInventory;
use App\Models\CashRegisterSession;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches')->where(fn ($q) => $q->where('business_id', $activeBusinessId))
            ],
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        $validated['business_id'] = $activeBusinessId;
        $validated['is_primary'] = !empty($validated['is_primary']) ? (bool) $validated['is_primary'] : false;

        // Inactive by default for newly added branches if status not explicitly provided and not primary
        if (empty($validated['status'])) {
            $isFirstBranch = Branch::where('business_id', $activeBusinessId)->count() === 0;
            $validated['status'] = (!empty($validated['is_primary']) || $isFirstBranch) ? 'active' : 'inactive';
        }

        // If setting is_primary true, remove is_primary from other branches in business
        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            Branch::where('business_id', $activeBusinessId)->update(['is_primary' => false]);
        }

        $branch = new Branch($validated);
        $branch->disableLogging();
        $branch->save();

        // Automatic default cash drawer provisioning for new branch
        FinancialAccount::firstOrCreate([
            'business_id' => $activeBusinessId,
            'branch_id' => $branch->id,
            'type' => 'cash',
            'is_default' => true,
        ], [
            'name' => "{$branch->name} Cash Drawer",
            'account_number' => "DRAWER-{$branch->id}",
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        // Assign creating user membership to branch
        if ($user && method_exists($user, 'branches')) {
            $user->branches()->syncWithoutDetaching([
                $branch->id => ['business_id' => $activeBusinessId]
            ]);
        }

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
     * Operational Launch Readiness Audit for a branch.
     * REQ-0003 / SRT-GAP-024
     */
    public function readiness(Request $request, $id)
    {
        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($id);

        $profileComplete = !empty($branch->name) && !empty($branch->code);
        $hasDrawer = FinancialAccount::where('business_id', $activeBusinessId)
            ->where('branch_id', $id)
            ->where('type', 'cash')
            ->where('is_default', true)
            ->exists();

        // Staff check (optional for owner-direct operation)
        $staffCount = User::where('business_id', $activeBusinessId)
            ->where('branch_id', $id)
            ->count();
        if (method_exists($branch, 'assignedUsers')) {
            $staffCount += $branch->assignedUsers()->count();
        }
        $hasStaff = $staffCount > 0;

        // Stock check
        $hasStock = BranchInventory::where('business_id', $activeBusinessId)
            ->where('branch_id', $id)
            ->where('quantity_on_hand', '>', 0)
            ->exists();

        $blockers = [];
        if (!$profileComplete) {
            $blockers[] = 'Branch profile incomplete (name and code required).';
        }
        if (!$hasDrawer) {
            $blockers[] = 'Default cash drawer account is missing.';
        }

        $warnings = [];
        if (!$hasStaff) {
            $warnings[] = 'No branch manager or cashier assigned. Note: Temporary direct operation by Business Owner is permitted.';
        }
        if (!$hasStock) {
            $warnings[] = 'No stock currently provisioned for this branch.';
        }

        return response()->json([
            'data' => [
                'branch_id' => (int) $id,
                'branch_name' => $branch->name,
                'status' => $branch->status,
                'is_ready' => empty($blockers),
                'checks' => [
                    'profile_complete' => [
                        'status' => $profileComplete,
                        'mandatory' => true,
                        'label' => 'Branch Profile Complete',
                    ],
                    'default_cash_drawer' => [
                        'status' => $hasDrawer,
                        'mandatory' => true,
                        'label' => 'Default Cash Drawer Provisioned',
                    ],
                    'assigned_staff' => [
                        'status' => $hasStaff,
                        'mandatory' => false,
                        'label' => 'Staff Assigned',
                        'note' => 'Optional for Business Owner direct operation',
                    ],
                    'initial_stock' => [
                        'status' => $hasStock,
                        'mandatory' => false,
                        'label' => 'Initial Stock Available',
                    ],
                ],
                'blockers' => $blockers,
                'warnings' => $warnings,
            ]
        ]);
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

        // Managers cannot alter unassigned Branches
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
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches')->where(fn ($q) => $q->where('business_id', $activeBusinessId))->ignore($id)
            ],
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        // Branch Deactivation Safety Guards (SRT-GAP-024)
        if (isset($validated['status']) && $validated['status'] === 'inactive') {
            // 1. Primary Branch Invariant
            if ($branch->is_primary) {
                return response()->json(['message' => 'Cannot deactivate the primary branch.'], 422);
            }

            // 2. Open Cash Register Session Guard
            if (CashRegisterSession::where('branch_id', $id)->where('status', 'open')->exists()) {
                return response()->json([
                    'message' => 'Cannot deactivate branch with an active open cash drawer session. Reconcile and close drawer first.'
                ], 422);
            }

            // 3. Remaining Stock Guard
            if (BranchInventory::where('branch_id', $id)->where('quantity_on_hand', '>', 0)->exists()) {
                return response()->json([
                    'message' => 'Cannot deactivate branch with remaining stock. Transfer or adjust stock out first.'
                ], 422);
            }

            // 4. In-Transit or Pending Stock Transfers Guard
            $hasPendingTransfer = StockTransfer::where(function ($q) use ($id) {
                $q->where('from_branch_id', $id)->orWhere('to_branch_id', $id);
            })->whereIn('status', ['draft', 'dispatched'])->exists();

            if ($hasPendingTransfer) {
                return response()->json([
                    'message' => 'Cannot deactivate branch with in-transit or pending stock transfers.'
                ], 422);
            }

            // 5. Current-Device Offline Outbox Guard (SRT-GAP-024)
            if (intval($request->header('X-Unsynced-Branch-Count', 0)) > 0) {
                return response()->json([
                    'message' => 'Cannot deactivate branch while unsynced offline transactions exist in local outbox.'
                ], 422);
            }
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
        $hasAccountActivity = FinancialAccount::where('branch_id', $id)
            ->where(function ($q) {
                $q->where('balance', '!=', 0)
                  ->orWhereHas('movements');
            })
            ->exists();

        $hasTransfers = StockTransfer::where(function ($q) use ($id) {
            $q->where('from_branch_id', $id)->orWhere('to_branch_id', $id);
        })->exists();

        $hasHistory = Sale::where('branch_id', $id)->exists()
            || Purchase::where('branch_id', $id)->exists()
            || InventoryMovement::where('branch_id', $id)->exists()
            || BranchInventory::where('branch_id', $id)->where('quantity_on_hand', '>', 0)->exists()
            || $hasAccountActivity
            || Expense::where('branch_id', $id)->exists()
            || $hasTransfers
            || BranchActionNote::where('branch_id', $id)->exists();

        if ($hasHistory) {
            return response()->json([
                'message' => 'Cannot delete branch with existing operational history. Please deactivate the branch instead.'
            ], 422);
        }

        // Clean up unutilized default cash drawer if present
        FinancialAccount::where('branch_id', $id)
            ->where('balance', 0)
            ->whereDoesntHave('movements')
            ->delete();

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

    /**
     * Preview decommissioning impact for a secondary branch.
     */
    public function previewDecommission(Request $request, $id)
    {
        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($id);
        if ($branch->is_primary) {
            return response()->json(['message' => 'Primary branch cannot be decommissioned.'], 422);
        }

        $primaryBranch = Branch::where('business_id', $activeBusinessId)
            ->where('is_primary', true)
            ->first();

        // 1. Stock items to sweep
        $stockItems = BranchInventory::with('product')
            ->where('branch_id', $id)
            ->where('quantity_on_hand', '>', 0)
            ->get();

        $totalStockQty = $stockItems->sum('quantity_on_hand');
        $uniqueStockCount = $stockItems->count();

        // 2. Open Cash Sessions & Drawer balance
        $openSession = CashRegisterSession::where('branch_id', $id)->where('status', 'open')->first();
        $drawer = FinancialAccount::where('branch_id', $id)->where('type', 'cash')->where('is_default', true)->first();
        $drawerCash = $drawer ? (float) $drawer->balance : 0.00;

        // 3. Staff count
        $staffCount = User::where('branch_id', $id)->count();

        // 4. In-transit transfers
        $pendingTransfers = StockTransfer::where(function ($q) use ($id) {
            $q->where('from_branch_id', $id)->orWhere('to_branch_id', $id);
        })->whereIn('status', ['draft', 'dispatched'])->count();

        // 5. Customer sales history
        $salesCount = Sale::where('branch_id', $id)->count();

        return response()->json([
            'data' => [
                'branch_id' => (int) $id,
                'branch_name' => $branch->name,
                'primary_branch_id' => $primaryBranch ? (int) $primaryBranch->id : null,
                'primary_branch_name' => $primaryBranch ? $primaryBranch->name : 'Main Store',
                'unique_stock_items' => $uniqueStockCount,
                'total_stock_quantity' => (float) $totalStockQty,
                'has_open_cash_session' => (bool) $openSession,
                'drawer_cash_balance' => (float) $drawerCash,
                'staff_assigned_count' => $staffCount,
                'pending_transfers_count' => $pendingTransfers,
                'has_sales_history' => $salesCount > 0,
                'can_hard_purge' => $salesCount === 0 && $pendingTransfers === 0,
            ]
        ]);
    }

    /**
     * 1-Click Automated Branch Decommissioning and Consolidation into Primary Branch.
     */
    public function consolidateAndDecommission(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage branches') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Insufficient permission to decommission branches.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($id);
        if ($branch->is_primary) {
            return response()->json(['message' => 'Primary branch cannot be decommissioned.'], 422);
        }

        $primaryBranch = Branch::where('business_id', $activeBusinessId)
            ->where('is_primary', true)
            ->firstOrFail();

        // Guard against dispatched in-transit transfers: must be received first
        $dispatchedTransfers = StockTransfer::where(function ($q) use ($id) {
            $q->where('from_branch_id', $id)->orWhere('to_branch_id', $id);
        })->where('status', 'dispatched')->exists();

        if ($dispatchedTransfers) {
            return response()->json([
                'message' => 'Cannot decommission branch while in-transit (dispatched) transfers exist. Please receive them first.'
            ], 422);
        }

        $itemsSwept = 0;
        $totalQtySwept = 0.00;
        $cashSwept = 0.00;

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $branch, $primaryBranch, $activeBusinessId, $id, $user,
            &$itemsSwept, &$totalQtySwept, &$cashSwept
        ) {
            // Cancel any draft stock transfers involving this branch
            StockTransfer::where(function ($q) use ($id) {
                $q->where('from_branch_id', $id)->orWhere('to_branch_id', $id);
            })->where('status', 'draft')->update(['status' => 'cancelled']);

            // 1. STOCK SWEEP: Move all remaining inventory to Primary Branch
            $stockRows = BranchInventory::where('branch_id', $id)
                ->where('quantity_on_hand', '>', 0)
                ->lockForUpdate()
                ->get();

            foreach ($stockRows as $row) {
                $qty = (float) $row->quantity_on_hand;
                if ($qty <= 0) continue;

                // Deduct from closing branch
                InventoryMovement::create([
                    'business_id' => $activeBusinessId,
                    'branch_id' => $id,
                    'product_id' => $row->product_id,
                    'type' => 'transfer_out',
                    'quantity' => -$qty,
                    'balance' => 0.000,
                    'performed_by' => $user->id,
                    'notes' => "Decommissioning sweep: transferred to {$primaryBranch->name}",
                ]);
                $row->update(['quantity_on_hand' => 0.000]);

                // Credit into primary branch
                $primaryInv = BranchInventory::firstOrCreate([
                    'business_id' => $activeBusinessId,
                    'branch_id' => $primaryBranch->id,
                    'product_id' => $row->product_id,
                ], [
                    'quantity_on_hand' => 0.000,
                    'minimum_stock' => $row->minimum_stock ?? 0.000,
                ]);

                $newPrimaryQty = bcadd((string) $primaryInv->quantity_on_hand, (string) $qty, 3);
                $primaryInv->update(['quantity_on_hand' => $newPrimaryQty]);

                InventoryMovement::create([
                    'business_id' => $activeBusinessId,
                    'branch_id' => $primaryBranch->id,
                    'product_id' => $row->product_id,
                    'type' => 'transfer_in',
                    'quantity' => $qty,
                    'balance' => $newPrimaryQty,
                    'performed_by' => $user->id,
                    'notes' => "Decommissioning sweep: received from {$branch->name}",
                ]);

                $itemsSwept++;
                $totalQtySwept += $qty;
            }

            // 2. FINANCIAL SWEEP: Close open sessions & sweep drawer
            CashRegisterSession::where('branch_id', $id)
                ->where('status', 'open')
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'notes' => 'Auto-closed on branch decommissioning',
                ]);

            $closingDrawer = FinancialAccount::where('branch_id', $id)
                ->where('type', 'cash')
                ->where('is_default', true)
                ->lockForUpdate()
                ->first();

            if ($closingDrawer && $closingDrawer->balance > 0) {
                $cashAmt = (float) $closingDrawer->balance;
                $primaryDrawer = FinancialAccount::where('branch_id', $primaryBranch->id)
                    ->where('type', 'cash')
                    ->where('is_default', true)
                    ->lockForUpdate()
                    ->first();

                if ($primaryDrawer) {
                    $primaryDrawer->increment('balance', $cashAmt);
                }
                $closingDrawer->update(['balance' => 0.00]);
                $cashSwept = $cashAmt;
            }

            // 3. STAFF REASSIGNMENT: Move staff to Primary Branch
            User::where('branch_id', $id)->update(['branch_id' => $primaryBranch->id]);
            if (method_exists($branch, 'assignedUsers')) {
                $branch->assignedUsers()->detach();
            }

            // 4. DEACTIVATE BRANCH
            $branch->status = 'inactive';
            $branch->disableLogging();
            $branch->save();

            $this->auditService->log(
                logName: 'governance',
                event: 'decommissioned',
                description: "Branch '{$branch->name}' consolidated into '{$primaryBranch->name}' and deactivated.",
                subject: $branch,
                properties: [
                    'items_swept' => $itemsSwept,
                    'quantity_swept' => $totalQtySwept,
                    'cash_swept' => $cashSwept,
                    'consolidated_to' => $primaryBranch->id,
                ],
                branchId: $primaryBranch->id
            );
        });

        return response()->json([
            'message' => "Branch '{$branch->name}' successfully decommissioned. All {$itemsSwept} stock items and drawer cash were transferred to {$primaryBranch->name}.",
            'data' => [
                'branch_id' => $branch->id,
                'status' => 'inactive',
                'items_swept' => $itemsSwept,
                'quantity_swept' => $totalQtySwept,
                'cash_swept' => $cashSwept,
            ]
        ]);
    }

    /**
     * Purge an unutilized or test branch completely.
     */
    public function purgeTestBranch(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Only Business Owners can purge test branches.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($id);
        if ($branch->is_primary) {
            return response()->json(['message' => 'Cannot purge the primary branch.'], 422);
        }

        if (Sale::where('branch_id', $id)->exists()) {
            return response()->json([
                'message' => 'Cannot purge branch with recorded sales. Please use "Consolidate & Decommission" instead.'
            ], 422);
        }

        $branchName = $branch->name;

        \Illuminate\Support\Facades\DB::transaction(function () use ($branch, $id) {
            StockTransfer::where('from_branch_id', $id)->orWhere('to_branch_id', $id)->delete();
            InventoryMovement::where('branch_id', $id)->delete();
            BranchInventory::where('branch_id', $id)->delete();
            CashRegisterSession::where('branch_id', $id)->delete();
            FinancialAccount::where('branch_id', $id)->delete();
            BranchActionNote::where('branch_id', $id)->delete();
            Expense::where('branch_id', $id)->delete();
            Purchase::where('branch_id', $id)->delete();
            if (method_exists($branch, 'assignedUsers')) {
                $branch->assignedUsers()->detach();
            }
            $branch->delete();
        });

        return response()->json([
            'message' => "Test branch '{$branchName}' purged cleanly from the system."
        ]);
    }
}
