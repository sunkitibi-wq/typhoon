<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\CryptoDeposit;
use App\Models\Banking\CryptoOrder;
use App\Models\Banking\CryptoWallet;
use App\Models\Banking\CryptoWithdrawal;
use App\Models\Banking\ExchangeRate;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CryptoExchangeService
{
    public function __construct(
        private readonly BlockchainService $blockchain,
        private readonly TransactionService $transactionService,
    ) {}

    public function createWallet(User $user, CryptoCurrency $currency, ?string $label = null): CryptoWallet
    {
        if ($user->cryptoWallets()->where('crypto_currency_id', $currency->id)->exists()) {
            throw new \RuntimeException("Wallet for {$currency->code} already exists");
        }

        $keyPair = $this->blockchain->generateAddress();

        return CryptoWallet::create([
            'user_id' => $user->id,
            'crypto_currency_id' => $currency->id,
            'address' => $keyPair['address'],
            'label' => $label ?? "{$currency->name} Wallet",
            'private_key' => $keyPair['private_key'],
            'balance' => 0,
            'locked_balance' => 0,
            'status' => 'active',
        ]);
    }

    public function placeOrder(User $user, array $data): CryptoOrder
    {
        return DB::transaction(function () use ($user, $data) {
            $rate = ExchangeRate::where('base_currency', $data['base_currency'])
                ->where('quote_currency', $data['quote_currency'])
                ->firstOrFail();

            $price = $data['side'] === 'buy' ? $rate->ask : $rate->bid;

            $order = CryptoOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'order_type' => $data['order_type'] ?? 'market',
                'side' => $data['side'],
                'base_currency' => $data['base_currency'],
                'quote_currency' => $data['quote_currency'],
                'amount' => $data['amount'],
                'filled_amount' => 0,
                'price' => $data['order_type'] === 'market' ? $price : ($data['price'] ?? null),
                'stop_price' => $data['stop_price'] ?? null,
                'fee' => 0,
                'fee_rate' => 0.001,
                'total' => $data['amount'] * $price,
                'status' => 'open',
                'time_in_force' => $data['time_in_force'] ?? 'GTC',
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            if ($data['order_type'] === 'market') {
                $this->executeMarketOrder($order);
            }

            return $order;
        });
    }

    public function executeMarketOrder(CryptoOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $totalCost = $order->amount * $order->price;
            $fee = $totalCost * $order->fee_rate;

            $quoteWallet = CryptoWallet::where('user_id', $order->user_id)
                ->whereHas('cryptoCurrency', fn($q) => $q->where('code', $order->quote_currency))
                ->firstOrFail();

            $baseWallet = CryptoWallet::where('user_id', $order->user_id)
                ->whereHas('cryptoCurrency', fn($q) => $q->where('code', $order->base_currency))
                ->firstOrFail();

            // Lock wallets in order of ID to prevent deadlocks
            $firstId = min($quoteWallet->id, $baseWallet->id);
            $secondId = max($quoteWallet->id, $baseWallet->id);

            $wallets = CryptoWallet::whereIn('id', [$firstId, $secondId])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $quoteWalletLocked = $wallets->get($quoteWallet->id);
            $baseWalletLocked = $wallets->get($baseWallet->id);

            if (!$quoteWalletLocked || !$baseWalletLocked) {
                throw new \RuntimeException('One or both crypto wallets could not be locked.');
            }

            if ($order->side === 'buy') {
                $totalDebit = $totalCost + $fee;
                if ($quoteWalletLocked->balance < $totalDebit) {
                    throw new \RuntimeException('Insufficient quote currency balance');
                }

                $quoteWalletLocked->decrement('balance', $totalDebit);
                $baseWalletLocked->increment('balance', $order->amount - ($order->amount * 0.001));
            } else {
                if ($baseWalletLocked->balance < $order->amount) {
                    throw new \RuntimeException('Insufficient base currency balance');
                }

                $baseWalletLocked->decrement('balance', $order->amount);
                $quoteWalletLocked->increment('balance', $totalCost - $fee);
            }

            $order->update([
                'filled_amount' => $order->amount,
                'fee' => $fee,
                'status' => 'filled',
                'filled_at' => now(),
            ]);
        });
    }

    public function recordDeposit(array $data): CryptoDeposit
    {
        return DB::transaction(function () use ($data) {
            $deposit = CryptoDeposit::create([
                'reference' => $data['reference'] ?? $this->generateReference('CRYPTO-DEP'),
                'user_id' => $data['user_id'],
                'crypto_currency_id' => $data['crypto_currency_id'],
                'crypto_wallet_id' => $data['crypto_wallet_id'],
                'tx_hash' => $data['tx_hash'] ?? null,
                'amount' => $data['amount'],
                'fee' => $data['fee'] ?? 0,
                'net_amount' => $data['amount'] - ($data['fee'] ?? 0),
                'from_address' => $data['from_address'] ?? null,
                'status' => 'pending',
                'confirmations' => 0,
            ]);

            return $deposit;
        });
    }

    public function confirmDeposit(CryptoDeposit $deposit): void
    {
        DB::transaction(function () use ($deposit) {
            $confirmations = 12;
            $txHash = $deposit->tx_hash;

            if ($txHash && $this->blockchain->isConfigured()) {
                $status = $this->blockchain->getTransactionStatus($txHash);
                $confirmations = $status['confirmations'] ?? 0;
            }

            $deposit->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmations' => $confirmations,
            ]);

            $wallet = CryptoWallet::where('id', $deposit->crypto_wallet_id)->lockForUpdate()->firstOrFail();
            $wallet->increment('balance', $deposit->net_amount);
        });
    }

    public function requestWithdrawal(User $user, CryptoWallet $wallet, float $amount, string $toAddress): CryptoWithdrawal
    {
        if ($wallet->user_id !== $user->id) {
            throw new \RuntimeException('Wallet does not belong to user');
        }

        $currency = $wallet->cryptoCurrency;

        if ($amount < $currency->minimum_withdrawal) {
            throw new \RuntimeException("Minimum withdrawal is {$currency->minimum_withdrawal} {$currency->code}");
        }

        return DB::transaction(function () use ($user, $wallet, $currency, $amount, $toAddress) {
            $walletLocked = CryptoWallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($walletLocked->balance < $amount) {
                throw new \RuntimeException('Insufficient balance');
            }

            $withdrawal = CryptoWithdrawal::create([
                'reference' => $this->generateReference('CRYPTO-WTH'),
                'user_id' => $user->id,
                'crypto_currency_id' => $currency->id,
                'crypto_wallet_id' => $walletLocked->id,
                'to_address' => $toAddress,
                'amount' => $amount,
                'fee' => $currency->withdrawal_fee,
                'net_amount' => $amount - $currency->withdrawal_fee,
                'status' => 'pending',
                'tx_hash' => null,
            ]);

            $walletLocked->decrement('balance', $amount);

            return $withdrawal;
        });
    }

    public function sendCrypto(User $user, CryptoWallet $wallet, float $amount, string $toAddress, ?int $gasLimit = null, ?string $gasPrice = null): CryptoWithdrawal
    {
        if ($wallet->user_id !== $user->id) {
            throw new \RuntimeException('Wallet does not belong to user');
        }

        $currency = $wallet->cryptoCurrency;

        if ($amount < $currency->minimum_withdrawal) {
            throw new \RuntimeException("Minimum withdrawal is {$currency->minimum_withdrawal} {$currency->code}");
        }

        return DB::transaction(function () use ($user, $wallet, $currency, $amount, $toAddress, $gasLimit, $gasPrice) {
            $walletLocked = CryptoWallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($walletLocked->balance < $amount) {
                throw new \RuntimeException('Insufficient balance');
            }

            $valueHex = $this->toWeiHex($amount - $currency->withdrawal_fee, $currency->decimals);
            $txHash = null;

            if ($this->blockchain->isConfigured()) {
                $privateKey = $walletLocked->private_key;

                if (!$privateKey) {
                    throw new \RuntimeException('Wallet private key is required to broadcast the transaction.');
                }

                try {
                    $txHash = $this->blockchain->sendTransaction(
                        $privateKey,
                        $toAddress,
                        $valueHex,
                        '0x',
                        $gasLimit ? '0x' . dechex($gasLimit) : '0x5208',
                        $gasPrice,
                    );
                } catch (\Exception $e) {
                    throw new \RuntimeException('Blockchain broadcast failed: ' . $e->getMessage());
                }
            }

            $withdrawal = CryptoWithdrawal::create([
                'reference' => $this->generateReference('CRYPTO-WTH'),
                'user_id' => $user->id,
                'crypto_currency_id' => $currency->id,
                'crypto_wallet_id' => $walletLocked->id,
                'to_address' => $toAddress,
                'amount' => $amount,
                'fee' => $currency->withdrawal_fee,
                'net_amount' => $amount - $currency->withdrawal_fee,
                'status' => $txHash ? 'submitted' : 'pending',
                'tx_hash' => $txHash,
            ]);

            $walletLocked->decrement('balance', $amount);

            return $withdrawal;
        });
    }

    public function approveWithdrawal(CryptoWithdrawal $withdrawal, User $admin): void
    {
        DB::transaction(function () use ($withdrawal, $admin) {
            $txHash = null;

            if ($this->blockchain->isConfigured()) {
                $wallet = $withdrawal->cryptoWallet;
                $privateKey = $wallet->private_key;

                if (!$privateKey) {
                    throw new \RuntimeException('Wallet private key is required to broadcast the transaction.');
                }

                try {
                    $valueHex = $this->toWeiHex($withdrawal->net_amount, $wallet->cryptoCurrency->decimals);

                    $txHash = $this->blockchain->sendTransaction(
                        $privateKey,
                        $withdrawal->to_address,
                        $valueHex,
                    );
                } catch (\Exception $e) {
                    throw new \RuntimeException('Blockchain broadcast failed: ' . $e->getMessage());
                }
            }

            $withdrawal->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'tx_hash' => $txHash ?? '0x' . Str::random(64),
            ]);
        });
    }

    public function buyWithFiat(User $user, Account $account, string $cryptoCode, float $fiatAmount, ?string $externalAddress = null): array
    {
        if ($account->user_id !== $user->id) {
            throw new \RuntimeException('Account does not belong to user');
        }

        if ($account->currency !== 'EUR') {
            throw new \RuntimeException('Only EUR accounts can be used for crypto purchases');
        }

        $rate = ExchangeRate::where('base_currency', $cryptoCode)
            ->where('quote_currency', 'EUR')
            ->firstOrFail();

        $cryptoPrice = $rate->mid_rate;
        $cryptoAmount = $fiatAmount / $cryptoPrice;
        $fee = $fiatAmount * 0.002;
        $netFiat = $fiatAmount - $fee;
        $netCrypto = $netFiat / $cryptoPrice;

        return DB::transaction(function () use ($user, $account, $cryptoCode, $fiatAmount, $cryptoAmount, $netCrypto, $fee, $externalAddress, $cryptoPrice) {
            $accountLocked = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            if ($accountLocked->available_balance < $fiatAmount) {
                throw new \RuntimeException('Insufficient available balance');
            }

            $accountLocked->decrement('balance', $fiatAmount);
            $accountLocked->decrement('available_balance', $fiatAmount);
            $accountLocked->decrement('ledger_balance', $fiatAmount);

            \App\Models\Banking\Transaction::create([
                'reference' => 'CRYPTO-BUY-' . strtoupper(Str::random(10)),
                'type' => 'withdrawal',
                'status' => 'completed',
                'debit_account_id' => $accountLocked->id,
                'user_id' => $user->id,
                'amount' => $fiatAmount,
                'fee' => 0,
                'net_amount' => $fiatAmount,
                'currency' => 'EUR',
                'description' => "Crypto purchase ({$cryptoCode})",
                'category' => 'crypto',
                'completed_at' => now(),
            ]);

            $currency = CryptoCurrency::where('code', $cryptoCode)->firstOrFail();

            $walletKeypair = $this->blockchain->generateAddress();

            $wallet = CryptoWallet::firstOrCreate(
                ['user_id' => $user->id, 'crypto_currency_id' => $currency->id],
                [
                    'address' => $walletKeypair['address'],
                    'private_key' => $walletKeypair['private_key'],
                    'label' => "{$currency->name} Wallet",
                    'balance' => 0,
                    'locked_balance' => 0,
                    'status' => 'active',
                ],
            );

            $walletLocked = CryptoWallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            $walletLocked->increment('balance', $netCrypto);

            $txHash = null;
            if ($externalAddress && $this->blockchain->isConfigured()) {
                try {
                    $weiHex = '0x' . dechex($netCrypto * 1e18);
                    $txHash = $this->blockchain->sendTransaction(
                        $walletLocked->private_key,
                        $externalAddress,
                        $weiHex,
                    );
                } catch (\Exception) {
                    $txHash = null;
                }
            }

            CryptoOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'order_type' => 'market',
                'side' => 'buy',
                'base_currency' => $cryptoCode,
                'quote_currency' => 'EUR',
                'amount' => $netCrypto,
                'filled_amount' => $netCrypto,
                'price' => $cryptoPrice,
                'fee' => $fee,
                'fee_rate' => 0.002,
                'total' => $fiatAmount,
                'status' => 'filled',
                'time_in_force' => 'GTC',
                'filled_at' => now(),
            ]);

            return [
                'fiat_amount' => $fiatAmount,
                'crypto_amount' => $netCrypto,
                'crypto_code' => $cryptoCode,
                'price_per_unit' => $cryptoPrice,
                'fee' => $fee,
                'wallet_id' => $walletLocked->id,
                'tx_hash' => $txHash,
                'to_external' => $externalAddress,
            ];
        });
    }

    public function sellToFiat(User $user, Account $account, string $cryptoCode, float $cryptoAmount): array
    {
        if ($account->user_id !== $user->id) {
            throw new \RuntimeException('Account does not belong to user');
        }

        if ($account->currency !== 'EUR') {
            throw new \RuntimeException('Only EUR accounts can be used for crypto sales');
        }

        $rate = ExchangeRate::where('base_currency', $cryptoCode)
            ->where('quote_currency', 'EUR')
            ->firstOrFail();

        $cryptoPrice = $rate->mid_rate;
        $fiatAmount = $cryptoAmount * $cryptoPrice;
        $fee = $fiatAmount * 0.002;
        $netFiat = $fiatAmount - $fee;

        $currency = CryptoCurrency::where('code', $cryptoCode)->firstOrFail();

        return DB::transaction(function () use ($user, $account, $cryptoCode, $cryptoAmount, $fiatAmount, $netFiat, $fee, $cryptoPrice, $currency) {
            $wallet = CryptoWallet::where('user_id', $user->id)
                ->where('crypto_currency_id', $currency->id)
                ->firstOrFail();

            $walletLocked = CryptoWallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($walletLocked->balance < $cryptoAmount) {
                throw new \RuntimeException('Insufficient crypto balance');
            }

            $walletLocked->decrement('balance', $cryptoAmount);

            $accountLocked = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();
            $accountLocked->increment('balance', $netFiat);
            $accountLocked->increment('available_balance', $netFiat);
            $accountLocked->increment('ledger_balance', $netFiat);

            \App\Models\Banking\Transaction::create([
                'reference' => 'CRYPTO-SELL-' . strtoupper(Str::random(10)),
                'type' => 'deposit',
                'status' => 'completed',
                'credit_account_id' => $accountLocked->id,
                'user_id' => $user->id,
                'amount' => $netFiat,
                'fee' => 0,
                'net_amount' => $netFiat,
                'currency' => 'EUR',
                'description' => "Crypto sale ({$cryptoCode})",
                'category' => 'crypto',
                'completed_at' => now(),
            ]);

            CryptoOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'order_type' => 'market',
                'side' => 'sell',
                'base_currency' => $cryptoCode,
                'quote_currency' => 'EUR',
                'amount' => $cryptoAmount,
                'filled_amount' => $cryptoAmount,
                'price' => $cryptoPrice,
                'fee' => $fee,
                'fee_rate' => 0.002,
                'total' => $fiatAmount,
                'status' => 'filled',
                'time_in_force' => 'GTC',
                'filled_at' => now(),
            ]);

            return [
                'fiat_amount' => $fiatAmount,
                'net_fiat' => $netFiat,
                'crypto_amount' => $cryptoAmount,
                'crypto_code' => $cryptoCode,
                'price_per_unit' => $cryptoPrice,
                'fee' => $fee,
                'wallet_id' => $walletLocked->id,
            ];
        });
    }

    public function getQuote(string $fromCurrency, string $toCurrency, float $amount): array
    {
        $rate = ExchangeRate::where('base_currency', $fromCurrency)
            ->where('quote_currency', $toCurrency)
            ->firstOrFail();

        $total = $amount * $rate->mid_rate;
        $fee = $total * 0.001;

        return [
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'amount' => $amount,
            'rate' => $rate->mid_rate,
            'total' => $total,
            'fee' => $fee,
            'net_receive' => $total - $fee,
            'last_refreshed' => $rate->last_refreshed_at,
        ];
    }

    public function getWalletBalance(CryptoWallet $wallet): float
    {
        if (!$this->blockchain->isConfigured()) {
            return (float) $wallet->balance;
        }

        try {
            $onChainBalance = $this->blockchain->getBalance($wallet->address);
            return (float) $onChainBalance;
        } catch (\Exception) {
            return (float) $wallet->balance;
        }
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(Str::random(12));
    }

    private function generateReference(string $prefix): string
    {
        return $prefix . '-' . strtoupper(Str::random(10));
    }

    private function toWeiHex(float $amount, int $decimals): string
    {
        $value = BigDecimal::of((string) $amount)
            ->multipliedBy(BigDecimal::of(10)->power($decimals))
            ->toBigInteger();

        return '0x' . $value->toString(16);
    }
}
