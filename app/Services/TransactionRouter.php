<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\User;
use App\Models\Banking\Transaction;

class TransactionRouter
{
    private static array $sepaCountries = [
        'AD', 'AT', 'BE', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 
        'GB', 'GI', 'GR', 'HR', 'HU', 'IE', 'IS', 'IT', 'LI', 'LT', 'LU', 'LV', 'MC', 
        'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'SM', 'VA'
    ];

    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly SepaService $sepaService,
        private readonly SwiftService $swiftService,
    ) {}

    public function route(
        User $user,
        Account $debitAccount,
        array $paymentDetails
    ): mixed {
        $iban = $paymentDetails['beneficiary_iban'] ?? $paymentDetails['beneficiary_account'] ?? '';
        $iban = str_replace(' ', '', strtoupper($iban));
        $amount = (float) $paymentDetails['amount'];
        $currency = $paymentDetails['currency'] ?? 'EUR';
        $beneficiaryName = $paymentDetails['beneficiary_name'];
        $remittanceInfo = $paymentDetails['remittance_info'] ?? $paymentDetails['description'] ?? null;

        // 1. Resolve internal account
        $internalAccount = Account::where('iban', $iban)
            ->orWhere('account_number', $iban)
            ->first();

        if ($internalAccount) {
            return $this->transactionService->transfer(
                $debitAccount,
                $internalAccount,
                $amount,
                $remittanceInfo ?? "Internal Transfer to {$beneficiaryName}",
                $user
            );
        }

        // 2. Resolve if it is SEPA Credit Transfer (EUR + SEPA Country Prefix)
        $countryPrefix = substr($iban, 0, 2);
        $isSepaEligible = in_array($countryPrefix, self::$sepaCountries) && $currency === 'EUR';

        if ($isSepaEligible) {
            $bic = $paymentDetails['beneficiary_bic'] ?? $paymentDetails['bic'] ?? 'MOCKSEPA';
            return $this->sepaService->createCreditTransfer(
                $debitAccount,
                $beneficiaryName,
                $iban,
                $bic,
                $amount,
                $remittanceInfo
            );
        }

        // 3. Otherwise SWIFT
        $bic = $paymentDetails['beneficiary_bic'] ?? $paymentDetails['bic'] ?? 'MOCKSWFT';
        $bankName = $paymentDetails['beneficiary_bank_name'] ?? 'Unknown Bank';
        
        return $this->swiftService->createInternationalTransfer(
            $user,
            $debitAccount,
            $beneficiaryName,
            $iban,
            $bic,
            $bankName,
            $amount,
            $currency,
            $paymentDetails
        );
    }
}
