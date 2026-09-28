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
        Schema::table('activity_log', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();

            $table->index(['business_id', 'created_at'], 'activity_log_business_created_idx');
            $table->index(['business_id', 'branch_id', 'created_at'], 'activity_log_business_branch_idx');
            $table->index(['business_id', 'causer_id', 'created_at'], 'activity_log_business_causer_idx');
            $table->index(['business_id', 'log_name', 'created_at'], 'activity_log_business_log_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['branch_id']);

            $table->dropIndex('activity_log_business_created_idx');
            $table->dropIndex('activity_log_business_branch_idx');
            $table->dropIndex('activity_log_business_causer_idx');
            $table->dropIndex('activity_log_business_log_name_idx');

            $table->dropColumn(['business_id', 'branch_id']);
        });
    }
};
