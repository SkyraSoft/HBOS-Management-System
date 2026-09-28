<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\User;
use App\Models\Sale;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Services\SaleService;
use App\Services\CustomerPaymentService;
use App\Services\InventoryService;
use App\Services\ExpenseService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;

class BulkSyncService
{
    protected SaleService $saleService;
    protected CustomerPaymentService $customerPaymentService;
    protected InventoryService $inventoryService;
    protected ExpenseService $expenseService;
    protected AuditService $auditService;

    public function __construct(
        SaleService $saleService,
        CustomerPaymentService $customerPaymentService,
        InventoryService $inventoryService,
        ExpenseService $expenseService,
        AuditService $auditService
    ) {
        $this->saleService = $saleService;
        $this->customerPaymentService = $customerPaymentService;
        $this->inventoryService = $inventoryService;
        $this->expenseService = $expenseService;
        $this->auditService = $auditService;
    }

    /**
     * Atomically ingest a bulk array of offline transactions.
     */
    public function ingestBulkTransactions(array $payload, User $user, int $businessId): array
    {
        $salesResults = [];
        $paymentsResults = [];
        $adjustmentsResults = [];
        $expensesResults = [];
        $totalProcessed = 0;

        DB::beginTransaction();
        try {
            // 1. Process Bulk Sales
            if (!empty($payload['sales']) && is_array($payload['sales'])) {
                foreach ($payload['sales'] as $saleData) {
                    $saleData['business_id'] = $businessId;
                    if (!isset($saleData['branch_id']) && $user->branch_id) {
                        $saleData['branch_id'] = $user->branch_id;
                    }
                    
                    $sale = $this->saleService->createSale($saleData, $user);
                    $salesResults[] = [
                        'client_uuid' => $saleData['idempotency_key'] ?? ($saleData['client_uuid'] ?? null),
                        'server_id' => $sale->id,
                        'invoice_number' => $sale->invoice_number,
                        'status' => 'synced',
                        'total' => (float) $sale->total,
                    ];
                    $totalProcessed++;
                }
            }

            // 2. Process Bulk Customer Khata Payments
            if (!empty($payload['khata_payments']) && is_array($payload['khata_payments'])) {
                foreach ($payload['khata_payments'] as $paymentData) {
                    $paymentData['business_id'] = $businessId;
                    if (!isset($paymentData['branch_id']) && $user->branch_id) {
                        $paymentData['branch_id'] = $user->branch_id;
                    }

                    $payment = $this->customerPaymentService->recordPayment($paymentData, $user);
                    $paymentsResults[] = [
                        'client_uuid' => $paymentData['idempotency_key'] ?? ($paymentData['client_uuid'] ?? null),
                        'server_id' => $payment->id,
                        'customer_id' => $payment->customer_id,
                        'amount' => (float) $payment->amount,
                        'status' => 'synced',
                    ];
                    $totalProcessed++;
                }
            }

            // 3. Process Bulk Stock Adjustments
            if (!empty($payload['stock_adjustments']) && is_array($payload['stock_adjustments'])) {
                foreach ($payload['stock_adjustments'] as $adjData) {
                    $adjData['business_id'] = $businessId;
                    if (!isset($adjData['branch_id']) && $user->branch_id) {
                        $adjData['branch_id'] = $user->branch_id;
                    }

                    $adj = $this->inventoryService->adjustStock($adjData, $user);
                    $adjustmentsResults[] = [
                        'client_uuid' => $adjData['idempotency_key'] ?? ($adjData['client_uuid'] ?? null),
                        'product_id' => $adjData['product_id'] ?? null,
                        'quantity' => $adjData['quantity'] ?? null,
                        'status' => 'synced',
                    ];
                    $totalProcessed++;
                }
            }

            // 4. Process Bulk Expenses
            if (!empty($payload['expenses']) && is_array($payload['expenses'])) {
                foreach ($payload['expenses'] as $expData) {
                    $expData['business_id'] = $businessId;
                    if (!isset($expData['branch_id']) && $user->branch_id) {
                        $expData['branch_id'] = $user->branch_id;
                    }

                    $expense = $this->expenseService->recordExpense($expData, $user);
                    $expensesResults[] = [
                        'client_uuid' => $expData['idempotency_key'] ?? ($expData['client_uuid'] ?? null),
                        'server_id' => $expense->id,
                        'amount' => (float) $expense->amount,
                        'status' => 'synced',
                    ];
                    $totalProcessed++;
                }
            }

            DB::commit();

            // Log Audit event
            $this->auditService->log(
                logName: 'sync',
                event: 'bulk_ingested',
                description: "Bulk sync ingested {$totalProcessed} transactions for Business #{$businessId}",
                subject: null,
                properties: [
                    'sales_count' => count($salesResults),
                    'payments_count' => count($paymentsResults),
                    'adjustments_count' => count($adjustmentsResults),
                    'expenses_count' => count($expensesResults),
                    'total_processed' => $totalProcessed,
                ],
                branchId: $user->branch_id,
                businessId: $businessId
            );

            return [
                'status' => 'success',
                'message' => "Successfully synchronized {$totalProcessed} transactions.",
                'total_processed' => $totalProcessed,
                'sales' => $salesResults,
                'khata_payments' => $paymentsResults,
                'stock_adjustments' => $adjustmentsResults,
                'expenses' => $expensesResults,
                'server_timestamp' => now()->toIso8601String(),
            ];

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Bulk sync failed: " . $e->getMessage(), 400, $e);
        }
    }

