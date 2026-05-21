<?php

namespace Database\Seeders;

use App\Models\Banking\AccountType;
use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\ExchangeRate;
use App\Models\Banking\FeeSchedule;
use App\Models\Banking\MonitoringRule;
use App\Models\Banking\PlatformSetting;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BankingSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = [
            'admin' => 'Full system access',
            'compliance' => 'Compliance and monitoring',
            'user' => 'Standard client user',
            'auditor' => 'Read-only access',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@typhoon.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '+491234567890',
                'country_of_residence' => 'DE',
                'kyc_level' => 'tier_3',
                'status' => 'active',
            ]
        );
        $admin->role()->associate(Role::where('name', 'admin')->first());
        $admin->save();

        // Create test client user
        $client = User::firstOrCreate(
            ['email' => 'client@typhoon.com'],
            [
                'name' => 'Emma Johnson',
                'password' => Hash::make('password'),
                'phone' => '+491234567891',
                'country_of_residence' => 'DE',
                'kyc_level' => 'tier_2',
                'status' => 'active',
                'date_of_birth' => '1990-05-15',
            ]
        );
        $client->role()->associate(Role::where('name', 'user')->first());
        $client->save();

        // Account types
        $accountTypes = [
            ['code' => 'personal', 'name' => 'Personal Account', 'currency' => 'EUR', 'minimum_balance' => 0, 'monthly_fee' => 0],
            ['code' => 'savings', 'name' => 'Savings Account', 'currency' => 'EUR', 'minimum_balance' => 100, 'monthly_fee' => 0],
            ['code' => 'business', 'name' => 'Business Account', 'currency' => 'EUR', 'minimum_balance' => 1000, 'monthly_fee' => 10],
            ['code' => 'crypto_fiat', 'name' => 'Crypto-Fiat Wallet', 'currency' => 'EUR', 'minimum_balance' => 0, 'monthly_fee' => 0],
        ];

        foreach ($accountTypes as $type) {
            AccountType::firstOrCreate(['code' => $type['code']], $type);
        }

        // Create accounts for client
        $personalType = AccountType::where('code', 'personal')->first();
        $savingsType = AccountType::where('code', 'savings')->first();

        $client->accounts()->create([
            'account_type_id' => $personalType->id,
            'account_number' => 'TY0000001001',
            'iban' => 'DE89370400440532013000',
            'swift_bic' => 'COBADEFFXXX',
            'currency' => 'EUR',
            'balance' => 15000.00,
            'available_balance' => 15000.00,
            'ledger_balance' => 15000.00,
            'status' => 'active',
            'label' => 'Main Account',
            'is_default' => true,
        ]);

        $client->accounts()->create([
            'account_type_id' => $savingsType->id,
            'account_number' => 'TY0000001002',
            'iban' => 'DE89700400440532013001',
            'currency' => 'EUR',
            'balance' => 50000.00,
            'available_balance' => 50000.00,
            'ledger_balance' => 50000.00,
            'status' => 'active',
            'label' => 'Savings Account',
            'is_default' => false,
        ]);

        // Also create an account for admin
        $admin->accounts()->create([
            'account_type_id' => $personalType->id,
            'account_number' => 'TY0000002001',
            'currency' => 'EUR',
            'balance' => 250000.00,
            'available_balance' => 250000.00,
            'ledger_balance' => 250000.00,
            'status' => 'active',
            'label' => 'Admin Account',
            'is_default' => true,
        ]);

        // Crypto currencies
        $cryptos = [
            ['code' => 'BTC', 'coingecko_id' => 'bitcoin', 'name' => 'Bitcoin', 'network' => 'Bitcoin', 'decimals' => 8, 'minimum_withdrawal' => 0.001, 'withdrawal_fee' => 0.0005, 'minimum_deposit' => 0.0001, 'deposit_fee' => 0, 'is_quote_currency' => true],
            ['code' => 'ETH', 'coingecko_id' => 'ethereum', 'name' => 'Ethereum', 'network' => 'ERC20', 'decimals' => 18, 'minimum_withdrawal' => 0.01, 'withdrawal_fee' => 0.005, 'minimum_deposit' => 0.001, 'deposit_fee' => 0, 'is_quote_currency' => true],
            ['code' => 'USDT', 'coingecko_id' => 'tether', 'name' => 'Tether', 'network' => 'ERC20', 'decimals' => 6, 'minimum_withdrawal' => 10, 'withdrawal_fee' => 1, 'minimum_deposit' => 1, 'deposit_fee' => 0, 'is_quote_currency' => true],
            ['code' => 'SOL', 'coingecko_id' => 'solana', 'name' => 'Solana', 'network' => 'Solana', 'decimals' => 9, 'minimum_withdrawal' => 0.1, 'withdrawal_fee' => 0.01, 'minimum_deposit' => 0.01, 'deposit_fee' => 0],
            ['code' => 'XRP', 'coingecko_id' => 'ripple', 'name' => 'Ripple', 'network' => 'XRP Ledger', 'decimals' => 6, 'minimum_withdrawal' => 1, 'withdrawal_fee' => 0.1, 'minimum_deposit' => 0.1, 'deposit_fee' => 0],
        ];

        foreach ($cryptos as $crypto) {
            CryptoCurrency::firstOrCreate(['code' => $crypto['code']], $crypto);
        }

        // Exchange rates
        $btc = CryptoCurrency::where('code', 'BTC')->first();
        $eth = CryptoCurrency::where('code', 'ETH')->first();
        $usdt = CryptoCurrency::where('code', 'USDT')->first();

        ExchangeRate::create([
            'base_currency' => 'BTC', 'quote_currency' => 'EUR',
            'bid' => 62500.00, 'ask' => 62800.00, 'mid_rate' => 62650.00,
            'change_24h' => 2.35, 'volume_24h' => 28500000000,
            'high_24h' => 63500.00, 'low_24h' => 61800.00,
            'last_refreshed_at' => now(),
        ]);

        ExchangeRate::create([
            'base_currency' => 'ETH', 'quote_currency' => 'EUR',
            'bid' => 3450.00, 'ask' => 3475.00, 'mid_rate' => 3462.50,
            'change_24h' => -1.20, 'volume_24h' => 15000000000,
            'high_24h' => 3520.00, 'low_24h' => 3410.00,
            'last_refreshed_at' => now(),
        ]);

        ExchangeRate::create([
            'base_currency' => 'USDT', 'quote_currency' => 'EUR',
            'bid' => 0.92, 'ask' => 0.93, 'mid_rate' => 0.925,
            'change_24h' => 0.05, 'volume_24h' => 45000000000,
            'last_refreshed_at' => now(),
        ]);

        ExchangeRate::create([
            'base_currency' => 'SOL', 'quote_currency' => 'EUR',
            'bid' => 145.00, 'ask' => 147.50, 'mid_rate' => 146.25,
            'change_24h' => 5.80, 'volume_24h' => 3200000000,
            'last_refreshed_at' => now(),
        ]);

        // Fee schedules
        $fees = [
            ['name' => 'SEPA Transfer Fee', 'fee_type' => 'transfer', 'calculation_method' => 'fixed', 'fee_value' => 0.50, 'currency' => 'EUR', 'is_active' => true],
            ['name' => 'International Transfer Fee', 'fee_type' => 'transfer', 'calculation_method' => 'percentage', 'fee_value' => 1.00, 'min_fee' => 5.00, 'max_fee' => 50.00, 'currency' => 'EUR', 'is_active' => true],
            ['name' => 'ATM Withdrawal Fee', 'fee_type' => 'withdrawal', 'calculation_method' => 'fixed', 'fee_value' => 2.00, 'currency' => 'EUR', 'is_active' => true],
            ['name' => 'Crypto Withdrawal Fee', 'fee_type' => 'crypto_withdrawal', 'calculation_method' => 'fixed', 'fee_value' => 0.001, 'currency' => 'BTC', 'is_active' => true],
        ];

        foreach ($fees as $fee) {
            FeeSchedule::create($fee);
        }

        // Monitoring rules
        $rules = [
            [
                'name' => 'Large Transaction Alert',
                'category' => 'transaction_volume',
                'rule_type' => 'threshold',
                'conditions' => ['amount' => ['operator' => 'gte', 'value' => 10000]],
                'severity' => 'high', 'is_active' => true,
                'description' => 'Flag transactions of 10,000 EUR or more',
                'action' => 'flag',
            ],
            [
                'name' => 'Rapid Withdrawal Detection',
                'category' => 'velocity',
                'rule_type' => 'velocity',
                'conditions' => ['type' => ['operator' => 'eq', 'value' => 'withdrawal']],
                'severity' => 'medium', 'is_active' => true,
                'description' => 'Monitor rapid sequential withdrawals',
                'action' => 'flag',
            ],
        ];

        foreach ($rules as $rule) {
            MonitoringRule::create($rule);
        }

        // Platform settings
        $settings = [
            ['key' => 'platform_name', 'value' => 'Typhoon Banking', 'group' => 'general', 'type' => 'string'],
            ['key' => 'support_email', 'value' => 'support@typhoon.com', 'group' => 'general', 'type' => 'string'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'max_daily_transfer', 'value' => '100000', 'group' => 'limits', 'type' => 'number'],
            ['key' => 'default_currency', 'value' => 'EUR', 'group' => 'general', 'type' => 'string'],
            ['key' => 'kyc_required', 'value' => 'true', 'group' => 'compliance', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        $this->command->info('Banking demo data seeded successfully!');
        $this->command->info('Admin login: admin@typhoon.com / password');
        $this->command->info('Client login: client@typhoon.com / password');
    }
}
