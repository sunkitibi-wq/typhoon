<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\Transaction;
use App\Services\ComplianceService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ComplianceService $complianceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Transaction::where('user_id', $request->user()->id)
            ->with(['debitAccount:id,account_number,label', 'creditAccount:id,account_number,label']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $transactions = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json($transactions);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $toAccount = Account::findOrFail($validated['to_account_id']);

        if ($fromAccount->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $transaction = $this->transactionService->transfer(
                $fromAccount,
                $toAccount,
                $validated['amount'],
                $validated['description'] ?? null,
                $request->user(),
            );

            $this->complianceService->evaluateTransaction($transaction);

            return response()->json([
                'message' => 'Transfer completed successfully',
                'transaction' => $transaction->load(['debitAccount:id,account_number', 'creditAccount:id,account_number']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Transaction $transaction, Request $request): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'transaction' => $transaction->load(['debitAccount', 'creditAccount', 'fees', 'sepaTransfer', 'swiftTransfer']),
        ]);
    }
}
