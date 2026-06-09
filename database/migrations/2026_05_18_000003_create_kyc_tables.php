<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // KYC verifications (one per user)
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kyc_level', 20)->default('tier_1');
            $table->string('status', 20)->default('pending');
            $table->string('id_type', 50)->nullable();
            $table->string('id_number')->nullable();
            $table->date('id_expiry_date')->nullable();
            $table->string('country', 2);
            $table->string('nationality', 2)->nullable();
            $table->date('date_of_birth');
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('source_of_funds')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->decimal('annual_income_range', 20, 2)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'kyc_level']);
        });

        // KYC documents (uploaded files)
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_verification_id')->constrained('kyc_verifications')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Sanctions screening logs
        Schema::create('sanctions_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('list_type', 50);
            $table->string('matched_term')->nullable();
            $table->string('match_score', 10)->nullable();
            $table->string('status', 20)->default('clear');
            $table->json('screening_result')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('screened_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Suspicious activity reports
        Schema::create('suspicious_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('risk_level', 20);
            $table->string('category', 50);
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('reported_at');
            $table->foreignId('reported_by')->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspicious_activities');
        Schema::dropIfExists('sanctions_screenings');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('kyc_verifications');
    }
};
