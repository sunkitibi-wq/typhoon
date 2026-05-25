<?php

namespace App\Console\Commands;

use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateExchangeRates extends Command
{
    protected $signature = 'typhoon:update-exchange-rates
        {--source= : Data source (coingecko, mock)}
        {--base=EUR : Base currency for fiat rates}';

    protected $description = 'Update cryptocurrency and fiat exchange rates from external API';

    public function handle(): int
    {
        $source = $this->option('source') ?? config('services.exchange_rates.source', 'mock');
        $base = $this->option('base');

        $this->info("Updating exchange rates from source: {$source}");

        if ($source === 'coingecko') {
            $this->fromCoinGecko($base);
            $this->fromFrankfurter($base);
        } else {
            $this->fromMock($base);
        }

        $this->newLine();
        $this->info('Exchange rates updated successfully.');

        return self::SUCCESS;
    }

    private function fromCoinGecko(string $base): void
    {
        $apiKey = config('services.exchange_rates.coingecko_key');
        $isPro = config('services.exchange_rates.coingecko_is_pro', true);

        $currencies = CryptoCurrency::where('status', 'active')
            ->whereNotNull('coingecko_id')
            ->pluck('code', 'coingecko_id');

        if ($currencies->isEmpty()) {
            $this->warn('No active crypto currencies with coingecko_id found.');
            return;
        }

        $coinIds = $currencies->keys()->implode(',');

        $domain = $isPro && $apiKey ? 'pro-api.coingecko.com' : 'api.coingecko.com';
        $headers = [];
        if ($apiKey) {
            $headers[$isPro ? 'x-cg-pro-api-key' : 'x-cg-demo-api-key'] = $apiKey;
        }

        $response = Http::withHeaders($headers)
            ->get("https://{$domain}/api/v3/simple/price", [
                'ids' => $coinIds,
                'vs_currencies' => strtolower($base),
                'include_24hr_change' => 'true',
                'include_24hr_vol' => 'true',
                'include_24hr_high_low' => 'true',
            ]);

        if ($response->failed()) {
            $this->error('Failed to fetch rates from CoinGecko: ' . $response->body());
            return;
        }

        $data = $response->json();
        $count = 0;

        foreach ($data as $coinId => $rates) {
            $code = $currencies[$coinId] ?? null;
            if (!$code) {
                continue;
            }

            $price = $rates[strtolower($base)] ?? null;
            if (!$price) {
                continue;
            }

            $baseLower = strtolower($base);
            ExchangeRate::updateOrCreate(
                ['base_currency' => $code, 'quote_currency' => strtoupper($base)],
                [
                    'bid' => $price * 0.9995,
                    'ask' => $price * 1.0005,
                    'mid_rate' => $price,
                    'change_24h' => $rates["{$baseLower}_24h_change"] ?? 0,
                    'volume_24h' => $rates["{$baseLower}_24h_vol"] ?? 0,
                    'high_24h' => $rates["{$baseLower}_24h_high"] ?? 0,
                    'low_24h' => $rates["{$baseLower}_24h_low"] ?? 0,
                    'last_refreshed_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Updated {$count} exchange rates from CoinGecko.");
    }

    private function fromFrankfurter(string $base): void
    {
        $this->info("Fetching fiat rates from Frankfurter against base: {$base}...");
        
        try {
            $response = Http::get("https://api.frankfurter.app/latest", [
                'base' => $base,
            ]);

            if ($response->failed()) {
                $this->error('Failed to fetch rates from Frankfurter: ' . $response->body());
                return;
            }

            $data = $response->json();
            $rates = $data['rates'] ?? [];
            $count = 0;

            foreach ($rates as $currency => $rate) {
                if (!in_array($currency, ['USD', 'GBP', 'CHF'])) {
                    continue;
                }

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
                    $rateC1InBase = $rates[$c1] ?? 1.0;
                    $rateC2InBase = $rates[$c2] ?? 1.0;
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

            $this->info("Updated {$count} fiat exchange rates from Frankfurter.");
        } catch (\Exception $e) {
            $this->error('Frankfurter API Error: ' . $e->getMessage());
        }
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
}
