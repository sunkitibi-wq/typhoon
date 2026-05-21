<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\BulkPayment;
use App\Models\Banking\BulkPaymentItem;
use App\Models\Banking\BusinessProfile;
use App\Models\Banking\CorporateUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CorporateService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function createBusinessProfile(User $user, array $data): BusinessProfile
    {
        return BusinessProfile::create([
            'user_id' => $user->id,
            'company_name' => $data['company_name'],
            'registration_number' => $data['registration_number'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
            'registered_address' => $data['registered_address'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'founded_year' => $data['founded_year'] ?? null,
            'employee_count' => $data['employee_count'] ?? null,
            'annual_revenue' => $data['annual_revenue'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function inviteTeamMember(BusinessProfile $business, User $user, string $role, ?float $spendingLimit = null): CorporateUser
    {
        return CorporateUser::create([
            'business_profile_id' => $business->id,
            'user_id' => $user->id,
            'role' => $role,
            'permissions' => $this->getDefaultPermissions($role),
            'spending_limit' => $spendingLimit,
            'status' => 'active',
            'accepted_at' => now(),
        ]);
    }

    public function processBulkPayment(
        BusinessProfile $business,
        Account $debitAccount,
        array $payments,
        string $type = 'supplier'
    ): BulkPayment {
        return DB::transaction(function () use ($business, $debitAccount, $payments, $type) {
            $totalAmount = array_sum(array_column($payments, 'amount'));

            $bulkPayment = BulkPayment::create([
                'batch_reference' => 'BATCH-' . strtoupper(Str::random(12)),
                'business_profile_id' => $business->id,
                'debit_account_id' => $debitAccount->id,
                'total_transactions' => count($payments),
                'total_amount' => $totalAmount,
                'currency' => $debitAccount->currency,
                'type' => $type,
                'status' => 'pending',
            ]);

            foreach ($payments as $payment) {
                try {
                    $transaction = $this->transactionService->transfer(
                        $debitAccount,
                        $this->resolveAccount($payment),
                        $payment['amount'],
                        "Bulk payment: {$payment['beneficiary_name']}",
                        $business->user
                    );

                    $bulkPayment->items()->create([
                        'transaction_id' => $transaction->id,
                        'beneficiary_name' => $payment['beneficiary_name'],
                        'beneficiary_iban' => $payment['beneficiary_iban'],
                        'beneficiary_bic' => $payment['beneficiary_bic'] ?? null,
                        'amount' => $payment['amount'],
                        'reference' => $payment['reference'] ?? null,
                        'status' => 'completed',
                    ]);
                } catch (\Exception $e) {
                    $bulkPayment->items()->create([
                        'beneficiary_name' => $payment['beneficiary_name'],
                        'beneficiary_iban' => $payment['beneficiary_iban'],
                        'amount' => $payment['amount'],
                        'status' => 'failed',
                        'failure_reason' => $e->getMessage(),
                    ]);
                }
            }

            $failedCount = $bulkPayment->items()->where('status', 'failed')->count();
            $bulkPayment->update([
                'status' => $failedCount === $bulkPayment->total_transactions ? 'failed' : ($failedCount > 0 ? 'partial' : 'completed'),
                'processed_at' => now(),
            ]);

            return $bulkPayment;
        });
    }

    private function resolveAccount(array $payment): Account
    {
        $account = Account::where('iban', $payment['beneficiary_iban'])->first();

        if (!$account) {
            throw new \RuntimeException("No internal account found for IBAN: {$payment['beneficiary_iban']}");
        }

        return $account;
    }

    private function getDefaultPermissions(string $role): array
    {
        $permissions = [
            'admin' => ['view_accounts', 'make_transfers', 'approve_payments', 'manage_team', 'view_reports'],
            'finance' => ['view_accounts', 'make_transfers', 'approve_payments', 'view_reports'],
            'operator' => ['view_accounts', 'make_transfers'],
            'viewer' => ['view_accounts', 'view_reports'],
        ];

        return $permissions[$role] ?? ['view_accounts'];
    }
}
