<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\AccountCreated;

class AccountService
{
    public function createAccount(User $user, string $accountTypeCode, string $currency = 'EUR', ?string $label = null): Account
    {
        $type = AccountType::where('code', $accountTypeCode)->where('is_active', true)->firstOrFail();

        $isFirst = $user->accounts()->count() === 0;

        $solarisAccountId = null;
        $iban = null;

        if ($user->solaris_person_id) {
            $baasService = app(\App\Services\BaasService::class);
            $res = $baasService->createAccount([
                'external_person_id' => $user->solaris_person_id,
                'currency' => $currency,
            ]);

            if ($res['success']) {
                $solarisAccountId = $res['id'] ?? null;
                $iban = $res['iban'] ?? null;
            } else {
                \Illuminate\Support\Facades\Log::error('BaaS: Failed to create account on Solarisbank: ' . ($res['error'] ?? 'Unknown error'));
            }
        }

        if (!$iban) {
            $iban = $this->generateIban($user->country_of_residence ?? 'DE');
        }

        $account = DB::transaction(function () use ($user, $type, $currency, $label, $isFirst, $solarisAccountId, $iban) {
            $account = Account::create([
                'user_id' => $user->id,
                'account_type_id' => $type->id,
                'account_number' => $this->generateAccountNumber(),
                'iban' => $iban,
                'swift_bic' => 'COBADEFFXXX',
                'currency' => $currency,
                'balance' => 0,
                'available_balance' => 0,
                'ledger_balance' => 0,
                'status' => $solarisAccountId ? 'active' : 'pending',
                'solaris_account_id' => $solarisAccountId,
                'label' => $label ?? $type->name,
                'is_default' => $isFirst,
            ]);

            return $account;
        });

        event(new AccountCreated($account));

        return $account;
    }

    public function getBalance(Account $account): array
    {
        return [
            'balance' => $account->balance,
            'available_balance' => $account->available_balance,
            'ledger_balance' => $account->ledger_balance,
            'currency' => $account->currency,
        ];
    }

    public function closeAccount(Account $account): void
    {
        if ($account->balance > 0) {
            throw new \RuntimeException('Cannot close account with positive balance. Transfer funds first.');
        }

        $account->update(['status' => 'closed', 'closed_at' => now()]);
    }

    public function freezeAccount(Account $account): void
    {
        $account->update(['status' => 'frozen']);
    }

    public function unfreezeAccount(Account $account): void
    {
        $account->update(['status' => 'active']);
    }

    private function generateAccountNumber(): string
    {
        do {
            $number = 'TY' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (Account::where('account_number', $number)->exists());

        return $number;
    }

    private function generateIban(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);
        $bban = str_pad((string) random_int(0, 999999999999999999), 18, '0', STR_PAD_LEFT);
        $checkDigits = '00';
        $iban = $countryCode . $checkDigits . $bban;

        // Calculate IBAN check digits
        $swapped = substr($iban, 4) . substr($iban, 0, 4);
        $converted = '';
        foreach (str_split($swapped) as $char) {
            $converted .= ctype_alpha($char) ? (ord($char) - 55) : $char;
        }

        $check = bcmod($converted, '97');
        $checkDigits = str_pad((string) (98 - (int) $check), 2, '0', STR_PAD_LEFT);

        $iban = $countryCode . $checkDigits . $bban;

        if (Account::where('iban', $iban)->exists()) {
            return $this->generateIban($countryCode);
        }

        return $iban;
    }
}
