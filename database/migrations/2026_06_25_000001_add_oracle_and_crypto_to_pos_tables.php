<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->string('oracle_terminal_id', 50)->nullable()->unique()->after('serial_number');
            $table->boolean('crypto_processor_enabled')->default(false)->after('model');
            $table->string('default_crypto_currency', 10)->default('USDC')->after('crypto_processor_enabled');
            $table->string('settlement_mode', 20)->default('fiat')->after('default_crypto_currency');
            $table->string('oracle_api_url')->nullable()->after('settlement_mode');
            $table->string('oracle_api_key')->nullable()->after('oracle_api_url');
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('card')->after('payment_method');
            $table->string('crypto_currency', 10)->nullable()->after('payment_type');
            $table->decimal('crypto_amount', 30, 8)->nullable()->after('crypto_currency');
            $table->string('crypto_address', 255)->nullable()->after('crypto_amount');
            $table->string('tx_hash', 255)->nullable()->after('crypto_address');
        });
    }

    public function down(): void
    {
        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropColumn([
                'oracle_terminal_id',
                'crypto_processor_enabled',
                'default_crypto_currency',
                'settlement_mode',
                'oracle_api_url',
                'oracle_api_key',
            ]);
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'crypto_currency',
                'crypto_amount',
                'crypto_address',
                'tx_hash',
            ]);
        });
    }
};
