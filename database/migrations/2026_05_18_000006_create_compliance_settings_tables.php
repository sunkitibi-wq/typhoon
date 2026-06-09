<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transaction monitoring rules
        Schema::create('monitoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 50);
            $table->string('rule_type', 50);
            $table->json('conditions');
            $table->string('severity', 20)->default('medium');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('action', 50)->default('flag');
            $table->timestamps();
        });

        // Monitoring alerts
        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_rule_id')->nullable()->constrained('monitoring_rules')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('alert_type', 50);
            $table->string('severity', 20);
            $table->string('status', 20)->default('open');
            $table->text('description');
            $table->json('details')->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });

        // Fee schedules
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fee_type', 50);
            $table->string('calculation_method', 50);
            $table->decimal('fee_value', 20, 6);
            $table->decimal('min_fee', 20, 2)->nullable();
            $table->decimal('max_fee', 20, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->json('tiers')->nullable();
            $table->json('applicable_channels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Platform settings / config
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('type', 50)->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        // Notifications
        Schema::create('bank_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('channel', 20);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Add banking-specific columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('kyc_level', 20)->default('none')->after('role_id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('nationality', 2)->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('nationality');
            $table->string('country_of_residence', 2)->default('DE')->after('date_of_birth');
            $table->boolean('two_factor_enabled')->default(false)->after('country_of_residence');
            $table->string('status', 20)->default('active')->after('two_factor_enabled');
            $table->timestamp('terms_accepted_at')->nullable()->after('status');
            $table->softDeletes()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_level', 'phone', 'nationality', 'date_of_birth',
                'country_of_residence', 'two_factor_enabled', 'status',
                'terms_accepted_at', 'deleted_at',
            ]);
        });
        Schema::dropIfExists('bank_notifications');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('fee_schedules');
        Schema::dropIfExists('monitoring_alerts');
        Schema::dropIfExists('monitoring_rules');
    }
};
