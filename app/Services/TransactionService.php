<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\FeeSchedule;
use App\Models\Banking\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function transfer(Account $from, Account $to, float $amount, ?string $description = null, ?User $initiator = null): Transaction
    {
        return DB::transaction(function () use ($from, $to, $amount, $description, $initiator) {
            // Lock accounts in order of ID to prevent deadlocks
            $firstId = min($from->id, $to->id);
            $secondId = max($from->id, $to->id);

            $accounts = Account::whereIn('id', [$firstId, $secondId])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fromLocked = $accounts->get($from->id);
            $toLocked = $accounts->get($to->id);

            if (!$fromLocked || !$toLocked) {
                throw new \RuntimeException('One or both accounts could not be found or locked.');
            }

            $this->validateTransfer($fromLocked, $toLocked, $amount);

            $fee = $this->calculateFee('transfer', $amount, $fromLocked->currency);
            $netAmount = $amount + $fee;

            if ($fromLocked->available_balance < $netAmount) {
                throw new \RuntimeException('Insufficient available balance');
            }

            $fromLocked->decrement('balance', $netAmount);
            $fromLocked->decrement('available_balance', $netAmount);
            $toLocked->increment('balance', $amount);
            $toLocked->increment('available_balance', $amount);

            $reference = $this->generateReference('TFR');

            $transaction = Transaction::create([
                'reference' => $reference,
                'type' => 'transfer',
                'status' => 'completed',
                'debit_account_id' => $fromLocked->id,
                'credit_account_id' => $toLocked->id,
                'user_id' => $initiator?->id ?? $fromLocked->user_id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $amount,
                'currency' => $fromLocked->currency,
                'description' => $description ?? "Transfer to {$toLocked->account_number}",
                'category' => 'transfer',
                'completed_at' => now(),
            ]);

            if ($fee > 0) {
                $transaction->fees()->create([
                    'fee_type' => 'transfer_fee',
                    'amount' => $fee,
                    'currency' => $fromLocked->currency,
                    'description' => 'Transfer processing fee',
                ]);
            }

            return $transaction->fresh();
        });
    }

    public function deposit(Account $account, float $amount, string $method = 'bank_transfer', ?string $reference = null): Transaction
    {
        return DB::transaction(function () use ($account, $amount, $method, $reference) {
            $accountLocked = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            $accountLocked->increment('balance', $amount);
            $accountLocked->increment('available_balance', $amount);
            $accountLocked->increment('ledger_balance', $amount);

            $transaction = Transaction::create([
                'reference' => $reference ?? $this->generateReference('DEP'),
                'type' => 'deposit',
                'status' => 'completed',
                'credit_account_id' => $accountLocked->id,
                'user_id' => $accountLocked->user_id,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'currency' => $accountLocked->currency,
                'description' => "Deposit via {$method}",
                'category' => 'deposit',
                'metadata' => ['method' => $method],
                'completed_at' => now(),
            ]);

            return $transaction;
        });
    }

    public function withdraw(Account $account, float $amount, string $method = 'bank_transfer'): Transaction
    {
        return DB::transaction(function () use ($account, $amount, $method) {
            $accountLocked = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            $fee = $this->calculateFee('withdrawal', $amount, $accountLocked->currency);
            $totalDeduction = $amount + $fee;

            if ($accountLocked->available_balance < $totalDeduction) {
                throw new \RuntimeException('Insufficient available balance');
            }

            $accountLocked->decrement('balance', $totalDeduction);
            $accountLocked->decrement('available_balance', $totalDeduction);
            $accountLocked->decrement('ledger_balance', $totalDeduction);

            $reference = $this->generateReference('WTH');

            $transaction = Transaction::create([
                'reference' => $reference,
                'type' => 'withdrawal',
                'status' => 'completed',
                'debit_account_id' => $accountLocked->id,
                'user_id' => $accountLocked->user_id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $amount,
                'currency' => $accountLocked->currency,
                'description' => "Withdrawal via {$method}",
                'category' => 'withdrawal',
                'metadata' => ['method' => $method],
                'completed_at' => now(),
            ]);

            if ($fee > 0) {
                $transaction->fees()->create([
                    'fee_type' => 'withdrawal_fee',
                    'amount' => $fee,
                    'currency' => $accountLocked->currency,
                    'description' => 'Withdrawal processing fee',
                ]);
            }

            return $transaction;
        });
    }

    public function reverseTransaction(Transaction $transaction, ?string $reason = null): Transaction
    {
        if ($transaction->status === 'reversed') {
            throw new \RuntimeException('Transaction already reversed');
        }

        return DB::transaction(function () use ($transaction, $reason) {
            $transaction->update(['status' => 'reversed', 'metadata' => array_merge(
                $transaction->metadata ?? [],
                ['reversal_reason' => $reason, 'reversed_at' => now()->toIso8601String()]
            )]);

            if ($transaction->debit_account_id && $transaction->credit_account_id) {
                $debitAccount = Account::find($transaction->debit_account_id);
                $creditAccount = Account::find($transaction->credit_account_id);

                if ($debitAccount && $creditAccount) {
                    $debitAccount->increment('balance', $transaction->net_amount + $transaction->fee);
                    $debitAccount->increment('available_balance', $transaction->net_amount + $transaction->fee);
                    $creditAccount->decrement('balance', $transaction->net_amount);
                    $creditAccount->decrement('available_balance', $transaction->net_amount);
                }
            } elseif ($transaction->debit_account_id) {
                $account = Account::find($transaction->debit_account_id);
                if ($account) {
                    $account->increment('balance', $transaction->net_amount + $transaction->fee);
                    $account->increment('available_balance', $transaction->net_amount + $transaction->fee);
                }
            } elseif ($transaction->credit_account_id) {
                $account = Account::find($transaction->credit_account_id);
                if ($account) {
                    $account->decrement('balance', $transaction->net_amount);
                    $account->decrement('available_balance', $transaction->net_amount);
                }
            }

            return $transaction->fresh();
        });
    }

    private function validateTransfer(Account $from, Account $to, float $amount): void
    {
        if ($from->id === $to->id) {
            throw new \RuntimeException('Cannot transfer to the same account');
        }

        if ($from->status !== 'active') {
            throw new \RuntimeException('Source account is not active');
        }

        if ($to->status !== 'active') {
            throw new \RuntimeException('Destination account is not active');
        }

        if ($amount <= 0) {
            throw new \RuntimeException('Amount must be positive');
        }

        if ($from->available_balance < $amount) {
            throw new \RuntimeException('Insufficient available balance');
        }
    }

    private function calculateFee(string $type, float $amount, string $currency): float
    {
        $schedule = FeeSchedule::where('fee_type', $type)
            ->where('is_active', true)
            ->where('currency', $currency)
            ->first();

        if (!$schedule) {
            return 0;
        }

        $fee = match ($schedule->calculation_method) {
            'percentage' => $amount * ($schedule->fee_value / 100),
            'fixed' => $schedule->fee_value,
            'tiered' => $this->calculateTieredFee($schedule, $amount),
            default => 0,
        };

        if ($schedule->min_fee && $fee < $schedule->min_fee) {
            $fee = $schedule->min_fee;
        }

        if ($schedule->max_fee && $fee > $schedule->max_fee) {
            $fee = $schedule->max_fee;
        }

        return round($fee, 2);
    }

    private function calculateTieredFee(FeeSchedule $schedule, float $amount): float
    {
        $tiers = $schedule->tiers ?? [];
        foreach ($tiers as $tier) {
            if ($amount >= ($tier['min'] ?? 0) && $amount <= ($tier['max'] ?? PHP_FLOAT_MAX)) {
                return $tier['fee'] ?? 0;
            }
        }
        return $schedule->fee_value;
    }

    private function generateReference(string $prefix): string
    {
        do {
            $ref = $prefix . '-' . strtoupper(Str::random(10));
        } while (Transaction::where('reference', $ref)->exists());

        return $ref;
    }
}
