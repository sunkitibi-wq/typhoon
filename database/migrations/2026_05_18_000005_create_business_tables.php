<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Business / corporate profiles
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('registered_address')->nullable();
            $table->string('business_type', 50)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->year('founded_year')->nullable();
            $table->integer('employee_count')->nullable();
            $table->decimal('annual_revenue', 20, 2)->nullable();
            $table->json('documents')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // Corporate user roles (multi-user business accounts)
        Schema::create('corporate_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained('business_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 50);
            $table->json('permissions')->nullable();
            $table->decimal('spending_limit', 20, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['business_profile_id', 'user_id']);
        });

        // Bulk payments (corporate payroll/supplier)
        Schema::create('bulk_payments', function (Blueprint $table) {
            $table->id();
            $table->string('batch_reference', 50)->unique();
            $table->foreignId('business_profile_id')->constrained('business_profiles')->cascadeOnDelete();
            $table->foreignId('debit_account_id')->constrained('accounts');
            $table->integer('total_transactions');
            $table->decimal('total_amount', 20, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Bulk payment items
        Schema::create('bulk_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_payment_id')->constrained('bulk_payments')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('beneficiary_name');
            $table->string('beneficiary_iban', 34);
            $table->string('beneficiary_bic', 11)->nullable();
            $table->decimal('amount', 20, 2);
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        // API clients for programmatic access
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('client_id', 80)->unique();
            $table->string('client_secret');
            $table->json('scopes')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
        Schema::dropIfExists('bulk_payment_items');
        Schema::dropIfExists('bulk_payments');
        Schema::dropIfExists('corporate_users');
        Schema::dropIfExists('business_profiles');
    }
};
