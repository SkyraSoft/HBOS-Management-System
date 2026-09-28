<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class SaleService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Resolve active business context from request container or user default.
     */
    public function getActiveBusinessId(User $user): int
    {
        $businessId = \App\Http\Middleware\ResolveActiveBusiness::getActiveBusinessId();
        if (!$businessId) {
            if ($user->businesses()->count() === 1) {
                $businessId = (int) $user->businesses()->value('businesses.id');
            } else {
                $businessId = \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();
            }
        }
        return (int) $businessId;
    }

    /**
     * Create a new Sale atomically with active-business scoping and idempotency protection.
     */
    public function createSale(array $data, User $user): Sale
    {
        $businessId = $this->getActiveBusinessId($user);

        // Check for idempotency key replay
        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existingSale = Sale::with('items')->where('business_id', $businessId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existingSale) {
                // Verify payload parity across branch, customer, payment snapshot, and pricing items
                $isConflicting = false;

                if (isset($data['branch_id']) && intval($data['branch_id']) !== intval($existingSale->branch_id)) {
                    $isConflicting = true;
                }

                $reqCust = isset($data['customer_id']) && $data['customer_id'] ? intval($data['customer_id']) : null;
                $existCust = $existingSale->customer_id ? intval($existingSale->customer_id) : null;
                if ($reqCust !== $existCust) {
                    $isConflicting = true;
                }

                if (isset($data['paid_amount']) && abs(round(floatval($data['paid_amount']), 2) - round(floatval($existingSale->paid_amount), 2)) > 0.001) {
                    $isConflicting = true;
                }

                if (isset($data['payment_method']) && trim(strval($data['payment_method'])) !== trim(strval($existingSale->payment_method))) {
                    $isConflicting = true;
                }

                if (isset($data['discount']) && abs(round(floatval($data['discount']), 2) - round(floatval($existingSale->discount), 2)) > 0.001) {
                    $isConflicting = true;
                }

                if (isset($data['tax']) && abs(round(floatval($data['tax']), 2) - round(floatval($existingSale->tax), 2)) > 0.001) {
                    $isConflicting = true;
                }

                if (!empty($data['financial_account_id'])) {
                    $existingMovement = \App\Models\AccountMovement::where('reference_type', get_class($existingSale))
                        ->where('reference_id', $existingSale->id)
                        ->first();
                    if ($existingMovement && (int) $data['financial_account_id'] !== (int) $existingMovement->account_id) {
                        $isConflicting = true;
                    }
                }

                $reqItems = $data['items'] ?? [];
                if (count($reqItems) !== $existingSale->items->count()) {
                    $isConflicting = true;
                } else {
                    foreach ($reqItems as $rItem) {
                        $pId = intval(explode('_', strval($rItem['product_id'] ?? 0))[0]);
                        $reqQty = intval($rItem['quantity'] ?? 0);
                        $reqPrice = isset($rItem['unit_price']) ? round(floatval($rItem['unit_price']), 2) : null;
                        $reqDisc = isset($rItem['discount']) ? round(floatval($rItem['discount']), 2) : null;

                        $matched = $existingSale->items->first(function ($eItem) use ($pId, $reqQty, $reqPrice, $reqDisc) {
                            if (intval($eItem->product_id) !== $pId || intval($eItem->quantity) !== $reqQty) {
                                return false;
                            }
                            if ($reqPrice !== null && abs(round(floatval($eItem->unit_price), 2) - $reqPrice) > 0.001) {
                                return false;
                            }
                            if ($reqDisc !== null && abs(round(floatval($eItem->discount), 2) - $reqDisc) > 0.001) {
                                return false;
                            }
                            return true;
                        });

                        if (!$matched) {
                            $isConflicting = true;
                            break;
                        }
                    }
                }

                if ($isConflicting) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => ['Idempotency key has already been used with different request parameters.']
                    ]);
                }

                return $existingSale->load(['items.product', 'customer', 'branch', 'user']);
            }
        }

        // 1. Resolve & Authorize Branch
        $branchId = $data['branch_id'] ?? $user->branch_id;
        if (!$branchId) {
            $primaryBranch = Branch::where('business_id', $businessId)->where('is_primary', true)->first()
                ?? Branch::where('business_id', $businessId)->first();
            $branchId = $primaryBranch ? $primaryBranch->id : null;
        }

        if (!$branchId) {
            throw ValidationException::withMessages([
                'branch_id' => ['No active branch resolved for sale transaction.']
            ]);
        }

        // Verify Branch belongs to active Business
        $branch = Branch::where('business_id', $businessId)->where('id', $branchId)->first();
        if (!$branch) {
            throw ValidationException::withMessages([
                'branch_id' => ['Selected branch does not belong to the active business.']
            ]);
        }

        // Branch-restricted roles (Salesperson, Branch Manager) must match assigned branch if branch belongs to active business
        $userBranchBelongsToActiveBusiness = $user->branch_id
            ? Branch::where('business_id', $businessId)->where('id', $user->branch_id)->exists()
            : false;

        if ($userBranchBelongsToActiveBusiness && intval($user->branch_id) !== intval($branchId)) {
            if ($user->hasRole(['Salesperson', 'Branch Manager']) && !$user->hasRole('Business Owner')) {
                throw ValidationException::withMessages([
                    'branch_id' => ['User is not authorized to create sales for this branch.']
                ]);
            }
        }

        // 2. Validate Customer ownership if provided
        $customerId = isset($data['customer_id']) && $data['customer_id'] ? intval($data['customer_id']) : null;
        if ($customerId) {
            $customer = Customer::where('business_id', $businessId)->where('id', $customerId)->first();
            if (!$customer) {
                throw ValidationException::withMessages([
                    'customer_id' => ['Selected customer does not belong to the active business.']
                ]);
            }
        }

        // 3. Validate Products and calculate line items server-side
        $itemsPayload = $data['items'] ?? [];
        if (empty($itemsPayload)) {
            throw ValidationException::withMessages([
                'items' => ['Sale must contain at least one item.']
            ]);
        }

        $lineItems = [];
        $computedSubtotal = 0.00;

        foreach ($itemsPayload as $index => $itemInput) {
            $rawPId = strval($itemInput['product_id']);
            $cleanPId = strpos($rawPId, '_') !== false ? explode('_', $rawPId)[0] : $rawPId;
            $productId = intval($cleanPId);

            $product = Product::where('business_id', $businessId)->where('id', $productId)->first();
            if (!$product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => ["Product #{$productId} does not belong to the active business."]
                ]);
            }

            $qty = intval($itemInput['quantity'] ?? 0);
            if ($qty < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ['Item quantity must be at least 1.']
                ]);
            }

            // Server-side Selling Price Authority
            $catalogPrice = round(floatval($product->selling_price), 2);
            $requestedPrice = isset($itemInput['unit_price']) ? round(floatval($itemInput['unit_price']), 2) : $catalogPrice;

            if (abs($requestedPrice - $catalogPrice) > 0.001) {
                // Price override attempted - verify permission
                if (!$user->can('override sale price') && !$user->hasRole('Business Owner')) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_price" => ['You are not authorized to override the product selling price.']
                    ]);
                }
                $effectiveUnitPrice = $requestedPrice;
            } else {
                $effectiveUnitPrice = $catalogPrice;
            }

            $costPrice = round(floatval($product->cost_price ?? 0), 2);
            $itemDiscount = isset($itemInput['discount']) ? round(floatval($itemInput['discount']), 2) : 0.00;
            
            if ($itemDiscount < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => ['Line discount cannot be negative.']
                ]);
            }

            $grossLineTotal = round($qty * $effectiveUnitPrice, 2);
            if ($itemDiscount > $grossLineTotal) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => ['Line discount cannot exceed gross line total.']
                ]);
            }

            $netLineTotal = round($grossLineTotal - $itemDiscount, 2);
            $computedSubtotal += $netLineTotal;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $effectiveUnitPrice,
                'cost_price' => $costPrice,
                'discount' => $itemDiscount,
                'total' => $netLineTotal,
                'price_overridden' => abs($requestedPrice - $catalogPrice) > 0.001,
                'catalog_price' => $catalogPrice,
            ];
        }

        $computedSubtotal = round($computedSubtotal, 2);

        // 4. Server-Side Header Calculations
        $saleDiscount = isset($data['discount']) ? round(floatval($data['discount']), 2) : 0.00;
        if ($saleDiscount < 0) {
            throw ValidationException::withMessages([
                'discount' => ['Sale discount cannot be negative.']
            ]);
        }
        if ($saleDiscount > $computedSubtotal) {
            throw ValidationException::withMessages([
                'discount' => ['Sale discount cannot exceed subtotal.']
            ]);
        }

        $netSubtotal = round($computedSubtotal - $saleDiscount, 2);
        $tax = isset($data['tax']) ? round(floatval($data['tax']), 2) : 0.00;
        if ($tax < 0) {
            throw ValidationException::withMessages([
                'tax' => ['Tax cannot be negative.']
            ]);
        }

        $computedTotal = round($netSubtotal + $tax, 2);

        // 5. Payment Snapshot & Credit Rule Enforcement
        $paidAmount = isset($data['paid_amount']) ? round(floatval($data['paid_amount']), 2) : $computedTotal;
        if ($paidAmount < 0) {
            throw ValidationException::withMessages([
                'paid_amount' => ['Paid amount cannot be negative.']
            ]);
        }
        if ($paidAmount > $computedTotal) {
            throw ValidationException::withMessages([
                'paid_amount' => ['Paid amount cannot exceed total sale amount.']
            ]);
        }

        $dueAmount = round($computedTotal - $paidAmount, 2);

        // CREDIT SALE MANDATORY CUSTOMER RULE
        if ($dueAmount > 0 && !$customerId) {
            throw ValidationException::withMessages([
                'customer_id' => ['A registered customer is required for credit sales with unpaid due amounts.']
            ]);
        }

        // 6. Generate Unique Tenant Invoice Number
        $invoiceNumber = $data['invoice_number'] ?? null;
        if (!$invoiceNumber || Sale::where('business_id', $businessId)->where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(10000, 99999);
            while (Sale::where('business_id', $businessId)->where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(10000, 99999);
            }
        }

        $saleDate = isset($data['date']) ? date('Y-m-d', strtotime($data['date'])) : date('Y-m-d');
        $paymentMethod = $data['payment_method'] ?? 'Cash';
        $notes = $data['notes'] ?? null;

        // Resolve Financial Account for payment settlement if paid_amount > 0
        $targetAccount = null;
        if ($paidAmount > 0) {
            if (!empty($data['financial_account_id'])) {
                $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                    ->where('id', $data['financial_account_id'])
                    ->where('status', 'active')
                    ->first();
                if (!$targetAccount) {
                    throw ValidationException::withMessages([
                        'financial_account_id' => ['Selected financial account is invalid or inactive.']
                    ]);
                }
            } else {
                $isCash = strtolower(trim($paymentMethod)) === 'cash';
                if ($isCash) {
                    $targetAccount = app(\App\Services\FinancialAccountService::class)->getDefaultCashAccount($businessId, $branchId);
                    if (!$targetAccount) {
                        throw ValidationException::withMessages([
                            'branch_id' => ['Branch main cash drawer is not configured or active for cash checkout.']
                        ]);
                    }
                } else {
                    $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                        ->where('type', 'bank')
                        ->where('status', 'active')
                        ->first();
                    if (!$targetAccount) {
                        throw ValidationException::withMessages([
                            'financial_account_id' => ['Explicit bank account selection is required for non-cash payment methods.']
                        ]);
                    }
                }
            }

            if (!$targetAccount || $targetAccount->status !== 'active') {
                throw ValidationException::withMessages([
                    'financial_account_id' => ['Active destination financial account is required for payment settlement.']
                ]);
            }

            $isCash = strtolower(trim($paymentMethod)) === 'cash';
            if ($isCash && $targetAccount->type !== 'cash') {
                throw ValidationException::withMessages([
                    'financial_account_id' => ['Cash payments must be routed to a cash account.']
                ]);
            }
            if (!$isCash && $targetAccount->type !== 'bank') {
                throw ValidationException::withMessages([
                    'financial_account_id' => ['Non-cash payments must be routed to a bank account.']
                ]);
            }
        }

        // 7. Execute Atomic Transaction
        return DB::transaction(function () use (
            $businessId,
            $branchId,
            $user,
            $customerId,
            $invoiceNumber,
            $saleDate,
            $computedSubtotal,
            $saleDiscount,
            $tax,
            $computedTotal,
            $paidAmount,
            $dueAmount,
            $paymentMethod,
            $notes,
            $idempotencyKey,
            $lineItems,
            $targetAccount
        ) {
            $sale = Sale::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'invoice_number' => $invoiceNumber,
                'date' => $saleDate,
                'subtotal' => $computedSubtotal,
                'discount' => $saleDiscount,
                'tax' => $tax,
                'total' => $computedTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'notes' => $notes,
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($lineItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $item['cost_price'],
                    'discount' => $item['discount'],
                    'total' => $item['total'],
                    'returned_quantity' => 0,
                ]);

                // Transactionally issue stock via unified InventoryService
                $this->inventoryService->issueStock([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'performed_by' => $user->id,
                    'notes' => "Sale #{$sale->invoice_number}",
                ]);

                if (!empty($item['price_overridden'])) {
                    app(\App\Services\AuditService::class)->log(
                        logName: 'sale',
                        event: 'updated',
                        description: "Price override on Product '{$item['product']->name}' for Sale {$sale->invoice_number}. Catalog: {$item['catalog_price']}, Overridden: {$item['unit_price']}",
                        subject: $sale,
                        properties: [
                            'action' => 'price_override',
                            'sale_id' => $sale->id,
                            'product_id' => $item['product']->id,
                            'catalog_price' => $item['catalog_price'],
                            'override_price' => $item['unit_price'],
                            'quantity' => $item['quantity'],
                        ],
                        branchId: $branchId,
                        businessId: (int) $businessId,
                        causer: $user
                    );
                }
            }

            // Post inflow movement for paid sale amount
            if ($targetAccount && $paidAmount > 0) {
                app(\App\Services\AccountMovementService::class)->postInflow($targetAccount, [
                    'branch_id' => $branchId,
                    'movement_category' => 'sale_pos',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'amount' => $paidAmount,
                    'date' => $saleDate,
                    'description' => "POS Sale Invoice #{$invoiceNumber}",
                    'user_id' => $user->id,
                    'idempotency_key' => $idempotencyKey ? "sale_{$idempotencyKey}" : null,
                ]);
            }

            if ($customerId) {
                $cust = Customer::find($customerId);
                if ($cust) {
                    app(CustomerAccountService::class)->recalculateBalance($cust);
                }
            }

            return $sale->load(['items.product', 'customer', 'branch', 'user']);
        });
    }

    /**
     * Cancel an unpaid Sale atomically and restore stock.
     */
    public function cancelSale(Sale $sale, string $reason, User $user): Sale
    {
        $businessId = $this->getActiveBusinessId($user);

        if (intval($sale->business_id) !== intval($businessId)) {
            throw ValidationException::withMessages([
                'sale' => ['Unauthorized access to sale record.']
            ]);
        }

        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages([
                'status' => ["Cannot cancel sale with status '{$sale->status}'."]
            ]);
        }

        // FINANCIAL CANCELLATION PROTECTION RULE
        if ($sale->paid_amount > 0) {
            throw ValidationException::withMessages([
                'sale' => ['Sale with recorded payment cannot be cancelled directly. Use return/refund workflow.']
            ]);
        }

        return DB::transaction(function () use ($sale, $reason, $user) {
            $sale->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $reason,
            ]);

            foreach ($sale->items as $item) {
                $this->inventoryService->receiveStock([
                    'business_id' => $sale->business_id,
                    'branch_id' => $sale->branch_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'type' => 'sale_return',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'performed_by' => $user->id,
                    'notes' => "Sale cancellation reversal for #{$sale->invoice_number}",
                ]);
            }

            if ($sale->customer_id) {
                $cust = Customer::find($sale->customer_id);
                if ($cust) {
                    app(CustomerAccountService::class)->recalculateBalance($cust);
                }
            }

            return $sale->fresh(['items.product', 'customer', 'branch', 'user', 'cancelledBy']);
        });
    }

    /**
     * Process partial or full Sale return atomically and restore stock.
     */
    public function processReturn(Sale $sale, array $itemsData, ?string $reason, User $user, ?string $idempotencyKey = null): SaleReturn
    {
        $businessId = $this->getActiveBusinessId($user);

        if (intval($sale->business_id) !== intval($businessId)) {
            throw ValidationException::withMessages([
                'sale' => ['Unauthorized access to sale record.']
            ]);
        }

        // Check for return idempotency key replay
        if ($idempotencyKey) {
            $existingReturn = SaleReturn::with('items')->where('business_id', $businessId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existingReturn) {
                // Verify payload parity
                $isConflicting = false;
                if (intval($existingReturn->sale_id) !== intval($sale->id)) {
                    $isConflicting = true;
                } elseif (count($itemsData) !== $existingReturn->items->count()) {
                    $isConflicting = true;
                } else {
                    foreach ($itemsData as $rItem) {
                        $sItemId = intval($rItem['sale_item_id'] ?? 0);
                        $matched = $existingReturn->items->first(function ($eItem) use ($sItemId, $rItem) {
                            return intval($eItem->sale_item_id) === $sItemId && intval($eItem->quantity) === intval($rItem['quantity'] ?? 0);
                        });
                        if (!$matched) {
                            $isConflicting = true;
                            break;
                        }
                    }
                }

                if ($isConflicting) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => ['Idempotency key has already been used with different return parameters.']
                    ]);
                }

                return $existingReturn->load(['items.product', 'sale', 'branch', 'user']);
            }
        }

        if (!in_array($sale->status, ['completed', 'partially_returned'])) {
            throw ValidationException::withMessages([
                'status' => ["Cannot return items from a sale with status '{$sale->status}'."]
            ]);
        }

        if (empty($itemsData)) {
            throw ValidationException::withMessages([
                'items' => ['Return request must contain at least one item.']
            ]);
        }

        $saleItemsMap = $sale->items->keyBy('id');
        $validatedReturnLines = [];
        $totalRefundAmount = 0.00;

        // Sale-level discount & tax proportional ratio: allocates header discount/tax across returned items
        $saleSubtotal = round(floatval($sale->subtotal), 2);
        $saleTotal = round(floatval($sale->total), 2);
        $saleRatio = ($saleSubtotal > 0 && $saleTotal > 0) ? ($saleTotal / $saleSubtotal) : 1.0;

        foreach ($itemsData as $index => $returnInput) {
            $saleItemId = intval($returnInput['sale_item_id'] ?? 0);

            // Lock SaleItem for update to prevent concurrent over-return race conditions
            $saleItem = SaleItem::where('sale_id', $sale->id)->lockForUpdate()->find($saleItemId);

            if (!$saleItem) {
                throw ValidationException::withMessages([
                    "items.{$index}.sale_item_id" => ["Item #{$saleItemId} does not belong to Sale #{$sale->invoice_number}."]
                ]);
            }

            $returnQty = intval($returnInput['quantity'] ?? 0);
            if ($returnQty < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ['Return quantity must be at least 1.']
                ]);
            }

            $remainingReturnable = $saleItem->quantity - $saleItem->returned_quantity;
            if ($returnQty > $remainingReturnable) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ["Return quantity ({$returnQty}) exceeds remaining returnable quantity ({$remainingReturnable}) for product #{$saleItem->product_id}."]
                ]);
            }

            // Effective unit refund value derived from line total, line discount, and sale-level discount/tax ratio
            $unitLineNet = $saleItem->quantity > 0 ? ($saleItem->total / $saleItem->quantity) : $saleItem->unit_price;
            $effectiveRefundRate = round($unitLineNet * $saleRatio, 4);
            $lineRefundAmount = round($returnQty * $effectiveRefundRate, 2);
            $totalRefundAmount += $lineRefundAmount;

            $validatedReturnLines[] = [
                'sale_item' => $saleItem,
                'quantity' => $returnQty,
                'unit_price' => $saleItem->unit_price,
                'refund_amount' => $lineRefundAmount,
            ];
        }

        $totalRefundAmount = round($totalRefundAmount, 2);

        // Generate Return Number
        $returnNumber = 'RET-' . date('Ymd') . '-' . rand(10000, 99999);
        while (SaleReturn::where('business_id', $sale->business_id)->where('return_number', $returnNumber)->exists()) {
            $returnNumber = 'RET-' . date('Ymd') . '-' . rand(10000, 99999);
        }

        return DB::transaction(function () use ($sale, $user, $returnNumber, $totalRefundAmount, $reason, $idempotencyKey, $validatedReturnLines) {
            $saleReturn = SaleReturn::create([
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'return_number' => $returnNumber,
                'refund_amount' => $totalRefundAmount,
                'reason' => $reason,
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($validatedReturnLines as $line) {
                /** @var SaleItem $saleItem */
                $saleItem = $line['sale_item'];
                $qty = $line['quantity'];

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'unit_price' => $line['unit_price'],
                    'refund_amount' => $line['refund_amount'],
                ]);

                // Increment returned_quantity on SaleItem
                $saleItem->increment('returned_quantity', $qty);

                // Transactionally restore stock via unified InventoryService
                $this->inventoryService->receiveStock([
                    'business_id' => $sale->business_id,
                    'branch_id' => $sale->branch_id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'type' => 'sale_return',
                    'reference_type' => SaleReturn::class,
                    'reference_id' => $saleReturn->id,
                    'performed_by' => $user->id,
                    'notes' => "Partial return {$returnNumber} for Sale #{$sale->invoice_number}",
                ]);
            }

            // Check if all items in sale are fully returned
            $freshSaleItems = $sale->items()->get();
            $allReturned = $freshSaleItems->every(function ($item) {
                return $item->returned_quantity >= $item->quantity;
            });

            $sale->update([
                'status' => $allReturned ? 'returned' : 'partially_returned',
            ]);

            if ($sale->customer_id) {
                $cust = Customer::find($sale->customer_id);
                if ($cust) {
                    app(CustomerAccountService::class)->recalculateBalance($cust);
                }
            }

            return $saleReturn->load(['items.product', 'sale', 'branch', 'user']);
        });
    }
}
