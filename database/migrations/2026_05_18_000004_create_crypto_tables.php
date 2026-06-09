<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supported crypto currencies
        Schema::create('crypto_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('network', 50);
            $table->string('contract_address')->nullable();
            $table->integer('decimals')->default(18);
            $table->decimal('minimum_withdrawal', 20, 8)->default(0);
            $table->decimal('withdrawal_fee', 20, 8)->default(0);
            $table->decimal('minimum_deposit', 20, 8)->default(0);
            $table->decimal('deposit_fee', 20, 8)->default(0);
            $table->string('status', 20)->default('active');
            $table->string('icon_url')->nullable();
            $table->boolean('is_quote_currency')->default(false);
            $table->timestamps();
        });

        // Crypto wallets per user
        Schema::create('crypto_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crypto_currency_id')->constrained('crypto_currencies')->cascadeOnDelete();
            $table->string('address', 255)->unique();
            $table->string('label')->nullable();
            $table->decimal('balance', 30, 8)->default(0);
            $table->decimal('locked_balance', 30, 8)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'crypto_currency_id']);
        });

        // Exchange rates (crypto/fiat and crypto/crypto)
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 10);
            $table->string('quote_currency', 10);
            $table->decimal('bid', 20, 8);
            $table->decimal('ask', 20, 8);
            $table->decimal('mid_rate', 20, 8);
            $table->decimal('change_24h', 20, 8)->default(0);
            $table->decimal('volume_24h', 30, 8)->default(0);
            $table->decimal('high_24h', 20, 8)->nullable();
            $table->decimal('low_24h', 20, 8)->nullable();
            $table->timestamp('last_refreshed_at');
            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency']);
        });

        // Crypto orders (buy/sell)
        Schema::create('crypto_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('order_type', 20);
            $table->string('side', 10);
            $table->string('base_currency', 10);
            $table->string('quote_currency', 10);
            $table->decimal('amount', 30, 8);
            $table->decimal('filled_amount', 30, 8)->default(0);
            $table->decimal('price', 20, 8)->nullable();
            $table->decimal('stop_price', 20, 8)->nullable();
            $table->decimal('fee', 30, 8)->default(0);
            $table->decimal('fee_rate', 10, 6)->default(0);
            $table->decimal('total', 20, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->string('time_in_force', 20)->default('GTC');
            $table->string('failure_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('filled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'side']);
            $table->index(['base_currency', 'quote_currency']);
        });

        // Crypto deposits (incoming)
        Schema::create('crypto_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crypto_currency_id')->constrained('crypto_currencies');
            $table->foreignId('crypto_wallet_id')->constrained('crypto_wallets');
            $table->string('tx_hash', 255)->unique()->nullable();
            $table->decimal('amount', 30, 8);
            $table->decimal('fee', 30, 8)->default(0);
            $table->decimal('net_amount', 30, 8);
            $table->string('from_address', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->integer('confirmations')->default(0);
            $table->json('blockchain_meta')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        // Crypto withdrawals (outgoing)
        Schema::create('crypto_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crypto_currency_id')->constrained('crypto_currencies');
            $table->foreignId('crypto_wallet_id')->constrained('crypto_wallets');
            $table->string('tx_hash', 255)->unique()->nullable();
            $table->string('to_address', 255);
            $table->decimal('amount', 30, 8);
            $table->decimal('fee', 30, 8);
            $table->decimal('net_amount', 30, 8);
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->json('blockchain_meta')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_withdrawals');
        Schema::dropIfExists('crypto_deposits');
        Schema::dropIfExists('crypto_orders');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('crypto_wallets');
        Schema::dropIfExists('crypto_currencies');
    }
};
