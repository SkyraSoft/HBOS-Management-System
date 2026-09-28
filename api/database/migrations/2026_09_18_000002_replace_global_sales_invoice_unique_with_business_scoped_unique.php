<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop historical global unique index on invoice_number
            $table->dropUnique('sales_invoice_number_unique');

            // Add tenant-scoped composite unique index on (business_id, invoice_number)
            $table->unique(['business_id', 'invoice_number'], 'sales_business_invoice_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop tenant-scoped composite unique index
            $table->dropUnique('sales_business_invoice_number_unique');

            // Restore original global unique index on invoice_number
            $table->unique('invoice_number', 'sales_invoice_number_unique');
        });
    }
};
