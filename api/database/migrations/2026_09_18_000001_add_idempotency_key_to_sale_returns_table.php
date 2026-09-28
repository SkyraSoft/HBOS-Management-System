<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_returns', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->after('reason');
                $table->unique(['business_id', 'idempotency_key'], 'sale_returns_business_idempotency_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropUnique('sale_returns_business_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
