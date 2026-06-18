<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banking\Account;
use App\Models\Banking\Loan;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $loans = Loan::where('user_id', $user->id)
            ->with('account')
            ->latest()
            ->get();

        return response()->json([
            'loans' => $loans->map(fn($l) => [
                'id' => $l->id,
                'user_id' => $l->user_id,
                'account_id' => $l->account_id,
                'amount' => (float) $l->amount,
                'interest_rate' => (float) $l->interest_rate,
                'term_months' => $l->term_months,
                'monthly_payment' => (float) $l->monthly_payment,
                'total_payable' => (float) $l->total_payable,
                'paid_amount' => (float) $l->paid_amount,
                'status' => $l->status,
                'purpose' => $l->purpose,
                'collateral_value' => (float) $l->collateral_value,
                'collateral_description' => $l->collateral_description,
                'rejection_reason' => $l->rejection_reason,
                'created_at' => $l->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:100',
            'interest_rate' => 'required|numeric|min:0.1|max:50',
            'term_months' => 'required|integer|min:1|max:120',
            'purpose' => 'nullable|string|max:500',
            'collateral_description' => 'nullable|string|max:1000',
            'collateral_value' => 'nullable|numeric|min:0',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $loan = $this->loanService->apply(
                $user,
                $account,
                (float) $validated['amount'],
                (float) $validated['interest_rate'],
                (int) $validated['term_months'],
                $validated['purpose'] ?? null,
                $validated['collateral_description'] ?? null,
                isset($validated['collateral_value']) ? (float) $validated['collateral_value'] : null,
            );

            return response()->json([
                'message' => 'Loan application submitted successfully',
                'loan' => $loan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Loan $loan, Request $request): JsonResponse
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $schedule = $this->loanService->getSchedule($loan);
        $repayments = $schedule['repayments'];

        return response()->json([
            'id' => $loan->id,
            'user_id' => $loan->user_id,
            'account_id' => $loan->account_id,
            'amount' => (float) $loan->amount,
            'interest_rate' => (float) $loan->interest_rate,
            'term_months' => $loan->term_months,
            'monthly_payment' => (float) $loan->monthly_payment,
            'total_payable' => (float) $loan->total_payable,
            'paid_amount' => (float) $loan->paid_amount,
            'status' => $loan->status,
            'purpose' => $loan->purpose,
            'collateral_value' => (float) $loan->collateral_value,
            'collateral_description' => $loan->collateral_description,
            'rejection_reason' => $loan->rejection_reason,
            'created_at' => $loan->created_at?->toIso8601String(),
            'repayments' => $repayments->map(fn($r) => [
                'installment_number' => $r->installment_number,
                'due_date' => $r->due_date->toDateString(),
                'amount' => (float) $r->scheduled_amount,
                'status' => $r->status,
            ]),
        ]);
    }

    public function pay(Loan $loan, Request $request): JsonResponse
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'installment_number' => 'nullable|integer|min:1',
        ]);

        $fromAccount = Account::findOrFail($validated['from_account_id']);
        if ($fromAccount->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $repayment = $this->loanService->makePayment(
                $loan,
                $fromAccount,
                $validated['installment_number'] ?? null,
            );

            return response()->json([
                'message' => 'Payment made successfully',
                'repayment' => $repayment,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
