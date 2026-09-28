<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create branch_user pivot table if it doesn't exist
        if (!Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['business_id', 'branch_id', 'user_id']);
            });
        }

        // 2. Data Backfill: Populate business_user for all existing users with business_id
        $users = DB::table('users')->whereNotNull('business_id')->get();
        foreach ($users as $user) {
            $exists = DB::table('business_user')
                ->where('business_id', $user->business_id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$exists) {
                DB::table('business_user')->insert([
                    'business_id' => $user->business_id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Ensure every business has at least one primary branch
        $businesses = DB::table('businesses')->get();
        foreach ($businesses as $business) {
            $primaryBranch = DB::table('branches')
                ->where('business_id', $business->id)
                ->where('is_primary', true)
                ->first();

            if (!$primaryBranch) {
                // Check if any branch exists
                $anyBranch = DB::table('branches')->where('business_id', $business->id)->first();
                if ($anyBranch) {
                    DB::table('branches')->where('id', $anyBranch->id)->update(['is_primary' => true]);
                    $primaryBranchId = $anyBranch->id;
                } else {
                    $primaryBranchId = DB::table('branches')->insertGetId([
                        'business_id' => $business->id,
                        'name' => 'Main Branch',
                        'code' => 'MAIN',
                        'is_primary' => true,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $primaryBranchId = $primaryBranch->id;
            }

            // Also backfill users.branch_id if null
            DB::table('users')
                ->where('business_id', $business->id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $primaryBranchId]);
        }

        // 4. Backfill NULL branch_id on operational tables using the tenant's primary branch
        $operationalTables = ['sales', 'purchases', 'expenses', 'khata_transactions', 'supplier_payments'];
        foreach ($operationalTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                foreach ($businesses as $business) {
                    $primaryBranch = DB::table('branches')
                        ->where('business_id', $business->id)
                        ->where('is_primary', true)
                        ->first();

                    if ($primaryBranch) {
                        DB::table($table)
                            ->where('business_id', $business->id)
                            ->whereNull('branch_id')
                            ->update(['branch_id' => $primaryBranch->id]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
