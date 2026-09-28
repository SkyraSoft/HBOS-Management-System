<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('source_account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->foreignId('destination_account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key')->nullable();
            $table->enum('status', ['posted', 'voided'])->default('posted');
            $table->timestamps();

            $table->unique(['business_id', 'idempotency_key'], 'acc_trans_biz_idem_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
