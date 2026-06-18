<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\SepaTransfer;
use App\Services\SepaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SepaController extends Controller
{
    public function __construct(
        private readonly SepaService $sepaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $transfers = SepaTransfer::whereHas('transaction', fn($q) => $q->where('user_id', $user->id))
            ->with('transaction')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'transfers' => $transfers->map(fn($t) => [
                'id' => $t->id,
                'creditor_name' => $t->creditor_name,
                'creditor_iban' => $t->creditor_iban,
                'debtor_name' => $t->debtor_name,
                'debtor_iban' => $t->debtor_iban,
                'amount' => (float) $t->amount,
                'currency' => $t->currency,
                'remittance_info' => $t->remittance_info,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]),
        ]);
    }

    public function storeTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'debit_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'creditor_name' => 'required|string|max:255',
            'creditor_iban' => 'required|string|max:34',
            'bic' => 'nullable|string|max:11',
            'remittance_info' => 'nullable|string|max:500',
        ]);

        $account = Account::findOrFail($validated['debit_account_id']);

        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $bic = $validated['bic'] ?? 'TYPHOONBXXX';

            $transfer = $this->sepaService->createCreditTransfer(
                $account,
                $validated['creditor_name'],
                $validated['creditor_iban'],
                $bic,
                (float) $validated['amount'],
                $validated['remittance_info'] ?? null,
            );

            return response()->json([
                'message' => 'SEPA credit transfer completed successfully',
                'transfer' => $transfer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeDirectDebit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credit_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'debtor_name' => 'required|string|max:255',
            'debtor_iban' => 'required|string|max:34',
            'mandate_reference' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($validated['credit_account_id']);

        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $debit = $this->sepaService->createDirectDebit(
                $account,
                $validated['debtor_name'],
                $validated['debtor_iban'],
                (float) $validated['amount'],
                $validated['mandate_reference'] ?? null,
            );

            return response()->json([
                'message' => 'SEPA direct debit initiated successfully',
                'direct_debit' => $debit,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
