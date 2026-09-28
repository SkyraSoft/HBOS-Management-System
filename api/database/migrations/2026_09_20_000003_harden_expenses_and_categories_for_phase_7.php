<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('financial_account_id')->nullable()->after('category_id')->constrained('financial_accounts')->nullOnDelete();
            $table->enum('status', ['posted', 'voided'])->default('posted')->after('amount');
            $table->string('idempotency_key')->nullable()->after('status');
            $table->timestamp('voided_at')->nullable()->after('idempotency_key');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
            $table->softDeletes()->after('updated_at');

            $table->unique(['business_id', 'idempotency_key'], 'expenses_biz_idem_unique');
        });

        // Data migration: Backfill legacy category_id
        $legacyExpenses = DB::table('expenses')->whereNull('category_id')->get();
        foreach ($legacyExpenses as $expense) {
            $busId = $expense->business_id;
            $rawCat = trim((string)$expense->category);
            $catName = $rawCat !== '' ? $rawCat : 'General';

            // Normalized search in same business
            $existingCat = DB::table('expense_categories')
                ->where('business_id', $busId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($catName)])
                ->first();

            if ($existingCat) {
                $catId = $existingCat->id;
            } else {
                // Create missing category preserving display casing
                $catId = DB::table('expense_categories')->insertGetId([
                    'business_id' => $busId,
                    'name' => $catName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('expenses')->where('id', $expense->id)->update([
                'category_id' => $catId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_biz_idem_unique');
            $table->dropForeign(['user_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['financial_account_id']);
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'user_id',
                'category_id',
                'financial_account_id',
                'status',
                'idempotency_key',
                'voided_at',
                'voided_by',
                'void_reason',
                'deleted_at',
            ]);
        });
    }
};
