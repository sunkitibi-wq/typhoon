<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function __construct(
        private readonly KycService $kycService,
    ) {}

    public function status(Request $request): JsonResponse
    {
        return response()->json($this->kycService->getVerificationStatus($request->user()));
    }

    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_type' => 'nullable|string|in:passport,national_id,drivers_license',
            'id_number' => 'nullable|string|max:50',
            'id_expiry_date' => 'nullable|date',
            'country' => 'required|string|size:2',
            'nationality' => 'nullable|string|size:2',
            'date_of_birth' => 'required|date|before:today',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'source_of_funds' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'employer' => 'nullable|string|max:255',
            'annual_income_range' => 'nullable|numeric',
        ]);

        try {
            $verification = $this->kycService->submitVerification($request->user(), $validated);

            return response()->json([
                'message' => 'KYC verification submitted successfully',
                'status' => $verification->status,
                'kyc_level' => $verification->kyc_level,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|string|in:passport,id_front,id_back,selfie,proof_of_address,bank_statement',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $verification = $request->user()->kycVerification;

        if (!$verification) {
            return response()->json(['message' => 'Submit KYC verification first'], 422);
        }

        $document = $this->kycService->uploadDocument(
            $verification,
            $validated,
            $validated['document_type'],
        );

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document->only(['id', 'document_type', 'status']),
        ], 201);
    }
}
