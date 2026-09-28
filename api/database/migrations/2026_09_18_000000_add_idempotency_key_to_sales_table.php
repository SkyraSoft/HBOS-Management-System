<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->after('notes');
                $table->unique(['business_id', 'idempotency_key'], 'sales_business_idempotency_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_business_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
