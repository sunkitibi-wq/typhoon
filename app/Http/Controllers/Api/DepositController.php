<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $deposits = Transaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->latest()
            ->get();

        return response()->json([
            'deposits' => $deposits->map(fn($d) => [
                'id' => $d->id,
                'account_id' => $d->debit_account_id ?? $d->credit_account_id,
                'amount' => (float) $d->amount,
                'method' => $d->description,
                'reference' => $d->reference,
                'status' => $d->status,
                'created_at' => $d->created_at,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $refCode = $validated['reference'] ?? ('DEP-' . strtoupper(bin2hex(random_bytes(6))));

            $transaction = $this->transactionService->deposit(
                $account,
                (float) $validated['amount'],
                $validated['method'] ?? 'bank_transfer',
                $refCode,
            );

            return response()->json([
                'message' => 'Deposit created successfully',
                'deposit' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
