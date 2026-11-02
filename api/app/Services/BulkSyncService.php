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
use App\Models\CashRegisterSession;
use App\Models\AccountMovement;
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
     * Atomically ingest a bulk array of offline transactions with strict tenant,
     * actor, stock, price, and whole-batch atomicity guarantees (REQ-0030, SRT-GAP-025).
     */
    public function ingestBulkTransactions(array $payload, User $user, int $businessId): array
    {
        // 1. Strict Tenant Isolation: cross-business replay denied
        if (isset($payload['business_id']) && (int) $payload['business_id'] !== $businessId) {
            throw ValidationException::withMessages([
                'business_id' => ['Cross-business replay denied. Payload business does not match authenticated business.'],
            ]);
        }

        $salesResults = [];
        $paymentsResults = [];
        $expensesResults = [];
        $totalProcessed = 0;
        $hasLateReplay = false;

        DB::beginTransaction();
        try {
            // 2. Process Bulk Sales
            if (!empty($payload['sales']) && is_array($payload['sales'])) {
                foreach ($payload['sales'] as $saleData) {
                    $itemKey = $saleData['idempotency_key'] ?? ($saleData['client_uuid'] ?? null);

                    // Cross-tenant guard per sale
                    if (isset($saleData['business_id']) && (int) $saleData['business_id'] !== $businessId) {
                        $ex = new Exception("Cross-business replay denied for sale #{$itemKey}.", 422);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'FORBIDDEN';
                        throw $ex;
                    }
                    $saleData['business_id'] = $businessId;

                    // Actor integrity guard: client cannot spoof another user
                    if (isset($saleData['user_id']) && (int) $saleData['user_id'] !== (int) $user->id) {
                        $ex = new Exception("Cross-user replay denied for sale #{$itemKey}. Authenticated user #{$user->id} does not match payload user #{$saleData['user_id']}.", 422);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'FORBIDDEN';
                        throw $ex;
                    }

                    // Branch validity check
                    $branchId = isset($saleData['branch_id']) ? (int) $saleData['branch_id'] : (int) $user->branch_id;
                    $branchValid = Branch::where('id', $branchId)->where('business_id', $businessId)->exists();
                    if (!$branchValid) {
                        $ex = new Exception("Branch #{$branchId} is invalid or does not belong to business #{$businessId}.", 422);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'VALIDATION';
                        throw $ex;
                    }
                    $saleData['branch_id'] = $branchId;

                    // Preserve historical occurrence date and detect late replay (normalized to app timezone)
                    if (!empty($saleData['occurred_at'])) {
                        try {
                            $occurred = Carbon::parse($saleData['occurred_at'])->setTimezone(config('app.timezone', 'Asia/Karachi'));
                            $saleData['date'] = $occurred->toDateString();
                            if ($this->isLateReplay($saleData['occurred_at'], $businessId, $branchId, $saleData['financial_account_id'] ?? null)) {
                                $hasLateReplay = true;
                            }
                        } catch (Exception $e) {
                            // keep existing date
                        }
                    }

                    // Strict Price Authority Check (Blocker NORM-006: Server price authority; offline manual overrides denied)
                    if (!empty($saleData['items']) && is_array($saleData['items'])) {
                        foreach ($saleData['items'] as $item) {
                            $prodId = $item['product_id'] ?? null;
                            if ($prodId) {
                                $product = Product::where('business_id', $businessId)->find($prodId);
                                if ($product) {
                                    $pricingMode = $item['pricing_mode'] ?? 'retail';
                                    $authoritativePrice = $pricingMode === 'wholesale' && $product->wholesale_price !== null
                                        ? (float) $product->wholesale_price
                                        : (float) $product->selling_price;
                                    $clientPrice = (float) ($item['unit_price'] ?? 0);

                                    if (abs($clientPrice - $authoritativePrice) > 0.01) {
                                        $ex = new Exception("PRICE_CONFLICT: Authoritative price for product '{$product->name}' is {$authoritativePrice}, but cached sale has {$clientPrice}.", 422);
                                        $ex->failed_item_key = $itemKey;
                                        $ex->error_type = 'PRICE_CONFLICT';
                                        throw $ex;
                                    }
                                }
                            }
                        }
                    }

                    // Process Sale through Authoritative SaleService
                    try {
                        $sale = $this->saleService->createSale($saleData, $user);

                        // If historical occurred_at timestamp is present, set movement created_at to prevent later session cash inflation
                        if (!empty($saleData['occurred_at']) && isset($occurred)) {
                            $occurredFormatted = $occurred->format('Y-m-d H:i:s');
                            AccountMovement::where('business_id', $businessId)
                                ->where('reference_type', Sale::class)
                                ->where('reference_id', $sale->id)
                                ->update(['created_at' => $occurredFormatted]);

                            $salePaymentIds = \App\Models\SalePayment::where('sale_id', $sale->id)->pluck('id');
                            if ($salePaymentIds->isNotEmpty()) {
                                AccountMovement::where('business_id', $businessId)
                                    ->where('reference_type', \App\Models\SalePayment::class)
                                    ->whereIn('reference_id', $salePaymentIds)
                                    ->update(['created_at' => $occurredFormatted]);
                            }
                        }

                        $salesResults[] = [
                            'client_uuid' => $itemKey,
                            'server_id' => $sale->id,
                            'invoice_number' => $sale->invoice_number,
                            'status' => 'synced',
                            'total' => (float) $sale->total,
                        ];
                        $totalProcessed++;
                    } catch (Exception $e) {
                        $msg = $e->getMessage();
                        $ex = new Exception($msg, 422, $e);
                        $ex->failed_item_key = $itemKey;
                        if (stripos($msg, 'Insufficient stock') !== false || stripos($msg, 'stock') !== false) {
                            $ex->error_type = 'STOCK_CONFLICT';
                        } else {
                            $ex->error_type = 'VALIDATION';
                        }
                        throw $ex;
                    }
                }
            }

            // 3. Process Bulk Customer Khata Payments
            $paymentsPayload = $payload['payments'] ?? ($payload['khata_payments'] ?? []);
            if (!empty($paymentsPayload) && is_array($paymentsPayload)) {
                foreach ($paymentsPayload as $paymentData) {
                    $itemKey = $paymentData['idempotency_key'] ?? ($paymentData['client_uuid'] ?? null);

                    if (isset($paymentData['business_id']) && (int) $paymentData['business_id'] !== $businessId) {
                        $ex = new Exception("Cross-business replay denied for payment #{$itemKey}.", 422);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'FORBIDDEN';
                        throw $ex;
                    }
                    $paymentData['business_id'] = $businessId;

                    // Actor integrity guard: client cannot spoof another user
                    if (isset($paymentData['user_id']) && (int) $paymentData['user_id'] !== (int) $user->id) {
                        $ex = new Exception("Cross-user replay denied for payment #{$itemKey}. Authenticated user #{$user->id} does not match payload user #{$paymentData['user_id']}.", 422);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'FORBIDDEN';
                        throw $ex;
                    }

                    $branchId = isset($paymentData['branch_id']) ? (int) $paymentData['branch_id'] : (int) $user->branch_id;
                    $paymentData['branch_id'] = $branchId;

                    if (!empty($paymentData['occurred_at'])) {
                        try {
                            $occurred = Carbon::parse($paymentData['occurred_at']);
                            $paymentData['date'] = $occurred->toDateString();
                            if ($this->isLateReplay($paymentData['occurred_at'], $businessId, $branchId, $paymentData['financial_account_id'] ?? null)) {
                                $hasLateReplay = true;
                            }
                        } catch (Exception $e) {}
                    }

                    // Idempotent replay check for CustomerPayment
                    if ($itemKey) {
                        $existing = CustomerPayment::where('business_id', $businessId)
                            ->where('idempotency_key', $itemKey)
                            ->first();
                        if ($existing) {
                            $paymentsResults[] = [
                                'client_uuid' => $itemKey,
                                'server_id' => $existing->id,
                                'customer_id' => $existing->customer_id,
                                'amount' => (float) $existing->amount,
                                'status' => 'synced',
                            ];
                            $totalProcessed++;
                            continue;
                        }
                    }

                    try {
                        $payment = $this->customerPaymentService->recordPayment($paymentData, $user);

                        if (!empty($paymentData['occurred_at']) && isset($occurred)) {
                            AccountMovement::where('business_id', $businessId)
                                ->where('reference_type', CustomerPayment::class)
                                ->where('reference_id', $payment->id)
                                ->update(['created_at' => $occurred]);
                        }

                        $paymentsResults[] = [
                            'client_uuid' => $itemKey,
                            'server_id' => $payment->id,
                            'customer_id' => $payment->customer_id,
                            'amount' => (float) $payment->amount,
                            'status' => 'synced',
                        ];
                        $totalProcessed++;
                    } catch (Exception $e) {
                        $ex = new Exception($e->getMessage(), 422, $e);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'VALIDATION';
                        throw $ex;
                    }
                }
            }

            // 4. Process Bulk Expenses
            if (!empty($payload['expenses']) && is_array($payload['expenses'])) {
                foreach ($payload['expenses'] as $expData) {
                    $itemKey = $expData['idempotency_key'] ?? ($expData['client_uuid'] ?? null);

                    if (isset($expData['business_id']) && (int) $expData['business_id'] !== $businessId) {
                        $ex = new Exception("Cross-business replay denied for expense #{$itemKey}.", 422);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'FORBIDDEN';
                        throw $ex;
                    }
                    $expData['business_id'] = $businessId;

                    // Actor integrity guard: client cannot spoof another user
                    if (isset($expData['user_id']) && (int) $expData['user_id'] !== (int) $user->id) {
                        $ex = new Exception("Cross-user replay denied for expense #{$itemKey}. Authenticated user #{$user->id} does not match payload user #{$expData['user_id']}.", 422);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'FORBIDDEN';
                        throw $ex;
                    }

                    $branchId = isset($expData['branch_id']) ? (int) $expData['branch_id'] : (int) $user->branch_id;
                    $expData['branch_id'] = $branchId;

                    if (!empty($expData['occurred_at'])) {
                        try {
                            $occurred = Carbon::parse($expData['occurred_at']);
                            $expData['date'] = $occurred->toDateString();
                            if ($this->isLateReplay($expData['occurred_at'], $businessId, $branchId, $expData['financial_account_id'] ?? null)) {
                                $hasLateReplay = true;
                            }
                        } catch (Exception $e) {}
                    }

                    // Idempotent replay check for Expense
                    if ($itemKey) {
                        $existing = Expense::where('business_id', $businessId)
                            ->where('idempotency_key', $itemKey)
                            ->first();
                        if ($existing) {
                            $expensesResults[] = [
                                'client_uuid' => $itemKey,
                                'server_id' => $existing->id,
                                'amount' => (float) $existing->amount,
                                'status' => 'synced',
                            ];
                            $totalProcessed++;
                            continue;
                        }
                    }

                    try {
                        // Authoritative call to ExpenseService::createExpense
                        $expense = $this->expenseService->createExpense($expData, $businessId, $user->id);

                        if (!empty($expData['occurred_at']) && isset($occurred)) {
                            AccountMovement::where('business_id', $businessId)
                                ->where('reference_type', Expense::class)
                                ->where('reference_id', $expense->id)
                                ->update(['created_at' => $occurred]);
                        }

                        $expensesResults[] = [
                            'client_uuid' => $itemKey,
                            'server_id' => $expense->id,
                            'amount' => (float) $expense->amount,
                            'status' => 'synced',
                        ];
                        $totalProcessed++;
                    } catch (Exception $e) {
                        $ex = new Exception($e->getMessage(), 422, $e);
                        $ex->failed_item_key = $itemKey;
                        $ex->error_type = 'VALIDATION';
                        throw $ex;
                    }
                }
            }

            DB::commit();

            // Log Audit event with late-sync indicator if applicable
            $this->auditService->log(
                logName: 'sync',
                event: 'bulk_ingested',
                description: "Bulk sync ingested {$totalProcessed} transactions for Business #{$businessId}" . ($hasLateReplay ? " (with late historical replays)" : ""),
                subject: null,
                properties: [
                    'sales_count' => count($salesResults),
                    'payments_count' => count($paymentsResults),
                    'expenses_count' => count($expensesResults),
                    'total_processed' => $totalProcessed,
                    'is_late_replay' => $hasLateReplay,
                    'actor_id' => $user->id,
                ],
                branchId: $user->branch_id,
                businessId: $businessId
            );

            return [
                'status' => 'success',
                'message' => "Successfully synchronized {$totalProcessed} transactions.",
                'total_processed' => $totalProcessed,
                'sales' => $salesResults,
                'payments' => $paymentsResults,
                'expenses' => $expensesResults,
                'is_late_replay' => $hasLateReplay,
                'server_timestamp' => now()->toIso8601String(),
            ];

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
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
                $stock = $inv ? (float) $inv->quantity_on_hand : 0;
            } else {
                $stock = (float) $p->branchInventories->sum('quantity_on_hand');
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
                'wholesale_price' => $p->wholesale_price ? (float) $p->wholesale_price : null,
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

        // Query Suppliers (returned in catalog delta for reference)
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
        $customers = $customersQuery->get(['id', 'name', 'phone', 'secondary_phone', 'address', 'opening_balance', 'balance', 'updated_at']);

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

    /**
     * Determine if an offline transaction's occurred_at timestamp represents a late replay:
     * 1. Calendar date check: occurred prior to today.
     * 2. Same-day session check: occurred prior to a closed session's close time,
     *    or occurred prior to the currently open session's opening time.
     */
    protected function isLateReplay(?string $occurredAt, int $businessId, ?int $branchId, ?int $financialAccountId): bool
    {
        if (empty($occurredAt)) {
            return false;
        }

        try {
            $occurred = Carbon::parse($occurredAt)->setTimezone(config('app.timezone', 'Asia/Karachi'));
        } catch (Exception $e) {
            return false;
        }

        // 1. Calendar date check: prior to today
        if ($occurred->isBefore(today())) {
            return true;
        }

        // 2. Same-day session check: occurred before a closed session's close time,
        // or occurred prior to the currently open session's opening time
        if ($financialAccountId) {
            $closedSessionExists = CashRegisterSession::where('financial_account_id', $financialAccountId)
                ->where('status', 'closed')
                ->where('closed_at', '>=', $occurred)
                ->exists();

            if ($closedSessionExists) {
                return true;
            }

            $openSessionOpenedAfter = CashRegisterSession::where('financial_account_id', $financialAccountId)
                ->where('status', 'open')
                ->where('opened_at', '>', $occurred)
                ->exists();

            if ($openSessionOpenedAfter) {
                return true;
            }
        }

        return false;
    }
}

