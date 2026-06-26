<?php

namespace App\Services;

use App\Models\Banking\Account;
use App\Models\Banking\PosTerminal;
use App\Models\Banking\PosTransaction;
use App\Models\Banking\Transaction;
use App\Models\Banking\ExchangeRate;
use App\Models\Banking\CryptoCurrency;
use App\Models\Banking\CryptoWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class OraclePosIntegrationService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly BlockchainService $blockchainService,
    ) {}

    public function configureTerminal(PosTerminal $terminal, array $config): PosTerminal
    {
        $terminal->update([
            'oracle_terminal_id' => $config['oracle_terminal_id'] ?? $terminal->oracle_terminal_id,
            'crypto_processor_enabled' => $config['crypto_processor_enabled'] ?? $terminal->crypto_processor_enabled,
            'default_crypto_currency' => $config['default_crypto_currency'] ?? $terminal->default_crypto_currency,
            'settlement_mode' => $config['settlement_mode'] ?? $terminal->settlement_mode,
            'oracle_api_url' => $config['oracle_api_url'] ?? $terminal->oracle_api_url,
            'oracle_api_key' => $config['oracle_api_key'] ?? $terminal->oracle_api_key,
        ]);

        return $terminal->fresh();
    }

    public function createOracleCharge(
        string $oracleTerminalId,
        float $amount,
        string $currency,
        ?string $preferredCrypto = null
    ): array {
        $terminal = PosTerminal::where('oracle_terminal_id', $oracleTerminalId)->first();

        if (!$terminal) {
            throw new \RuntimeException("Terminal with Oracle Workstation ID [{$oracleTerminalId}] not found.");
        }

        if ($terminal->status !== 'active') {
            throw new \RuntimeException("POS Terminal is inactive.");
        }

        if ($amount <= 0) {
            throw new \RuntimeException("Amount must be positive.");
        }

        // Determine if we should process this as a crypto payment
        $isCrypto = $preferredCrypto !== null || $terminal->crypto_processor_enabled;
        $cryptoCode = $preferredCrypto ?? $terminal->default_crypto_currency ?? 'USDC';

        return DB::transaction(function () use ($terminal, $amount, $currency, $isCrypto, $cryptoCode) {
            $terminalRef = 'TX-OPOS-' . strtoupper(Str::random(12));

            if (!$isCrypto) {
                // Regular Card Payment Simulation
                $ledgerTransaction = $this->transactionService->deposit(
                    $terminal->account,
                    $amount,
                    'pos_payment',
                    $terminalRef
                );

                $ledgerTransaction->update([
                    'description' => "POS Card Payment (Oracle POS Workstation: {$terminal->oracle_terminal_id})",
                    'category' => 'pos_payment',
                ]);

                $posTransaction = PosTransaction::create([
                    'transaction_id' => $ledgerTransaction->id,
                    'pos_terminal_id' => $terminal->id,
                    'card_brand' => 'Visa',
                    'card_last4' => '1111',
                    'payment_method' => 'contactless',
                    'payment_type' => 'card',
                    'terminal_reference' => $terminalRef,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'completed',
                ]);

                $terminal->update(['last_active_at' => now()]);

                return [
                    'success' => true,
                    'payment_type' => 'card',
                    'reference' => $terminalRef,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'completed',
                ];
            }

            // Crypto Payment Flow
            $cryptoCurrency = CryptoCurrency::where('code', $cryptoCode)->first();
            if (!$cryptoCurrency) {
                throw new \RuntimeException("Cryptocurrency [{$cryptoCode}] is not supported on this platform.");
            }

            // Fetch exchange rate to convert fiat amount to crypto amount
            $rate = ExchangeRate::where('base_currency', $cryptoCode)
                ->where('quote_currency', $currency)
                ->first();

            if (!$rate) {
                if (in_array($cryptoCode, ['USDT', 'USDC'])) {
                    $conversionRate = 1.0;
                } else {
                    throw new \RuntimeException("Exchange rate not found for conversion: {$cryptoCode} to {$currency}.");
                }
            } else {
                $conversionRate = (float) $rate->mid_rate;
            }

            $cryptoAmount = round($amount / $conversionRate, 8);

            // Generate temporary payment receiver address
            $keyPair = $this->blockchainService->generateAddress();
            $paymentAddress = $keyPair['address'];

            $reference = 'OPOS-' . strtoupper(Str::random(10));
            
            $ledgerTransaction = Transaction::create([
                'reference' => $reference,
                'type' => 'deposit',
                'status' => 'pending',
                'credit_account_id' => $terminal->account_id,
                'user_id' => $terminal->user_id,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'currency' => $currency,
                'description' => "POS Crypto Payment (Pending: {$cryptoCode})",
                'category' => 'pos_payment',
                'metadata' => [
                    'pos_type' => 'oracle_crypto',
                    'crypto_currency' => $cryptoCode,
                    'crypto_amount' => $cryptoAmount,
                    'payment_address' => $paymentAddress,
                ],
            ]);

            $posTransaction = PosTransaction::create([
                'transaction_id' => $ledgerTransaction->id,
                'pos_terminal_id' => $terminal->id,
                'card_brand' => 'Crypto',
                'card_last4' => '0000',
                'payment_method' => 'contactless',
                'payment_type' => 'crypto',
                'crypto_currency' => $cryptoCode,
                'crypto_amount' => $cryptoAmount,
                'crypto_address' => $paymentAddress,
                'terminal_reference' => $terminalRef,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
            ]);

            $terminal->update(['last_active_at' => now()]);

            $qrPayload = strtolower($cryptoCode) . ":" . $paymentAddress . "?amount=" . $cryptoAmount . "&label=TyphoonPOS";

            return [
                'success' => true,
                'payment_type' => 'crypto',
                'reference' => $terminalRef,
                'fiat_amount' => $amount,
                'fiat_currency' => $currency,
                'crypto_currency' => $cryptoCode,
                'crypto_amount' => $cryptoAmount,
                'crypto_address' => $paymentAddress,
                'qr_payload' => $qrPayload,
                'status' => 'pending',
            ];
        });
    }

    public function checkChargeStatus(string $reference): array
    {
        $posTransaction = PosTransaction::where('terminal_reference', $reference)->first();

        if (!$posTransaction) {
            throw new \RuntimeException("Transaction with reference [{$reference}] not found.");
        }

        return [
            'reference' => $posTransaction->terminal_reference,
            'status' => $posTransaction->status,
            'amount' => (float) $posTransaction->amount,
            'currency' => $posTransaction->currency,
            'payment_type' => $posTransaction->payment_type,
            'crypto_currency' => $posTransaction->crypto_currency,
            'crypto_amount' => (float) $posTransaction->crypto_amount,
            'crypto_address' => $posTransaction->crypto_address,
            'tx_hash' => $posTransaction->tx_hash,
        ];
    }

    public function simulateCryptoPayment(string $reference, string $txHash): PosTransaction
    {
        $posTransaction = PosTransaction::where('terminal_reference', $reference)
            ->where('payment_type', 'crypto')
            ->first();

        if (!$posTransaction) {
            throw new \RuntimeException("Pending crypto transaction with reference [{$reference}] not found.");
        }

        if ($posTransaction->status === 'completed') {
            return $posTransaction;
        }

        return DB::transaction(function () use ($posTransaction, $txHash) {
            $ledgerTx = $posTransaction->transaction;
            $terminal = $posTransaction->posTerminal;
            $merchant = $terminal->user;

            if ($terminal->settlement_mode === 'crypto') {
                $cryptoCurrency = CryptoCurrency::where('code', $posTransaction->crypto_currency)->firstOrFail();
                $wallet = CryptoWallet::firstOrCreate(
                    ['user_id' => $merchant->id, 'crypto_currency_id' => $cryptoCurrency->id],
                    [
                        'address' => $this->blockchainService->generateAddress()['address'],
                        'private_key' => $this->blockchainService->generateAddress()['private_key'],
                        'balance' => 0,
                        'locked_balance' => 0,
                        'status' => 'active',
                    ]
                );

                $wallet->increment('balance', $posTransaction->crypto_amount);
                $description = "Oracle POS Crypto Settlement ({$posTransaction->crypto_amount} {$posTransaction->crypto_currency})";
            } else {
                $account = $terminal->account;
                $account->increment('balance', $posTransaction->amount);
                $account->increment('available_balance', $posTransaction->amount);
                $account->increment('ledger_balance', $posTransaction->amount);
                $description = "Oracle POS Card/Crypto Payment Settlement (Converted to Fiat)";
            }

            $ledgerTx->update([
                'status' => 'completed',
                'description' => $description,
                'completed_at' => now(),
            ]);

            $posTransaction->update([
                'status' => 'completed',
                'tx_hash' => $txHash,
            ]);

            return $posTransaction->fresh();
        });
    }
}
