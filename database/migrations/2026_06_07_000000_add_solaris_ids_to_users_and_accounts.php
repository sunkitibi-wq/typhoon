<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('solaris_person_id')->nullable()->after('status');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('solaris_account_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('solaris_person_id');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('solaris_account_id');
        });
    }
};
