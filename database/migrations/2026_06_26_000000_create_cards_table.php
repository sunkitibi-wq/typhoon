<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('solaris_card_id')->nullable()->unique();
            $table->string('type'); // virtual, physical
            $table->string('cardholder_name');
            $table->string('masked_pan');
            $table->string('expiration_date');
            $table->string('status')->default('pending'); // pending, active, blocked, closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
