<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\Loan;
use App\Models\Banking\LoanRepayment;
use App\Models\Role;
use App\Models\User;
use App\Services\SepaService;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepaAndLoanServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private AccountType $accountType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);

        $this->accountType = AccountType::create([
            'code' => 'personal',
            'name' => 'Personal Account',
            'currency' => 'EUR',
            'minimum_balance' => 0,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->role()->associate(Role::where('name', 'user')->first());
        $this->user->save();

        $this->account = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000002001',
            'iban' => 'DE89370400440532013001',
            'currency' => 'EUR',
            'balance' => 10000,
            'available_balance' => 10000,
            'ledger_balance' => 10000,
            'status' => 'active',
            'label' => 'Primary Account',
            'is_default' => true,
        ]);
    }

    public function test_external_sepa_credit_transfer_debits_account()
    {
        $sepaService = app(SepaService::class);

        $transfer = $sepaService->createCreditTransfer(
            $this->account,
            'External Receiver',
            'FR7630006000012345678901234', // External IBAN
            'BNPAFRPPXXX',
            2000,
            'Rent Payment'
        );

        $this->assertDatabaseHas('sepa_transfers', [
            'id' => $transfer->id,
            'creditor_iban' => 'FR7630006000012345678901234',
        ]);

        $this->account->refresh();
        // Checked balance: 10000 - 2000 = 8000 (no fees or default fee)
        $this->assertEquals(8000, $this->account->balance);
    }

    public function test_external_sepa_direct_debit_credits_account()
    {
        $sepaService = app(SepaService::class);

        $transfer = $sepaService->createDirectDebit(
            $this->account,
            'External Debtor',
            'DE89370400440532019999', // External IBAN
            1500,
            'Mandate-123'
        );

        $this->assertDatabaseHas('sepa_transfers', [
            'id' => $transfer->id,
            'creditor_name' => 'External Debtor',
            'creditor_iban' => 'DE89370400440532019999',
        ]);

        $this->account->refresh();
        $this->assertEquals(11500, $this->account->balance);
    }

    public function test_loan_overdue_cumulative_default()
    {
        $loanService = app(LoanService::class);

        // Apply and disburse loan
        $admin = User::factory()->admin()->create();
        $loan = $loanService->apply($this->user, $this->account, 5000, 5.0, 12);
        
        $loan->update(['status' => 'approved']);
        $loanService->disburse($loan, $admin);

        // Make repayments overdue
        $loan->repayments()->update(['status' => 'pending']);

        // Set 3 installments to be overdue (due date in the past)
        $installments = $loan->repayments()->take(3)->get();
        foreach ($installments as $inst) {
            $inst->update([
                'due_date' => now()->subDays(5),
            ]);
        }

        $count = $loanService->checkOverdue();
        $this->assertEquals(3, $count);

        $loan->refresh();
        $this->assertEquals('defaulted', $loan->status);
    }
}
