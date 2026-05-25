<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\AccountType;
use App\Models\Banking\ExchangeRate;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCurrencyTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccountType $accountType;
    private Account $eurAccount;
    private Account $usdAccount;

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

        // Create a source EUR account
        $this->eurAccount = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000003001',
            'iban' => 'DE89370400440532013001',
            'swift_bic' => 'COBADEFFXXX',
            'currency' => 'EUR',
            'balance' => 1000,
            'available_balance' => 1000,
            'ledger_balance' => 1000,
            'status' => 'active',
            'label' => 'EUR Account',
            'is_default' => true,
        ]);

        // Create a destination USD account
        $this->usdAccount = $this->user->accounts()->create([
            'account_type_id' => $this->accountType->id,
            'account_number' => 'TY0000003002',
            'iban' => 'DE89370400440532013002',
            'swift_bic' => 'COBADEFFXXX',
            'currency' => 'USD',
            'balance' => 0,
            'available_balance' => 0,
            'ledger_balance' => 0,
            'status' => 'active',
            'label' => 'USD Account',
            'is_default' => false,
        ]);

        // Seed exchange rate EUR/USD = 1.10
        ExchangeRate::create([
            'base_currency' => 'EUR',
            'quote_currency' => 'USD',
            'bid' => 1.099,
            'ask' => 1.101,
            'mid_rate' => 1.10,
            'change_24h' => 0.5,
            'volume_24h' => 1000000,
            'last_refreshed_at' => now(),
        ]);
    }

    public function test_create_account_validation_accepts_custom_currency(): void
    {
        $response = $this->actingAs($this->user)->post(route('banking.accounts.create'), [
            'account_type_code' => 'personal',
            'label' => 'My USD Account',
            'currency' => 'USD',
        ]);

        $response->assertRedirect(route('banking.accounts'));
        
        $this->assertDatabaseHas('accounts', [
            'user_id' => $this->user->id,
            'currency' => 'USD',
            'label' => 'My USD Account',
        ]);
    }

    public function test_cross_currency_transfer_performs_conversion(): void
    {
        $transactionService = app(TransactionService::class);

        // Transfer €100 EUR from EUR Account to USD Account (exchange rate 1.10)
        $tx = $transactionService->transfer($this->eurAccount, $this->usdAccount, 100.0, 'EUR to USD Conversion');

        $this->eurAccount->refresh();
        $this->usdAccount->refresh();

        // €100 EUR deducted from EUR account (balance: 1000 - 100 = 900)
        $this->assertEquals(900.0, (float) $this->eurAccount->balance);

        // €100 * 1.10 = $110.00 USD credited to USD account
        $this->assertEquals(110.0, (float) $this->usdAccount->balance);

        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'amount' => 100.0,
            'net_amount' => 100.0,
            'currency' => 'EUR',
            'status' => 'completed',
        ]);
    }

    public function test_fiat_exchange_rates_sync_command(): void
    {
        $this->artisan('typhoon:update-exchange-rates --source=mock')
            ->assertExitCode(0);

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'EUR',
            'quote_currency' => 'USD',
        ]);
        
        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'USD',
            'quote_currency' => 'EUR',
        ]);
    }
}
