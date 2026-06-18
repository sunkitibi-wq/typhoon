<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CyberSourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccountType $accountType;
    private Account $account;

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
            'account_number' => 'TY0000000002',
            'iban' => 'DE89370400440532013001',
            'currency' => 'EUR',
            'balance' => 1000,
            'available_balance' => 1000,
            'ledger_balance' => 1000,
            'status' => 'active',
            'label' => 'Primary Account',
            'is_default' => true,
        ]);
    }

    public function test_card_deposit_validation_fails_without_token_or_billing(): void
    {
        $response = $this->actingAs($this->user)->post(route('banking.deposit'), [
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'method' => 'card',
        ]);

        $response->assertSessionHasErrors(['transient_token', 'billing']);
        $this->assertEquals(1000.00, $this->account->fresh()->balance);
    }

    public function test_successful_card_deposit_via_mock_cybersource(): void
    {
        $response = $this->actingAs($this->user)->post(route('banking.deposit'), [
            'account_id' => $this->account->id,
            'amount' => 150.00,
            'method' => 'card',
            'transient_token' => 'FLEX-TOK-MOCK-TEST',
            'billing' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address_line1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
                'email' => 'jane.doe@example.com',
            ],
        ]);

        $response->assertRedirect(route('banking.deposit'));
        $this->assertEquals(1150.00, $this->account->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'credit_account_id' => $this->account->id,
            'amount' => 150.00,
            'type' => 'deposit',
            'status' => 'completed',
        ]);
    }
}