    /**
     * Get differential master catalog data since a specific timestamp.
     */
    public function getCatalogDelta(int $businessId, ?string $since = null, ?int $branchId = null): array
    {
        $sinceDate = null;
        if ($since) {
            try {
                $sinceDate = Carbon::parse($since);
            } catch (Exception $e) {
                $sinceDate = null;
            }
        }

        // Query Products
        $productsQuery = Product::with(['category', 'subcategory', 'brand', 'branchInventories'])
            ->where('business_id', $businessId);
        if ($sinceDate) {
            $productsQuery->where('updated_at', '>=', $sinceDate);
        }
        $products = $productsQuery->get()->map(function ($p) use ($branchId) {
            $stock = 0;
            if ($branchId) {
                $inv = $p->branchInventories->firstWhere('branch_id', $branchId);
                $stock = $inv ? (int) $inv->quantity_on_hand : 0;
            } else {
                $stock = (int) $p->branchInventories->sum('quantity_on_hand');
            }
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'category_id' => $p->category_id,
                'subcategory_id' => $p->subcategory_id,
                'brand_id' => $p->brand_id,
                'unit' => $p->unit ?? 'pc',
                'cost_price' => (float) $p->cost_price,
                'selling_price' => (float) $p->selling_price,
                'stock' => $stock,
                'low_stock_alert' => (int) ($p->low_stock_alert ?? 5),
                'is_active' => (bool) $p->is_active,
                'updated_at' => $p->updated_at?->toIso8601String(),
            ];
        });

        // Query Categories
        $categoriesQuery = Category::where('business_id', $businessId);
        if ($sinceDate) {
            $categoriesQuery->where('updated_at', '>=', $sinceDate);
        }
        $categories = $categoriesQuery->get(['id', 'name', 'color', 'is_active', 'updated_at']);

        // Query Subcategories
        $subcategoriesQuery = Subcategory::where('business_id', $businessId);
        if ($sinceDate) {
            $subcategoriesQuery->where('updated_at', '>=', $sinceDate);
        }
        $subcategories = $subcategoriesQuery->get(['id', 'category_id', 'name', 'is_active', 'updated_at']);

        // Query Brands
        $brandsQuery = Brand::where('business_id', $businessId);
        if ($sinceDate) {
            $brandsQuery->where('updated_at', '>=', $sinceDate);
        }
        $brands = $brandsQuery->get(['id', 'name', 'is_active', 'updated_at']);

        // Query Suppliers
        $suppliersQuery = Supplier::where('business_id', $businessId);
        if ($sinceDate) {
            $suppliersQuery->where('updated_at', '>=', $sinceDate);
        }
        $suppliers = $suppliersQuery->get(['id', 'name', 'company_name', 'phone', 'current_balance', 'updated_at']);

        // Query Customers
        $customersQuery = Customer::where('business_id', $businessId);
        if ($sinceDate) {
            $customersQuery->where('updated_at', '>=', $sinceDate);
        }
        $customers = $customersQuery->get(['id', 'name', 'phone', 'address', 'opening_balance', 'balance', 'updated_at']);

        return [
            'server_timestamp' => now()->toIso8601String(),
            'since_timestamp' => $since,
            'branch_id' => $branchId,
            'products' => $products,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'suppliers' => $suppliers,
            'customers' => $customers,
        ];
    }
}
