<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\ExchangeRate;
use App\Models\Role;
use App\Models\User;
use App\Services\CryptoExchangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoExchangeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CryptoCurrency $btc;

    private CryptoCurrency $eur;

    private CryptoExchangeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user'], ['description' => 'Standard user']);

        $this->user = User::factory()->create();

        $this->btc = CryptoCurrency::create([
            'code' => 'BTC',
            'name' => 'Bitcoin',
            'network' => 'Bitcoin',
            'decimals' => 8,
            'minimum_withdrawal' => 0.001,
            'withdrawal_fee' => 0.0005,
            'minimum_deposit' => 0.0001,
            'deposit_fee' => 0,
            'status' => 'active',
            'is_quote_currency' => true,
        ]);

        $this->eur = CryptoCurrency::create([
            'code' => 'ETH',
            'name' => 'Ethereum',
            'network' => 'ERC20',
            'decimals' => 18,
            'minimum_withdrawal' => 0.01,
            'withdrawal_fee' => 0.005,
            'minimum_deposit' => 0.001,
            'deposit_fee' => 0,
            'status' => 'active',
            'is_quote_currency' => true,
        ]);

        ExchangeRate::create([
            'base_currency' => 'BTC',
            'quote_currency' => 'ETH',
            'bid' => 62500.00,
            'ask' => 62800.00,
            'mid_rate' => 62650.00,
            'change_24h' => 2.35,
            'last_refreshed_at' => now(),
        ]);

        ExchangeRate::create([
            'base_currency' => 'ETH',
            'quote_currency' => 'BTC',
            'bid' => 0.015,
            'ask' => 0.016,
            'mid_rate' => 0.0155,
            'change_24h' => -1.20,
            'last_refreshed_at' => now(),
        ]);

        $this->service = $this->app->make(CryptoExchangeService::class);
    }

    public function test_can_create_wallet(): void
    {
        $wallet = $this->service->createWallet($this->user, $this->btc, 'My BTC Wallet');

        $this->assertDatabaseHas('crypto_wallets', [
            'id' => $wallet->id,
            'user_id' => $this->user->id,
            'crypto_currency_id' => $this->btc->id,
            'label' => 'My BTC Wallet',
            'balance' => 0,
        ]);

        $this->assertNotNull($wallet->address);
        $this->assertStringStartsWith('0x', $wallet->address);
        $this->assertNotNull($wallet->private_key);
        $this->assertStringStartsWith('0x', $wallet->private_key);
    }

    public function test_cannot_create_duplicate_wallet(): void
    {
        $this->service->createWallet($this->user, $this->btc);

        $this->expectException(\RuntimeException::class);
        $this->service->createWallet($this->user, $this->btc);
    }

    public function test_can_get_quote(): void
    {
        $quote = $this->service->getQuote('BTC', 'ETH', 1);

        $this->assertEquals('BTC', $quote['from']);
        $this->assertEquals('ETH', $quote['to']);
        $this->assertEquals(1, $quote['amount']);
        $this->assertEquals(62650.00, $quote['rate']);
        $this->assertEquals(62650.00, $quote['total']);
        $this->assertEquals(62.65, $quote['fee']);
        $this->assertEquals(62587.35, $quote['net_receive']);
    }

    public function test_can_place_buy_market_order(): void
    {
        $btcWallet = $this->service->createWallet($this->user, $this->btc);

        $ethWallet = $this->service->createWallet($this->user, $this->eur);
        $ethWallet->increment('balance', 100000);

        $order = $this->service->placeOrder($this->user, [
            'base_currency' => 'BTC',
            'quote_currency' => 'ETH',
            'side' => 'buy',
            'amount' => 1,
            'order_type' => 'market',
        ]);

        $this->assertDatabaseHas('crypto_orders', [
            'id' => $order->id,
            'user_id' => $this->user->id,
            'side' => 'buy',
            'base_currency' => 'BTC',
            'status' => 'filled',
        ]);

        $btcWallet->refresh();
        $ethWallet->refresh();

        $this->assertGreaterThan(0, $btcWallet->balance);
        $this->assertLessThan(100000, $ethWallet->balance);
    }

    public function test_can_place_sell_market_order(): void
    {
        $btcWallet = $this->service->createWallet($this->user, $this->btc);
        $btcWallet->increment('balance', 2);

        $ethWallet = $this->service->createWallet($this->user, $this->eur);

        $order = $this->service->placeOrder($this->user, [
            'base_currency' => 'BTC',
            'quote_currency' => 'ETH',
            'side' => 'sell',
            'amount' => 1,
            'order_type' => 'market',
        ]);

        $this->assertDatabaseHas('crypto_orders', [
            'id' => $order->id,
            'side' => 'sell',
            'status' => 'filled',
        ]);

        $btcWallet->refresh();
        $ethWallet->refresh();

        $this->assertLessThan(2, $btcWallet->balance);
        $this->assertGreaterThan(0, $ethWallet->balance);
    }

    public function test_buy_order_fails_without_balance(): void
    {
        $this->service->createWallet($this->user, $this->btc);
        $this->service->createWallet($this->user, $this->eur);

        $this->expectException(\RuntimeException::class);
        $this->service->placeOrder($this->user, [
            'base_currency' => 'BTC',
            'quote_currency' => 'ETH',
            'side' => 'buy',
            'amount' => 1,
            'order_type' => 'market',
        ]);
    }

    public function test_can_record_deposit(): void
    {
        $wallet = $this->service->createWallet($this->user, $this->btc);

        $deposit = $this->service->recordDeposit([
            'user_id' => $this->user->id,
            'crypto_currency_id' => $this->btc->id,
            'crypto_wallet_id' => $wallet->id,
            'amount' => 1,
            'tx_hash' => '0xabc123',
            'from_address' => '0xdef456',
        ]);

        $this->assertDatabaseHas('crypto_deposits', [
            'id' => $deposit->id,
            'user_id' => $this->user->id,
            'amount' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_can_confirm_deposit(): void
    {
        $wallet = $this->service->createWallet($this->user, $this->btc);

        $deposit = $this->service->recordDeposit([
            'user_id' => $this->user->id,
            'crypto_currency_id' => $this->btc->id,
            'crypto_wallet_id' => $wallet->id,
            'amount' => 1,
        ]);

        $this->service->confirmDeposit($deposit);

        $wallet->refresh();
        $this->assertEquals(1, $wallet->balance);
        $this->assertEquals('confirmed', $deposit->fresh()->status);
    }

    public function test_can_request_withdrawal(): void
    {
        $wallet = $this->service->createWallet($this->user, $this->btc);
        $wallet->increment('balance', 2);

        $withdrawal = $this->service->requestWithdrawal(
            $this->user,
            $wallet,
            1,
            '0xdestination123'
        );

        $this->assertDatabaseHas('crypto_withdrawals', [
            'id' => $withdrawal->id,
            'status' => 'pending',
            'amount' => 1,
            'to_address' => '0xdestination123',
        ]);

        $wallet->refresh();
        $this->assertEquals(1.0, $wallet->balance);
    }

    public function test_withdrawal_fails_without_balance(): void
    {
        $wallet = $this->service->createWallet($this->user, $this->btc);

        $this->expectException(\RuntimeException::class);
        $this->service->requestWithdrawal($this->user, $wallet, 1, '0xdest');
    }

    public function test_withdrawal_fails_wallet_mismatch(): void
    {
        $otherUser = User::factory()->create();
        $wallet = $this->service->createWallet($this->user, $this->btc);
        $wallet->increment('balance', 2);

        $this->expectException(\RuntimeException::class);
        $this->service->requestWithdrawal($otherUser, $wallet, 1, '0xdest');
    }

    public function test_can_place_limit_order(): void
    {
        $btcWallet = $this->service->createWallet($this->user, $this->btc);
        $eurWallet = $this->service->createWallet($this->user, $this->eur);
        $eurWallet->increment('balance', 100000);

        $order = $this->service->placeOrder($this->user, [
            'base_currency' => 'BTC',
            'quote_currency' => 'ETH',
            'side' => 'buy',
            'amount' => 0.5,
            'order_type' => 'limit',
            'price' => 62000,
        ]);

        $this->assertDatabaseHas('crypto_orders', [
            'id' => $order->id,
            'order_type' => 'limit',
            'status' => 'open',
            'price' => 62000,
        ]);
    }

    public function test_public_api_currencies(): void
    {
        $response = $this->getJson('/api/crypto/currencies');

        $response->assertOk()
            ->assertJsonStructure(['currencies']);
    }

    public function test_public_api_rates(): void
    {
        $response = $this->getJson('/api/crypto/rates');

        $response->assertOk()
            ->assertJsonStructure(['rates']);
    }

    public function test_api_wallets_requires_auth(): void
    {
        $this->getJson('/api/crypto/wallets')->assertUnauthorized();
    }

    public function test_api_authenticated_wallets(): void
    {
        $this->service->createWallet($this->user, $this->btc);

        $response = $this->actingAs($this->user)->getJson('/api/crypto/wallets');

        $response->assertOk()
            ->assertJsonStructure(['wallets']);
    }

    public function test_api_place_order(): void
    {
        $btcWallet = $this->service->createWallet($this->user, $this->btc);
        $ethWallet = $this->service->createWallet($this->user, $this->eur);
        $ethWallet->increment('balance', 10000);

        $response = $this->actingAs($this->user)->postJson('/api/crypto/orders', [
            'base_currency' => 'BTC',
            'quote_currency' => 'ETH',
            'side' => 'buy',
            'amount' => 0.1,
            'order_type' => 'market',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'order']);
    }

    public function test_api_send_crypto_transaction(): void
    {
        $ethWallet = $this->service->createWallet($this->user, $this->eur);
        $ethWallet->increment('balance', 10);

        $response = $this->actingAs($this->user)->postJson('/api/crypto/transactions/send', [
            'wallet_id' => $ethWallet->id,
            'amount' => 1,
            'to_address' => '0x0123456789abcdef0123456789abcdef01234567',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'withdrawal' => ['reference', 'tx_hash', 'status']]);

        $ethWallet->refresh();
        $this->assertEquals(9.0, (float) $ethWallet->balance);
    }

    public function test_api_quote(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/crypto/quote?from=BTC&to=ETH&amount=1');

        $response->assertOk()
            ->assertJsonStructure(['from', 'to', 'rate', 'total', 'fee', 'net_receive']);
    }

    public function test_deposit_then_order_flow(): void
    {
        $btcWallet = $this->service->createWallet($this->user, $this->btc);
        $ethWallet = $this->service->createWallet($this->user, $this->eur);

        $deposit = $this->service->recordDeposit([
            'user_id' => $this->user->id,
            'crypto_currency_id' => $this->eur->id,
            'crypto_wallet_id' => $ethWallet->id,
            'amount' => 10000,
        ]);

        $this->assertEquals('pending', $deposit->status);

        $this->service->confirmDeposit($deposit);

        $ethWallet->refresh();
        $this->assertEquals(10000, $ethWallet->balance);

        $order = $this->service->placeOrder($this->user, [
            'base_currency' => 'BTC',
            'quote_currency' => 'ETH',
            'side' => 'buy',
            'amount' => 0.1,
            'order_type' => 'market',
        ]);

        $this->assertEquals('filled', $order->status);

        $btcWallet->refresh();
        $ethWallet->refresh();

        $this->assertGreaterThan(0, $btcWallet->balance);
        $this->assertLessThan(10000, $ethWallet->balance);
    }

    public function test_web_deposit_auto_confirms(): void
    {
        $this->actingAs($this->user);
        $wallet = $this->service->createWallet($this->user, $this->eur);

        $response = $this->post(route('banking.crypto'), [
            'action' => 'record_deposit',
            'crypto_currency_id' => $this->eur->id,
            'crypto_wallet_id' => $wallet->id,
            'amount' => 5,
        ]);

        $response->assertRedirect();

        $wallet->refresh();
        $this->assertEquals(5, $wallet->balance);
    }

    public function test_regular_user_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/dashboard');

        $response->assertForbidden();
    }
}
