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
        try {
            DB::statement('ALTER TABLE subcategories MODIFY category_id BIGINT UNSIGNED NULL;');
        } catch (\Exception $e) {
            // If already nullable or different driver
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE subcategories MODIFY category_id BIGINT UNSIGNED NOT NULL;');
        } catch (\Exception $e) {
            //
        }
    }
};
