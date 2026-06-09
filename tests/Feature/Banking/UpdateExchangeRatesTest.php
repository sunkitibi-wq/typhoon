<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateExchangeRatesTest extends TestCase
{
    use RefreshDatabase;

    private CryptoCurrency $btc;
    private CryptoCurrency $eth;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->eth = CryptoCurrency::create([
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
    }

    public function test_command_runs_with_mock_source(): void
    {
        $this->artisan('typhoon:update-exchange-rates --source=mock')
            ->expectsOutput('Updating exchange rates from source: mock')
            ->expectsOutput('Exchange rates updated successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'BTC',
            'quote_currency' => 'EUR',
        ]);

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'EUR',
            'quote_currency' => 'USD',
        ]);
    }

    public function test_command_fails_on_coinmarketcap_when_api_key_missing(): void
    {
        config(['services.exchange_rates.coinmarketcap_key' => null]);

        $this->artisan('typhoon:update-exchange-rates --source=coinmarketcap')
            ->expectsOutput('CoinMarketCap API key not configured. Set COINMARKETCAP_API_KEY in .env')
            ->assertExitCode(0);
    }

    public function test_command_runs_with_coinmarketcap_source(): void
    {
        config(['services.exchange_rates.coinmarketcap_key' => 'fake_cmc_key']);

        Http::fake([
            'https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest*' => Http::response([
                'data' => [
                    'BTC' => [
                        [
                            'quote' => [
                                'EUR' => [
                                    'price' => 60000.00,
                                    'volume_24h' => 50000000,
                                    'percent_change_24h' => 1.5,
                                ]
                            ]
                        ]
                    ],
                    'ETH' => [
                        [
                            'quote' => [
                                'EUR' => [
                                    'price' => 3000.00,
                                    'volume_24h' => 20000000,
                                    'percent_change_24h' => -0.5,
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
            'https://open.er-api.com/v6/latest/EUR' => Http::response([
                'rates' => [
                    'USD' => 1.08,
                    'GBP' => 0.85,
                    'CHF' => 0.96,
                ]
            ], 200)
        ]);

        $this->artisan('typhoon:update-exchange-rates --source=coinmarketcap')
            ->expectsOutput('Updating exchange rates from source: coinmarketcap')
            ->expectsOutput('Updated 2 crypto exchange rates from CoinMarketCap.')
            ->expectsOutput('Updated 12 fiat exchange rates from Open Exchange Rates API.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'BTC',
            'quote_currency' => 'EUR',
            'mid_rate' => 60000.00,
        ]);

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'EUR',
            'quote_currency' => 'USD',
            'mid_rate' => 1.08,
        ]);
    }

    public function test_command_fails_on_coinapi_when_api_key_missing(): void
    {
        config(['services.exchange_rates.coinapi_key' => null]);

        $this->artisan('typhoon:update-exchange-rates --source=coinapi')
            ->expectsOutput('CoinAPI key not configured. Set COINAPI_API_KEY in .env')
            ->assertExitCode(0);
    }

    public function test_command_runs_with_coinapi_source(): void
    {
        config(['services.exchange_rates.coinapi_key' => 'fake_coinapi_key']);

        Http::fake([
            'https://api-realtime.exrates.coinapi.io/v1/assets*' => Http::response([
                [
                    'asset_id' => 'BTC',
                    'volume_1day_usd' => 45000000,
                ],
                [
                    'asset_id' => 'ETH',
                    'volume_1day_usd' => 18000000,
                ]
            ], 200),
            'https://api-realtime.exrates.coinapi.io/v1/exchangerate/BTC/EUR' => Http::response([
                'rate' => 59000.00,
            ], 200),
            'https://api-realtime.exrates.coinapi.io/v1/exchangerate/ETH/EUR' => Http::response([
                'rate' => 2950.00,
            ], 200),
            'https://api-realtime.exrates.coinapi.io/v1/exchangerate/EUR*' => Http::response([
                'rates' => [
                    [
                        'asset_id_quote' => 'USD',
                        'rate' => 1.09,
                    ],
                    [
                        'asset_id_quote' => 'GBP',
                        'rate' => 0.86,
                    ],
                    [
                        'asset_id_quote' => 'CHF',
                        'rate' => 0.95,
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('typhoon:update-exchange-rates --source=coinapi')
            ->expectsOutput('Updating exchange rates from source: coinapi')
            ->expectsOutput('Updated 2 crypto exchange rates from CoinAPI.')
            ->expectsOutput('Updated 12 fiat exchange rates from CoinAPI.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'BTC',
            'quote_currency' => 'EUR',
            'mid_rate' => 59000.00,
        ]);

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'EUR',
            'quote_currency' => 'USD',
            'mid_rate' => 1.09,
        ]);
    }
}
