<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckViewAsReadOnly
{
    /**
     * Handle an incoming request.
     * REQ-0006 / SRT-GAP-020: Enforce strict read-only mode during Owner View-As preview.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $viewAsRole = $request->header('X-View-As-Role');
        $viewAsActive = $request->header('X-View-As-Active');

        // Check if View-As mode is indicated via header
        if (!empty($viewAsRole) || $viewAsActive === 'true' || $viewAsActive === '1') {
            $user = $request->user();

            // Guard: Only Business Owner can use View-As preview mode
            if (!$user || !$user->hasRole('Business Owner')) {
                return response()->json([
                    'message' => 'Unauthorized: Only Business Owners can access View-As preview mode.'
                ], 403);
            }

            // Guard: Check target perspective role is valid
            if (!empty($viewAsRole) && !in_array($viewAsRole, ['Branch Manager', 'Cashier'], true)) {
                return response()->json([
                    'message' => 'Invalid View-As perspective role.'
                ], 422);
            }

            // Exemption: View-As exit endpoint allows POST
            if ($request->is('api/v1/view-as/exit') || $request->is('*/view-as/exit')) {
                return $next($request);
            }

            // Deny all mutating HTTP methods
            $method = strtoupper($request->method());
            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return response()->json([
                    'message' => 'Mutations are strictly forbidden during read-only View-As preview mode.',
                    'view_as_role' => $viewAsRole,
                    'is_read_only' => true
                ], 403);
            }
        }

        return $next($request);
    }
}
