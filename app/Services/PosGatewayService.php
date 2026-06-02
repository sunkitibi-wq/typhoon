<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\PosTerminal;
use App\Models\Banking\PosTransaction;
use App\Models\Banking\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosGatewayService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ComplianceService $complianceService,
    ) {}

    public function pairTerminal(User $user, Account $account, array $data): PosTerminal
    {
        // Enforce account ownership validation
        if ($account->user_id !== $user->id) {
            throw new \RuntimeException('You do not own the selected credit account.');
        }

        if (PosTerminal::where('serial_number', $data['serial_number'])->exists()) {
            throw new \RuntimeException('Terminal with this serial number is already registered.');
        }

        return PosTerminal::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'serial_number' => $data['serial_number'],
            'label' => $data['label'] ?? null,
            'model' => $data['model'] ?? 'Standard Reader',
            'status' => 'active',
            'pairing_code' => $data['pairing_code'] ?? null,
            'paired_at' => now(),
        ]);
    }

    public function processPayment(string $serialNumber, float $amount, string $currency, array $cardDetails): PosTransaction
    {
        $terminal = PosTerminal::where('serial_number', $serialNumber)->first();

        if (!$terminal) {
            throw new \RuntimeException('Terminal not found.');
        }

        if ($terminal->status !== 'active') {
            throw new \RuntimeException('Terminal is inactive.');
        }

        if ($amount <= 0) {
            throw new \RuntimeException('Invalid payment amount.');
        }

        if ($terminal->account->currency !== $currency) {
            throw new \RuntimeException('Terminal account currency mismatch.');
        }

        return DB::transaction(function () use ($terminal, $amount, $currency, $cardDetails) {
            // Generate terminal reference
            $terminalRef = 'TX-POS-' . strtoupper(Str::random(12));

            // Execute core banking ledger deposit
            $ledgerTransaction = $this->transactionService->deposit(
                $terminal->account,
                $amount,
                'pos_payment',
                $terminalRef
            );

            // Update description and category
            $ledgerTransaction->update([
                'description' => "POS Card Payment via Terminal {$terminal->serial_number}",
                'category' => 'pos_payment',
            ]);

            // Create POS specific transaction record
            $posTransaction = PosTransaction::create([
                'transaction_id' => $ledgerTransaction->id,
                'pos_terminal_id' => $terminal->id,
                'card_brand' => $cardDetails['card_brand'] ?? 'Visa',
                'card_last4' => $cardDetails['card_last4'] ?? '4242',
                'payment_method' => $cardDetails['payment_method'] ?? 'contactless',
                'terminal_reference' => $terminalRef,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'completed',
            ]);

            // Screen/evaluate transaction via compliance service rules
            $this->complianceService->evaluateTransaction($ledgerTransaction);

            // Touch terminal's last active timestamp
            $terminal->update(['last_active_at' => now()]);

            return $posTransaction;
        });
    }

    public function refundPayment(PosTransaction $posTransaction, User $user): PosTransaction
    {
        if ($posTransaction->status === 'refunded') {
            throw new \RuntimeException('This transaction has already been refunded.');
        }

        $terminal = $posTransaction->posTerminal;
        if ($terminal && $terminal->user_id !== $user->id) {
            throw new \RuntimeException('Unauthorized refund operation.');
        }

        return DB::transaction(function () use ($posTransaction) {
            // Reverse transaction in core ledger
            $this->transactionService->reverseTransaction(
                $posTransaction->transaction,
                "POS Refund for reference: {$posTransaction->terminal_reference}"
            );

            // Mark POS transaction as refunded
            $posTransaction->update([
                'status' => 'refunded',
            ]);

            return $posTransaction->fresh();
        });
    }
}
