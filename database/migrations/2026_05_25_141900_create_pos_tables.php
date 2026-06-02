<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('serial_number', 50)->unique();
            $table->string('label')->nullable();
            $table->string('model')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('pairing_code', 20)->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('serial_number');
        });

        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('pos_terminal_id')->nullable()->constrained('pos_terminals')->nullOnDelete();
            $table->string('card_brand', 50);
            $table->string('card_last4', 4);
            $table->string('payment_method', 20); // contactless, dip, swipe, manual
            $table->string('terminal_reference', 50)->unique();
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 20)->default('completed');
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index('pos_terminal_id');
            $table->index('terminal_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('pos_terminals');
    }
};
