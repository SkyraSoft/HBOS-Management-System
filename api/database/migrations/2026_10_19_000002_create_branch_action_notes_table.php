<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * REQ-0029 / SRT-GAP-023: Owner-to-Manager Operational Task Delegation & Action Notes
     */
    public function up(): void
    {
        if (!Schema::hasTable('branch_action_notes')) {
            Schema::create('branch_action_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->string('status', 20)->default('open')->index(); // open, in_progress, resolved
                $table->text('resolution_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['business_id', 'branch_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_action_notes');
    }
};
