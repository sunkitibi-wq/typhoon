<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $fromAccount;
    private Account $toAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

        $type = AccountType::create([
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

        $this->fromAccount = $this->user->accounts()->create([
            'account_type_id' => $type->id,
            'account_number' => 'TY0000001001',
            'currency' => 'EUR',
            'balance' => 10000,
            'available_balance' => 10000,
            'ledger_balance' => 10000,
            'status' => 'active',
            'label' => 'From Account',
            'is_default' => true,
        ]);

        $this->toAccount = $this->user->accounts()->create([
            'account_type_id' => $type->id,
            'account_number' => 'TY0000001002',
            'currency' => 'EUR',
            'balance' => 500,
            'available_balance' => 500,
            'ledger_balance' => 500,
            'status' => 'active',
            'label' => 'To Account',
            'is_default' => false,
        ]);
    }

    public function test_transfer_page_is_rendered()
    {
        $response = $this->actingAs($this->user)->get(route('banking.transfer'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('banking/transfer'));
    }

    public function test_user_can_transfer_between_own_accounts()
    {
        $response = $this->actingAs($this->user)->post(route('banking.transfer'), [
            'from_account_id' => $this->fromAccount->id,
            'to_account_id' => $this->toAccount->id,
            'amount' => 1000,
            'description' => 'Test transfer',
        ]);

        $response->assertRedirect(route('banking.transactions'));

        $this->fromAccount->refresh();
        $this->toAccount->refresh();

        $this->assertEquals(9000, $this->fromAccount->balance);
        $this->assertEquals(1500, $this->toAccount->balance);
    }

    public function test_transfer_fails_with_insufficient_balance()
    {
        $response = $this->actingAs($this->user)->post(route('banking.transfer'), [
            'from_account_id' => $this->fromAccount->id,
            'to_account_id' => $this->toAccount->id,
            'amount' => 999999,
        ]);

        $response->assertSessionHasErrors('amount');

        $this->fromAccount->refresh();
        $this->assertEquals(10000, $this->fromAccount->balance);
    }

    public function test_transfer_fails_with_zero_amount()
    {
        $response = $this->actingAs($this->user)->post(route('banking.transfer'), [
            'from_account_id' => $this->fromAccount->id,
            'to_account_id' => $this->toAccount->id,
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_transfer_fails_when_same_account()
    {
        $response = $this->actingAs($this->user)->post(route('banking.transfer'), [
            'from_account_id' => $this->fromAccount->id,
            'to_account_id' => $this->fromAccount->id,
            'amount' => 100,
        ]);

        $response->assertSessionHasErrors('to_account_id');
    }

    public function test_transfer_creates_transaction_record()
    {
        $this->actingAs($this->user)->post(route('banking.transfer'), [
            'from_account_id' => $this->fromAccount->id,
            'to_account_id' => $this->toAccount->id,
            'amount' => 500,
            'description' => 'Test transfer',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'transfer',
            'amount' => 500,
            'status' => 'completed',
            'description' => 'Test transfer',
        ]);
    }
}
