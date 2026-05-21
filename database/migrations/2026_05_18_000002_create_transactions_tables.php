<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Central transaction ledger
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->string('type', 50);
            $table->string('status', 20)->default('pending');
            $table->foreignId('debit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('credit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 20, 2);
            $table->decimal('fee', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
            $table->index(['debit_account_id', 'credit_account_id']);
            $table->index('reference');
            $table->index('created_at');
        });

        // Transaction fees breakdown
        Schema::create('transaction_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('fee_type', 50);
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Recurring / standing orders
        Schema::create('standing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('debit_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('account_beneficiaries')->nullOnDelete();
            $table->string('beneficiary_iban', 34)->nullable();
            $table->string('beneficiary_bic', 11)->nullable();
            $table->string('beneficiary_name');
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('frequency', 20);
            $table->json('schedule')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('next_execution_at');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        // SEPA transfers
        Schema::create('sepa_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('sepa_type', 20);
            $table->string('creditor_name');
            $table->string('creditor_iban', 34);
            $table->string('creditor_bic', 11)->nullable();
            $table->string('remittance_info')->nullable();
            $table->string('end_to_end_id', 35)->nullable();
            $table->string('purpose_code', 10)->nullable();
            $table->timestamps();
        });

        // SWIFT transfers
        Schema::create('swift_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('beneficiary_name');
            $table->string('beneficiary_account', 50);
            $table->string('beneficiary_bic', 11);
            $table->string('beneficiary_bank_name');
            $table->string('beneficiary_bank_address')->nullable();
            $table->string('beneficiary_address')->nullable();
            $table->string('intermediary_bic', 11)->nullable();
            $table->string('remittance_info')->nullable();
            $table->string('charge_bearer', 10)->default('SHA');
            $table->string('purpose_of_payment')->nullable();
            $table->string('sender_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swift_transfers');
        Schema::dropIfExists('sepa_transfers');
        Schema::dropIfExists('standing_orders');
        Schema::dropIfExists('transaction_fees');
        Schema::dropIfExists('transactions');
    }
};
