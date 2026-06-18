<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\SwiftTransfer;
use App\Services\SwiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SwiftController extends Controller
{
    public function __construct(
        private readonly SwiftService $swiftService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $transfers = SwiftTransfer::whereHas('transaction', fn($q) => $q->where('user_id', $user->id))
            ->with('transaction')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'transfers' => $transfers->map(fn($t) => [
                'id' => $t->id,
                'beneficiary_name' => $t->beneficiary_name,
                'beneficiary_account' => $t->beneficiary_account,
                'beneficiary_bank_name' => $t->beneficiary_bank_name,
                'amount' => (float) $t->amount,
                'currency' => $t->currency,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'debit_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'beneficiary_name' => 'required|string|max:255',
            'beneficiary_account' => 'required|string|max:34',
            'bic' => 'required|string|max:11',
            'bank_name' => 'required|string|max:255',
            'beneficiary_address' => 'nullable|string|max:500',
            'bank_address' => 'nullable|string|max:500',
            'remittance_info' => 'nullable|string|max:500',
            'purpose_of_payment' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($validated['debit_account_id']);

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $transfer = $this->swiftService->createInternationalTransfer(
                $user,
                $account,
                $validated['beneficiary_name'],
                $validated['beneficiary_account'],
                $validated['bic'],
                $validated['bank_name'],
                (float) $validated['amount'],
                'EUR',
                [
                    'remittance_info' => $validated['remittance_info'] ?? null,
                    'beneficiary_address' => $validated['beneficiary_address'] ?? null,
                    'beneficiary_bank_address' => $validated['bank_address'] ?? null,
                    'purpose_of_payment' => $validated['purpose_of_payment'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'SWIFT transfer completed successfully',
                'transfer' => $transfer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
