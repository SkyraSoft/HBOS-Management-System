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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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
                        $reqQty = round(floatval($rItem['quantity'] ?? 0), 3);
                        $reqPrice = isset($rItem['unit_price']) ? round(floatval($rItem['unit_price']), 2) : null;
                        $reqDisc = isset($rItem['discount']) ? round(floatval($rItem['discount']), 2) : null;

                        $matched = $existingSale->items->first(function ($eItem) use ($pId, $reqQty, $reqPrice, $reqDisc) {
                            if (intval($eItem->product_id) !== $pId || abs(round(floatval($eItem->quantity), 3) - $reqQty) > 0.0001) {
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
        $computedSubtotal = '0.00';
        $verifiedApprover = null;
        $overrideReason = null;

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

            if (!$product->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => ["Product '{$product->name}' is inactive and cannot be sold."]
                ]);
            }

            $rawQtyInput = (string) ($itemInput['quantity'] ?? '0');
            if (!preg_match('/^\d+(\.\d{1,3})?$/', $rawQtyInput)) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ['Item quantity must be a positive number with at most 3 decimal places.']
                ]);
            }

            $qty = $rawQtyInput;
            if (bccomp($qty, '0.001', 3) < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ['Item quantity must be at least 0.001.']
                ]);
            }

            // Unit conversion & non-representable conversion guard (SRT-GAP-012)
            $itemUnit = isset($itemInput['unit']) ? strtolower(trim((string)$itemInput['unit'])) : null;
            $baseUnit = strtolower(trim((string)$product->unit));
            $saleUnit = $product->sale_unit ? strtolower(trim((string)$product->sale_unit)) : null;
            $conversionFactor = $product->conversion_factor ? (string)$product->conversion_factor : '1.000';

            if ($itemUnit !== null && $itemUnit !== '' && $saleUnit !== null && $itemUnit === $saleUnit && $itemUnit !== $baseUnit) {
                if (bccomp($conversionFactor, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => ["Invalid conversion factor for product '{$product->name}'."]
                    ]);
                }

                // Check representability in base unit (scale 3)
                // e.g. selling 1 gram with conversion factor 3000 to kg: 1 / 3000 = 0.000333... in 3 decimals = 0.000, 1 gram discarded!
                $baseQty = bcdiv($qty, $conversionFactor, 3);
                $reconstructedQty = bcmul($baseQty, $conversionFactor, 6);
                if (bccomp($qty, $reconstructedQty, 6) !== 0 || bccomp($baseQty, '0.001', 3) < 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => ["Quantity {$qty} {$itemUnit} cannot be represented in base unit {$baseUnit} without remainder loss (conversion factor: {$conversionFactor})."]
                    ]);
                }
                $qty = $baseQty;
            } elseif ($itemUnit !== null && $itemUnit !== '' && $baseUnit !== '' && $itemUnit !== $baseUnit) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit" => ["Unit '{$itemUnit}' is not compatible with product base unit '{$product->unit}' or sale unit '{$product->sale_unit}'."]
                ]);
            }

            // Fractional product eligibility enforcement (SRT-GAP-012)
            if (!$product->allow_fractional && floor((float)$qty) != (float)$qty) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ["Product '{$product->name}' is a discrete item and cannot be sold in fractional quantities."]
                ]);
            }

            // Server-side Selling Price Authority (with wholesale pricing tier support SRT-GAP-013)
            $pricingMode = strtoupper(trim(strval($itemInput['pricing_mode'] ?? $data['pricing_mode'] ?? '')));
            $isWholesale = $pricingMode === 'WHOLESALE'
                || (isset($customer) && (strtolower($customer->customer_type ?? '') === 'wholesale'))
                || (isset($data['pricing_tier']) && strtolower($data['pricing_tier']) === 'wholesale');

            if ($isWholesale && !is_null($product->wholesale_price) && bccomp((string) $product->wholesale_price, '0.00', 2) > 0) {
                $catalogPrice = $this->roundMoney((string) $product->wholesale_price, 2);
            } else {
                $catalogPrice = $this->roundMoney((string) $product->selling_price, 2);
            }
            $requestedPrice = isset($itemInput['unit_price']) ? $this->roundMoney((string) $itemInput['unit_price'], 2) : $catalogPrice;

            if (bccomp((string) $requestedPrice, (string) $catalogPrice, 2) !== 0) {
                // Price override attempted - verify permission or delegated authorization
                if ($user->can('override sale price') || $user->hasRole('Business Owner')) {
                    $verifiedApprover = $user;
                    $overrideReason = $data['override_authorization']['reason'] ?? ($user->hasRole('Business Owner') ? 'Direct Owner price override' : 'Direct Manager price override');
                } else {
                    // Delegated Cashier override requires override_authorization block
                    if (empty($data['override_authorization']) || !is_array($data['override_authorization'])) {
                        throw ValidationException::withMessages([
                            "items.{$index}.unit_price" => ['You are not authorized to override the product selling price. Manager authorization required.']
                        ]);
                    }

                    if (!$verifiedApprover) {
                        $throttleKey = 'price_override:' . $businessId . ':' . $user->id;
                        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                            $seconds = RateLimiter::availableIn($throttleKey);
                            throw ValidationException::withMessages([
                                'override_authorization' => ["Too many failed override authorization attempts. Please try again in {$seconds} seconds."]
                            ]);
                        }

                        $overrideAuth = $data['override_authorization'];
                        $approverEmail = trim(strval($overrideAuth['approver_email'] ?? ''));
                        $approverPassword = strval($overrideAuth['approver_password'] ?? '');
                        $overrideReason = trim(strval($overrideAuth['reason'] ?? 'Price override authorized by manager'));

                        $approver = User::where('business_id', $businessId)
                            ->where('email', $approverEmail)
                            ->where('is_active', true)
                            ->first();

                        $isValidApprover = false;
                        if ($approver && Hash::check($approverPassword, $approver->password)) {
                            // Approver cannot be the cashier themselves
                            if ($approver->id !== $user->id) {
                                // Approver must have Business Owner role or 'override sale price' permission
                                if ($approver->hasRole('Business Owner') || $approver->can('override sale price')) {
                                    // Branch Manager must belong to the sale branch
                                    if ($approver->hasRole('Business Owner') || !$approver->branch_id || intval($approver->branch_id) === intval($branchId)) {
                                        $isValidApprover = true;
                                    }
                                }
                            }
                        }

                        if (!$isValidApprover) {
                            RateLimiter::hit($throttleKey, 300);
                            throw ValidationException::withMessages([
                                'override_authorization' => ['Invalid approver credentials or unauthorized manager for this branch.']
                            ]);
                        }

                        RateLimiter::clear($throttleKey);
                        $verifiedApprover = $approver;
                    }
                }
                $effectiveUnitPrice = $requestedPrice;
            } else {
                $effectiveUnitPrice = $catalogPrice;
            }

            $costPrice = $this->roundMoney((string) ($product->cost_price ?? 0), 2);
            $itemDiscount = isset($itemInput['discount']) ? $this->roundMoney((string) $itemInput['discount'], 2) : '0.00';
            
            if (bccomp((string) $itemDiscount, '0.00', 2) < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => ['Line discount cannot be negative.']
                ]);
            }

            $grossLineTotal = $this->roundMoney(bcmul((string) $qty, (string) $effectiveUnitPrice, 4), 2);
            if (bccomp((string) $itemDiscount, (string) $grossLineTotal, 2) > 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => ['Line discount cannot exceed gross line total.']
                ]);
            }

            $netLineTotal = $this->roundMoney(bcsub((string) $grossLineTotal, (string) $itemDiscount, 4), 2);
            $computedSubtotal = bcadd((string) $computedSubtotal, (string) $netLineTotal, 2);

            $isBelowCost = bccomp((string) $effectiveUnitPrice, (string) $costPrice, 2) < 0;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $effectiveUnitPrice,
                'cost_price' => $costPrice,
                'discount' => $itemDiscount,
                'total' => $netLineTotal,
                'price_overridden' => bccomp((string) $requestedPrice, (string) $catalogPrice, 2) !== 0,
                'below_cost' => $isBelowCost,
                'catalog_price' => $catalogPrice,
            ];
        }

        $computedSubtotal = $this->roundMoney((string) $computedSubtotal, 2);

        // 4. Server-Side Header Calculations
        $saleDiscount = isset($data['discount']) ? $this->roundMoney((string) $data['discount'], 2) : '0.00';
        if (bccomp((string) $saleDiscount, '0.00', 2) < 0) {
            throw ValidationException::withMessages([
                'discount' => ['Sale discount cannot be negative.']
            ]);
        }
        if (bccomp((string) $saleDiscount, (string) $computedSubtotal, 2) > 0) {
            throw ValidationException::withMessages([
                'discount' => ['Sale discount cannot exceed subtotal.']
            ]);
        }

        $netSubtotal = $this->roundMoney(bcsub((string) $computedSubtotal, (string) $saleDiscount, 4), 2);
        $tax = isset($data['tax']) ? $this->roundMoney((string) $data['tax'], 2) : '0.00';
        if (bccomp((string) $tax, '0.00', 2) < 0) {
            throw ValidationException::withMessages([
                'tax' => ['Tax cannot be negative.']
            ]);
        }

        $computedTotal = $this->roundMoney(bcadd((string) $netSubtotal, (string) $tax, 4), 2);

        // 5. Payment Snapshot & Multi-Tender Settlement Enforcement
        $paymentsPayload = $data['payments'] ?? [];
        $processedTenders = [];
        $appliedNonKhataTenders = '0.00';
        $khataTenderAmount = '0.00';

        if (!empty($paymentsPayload) && is_array($paymentsPayload)) {
            foreach ($paymentsPayload as $pIndex => $pPay) {
                $pMethodRaw = trim(strval($pPay['payment_method'] ?? 'Cash'));
                $pMethodLower = strtolower($pMethodRaw);
                $pAmount = $this->roundMoney((string) ($pPay['amount'] ?? 0), 2);

                if (bccomp((string) $pAmount, '0.00', 2) < 0) {
                    throw ValidationException::withMessages([
                        "payments.{$pIndex}.amount" => ['Payment tender amount cannot be negative.']
                    ]);
                }
                if (bccomp((string) $pAmount, '0.00', 2) <= 0) {
                    continue;
                }

                if ($pMethodLower === 'khata') {
                    $khataTenderAmount = bcadd((string) $khataTenderAmount, (string) $pAmount, 2);
                } else {
                    $appliedNonKhataTenders = bcadd((string) $appliedNonKhataTenders, (string) $pAmount, 2);
                    $pAccountId = !empty($pPay['financial_account_id']) ? intval($pPay['financial_account_id']) : null;
                    $pAccount = null;

                    if ($pAccountId) {
                        $pAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                            ->where('id', $pAccountId)
                            ->where('status', 'active')
                            ->first();
                        if (!$pAccount) {
                            throw ValidationException::withMessages([
                                "payments.{$pIndex}.financial_account_id" => ['Selected financial account is invalid, inactive, or belongs to another business.']
                            ]);
                        }
                    } else {
                        if ($pMethodLower === 'cash') {
                            $pAccount = app(\App\Services\FinancialAccountService::class)->getDefaultCashAccount($businessId, $branchId);
                            if (!$pAccount) {
                                throw ValidationException::withMessages([
                                    'branch_id' => ['Branch main cash drawer is not configured or active for cash checkout.']
                                ]);
                            }
                        } else {
                            $pAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                                ->where('type', 'bank')
                                ->where('status', 'active')
                                ->first();
                            if (!$pAccount) {
                                throw ValidationException::withMessages([
                                    "payments.{$pIndex}.financial_account_id" => ['Explicit active bank account is required for non-cash tenders.']
                                ]);
                            }
                        }
                    }

                    if ($pMethodLower === 'cash' && $pAccount->type !== 'cash') {
                        throw ValidationException::withMessages([
                            "payments.{$pIndex}.financial_account_id" => ['Cash payments must be routed to a cash account.']
                        ]);
                    }
                    if ($pMethodLower !== 'cash' && $pAccount->type !== 'bank') {
                        throw ValidationException::withMessages([
                            "payments.{$pIndex}.financial_account_id" => ['Non-cash payments must be routed to a bank account.']
                        ]);
                    }

                    $processedTenders[] = [
                        'payment_method' => $pMethodRaw,
                        'amount' => $pAmount,
                        'account' => $pAccount,
                        'reference_number' => $pPay['reference_number'] ?? null,
                    ];
                }
            }

            $appliedNonKhataTenders = $this->roundMoney((string) $appliedNonKhataTenders, 2);
            $khataTenderAmount = $this->roundMoney((string) $khataTenderAmount, 2);
            $totalTenderedSettlement = $this->roundMoney(bcadd((string) $appliedNonKhataTenders, (string) $khataTenderAmount, 4), 2);

            if (bccomp((string) $totalTenderedSettlement, (string) $computedTotal, 2) > 0) {
                throw ValidationException::withMessages([
                    'payments' => ['Total applied payment tenders exceed the total sale amount.']
                ]);
            }

            $paidAmount = $appliedNonKhataTenders;
            $dueAmount = $this->roundMoney(bcsub((string) $computedTotal, (string) $paidAmount, 4), 2);

            if (bccomp((string) $khataTenderAmount, '0.00', 2) > 0 && bccomp((string) $dueAmount, (string) $khataTenderAmount, 2) !== 0) {
                throw ValidationException::withMessages([
                    'payments' => ["Split tender reconciliation error: applied tenders ({$paidAmount}) + Khata ({$khataTenderAmount}) does not reconcile with total ({$computedTotal})."]
                ]);
            }
        } else {
            // Single tender fallback / legacy contract
            $paidAmount = isset($data['paid_amount']) ? $this->roundMoney((string) $data['paid_amount'], 2) : $computedTotal;
            if (bccomp((string) $paidAmount, '0.00', 2) < 0) {
                throw ValidationException::withMessages([
                    'paid_amount' => ['Paid amount cannot be negative.']
                ]);
            }
            if (bccomp((string) $paidAmount, (string) $computedTotal, 2) > 0) {
                throw ValidationException::withMessages([
                    'paid_amount' => ['Paid amount cannot exceed total sale amount.']
                ]);
            }
            $dueAmount = $this->roundMoney(bcsub((string) $computedTotal, (string) $paidAmount, 4), 2);
        }

        // CREDIT SALE MANDATORY CUSTOMER RULE
        if (bccomp((string) $dueAmount, '0.00', 2) > 0 && !$customerId) {
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

        // Resolve Financial Account for payment settlement if paid_amount > 0 and no split payments
        $targetAccount = null;
        if (empty($processedTenders) && $paidAmount > 0) {
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

        // Determine effective payment method label for sale header
        if (count($processedTenders) > 1) {
            $effectivePaymentMethod = 'Split';
        } elseif (count($processedTenders) === 1) {
            $effectivePaymentMethod = $processedTenders[0]['payment_method'];
        } else {
            $effectivePaymentMethod = ($dueAmount > 0 && $paidAmount == 0) ? 'Khata' : $paymentMethod;
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
            $effectivePaymentMethod,
            $notes,
            $idempotencyKey,
            $lineItems,
            $targetAccount,
            $processedTenders,
            $verifiedApprover,
            $overrideReason,
            $data
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
                'payment_method' => $effectivePaymentMethod,
                'status' => 'completed',
                'notes' => $notes,
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($lineItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'barcode' => $item['product']->barcode ?? null,
                    'sku' => $item['product']->sku ?? null,
                    'product_name' => $item['product']->name ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $item['cost_price'],
                    'unit_cost' => $item['cost_price'],
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
                    $approverId = $verifiedApprover ? $verifiedApprover->id : $user->id;
                    $approverName = $verifiedApprover ? $verifiedApprover->name : $user->name;
                    $approverRole = $verifiedApprover ? ($verifiedApprover->roles->first()?->name ?? 'Approver') : ($user->roles->first()?->name ?? 'User');

                    $overrideAuditProps = [
                        'action' => 'price_override',
                        'sale_id' => $sale->id,
                        'actual_sale_actor_user_id' => $user->id,
                        'sale_actor_user_id' => $user->id,
                        'override_authorized_by_user_id' => $approverId,
                        'override_authorized_by_name' => $approverName,
                        'override_authorized_by_role' => $approverRole,
                        'product_id' => $item['product']->id,
                        'product_name' => $item['product']->name,
                        'catalog_price' => $item['catalog_price'],
                        'override_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'below_cost' => !empty($item['below_cost']),
                        'reason' => $overrideReason ?? 'Price override',
                        'branch_id' => $branchId,
                        'business_id' => (int) $businessId,
                        'timestamp' => now()->toIso8601String(),
                    ];

                    app(\App\Services\AuditService::class)->log(
                        logName: 'sale',
                        event: 'sale.price_override',
                        description: "Price override on Product '{$item['product']->name}' for Sale {$sale->invoice_number}. Catalog: {$item['catalog_price']}, Overridden: {$item['unit_price']}. Approved by: {$approverName}",
                        subject: $sale,
                        properties: $overrideAuditProps,
                        branchId: $branchId,
                        businessId: (int) $businessId,
                        causer: $user
                    );

                    // Also emit standard updated event for transactional audit consumers (Phase 9 regression parity)
                    app(\App\Services\AuditService::class)->log(
                        logName: 'sale',
                        event: 'updated',
                        description: "Price override on Product '{$item['product']->name}' for Sale {$sale->invoice_number}. Catalog: {$item['catalog_price']}, Overridden: {$item['unit_price']}. Approved by: {$approverName}",
                        subject: $sale,
                        properties: $overrideAuditProps,
                        branchId: $branchId,
                        businessId: (int) $businessId,
                        causer: $user
                    );
                }
            }

            // Post payment records & inflow movements
            if (!empty($processedTenders)) {
                foreach ($processedTenders as $pIndex => $tender) {
                    $pAmount = $tender['amount'];
                    $pMethod = $tender['payment_method'];
                    $payAcc = $tender['account'];
                    $pRef = $tender['reference_number'] ?? null;

                    $salePayment = \App\Models\SalePayment::create([
                        'business_id' => $businessId,
                        'sale_id' => $sale->id,
                        'payment_method' => $pMethod,
                        'amount' => $pAmount,
                        'financial_account_id' => $payAcc ? $payAcc->id : null,
                        'reference_number' => $pRef,
                    ]);

                    if ($payAcc) {
                        app(\App\Services\AccountMovementService::class)->postInflow($payAcc, [
                            'branch_id' => $branchId,
                            'movement_category' => 'sale_pos',
                            'reference_type' => \App\Models\SalePayment::class,
                            'reference_id' => $salePayment->id,
                            'amount' => $pAmount,
                            'date' => $saleDate,
                            'description' => "POS Split Payment ({$pMethod}) Invoice #{$invoiceNumber}",
                            'user_id' => $user->id,
                            'idempotency_key' => $idempotencyKey ? "sale_{$idempotencyKey}_{$pIndex}" : null,
                        ]);
                    }
                }
            } elseif ($paidAmount > 0) {
                \App\Models\SalePayment::create([
                    'business_id' => $businessId,
                    'sale_id' => $sale->id,
                    'payment_method' => $effectivePaymentMethod,
                    'amount' => $paidAmount,
                    'financial_account_id' => $targetAccount ? $targetAccount->id : null,
                    'reference_number' => $data['reference_number'] ?? null,
                ]);

                if ($targetAccount) {
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
            }

            if ($customerId) {
                $cust = Customer::find($customerId);
                if ($cust) {
                    app(CustomerAccountService::class)->recalculateBalance($cust);
                }
            }

            return $sale->load(['items.product', 'customer', 'branch', 'user', 'payments']);
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
                    'quantity' => round(floatval($item->quantity), 3),
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

        if ($user && !$user->hasRole('Business Owner')) {
            if ($user->branch_id && intval($sale->branch_id) !== intval($user->branch_id)) {
                throw ValidationException::withMessages([
                    'sale' => ['Unauthorized access to sale record for this branch.']
                ]);
            }
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
                            return intval($eItem->sale_item_id) === $sItemId && abs(round(floatval($eItem->quantity), 3) - round(floatval($rItem['quantity'] ?? 0), 3)) < 0.0001;
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
        $totalRefundAmount = '0.00';

        // Sale-level discount & tax proportional ratio: allocates header discount/tax across returned items
        $saleSubtotal = $this->roundMoney((string) $sale->subtotal, 2);
        $saleTotal = $this->roundMoney((string) $sale->total, 2);
        $saleRatio = (bccomp($saleSubtotal, '0.00', 2) > 0 && bccomp($saleTotal, '0.00', 2) > 0)
            ? bcdiv($saleTotal, $saleSubtotal, 8)
            : '1.00000000';

        foreach ($itemsData as $index => $returnInput) {
            $saleItemId = intval($returnInput['sale_item_id'] ?? 0);

            // Lock SaleItem for update to prevent concurrent over-return race conditions
            $saleItem = SaleItem::where('sale_id', $sale->id)->lockForUpdate()->find($saleItemId);

            if (!$saleItem) {
                throw ValidationException::withMessages([
                    "items.{$index}.sale_item_id" => ["Item #{$saleItemId} does not belong to Sale #{$sale->invoice_number}."]
                ]);
            }

            $rawReturnQty = (string) ($returnInput['quantity'] ?? '0');
            if (!preg_match('/^\d+(\.\d{1,3})?$/', $rawReturnQty)) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ['Return quantity must be a positive number with at most 3 decimal places.']
                ]);
            }

            $returnQty = $rawReturnQty;
            if (bccomp($returnQty, '0.001', 3) < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ['Return quantity must be at least 0.001.']
                ]);
            }

            $remainingReturnable = bcsub((string) $saleItem->quantity, (string) $saleItem->returned_quantity, 3);
            if (bccomp((string) $returnQty, (string) $remainingReturnable, 3) > 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => ["Return quantity ({$returnQty}) exceeds remaining returnable quantity ({$remainingReturnable}) for product #{$saleItem->product_id}."]
                ]);
            }

            // Effective unit refund value derived from line total, line discount, and sale-level discount/tax ratio
            $unitLineNet = bccomp((string) $saleItem->quantity, '0.000', 3) > 0
                ? bcdiv((string) $saleItem->total, (string) $saleItem->quantity, 8)
                : (string) $saleItem->unit_price;
            $effectiveRefundRate = bcmul($unitLineNet, $saleRatio, 6);
            $lineRefundAmount = $this->roundMoney(bcmul((string) $returnQty, $effectiveRefundRate, 4), 2);
            $totalRefundAmount = bcadd((string) $totalRefundAmount, (string) $lineRefundAmount, 2);

            $validatedReturnLines[] = [
                'sale_item' => $saleItem,
                'quantity' => $returnQty,
                'unit_price' => $saleItem->unit_price,
                'refund_amount' => $lineRefundAmount,
                'is_restockable' => isset($returnInput['is_restockable']) ? (bool) $returnInput['is_restockable'] : true,
                'return_reason' => $returnInput['return_reason'] ?? $reason,
            ];
        }

        $totalRefundAmount = $this->roundMoney((string) $totalRefundAmount, 2);

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
                $isRestockable = $line['is_restockable'];
                $retReason = $line['return_reason'];

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'unit_price' => $line['unit_price'],
                    'refund_amount' => $line['refund_amount'],
                    'is_restockable' => $isRestockable,
                    'return_reason' => $retReason,
                ]);

                // Increment returned_quantity on SaleItem
                $saleItem->returned_quantity = bcadd((string) $saleItem->returned_quantity, (string) $qty, 3);
                $saleItem->save();

                // Transactionally restore stock only if item is restockable
                if ($isRestockable) {
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
            }

            // Check if all items in sale are fully returned
            $freshSaleItems = $sale->items()->get();
            $allReturned = $freshSaleItems->every(function ($item) {
                return bccomp((string) $item->returned_quantity, (string) $item->quantity, 3) >= 0;
            });

            $saleDueStr = (string) $sale->due_amount;
            $khataReduction = bccomp((string) $totalRefundAmount, $saleDueStr, 2) > 0 ? $saleDueStr : (string) $totalRefundAmount;
            $newDue = $this->roundMoney(bcsub($saleDueStr, $khataReduction, 4), 2);
            if (bccomp($newDue, '0.00', 2) < 0) {
                $newDue = '0.00';
            }

            $sale->update([
                'status' => $allReturned ? 'returned' : 'partially_returned',
                'due_amount' => $newDue,
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

    /**
     * Exact 2-decimal (or arbitrary scale) deterministic half-up monetary rounding.
     * Uses PHP 8.4 native bcround with pure-string fallback.
     */
    protected function roundMoney(string|float|int $amount, int $scale = 2): string
    {
        $str = is_numeric($amount) ? (string) $amount : '0';
        if (function_exists('bcround')) {
            return bcround($str, $scale);
        }
        $negative = str_starts_with($str, '-');
        $unsigned = ltrim($str, '-');
        $parts = explode('.', $unsigned);
        $intPart = $parts[0] === '' ? '0' : $parts[0];
        $fracPart = $parts[1] ?? '';
        if (strlen($fracPart) <= $scale) {
            $res = $intPart . ($scale > 0 ? '.' . str_pad($fracPart, $scale, '0', STR_PAD_RIGHT) : '');
            return $negative ? '-' . $res : $res;
        }
        $targetFrac = substr($fracPart, 0, $scale);
        $nextDigit = (int) $fracPart[$scale];
        if ($nextDigit >= 5) {
            $factor = bcpow('10', (string) $scale);
            $scaled = bcadd(bcmul($intPart, $factor, 0), $targetFrac === '' ? '0' : $targetFrac, 0);
            $scaledPlus = bcadd($scaled, '1', 0);
            $newInt = bcdiv($scaledPlus, $factor, 0);
            $newFrac = str_pad(bcmod($scaledPlus, $factor), $scale, '0', STR_PAD_LEFT);
            $res = $newInt . ($scale > 0 ? '.' . $newFrac : '');
        } else {
            $res = $intPart . ($scale > 0 ? '.' . $targetFrac : '');
        }
        return $negative ? '-' . $res : $res;
    }
}

