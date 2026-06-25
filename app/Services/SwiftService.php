<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\SwiftTransfer;
use App\Models\Banking\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SwiftService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly BaasService $baasService,
    ) {}

    public function createInternationalTransfer(
        User $user,
        Account $debitAccount,
        string $beneficiaryName,
        string $beneficiaryAccount,
        string $beneficiaryBic,
        string $beneficiaryBankName,
        float $amount,
        string $currency = 'EUR',
        array $optional = []
    ): SwiftTransfer {
        $transaction = $this->transactionService->withdraw($debitAccount, $amount, 'swift');

        $transaction->update([
            'status' => 'pending',
            'description' => "SWIFT Wire Transfer to {$beneficiaryName}"
        ]);

        $baasResponse = $this->baasService->initiateCreditTransfer([
            'external_person_id' => $user->solaris_person_id ?? $debitAccount->user->solaris_person_id ?? null,
            'external_account_id' => $debitAccount->solaris_account_id ?? null,
            'debtor_iban' => $debitAccount->iban,
            'debtor_name' => $user->name,
            'creditor_iban' => $beneficiaryAccount,
            'creditor_name' => $beneficiaryName,
            'creditor_bic' => $beneficiaryBic,
            'amount' => $amount,
            'currency' => $currency,
            'remittance_info' => $optional['remittance_info'] ?? null,
            'intermediary_bic' => $optional['intermediary_bic'] ?? null,
            'charge_bearer' => $optional['charge_bearer'] ?? 'SHA',
            'purpose_code' => $optional['purpose_of_payment'] ?? null,
        ]);

        if (!$baasResponse['success']) {
            $debitAccount->balance += ($transaction->amount + $transaction->fee);
            $debitAccount->available_balance += ($transaction->amount + $transaction->fee);
            $debitAccount->save();

            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $baasResponse['error'] ?? 'BaaS initiation failed',
            ]);

            throw new \RuntimeException("BaaS SWIFT Transfer initiation failed: " . ($baasResponse['error'] ?? 'Unknown error'));
        }

        $transaction->update([
            'metadata' => array_merge($transaction->metadata ?? [], [
                'baas_external_id' => $baasResponse['external_id'] ?? null,
            ])
        ]);

        return DB::transaction(function () use ($transaction, $beneficiaryName, $beneficiaryAccount, $beneficiaryBic, $beneficiaryBankName, $optional) {
            return SwiftTransfer::create([
                'transaction_id' => $transaction->id,
                'beneficiary_name' => $beneficiaryName,
                'beneficiary_account' => $beneficiaryAccount,
                'beneficiary_bic' => $beneficiaryBic,
                'beneficiary_bank_name' => $beneficiaryBankName,
                'beneficiary_bank_address' => $optional['beneficiary_bank_address'] ?? null,
                'beneficiary_address' => $optional['beneficiary_address'] ?? null,
                'intermediary_bic' => $optional['intermediary_bic'] ?? null,
                'remittance_info' => $optional['remittance_info'] ?? null,
                'charge_bearer' => $optional['charge_bearer'] ?? 'SHA',
                'purpose_of_payment' => $optional['purpose_of_payment'] ?? null,
                'sender_reference' => $optional['sender_reference'] ?? null,
            ]);
        });
    }
}
