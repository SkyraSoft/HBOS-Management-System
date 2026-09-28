<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'business_name' => 'required|string|max:255'
        ]);

        $business = Business::create([
            'name' => $request->business_name
        ]);

        // Create initial primary branch for the tenant
        $primaryBranch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'business_id' => $business->id,
            'branch_id' => $primaryBranch->id,
            'role' => 'Business Owner'
        ]);

        // Ensure business_user and branch_user memberships are populated cleanly with business_id
        $user->businesses()->syncWithoutDetaching([$business->id]);
        $user->branches()->syncWithoutDetaching([
            $primaryBranch->id => ['business_id' => $business->id]
        ]);

        // Assign canonical Spatie role with team context
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($business->id);
        }
        $role = Role::firstOrCreate(['name' => 'Business Owner', 'business_id' => $business->id]);
        $user->assignRole($role);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'business' => $business,
            'branch' => $primaryBranch,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
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

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact your business administrator.'
            ], 403);
        }

        $user->update(['last_login_at' => now()]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

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
}
