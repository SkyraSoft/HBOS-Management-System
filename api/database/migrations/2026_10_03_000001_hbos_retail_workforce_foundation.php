<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Retail Platform & Workforce Foundation Migration (RETAIL-GAP-001..007, 012..014)
     */
    public function up(): void
    {
        // 1. Expand businesses table with onboarding & company profile attributes
        Schema::table('businesses', function (Blueprint $table) {
            if (!Schema::hasColumn('businesses', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('businesses', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
            if (!Schema::hasColumn('businesses', 'country')) {
                $table->string('country')->default('Pakistan')->after('address');
            }
            if (!Schema::hasColumn('businesses', 'timezone')) {
                $table->string('timezone')->default('Asia/Karachi')->after('country');
            }
            if (!Schema::hasColumn('businesses', 'tax_number')) {
                $table->string('tax_number')->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('businesses', 'onboarding_status')) {
                $table->string('onboarding_status')->default('completed')->after('currency');
            }
            if (!Schema::hasColumn('businesses', 'onboarding_step')) {
                $table->integer('onboarding_step')->default(5)->after('onboarding_status');
            }
            if (!Schema::hasColumn('businesses', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            }
        });

        // 2. Expand users table with employee contact, code, and invitation token
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('employee_code');
            }
            if (!Schema::hasColumn('users', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('users', 'invitation_token')) {
                $table->string('invitation_token')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'invitation_expires_at')) {
                $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token');
            }
        });

        // 3. Ensure business-scoped branch code uniqueness
        $indexes = collect(Schema::getIndexes('branches'))->pluck('name')->toArray();
        if (!in_array('branches_business_id_code_unique', $indexes, true)) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unique(['business_id', 'code'], 'branches_business_id_code_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('branches'))->pluck('name')->toArray();
        if (in_array('branches_business_id_code_unique', $indexes, true)) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropUnique('branches_business_id_code_unique');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'employee_code',
                'job_title',
                'hire_date',
                'invitation_token',
                'invitation_expires_at',
            ]);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'website',
                'country',
                'timezone',
                'tax_number',
                'onboarding_status',
                'onboarding_step',
                'onboarding_completed_at',
            ]);
        });
    }
};
