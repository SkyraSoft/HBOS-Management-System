<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckClientVersion
{
    /**
     * Handle an incoming request and enforce client version compatibility.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $minVersion = config('hbos_version.min_supported_client_version', '1.0.0');
        $latestVersion = config('hbos_version.latest_client_version', '1.0.0');
        $expectedSyncSchema = config('hbos_version.sync_schema_version', 1);

        $clientVersion = $request->header('X-HBOS-App-Version') ?: $request->header('X-App-Version');
        $clientSyncSchema = $request->header('X-HBOS-Sync-Schema-Version');

        // 1. Minimum Supported Version Gate
        if ($clientVersion && version_compare($clientVersion, $minVersion, '<')) {
            return response()->json([
                'status' => 'UPDATE_REQUIRED',
                'current_version' => $clientVersion,
                'minimum_supported_version' => $minVersion,
                'latest_version' => $latestVersion,
                'update_url' => config('hbos_version.update_url', 'https://skyrasoft.com/hbos/download'),
                'message' => 'Your HBOS client application is outdated and must be updated to continue syncing.'
            ], 426, [
                'X-HBOS-Update-Status' => 'UPDATE_REQUIRED',
                'X-HBOS-Latest-Version' => $latestVersion,
            ]);
        }

        // 2. Sync Schema Protocol Gate (for sync mutation endpoints)
        if ($clientSyncSchema !== null && $request->is('api/v1/sync/*')) {
            if ((int) $clientSyncSchema !== (int) $expectedSyncSchema) {
                return response()->json([
                    'status' => 'SYNC_SCHEMA_INCOMPATIBLE',
                    'client_sync_schema' => (int) $clientSyncSchema,
                    'server_sync_schema' => (int) $expectedSyncSchema,
                    'message' => 'Offline synchronization protocol mismatch. Please update client application.'
                ], 422, [
                    'X-HBOS-Update-Status' => 'SYNC_SCHEMA_INCOMPATIBLE'
                ]);
            }
        }

        /** @var Response $response */
        $response = $next($request);

        // Determine update status header
        $updateStatus = 'CURRENT';
        if ($clientVersion) {
            if (version_compare($clientVersion, $minVersion, '<')) {
                $updateStatus = 'UPDATE_REQUIRED';
            } elseif (version_compare($clientVersion, $latestVersion, '<')) {
                $updateStatus = 'UPDATE_AVAILABLE';
            }
        }

        $response->headers->set('X-HBOS-Api-Version', config('hbos_version.api_version', 'v1'));
        $response->headers->set('X-HBOS-Latest-Version', $latestVersion);
        $response->headers->set('X-HBOS-Update-Status', $updateStatus);

        return $response;
    }
}
