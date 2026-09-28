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

use App\Http\Controllers\Api\V1\CustomerPaymentController;
use App\Http\Controllers\Api\V1\SupplierPaymentController;
use App\Http\Controllers\Api\V1\FinancialAccountController;
use App\Http\Controllers\Api\V1\AccountTransferController;
use App\Http\Controllers\Api\V1\SaleReturnRefundController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\UserGovernanceController;
use App\Http\Controllers\Api\V1\SyncController;

Route::prefix('v1')->group(function () {
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

    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware(['auth:sanctum', EnsureUserIsActive::class, ResolveActiveBusiness::class])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        
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

        // Branch Inventory & Stock Ledger
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);
        Route::post('inventory/adjustments', [InventoryController::class, 'adjust']);
        Route::post('inventory/transfers', [InventoryController::class, 'transfer']);
        
        // Financials & Khata
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/ledger', [CustomerController::class, 'ledger']);
        Route::get('customers/{customer}/payments', [CustomerPaymentController::class, 'index']);
        Route::post('customers/{customer}/payments', [CustomerPaymentController::class, 'store']);
        Route::post('customer-payments/{payment}/reverse', [CustomerPaymentController::class, 'reverse']);
        Route::apiResource('khata', KhataController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('suppliers', SupplierController::class);
        Route::get('suppliers/{supplier}/payments', [SupplierPaymentController::class, 'index']);
        Route::post('suppliers/{supplier}/payments', [SupplierPaymentController::class, 'store']);
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
        
        // Phase 8 Analytics & Reports
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);
        Route::get('/reports/inventory', [ReportController::class, 'inventory']);
        Route::get('/reports/customers', [ReportController::class, 'customers']);
        Route::get('/reports/suppliers', [ReportController::class, 'suppliers']);
        Route::get('/reports/expenses', [ReportController::class, 'expenses']);
        Route::get('/reports/financial-accounts', [ReportController::class, 'financialAccounts']);
        Route::get('/reports/branches', [ReportController::class, 'branches']);
        
        // Phase 9 Governance, Settings & Audit Trail (Active Business Scoped)
        // Business & Branch Administration
        Route::apiResource('businesses', BusinessController::class);
        Route::apiResource('branches', BranchController::class);

        // Settings Management
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);

        // Phase 9 User Governance
        Route::get('/users', [UserGovernanceController::class, 'index']);
        Route::post('/users', [UserGovernanceController::class, 'store']);
        Route::get('/users/{id}', [UserGovernanceController::class, 'show']);
        Route::put('/users/{id}', [UserGovernanceController::class, 'update']);
        Route::post('/users/{id}/status', [UserGovernanceController::class, 'toggleStatus']);

        // Phase 9 Audit Trail
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);

        // Headless Cloud Sync & Bulk Ingestion Engine
        Route::get('/sync/status', [SyncController::class, 'status']);
        Route::get('/sync/catalog-delta', [SyncController::class, 'catalogDelta']);
        Route::post('/sync/bulk-transactions', [SyncController::class, 'bulkIngest']);
    });
});
