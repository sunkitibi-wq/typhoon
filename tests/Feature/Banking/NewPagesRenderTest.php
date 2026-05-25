<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccountType $accountType;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

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
            'swift_bic' => 'COBADEFFXXX',
            'currency' => 'EUR',
            'balance' => 10000,
            'available_balance' => 10000,
            'ledger_balance' => 10000,
            'status' => 'active',
            'label' => 'Primary Account',
            'is_default' => true,
        ]);
    }

    public function test_sepa_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.sepa'));
        $response->assertOk();
    }

    public function test_sepa_transfer_submits_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('banking.sepa'))
            ->post(route('banking.sepa'), [
                'debit_account_id' => $this->account->id,
                'amount' => 500,
                'creditor_name' => 'John SEPA',
                'creditor_iban' => 'DE89370400440532019999',
                'creditor_bic' => 'DBKADEFFXXX',
                'remittance_info' => 'SEPA Test',
            ]);

        $response->assertRedirect(route('banking.sepa'));
        $this->assertDatabaseHas('sepa_transfers', [
            'creditor_name' => 'John SEPA',
            'creditor_iban' => 'DE89370400440532019999',
        ]);
    }

    public function test_swift_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.swift'));
        $response->assertOk();
    }

    public function test_swift_transfer_submits_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('banking.swift'))
            ->post(route('banking.swift'), [
                'debit_account_id' => $this->account->id,
                'amount' => 1000,
                'beneficiary_name' => 'John SWIFT',
                'beneficiary_account' => 'US1234567890',
                'beneficiary_bic' => 'CHASEUS3XXX',
                'beneficiary_bank_name' => 'Chase Bank',
                'beneficiary_address' => '123 Wall St',
                'beneficiary_bank_address' => 'New York',
                'remittance_info' => 'SWIFT Test',
                'purpose_of_payment' => 'Invoice 123',
            ]);

        $response->assertRedirect(route('banking.swift'));
        $this->assertDatabaseHas('swift_transfers', [
            'beneficiary_name' => 'John SWIFT',
            'beneficiary_account' => 'US1234567890',
        ]);
    }

    public function test_standing_orders_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.standing-orders'));
        $response->assertOk();
    }

    public function test_beneficiaries_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.beneficiaries'));
        $response->assertOk();
    }

    public function test_statements_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.statements'));
        $response->assertOk();
    }

    public function test_notifications_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.notifications'));
        $response->assertOk();
    }
}
