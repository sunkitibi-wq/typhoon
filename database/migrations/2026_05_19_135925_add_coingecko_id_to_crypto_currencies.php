<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crypto_currencies', function (Blueprint $table) {
            $table->string('coingecko_id', 50)->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('crypto_currencies', function (Blueprint $table) {
            $table->dropColumn('coingecko_id');
        });
    }
};
