<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Public Self-Service Account & Tenant Provisioning.
     * Atomically creates Business, Primary Branch, User, Role, Default Financials, and Settings.
     */
    public function register(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', '')))
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'currency' => 'nullable|string|max:10'
        ]);

        $normalizedEmail = $request->email;
        $currency = $request->input('currency', 'PKR');

        $provisioned = DB::transaction(function () use ($request, $normalizedEmail, $currency) {
            // 1. Create Business Tenant with In-Progress Onboarding State
            $business = Business::create([
                'name' => trim($request->business_name),
                'currency' => $currency,
                'type' => 'retail',
                'onboarding_status' => 'in_progress',
                'onboarding_step' => 2,
            ]);

            // 2. Create Initial Primary Branch
            $primaryBranch = Branch::create([
                'business_id' => $business->id,
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'is_primary' => true,
                'status' => 'active'
            ]);

            // 3. Create Root Owner User with Phone (RETAIL-GAP-001)
            $user = User::create([
                'name' => trim($request->name),
                'email' => $normalizedEmail,
                'phone' => $request->filled('phone') ? trim($request->phone) : null,
                'password' => Hash::make($request->password),
                'business_id' => $business->id,
                'branch_id' => $primaryBranch->id,
                'role' => 'Business Owner',
                'is_active' => true,
            ]);

            // 4. Link Memberships
            $user->businesses()->syncWithoutDetaching([$business->id]);
            $user->branches()->syncWithoutDetaching([
                $primaryBranch->id => ['business_id' => $business->id]
            ]);

            // 5. Assign Spatie Team Role with canonical permissions
            $role = \App\Services\UserGovernanceService::getOrCreateTenantRole('Business Owner', $business->id);
            $user->assignRole($role);

            // 6. Provision Default Initial Financial Cash Account (PLATFORM-GAP-008, PF-P1-001)
            FinancialAccount::create([
                'business_id' => $business->id,
                'branch_id' => $primaryBranch->id,
                'name' => 'Main Cash',
                'type' => 'cash',
                'account_number' => 'CASH-MAIN',
                'opening_balance' => 0.00,
                'balance' => 0.00,
                'is_default' => true,
                'status' => 'active',
            ]);

            // 7. Seed Initial Core Business Settings (PLATFORM-GAP-008)
            $defaultSettings = [
                'receipt_header' => $business->name,
                'receipt_footer' => 'Thank you for your business!',
                'invoice_prefix' => 'INV-',
                'currency_symbol' => $currency,
            ];
            foreach ($defaultSettings as $key => $value) {
                Setting::create([
                    'business_id' => $business->id,
                    'key' => $key,
                    'value' => $value
                ]);
            }

            return [
                'user' => $user,
                'business' => $business,
                'branch' => $primaryBranch
            ];
        });

        $user = $provisioned['user'];
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user->load('roles'),
            'business' => $provisioned['business'],
            'branch' => $provisioned['branch'],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * User Login with Email Normalization and Inactive Status Check.
     */
    public function login(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', '')))
        ]);

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->is_active !== null && !$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact your business administrator.'
            ], 403);
        }

        $user->update(['last_login_at' => now()]);
        
        // Scope Spatie team context deterministically without runtime permission mutation
        if ($user->business_id) {
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($user->business_id);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Forgot Password Request with Rate-Limited Expiry Token Generation.
     */
    public function forgotPassword(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', '')))
        ]);

        $request->validate([
            'email' => 'required|string|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // In production, an email is dispatched. Return token in dev/testing for verification.
            $isLocalOrTesting = app()->environment('local', 'testing');

            return response()->json([
                'message' => 'If an account exists with this email, password reset instructions have been dispatched.',
                'reset_token' => $isLocalOrTesting ? $token : null
            ]);
        }

        return response()->json([
            'message' => 'If an account exists with this email, password reset instructions have been dispatched.'
        ]);
    }

    /**
     * Reset Password Execution with Token Verification and Multi-Device Session Invalidation.
     */
    public function resetPassword(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', '')))
        ]);

        $request->validate([
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired password reset token.'],
            ]);
        }

        // Check 60-minute token expiry
        if (now()->subMinutes(60)->isAfter($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages([
                'token' => ['Password reset token has expired. Please request a new one.'],
            ]);
        }

        // Verify token hash
        if (!Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid password reset token.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->password = Hash::make($request->password);
        $user->save();

        // Invalidate all existing device sessions for security
        $user->tokens()->delete();

        // Remove used reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password has been successfully reset. Please log in with your new credentials.'
        ]);
    }

    /**
     * Logout from Current Device.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Logout from All Connected Devices (Multi-Device Session Invalidation).
     */
    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices successfully.'
        ]);
    }

    /**
     * Current Authenticated User Profile & Active Tenant Metadata.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('roles');
        $activeBusinessId = app()->has('active_business_id') ? app('active_business_id') : $user->business_id;

        return response()->json([
            'user' => $user,
            'business' => Business::find($activeBusinessId),
            'branch' => Branch::find($user->branch_id)
        ]);
    }

    /**
     * Complete employee invitation and set initial password.
     */
    public function acceptInvitation(Request $request)
    {
        if ($request->filled('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email', '')))
            ]);
        }

        $request->validate([
            'email' => 'nullable|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } else {
            $user = User::whereNotNull('invitation_token')
                ->where('invitation_expires_at', '>', now())
                ->get()
                ->first(fn ($u) => Hash::check($request->token, $u->invitation_token));
        }

        if (!$user || empty($user->invitation_token) || empty($user->invitation_expires_at)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or non-existent invitation token.'],
            ]);
        }

        if (now()->isAfter($user->invitation_expires_at)) {
            $user->update([
                'invitation_token' => null,
                'invitation_expires_at' => null,
            ]);
            throw ValidationException::withMessages([
                'token' => ['Invitation has expired. Please request a new invitation from your business administrator.'],
            ]);
        }

        if (!Hash::check($request->token, $user->invitation_token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid invitation token.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->is_active = true;
        $user->invitation_token = null;
        $user->invitation_expires_at = null;
        $user->last_login_at = now();
        $user->save();

        // Invalidate any previous device tokens
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Account setup complete. Welcome to HBOS!',
            'user' => $user->load('roles'),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
