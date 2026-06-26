<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $beneficiaries = $request->user()->beneficiaries()->latest()->get();

        return response()->json(['beneficiaries' => $beneficiaries]);
    }

    public function show(Beneficiary $beneficiary, Request $request): JsonResponse
    {
        if ($beneficiary->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['beneficiary' => $beneficiary]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iban' => 'nullable|string|max:34',
            'bic' => 'nullable|string|max:11',
            'bank_name' => 'nullable|string|max:255',
            'bank_country' => 'nullable|string|size:2',
            'email' => 'nullable|email',
            'notes' => 'nullable|string|max:1000',
        ]);

        $beneficiary = $request->user()->beneficiaries()->create($validated);

        return response()->json(['beneficiary' => $beneficiary], 201);
    }

    public function update(Beneficiary $beneficiary, Request $request): JsonResponse
    {
        if ($beneficiary->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'iban' => 'nullable|string|max:34',
            'bic' => 'nullable|string|max:11',
            'bank_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'notes' => 'nullable|string|max:1000',
        ]);

        $beneficiary->update($validated);

        return response()->json(['beneficiary' => $beneficiary]);
    }

    public function destroy(Beneficiary $beneficiary, Request $request): JsonResponse
    {
        if ($beneficiary->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $beneficiary->delete();

        return response()->json(['message' => 'Beneficiary deleted']);
    }
}
