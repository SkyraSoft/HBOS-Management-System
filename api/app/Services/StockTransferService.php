<?php

namespace App\Services;

use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Verify whether a user is authorized for a specific branch.
     * Owner has business-wide authority.
     * Manager/other roles require branch in primary branch_id or branch_user membership.
     */
    public function isUserAuthorizedForBranch(User $user, int $branchId): bool
    {
        if ($user->hasRole('Business Owner')) {
            return true;
        }

        if ((int) $user->branch_id === $branchId) {
            return true;
        }

        if (method_exists($user, 'branches')) {
            $assigned = $user->branches()->pluck('branches.id')->map(fn($id) => (int) $id)->toArray();
            return in_array($branchId, $assigned, true);
        }

        return false;
    }

    /**
     * Create a new draft stock transfer.
     */
    public function createTransfer(array $data, User $actor): StockTransfer
    {
        $businessId = $data['business_id'] ?? ResolveActiveBusiness::requireActiveBusinessId();

        $fromBranchId = (int) ($data['from_branch_id'] ?? 0);
        $toBranchId = (int) ($data['to_branch_id'] ?? 0);

        if ($fromBranchId <= 0 || $toBranchId <= 0) {
            throw new Exception("Source and destination branch IDs are required.");
        }

        if ($fromBranchId === $toBranchId) {
            throw new Exception("Source and destination branches must be different.");
        }

        // Verify branches exist and are active under active business
        $fromBranch = Branch::where('business_id', $businessId)->findOrFail($fromBranchId);
        $toBranch = Branch::where('business_id', $businessId)->findOrFail($toBranchId);

        if ($fromBranch->status !== 'active') {
            throw new Exception("Source branch is not active.");
        }

        if ($toBranch->status !== 'active') {
            throw new Exception("Destination branch is not active.");
        }

        // Role authorization: Owner or source Manager
        if (!$actor->hasRole('Business Owner')) {
            if (!$actor->hasRole('Branch Manager') && !$actor->hasRole('Inventory Manager')) {
                throw new Exception("Unauthorized: Only Business Owners and Managers can create stock transfers.");
            }
            if (!$this->isUserAuthorizedForBranch($actor, $fromBranchId)) {
                throw new Exception("Unauthorized: You are not assigned to the source branch.");
            }
        }

        $itemsData = $data['items'] ?? [];
        if (empty($itemsData) || !is_array($itemsData)) {
            throw new Exception("At least one product item is required for a stock transfer.");
        }

        // Merge duplicate product items deterministically
        $mergedItems = [];
        foreach ($itemsData as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (string) ($item['quantity'] ?? '0');

            if ($productId <= 0) {
                throw new Exception("Valid product ID required for each item.");
            }

            if (bccomp($qty, '0', 3) <= 0) {
                throw new Exception("Quantity must be greater than zero.");
            }

            // Check decimal precision (scale max 3)
            if (str_contains($qty, '.')) {
                $decimals = strlen(substr(strrchr($qty, "."), 1));
                if ($decimals > 3) {
                    throw new Exception("Quantity cannot exceed 3 decimal places.");
                }
            }

            if (!isset($mergedItems[$productId])) {
                $mergedItems[$productId] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'notes' => $item['notes'] ?? null,
                ];
            } else {
                $mergedItems[$productId]['quantity'] = bcadd($mergedItems[$productId]['quantity'], $qty, 3);
            }
        }

        // Validate products ownership and fractional policies
        foreach ($mergedItems as $productId => $item) {
            $product = Product::where('business_id', $businessId)->findOrFail($productId);
            if (!$product->allow_fractional) {
                $parts = explode('.', $item['quantity']);
                if (isset($parts[1]) && intval($parts[1]) > 0) {
                    throw new Exception("Product '{$product->name}' is a discrete unit and does not allow fractional quantities.");
                }
            }
        }

        return DB::transaction(function () use ($businessId, $fromBranchId, $toBranchId, $data, $actor, $mergedItems) {
            $prefix = 'TRF-' . date('Ymd');
            $count = StockTransfer::where('business_id', $businessId)
                ->whereDate('created_at', now()->toDateString())
                ->count();
            $transferNumber = sprintf('%s-%04d', $prefix, $count + 1);

            $transfer = StockTransfer::create([
                'business_id' => $businessId,
                'transfer_number' => $transferNumber,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($mergedItems as $item) {
                $product = Product::find($item['product_id']);
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity'],
                    'quantity_sent' => $item['quantity'],
                    'quantity_received' => null,
                    'unit_cost' => $product->cost_price ?? 0.00,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->auditService->log(
                logName: 'inventory',
                event: 'created',
                description: "Created draft stock transfer {$transfer->transfer_number}",
                subject: $transfer,
                properties: [
                    'transfer_id' => $transfer->id,
                    'from_branch_id' => $fromBranchId,
                    'to_branch_id' => $toBranchId,
                    'items_count' => count($mergedItems),
                ],
                branchId: $fromBranchId
            );

            return $transfer->load(['items.product', 'fromBranch', 'toBranch']);
        });
    }

    /**
     * Dispatch stock transfer: decrements source branch inventory atomically.
     * Goods enter the 'dispatched' / in-transit state (not sellable at source or destination).
     */
    public function dispatchTransfer(int $transferId, User $actor): StockTransfer
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        return DB::transaction(function () use ($transferId, $businessId, $actor) {
            $transfer = StockTransfer::where('business_id', $businessId)
                ->lockForUpdate()
                ->findOrFail($transferId);

            if ($transfer->status !== 'draft') {
                throw new Exception("Transfer cannot be dispatched because it is in '{$transfer->status}' status.");
            }

            // Role authorization: Owner or source Manager
            if (!$actor->hasRole('Business Owner')) {
                if (!$actor->hasRole('Branch Manager') && !$actor->hasRole('Inventory Manager')) {
                    throw new Exception("Unauthorized: Only Business Owners and Managers can dispatch transfers.");
                }
                if (!$this->isUserAuthorizedForBranch($actor, (int) $transfer->from_branch_id)) {
                    throw new Exception("Unauthorized: You are not assigned to the source branch.");
                }
            }

            // Order items by product_id to guarantee deterministic lock acquisition and prevent deadlocks
            $items = $transfer->items()->orderBy('product_id')->get();
            if ($items->isEmpty()) {
                throw new Exception("Cannot dispatch an empty stock transfer.");
            }

            foreach ($items as $item) {
                $product = Product::where('business_id', $businessId)->findOrFail($item->product_id);

                // Lock source BranchInventory
                $inventory = BranchInventory::where('business_id', $businessId)
                    ->where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $currentQty = $inventory ? (string) $inventory->quantity_on_hand : '0.000';
                $requestedQty = (string) $item->quantity_requested;

                if (bccomp($currentQty, $requestedQty, 3) < 0) {
                    throw new Exception("Insufficient stock for product '{$product->name}' at source branch. Available: {$currentQty}, Requested: {$requestedQty}.");
                }

                // Decrement source inventory
                $inventory->quantity_on_hand = bcsub($currentQty, $requestedQty, 3);
                $inventory->save();

                // Update non-authoritative fallback stock on product
                $newStock = bcsub((string) $product->stock, $requestedQty, 3);
                $product->stock = bccomp($newStock, '0', 3) < 0 ? '0.000' : $newStock;
                $product->save();

                // Create InventoryMovement for source decrement
                InventoryMovement::create([
                    'business_id' => $businessId,
                    'branch_id' => $transfer->from_branch_id,
                    'product_id' => $item->product_id,
                    'type' => 'transfer_out',
                    'quantity' => -abs((float) $requestedQty),
                    'unit_cost' => $item->unit_cost ?? $product->cost_price,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'performed_by' => $actor->id,
                    'balance' => $inventory->quantity_on_hand,
                    'notes' => "Dispatched via transfer {$transfer->transfer_number} to Branch #{$transfer->to_branch_id}",
                ]);

                $item->quantity_sent = $item->quantity_requested;
                $item->save();
            }

            $transfer->update([
                'status' => 'dispatched',
                'dispatched_by' => $actor->id,
                'dispatched_at' => now(),
            ]);

            $this->auditService->log(
                logName: 'inventory',
                event: 'dispatched',
                description: "Dispatched stock transfer {$transfer->transfer_number}",
                subject: $transfer,
                properties: [
                    'transfer_id' => $transfer->id,
                    'dispatched_by' => $actor->id,
                    'from_branch_id' => $transfer->from_branch_id,
                    'to_branch_id' => $transfer->to_branch_id,
                ],
                branchId: $transfer->from_branch_id
            );

            return $transfer->fresh(['items.product', 'fromBranch', 'toBranch', 'dispatchedBy']);
        });
    }

    /**
     * Receive stock transfer: increments destination branch inventory atomically.
     * Completes the transfer lifecycle.
     */
    public function receiveTransfer(int $transferId, User $actor): StockTransfer
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        return DB::transaction(function () use ($transferId, $businessId, $actor) {
            $transfer = StockTransfer::where('business_id', $businessId)
                ->lockForUpdate()
                ->findOrFail($transferId);

            if ($transfer->status === 'received') {
                throw new Exception("Transfer {$transfer->transfer_number} has already been received.");
            }

            if ($transfer->status !== 'dispatched') {
                throw new Exception("Transfer cannot be received because it is in '{$transfer->status}' status.");
            }

            // Role authorization: Owner or destination Manager
            if (!$actor->hasRole('Business Owner')) {
                if (!$actor->hasRole('Branch Manager') && !$actor->hasRole('Inventory Manager')) {
                    throw new Exception("Unauthorized: Only Business Owners and Managers can receive transfers.");
                }
                if (!$this->isUserAuthorizedForBranch($actor, (int) $transfer->to_branch_id)) {
                    throw new Exception("Unauthorized: You are not assigned to the destination branch.");
                }
            }

            // Order items by product_id for deterministic locking
            $items = $transfer->items()->orderBy('product_id')->get();
            if ($items->isEmpty()) {
                throw new Exception("Cannot receive an empty stock transfer.");
            }

            foreach ($items as $item) {
                $product = Product::where('business_id', $businessId)->findOrFail($item->product_id);
                $sentQty = (string) $item->quantity_sent;

                // Lock or create destination BranchInventory
                $inventory = BranchInventory::where('business_id', $businessId)
                    ->where('branch_id', $transfer->to_branch_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    $inventory = BranchInventory::create([
                        'business_id' => $businessId,
                        'branch_id' => $transfer->to_branch_id,
                        'product_id' => $item->product_id,
                        'quantity_on_hand' => '0.000',
                        'minimum_stock' => $product->min_stock ?? 0.000,
                    ]);
                }

                $inventory->quantity_on_hand = bcadd((string) $inventory->quantity_on_hand, $sentQty, 3);
                $inventory->save();

                // Create InventoryMovement for destination increment
                InventoryMovement::create([
                    'business_id' => $businessId,
                    'branch_id' => $transfer->to_branch_id,
                    'product_id' => $item->product_id,
                    'type' => 'transfer_in',
                    'quantity' => abs((float) $sentQty),
                    'unit_cost' => $item->unit_cost ?? $product->cost_price,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'performed_by' => $actor->id,
                    'balance' => $inventory->quantity_on_hand,
                    'notes' => "Received via transfer {$transfer->transfer_number} from Branch #{$transfer->from_branch_id}",
                ]);

                $item->quantity_received = $item->quantity_sent;
                $item->save();
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $actor->id,
                'received_at' => now(),
            ]);

            $this->auditService->log(
                logName: 'inventory',
                event: 'received',
                description: "Received stock transfer {$transfer->transfer_number}",
                subject: $transfer,
                properties: [
                    'transfer_id' => $transfer->id,
                    'received_by' => $actor->id,
                    'from_branch_id' => $transfer->from_branch_id,
                    'to_branch_id' => $transfer->to_branch_id,
                ],
                branchId: $transfer->to_branch_id
            );

            return $transfer->fresh(['items.product', 'fromBranch', 'toBranch', 'dispatchedBy', 'receivedBy']);
        });
    }

    /**
     * Cancel stock transfer:
     * - If draft: marks cancelled, no stock movement.
     * - If dispatched: restores decremented stock back to source with compensating movement.
     * - If received: denied permanently.
     */
    public function cancelTransfer(int $transferId, User $actor, ?string $reason = null): StockTransfer
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        return DB::transaction(function () use ($transferId, $businessId, $actor, $reason) {
            $transfer = StockTransfer::where('business_id', $businessId)
                ->lockForUpdate()
                ->findOrFail($transferId);

            if ($transfer->status === 'received') {
                throw new Exception("Completed transfers cannot be cancelled.");
            }

            if ($transfer->status === 'cancelled') {
                throw new Exception("Transfer {$transfer->transfer_number} is already cancelled.");
            }

            // Role authorization: Owner or source Manager
            if (!$actor->hasRole('Business Owner')) {
                if (!$actor->hasRole('Branch Manager') && !$actor->hasRole('Inventory Manager')) {
                    throw new Exception("Unauthorized: Only Business Owners and Managers can cancel transfers.");
                }
                if (!$this->isUserAuthorizedForBranch($actor, (int) $transfer->from_branch_id)) {
                    throw new Exception("Unauthorized: You are not assigned to the source branch.");
                }
            }

            if ($transfer->status === 'dispatched') {
                // Compensating stock restoration at source branch
                $items = $transfer->items()->orderBy('product_id')->get();
                foreach ($items as $item) {
                    $inventory = BranchInventory::where('business_id', $businessId)
                        ->where('branch_id', $transfer->from_branch_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        $inventory->quantity_on_hand = bcadd((string) $inventory->quantity_on_hand, (string) $item->quantity_sent, 3);
                        $inventory->save();
                    }

                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock = bcadd((string) $product->stock, (string) $item->quantity_sent, 3);
                        $product->save();
                    }

                    InventoryMovement::create([
                        'business_id' => $businessId,
                        'branch_id' => $transfer->from_branch_id,
                        'product_id' => $item->product_id,
                        'type' => 'transfer_in',
                        'quantity' => abs((float) $item->quantity_sent),
                        'unit_cost' => $item->unit_cost,
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'performed_by' => $actor->id,
                        'balance' => $inventory ? $inventory->quantity_on_hand : 0,
                        'notes' => "Compensating return for cancelled transfer {$transfer->transfer_number}. Reason: {$reason}",
                    ]);
                }
            }

            $transfer->update([
                'status' => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->auditService->log(
                logName: 'inventory',
                event: 'cancelled',
                description: "Cancelled stock transfer {$transfer->transfer_number}",
                subject: $transfer,
                properties: [
                    'transfer_id' => $transfer->id,
                    'cancelled_by' => $actor->id,
                    'reason' => $reason,
                ],
                branchId: $transfer->from_branch_id
            );

            return $transfer->fresh(['items.product', 'fromBranch', 'toBranch', 'cancelledBy']);
        });
    }

    /**
     * Get printable challan delivery note data.
     */
    public function getChallanData(int $transferId): array
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        $transfer = StockTransfer::where('business_id', $businessId)
            ->with(['fromBranch', 'toBranch', 'items.product', 'dispatchedBy', 'receivedBy'])
            ->findOrFail($transferId);

        return [
            'transfer_number' => $transfer->transfer_number,
            'status' => $transfer->status,
            'source_branch' => [
                'id' => $transfer->fromBranch->id,
                'name' => $transfer->fromBranch->name,
                'code' => $transfer->fromBranch->code,
                'address' => $transfer->fromBranch->address,
                'phone' => $transfer->fromBranch->phone,
            ],
            'destination_branch' => [
                'id' => $transfer->toBranch->id,
                'name' => $transfer->toBranch->name,
                'code' => $transfer->toBranch->code,
                'address' => $transfer->toBranch->address,
                'phone' => $transfer->toBranch->phone,
            ],
            'dispatched_at' => $transfer->dispatched_at?->toIso8601String(),
            'dispatched_by' => $transfer->dispatchedBy?->name,
            'received_at' => $transfer->received_at?->toIso8601String(),
            'received_by' => $transfer->receivedBy?->name,
            'notes' => $transfer->notes,
            'items' => $transfer->items->map(fn($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_code' => $item->product->code,
                'quantity_requested' => $item->quantity_requested,
                'quantity_sent' => $item->quantity_sent,
                'quantity_received' => $item->quantity_received,
                'unit_cost' => $item->unit_cost,
                'notes' => $item->notes,
            ])->toArray(),
        ];
    }
}
