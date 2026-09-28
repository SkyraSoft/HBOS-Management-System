<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log a general governance/operational action.
     */
    public function log(
        string $logName,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $branchId = null,
        ?int $businessId = null,
        ?User $causer = null
    ): ?ActivityLog {
        // Resolve tenant authority strictly from active context
        $resolvedBusinessId = $businessId;
        if (!$resolvedBusinessId && app()->has('active_business_id') && app('active_business_id')) {
            $resolvedBusinessId = (int) app('active_business_id');
        } elseif (!$resolvedBusinessId && $subject && isset($subject->business_id)) {
            $resolvedBusinessId = (int) $subject->business_id;
        }

        if (!$resolvedBusinessId) {
            // Never write audit records without a deterministic business scope
            return null;
        }

        // Resolve branch provenance
        $resolvedBranchId = $branchId;
        if ($resolvedBranchId === null && $subject && isset($subject->branch_id)) {
            $resolvedBranchId = (int) $subject->branch_id;
        }

        // Resolve causer
        $resolvedCauser = $causer ?? (Auth::check() ? Auth::user() : null);

        // Sanitize properties to ensure zero sensitive credentials or tokens leak
        $sanitizedProperties = $this->sanitizeProperties($properties);

        return ActivityLog::create([
            'log_name' => strtolower($logName),
            'event' => strtolower($event),
            'description' => $description,
            'business_id' => $resolvedBusinessId,
            'branch_id' => $resolvedBranchId,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'causer_type' => $resolvedCauser ? get_class($resolvedCauser) : null,
            'causer_id' => $resolvedCauser ? $resolvedCauser->getKey() : null,
            'properties' => $sanitizedProperties,
        ]);
    }

    /**
     * Static helper for logging general actions.
     */
    public static function logAction(
        string $logName,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $branchId = null,
        ?User $causer = null,
        ?int $businessId = null
    ): ?ActivityLog {
        return app(self::class)->log(
            logName: $logName,
            event: $event,
            description: $description,
            subject: $subject,
            properties: $properties,
            branchId: $branchId,
            businessId: $businessId,
            causer: $causer
        );
    }

    /**
     * Static helper for logging entity mutations with before/after deltas.
     */
    public static function logMutation(
        string $category,
        string $event,
        string $description,
        Model $subject,
        array $oldValues,
        array $newValues,
        array $properties = [],
        ?int $branchId = null,
        ?User $causer = null,
        ?int $businessId = null
    ): ?ActivityLog {
        $props = array_merge($properties, [
            'old' => self::sanitizeProperties($oldValues),
            'new' => self::sanitizeProperties($newValues),
        ]);

        return app(self::class)->log(
            logName: $category,
            event: $event,
            description: $description,
            subject: $subject,
            properties: $props,
            branchId: $branchId,
            businessId: $businessId,
            causer: $causer
        );
    }

    /**
     * Recursively sanitize array keys and values to prevent secret/token exposure.
     */
    public static function sanitizeProperties(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeProperties($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Determine if a property key contains sensitive secret information.
     */
    protected static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(trim($key));

        // Exact matches
        $exactMatches = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'remember_token',
            'access_token',
            'api_token',
            'token',
            'secret',
            'secret_key',
            'private_key',
            'authorization',
            'cookie',
            'session',
            'cvv',
            'cvv2',
            'pin',
        ];

        if (in_array($normalized, $exactMatches, true)) {
            return true;
        }

        // Substring matches for compound keys
        if (preg_match('/(password|remember_token|access_token|api_token|secret|private_key|api_key|encryption_key|auth_key|_token|token_|cvv|pin)/i', $normalized)) {
            return true;
        }

        return false;
    }
}
