<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveBusiness
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            if ($user->is_active !== null && !$user->is_active) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact your business administrator.'
                ], 403);
            }

            // Check if X-Business-ID header is explicitly supplied
            $requestedBusinessId = $request->header('X-Business-ID') ?: $request->input('active_business_id');

            if ($requestedBusinessId) {
                // Verify user actually belongs to this business
                $belongsToBusiness = $user->businesses()->where('businesses.id', $requestedBusinessId)->exists();

                if (!$belongsToBusiness) {
                    return response()->json([
                        'message' => 'Unauthorized business context provided.'
                    ], 403);
                }

                $activeBusinessId = (int) $requestedBusinessId;
            } else {
                // Multi-business user MUST explicitly supply active business context.
                // Single membership may resolve implicitly.
                // Zero membership user has NO tenant authority.
                // Do NOT silently fall back to legacy/stale users.business_id.
                $userBusinessCount = $user->businesses()->count();
                if ($userBusinessCount > 1) {
                    $activeBusinessId = 0;
                } elseif ($userBusinessCount === 1) {
                    $activeBusinessId = (int) $user->businesses()->value('businesses.id');
                } else {
                    $activeBusinessId = 0;
                }
            }

            if ($activeBusinessId > 0) {
                // Bind active business ID into container service for request lifetime
                app()->instance('active_business_id', $activeBusinessId);

                // Set Spatie permission team context for tenant-scoped authorization
                if (function_exists('setPermissionsTeamId')) {
                    setPermissionsTeamId($activeBusinessId);
                    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                    $user->unsetRelation('roles')->unsetRelation('permissions');
                }
            }
        }

        return $next($request);
    }

    public static function getActiveBusinessId(): ?int
    {
        if (app()->has('active_business_id') && app('active_business_id')) {
            return (int) app('active_business_id');
        }
        if (Auth::check()) {
            $user = Auth::user();
            $count = $user->businesses()->count();
            if ($count === 1) {
                return (int) $user->businesses()->value('businesses.id');
            }
        }
        return null;
    }

    public static function requireActiveBusinessId(): int
    {
        $id = static::getActiveBusinessId();
        if (!$id || $id <= 0) {
            abort(response()->json(['message' => 'Active business context is required.'], 400));
        }
        return (int) $id;
    }

    public static function getBusinessId(): ?int
    {
        return static::getActiveBusinessId();
    }
}
