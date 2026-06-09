<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Account types (personal, business, savings, crypto, etc.)
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->decimal('minimum_balance', 20, 2)->default(0);
            $table->decimal('monthly_fee', 20, 2)->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('account_type_id')->constrained('account_types');
            $table->string('account_number', 50)->unique();
            $table->string('iban', 34)->unique()->nullable();
            $table->string('swift_bic', 11)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->decimal('balance', 20, 2)->default(0);
            $table->decimal('available_balance', 20, 2)->default(0);
            $table->decimal('ledger_balance', 20, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->string('label')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_joint')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('account_number');
            $table->index('iban');
        });

        // Account beneficiaries (for recurring transfers)
        Schema::create('account_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_country', 2)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'iban']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_beneficiaries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_types');
    }
};
