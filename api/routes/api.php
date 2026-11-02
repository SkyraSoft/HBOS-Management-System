<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\SubcategoryController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\KhataController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\RecurringExpenseController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\ProfileController;

use App\Http\Middleware\ResolveActiveBusiness;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\CheckClientVersion;

use App\Http\Controllers\Api\V1\CustomerPaymentController;
use App\Http\Controllers\Api\V1\SupplierPaymentController;
use App\Http\Controllers\Api\V1\FinancialAccountController;
use App\Http\Controllers\Api\V1\AccountTransferController;
use App\Http\Controllers\Api\V1\SaleReturnRefundController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\UserGovernanceController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\SupplierReturnController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\CashRegisterSessionController;
use App\Http\Controllers\Api\V1\StockTransferController;
use App\Http\Controllers\Api\V1\BranchActionNoteController;
use App\Http\Controllers\Api\V1\ViewAsController;
use App\Http\Middleware\CheckViewAsReadOnly;

Route::prefix('v1')->middleware([CheckClientVersion::class])->group(function () {
    // Release Health Check (Boot + Database connectivity, zero internal credential exposure)
    Route::get('/health', function () {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return response()->json([
                'status' => 'ok',
                'database' => 'ok',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'degraded',
                'database' => 'unavailable',
            ], 503);
        }
    });

    Route::get('/ping', function () {
        return response()->json(['status' => 'pong'], 200);
    });

    // Public Authentication & Password Recovery Endpoints with Server-Side Throttling
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/auth/accept-invitation', [AuthController::class, 'acceptInvitation'])->middleware('throttle:6,1');

    Route::middleware(['auth:sanctum', EnsureUserIsActive::class, ResolveActiveBusiness::class, CheckViewAsReadOnly::class])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all-devices', [AuthController::class, 'logoutAllDevices']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Staged Onboarding
        Route::get('/onboarding/status', [OnboardingController::class, 'status']);
        Route::post('/onboarding/step', [OnboardingController::class, 'saveStep']);
        
        // Profile Management
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

        // Core Operational Resources
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('subcategories', SubcategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('sales', SaleController::class);
        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel']);
        Route::post('sales/{sale}/returns', [SaleController::class, 'returns']);
        Route::get('sales/{sale}/returns', [SaleController::class, 'listReturns']);
        Route::post('sale-returns/{saleReturn}/settle-refund', [SaleReturnRefundController::class, 'settleRefund']);
        Route::post('sale-returns/{saleReturn}/reverse-refund', [SaleReturnRefundController::class, 'reverseRefund']);
        Route::apiResource('purchases', PurchaseController::class);
        Route::post('purchases/{id}/cancel', [PurchaseController::class, 'cancel']);
        Route::apiResource('supplier-returns', SupplierReturnController::class)->only(['index', 'store', 'show']);

        // Branch Inventory & Stock Ledger
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);
        Route::post('inventory/adjustments', [InventoryController::class, 'adjust']);
        // Legacy transfer endpoint compatibility: must enter authoritative StockTransfer state machine (Section 40)
        Route::post('inventory/transfers', function (Request $request) {
            if ($request->user() && !$request->user()->hasPermissionTo('transfer inventory')) {
                return response()->json(['message' => 'Unauthorized action. Required permission: transfer inventory'], 403);
            }

            $request->validate([
                'from_branch_id' => 'required|integer',
                'to_branch_id' => 'required|integer|different:from_branch_id',
                'product_id' => 'required|integer',
                'quantity' => 'required|numeric|min:0.001',
                'notes' => 'nullable|string',
            ]);

            try {
                $businessId = ResolveActiveBusiness::requireActiveBusinessId();
                $user = $request->user();
                $isOwner = $user && $user->hasRole('Business Owner');
                $userBranchId = $user ? $user->branch_id : null;

                if (!$isOwner && $userBranchId && intval($request->from_branch_id) !== intval($userBranchId)) {
                    return response()->json(['message' => 'Cannot transfer stock from a branch you are not assigned to.'], 403);
                }

                $transferService = app(\App\Services\StockTransferService::class);
                $transfer = $transferService->createTransfer([
                    'business_id' => $businessId,
                    'from_branch_id' => intval($request->from_branch_id),
                    'to_branch_id' => intval($request->to_branch_id),
                    'items' => [
                        [
                            'product_id' => intval($request->product_id),
                            'quantity' => (string) $request->quantity,
                            'notes' => $request->notes ?? 'Inter-branch stock transfer',
                        ]
                    ],
                    'notes' => $request->notes ?? 'Inter-branch stock transfer',
                ], $user);

                $transfer = $transferService->dispatchTransfer($transfer->id, $user);
                $transfer = $transferService->receiveTransfer($transfer->id, $user);

                $outMovement = \App\Models\InventoryMovement::where('reference_type', \App\Models\StockTransfer::class)
                    ->where('reference_id', $transfer->id)
                    ->where('type', 'transfer_out')
                    ->first();

                $inMovement = \App\Models\InventoryMovement::where('reference_type', \App\Models\StockTransfer::class)
                    ->where('reference_id', $transfer->id)
                    ->where('type', 'transfer_in')
                    ->first();

                app(\App\Services\AuditService::class)->log(
                    logName: 'inventory',
                    event: 'updated',
                    description: "Stock transfer of qty {$request->quantity} from Branch #{$request->from_branch_id} to Branch #{$request->to_branch_id}",
                    subject: $outMovement,
                    properties: [
                        'action' => 'inventory_transfer',
                        'product_id' => (int) $request->product_id,
                        'from_branch_id' => (int) $request->from_branch_id,
                        'to_branch_id' => (int) $request->to_branch_id,
                        'quantity' => (int) $request->quantity,
                        'notes' => $request->notes,
                    ],
                    branchId: (int) $request->from_branch_id,
                    businessId: (int) $businessId
                );

                return response()->json([
                    'message' => 'Stock transfer recorded successfully',
                    'out_movement' => $outMovement,
                    'in_movement' => $inMovement,
                    'transfer' => $transfer,
                ], 201);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        });
        
        // Financials & Khata
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/ledger', [CustomerController::class, 'ledger']);
        Route::get('customers/{customer}/payments', [CustomerPaymentController::class, 'index']);
        Route::post('customers/{customer}/payments', [CustomerPaymentController::class, 'store']);
        Route::get('customer-payments', [CustomerPaymentController::class, 'allPayments']);
        Route::post('customer-payments/{payment}/reverse', [CustomerPaymentController::class, 'reverse']);
        Route::apiResource('khata', KhataController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('suppliers', SupplierController::class);
        Route::get('suppliers/{supplier}/payments', [SupplierPaymentController::class, 'index']);
        Route::post('suppliers/{supplier}/payments', [SupplierPaymentController::class, 'store']);
        Route::get('supplier-payments', [SupplierPaymentController::class, 'allPayments']);
        Route::post('supplier-payments/{id}/reverse', [SupplierPaymentController::class, 'reverse']);
        Route::apiResource('expenses', ExpenseController::class);
        Route::post('expenses/{expense}/void', [ExpenseController::class, 'void']);
        Route::apiResource('expense-categories', ExpenseCategoryController::class);
        Route::apiResource('recurring-expenses', RecurringExpenseController::class);

        // Phase 7 Cash & Bank Financial Accounts & Movements
        Route::apiResource('financial-accounts', FinancialAccountController::class);
        Route::get('financial-accounts/{account}/movements', [FinancialAccountController::class, 'movements']);
        Route::post('financial-accounts/{account}/capital-in', [FinancialAccountController::class, 'capitalIn']);
        Route::post('financial-accounts/{account}/capital-out', [FinancialAccountController::class, 'capitalOut']);
        Route::apiResource('account-transfers', AccountTransferController::class)->only(['index', 'store']);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::put('notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
        
        // Cash Register Sessions (Day-Open, Day-Close, Float & Reconciliation)
        Route::get('/cash-register-sessions/current', [CashRegisterSessionController::class, 'currentSession']);
        Route::post('/cash-register-sessions/open', [CashRegisterSessionController::class, 'open']);
        Route::post('/cash-register-sessions/close', [CashRegisterSessionController::class, 'close']);
        Route::get('/cash-register-sessions/daily-summary', [CashRegisterSessionController::class, 'dailySummary']);
        Route::get('/cash-register-sessions', [CashRegisterSessionController::class, 'index']);
        Route::get('/cash-register-sessions/{id}', [CashRegisterSessionController::class, 'show']);

        // Analytics & Reports
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);
        Route::get('/reports/profitability', [ReportController::class, 'profitability']);
        Route::get('/reports/inventory', [ReportController::class, 'inventory']);
        Route::get('/reports/customers', [ReportController::class, 'customers']);
        Route::get('/reports/suppliers', [ReportController::class, 'suppliers']);
        Route::get('/reports/expenses', [ReportController::class, 'expenses']);
        Route::get('/reports/financial-accounts', [ReportController::class, 'financialAccounts']);
        Route::get('/reports/branches', [ReportController::class, 'branches']);
        
        // Governance, Settings & Audit Trail (Active Business Scoped)
        Route::apiResource('businesses', BusinessController::class);
        Route::get('branches/{id}/readiness', [BranchController::class, 'readiness']);
        Route::get('branches/{id}/decommission-preview', [BranchController::class, 'previewDecommission']);
        Route::post('branches/{id}/consolidate-and-decommission', [BranchController::class, 'consolidateAndDecommission']);
        Route::post('branches/{id}/purge-test-branch', [BranchController::class, 'purgeTestBranch']);
        Route::apiResource('branches', BranchController::class);

        // Phase 7: Inter-Branch Stock Transfers & Challan Lifecycle (REQ-0018 / SRT-GAP-021)
        Route::get('transfers', [StockTransferController::class, 'index']);
        Route::post('transfers', [StockTransferController::class, 'store']);
        Route::get('transfers/{id}', [StockTransferController::class, 'show']);
        Route::post('transfers/{id}/dispatch', [StockTransferController::class, 'dispatch']);
        Route::post('transfers/{id}/receive', [StockTransferController::class, 'receive']);
        Route::post('transfers/{id}/cancel', [StockTransferController::class, 'cancel']);
        Route::get('transfers/{id}/challan', [StockTransferController::class, 'challan']);

        // Stock-transfers route aliases for test and client parity
        Route::get('stock-transfers', [StockTransferController::class, 'index']);
        Route::post('stock-transfers', [StockTransferController::class, 'store']);
        Route::get('stock-transfers/{id}', [StockTransferController::class, 'show']);
        Route::post('stock-transfers/{id}/dispatch', [StockTransferController::class, 'dispatch']);
        Route::post('stock-transfers/{id}/receive', [StockTransferController::class, 'receive']);
        Route::post('stock-transfers/{id}/cancel', [StockTransferController::class, 'cancel']);
        Route::get('stock-transfers/{id}/challan', [StockTransferController::class, 'challan']);

        // Phase 7: Branch Action Notes (REQ-0029 / SRT-GAP-023)
        Route::get('action-notes/pending-count', [BranchActionNoteController::class, 'pendingCount']);
        Route::get('action-notes', [BranchActionNoteController::class, 'index']);
        Route::post('action-notes', [BranchActionNoteController::class, 'store']);
        Route::patch('action-notes/{id}/status', [BranchActionNoteController::class, 'updateStatus']);

        // Phase 7: Owner View-As Preview Mode Audit (REQ-0006 / SRT-GAP-020)
        Route::post('view-as/enter', [ViewAsController::class, 'enter']);
        Route::post('view-as/exit', [ViewAsController::class, 'exit']);

        // Settings Management
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);

        // User Governance
        Route::get('/users', [UserGovernanceController::class, 'index']);
        Route::post('/users', [UserGovernanceController::class, 'store']);
        Route::get('/users/{id}', [UserGovernanceController::class, 'show']);
        Route::put('/users/{id}', [UserGovernanceController::class, 'update']);
        Route::post('/users/{id}/status', [UserGovernanceController::class, 'toggleStatus']);

        // Audit Trail
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);

        // Headless Cloud Sync & Bulk Ingestion Engine
        Route::get('/sync/status', [SyncController::class, 'status']);
        Route::get('/sync/catalog-delta', [SyncController::class, 'catalogDelta']);
        Route::post('/sync/bulk-transactions', [SyncController::class, 'bulkIngest']);
    });
});
