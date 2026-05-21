<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
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

        AccountType::create([
            'code' => 'savings',
            'name' => 'Savings Account',
            'currency' => 'EUR',
            'minimum_balance' => 100,
            'monthly_fee' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->role()->associate(Role::where('name', 'user')->first());
        $this->user->save();
    }

    public function test_user_can_view_accounts_list()
    {
        $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000001',
            'currency' => 'EUR',
            'balance' => 1000,
            'available_balance' => 1000,
            'ledger_balance' => 1000,
            'status' => 'active',
            'label' => 'Test Account',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('banking.accounts'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('banking/accounts')
            ->has('accounts', 1)
        );
    }

    public function test_user_can_create_account()
    {
        $response = $this->actingAs($this->user)->post(route('banking.accounts.create'), [
            'account_type_code' => 'personal',
            'label' => 'My New Account',
        ]);

        $response->assertRedirect(route('banking.accounts'));

        $this->assertDatabaseHas('accounts', [
            'user_id' => $this->user->id,
            'label' => 'My New Account',
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_create_account_with_invalid_type()
    {
        $response = $this->actingAs($this->user)->post(route('banking.accounts.create'), [
            'account_type_code' => 'invalid',
        ]);

        $response->assertSessionHasErrors('account_type_code');
    }

    public function test_user_can_view_account_detail()
    {
        $account = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000002',
            'iban' => 'DE89370400440532013000',
            'currency' => 'EUR',
            'balance' => 5000,
            'available_balance' => 5000,
            'ledger_balance' => 5000,
            'status' => 'active',
            'label' => 'Detail Test',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('banking.accounts.show', $account));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('banking/account')
            ->has('account')
            ->where('account.label', 'Detail Test')
        );
    }

    public function test_user_cannot_view_others_account()
    {
        $otherUser = User::factory()->create();
        $account = $otherUser->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000099',
            'currency' => 'EUR',
            'balance' => 0,
            'available_balance' => 0,
            'ledger_balance' => 0,
            'status' => 'active',
            'label' => 'Other Account',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('banking.accounts.show', $account));

        $response->assertForbidden();
    }

    public function test_banking_dashboard_shows_accounts()
    {
        $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000000003',
            'currency' => 'EUR',
            'balance' => 2500,
            'available_balance' => 2500,
            'ledger_balance' => 2500,
            'status' => 'active',
            'label' => 'Dashboard Test',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('banking.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('banking/dashboard')
            ->has('accounts', 1)
            ->where('total_balance', 2500)
        );
    }
}
