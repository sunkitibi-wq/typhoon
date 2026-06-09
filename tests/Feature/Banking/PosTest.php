<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\PosTerminal;
use App\Models\Banking\PosTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
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
            'account_number' => 'TY0000000001',
            'iban' => 'DE89370400440532013000',
            'currency' => 'EUR',
            'balance' => 1000,
            'available_balance' => 1000,
            'ledger_balance' => 1000,
            'status' => 'active',
            'label' => 'Primary Account',
            'is_default' => true,
        ]);
    }

    public function test_pos_dashboard_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('banking.pos'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('banking/pos')
            ->has('terminals')
            ->has('pos_transactions')
            ->has('accounts')
        );
    }

    public function test_user_can_pair_terminal(): void
    {
        $response = $this->actingAs($this->user)->post(route('banking.pos.terminals.store'), [
            'account_id' => $this->account->id,
            'serial_number' => 'TY-POS-9999',
            'label' => 'Checkout 1',
            'model' => 'Stripe S700',
            'pairing_code' => '12345',
        ]);

        $response->assertRedirect(route('banking.pos'));
        $this->assertDatabaseHas('pos_terminals', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'serial_number' => 'TY-POS-9999',
            'status' => 'active',
        ]);
    }

    public function test_user_can_toggle_terminal_status(): void
    {
        $terminal = PosTerminal::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'serial_number' => 'TY-POS-8888',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post(route('banking.pos.terminals.toggle', $terminal));

        $response->assertRedirect(route('banking.pos'));
        $this->assertEquals('inactive', $terminal->fresh()->status);

        // Toggle back
        $this->actingAs($this->user)->post(route('banking.pos.terminals.toggle', $terminal));
        $this->assertEquals('active', $terminal->fresh()->status);
    }

    public function test_user_can_delete_terminal(): void
    {
        $terminal = PosTerminal::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'serial_number' => 'TY-POS-8888',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->delete(route('banking.pos.terminals.destroy', $terminal));

        $response->assertRedirect(route('banking.pos'));
        $this->assertDatabaseMissing('pos_terminals', ['id' => $terminal->id]);
    }

    public function test_terminal_sale_processing(): void
    {
        $terminal = PosTerminal::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'serial_number' => 'TY-POS-7777',
            'status' => 'active',
        ]);

        // Call api sale endpoint
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/sale', [
            'serial_number' => 'TY-POS-7777',
            'amount' => 150.00,
            'currency' => 'EUR',
            'card_brand' => 'Mastercard',
            'card_last4' => '9999',
            'payment_method' => 'contactless',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Payment authorized and completed successfully');

        // Check account balance credited
        $this->assertEquals(1150.00, $this->account->fresh()->balance);

        // Check transaction recorded in POS transactions
        $this->assertDatabaseHas('pos_transactions', [
            'pos_terminal_id' => $terminal->id,
            'amount' => 150.00,
            'card_brand' => 'Mastercard',
            'card_last4' => '9999',
            'status' => 'completed',
        ]);
    }

    public function test_terminal_payment_fails_if_inactive(): void
    {
        $terminal = PosTerminal::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'serial_number' => 'TY-POS-6666',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/sale', [
            'serial_number' => 'TY-POS-6666',
            'amount' => 50.00,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Terminal is inactive.');
        $this->assertEquals(1000.00, $this->account->fresh()->balance);
    }

    public function test_pos_transaction_refund(): void
    {
        $terminal = PosTerminal::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'serial_number' => 'TY-POS-5555',
            'status' => 'active',
        ]);

        // Process a payment first via API
        $saleResponse = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/sale', [
            'serial_number' => 'TY-POS-5555',
            'amount' => 200.00,
            'currency' => 'EUR',
        ]);

        $saleResponse->assertStatus(201);
        $posTx = PosTransaction::first();

        // Perform refund from web dashboard
        $refundResponse = $this->actingAs($this->user)->post(route('banking.pos.transactions.refund', $posTx));

        $refundResponse->assertRedirect(route('banking.pos'));
        $this->assertEquals('refunded', $posTx->fresh()->status);

        // Verify ledger reversed (balance back to 1000.00)
        $this->assertEquals(1000.00, $this->account->fresh()->balance);
    }
}
