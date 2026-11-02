<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Services\BulkSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class SyncController extends Controller
{
    protected BulkSyncService $bulkSyncService;

    public function __construct(BulkSyncService $bulkSyncService)
    {
        $this->bulkSyncService = $bulkSyncService;
    }

    protected function getBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    /**
     * Ingest a batch of offline transactions atomically (REQ-0030, SRT-GAP-025).
     * POST /api/v1/sync/bulk-transactions
     */
    public function bulkIngest(Request $request)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['Business Owner', 'Branch Manager', 'Salesperson'])) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $this->getBusinessId($request);

        $request->validate([
            'sales' => 'nullable|array',
            'sales.*.idempotency_key' => 'nullable|string|max:255',
            'sales.*.items' => 'required_with:sales|array|min:1',
            'payments' => 'nullable|array',
            'khata_payments' => 'nullable|array',
            'expenses' => 'nullable|array',
        ]);

        try {
            $result = $this->bulkSyncService->ingestBulkTransactions($request->all(), $user, $businessId);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'validation_error',
                'error_type' => 'VALIDATION',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() <= 499 ? $e->getCode() : 422;
            $errorType = isset($e->error_type) ? $e->error_type : 'SERVER_ERROR';
            $failedKey = isset($e->failed_item_key) ? $e->failed_item_key : null;

            return response()->json([
                'status' => 'error',
                'error_type' => $errorType,
                'failed_item_key' => $failedKey,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get differential master catalog data since timestamp.
     * GET /api/v1/sync/catalog-delta
     */
    public function catalogDelta(Request $request)
    {
        $user = $request->user();
        $businessId = $this->getBusinessId($request);

        $since = $request->query('since');
        $branchId = $request->query('branch_id') ? intval($request->query('branch_id')) : ($user->branch_id ? intval($user->branch_id) : null);

        $delta = $this->bulkSyncService->getCatalogDelta($businessId, $since, $branchId);
        return response()->json($delta, 200);
    }

    /**
     * Check sync engine status and server time synchronization.
     * GET /api/v1/sync/status
     */
    public function status(Request $request)
    {
        $businessId = $this->getBusinessId($request);
        $deviceId = $request->header('X-HBOS-Device-ID');

        return response()->json([
            'status' => 'online',
            'engine' => 'HBOS-Sync-Core-v1.0',
            'business_id' => $businessId,
            'server_timestamp' => now()->toIso8601String(),
            'server_timezone' => config('app.timezone', 'UTC'),
            'client_version_status' => 'compatible',
            'supported_sync_protocol' => '1.0',
            'device_id_received' => $deviceId ? substr($deviceId, 0, 36) : null,
        ], 200);
    }
}
