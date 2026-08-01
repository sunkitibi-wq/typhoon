<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\StandingOrder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandingOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $debitAccount;

    private Account $recipientAccount;

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

        $this->debitAccount = $this->user->accounts()->create([
            'account_type_id' => $type->id,
            'account_number' => 'TY0000002001',
            'currency' => 'EUR',
            'balance' => 10000,
            'available_balance' => 10000,
            'ledger_balance' => 10000,
            'status' => 'active',
            'label' => 'Debit Account',
            'is_default' => true,
        ]);

        $recipient = User::factory()->create();
        $recipient->role()->associate(Role::where('name', 'user')->first());
        $recipient->save();

        $this->recipientAccount = $recipient->accounts()->create([
            'account_type_id' => $type->id,
            'account_number' => 'TY0000002002',
            'currency' => 'EUR',
            'balance' => 0,
            'available_balance' => 0,
            'ledger_balance' => 0,
            'status' => 'active',
            'label' => 'Recipient Account',
            'is_default' => false,
        ]);
    }

    public function test_due_standing_order_is_executed_and_advances_cycle(): void
    {
        $order = StandingOrder::create([
            'user_id' => $this->user->id,
            'debit_account_id' => $this->debitAccount->id,
            'beneficiary_name' => 'Recipient',
            'beneficiary_iban' => $this->recipientAccount->iban ?? $this->recipientAccount->account_number,
            'amount' => 250,
            'currency' => 'EUR',
            'frequency' => 'monthly',
            'starts_at' => now()->subMonth(),
            'next_execution_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $this->artisan('typhoon:process-standing-orders')->assertSuccessful();

        $order->refresh();

        $this->assertNotNull($order->last_executed_at);
        $this->assertNotNull($order->next_execution_at);
        $this->assertTrue($order->next_execution_at->isFuture());

        $this->debitAccount->refresh();
        $this->recipientAccount->refresh();
        $this->assertEquals(9750, $this->debitAccount->balance);
        $this->assertEquals(250, $this->recipientAccount->balance);
    }

    public function test_order_not_due_is_not_executed(): void
    {
        StandingOrder::create([
            'user_id' => $this->user->id,
            'debit_account_id' => $this->debitAccount->id,
            'beneficiary_name' => 'Recipient',
            'beneficiary_iban' => $this->recipientAccount->account_number,
            'amount' => 250,
            'currency' => 'EUR',
            'frequency' => 'monthly',
            'starts_at' => now()->addWeek(),
            'next_execution_at' => now()->addWeek(),
            'status' => 'active',
        ]);

        $this->artisan('typhoon:process-standing-orders')->assertSuccessful();

        $this->debitAccount->refresh();
        $this->assertEquals(10000, $this->debitAccount->balance);
    }

    public function test_paused_order_is_not_executed(): void
    {
        StandingOrder::create([
            'user_id' => $this->user->id,
            'debit_account_id' => $this->debitAccount->id,
            'beneficiary_name' => 'Recipient',
            'beneficiary_iban' => $this->recipientAccount->account_number,
            'amount' => 250,
            'currency' => 'EUR',
            'frequency' => 'monthly',
            'starts_at' => now()->subMonth(),
            'next_execution_at' => now()->subDay(),
            'status' => 'paused',
        ]);

        $this->artisan('typhoon:process-standing-orders')->assertSuccessful();

        $this->debitAccount->refresh();
        $this->assertEquals(10000, $this->debitAccount->balance);
    }

    public function test_order_completes_when_past_end_date(): void
    {
        $order = StandingOrder::create([
            'user_id' => $this->user->id,
            'debit_account_id' => $this->debitAccount->id,
            'beneficiary_name' => 'Recipient',
            'beneficiary_iban' => $this->recipientAccount->account_number,
            'amount' => 250,
            'currency' => 'EUR',
            'frequency' => 'monthly',
            'starts_at' => now()->subMonths(3),
            'ends_at' => now()->subWeek(),
            'next_execution_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $this->artisan('typhoon:process-standing-orders')->assertSuccessful();

        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals(10000, $this->debitAccount->balance);
    }
}
