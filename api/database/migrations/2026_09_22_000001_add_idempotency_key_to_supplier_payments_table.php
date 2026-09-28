<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('supplier_payments', 'idempotency_key')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                $table->string('idempotency_key', 100)->nullable()->after('notes');
                $table->unique(['business_id', 'idempotency_key'], 'sp_business_idempotency_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_payments', 'idempotency_key')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                $table->dropUnique('sp_business_idempotency_unique');
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
