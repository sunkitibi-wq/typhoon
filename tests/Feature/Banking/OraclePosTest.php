<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\PosTerminal;
use App\Models\Banking\PosTransaction;
use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\ExchangeRate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OraclePosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccountType $accountType;
    private Account $account;
    private PosTerminal $terminal;

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
            'account_number' => 'TY0000000002',
            'iban' => 'DE89370400440532013002',
            'currency' => 'EUR',
            'balance' => 1000,
            'available_balance' => 1000,
            'ledger_balance' => 1000,
            'status' => 'active',
            'label' => 'Primary Account',
            'is_default' => true,
        ]);

        $this->terminal = PosTerminal::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'serial_number' => 'PAX-A77-SERIAL123',
            'model' => 'PAX A77',
            'status' => 'active',
        ]);

        CryptoCurrency::create([
            'code' => 'USDC',
            'name' => 'USD Coin',
            'network' => 'ethereum',
            'decimals' => 6,
            'status' => 'active',
        ]);

        ExchangeRate::create([
            'base_currency' => 'USDC',
            'quote_currency' => 'EUR',
            'bid' => 0.92,
            'ask' => 0.93,
            'mid_rate' => 0.925,
            'last_refreshed_at' => now(),
        ]);
    }

    public function test_user_can_configure_terminal_for_oracle_and_crypto(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/configure-terminal', [
            'terminal_id' => $this->terminal->id,
            'oracle_terminal_id' => 'WS-101',
            'crypto_processor_enabled' => true,
            'default_crypto_currency' => 'USDC',
            'settlement_mode' => 'crypto',
            'oracle_api_url' => 'https://oracle-simphony.example.com/api',
            'oracle_api_key' => 'secret-api-key',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Oracle POS and Crypto configuration updated successfully.');

        $this->terminal->refresh();
        $this->assertEquals('WS-101', $this->terminal->oracle_terminal_id);
        $this->assertTrue($this->terminal->crypto_processor_enabled);
        $this->assertEquals('crypto', $this->terminal->settlement_mode);
    }

    public function test_oracle_fiat_charge_succeeds(): void
    {
        $this->terminal->update(['oracle_terminal_id' => 'WS-102', 'crypto_processor_enabled' => false]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/charge', [
            'oracle_terminal_id' => 'WS-102',
            'amount' => 100.00,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('payment_type', 'card');
        $response->assertJsonPath('status', 'completed');

        // Fiat is immediately deposited for card payments
        $this->assertEquals(1100.00, (float) $this->account->fresh()->balance);
    }

    public function test_oracle_crypto_charge_succeeds(): void
    {
        $this->terminal->update([
            'oracle_terminal_id' => 'WS-103',
            'crypto_processor_enabled' => true,
            'default_crypto_currency' => 'USDC',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/charge', [
            'oracle_terminal_id' => 'WS-103',
            'amount' => 92.50, // 92.50 EUR / 0.925 mid_rate = 100.00 USDC
            'currency' => 'EUR',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('payment_type', 'crypto');
        $response->assertJsonPath('crypto_currency', 'USDC');
        $response->assertJsonPath('crypto_amount', 100);
        $response->assertJsonPath('status', 'pending');
        $this->assertNotNull($response->json('qr_payload'));

        // Balance should not change yet as the crypto payment is pending on-chain confirmation
        $this->assertEquals(1000.00, (float) $this->account->fresh()->balance);
    }

    public function test_simulate_crypto_payment_settles_correctly_fiat_mode(): void
    {
        $this->terminal->update([
            'oracle_terminal_id' => 'WS-104',
            'crypto_processor_enabled' => true,
            'default_crypto_currency' => 'USDC',
            'settlement_mode' => 'fiat',
        ]);

        $charge = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/charge', [
            'oracle_terminal_id' => 'WS-104',
            'amount' => 92.50,
            'currency' => 'EUR',
        ]);

        $reference = $charge->json('reference');

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/simulate-payment', [
            'reference' => $reference,
            'tx_hash' => '0xmockedtransactionhash1234567890',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('pos_transaction.status', 'completed');
        $response->assertJsonPath('pos_transaction.tx_hash', '0xmockedtransactionhash1234567890');

        // Account balance should be credited with the fiat value (1000 + 92.50 = 1092.50)
        $this->assertEquals(1092.50, (float) $this->account->fresh()->balance);
    }

    public function test_simulate_crypto_payment_settles_correctly_crypto_mode(): void
    {
        $this->terminal->update([
            'oracle_terminal_id' => 'WS-105',
            'crypto_processor_enabled' => true,
            'default_crypto_currency' => 'USDC',
            'settlement_mode' => 'crypto',
        ]);

        $charge = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/charge', [
            'oracle_terminal_id' => 'WS-105',
            'amount' => 92.50,
            'currency' => 'EUR',
        ]);

        $reference = $charge->json('reference');

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/simulate-payment', [
            'reference' => $reference,
            'tx_hash' => '0xmockedtransactionhash1234567890',
        ]);

        $response->assertStatus(200);

        // Account balance stays same (settled directly to crypto wallet)
        $this->assertEquals(1000.00, (float) $this->account->fresh()->balance);

        // Merchant should now have a crypto wallet with the USDC credited
        $wallet = $this->user->cryptoWallets()->whereHas('cryptoCurrency', fn($q) => $q->where('code', 'USDC'))->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(100.00, (float) $wallet->balance);
    }

    public function test_get_oracle_charge_status(): void
    {
        $this->terminal->update([
            'oracle_terminal_id' => 'WS-106',
            'crypto_processor_enabled' => true,
            'default_crypto_currency' => 'USDC',
        ]);

        $charge = $this->actingAs($this->user, 'sanctum')->postJson('/api/pos/oracle/charge', [
            'oracle_terminal_id' => 'WS-106',
            'amount' => 92.50,
            'currency' => 'EUR',
        ]);

        $reference = $charge->json('reference');

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/pos/oracle/charge/{$reference}/status");

        $response->assertStatus(200);
        $response->assertJsonPath('reference', $reference);
        $response->assertJsonPath('status', 'pending');
        $response->assertJsonPath('crypto_currency', 'USDC');
        $response->assertJsonPath('crypto_amount', 100);
    }
}
