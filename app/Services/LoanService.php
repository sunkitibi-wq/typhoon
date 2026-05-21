<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\Loan;
use App\Models\Banking\LoanRepayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoanService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function apply(User $user, Account $account, float $amount, float $interestRate, int $termMonths, ?string $purpose = null, ?string $collateralDescription = null, ?float $collateralValue = null): Loan
    {
        $monthlyPayment = $this->calculateMonthlyPayment($amount, $interestRate, $termMonths);
        $totalPayable = round($monthlyPayment * $termMonths, 2);

        return DB::transaction(function () use ($user, $account, $amount, $interestRate, $termMonths, $monthlyPayment, $totalPayable, $purpose, $collateralDescription, $collateralValue) {
            return Loan::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'loan_number' => $this->generateLoanNumber(),
                'amount' => $amount,
                'interest_rate' => $interestRate,
                'term_months' => $termMonths,
                'monthly_payment' => $monthlyPayment,
                'total_payable' => $totalPayable,
                'purpose' => $purpose,
                'collateral_description' => $collateralDescription,
                'collateral_value' => $collateralValue,
                'status' => 'pending',
                'applied_at' => now(),
            ]);
        });
    }

    public function underwrite(Loan $loan, User $admin): void
    {
        if ($loan->status !== 'pending') {
            throw new \RuntimeException('Loan is not in pending state');
        }

        $loan->update([
            'status' => 'underwriting',
            'underwriting_at' => now(),
        ]);
    }

    public function approve(Loan $loan, User $admin): void
    {
        if (!in_array($loan->status, ['pending', 'underwriting'])) {
            throw new \RuntimeException('Loan cannot be approved from current state');
        }

        DB::transaction(function () use ($loan, $admin) {
            $loan->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);
        });
    }

    public function reject(Loan $loan, User $admin, string $reason): void
    {
        if (!in_array($loan->status, ['pending', 'underwriting'])) {
            throw new \RuntimeException('Loan cannot be rejected from current state');
        }

        $loan->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);
    }

    public function disburse(Loan $loan, User $admin): void
    {
        if ($loan->status !== 'approved') {
            throw new \RuntimeException('Loan must be approved before disbursement');
        }

        DB::transaction(function () use ($loan, $admin) {
            $account = $loan->account;

            // Credit the account
            $this->transactionService->deposit($account, $loan->amount, 'loan_disbursement', "LOAN-{$loan->loan_number}");

            // Generate repayment schedule
            $this->generateRepaymentSchedule($loan);

            $loan->update([
                'status' => 'active',
                'disbursed_at' => now(),
            ]);
        });
    }

    public function makePayment(Loan $loan, Account $fromAccount, ?int $installmentNumber = null): LoanRepayment
    {
        return DB::transaction(function () use ($loan, $fromAccount, $installmentNumber) {
            $loanLocked = Loan::where('id', $loan->id)->lockForUpdate()->firstOrFail();

            if ($loanLocked->status !== 'active') {
                throw new \RuntimeException('Loan is not active');
            }

            $repayment = $loanLocked->repayments()
                ->where('status', 'pending')
                ->when($installmentNumber, fn($q) => $q->where('installment_number', $installmentNumber))
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->firstOrFail();

            $tx = $this->transactionService->transfer(
                $fromAccount,
                $loanLocked->account,
                $repayment->scheduled_amount,
                "Loan Repayment {$loanLocked->loan_number} - Installment {$repayment->installment_number}",
            );

            $repayment->update([
                'paid_amount' => $repayment->scheduled_amount,
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_id' => $tx->id,
            ]);

            $newPaid = $loanLocked->paid_amount + $repayment->scheduled_amount;
            $loanLocked->update(['paid_amount' => $newPaid]);

            if ($newPaid >= $loanLocked->total_payable) {
                $loanLocked->update(['status' => 'paid', 'paid_at' => now()]);
            }

            return $repayment->fresh();
        });
    }

    public function getSchedule(Loan $loan): array
    {
        $repayments = $loan->repayments()->orderBy('installment_number')->get();

        return [
            'loan' => $loan,
            'repayments' => $repayments,
            'next_due' => $repayments->where('status', 'pending')->first(),
            'paid_count' => $repayments->where('status', 'paid')->count(),
            'pending_count' => $repayments->where('status', 'pending')->count(),
            'overdue_count' => $repayments->where('status', 'overdue')->count(),
            'progress_percent' => $loan->total_payable > 0
                ? round(($loan->paid_amount / $loan->total_payable) * 100, 1)
                : 0,
        ];
    }

    public function checkOverdue(): int
    {
        return DB::transaction(function () {
            $count = 0;
            $overdue = LoanRepayment::where('status', 'pending')
                ->where('due_date', '<', now())
                ->lockForUpdate()
                ->get()
                ->groupBy('loan_id');

            foreach ($overdue as $loanId => $repayments) {
                $loan = Loan::where('id', $loanId)->lockForUpdate()->first();
                if (!$loan || $loan->status !== 'active') {
                    continue;
                }

                $loan->repayments()
                    ->whereIn('id', $repayments->pluck('id'))
                    ->update(['status' => 'overdue']);

                $totalOverdue = $loan->repayments()->where('status', 'overdue')->count();
                if ($totalOverdue >= 3) {
                    $loan->update(['status' => 'defaulted']);
                }

                $count += $repayments->count();
            }

            return $count;
        });
    }

    private function generateRepaymentSchedule(Loan $loan): void
    {
        $schedule = [];
        $dueDate = now()->addMonth()->startOfDay();
        $remainingBalance = $loan->total_payable;

        for ($i = 1; $i <= $loan->term_months; $i++) {
            $isLast = $i === $loan->term_months;
            $payment = $isLast ? round($remainingBalance, 2) : $loan->monthly_payment;
            $remainingBalance -= $payment;

            $schedule[] = [
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => $dueDate->copy(),
                'scheduled_amount' => $payment,
                'paid_amount' => 0,
                'remaining_balance' => max(round($remainingBalance, 2), 0),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $dueDate->addMonth();
        }

        LoanRepayment::insert($schedule);
    }

    private function calculateMonthlyPayment(float $principal, float $annualRate, int $termMonths): float
    {
        if ($annualRate == 0) {
            return round($principal / $termMonths, 2);
        }

        $monthlyRate = ($annualRate / 100) / 12;
        $factor = (1 + $monthlyRate) ** $termMonths;
        $payment = $principal * ($monthlyRate * $factor) / ($factor - 1);

        return round($payment, 2);
    }

    private function generateLoanNumber(): string
    {
        do {
            $number = 'LN-' . strtoupper(Str::random(10));
        } while (Loan::where('loan_number', $number)->exists());

        return $number;
    }
}
