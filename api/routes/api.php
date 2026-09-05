<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\SubcategoryController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\KhataController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\RecurringExpenseController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\EmployeeController;
// Phase 5: Analytics & Settings
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SettingController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('subcategories', SubcategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('sales', SaleController::class);
        Route::apiResource('purchases', PurchaseController::class);
        
        // Phase 4: Financials
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('khata', KhataController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('expense-categories', ExpenseCategoryController::class);
        Route::apiResource('recurring-expenses', RecurringExpenseController::class);
        Route::apiResource('employees', EmployeeController::class);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::put('notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
        
        // Phase 5: Analytics & Settings
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);
        Route::get('/reports/purchases', [ReportController::class, 'purchases']);
        
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'store']); // Bulk update/create
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);
    });
});
