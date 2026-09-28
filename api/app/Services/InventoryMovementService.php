<?php

namespace App\Services;

use App\Services\InventoryService;

/**
 * @deprecated Legacy InventoryMovementService wrapper - Use App\Services\InventoryService directly.
 */
class InventoryMovementService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function recordMovement(array $data)
    {
        $type = $data['type'] ?? 'in';
        if ($type === 'in') {
            return $this->inventoryService->receiveStock($data);
        } else {
            return $this->inventoryService->issueStock($data);
        }
    }

    public function reverseMovement($movement, string $reason = 'Reversal')
    {
        return $this->inventoryService->reverseMovement($movement, $reason);
    }
}
