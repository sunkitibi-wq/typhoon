<?php

namespace App\Console\Commands;

use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateExchangeRates extends Command
{
    protected $signature = 'typhoon:update-exchange-rates
        {--source= : Data source (coinmarketcap, coinapi, mock)}
        {--base=EUR : Base currency for fiat rates}';

    protected $description = 'Update cryptocurrency and fiat exchange rates from CoinMarketCap / CoinAPI';

    private const BASE_URL = 'https://api-realtime.exrates.coinapi.io';

    private const FIAT_CURRENCIES = ['USD', 'GBP', 'CHF'];

    public function handle(): int
    {
        $source = $this->option('source') ?? config('services.exchange_rates.source', 'mock');
        $base = $this->option('base');

        $this->info("Updating exchange rates from source: {$source}");

        if ($source === 'coinmarketcap') {
            $this->fromCoinMarketCapCrypto($base);
            $this->fromCoinMarketCapFiat($base);
        } elseif ($source === 'coinapi') {
            $this->fromCoinApiCrypto($base);
            $this->fromCoinApiFiat($base);
        } else {
            $this->fromMock($base);
        }

        $this->newLine();
        $this->info('Exchange rates updated successfully.');

        return self::SUCCESS;
    }

    private function fromCoinApiCrypto(string $base): void
    {
        $apiKey = config('services.exchange_rates.coinapi_key');
        if (!$apiKey) {
            $this->error('CoinAPI key not configured. Set COINAPI_API_KEY in .env');
            return;
        }

        $codes = CryptoCurrency::where('status', 'active')
            ->pluck('code');

        if ($codes->isEmpty()) {
            $this->warn('No active crypto currencies found.');
            return;
        }

        $headers = ['X-CoinAPI-Key' => $apiKey];
        $count = 0;

        $assetsResponse = Http::withHeaders($headers)
            ->get(self::BASE_URL . '/v1/assets', [
                'filter_asset_id' => $codes->implode(';'),
            ]);

        $volumes = [];
        if ($assetsResponse->successful()) {
            foreach ($assetsResponse->json() as $asset) {
                $volumes[$asset['asset_id']] = $asset['volume_1day_usd'] ?? 0;
            }
        }

        foreach ($codes as $code) {
            $response = Http::withHeaders($headers)
                ->get(self::BASE_URL . "/v1/exchangerate/{$code}/{$base}");

            if ($response->failed()) {
                $this->warn("Failed to fetch rate for {$code}/{$base}: " . $response->body());
                continue;
            }

            $data = $response->json();
            $rate = $data['rate'] ?? null;

            if (!$rate || $rate <= 0) {
                continue;
            }

            $volume = $volumes[$code] ?? 0;

            ExchangeRate::updateOrCreate(
                ['base_currency' => $code, 'quote_currency' => strtoupper($base)],
                [
                    'bid' => $rate * 0.9995,
                    'ask' => $rate * 1.0005,
                    'mid_rate' => $rate,
                    'change_24h' => 0,
                    'volume_24h' => $volume,
                    'high_24h' => $rate * 1.01,
                    'low_24h' => $rate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Updated {$count} crypto exchange rates from CoinAPI.");
    }

    private function fromCoinApiFiat(string $base): void
    {
        $apiKey = config('services.exchange_rates.coinapi_key');
        if (!$apiKey) {
            return;
        }

        $headers = ['X-CoinAPI-Key' => $apiKey];
        $filter = implode(';', self::FIAT_CURRENCIES);

        $response = Http::withHeaders($headers)
            ->get(self::BASE_URL . "/v1/exchangerate/{$base}", [
                'filter_asset_id' => $filter,
            ]);

        if ($response->failed()) {
            $this->error('Failed to fetch fiat rates from CoinAPI: ' . $response->body());
            return;
        }

        $data = $response->json();
        $rates = [];
        foreach ($data['rates'] ?? [] as $r) {
            $rates[$r['asset_id_quote']] = $r['rate'];
        }

        $count = 0;

        foreach ($rates as $currency => $rate) {
            if (!in_array($currency, self::FIAT_CURRENCIES, true)) {
                continue;
            }

            ExchangeRate::updateOrCreate(
                ['base_currency' => strtoupper($base), 'quote_currency' => $currency],
                [
                    'bid' => $rate * 0.999,
                    'ask' => $rate * 1.001,
                    'mid_rate' => $rate,
                    'change_24h' => 0,
                    'volume_24h' => 0,
                    'high_24h' => $rate * 1.01,
                    'low_24h' => $rate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );

            $inverseRate = 1 / $rate;
            ExchangeRate::updateOrCreate(
                ['base_currency' => $currency, 'quote_currency' => strtoupper($base)],
                [
                    'bid' => $inverseRate * 0.999,
                    'ask' => $inverseRate * 1.001,
                    'mid_rate' => $inverseRate,
                    'change_24h' => 0,
                    'volume_24h' => 0,
                    'high_24h' => $inverseRate * 1.01,
                    'low_24h' => $inverseRate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );
            $count += 2;
        }

        foreach (self::FIAT_CURRENCIES as $c1) {
            foreach (self::FIAT_CURRENCIES as $c2) {
                if ($c1 === $c2) continue;
                $rateC1 = $rates[$c1] ?? 1.0;
                $rateC2 = $rates[$c2] ?? 1.0;
                $crossRate = $rateC2 / $rateC1;

                ExchangeRate::updateOrCreate(
                    ['base_currency' => $c1, 'quote_currency' => $c2],
                    [
                        'bid' => $crossRate * 0.999,
                        'ask' => $crossRate * 1.001,
                        'mid_rate' => $crossRate,
                        'change_24h' => 0,
                        'volume_24h' => 0,
                        'high_24h' => $crossRate * 1.01,
                        'low_24h' => $crossRate * 0.99,
                        'last_refreshed_at' => now(),
                    ]
                );
                $count++;
            }
        }

        $this->info("Updated {$count} fiat exchange rates from CoinAPI.");
    }

    private function fromMock(string $base): void
    {
        $rates = [
            ['base' => 'BTC', 'mid' => 65420.50, 'change' => 2.35],
            ['base' => 'ETH', 'mid' => 3450.80, 'change' => -1.20],
            ['base' => 'USDT', 'mid' => 0.92, 'change' => 0.05],
            ['base' => 'SOL', 'mid' => 145.30, 'change' => 5.60],
            ['base' => 'XRP', 'mid' => 0.62, 'change' => -0.80],
        ];

        $count = 0;

        foreach ($rates as $rate) {
            ExchangeRate::updateOrCreate(
                ['base_currency' => $rate['base'], 'quote_currency' => strtoupper($base)],
                [
                    'bid' => $rate['mid'] * 0.9995,
                    'ask' => $rate['mid'] * 1.0005,
                    'mid_rate' => $rate['mid'],
                    'change_24h' => $rate['change'],
                    'volume_24h' => rand(1000000, 50000000),
                    'high_24h' => $rate['mid'] * 1.02,
                    'low_24h' => $rate['mid'] * 0.98,
                    'last_refreshed_at' => now(),
                ]
            );
            $count++;
        }

        $fiatMock = [
            'USD' => 1.08 + (rand(-10, 10) / 1000),
            'GBP' => 0.85 + (rand(-10, 10) / 1000),
            'CHF' => 0.96 + (rand(-10, 10) / 1000),
        ];

        foreach ($fiatMock as $currency => $rate) {
            ExchangeRate::updateOrCreate(
                ['base_currency' => strtoupper($base), 'quote_currency' => $currency],
                [
                    'bid' => $rate * 0.999,
                    'ask' => $rate * 1.001,
                    'mid_rate' => $rate,
                    'change_24h' => (rand(-100, 100) / 100),
                    'volume_24h' => rand(10000000, 50000000),
                    'high_24h' => $rate * 1.01,
                    'low_24h' => $rate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );

            $inverseRate = 1 / $rate;
            ExchangeRate::updateOrCreate(
                ['base_currency' => $currency, 'quote_currency' => strtoupper($base)],
                [
                    'bid' => $inverseRate * 0.999,
                    'ask' => $inverseRate * 1.001,
                    'mid_rate' => $inverseRate,
                    'change_24h' => (rand(-100, 100) / 100),
                    'volume_24h' => rand(10000000, 50000000),
                    'high_24h' => $inverseRate * 1.01,
                    'low_24h' => $inverseRate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );
            $count += 2;
        }

        $fiatCurrencies = ['USD', 'GBP', 'CHF'];
        foreach ($fiatCurrencies as $c1) {
            foreach ($fiatCurrencies as $c2) {
                if ($c1 === $c2) continue;
                $rateC1InBase = $fiatMock[$c1];
                $rateC2InBase = $fiatMock[$c2];
                $crossRate = $rateC2InBase / $rateC1InBase;

                ExchangeRate::updateOrCreate(
                    ['base_currency' => $c1, 'quote_currency' => $c2],
                    [
                        'bid' => $crossRate * 0.999,
                        'ask' => $crossRate * 1.001,
                        'mid_rate' => $crossRate,
                        'change_24h' => (rand(-100, 100) / 100),
                        'volume_24h' => rand(10000000, 50000000),
                        'high_24h' => $crossRate * 1.01,
                        'low_24h' => $crossRate * 0.99,
                        'last_refreshed_at' => now(),
                    ]
                );
                $count++;
            }
        }

        $this->info("Updated {$count} mock exchange rates (base: {$base}).");
    }

    private function fromCoinMarketCapCrypto(string $base): void
    {
        $apiKey = config('services.exchange_rates.coinmarketcap_key');
        $baseUrl = config('services.exchange_rates.coinmarketcap_url', 'https://pro-api.coinmarketcap.com');
        if (!$apiKey) {
            $this->error('CoinMarketCap API key not configured. Set COINMARKETCAP_API_KEY in .env');
            return;
        }

        $codes = CryptoCurrency::where('status', 'active')
            ->pluck('code');

        if ($codes->isEmpty()) {
            $this->warn('No active crypto currencies found.');
            return;
        }

        $headers = [
            'X-CMC_PRO_API_KEY' => $apiKey,
            'Accept' => 'application/json',
        ];

        $response = Http::withHeaders($headers)
            ->get($baseUrl . '/v2/cryptocurrency/quotes/latest', [
                'symbol' => $codes->implode(','),
                'convert' => strtoupper($base),
            ]);

        if ($response->failed()) {
            $this->error('Failed to fetch crypto rates from CoinMarketCap: ' . $response->body());
            return;
        }

        $data = $response->json()['data'] ?? [];
        $count = 0;

        foreach ($codes as $code) {
            $assets = $data[strtoupper($code)] ?? null;
            if (!$assets || !is_array($assets) || empty($assets)) {
                $this->warn("No rate found for {$code}");
                continue;
            }

            $asset = $assets[0];
            $quote = $asset['quote'][strtoupper($base)] ?? null;

            if (!$quote) {
                $this->warn("No conversion quote found for {$code} to {$base}");
                continue;
            }

            $rate = $quote['price'] ?? null;
            if (!$rate || $rate <= 0) {
                continue;
            }

            $volume = $quote['volume_24h'] ?? 0;
            $change = $quote['percent_change_24h'] ?? 0;

            ExchangeRate::updateOrCreate(
                ['base_currency' => strtoupper($code), 'quote_currency' => strtoupper($base)],
                [
                    'bid' => $rate * 0.9995,
                    'ask' => $rate * 1.0005,
                    'mid_rate' => $rate,
                    'change_24h' => $change,
                    'volume_24h' => $volume,
                    'high_24h' => $rate * 1.01,
                    'low_24h' => $rate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Updated {$count} crypto exchange rates from CoinMarketCap.");
    }

    private function fromCoinMarketCapFiat(string $base): void
    {
        $response = Http::get("https://open.er-api.com/v6/latest/" . strtoupper($base));

        if ($response->failed()) {
            $this->error('Failed to fetch fiat rates from open exchange rates API: ' . $response->body());
            return;
        }

        $data = $response->json();
        $rates = $data['rates'] ?? $data['conversion_rates'] ?? [];

        if (empty($rates)) {
            $this->error('No fiat rates found in the open exchange rates response.');
            return;
        }

        $count = 0;

        foreach ($rates as $currency => $rate) {
            if (!in_array($currency, self::FIAT_CURRENCIES, true)) {
                continue;
            }

            ExchangeRate::updateOrCreate(
                ['base_currency' => strtoupper($base), 'quote_currency' => $currency],
                [
                    'bid' => $rate * 0.999,
                    'ask' => $rate * 1.001,
                    'mid_rate' => $rate,
                    'change_24h' => 0,
                    'volume_24h' => 0,
                    'high_24h' => $rate * 1.01,
                    'low_24h' => $rate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );

            $inverseRate = 1 / $rate;
            ExchangeRate::updateOrCreate(
                ['base_currency' => $currency, 'quote_currency' => strtoupper($base)],
                [
                    'bid' => $inverseRate * 0.999,
                    'ask' => $inverseRate * 1.001,
                    'mid_rate' => $inverseRate,
                    'change_24h' => 0,
                    'volume_24h' => 0,
                    'high_24h' => $inverseRate * 1.01,
                    'low_24h' => $inverseRate * 0.99,
                    'last_refreshed_at' => now(),
                ]
            );
            $count += 2;
        }

        foreach (self::FIAT_CURRENCIES as $c1) {
            foreach (self::FIAT_CURRENCIES as $c2) {
                if ($c1 === $c2) continue;
                $rateC1 = $rates[$c1] ?? 1.0;
                $rateC2 = $rates[$c2] ?? 1.0;
                $crossRate = $rateC2 / $rateC1;

                ExchangeRate::updateOrCreate(
                    ['base_currency' => $c1, 'quote_currency' => $c2],
                    [
                        'bid' => $crossRate * 0.999,
                        'ask' => $crossRate * 1.001,
                        'mid_rate' => $crossRate,
                        'change_24h' => 0,
                        'volume_24h' => 0,
                        'high_24h' => $crossRate * 1.01,
                        'low_24h' => $crossRate * 0.99,
                        'last_refreshed_at' => now(),
                    ]
                );
                $count++;
            }
        }

        $this->info("Updated {$count} fiat exchange rates from Open Exchange Rates API.");
    }
}
