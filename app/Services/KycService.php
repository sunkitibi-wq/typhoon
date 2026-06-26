<?php

namespace App\Services;

use App\Events\KycStatusChanged;
use App\Models\Banking\KycDocument;
use App\Models\Banking\KycVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KycService
{
    public function submitVerification(User $user, array $data): KycVerification
    {
        return DB::transaction(function () use ($user, $data) {
            $verification = KycVerification::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'kyc_level' => 'tier_1',
                    'status' => 'pending',
                    'id_type' => $data['id_type'] ?? null,
                    'id_number' => $data['id_number'] ?? null,
                    'id_expiry_date' => $data['id_expiry_date'] ?? null,
                    'country' => $data['country'],
                    'nationality' => $data['nationality'] ?? $data['country'],
                    'date_of_birth' => $data['date_of_birth'],
                    'address_line1' => $data['address_line1'] ?? null,
                    'address_line2' => $data['address_line2'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'source_of_funds' => $data['source_of_funds'] ?? null,
                    'occupation' => $data['occupation'] ?? null,
                    'employer' => $data['employer'] ?? null,
                    'annual_income_range' => $data['annual_income_range'] ?? null,
                ]
            );

            return $verification;
        });
    }

    public function uploadDocument(KycVerification $verification, array $file, string $documentType): KycDocument
    {
        $path = $file['file']->store("kyc/{$verification->user_id}", 'local');

        return $verification->documents()->create([
            'document_type' => $documentType,
            'file_path' => $path,
            'mime_type' => $file['file']->getMimeType(),
            'file_size' => $file['file']->getSize(),
            'status' => 'pending',
        ]);
    }

    public function approveVerification(KycVerification $verification, User $admin): void
    {
        DB::transaction(function () use ($verification, $admin) {
            $verification->update([
                'status' => 'approved',
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);

            $user = $verification->user;
            $user->update([
                'kyc_level' => $verification->kyc_level,
            ]);

            // If the user doesn't have a solaris_person_id, create it via BaasService
            if (!$user->solaris_person_id) {
                $baasService = app(\App\Services\BaasService::class);
                $res = $baasService->createPerson([
                    'first_name' => explode(' ', $user->name, 2)[0] ?? $user->name,
                    'last_name' => explode(' ', $user->name, 2)[1] ?? $user->name,
                    'email' => $user->email,
                    'date_of_birth' => $verification->date_of_birth,
                    'country' => $verification->country,
                    'nationality' => $verification->nationality,
                    'phone_number' => $user->phone_number ?? '+491701234567',
                    'address_line1' => $verification->address_line1,
                    'address_line2' => $verification->address_line2,
                    'postal_code' => $verification->postal_code,
                    'city' => $verification->city,
                ]);

                if ($res['success'] && isset($res['id'])) {
                    $user->update(['solaris_person_id' => $res['id']]);
                } else {
                    \Illuminate\Support\Facades\Log::error('BaaS: Failed to create person on KYC approval: ' . ($res['error'] ?? 'Unknown error'));
                }
            }

            $verification->documents()->update(['status' => 'approved']);
            
            // Broadcast the status change
            KycStatusChanged::dispatch($verification);
        });
    }

    public function rejectVerification(KycVerification $verification, User $admin, string $reason): void
    {
        DB::transaction(function () use ($verification, $admin, $reason) {
            $verification->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);
            
            // Broadcast the status change
            KycStatusChanged::dispatch($verification);
        });
    }

    public function getVerificationStatus(User $user): array
    {
        $verification = $user->kycVerification;

        if (!$verification) {
            return [
                'status' => 'not_submitted',
                'kyc_level' => 'none',
                'submitted' => false,
            ];
        }

        return [
            'status' => $verification->status,
            'kyc_level' => $verification->kyc_level,
            'submitted' => true,
            'pending_documents' => $verification->documents()->where('status', 'pending')->count(),
            'approved_documents' => $verification->documents()->where('status', 'approved')->count(),
        ];
    }
}
