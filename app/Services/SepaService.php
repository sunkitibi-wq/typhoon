<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\SepaTransfer;
use App\Models\Banking\Transaction;
use Illuminate\Support\Facades\DB;

class SepaService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly BaasService $baasService,
    ) {}

    public function createCreditTransfer(
        Account $debitAccount,
        string $creditorName,
        string $creditorIban,
        string $creditorBic,
        float $amount,
        ?string $remittanceInfo = null,
        ?string $endToEndId = null,
        ?string $purposeCode = null
    ): SepaTransfer {
        $internalCreditor = $this->resolveInternalAccount($creditorIban);

        if ($internalCreditor) {
            $transaction = $this->transactionService->transfer(
                $debitAccount,
                $internalCreditor,
                $amount,
                "SEPA Credit Transfer to {$creditorName} (Internal)"
            );
        } else {
            $transaction = $this->transactionService->withdraw(
                $debitAccount,
                $amount,
                'sepa'
            );
            
            $transaction->update([
                'status' => 'pending',
                'description' => "SEPA Credit Transfer to {$creditorName} (External)"
            ]);

            $baasResponse = $this->baasService->initiateCreditTransfer([
                'debtor_iban' => $debitAccount->iban,
                'debtor_name' => $debitAccount->user->name ?? 'Unknown',
                'creditor_iban' => $creditorIban,
                'creditor_name' => $creditorName,
                'creditor_bic' => $creditorBic,
                'amount' => $amount,
                'currency' => 'EUR',
                'remittance_info' => $remittanceInfo,
                'end_to_end_id' => $endToEndId,
                'purpose_code' => $purposeCode,
            ]);

            if (!$baasResponse['success']) {
                $debitAccount->balance += ($transaction->amount + $transaction->fee);
                $debitAccount->available_balance += ($transaction->amount + $transaction->fee);
                $debitAccount->save();

                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $baasResponse['error'] ?? 'BaaS initiation failed',
                ]);

                throw new \RuntimeException("BaaS Transfer initiation failed: " . ($baasResponse['error'] ?? 'Unknown error'));
            }

            $transaction->update([
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'baas_external_id' => $baasResponse['external_id'] ?? null,
                ])
            ]);
        }

        return DB::transaction(function () use ($transaction, $creditorName, $creditorIban, $creditorBic, $remittanceInfo, $endToEndId, $purposeCode) {
            return SepaTransfer::create([
                'transaction_id' => $transaction->id,
                'sepa_type' => 'credit_transfer',
                'creditor_name' => $creditorName,
                'creditor_iban' => $creditorIban,
                'creditor_bic' => $creditorBic,
                'remittance_info' => $remittanceInfo,
                'end_to_end_id' => $endToEndId ?? 'NOTPROVIDED',
                'purpose_code' => $purposeCode,
            ]);
        });
    }

    public function createDirectDebit(
        Account $creditAccount,
        string $debtorName,
        string $debtorIban,
        float $amount,
        ?string $mandateReference = null
    ): SepaTransfer {
        $internalDebtor = $this->resolveInternalAccount($debtorIban);

        if ($internalDebtor) {
            $transaction = $this->transactionService->transfer(
                $internalDebtor,
                $creditAccount,
                $amount,
                "SEPA Direct Debit from {$debtorName} (Internal)"
            );
        } else {
            $transaction = $this->transactionService->deposit(
                $creditAccount,
                $amount,
                'sepa_direct_debit'
            );
            
            $transaction->update([
                'status' => 'pending',
                'description' => "SEPA Direct Debit from {$debtorName} (External)"
            ]);

            $baasResponse = $this->baasService->initiateDirectDebit([
                'credit_iban' => $creditAccount->iban,
                'credit_name' => $creditAccount->user->name ?? 'Unknown',
                'debtor_iban' => $debtorIban,
                'debtor_name' => $debtorName,
                'amount' => $amount,
                'currency' => 'EUR',
                'mandate_reference' => $mandateReference,
            ]);

            if (!$baasResponse['success']) {
                $creditAccount->balance -= $transaction->amount;
                $creditAccount->available_balance -= $transaction->amount;
                $creditAccount->save();

                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $baasResponse['error'] ?? 'BaaS initiation failed',
                ]);

                throw new \RuntimeException("BaaS Direct Debit initiation failed: " . ($baasResponse['error'] ?? 'Unknown error'));
            }

            $transaction->update([
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'baas_external_id' => $baasResponse['external_id'] ?? null,
                ])
            ]);
        }

        return DB::transaction(function () use ($transaction, $debtorName, $debtorIban, $mandateReference) {
            return SepaTransfer::create([
                'transaction_id' => $transaction->id,
                'sepa_type' => 'direct_debit',
                'creditor_name' => $debtorName,
                'creditor_iban' => $debtorIban,
                'remittance_info' => $mandateReference,
            ]);
        });
    }

    private function resolveInternalAccount(string $iban): ?Account
    {
        return Account::where('iban', $iban)->first();
    }
}
